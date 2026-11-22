<?php
declare(strict_types=1);
// label => [question, expected source file]; null means no source answers it.
// (weak): the guides answer it only in passing, so its best score stays low.
return [
    'retry' => ['Where do we set the retry limit?', 'setup.md'],
    'damaged' => ['What does a damaged item return need?', 'support.md'],
    'invoice' => ['Where can a customer find an invoice?', 'billing.md'],
    'plan (weak)' => ['How do we change the plan?', 'billing.md'],
    'hours (weak)' => ['What are the support hours?', 'support.md'],
    'excel' => ['Can Tidepool export reports to Excel?', null],
];
