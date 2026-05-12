<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ManticoreClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
    ) {}

    public function replace(string $table, array $document): void
    {
        $this->request('POST', '/replace', ['index' => $table, 'doc' => $document]);
    }

    public function delete(string $table, int $id): void
    {
        $this->request('POST', '/delete', ['index' => $table, 'id' => $id]);
    }

    public function search(string $table, array $body): array
    {
        return $this->request('POST', '/search', array_merge(['index' => $table], $body));
    }

    public function sql(string $query): void
    {
        $response = $this->httpClient->request('POST', $this->baseUrl . '/sql', [
            'body' => 'query=' . urlencode($query),
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        ]);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException($response->getContent(false));
        }
    }

    private function request(string $method, string $path, array $body): array
    {
        $response = $this->httpClient->request($method, $this->baseUrl . $path, [
            'json' => $body,
        ]);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException($response->getContent(false));
        }

        return $response->toArray(false);
    }
}
