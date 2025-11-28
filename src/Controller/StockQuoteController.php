<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\StockQuoteRequest;
use App\Service\CompanySymbolService;
use App\Service\HistoricalQuoteService;
use App\Service\StockQuoteEmailService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class StockQuoteController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly CompanySymbolService $companySymbolService,
        private readonly HistoricalQuoteService $historicalQuoteService,
        private readonly StockQuoteEmailService $emailService,
    ) {
    }

    #[Route('/stock/quotes', name: 'api_stock_quotes', methods: ['POST'])]
    #[OA\Post(
        summary: 'Get historical stock quotes',
        description: 'Retrieve historical quotes for a company symbol within a date range and send them via email.',
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['companySymbol', 'startDate', 'endDate', 'email'],
            properties: [
                new OA\Property(property: 'companySymbol', type: 'string', example: 'AAPL'),
                new OA\Property(property: 'startDate', type: 'string', format: 'date', example: '2023-01-01'),
                new OA\Property(property: 'endDate', type: 'string', format: 'date', example: '2023-01-31'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Historical quotes retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'companySymbol', type: 'string'),
                new OA\Property(property: 'companyName', type: 'string'),
                new OA\Property(property: 'startDate', type: 'string', format: 'date'),
                new OA\Property(property: 'endDate', type: 'string', format: 'date'),
                new OA\Property(
                    property: 'quotes',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'Date', type: 'string', format: 'date'),
                            new OA\Property(property: 'Open', type: 'number', format: 'float'),
                            new OA\Property(property: 'High', type: 'number', format: 'float'),
                            new OA\Property(property: 'Low', type: 'number', format: 'float'),
                            new OA\Property(property: 'Close', type: 'number', format: 'float'),
                            new OA\Property(property: 'Volume', type: 'integer'),
                        ],
                        type: 'object'
                    )
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string')),
            ],
            type: 'object'
        )
    )]
    #[OA\Tag(name: 'Stock Quotes')]
    public function getQuotes(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $stockQuoteRequest = new StockQuoteRequest();
        $stockQuoteRequest->companySymbol = $data['companySymbol'] ?? null;
        $stockQuoteRequest->startDate = $data['startDate'] ?? null;
        $stockQuoteRequest->endDate = $data['endDate'] ?? null;
        $stockQuoteRequest->email = $data['email'] ?? null;

        // Validate the request
        $violations = $this->validator->validate($stockQuoteRequest);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Get company name
        $companyName = $this->companySymbolService->getCompanyName($stockQuoteRequest->companySymbol);

        // Get historical quotes
        $quotes = $this->historicalQuoteService->getQuotes(
            $stockQuoteRequest->companySymbol,
            $stockQuoteRequest->startDate,
            $stockQuoteRequest->endDate,
        );

        // Send email with quotes
        $this->emailService->sendQuotesEmail(
            $stockQuoteRequest->email,
            $companyName,
            $stockQuoteRequest->startDate,
            $stockQuoteRequest->endDate,
            $quotes,
        );

        // Return response
        return new JsonResponse([
            'companySymbol' => strtoupper($stockQuoteRequest->companySymbol),
            'companyName' => $companyName,
            'startDate' => $stockQuoteRequest->startDate,
            'endDate' => $stockQuoteRequest->endDate,
            'quotes' => array_map(fn($quote) => $quote->toArray(), $quotes),
        ]);
    }
}

