<?php
declare(strict_types=1);
return [
    // The token budget of one support conversation: a conservative
    // share of the configured model's context, not its maximum.
    'context_window' => (int) env('SUPPORT_CONTEXT_WINDOW', 32000),
];
