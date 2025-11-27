<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\HistoricalQuote;
use App\Service\HistoricalQuoteService;
use PHPUnit\Framework\TestCase;

class HistoricalQuoteServiceTest extends TestCase
{
    private HistoricalQuoteService $service;

    protected function setUp(): void
    {
        $this->service = new HistoricalQuoteService();
    }

    public function testGetQuotesReturnsArrayOfQuotes(): void
    {
        $quotes = $this->service->getQuotes('AAPL', '2023-01-02', '2023-01-06');

        $this->assertNotEmpty($quotes);
        $this->assertContainsOnlyInstancesOf(HistoricalQuote::class, $quotes);
    }

    public function testGetQuotesExcludesWeekends(): void
    {
        // January 7, 2023 is Saturday, January 8 is Sunday
        $quotes = $this->service->getQuotes('AAPL', '2023-01-06', '2023-01-09');

        // Should have quotes for Friday (6th) and Monday (9th) only
        $dates = array_map(fn($q) => $q->date, $quotes);
        
        $this->assertContains('2023-01-06', $dates); // Friday
        $this->assertNotContains('2023-01-07', $dates); // Saturday
        $this->assertNotContains('2023-01-08', $dates); // Sunday
        $this->assertContains('2023-01-09', $dates); // Monday
    }

    public function testGetQuotesReturnsDeterministicData(): void
    {
        $quotes1 = $this->service->getQuotes('AAPL', '2023-01-02', '2023-01-03');
        $quotes2 = $this->service->getQuotes('AAPL', '2023-01-02', '2023-01-03');

        $this->assertEquals($quotes1, $quotes2);
    }

    public function testGetQuotesReturnsDifferentDataForDifferentSymbols(): void
    {
        $quotesAAPL = $this->service->getQuotes('AAPL', '2023-01-02', '2023-01-02');
        $quotesGOOGL = $this->service->getQuotes('GOOGL', '2023-01-02', '2023-01-02');

        $this->assertNotEquals($quotesAAPL[0]->open, $quotesGOOGL[0]->open);
    }

    public function testQuotePropertiesAreValid(): void
    {
        $quotes = $this->service->getQuotes('AAPL', '2023-01-02', '2023-01-02');
        $quote = $quotes[0];

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $quote->date);
        $this->assertIsFloat($quote->open);
        $this->assertIsFloat($quote->high);
        $this->assertIsFloat($quote->low);
        $this->assertIsFloat($quote->close);
        $this->assertIsInt($quote->volume);
        
        // High should be >= Open and Close
        $this->assertGreaterThanOrEqual(max($quote->open, $quote->close), $quote->high);
        // Low should be <= Open and Close
        $this->assertLessThanOrEqual(min($quote->open, $quote->close), $quote->low);
    }

    public function testToCsvReturnsValidCsvString(): void
    {
        $quotes = $this->service->getQuotes('AAPL', '2023-01-02', '2023-01-03');
        $csv = $this->service->toCsv($quotes);

        $lines = explode("\n", $csv);
        
        // Check header
        $this->assertEquals('Date,Open,High,Low,Close,Volume', $lines[0]);
        
        // Check we have data rows
        $this->assertCount(count($quotes) + 1, $lines);
    }

    public function testToCsvReturnsOnlyHeaderForEmptyQuotes(): void
    {
        $csv = $this->service->toCsv([]);
        
        $this->assertEquals('Date,Open,High,Low,Close,Volume', $csv);
    }

    public function testGetQuotesReturnsEmptyForSingleWeekendDay(): void
    {
        // January 7, 2023 is Saturday
        $quotes = $this->service->getQuotes('AAPL', '2023-01-07', '2023-01-07');
        
        $this->assertEmpty($quotes);
    }
}

