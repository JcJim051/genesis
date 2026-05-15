<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\OsteoTemplate;
use App\Models\OsteoTemplateField;
use App\Models\OsteoTemplateSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OsteoSeedGobernacionTemplate extends Command
{
    protected $signature = 'osteo:seed-gobernacion {--replace : Reemplaza plantilla existente}';
    protected $description = 'Crea la plantilla osteomuscular de Gobernación del Meta basada en el formato entregado';

    public function handle(): int
    {
        $cliente = Cliente::query()
            ->whereRaw('LOWER(nombre) LIKE ?', ['%gobernacion%meta%'])
            ->first();

        if (! $cliente) {
            $this->error('No encontré la empresa Gobernación del Meta.');
            return self::FAILURE;
        }

        $replace = (bool) $this->option('replace');
        $existing = OsteoTemplate::query()
            ->where('cliente_id', $cliente->id)
            ->where('codigo', 'OSTEO-GOBMETA-V1')
            ->first();

        if ($existing && ! $replace) {
            $this->warn("Ya existe la plantilla para {$cliente->nombre}. Usa --replace para recrearla.");
            return self::SUCCESS;
        }

        DB::transaction(function () use ($existing, $replace, $cliente): void {
            if ($existing && $replace) {
                $existing->delete();
            }

            $template = OsteoTemplate::create([
                'cliente_id' => $cliente->id,
                'nombre_publico' => 'FORMATO EVALUACIÓN OSTEOMUSCULAR',
                'codigo' => 'OSTEO-GOBMETA-V1',
                'segmento' => 'General',
                'activo' => true,
            ]);

            foreach ($this->definition() as $sIndex => $sectionDef) {
                $section = OsteoTemplateSection::create([
                    'template_id' => $template->id,
                    'titulo' => $sectionDef['titulo'],
                    'orden' => ($sIndex + 1) * 10,
                ]);

                foreach ($sectionDef['fields'] as $fIndex => $fieldDef) {
                    OsteoTemplateField::create([
                        'section_id' => $section->id,
                        'key_name' => ($fieldDef['key_name'] ?? null) ?: $this->slugKey($fieldDef['label']),
                        'label' => $fieldDef['label'],
                        'tipo' => $fieldDef['tipo'],
                        'options_json' => $fieldDef['options'] ?? null,
                        'meta_json' => $fieldDef['meta'] ?? null,
                        'required' => (bool) ($fieldDef['required'] ?? false),
                        'orden' => ($fIndex + 1) * 10,
                    ]);
                }
            }
        });

        $this->info("Plantilla osteomuscular creada para {$cliente->nombre}.");
        return self::SUCCESS;
    }

    private function slugKey(string $label): string
    {
        return (string) Str::of($label)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_');
    }

    private function definition(): array
    {
        return [
            [
                'titulo' => 'Datos generales',
                'fields' => [
                    ['label' => 'Ciudad', 'tipo' => 'text', 'required' => true],
                    ['label' => 'Área funcional', 'tipo' => 'text', 'required' => true],
                    ['label' => 'Nombre del trabajador', 'tipo' => 'text', 'required' => true],
                    ['label' => 'Documento de identificación', 'tipo' => 'text', 'required' => true],
                    ['label' => 'Sexo', 'tipo' => 'select', 'options' => ['Femenino', 'Masculino']],
                    ['label' => 'Antigüedad en la entidad', 'tipo' => 'text'],
                    ['label' => 'Evaluador', 'tipo' => 'text'],
                    ['label' => 'N° licencia', 'tipo' => 'text'],
                    ['label' => 'Cargo / Profesional', 'tipo' => 'text'],
                ],
            ],
            [
                'titulo' => 'Antecedentes generales osteomusculares',
                'fields' => [
                    ['label' => 'Patológicos', 'tipo' => 'textarea'],
                    ['label' => 'Medicamentos', 'tipo' => 'textarea'],
                    ['label' => 'Quirúrgicos', 'tipo' => 'textarea'],
                ],
            ],
            [
                'titulo' => 'Carga física y hábitos',
                'fields' => [
                    ['label' => 'Manipulación de cargas (kg)', 'tipo' => 'select', 'options' => ['No realiza', 'Menor 12', '13 - 24', '25 - 50', 'Mayor 50']],
                    ['label' => 'Postura habitual', 'tipo' => 'select', 'options' => ['Sentado', 'De pie', 'Mixta']],
                    ['label' => 'Actividad física', 'tipo' => 'select', 'options' => ['SI', 'NO']],
                    ['label' => '¿Cuál actividad física?', 'tipo' => 'text'],
                    ['label' => 'Frecuencia actividad física', 'tipo' => 'select', 'options' => ['1 vez/semana', '2 veces/semana', '3 veces o más/semana']],
                    ['label' => 'Zona sintomatología', 'tipo' => 'text'],
                    ['label' => 'Frecuencia sintomatología', 'tipo' => 'select', 'options' => ['Ocasional', 'Frecuente', 'Constante']],
                ],
            ],
            [
                'titulo' => 'Fuerza muscular y movilidad - Miembro superior',
                'fields' => [
                    ['label' => 'Hombro flexión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Hombro extensión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Hombro abducción', 'tipo' => 'laterality_pair'],
                    ['label' => 'Hombro rotación interna', 'tipo' => 'laterality_pair'],
                    ['label' => 'Hombro rotación externa', 'tipo' => 'laterality_pair'],
                    ['label' => 'Codo flexión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Codo extensión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Codo pronación', 'tipo' => 'laterality_pair'],
                    ['label' => 'Codo supinación', 'tipo' => 'laterality_pair'],
                    ['label' => 'Muñeca flexión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Muñeca extensión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Muñeca desviación radial', 'tipo' => 'laterality_pair'],
                    ['label' => 'Muñeca desviación cubital', 'tipo' => 'laterality_pair'],
                    ['label' => 'Dedos flexión 1° dedo', 'tipo' => 'laterality_pair'],
                    ['label' => 'Dedos flexión (FD)', 'tipo' => 'laterality_pair'],
                    ['label' => 'Dedos flexión (FP)', 'tipo' => 'laterality_pair'],
                    ['label' => 'Dedos extensión (FD)', 'tipo' => 'laterality_pair'],
                    ['label' => 'Dedos extensión (FP)', 'tipo' => 'laterality_pair'],
                    ['label' => 'Dedos aducción', 'tipo' => 'laterality_pair'],
                    ['label' => 'Dedos abducción', 'tipo' => 'laterality_pair'],
                    ['label' => 'Observaciones miembro superior', 'tipo' => 'textarea'],
                ],
            ],
            [
                'titulo' => 'Fuerza muscular y movilidad - Miembro inferior',
                'fields' => [
                    ['label' => 'Cadera flexión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Cadera extensión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Cadera abducción', 'tipo' => 'laterality_pair'],
                    ['label' => 'Cadera aducción', 'tipo' => 'laterality_pair'],
                    ['label' => 'Rodilla flexión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Rodilla extensión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Rodilla desviación radial', 'tipo' => 'laterality_pair'],
                    ['label' => 'Tobillo flexión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Tobillo extensión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Tobillo inversión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Tobillo eversión', 'tipo' => 'laterality_pair'],
                    ['label' => 'Observaciones miembro inferior', 'tipo' => 'textarea'],
                ],
            ],
            [
                'titulo' => 'Pruebas funcionales (+/-)',
                'fields' => [
                    ['label' => 'Schober lumbar', 'tipo' => 'plus_minus_pair'],
                    ['label' => 'Lasegue', 'tipo' => 'plus_minus_pair'],
                    ['label' => 'Trendelenburg', 'tipo' => 'plus_minus_pair'],
                    ['label' => 'Compresión patelar', 'tipo' => 'plus_minus_pair'],
                    ['label' => 'McMurray', 'tipo' => 'plus_minus_pair'],
                    ['label' => 'Cajón posterior', 'tipo' => 'plus_minus_pair'],
                    ['label' => 'Cajón anterior', 'tipo' => 'plus_minus_pair'],
                    ['label' => 'Bostezo varo', 'tipo' => 'plus_minus_pair'],
                    ['label' => 'Bostezo valgo', 'tipo' => 'plus_minus_pair'],
                ],
            ],
            [
                'titulo' => 'Plan de intervención y recomendaciones',
                'fields' => [
                    ['label' => 'Alineación postural', 'tipo' => 'textarea'],
                    ['label' => 'Asignación de actividades', 'tipo' => 'textarea'],
                    ['label' => 'Inspección de puesto de trabajo', 'tipo' => 'textarea'],
                    ['label' => 'Escuela terapéutica', 'tipo' => 'textarea'],
                    ['label' => 'Recomendaciones físicas', 'tipo' => 'textarea'],
                    ['label' => 'Recomendaciones al puesto de trabajo', 'tipo' => 'textarea'],
                    ['label' => 'Valoración EPS', 'tipo' => 'textarea'],
                    ['label' => 'Otro', 'tipo' => 'textarea'],
                ],
            ],
        ];
    }
}
