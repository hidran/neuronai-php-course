<?php
declare(strict_types=1);
namespace App\Invoices;
use NeuronAI\Chat\Enums\MediaType;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Chat\Messages\UserMessage;
final readonly class InvoiceMessageFactory
{
    private const string QUESTION =
        'Extract this invoice exactly as printed. Read every row.';
    public function message(InvoiceSource $source): UserMessage
    {
        $encoded = base64_encode($source->bytes);
        $block = match ($source->mediaType) {
            MediaType::PDF => new FileContent(
                content: $encoded,
                sourceType: SourceType::BASE64,
                mediaType: MediaType::PDF,
                filename: $source->name,
            ),
            default => new ImageContent(
                content: $encoded,
                sourceType: SourceType::BASE64,
                mediaType: $source->mediaType,
            ),
        };
        return new UserMessage(self::QUESTION)->addContent($block);
    }
}
