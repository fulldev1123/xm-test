<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\HistoricalQuote;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class StockQuoteEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly HistoricalQuoteService $historicalQuoteService,
        private readonly string $senderEmail = 'noreply@xm-stock-quotes.local',
    ) {
    }

    /**
     * @param HistoricalQuote[] $quotes
     */
    public function sendQuotesEmail(
        string $recipientEmail,
        string $companyName,
        string $startDate,
        string $endDate,
        array $quotes,
    ): void {
        $csvContent = $this->historicalQuoteService->toCsv($quotes);
        
        $email = (new Email())
            ->from($this->senderEmail)
            ->to($recipientEmail)
            ->subject($companyName)
            ->text(sprintf('From %s to %s', $startDate, $endDate))
            ->attach($csvContent, 'historical_quotes.csv', 'text/csv');

        $this->mailer->send($email);
    }
}

