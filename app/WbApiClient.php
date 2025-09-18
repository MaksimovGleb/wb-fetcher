<?php

namespace App;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class WbApiClient
{
    private string $baseUrl;
    private string $key;
    private int $limit;

    public function __construct(?string $baseUrl = null, ?string $key = null, ?int $limit = null)
    {
        $this->baseUrl = $baseUrl ?? (string) config('wb.base_url');
        $this->key = $key ?? (string) config('wb.key');
        $this->limit = $limit ?? (int) config('wb.limit', 500);
    }

    public function fetchPage(string $endpoint, array $params, int $page = 1): array
    {
        $query = array_filter([
            'dateFrom' => $params['dateFrom'] ?? null,
            'dateTo'   => $params['dateTo']   ?? null,
            'page'     => $page,
            'limit'    => $params['limit'] ?? $this->limit,
            'key'      => $this->key,
        ], fn ($v) => $v !== null && $v !== '');

        $response = Http::baseUrl($this->baseUrl)
            ->timeout(60)
            ->acceptJson()
            ->get("/api/{$endpoint}", $query)
            ->throw();

        $data = $response->json();

        $items = [];
        if (is_array($data)) {
            if (isset($data['data']) && is_array($data['data'])) {
                $items = $data['data'];
            } elseif (isset($data['items']) && is_array($data['items'])) {
                $items = $data['items'];
            } elseif (Arr::isList($data)) {
                $items = $data;
            }
        }

        return [
            'items' => $items,
            'page'  => $page,
            'limit' => (int) ($query['limit'] ?? $this->limit),
        ];
    }

    public function iterate(string $endpoint, array $params): \Generator
    {
        $page = 1;
        while (true) {
            $result = $this->fetchPage($endpoint, $params, $page);
            $items = $result['items'];
            if (empty($items)) {
                break;
            }
            foreach ($items as $item) {
                yield $item;
            }

            if (count($items) < ($params['limit'] ?? $this->limit)) {
                break;
            }
            $page++;
        }
    }
}
