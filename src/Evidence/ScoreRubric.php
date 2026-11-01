<?php
declare(strict_types=1);
namespace App\Evidence;
final readonly class ScoreRubric
{
    public const array PENALTIES = [
        'no_test_configuration' => [25, 'testing', 'structure'],
        'no_ci_workflow' => [10, 'ci', 'structure'],
        'vulnerable_dependency' => [30, 'dependencies', 'advisories'],
        'abandoned_dependency' => [5, 'dependencies', 'advisories'],
    ];
    public static function evaluate(EvidenceLog $log): array
    {
        $structure = $log->get('structure')['facts'] ?? [];
        $advisories = $log->get('advisories') ?? [];
        $applies = [
            'no_test_configuration' =>
                ($structure['test_config'] ?? true) === false,
            'no_ci_workflow' => ($structure['ci_workflow'] ?? true)
                === false,
            'vulnerable_dependency' =>
                ($advisories['facts']['advisory_count'] ?? 0) > 0,
            'abandoned_dependency' =>
                ($advisories['facts']['abandoned'] ?? []) !== [],
        ];
        $penalties = [];
        foreach (array_keys(array_filter($applies)) as $rule) {
            [$points, $category, $evidence] = self::PENALTIES[$rule];
            $penalties[] = compact('rule', 'points', 'category',
                'evidence');
        }
        $unavailable = array_values(array_map(
            fn (array $item): string => $item['id'],
            array_filter($log->all(), fn (array $item): bool =>
                $item['status'] === 'unavailable'),
        ));
        return [
            'score' => 100
                - array_sum(array_column($penalties, 'points')),
            'penalties' => $penalties,
            'unavailable' => $unavailable,
        ];
    }
}
