<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Service\CompanySymbolService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidCompanySymbolValidator extends ConstraintValidator
{
    public function __construct(
        private readonly CompanySymbolService $companySymbolService,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidCompanySymbol) {
            throw new UnexpectedTypeException($constraint, ValidCompanySymbol::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (!$this->companySymbolService->isValidSymbol($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ symbol }}', $value)
                ->addViolation();
        }
    }
}

