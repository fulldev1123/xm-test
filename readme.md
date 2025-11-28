# XM Stock Quotes API

A Symfony 7.1 application that provides an API endpoint to retrieve historical stock quotes and send them via email.

## Features

- **REST API Endpoint**: `POST /api/stock/quotes`
- **Input Validation**: Company symbol, date range, and email validation
- **NASDAQ Symbol Validation**: Validates symbols against real NASDAQ listings (with caching)
- **Email Notifications**: Sends quotes via email with CSV attachment
- **OpenAPI Documentation**: Swagger UI available at `/api/doc`
- **Docker Support**: Complete containerized environment
- **Comprehensive Tests**: 41 tests with 138 assertions

## Requirements

- PHP 8.2+
- Composer
- Docker & Docker Compose (optional)

## Installation

### With Docker

```bash
docker compose up -d
```

The application will be available at:

- **API**: http://localhost:8080/api/stock/quotes
- **Swagger UI**: http://localhost:8080/api/doc
- **MailHog** (email testing): http://localhost:8025

### Without Docker

```bash
composer install
php -S localhost:8080 -t public
```

## API Usage

### Endpoint

```
POST /api/stock/quotes
Content-Type: application/json
```

### Request Body

```json
{
  "companySymbol": "AAPL",
  "startDate": "2023-01-01",
  "endDate": "2023-01-31",
  "email": "user@example.com"
}
```

### Parameters

| Parameter     | Type   | Required | Description                                               |
| ------------- | ------ | -------- | --------------------------------------------------------- |
| companySymbol | string | Yes      | Valid NASDAQ company symbol (e.g., AAPL, GOOGL, MSFT)     |
| startDate     | string | Yes      | Start date in YYYY-MM-DD format (cannot be in the future) |
| endDate       | string | Yes      | End date in YYYY-MM-DD format (cannot be in the future)   |
| email         | string | Yes      | Valid email address to receive the quotes                 |

### Success Response (200 OK)

```json
{
  "companySymbol": "AAPL",
  "companyName": "Apple Inc.",
  "startDate": "2023-01-02",
  "endDate": "2023-01-06",
  "quotes": [
    {
      "Date": "2023-01-02",
      "Open": 130.28,
      "High": 130.9,
      "Low": 124.17,
      "Close": 125.07,
      "Volume": 112117500
    }
  ]
}
```

### Error Response (400 Bad Request)

```json
{
  "errors": [
    "Company symbol is required.",
    "Start date must be a valid date in YYYY-MM-DD format.",
    "Email must be a valid email address."
  ]
}
```

## Running Tests

```bash
# Run all tests
php bin/phpunit

# Run tests with detailed output
php bin/phpunit --testdox

# Run specific test suite
php bin/phpunit tests/Service/
```

## Project Structure

```
src/
├── Controller/
│   └── StockQuoteController.php    # Main API endpoint
├── DTO/
│   ├── StockQuoteRequest.php       # Request DTO with validation
│   └── HistoricalQuote.php         # Quote data DTO
├── Service/
│   ├── CompanySymbolService.php    # NASDAQ symbol validation
│   ├── HistoricalQuoteService.php  # Quote data generation
│   └── StockQuoteEmailService.php  # Email service
└── Validator/
    └── Constraints/
        ├── ValidCompanySymbol.php           # Custom constraint
        └── ValidCompanySymbolValidator.php  # Constraint validator
```

## Configuration

### Environment Variables

| Variable   | Description                   | Default               |
| ---------- | ----------------------------- | --------------------- |
| MAILER_DSN | Email transport configuration | `smtp://mailhog:1025` |

### Services Configuration

Edit `config/services.yaml` to customize:

- Sender email address (`app.sender_email`)
