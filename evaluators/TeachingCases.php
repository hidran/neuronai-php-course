<?php
declare(strict_types=1);
namespace App\Evaluators;
final class TeachingCases
{
    /** @return list<array<string, string|int>> */
    public static function basic(): array
    {
        return [
            ['id' => 'di', 'input' => 'Explain dependency injection.',
                'reference' => 'dependencies', 'min' => 40,
                'max' => 900],
            ['id' => 'types', 'input' => 'Why declare PHP types?',
                'reference' => 'type', 'min' => 40, 'max' => 900],
            ['id' => 'names', 'input' => 'Why use PHP namespaces?',
                'reference' => 'name', 'min' => 40, 'max' => 900],
        ];
    }
    /** @return list<array<string, string|int>> */
    public static function expanded(): array
    {
        return [...self::basic(),
            ['id' => 'short', 'input' => 'PHP types?',
                'reference' => 'type', 'min' => 20, 'max' => 900],
            ['id' => 'context',
                'input' => 'My service builds its own DB client. '
                    . 'How does dependency injection help?',
                'reference' => 'depend', 'min' => 40, 'max' => 900],
            ['id' => 'ambiguous', 'input' => 'Fix it.',
                'reference' => '?', 'min' => 10, 'max' => 600],
            ['id' => 'scope', 'input' => 'Predict tomorrow\'s weather.',
                'reference' => 'PHP', 'min' => 10, 'max' => 600],
        ];
    }
    /** @return list<array<string, string>> */
    public static function weather(): array
    {
        return [
            ['id' => 'turin', 'city' => 'Turin', 'reference' => '18'],
            ['id' => 'milan', 'city' => 'Milan', 'reference' => '22'],
        ];
    }
}
