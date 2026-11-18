<?php
declare(strict_types=1);
namespace App\Tools;
use App\Evidence\EvidenceLog;
use NeuronAI\Tools\Tool;
final class ComposerManifestTool extends Tool
{
    protected string $name = 'read_composer_manifest';
    protected ?string $description = 'Call this first: declared '
        . 'requirements, autoload types and script names.';
    public function __construct(
        private readonly string $root,
        private readonly EvidenceLog $log,
    ) {
    }
    public function __invoke(): string
    {
        $file = "{$this->root}/composer.json";
        if (!is_file($file) || !is_readable($file)) {
            return $this->log->unavailable('manifest',
                'no readable composer.json');
        }
        if (filesize($file) > 65536) {
            return $this->log->unavailable('manifest',
                'composer.json is over 64 KB');
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data)) {
            return $this->log->unavailable('manifest',
                'composer.json is not valid JSON');
        }
        return $this->log->record('manifest', 'ok', [
            'name' => $data['name'] ?? null,
            'php_constraint' => $data['require']['php'] ?? null,
            'require' => $data['require'] ?? [],
            'require_dev' => $data['require-dev'] ?? [],
            'autoload' => array_keys($data['autoload'] ?? []),
            'scripts' => array_keys($data['scripts'] ?? []),
        ]);
    }
}
