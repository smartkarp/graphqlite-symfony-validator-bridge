<?php

declare(strict_types=1);

namespace TheCodingMachine\GraphQLite\Validator\Mappers\Parameters;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use ReflectionParameter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotations;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterHandlerInterface;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMiddlewareInterface;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameterInterface;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;
use TheCodingMachine\GraphQLite\Validator\Annotations\Assertion;
use function array_map;
use function array_merge;

/**
 * A parameter middleware that reads "Assert" annotations.
 */
class AssertParameterMiddleware implements ParameterMiddlewareInterface
{
    public function __construct(
        private readonly ConstraintValidatorFactoryInterface $constraintValidatorFactory,
        private readonly ValidatorInterface                  $validator,
        private readonly TranslatorInterface                 $translator
    ) {
    }

    /**
     * @throws InvalidAssertionAnnotationException
     */
    public function mapParameter(
        ReflectionParameter       $refParameter,
        DocBlock                  $docBlock,
        ?Type                     $paramTagType,
        ParameterAnnotations      $parameterAnnotations,
        ParameterHandlerInterface $next
    ): ParameterInterface {
        /** @var Assertion[] $assertionAnnotations */
        $assertionAnnotations = $parameterAnnotations->getAnnotationsByType(Assertion::class);

        $parameter = $next->mapParameter($refParameter, $docBlock, $paramTagType, $parameterAnnotations);

        if (empty($assertionAnnotations)) {
            return $parameter;
        }

        if (!$parameter instanceof InputTypeParameterInterface) {
            throw InvalidAssertionAnnotationException::canOnlyValidateInputType($refParameter);
        }

        // Let's wrap the ParameterInterface into a ParameterValidator.
        /** @var Constraint[] $recursiveConstraints */
        $recursiveConstraints = array_map(
            static fn(Assertion $assertAnnotation): array => $assertAnnotation->getConstraint(),
            $assertionAnnotations
        );
        $constraints = array_merge(...$recursiveConstraints);

        return new ParameterValidator(
            $parameter,
            $refParameter->getName(),
            $constraints,
            $this->constraintValidatorFactory,
            $this->validator,
            $this->translator
        );
    }
}
