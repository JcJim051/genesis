<?php

namespace App\Services\Ipt;

use App\Models\IptTemplate;

class IptScoringService
{
    /**
     * @param array<int|string, string|null> $answersByQuestionId
     * @return array{total:int,answers:array<int,array{question_id:int,respuesta:string|null,score:int}>,risk:?array{nivel:string,followup_months:int,min_score:int,max_score:int}}
     */
    public function evaluate(IptTemplate $template, array $answersByQuestionId): array
    {
        $total = 0;
        $normalizedAnswers = [];

        $questions = $template->sections
            ->flatMap(fn ($section) => $section->questions)
            ->sortBy('orden')
            ->values();

        foreach ($questions as $question) {
            $raw = $answersByQuestionId[$question->id] ?? null;
            $respuesta = $raw ? strtolower(trim((string) $raw)) : null;
            $score = 0;

            $scoreOnAnswer = strtolower((string) ($question->score_on_answer ?? 'si'));
            if (! in_array($scoreOnAnswer, ['si', 'no'], true)) {
                $scoreOnAnswer = 'si';
            }

            if ($question->scorable && $respuesta === $scoreOnAnswer) {
                $score = (int) ($question->si_score ?? 1);
            }

            $total += $score;
            $normalizedAnswers[] = [
                'question_id' => (int) $question->id,
                'respuesta' => $respuesta,
                'score' => $score,
            ];
        }

        $riskRule = $template->riskRules
            ->first(function ($rule) use ($total) {
                return $total >= (int) $rule->min_score && $total <= (int) $rule->max_score;
            });

        return [
            'total' => $total,
            'answers' => $normalizedAnswers,
            'risk' => $riskRule ? [
                'nivel' => (string) $riskRule->nivel,
                'followup_months' => (int) $riskRule->followup_months,
                'min_score' => (int) $riskRule->min_score,
                'max_score' => (int) $riskRule->max_score,
            ] : null,
        ];
    }
}
