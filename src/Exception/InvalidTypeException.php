<?php declare(strict_types = 1);

namespace Vondrasoft\DataStructures\Exception;

use InvalidArgumentException;
use Vondrasoft\DataStructures\ValueType;
use function sprintf;

final class InvalidTypeException extends InvalidArgumentException
{

    public static function create(
        ValueType $expectedType,
        ValueType $actualType,
    ): self
    {
        return new self(
            sprintf(
                'Cannot add value of type "%s" to a list locked to type "%s".',
                $actualType->value,
                $expectedType->value,
            ),
        );
    }

}
