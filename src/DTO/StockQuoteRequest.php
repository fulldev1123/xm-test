<?php

declare(strict_types=1);

namespace App\DTO;

use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class StockQuoteRequest
{
    #[Assert\NotBlank(message: 'Company symbol is required.')]
    #[AppAssert\ValidCompanySymbol]
    public ?string $companySymbol = null;

    #[Assert\NotBlank(message: 'Start date is required.')]
    #[Assert\Date(message: 'Start date must be a valid date in YYYY-MM-DD format.')]
    #[Assert\LessThanOrEqual(
        propertyPath: 'endDate',
        message: 'Start date must be less than or equal to end date.'
    )]
    public ?string $startDate = null;

    #[Assert\NotBlank(message: 'End date is required.')]
    #[Assert\Date(message: 'End date must be a valid date in YYYY-MM-DD format.')]
    #[Assert\GreaterThanOrEqual(
        propertyPath: 'startDate',
        message: 'End date must be greater than or equal to start date.'
    )]
    public ?string $endDate = null;

    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Email must be a valid email address.')]
    public ?string $email = null;

    #[Assert\Callback]
    public function validateDatesNotInFuture(ExecutionContextInterface $context): void
    {
        $today = date('Y-m-d');

        if ($this->startDate !== null && $this->startDate > $today) {
            $context->buildViolation('Start date cannot be in the future.')
                ->atPath('startDate')
                ->addViolation();
        }

        if ($this->endDate !== null && $this->endDate > $today) {
            $context->buildViolation('End date cannot be in the future.')
                ->atPath('endDate')
                ->addViolation();
        }
    }
}

