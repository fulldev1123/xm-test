<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\CompanySymbolService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CompanySymbolServiceTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private CacheInterface&MockObject $cache;
    private LoggerInterface&MockObject $logger;
    private CompanySymbolService $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->service = new CompanySymbolService(
            $this->httpClient,
            $this->cache,
            $this->logger,
        );
    }

    public function testGetCompanySymbolsReturnsCachedData(): void
    {
        $expectedSymbols = [
            'AAPL' => ['symbol' => 'AAPL', 'name' => 'Apple Inc.'],
            'GOOGL' => ['symbol' => 'GOOGL', 'name' => 'Alphabet Inc.'],
        ];

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn($expectedSymbols);

        $result = $this->service->getCompanySymbols();

        $this->assertEquals($expectedSymbols, $result);
    }

    public function testIsValidSymbolReturnsTrueForValidSymbol(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn([
                'AAPL' => ['symbol' => 'AAPL', 'name' => 'Apple Inc.'],
            ]);

        $this->assertTrue($this->service->isValidSymbol('AAPL'));
    }

    public function testIsValidSymbolReturnsTrueForLowercaseSymbol(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn([
                'AAPL' => ['symbol' => 'AAPL', 'name' => 'Apple Inc.'],
            ]);

        $this->assertTrue($this->service->isValidSymbol('aapl'));
    }

    public function testIsValidSymbolReturnsFalseForInvalidSymbol(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn([
                'AAPL' => ['symbol' => 'AAPL', 'name' => 'Apple Inc.'],
            ]);

        $this->assertFalse($this->service->isValidSymbol('INVALID'));
    }

    public function testGetCompanyNameReturnsNameForValidSymbol(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn([
                'AAPL' => ['symbol' => 'AAPL', 'name' => 'Apple Inc.'],
            ]);

        $this->assertEquals('Apple Inc.', $this->service->getCompanyName('AAPL'));
    }

    public function testGetCompanyNameReturnsNullForInvalidSymbol(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn([
                'AAPL' => ['symbol' => 'AAPL', 'name' => 'Apple Inc.'],
            ]);

        $this->assertNull($this->service->getCompanyName('INVALID'));
    }

    public function testFetchCompanySymbolsFromApi(): void
    {
        $apiResponse = [
            ['Symbol' => 'AAPL', 'Company Name' => 'Apple Inc.'],
            ['Symbol' => 'GOOGL', 'Company Name' => 'Alphabet Inc.'],
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($apiResponse);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $result = $this->service->getCompanySymbols();

        $this->assertArrayHasKey('AAPL', $result);
        $this->assertArrayHasKey('GOOGL', $result);
        $this->assertEquals('Apple Inc.', $result['AAPL']['name']);
    }

    public function testFetchCompanySymbolsReturnsFallbackOnError(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Network error'));

        $this->logger->expects($this->once())
            ->method('error');

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $result = $this->service->getCompanySymbols();

        // Should return fallback symbols
        $this->assertArrayHasKey('AAPL', $result);
        $this->assertArrayHasKey('GOOGL', $result);
    }
}

