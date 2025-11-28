<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\StockQuoteController;
use App\DTO\HistoricalQuote;
use App\Service\CompanySymbolService;
use App\Service\HistoricalQuoteService;
use App\Service\StockQuoteEmailService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StockQuoteControllerTest extends TestCase
{
    private ValidatorInterface&MockObject $validator;
    private CompanySymbolService&MockObject $companySymbolService;
    private HistoricalQuoteService&MockObject $historicalQuoteService;
    private StockQuoteEmailService&MockObject $emailService;
    private StockQuoteController $controller;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->companySymbolService = $this->createMock(CompanySymbolService::class);
        $this->historicalQuoteService = $this->createMock(HistoricalQuoteService::class);
        $this->emailService = $this->createMock(StockQuoteEmailService::class);

        $this->controller = new StockQuoteController(
            $this->validator,
            $this->companySymbolService,
            $this->historicalQuoteService,
            $this->emailService,
        );
    }

    public function testGetQuotesReturnsValidResponse(): void
    {
        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '2023-01-02',
            'endDate' => '2023-01-06',
            'email' => 'test@example.com',
        ];

        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->companySymbolService->method('getCompanyName')
            ->with('AAPL')
            ->willReturn('Apple Inc.');

        $quotes = [
            new HistoricalQuote('2023-01-02', 100.0, 105.0, 99.0, 102.0, 1000000),
        ];

        $this->historicalQuoteService->method('getQuotes')
            ->with('AAPL', '2023-01-02', '2023-01-06')
            ->willReturn($quotes);

        $this->emailService->expects($this->once())
            ->method('sendQuotesEmail')
            ->with('test@example.com', 'Apple Inc.', '2023-01-02', '2023-01-06', $quotes);

        $response = $this->controller->getQuotes($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('AAPL', $responseData['companySymbol']);
        $this->assertEquals('Apple Inc.', $responseData['companyName']);
        $this->assertEquals('2023-01-02', $responseData['startDate']);
        $this->assertEquals('2023-01-06', $responseData['endDate']);
        $this->assertArrayHasKey('quotes', $responseData);
        $this->assertCount(1, $responseData['quotes']);
    }

    public function testGetQuotesValidationErrorForMissingCompanySymbol(): void
    {
        $requestData = [
            'startDate' => '2023-01-02',
            'endDate' => '2023-01-06',
            'email' => 'test@example.com',
        ];

        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        $violation = new ConstraintViolation(
            'Company symbol is required.',
            null,
            [],
            null,
            'companySymbol',
            null,
        );

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList([$violation]));

        $response = $this->controller->getQuotes($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertContains('Company symbol is required.', $responseData['errors']);
    }

    public function testGetQuotesValidationErrorForInvalidEmail(): void
    {
        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '2023-01-02',
            'endDate' => '2023-01-06',
            'email' => 'invalid-email',
        ];

        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        $violation = new ConstraintViolation(
            'Email must be a valid email address.',
            null,
            [],
            null,
            'email',
            'invalid-email',
        );

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList([$violation]));

        $response = $this->controller->getQuotes($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertContains('Email must be a valid email address.', $responseData['errors']);
    }

    public function testGetQuotesValidationErrorForInvalidDateFormat(): void
    {
        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '01-02-2023',
            'endDate' => '2023-01-06',
            'email' => 'test@example.com',
        ];

        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        $violation = new ConstraintViolation(
            'Start date must be a valid date in YYYY-MM-DD format.',
            null,
            [],
            null,
            'startDate',
            '01-02-2023',
        );

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList([$violation]));

        $response = $this->controller->getQuotes($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
    }

    public function testGetQuotesValidationErrorForStartDateAfterEndDate(): void
    {
        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '2023-01-10',
            'endDate' => '2023-01-05',
            'email' => 'test@example.com',
        ];

        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        $violation = new ConstraintViolation(
            'Start date must be less than or equal to end date.',
            null,
            [],
            null,
            'startDate',
            '2023-01-10',
        );

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList([$violation]));

        $response = $this->controller->getQuotes($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testGetQuotesValidationErrorForInvalidSymbol(): void
    {
        $requestData = [
            'companySymbol' => 'INVALID',
            'startDate' => '2023-01-02',
            'endDate' => '2023-01-06',
            'email' => 'test@example.com',
        ];

        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        $violation = new ConstraintViolation(
            'The company symbol "INVALID" is not valid.',
            null,
            [],
            null,
            'companySymbol',
            'INVALID',
        );

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList([$violation]));

        $response = $this->controller->getQuotes($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertContains('The company symbol "INVALID" is not valid.', $responseData['errors']);
    }

    public function testGetQuotesReturnsMultipleValidationErrors(): void
    {
        $requestData = [];

        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        $violations = [
            new ConstraintViolation('Company symbol is required.', null, [], null, 'companySymbol', null),
            new ConstraintViolation('Start date is required.', null, [], null, 'startDate', null),
            new ConstraintViolation('End date is required.', null, [], null, 'endDate', null),
            new ConstraintViolation('Email is required.', null, [], null, 'email', null),
        ];

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList($violations));

        $response = $this->controller->getQuotes($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertCount(4, $responseData['errors']);
    }
}

