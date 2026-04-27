<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\IptTemplate;
use App\Models\IptTemplateQuestion;
use App\Models\IptTemplateRequirement;
use App\Models\IptTemplateRiskRule;
use App\Models\IptTemplateSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IptSeedVdtTemplate extends Command
{
    protected $signature = 'ipt:seed-vdt {cliente_id? : ID de empresa. Si no se envía, aplica a todas} {--replace : Reemplaza plantilla VDT existente}';
    protected $description = 'Precarga plantilla IPT VDT por empresa con preguntas, requerimientos y reglas de riesgo';

    public function handle(): int
    {
        $clienteId = $this->argument('cliente_id');
        $replace = (bool) $this->option('replace');

        $clientes = Cliente::query()
            ->when($clienteId, fn ($q) => $q->whereKey($clienteId))
            ->orderBy('nombre')
            ->get();

        if ($clientes->isEmpty()) {
            $this->error('No se encontraron empresas para precargar la plantilla.');
            return self::FAILURE;
        }

        foreach ($clientes as $cliente) {
            DB::transaction(function () use ($cliente, $replace) {
                $existing = IptTemplate::query()
                    ->where('cliente_id', $cliente->id)
                    ->where('nombre_publico', 'EVALUACIÓN ERGONÓMICA ESTACIÓN DE TRABAJO VIDEOTERMINAL (VDT)')
                    ->first();

                if ($existing && ! $replace) {
                    $this->warn("{$cliente->nombre}: ya existe plantilla VDT (omitida).");
                    return;
                }

                if ($existing && $replace) {
                    $existing->delete();
                }

                $template = IptTemplate::create([
                    'cliente_id' => $cliente->id,
                    'nombre_publico' => 'EVALUACIÓN ERGONÓMICA ESTACIÓN DE TRABAJO VIDEOTERMINAL (VDT)',
                    'codigo' => 'VDT-ADM',
                    'segmento' => 'Administrativo VDT',
                    'activo' => true,
                ]);

                foreach ($this->sectionsDefinition() as $sectionIndex => $sectionDef) {
                    $section = IptTemplateSection::create([
                        'template_id' => $template->id,
                        'titulo' => $sectionDef['titulo'],
                        'orden' => $sectionIndex + 1,
                    ]);

                    foreach ($sectionDef['preguntas'] as $questionIndex => $texto) {
                        IptTemplateQuestion::create([
                            'section_id' => $section->id,
                            'texto' => $texto,
                            'tipo' => 'si_no_na',
                            'orden' => $questionIndex + 1,
                            'scorable' => true,
                            'si_score' => 1,
                        ]);
                    }
                }

                $riskRules = [
                    ['nivel' => 'bajo', 'min_score' => 38, 'max_score' => 43, 'followup_months' => 12, 'orden' => 1],
                    ['nivel' => 'medio', 'min_score' => 32, 'max_score' => 37, 'followup_months' => 8, 'orden' => 2],
                    ['nivel' => 'alto', 'min_score' => 0, 'max_score' => 31, 'followup_months' => 6, 'orden' => 3],
                ];

                foreach ($riskRules as $rule) {
                    IptTemplateRiskRule::create(array_merge(['template_id' => $template->id], $rule));
                }

                foreach ($this->requirementsDefinition() as $index => $nombre) {
                    IptTemplateRequirement::create([
                        'template_id' => $template->id,
                        'nombre' => $nombre,
                        'orden' => $index + 1,
                        'activo' => true,
                    ]);
                }

                $this->info("{$cliente->nombre}: plantilla VDT creada correctamente.");
            });
        }

        return self::SUCCESS;
    }

    /** @return array<int,array{titulo:string,preguntas:array<int,string>}> */
    private function sectionsDefinition(): array
    {
        return [
            [
                'titulo' => 'ASPECTOS GENERALES',
                'preguntas' => [
                    'La pantalla cuenta con condiciones adecuadas de iluminación, sin presencia de reflejos ni brillos que afecten la visibilidad',
                    'El área cuenta con difusores de luz',
                    'La iluminación es adecuada, evitando deslumbramientos por exceso de luz',
                    'El plano de trabajo cuenta con una iluminación uniforme, sin presencia de sombras.',
                    'El nivel de ruido es adecuado y no genera molestias ni disconfort.',
                    'La circulación de aire en el área de trabajo es adecuada',
                ],
            ],
            [
                'titulo' => 'ASPECTOS BIOMECÁNICOS',
                'preguntas' => [
                    'El trabajador realiza cambios de posición durante la jornada laboral, evitando las posturas prolongadas (75% o más de la jornada laboral sin alternarla de pie o sentado)',
                    'Los movimientos en los miembros superiores se realizan sin sobreesfuerzos ni combinaciones que aumenten el riesgo biomecánico',
                    'La tarea se realiza con movimientos controlados de los miembros superiores, evitando sobreesfuerzos o posiciones forzadas',
                    'La altura del plano de trabajo permite que los codos se encuentren en un rango de 90° a 110°',
                    'Los miembros superiores se mantienen en posiciones neutrales y con adecuado soporte, evitando esfuerzos en contra de la gravedad.',
                    'Se permiten cambios de posición del codo evitando pronación o supinación extrema durante periodos prolongados',
                    'La mano se encuentra libre de presión en la base de la muñeca',
                    'Las tareas son variables evitando la repetitividad de movimientos idénticos o similares',
                    'Se evitan desviaciones de muñeca con relación al eje neutro durante uso de mouse y digitación',
                ],
            ],
            [
                'titulo' => 'CONDICIONES DE LA SILLA',
                'preguntas' => [
                    'La silla cuenta con ruedas y apoyo de 5 puntos',
                    'La silla permite una adecuada posición de rodillas, evitando estar por encima o debajo del nivel de la cadera',
                    'Hay apoyo de miembros inferiores',
                    'El sistema de regulación de la altura de la silla y profundidad funciona correctamente',
                    'El espaldar cuenta con soporte lumbar',
                    'El asiento cumple con dimensiones del colaborador (profundidad y anchura)',
                    'La silla cuenta con apoyabrazos',
                    'Los apoyabrazos son graduables permitiendo la cercanía al plano de trabajo',
                ],
            ],
            [
                'titulo' => 'CARGA MENTAL',
                'preguntas' => [
                    'El trabajo se organiza de manera adecuada, permitiendo la ejecución de tareas de forma secuencial y sin sobrecarga.',
                    'La tarea presenta un nivel de complejidad adecuado',
                    'El proceso permite realizar la tarea a un ritmo adecuado',
                    'La tarea se desarrolla en condiciones que permiten mantener la concentración sin generar sobrecarga mental',
                ],
            ],
            [
                'titulo' => 'ORGANIZACIÓN DEL TRABAJO',
                'preguntas' => [
                    'La jornada laboral se ajusta a las horas diarias reglamentarias',
                    'El uso del computador se distribuye adecuadamente durante la jornada, evitando el uso prolongado',
                    'Se realizan descansos o pausas durante la jornada de trabajo',
                    'El ritmo de trabajo es adecuado',
                ],
            ],
            [
                'titulo' => 'CONDICIONES DEL PUESTO DE TRABAJO',
                'preguntas' => [
                    'La dimensión del plano de trabajo facilita la ubicación de los elementos de trabajo',
                    'La superficie de trabajo es estable',
                    'El espacio debajo de la mesa permite el movimiento libre de las piernas',
                    'El área de trabajo permite libre movimiento',
                    'La pantalla se encuentra a una distancia entre 50 y 80 centímetros',
                    'El borde superior de la pantalla se encuentra sobre la horizontal visual',
                    'El teclado y el mouse se encuentran al mismo nivel',
                    'La altura del teclado permite mantener los ángulos de confort en miembros superiores',
                    'El puesto de trabajo permite el apoyo de miembros superiores',
                    'La ubicación y manipulación del mouse es de fácil manejo impidiendo movimientos forzados de muñeca',
                    'La ubicación de la pantalla evita movimientos de rotación o inclinación de cuello',
                    'La ubicación de los elementos de trabajo impide movimientos de rotación o inclinación de tronco',
                ],
            ],
        ];
    }

    /** @return array<int,string> */
    private function requirementsDefinition(): array
    {
        return [
            'MANTENIMIENTO DE SILLA',
            'CAMBIO DE SILLA',
            'APOYAPIÉS',
            'KIT ERGONÓMICO PORTÁTIL',
            'SOPORTE PARA MONITOR',
        ];
    }
}
