<?php
declare(strict_types=1);
// Studio hook installed by prepare.sh in storage/, never typed on camera:
// labels the Inspector transaction so the recorded run is easy to find.
use Inspector\Inspector;
$label = $thread ?? 'teaching-run';
Inspector::beforeFlush(function (Inspector $inspector) use ($label): void {
    $transaction = $inspector->transaction();
    if ($transaction === null) {
        return;
    }
    $transaction->name = $label;
    $transaction->addContext('Teaching run label', $label);
    file_put_contents(__DIR__ . '/inspector-latest.json', json_encode([
        'label' => $label, 'hash' => $transaction->hash,
        'utc' => gmdate(DATE_ATOM),
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo 'Inspector transaction hash: ' . $transaction->hash . PHP_EOL;
});
