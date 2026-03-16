<?php declare(strict_types = 1);

namespace Vondrasoft\DataStructures;

use LogicException;

/**
 * @template T of int|string
 *
 * @internal
 */
final class Node
{

    public function __construct(
        /** @var T */
        public readonly int|string $value,
        /** @var self<T>|null $next */
        private(set) ?self $next = null,
    )
    {
    }

    /** @param self<T>|null $next */
    public function setNext(?self $next): void
    {
        if ($next === $this) {
            throw new LogicException('Node cannot reference itself.');
        }

        $this->next = $next;
    }

}
