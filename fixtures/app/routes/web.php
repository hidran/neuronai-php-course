<?php
declare(strict_types=1);
use App\Http\Controllers\OrderController;
return ['/orders/{id}' => [OrderController::class, 'show']];
