<?php declare(strict_types = 1);

namespace Vondrasoft\DataStructures;

use function is_int;

enum ValueType: string
{

    case Integer = 'integer';
    case String = 'string';

    public static function fromValue(int|string $value): self
    {
        return is_int($value)
            ? self::Integer
            : self::String;
    }

}
