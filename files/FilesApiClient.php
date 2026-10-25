<?php
declare(strict_types=1);
namespace App\Http;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\HttpClient\HttpRequest;
use NeuronAI\HttpClient\HttpResponse;
use NeuronAI\HttpClient\StreamInterface;
/**
 * Adds the Anthropic Files API beta header to every request, so a
 * message may reference an uploaded file by id. A decorator: the real
 * client stays unchanged and the provider does not know about it.
 */
final readonly class FilesApiClient implements HttpClientInterface
{
    private const array HEADERS = [
        'anthropic-beta' => 'files-api-2025-04-14',
    ];
    public function __construct(private HttpClientInterface $inner)
    {
    }
    #[\Override]
    public function request(HttpRequest $request): HttpResponse
    {
        return $this->inner->request(
            $request->withHeaders(self::HEADERS));
    }
    #[\Override]
    public function stream(HttpRequest $request): StreamInterface
    {
        return $this->inner->stream(
            $request->withHeaders(self::HEADERS));
    }
}
