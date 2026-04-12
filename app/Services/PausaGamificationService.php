<?php

namespace App\Services;

use App\Models\EmpleadoPausaStat;
use App\Models\PausaBadge;
use App\Models\PausaParticipacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class PausaGamificationService
{
    public const POINTS_PER_COMPLETION = 10;
    public const WEEKLY_TARGET = 3;

    public function awardForCompletion(PausaParticipacion $participacion): array
    {
        $empleadoId = $participacion->empleado_id;
        $completedAt = $participacion->respondido_en ?? $participacion->updated_at ?? now();

        return DB::transaction(function () use ($empleadoId, $completedAt) {
            $stats = EmpleadoPausaStat::firstOrCreate(
                ['empleado_id' => $empleadoId],
                [
                    'total_points' => 0,
                    'total_completadas' => 0,
                    'current_streak_weeks' => 0,
                    'best_streak_weeks' => 0,
                    'current_week_key' => null,
                    'current_week_count' => 0,
                ]
            );

            $prevWeekKey = $stats->current_week_key;
            $prevCount = (int) $stats->current_week_count;
            $weekKey = $this->weekKey($completedAt);

            if ($prevWeekKey === $weekKey) {
                $newCount = $prevCount + 1;
            } else {
                $newCount = 1;
            }

            $stats->total_points += self::POINTS_PER_COMPLETION;
            $stats->total_completadas += 1;
            $stats->current_week_key = $weekKey;
            $stats->current_week_count = $newCount;
            $stats->last_completed_at = $completedAt;

            $justCompletedWeek = $newCount >= self::WEEKLY_TARGET && ($prevWeekKey !== $weekKey || $prevCount < self::WEEKLY_TARGET);
            if ($justCompletedWeek) {
                $shouldExtend = $prevWeekKey
                    && $prevCount >= self::WEEKLY_TARGET
                    && $this->isPreviousWeek($prevWeekKey, $weekKey);

                $stats->current_streak_weeks = $shouldExtend ? ($stats->current_streak_weeks + 1) : 1;
                $stats->best_streak_weeks = max((int) $stats->best_streak_weeks, (int) $stats->current_streak_weeks);
            }

            $stats->save();

            $awardedBadges = $this->grantBadges($empleadoId, $stats);

            return [
                'stats' => $stats->fresh(),
                'awarded' => $awardedBadges,
            ];
        });
    }

    public function recalculateAll(): void
    {
        DB::transaction(function () {
            DB::table('empleado_pausa_badges')->delete();
            EmpleadoPausaStat::query()->delete();

            PausaParticipacion::query()
                ->where('estado', 'completada')
                ->orderBy('respondido_en')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $participacion) {
                        $this->awardForCompletion($participacion);
                    }
                });
        });
    }

    protected function grantBadges(int $empleadoId, EmpleadoPausaStat $stats): Collection
    {
        $codesToCheck = [];

        if ($stats->total_completadas >= 1) {
            $codesToCheck[] = 'first_pause';
        }
        if ($stats->total_completadas >= 10) {
            $codesToCheck[] = 'ten_pauses';
        }
        if ($stats->current_streak_weeks >= 4) {
            $codesToCheck[] = 'streak_4w';
        }
        if ($stats->current_streak_weeks >= 12) {
            $codesToCheck[] = 'streak_12w';
        }

        if (empty($codesToCheck)) {
            return collect();
        }

        $badges = PausaBadge::query()
            ->whereIn('code', $codesToCheck)
            ->where('activo', true)
            ->get()
            ->keyBy('code');

        $existing = DB::table('empleado_pausa_badges')
            ->where('empleado_id', $empleadoId)
            ->pluck('badge_id')
            ->all();

        $awarded = collect();
        foreach ($badges as $badge) {
            if (in_array($badge->id, $existing, true)) {
                continue;
            }
            DB::table('empleado_pausa_badges')->insert([
                'empleado_id' => $empleadoId,
                'badge_id' => $badge->id,
                'awarded_at' => now(),
            ]);
            $awarded->push($badge);
        }

        return $awarded;
    }

    protected function weekKey($date): string
    {
        $dt = $date instanceof Carbon ? $date : Carbon::parse($date);
        $year = $dt->isoWeekYear;
        $week = $dt->isoWeek;

        return sprintf('%04d-%02d', $year, $week);
    }

    protected function isPreviousWeek(string $prevWeekKey, string $currentWeekKey): bool
    {
        [$prevYear, $prevWeek] = array_map('intval', explode('-', $prevWeekKey));
        [$currYear, $currWeek] = array_map('intval', explode('-', $currentWeekKey));

        $prev = Carbon::now()->setISODate($prevYear, $prevWeek)->startOfWeek();
        $curr = Carbon::now()->setISODate($currYear, $currWeek)->startOfWeek();

        return $prev->addWeek()->equalTo($curr);
    }
}
