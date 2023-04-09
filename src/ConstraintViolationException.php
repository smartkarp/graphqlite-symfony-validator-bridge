<?php

declare(strict_types=1);

namespace TheCodingMachine\GraphQLite\Validator;

use Exception;
use Symfony\Component\Validator\ConstraintViolationInterface;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLExceptionInterface;

class ConstraintViolationException extends Exception implements GraphQLExceptionInterface
{
    public function __construct(
        private readonly ConstraintViolationInterface $violation
    ) {
        parent::__construct((string) $violation->getMessage(), 400);
    }

    /**
     * Returns string describing a category of the error.
     *
     * Value "graphql" is reserved for errors produced by query parsing or validation, do not use it.
     */
    public function getCategory(): string
    {
        return 'Validate';
    }

    /**
     * Returns the "extensions" object attached to the GraphQL error.
     */
    public function getExtensions(): array
    {
        $extensions = [];
        $code = $this->violation->getCode();

        if (!empty($code)) {
            $extensions['code'] = $code;
        }

        $propertyPath = $this->violation->getPropertyPath();

        if (!empty($propertyPath)) {
            $extensions['field'] = $propertyPath;
        }

        return $extensions;
    }

    /**
     * Returns true when exception message is safe to be displayed to a client.
     */
    public function isClientSafe(): bool
    {
        return true;
    }
}
