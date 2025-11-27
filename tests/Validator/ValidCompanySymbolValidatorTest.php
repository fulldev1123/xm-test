<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Service\CompanySymbolService;
use App\Validator\Constraints\ValidCompanySymbol;
use App\Validator\Constraints\ValidCompanySymbolValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ValidCompanySymbolValidatorTest extends TestCase
{
    private CompanySymbolService&MockObject $companySymbolService;
    private ExecutionContextInterface&MockObject $context;
    private ValidCompanySymbolValidator $validator;

    protected function setUp(): void
    {
        $this->companySymbolService = $this->createMock(CompanySymbolService::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        
        $this->validator = new ValidCompanySymbolValidator($this->companySymbolService);
        $this->validator->initialize($this->context);
    }

    public function testValidateDoesNothingForNullValue(): void
    {
        $constraint = new ValidCompanySymbol();

        $this->companySymbolService->expects($this->never())->method('isValidSymbol');
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate(null, $constraint);
    }

    public function testValidateDoesNothingForEmptyValue(): void
    {
        $constraint = new ValidCompanySymbol();

        $this->companySymbolService->expects($this->never())->method('isValidSymbol');
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate('', $constraint);
    }

    public function testValidatePassesForValidSymbol(): void
    {
        $constraint = new ValidCompanySymbol();

        $this->companySymbolService->expects($this->once())
            ->method('isValidSymbol')
            ->with('AAPL')
            ->willReturn(true);

        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate('AAPL', $constraint);
    }

    public function testValidateFailsForInvalidSymbol(): void
    {
        $constraint = new ValidCompanySymbol();

        $this->companySymbolService->expects($this->once())
            ->method('isValidSymbol')
            ->with('INVALID')
            ->willReturn(false);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ symbol }}', 'INVALID')
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder);

        $this->validator->validate('INVALID', $constraint);
    }

    public function testValidateThrowsExceptionForWrongConstraintType(): void
    {
        $wrongConstraint = $this->createMock(\Symfony\Component\Validator\Constraint::class);

        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('AAPL', $wrongConstraint);
    }

    public function testCustomMessageIsUsed(): void
    {
        $customMessage = 'Custom error message for {{ symbol }}';
        $constraint = new ValidCompanySymbol(message: $customMessage);

        $this->assertEquals($customMessage, $constraint->message);
    }
}

