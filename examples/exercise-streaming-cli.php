<?php
declare(strict_types=1);
$prompt = 'Explain when a PHP interface is useful in testing.';
$command = [PHP_BINARY, __DIR__ . '/40-streaming-cli.php', $prompt];
$process = proc_open($command, [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => STDERR,
], $pipes);
if (!is_resource($process)) {
    throw new RuntimeException('Could not start the CLI runner.');
}
fclose($pipes[0]);
$output = '';
$observed = null;
$started = hrtime(true);
$header = 'You: ' . $prompt . PHP_EOL . 'Assistant: ';
while (!feof($pipes[1])) {
    $part = fread($pipes[1], 8192);
    if ($part === false) {
        throw new RuntimeException('Could not read the CLI output.');
    }
    $output .= $part;
    echo $part;
    flush();
    if (str_starts_with($output, $header)
        && strlen($output) > strlen($header)
        && !str_contains($output, "\n\nFirst text: ")
    ) {
        $observed ??= hrtime(true);
    }
}
$finished = hrtime(true);
fclose($pipes[1]);
$exit = proc_close($process);
if ($exit !== 0) {
    throw new RuntimeException('The CLI runner failed.');
}
$pattern = '~\A' . preg_quote($header, '~')
    . '(?<reply>.*?)\n\nFirst text: (?<first>[0-9]+\.[0-9]{2})s'
    . ' \| Total: (?<total>[0-9]+\.[0-9]{2})s\n'
    . 'Final text: (?<chars>[0-9]+) characters\n'
    . 'CLI stream complete\.\n\z~s';
if (preg_match($pattern, $output, $matches) !== 1) {
    throw new RuntimeException('CLI labels or metrics differ.');
}
if ($matches['reply'] === ''
    || mb_strlen($matches['reply']) !== (int) $matches['chars']
) {
    throw new RuntimeException('Rendered and final lengths differ.');
}
if ((float) $matches['first'] > (float) $matches['total']) {
    throw new RuntimeException('First text follows completion.');
}
if ($observed === null || $observed < $started
    || $observed >= $finished
) {
    throw new RuntimeException('No text observed before completion.');
}
echo 'Caller prompt preserved: yes' . PHP_EOL;
echo 'Output length matches final state: yes' . PHP_EOL;
echo 'Text observed before completion report: yes' . PHP_EOL;
echo 'Exercise complete.' . PHP_EOL;
