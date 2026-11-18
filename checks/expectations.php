<?php
declare(strict_types=1);
return [
    'app' => ['score' => 70, 'unavailable' => [],
        'findings' => ['dependencies' => 'advisories']],
    'library' => ['score' => 90, 'unavailable' => [],
        'findings' => ['ci' => 'structure']],
    'legacy' => ['score' => 60, 'unavailable' => [],
        'findings' => ['testing' => 'structure', 'ci' => 'structure',
            'dependencies' => 'advisories']],
    'no-manifest' => ['score' => 65,
        'unavailable' => ['advisories', 'manifest'],
        'findings' => ['testing' => 'structure', 'ci' => 'structure']],
    'no-history' => ['score' => 65,
        'unavailable' => ['advisories', 'history'],
        'findings' => ['testing' => 'structure', 'ci' => 'structure']],
];
