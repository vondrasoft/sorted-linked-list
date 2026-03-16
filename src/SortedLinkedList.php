<?php declare(strict_types = 1);

namespace Vondrasoft\DataStructures;

use Countable;
use Generator;
use IteratorAggregate;
use Vondrasoft\DataStructures\Exception\InvalidTypeException;
use function assert;

/**
 * A singly linked list that keeps its elements sorted in ascending order.
 *
 * The list is homogeneous — it accepts either int or string values, but never
 * both at the same time. The type is locked upon the first insertion.
 *
 * @template T of int|string
 * @implements IteratorAggregate<int, T>
 */
final class SortedLinkedList implements IteratorAggregate, Countable
{

    /**
     * @var Node<T>|null
     */
    private ?Node $head = null;

    /**
     * @var Node<T>|null
     */
    private ?Node $tail = null;

    /**
     * @var int<0, max>
     */
    private int $count = 0;

    private ?ValueType $lockedType = null;

    /**
     * Add a value to the list while maintaining sorted order.
     *
     * Time complexity: O(n).
     *
     * @throws InvalidTypeException If the value type does not match the list type.
     */
    public function add(int|string $value): void
    {
        $valueType = ValueType::fromValue($value);

        if ($this->lockedType === null) {
            $this->lockedType = $valueType;
        } elseif ($this->lockedType !== $valueType) {
            throw InvalidTypeException::create($this->lockedType, $valueType);
        }

        /** @var Node<T> $newNode */
        $newNode = new Node($value);

        // Insert into an empty list.
        if ($this->head === null) {
            $this->head = $newNode;
            $this->tail = $newNode;
            $this->count++;

            return;
        }

        // Insert before head when the new value is smaller.
        if ($value < $this->head->value) {
            $newNode->setNext($this->head);
            $this->head = $newNode;
            $this->count++;

            return;
        }

        // Append after tail when the new value is the largest — O(1).
        if ($this->tail !== null && $value >= $this->tail->value) {
            $this->tail->setNext($newNode);
            $this->tail = $newNode;
            $this->count++;

            return;
        }

        // Traverse to find the correct position.
        $current = $this->head;

        while ($current->next !== null && $current->next->value <= $value) {
            $current = $current->next;
        }

        $newNode->setNext($current->next);
        $current->setNext($newNode);
        $this->count++;
    }

    /**
     * Remove the first occurrence of the given value.
     *
     * @return bool True if the value was found and removed, false otherwise.
     */
    public function remove(int|string $value): bool
    {
        if ($this->head === null) {
            return false;
        }

        // Removing the head node.
        if ($this->head->value === $value) {
            $this->head = $this->head->next;
            $this->decrementCount();

            if ($this->count === 0) {
                $this->tail = null;
                $this->lockedType = null;
            }

            return true;
        }

        $current = $this->head;

        while ($current->next !== null) {
            if ($current->next->value === $value) {
                // Update tail if we are removing the last node.
                if ($current->next === $this->tail) {
                    $this->tail = $current;
                }

                $current->setNext($current->next->next);
                $this->decrementCount();

                return true;
            }

            // The list is sorted — no point in searching further.
            if ($current->next->value > $value) {
                return false;
            }

            $current = $current->next;
        }

        return false;
    }

    /**
     * Return all values as a sorted array.
     *
     * @return list<T>
     */
    public function toArray(): array
    {
        $result = [];
        $current = $this->head;

        while ($current !== null) {
            /** @var T $currentValue */
            $currentValue = $current->value;
            $result[] = $currentValue;
            $current = $current->next;
        }

        return $result;
    }

    /** @return Generator<int, T> */
    public function getIterator(): Generator
    {
        $current = $this->head;

        while ($current !== null) {
            /** @var T $value */
            $value = $current->value;
            yield $value;
            $current = $current->next;
        }
    }

    public function count(): int
    {
        return $this->count;
    }

    public function isEmpty(): bool
    {
        return $this->count === 0;
    }

    /**
     * Checks whether the list contains the given value.
     *
     * Returns false if the value type does not match the list type.
     */
    public function contains(int|string $value): bool
    {
        $valueType = ValueType::fromValue($value);

        if ($this->lockedType !== $valueType) {
            return false;
        }

        $current = $this->head;

        while ($current !== null) {
            if ($current->value === $value) {
                return true;
            }

            if ($current->value > $value) {
                return false;
            }

            $current = $current->next;
        }

        return false;
    }

    /**
     * Return the first (smallest) value in the list, or null if the list is empty.
     *
     * Time complexity: O(1).
     *
     * @return T|null
     */
    public function first(): int|string|null
    {
        return $this->head?->value;
    }

    /**
     * Return the last (largest) value in the list, or null if the list is empty.
     *
     * Time complexity: O(1).
     *
     * @return T|null
     */
    public function last(): int|string|null
    {
        return $this->tail?->value;
    }

    /**
     * Remove all occurrences of the given value.
     *
     * @return int The number of elements removed.
     */
    public function removeAll(int|string $value): int
    {
        $removed = 0;

        // Remove matching head nodes.
        while ($this->head !== null && $this->head->value === $value) {
            $this->head = $this->head->next;
            $this->decrementCount();
            $removed++;
        }

        if ($this->head === null) {
            if ($removed > 0) {
                $this->tail = null;
                $this->lockedType = null;
            }

            return $removed;
        }

        // Remove matching nodes after head.
        $current = $this->head;

        while ($current->next !== null) {
            if ($current->next->value === $value) {
                $current->setNext($current->next->next);
                $this->decrementCount();
                $removed++;

                // Update tail if we removed the last node.
                if ($current->next === null) {
                    $this->tail = $current;
                }

                continue;
            }

            // The list is sorted — no point in searching further.
            if ($current->next->value > $value) {
                break;
            }

            $current = $current->next;
        }

        return $removed;
    }

    /**
     * Remove all elements from the list, resetting it to its initial state.
     */
    public function clear(): void
    {
        $this->head = null;
        $this->tail = null;
        $this->count = 0;
        $this->lockedType = null;
    }

    /**
     * Asserts the count invariant and decrements.
     * The assert also helps PHPStan (level 9) narrow int<0, max> after decrement.
     */
    private function decrementCount(): void
    {
        assert($this->count > 0);
        $this->count--;
    }

}
