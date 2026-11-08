<?php
declare(strict_types=1);
// Where did the installer put the NeuronAI skills, and do they still
// match the skills shipped by the package we have installed?
$root = dirname(__DIR__);
$vendor = $root . '/vendor/neuron-core/neuron-ai/skills';
$checked = 0;
$paths = glob($root . '/.*/skills/neuron-*') ?: [];
foreach ($paths as $path) {
    $name = basename($path);
    $where = substr($path, strlen($root) + 1);
    $kind = is_link($path) ? 'link to ' . readlink($path) : 'copy';
    $same = is_file("{$path}/SKILL.md")
        && sha1_file("{$path}/SKILL.md")
            === sha1_file("{$vendor}/{$name}/SKILL.md");
    echo "{$where}: {$kind}, "
        . ($same ? 'same as vendor' : 'DIFFERENT from vendor')
        . PHP_EOL;
    $checked++;
}
echo "Skill targets checked: {$checked}" . PHP_EOL;
