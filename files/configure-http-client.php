<?php
declare(strict_types=1);
// Off camera only: configure private authentication, never print it.
require __DIR__ . '/../vendor/autoload.php';
use App\Config\Provider;
use App\Config\Settings;
$root = dirname(__DIR__);
$settings = require __DIR__ . '/bootstrap.php';
if ($settings->provider !== Provider::Anthropic) {
    throw new RuntimeException('Set NEURON_PROVIDER=anthropic first.');
}
$key = $settings->key ?? throw new RuntimeException('Key missing.');
$target = $root . '/http-client.private.env.json';
file_put_contents($target, json_encode(
    ['studio' => ['apiKey' => $key]],
    JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT,
));
chmod($target, 0600);
$directory = $root . '/.idea/runConfigurations';
if (!is_dir($directory)) {
    mkdir($directory, 0700, true);
}
$xml = <<<'XML'
<component name="ProjectRunConfigurationManager">
  <configuration default="false" name="Upload teaching invoice"
      type="HttpClient.HttpRequestRunConfigurationType"
      factoryName="HTTP Request" environment="studio"
      path="$PROJECT_DIR$/requests/upload-invoice.http" index="1">
    <method v="2" />
  </configuration>
</component>
XML;
file_put_contents($directory . '/Upload_teaching_invoice.xml', $xml);
$workspace = $root . '/.idea/workspace.xml';
if (isset($argv[1])) {
    $candidates = glob($argv[1] . '/*.xml') ?: [];
    usort($candidates, fn ($a, $b) => filemtime($b) <=> filemtime($a));
    $workspace = $candidates[0] ?? $workspace;
}
if (is_file($workspace)) {
    $doc = new DOMDocument();
    $doc->load($workspace);
    $xpath = new DOMXPath($doc);
    $manager = $xpath->query('/project/component[@name="RunManager"]')->item(0);
    if ($manager === null) {
        $manager = $doc->createElement('component');
        $manager->setAttribute('name', 'RunManager');
        $doc->documentElement->appendChild($manager);
    }
    $manager->setAttribute('selected',
        'HttpClient.HttpRequestRunConfigurationType.Upload teaching invoice');
    $doc->save($workspace);
}
echo 'HTTP CLIENT CONFIGURED' . PHP_EOL;
