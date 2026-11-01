<?php
declare(strict_types=1);
namespace App\Http\Controllers;
final class OrderController
{
    public function show(int $id): array
    {
        return ['order' => $id, 'status' => 'shipped'];
    }
}
