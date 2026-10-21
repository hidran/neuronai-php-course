<?php
declare(strict_types=1);
function workflow($model, $db, string $email): string
{
    $facts = $model->extract($email);
    $customer = $db->find($facts);
    return $model->reply($customer, $facts);
}
function agent($model, string $goal): string
{
    $history = [$goal];
    while (true) {
        $next = $model->chooseNextStep($history);
        if ($next->isDone()) {
            return $next->answer();
        }
        $history[] = $next->run();
    }
}
