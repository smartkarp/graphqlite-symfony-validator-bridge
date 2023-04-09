<?php

declare(strict_types=1);

namespace TheCodingMachine\GraphQLite\Validator\Mappers\Parameters;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameterInterface;
use TheCodingMachine\GraphQLite\Validator\ValidationFailedException;

class ParameterValidator implements InputTypeParameterInterface
{
    public function __construct(
        private readonly InputTypeParameterInterface         $parameter,
        private readonly string                              $parameterName,
        private readonly array                               $constraints,
        private readonly ConstraintValidatorFactoryInterface $constraintValidatorFactory,
        private readonly ValidatorInterface                  $validator,
        private readonly TranslatorInterface                 $translator
    ) {
    }

    public function getDefaultValue(): mixed
    {
        return $this->parameter->getDefaultValue();
    }

    public function getType(): InputType&Type
    {
        return $this->parameter->getType();
    }

    public function hasDefaultValue(): bool
    {
        return $this->parameter->hasDefaultValue();
    }

    public function resolve(?object $source, array $args, mixed $context, ResolveInfo $info): mixed
    {
        $value = $this->parameter->resolve($source, $args, $context, $info);

        $executionContext = new ExecutionContext($this->validator, $this->parameterName, $this->translator, null);

        foreach ($this->constraints as $constraint) {
            $validator = $this->constraintValidatorFactory->getInstance($constraint);
            $validator->initialize($executionContext);
            $executionContext->setConstraint($constraint);
            $executionContext->setNode($value, $source, null, $this->parameterName);
            $validator->validate($value, $constraint);
        }

        $violations = $executionContext->getViolations();

        ValidationFailedException::throwException($violations);

        return $value;
    }
}
