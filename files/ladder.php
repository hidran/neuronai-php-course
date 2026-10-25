<?php
declare(strict_types=1);
function workflow(object $model, object $db, string $email): string
{
    $facts = $model->extract($email);
    $customer = $db->find($facts);
    return $model->reply($customer, $facts);
}
function agent(object $model, string $goal, int $maxSteps = 8): string
{
    $history = [$goal];
    for ($step = 1; $step <= $maxSteps; $step++) {
        $next = $model->chooseNextStep($history);
        if ($next->isDone()) {
            return $next->answer();
        }
        $history[] = $next->run();
    }
    throw new RuntimeException("No answer after {$maxSteps} steps");
}
