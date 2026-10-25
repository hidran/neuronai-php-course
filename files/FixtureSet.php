<?php
declare(strict_types=1);
namespace App\Media;
use NeuronAI\Chat\Enums\MediaType;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Chat\Messages\UserMessage;
use RuntimeException;
final readonly class FixtureSet
{
    private const int MAX_BYTES = 4_000_000;
    /** @param list<ImageCase> $cases */
    public function __construct(
        private string $directory,
        public string $question,
        public string $exerciseQuestion,
        public array $cases,
    ) {
    }
    public static function load(string $directory): self
    {
        $json = file_get_contents($directory . '/manifest.json')
            ?: throw new RuntimeException('Manifest read failed.');
        $manifest = json_decode($json, associative: true,
            flags: JSON_THROW_ON_ERROR);
        $cases = array_map(
            static fn (array $case) => new ImageCase(...$case),
            $manifest['variants'],
        );
        return new self($directory, $manifest['question'],
            $manifest['exercise_question'], $cases);
    }
    public function measure(ImageCase $case): Measurement
    {
        $path = $this->directory . '/' . $case->file;
        $bytes = file_get_contents($path);
        if ($bytes === false || strlen($bytes) > self::MAX_BYTES) {
            throw new RuntimeException('Image read or size failed.');
        }
        $size = getimagesizefromstring($bytes);
        if ($size === false || $size['mime'] !== 'image/png'
            || $size[0] !== $case->width
            || $size[1] !== $case->height
            || hash('sha256', $bytes) !== $case->sha256) {
            throw new RuntimeException("Fixture {$case->name} differs");
        }
        $encoded = base64_encode($bytes);
        return new Measurement(
            dimensions: $size[0] . 'x' . $size[1],
            rawBytes: strlen($bytes),
            base64Bytes: strlen($encoded),
            encoded: $encoded,
        );
    }
    public function message(string $question, Measurement $m)
        : UserMessage
    {
        $message = new UserMessage($question);
        $message->addContent(new ImageContent(
            content: $m->encoded,
            sourceType: SourceType::BASE64,
            mediaType: MediaType::PNG,
        ));
        return $message;
    }
}
