<?php
declare(strict_types=1);
namespace Tests\Feature;
use App\Http\Controllers\OrderController;
use PHPUnit\Framework\TestCase;
final class OrderTest extends TestCase
{
    public function testShowsAnOrder(): void
    {
        self::assertSame(7, (new OrderController())->show(7)['order']);
    }
}
