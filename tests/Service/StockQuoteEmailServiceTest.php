<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\HistoricalQuote;
use App\Service\HistoricalQuoteService;
use App\Service\StockQuoteEmailService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class StockQuoteEmailServiceTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private HistoricalQuoteService&MockObject $historicalQuoteService;
    private StockQuoteEmailService $service;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->historicalQuoteService = $this->createMock(HistoricalQuoteService::class);
        
        $this->service = new StockQuoteEmailService(
            $this->mailer,
            $this->historicalQuoteService,
            'test@example.com',
        );
    }

    public function testSendQuotesEmailSendsEmail(): void
    {
        $quotes = [
            new HistoricalQuote('2023-01-02', 100.0, 105.0, 99.0, 102.0, 1000000),
        ];

        $this->historicalQuoteService->expects($this->once())
            ->method('toCsv')
            ->with($quotes)
            ->willReturn("Date,Open,High,Low,Close,Volume\n2023-01-02,100.0,105.0,99.0,102.0,1000000");

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getTo()[0]->getAddress() === 'user@example.com'
                    && $email->getSubject() === 'Apple Inc.'
                    && str_contains($email->getTextBody(), 'From 2023-01-01 to 2023-01-31')
                    && count($email->getAttachments()) === 1;
            }));

        $this->service->sendQuotesEmail(
            'user@example.com',
            'Apple Inc.',
            '2023-01-01',
            '2023-01-31',
            $quotes,
        );
    }

    public function testSendQuotesEmailUsesCompanyNameAsSubject(): void
    {
        $quotes = [];

        $this->historicalQuoteService->method('toCsv')->willReturn('Date,Open,High,Low,Close,Volume');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getSubject() === 'Google Inc.';
            }));

        $this->service->sendQuotesEmail(
            'user@example.com',
            'Google Inc.',
            '2023-01-01',
            '2023-01-31',
            $quotes,
        );
    }

    public function testSendQuotesEmailIncludesDateRangeInBody(): void
    {
        $quotes = [];

        $this->historicalQuoteService->method('toCsv')->willReturn('Date,Open,High,Low,Close,Volume');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getTextBody() === 'From 2023-06-01 to 2023-06-30';
            }));

        $this->service->sendQuotesEmail(
            'user@example.com',
            'Test Company',
            '2023-06-01',
            '2023-06-30',
            $quotes,
        );
    }

    public function testSendQuotesEmailIncludesCsvAttachment(): void
    {
        $quotes = [
            new HistoricalQuote('2023-01-02', 100.0, 105.0, 99.0, 102.0, 1000000),
        ];

        $csvContent = "Date,Open,High,Low,Close,Volume\n2023-01-02,100.0,105.0,99.0,102.0,1000000";

        $this->historicalQuoteService->expects($this->once())
            ->method('toCsv')
            ->with($quotes)
            ->willReturn($csvContent);

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($csvContent) {
                $attachments = $email->getAttachments();
                return count($attachments) === 1
                    && $attachments[0]->getBody() === $csvContent;
            }));

        $this->service->sendQuotesEmail(
            'user@example.com',
            'Test Company',
            '2023-01-01',
            '2023-01-31',
            $quotes,
        );
    }
}

