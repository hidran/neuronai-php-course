<?php
declare(strict_types=1);
namespace Acme\Slugger\Tests;
use Acme\Slugger\Slugger;
use Acme\Slugger\Transliterator;
use PHPUnit\Framework\TestCase;
final class SluggerTest extends TestCase
{
    public function testTitlesBecomeSlugs(): void
    {
        $slugger = new Slugger(new Transliterator());
        self::assertSame('caffe-e-crema', $slugger->slug('Caffè e  crema!'));
    }
}
