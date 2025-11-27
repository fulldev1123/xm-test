<?php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\HistoricalQuote;
use PHPUnit\Framework\TestCase;

class HistoricalQuoteTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $quote = new HistoricalQuote(
            date: '2023-01-02',
            open: 100.123456,
            high: 105.654321,
            low: 99.111111,
            close: 102.222222,
            volume: 1000000,
        );

        $this->assertEquals('2023-01-02', $quote->date);
        $this->assertEquals(100.123456, $quote->open);
        $this->assertEquals(105.654321, $quote->high);
        $this->assertEquals(99.111111, $quote->low);
        $this->assertEquals(102.222222, $quote->close);
        $this->assertEquals(1000000, $quote->volume);
    }

    public function testToArrayReturnsCorrectFormat(): void
    {
        $quote = new HistoricalQuote(
            date: '2023-01-02',
            open: 100.123456,
            high: 105.654321,
            low: 99.111111,
            close: 102.222222,
            volume: 1000000,
        );

        $expected = [
            'Date' => '2023-01-02',
            'Open' => 100.123456,
            'High' => 105.654321,
            'Low' => 99.111111,
            'Close' => 102.222222,
            'Volume' => 1000000,
        ];

        $this->assertEquals($expected, $quote->toArray());
    }

    public function testPropertiesAreReadonly(): void
    {
        $quote = new HistoricalQuote(
            date: '2023-01-02',
            open: 100.0,
            high: 105.0,
            low: 99.0,
            close: 102.0,
            volume: 1000000,
        );

        $reflection = new \ReflectionClass($quote);
        
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly());
        }
    }
}

