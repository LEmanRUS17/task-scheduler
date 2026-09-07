<?php

declare(strict_types=1);

namespace App\Shared\ClickHouse;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ClickHouseClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly string $user,
        private readonly string $password,
    ) {
    }

    /**
     * @param array<array<string, mixed>> $rows
     */
    public function insert(string $table, array $rows): void
    {
        $body = implode("\n", array_map(
            static fn(array $row): string => json_encode($row, JSON_THROW_ON_ERROR),
            $rows,
        ));

        $response = $this->httpClient->request('POST', $this->baseUrl . '/', [
            'query' => $this->auth(['query' => sprintf('INSERT INTO %s FORMAT JSONEachRow', $table)]),
            'body'  => $body,
        ]);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException($response->getContent(false));
        }
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function query(string $sql): array
    {
        $response = $this->httpClient->request('POST', $this->baseUrl . '/', [
            'query' => $this->auth(['query' => $sql . ' FORMAT JSONEachRow']),
        ]);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException($response->getContent(false));
        }

        $content = trim($response->getContent(false));

        if ($content === '') {
            return [];
        }

        return array_map(
            static fn(string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR),
            explode("\n", $content),
        );
    }

    /**
     * @param array<string, string> $params
     * @return array<string, string>
     */
    private function auth(array $params): array
    {
        return array_merge($params, ['user' => $this->user, 'password' => $this->password]);
    }
}
