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

    private static function asCollection(mixed $items): Collection
    {
        if ($items instanceof Collection) {
            return $items;
        }

        return collect($items ?? []);
    }
}
