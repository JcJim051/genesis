<?php

namespace App\Services\Ipt;

use Illuminate\Support\Collection;

/**
 * Visibility rules for IPT inspection screens built from a template.
 *
 * A template section with zero items (questions, requirements, …)
 * must not render as an empty header/box on fill, show, or PDF.
 */
class IptFormLayout
{
    /**
     * @param  object{questions?: mixed}  $section
     */
    public static function sectionQuestions(object $section): Collection
    {
        return self::asCollection($section->questions ?? []);
    }

    /**
     * @param  object{questions?: mixed}  $section
     */
    public static function sectionIsVisible(object $section): bool
    {
        return self::sectionQuestions($section)->isNotEmpty();
    }

    /**
     * @param  object{sections?: mixed}  $template
     */
    public static function visibleQuestionSections(object $template): Collection
    {
        return self::asCollection($template->sections ?? [])
            ->filter(fn (object $section): bool => self::sectionIsVisible($section))
            ->values();
    }

    /**
     * @param  object{sections?: mixed}  $template
     */
    public static function hasVisibleQuestionSections(object $template): bool
    {
        return self::visibleQuestionSections($template)->isNotEmpty();
    }

    /**
     * Active station requirements configured on the template.
     *
     * @param  object{requirements?: mixed}  $template
     */
    public static function activeRequirements(object $template): Collection
    {
        return self::asCollection($template->requirements ?? [])
            ->filter(function (mixed $requirement): bool {
                $activo = is_object($requirement)
                    ? ($requirement->activo ?? false)
                    : (is_array($requirement) ? ($requirement['activo'] ?? false) : false);

                return $activo === true || $activo === 1 || $activo === '1';
            })
            ->values();
    }

    /**
     * @param  object{requirements?: mixed}  $template
     */
    public static function requirementsSectionIsVisible(object $template): bool
    {
        return self::activeRequirements($template)->isNotEmpty();
    }

    /**
     * Template capture-field flags default ON so existing templates keep current behavior.
     *
     * @param  object  $template
     */
    public static function showsHallazgos(object $template): bool
    {
        return self::templateFlag($template, 'mostrar_hallazgos');
    }

    /**
     * Observaciones shares the `hallazgos` textarea on the inspection form.
     *
     * @param  object  $template
     */
    public static function showsObservaciones(object $template): bool
    {
        return self::templateFlag($template, 'mostrar_observaciones');
    }

    /**
     * Combined “Hallazgos / observaciones” input is shown if either flag is on.
     *
     * @param  object  $template
     */
    public static function showsHallazgosObservacionesField(object $template): bool
    {
        return self::showsHallazgos($template) || self::showsObservaciones($template);
    }

    /**
     * @param  object  $template
     */
    public static function hallazgosObservacionesLabel(object $template): string
    {
        $hallazgos = self::showsHallazgos($template);
        $observaciones = self::showsObservaciones($template);

        if ($hallazgos && $observaciones) {
            return 'Hallazgos / observaciones';
        }

        if ($hallazgos) {
            return 'Hallazgos';
        }

        if ($observaciones) {
            return 'Observaciones';
        }

        return 'Hallazgos / observaciones';
    }

    /**
     * @param  object  $template
     */
    public static function showsRecomendaciones(object $template): bool
    {
        return self::templateFlag($template, 'mostrar_recomendaciones');
    }

    /**
     * @param  object  $template
     */
    public static function showsAccion(object $template): bool
    {
        return self::templateFlag($template, 'mostrar_accion');
    }

    /**
     * @param  object  $template
     */
    public static function showsResponsable(object $template): bool
    {
        return self::templateFlag($template, 'mostrar_responsable');
    }

    /**
     * @param  object  $template
     */
    public static function showsEstado(object $template): bool
    {
        return self::templateFlag($template, 'mostrar_estado');
    }

    /**
     * Notes/plan block on fill, show, and PDF (excludes estado, which lives in meta).
     *
     * @param  object  $template
     */
    public static function captureNotesSectionIsVisible(object $template): bool
    {
        return self::showsHallazgosObservacionesField($template)
            || self::showsRecomendaciones($template)
            || self::showsAccion($template)
            || self::showsResponsable($template);
    }

    /**
     * Fill-form row that also includes estado and optional follow-up success.
     *
     * @param  object  $template
     */
    public static function captureFieldsRowIsVisible(object $template, bool $includeFollowup = false): bool
    {
        return self::captureNotesSectionIsVisible($template)
            || self::showsEstado($template)
            || $includeFollowup;
    }

    /**
     * @param  object  $template
     */
    private static function templateFlag(object $template, string $attribute, bool $default = true): bool
    {
        $value = $template->{$attribute} ?? $default;
        if ($value === null) {
            return $default;
        }

        return $value === true || $value === 1 || $value === '1';
    }

    private static function asCollection(mixed $items): Collection
    {
        if ($items instanceof Collection) {
            return $items;
        }

        return collect($items ?? []);
    }
}
