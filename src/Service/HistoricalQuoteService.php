<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\HistoricalQuote;

class HistoricalQuoteService
{
    /**
     * @return HistoricalQuote[]
     */
    public function getQuotes(string $symbol, string $startDate, string $endDate): array
    {
        $quotes = [];
        $start = new \DateTimeImmutable($startDate);
        $end = new \DateTimeImmutable($endDate);
        
        // Generate dummy data for each date in the range
        $current = $start;
        $basePrice = $this->getBasePrice($symbol);
        
        while ($current <= $end) {
            // Skip weekends (stock market is closed)
            if ($current->format('N') < 6) {
                $quotes[] = $this->generateQuote($symbol, $current, $basePrice);
            }
            $current = $current->modify('+1 day');
        }

        return $quotes;
    }

    /**
     * @param HistoricalQuote[] $quotes
     */
    public function toCsv(array $quotes): string
    {
        $lines = [];
        $lines[] = 'Date,Open,High,Low,Close,Volume';

        foreach ($quotes as $quote) {
            $lines[] = sprintf(
                '%s,%.6f,%.6f,%.6f,%.6f,%d',
                $quote->date,
                $quote->open,
                $quote->high,
                $quote->low,
                $quote->close,
                $quote->volume,
            );
        }

        return implode("\n", $lines);
    }

    private function getBasePrice(string $symbol): float
    {
        $hash = crc32(strtoupper($symbol));
        return ($hash % 50000) / 100 + 10; // Price between 10 and 510
    }

    private function generateQuote(string $symbol, \DateTimeImmutable $date, float $basePrice): HistoricalQuote
    {
        $dateHash = crc32($date->format('Y-m-d') . $symbol);
        $variation = (($dateHash % 1000) - 500) / 1000; // -0.5 to +0.5
        
        $open = $basePrice * (1 + $variation * 0.05);
        $close = $open * (1 + (($dateHash % 100) - 50) / 5000);
        $high = max($open, $close) * (1 + abs($dateHash % 50) / 5000);
        $low = min($open, $close) * (1 - abs($dateHash % 50) / 5000);
        $volume = 100000 + ($dateHash % 9000000);

        return new HistoricalQuote(
            date: $date->format('Y-m-d'),
            open: round($open, 6),
            high: round($high, 6),
            low: round($low, 6),
            close: round($close, 6),
            volume: $volume,
        );
    }
}

