<?php
declare(strict_types=1);
namespace App\Knowledge;
/** Where the true answer lives decides the knowledge mechanism. */
enum Authority: string
{
    case ShortStableText = 'short stable text';
    case Document = 'document';
    case Record = 'record';
    case LearnedBehavior = 'learned behavior';
}
