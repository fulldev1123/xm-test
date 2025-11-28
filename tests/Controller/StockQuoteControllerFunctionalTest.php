<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class StockQuoteControllerFunctionalTest extends WebTestCase
{
    public function testGetQuotesWithValidDataReturnsSuccess(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/stock/quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'companySymbol' => 'AAPL',
                'startDate' => '2023-01-02',
                'endDate' => '2023-01-06',
                'email' => 'test@example.com',
            ])
        );

        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertEquals('AAPL', $responseData['companySymbol']);
        $this->assertNotEmpty($responseData['companyName']);
        $this->assertEquals('2023-01-02', $responseData['startDate']);
        $this->assertEquals('2023-01-06', $responseData['endDate']);
        $this->assertArrayHasKey('quotes', $responseData);
        $this->assertIsArray($responseData['quotes']);
        
        // Verify quote structure
        if (!empty($responseData['quotes'])) {
            $quote = $responseData['quotes'][0];
            $this->assertArrayHasKey('Date', $quote);
            $this->assertArrayHasKey('Open', $quote);
            $this->assertArrayHasKey('High', $quote);
            $this->assertArrayHasKey('Low', $quote);
            $this->assertArrayHasKey('Close', $quote);
            $this->assertArrayHasKey('Volume', $quote);
        }
    }

    public function testGetQuotesWithMissingFieldsReturnsBadRequest(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/stock/quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertNotEmpty($responseData['errors']);
    }

    public function testGetQuotesWithInvalidSymbolReturnsBadRequest(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/stock/quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'companySymbol' => 'INVALIDXYZ123',
                'startDate' => '2023-01-02',
                'endDate' => '2023-01-06',
                'email' => 'test@example.com',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
    }

    public function testGetQuotesWithFutureDateReturnsBadRequest(): void
    {
        $client = static::createClient();

        $futureDate = (new \DateTime('+1 year'))->format('Y-m-d');

        $client->request(
            'POST',
            '/api/stock/quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'companySymbol' => 'AAPL',
                'startDate' => $futureDate,
                'endDate' => $futureDate,
                'email' => 'test@example.com',
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
    }

    public function testGetQuotesEndpointExists(): void
    {
        $client = static::createClient();

        // Test that the endpoint exists (should not return 404)
        $client->request(
            'POST',
            '/api/stock/quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['test' => 'data'])
        );

        $this->assertNotEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }
}

