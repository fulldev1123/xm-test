<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CompanySymbolService
{
    private const NASDAQ_LISTINGS_URL = 'https://pkgstore.datahub.io/core/nasdaq-listings/nasdaq-listed_json/data/a5bc7580d6176d60ac0b2142ca8d7df6/nasdaq-listed_json.json';
    private const CACHE_KEY = 'nasdaq_company_symbols';
    private const CACHE_TTL = 3600; // 1 hour - symbols might change daily

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @return array<string, array{symbol: string, name: string}>
     */
    public function getCompanySymbols(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL);
            return $this->fetchCompanySymbols();
        });
    }

    public function isValidSymbol(string $symbol): bool
    {
        $symbols = $this->getCompanySymbols();
        return isset($symbols[strtoupper($symbol)]);
    }

    public function getCompanyName(string $symbol): ?string
    {
        $symbols = $this->getCompanySymbols();
        $upperSymbol = strtoupper($symbol);
        
        return $symbols[$upperSymbol]['name'] ?? null;
    }

    /**
     * @return array<string, array{symbol: string, name: string}>
     */
    private function fetchCompanySymbols(): array
    {
        try {
            $response = $this->httpClient->request('GET', self::NASDAQ_LISTINGS_URL);
            $data = $response->toArray();

            $symbols = [];
            foreach ($data as $company) {
                $symbol = $company['Symbol'] ?? null;
                $name = $company['Company Name'] ?? null;

                if ($symbol !== null && $name !== null) {
                    $symbols[strtoupper($symbol)] = [
                        'symbol' => strtoupper($symbol),
                        'name' => $name,
                    ];
                }
            }

            return $symbols;
        } catch (\Throwable $e) {
            $this->logger?->error('Failed to fetch NASDAQ listings', [
                'error' => $e->getMessage(),
            ]);
            
            // Return fallback data for common symbols
            return $this->getFallbackSymbols();
        }
    }

    /**
     * @return array<string, array{symbol: string, name: string}>
     */
    private function getFallbackSymbols(): array
    {
        return [
            'AAPL' => ['symbol' => 'AAPL', 'name' => 'Apple Inc.'],
            'GOOGL' => ['symbol' => 'GOOGL', 'name' => 'Alphabet Inc.'],
            'GOOG' => ['symbol' => 'GOOG', 'name' => 'Alphabet Inc.'],
            'MSFT' => ['symbol' => 'MSFT', 'name' => 'Microsoft Corporation'],
            'AMZN' => ['symbol' => 'AMZN', 'name' => 'Amazon.com Inc.'],
            'META' => ['symbol' => 'META', 'name' => 'Meta Platforms Inc.'],
            'TSLA' => ['symbol' => 'TSLA', 'name' => 'Tesla Inc.'],
            'NVDA' => ['symbol' => 'NVDA', 'name' => 'NVIDIA Corporation'],
            'NFLX' => ['symbol' => 'NFLX', 'name' => 'Netflix Inc.'],
            'INTC' => ['symbol' => 'INTC', 'name' => 'Intel Corporation'],
        ];
    }
}

