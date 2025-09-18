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

    /**
     * Fetch a single page for the given endpoint.
     *
     * @param string $endpoint e.g. 'sales', 'orders', 'stocks', 'incomes'
     * @param array $params Should contain dateFrom/dateTo as per API
     * @param int $page 1-based
     * @return array{items: array<int,mixed>, page:int, limit:int}
     */
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

        // Try standard shapes; fall back to array itself
        $items = [];
        if (is_array($data)) {
            if (isset($data['data']) && is_array($data['data'])) {
                $items = $data['data'];
            } elseif (isset($data['items']) && is_array($data['items'])) {
                $items = $data['items'];
            } elseif (Arr::isList($data)) {
                $items = $data; // plain list
            }
        }

        return [
            'items' => $items,
            'page'  => $page,
            'limit' => (int) ($query['limit'] ?? $this->limit),
        ];
    }

    /**
     * Generator that yields items across all pages.
     *
     * @param string $endpoint
     * @param array $params
     * @return \Generator<mixed>
     */
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
            // Stop if less than limit -> last page
            if (count($items) < ($params['limit'] ?? $this->limit)) {
                break;
            }
            $page++;
        }
    }
}