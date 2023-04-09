<?php

declare(strict_types=1);

namespace TheCodingMachine\GraphQLite\Validator\Annotations;

use BadMethodCallException;
use Symfony\Component\Validator\Constraint;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotationInterface;
use function is_array;
use function ltrim;

/**
 * Use this annotation to validate a parameter for a query or mutation.
 *
 * @Annotation
 * @Target({"METHOD"})
 * @Attributes({
 *   @Attribute("for", type = "string"),
 *   @Attribute("constraint", type = "Symfony\Component\Validator\Constraint[]|Symfony\Component\Validator\Constraint")
 * })
 */
class Assertion implements ParameterAnnotationInterface
{
    /** @var Constraint[] */
    private array $constraint;

    private string $for;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values)
    {
        if (!isset($values['for'])) {
            throw new BadMethodCallException(
                'The @Assert annotation must be passed a target. For instance: "@Assert(for="$email", constraint=@Email)"'
            );
        }

        if (!isset($values['constraint'])) {
            throw new BadMethodCallException(
                'The @Assert annotation must be passed one or many constraints. For instance: "@Assert(for="$email", constraint=@Email)"'
            );
        }

        $this->for = ltrim($values['for'], '$');
        $this->constraint = is_array($values['constraint']) ? $values['constraint'] : [$values['constraint']];
    }

    /**
     * @return Constraint[]
     */
    public function getConstraint(): array
    {
        return $this->constraint;
    }

    public function getTarget(): string
    {
        return $this->for;
    }
}
