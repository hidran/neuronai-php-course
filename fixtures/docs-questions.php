<?php
declare(strict_types=1);
// label => [question, expected source]; never an expected answer.
return [
    'retry' => ['Where do we set the retry limit?', 'setup.md'],
    'damaged' => ['What does a damaged item return need?',
        'support.md'],
    'invoice' => ['Where can a customer find an invoice?',
        'billing.md'],
    'unsupported' => ['Can Tidepool export reports to Excel?', null],
];
