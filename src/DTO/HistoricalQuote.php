<?php

declare(strict_types=1);

namespace App\DTO;

class HistoricalQuote
{
    public function __construct(
        public readonly string $date,
        public readonly float $open,
        public readonly float $high,
        public readonly float $low,
        public readonly float $close,
        public readonly int $volume,
    ) {
    }

    public function toArray(): array
    {
        return [
            'Date' => $this->date,
            'Open' => $this->open,
            'High' => $this->high,
            'Low' => $this->low,
            'Close' => $this->close,
            'Volume' => $this->volume,
        ];
    }
}

