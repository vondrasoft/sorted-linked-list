<?php declare(strict_types = 1);

namespace Vondrasoft\DataStructures\Tests;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Vondrasoft\DataStructures\Exception\InvalidTypeException;
use Vondrasoft\DataStructures\Node;
use Vondrasoft\DataStructures\SortedLinkedList;
use Vondrasoft\DataStructures\ValueType;
use function range;
use function shuffle;

#[CoversClass(SortedLinkedList::class)]
#[CoversClass(Node::class)]
#[CoversClass(ValueType::class)]
#[CoversClass(InvalidTypeException::class)]
final class SortedLinkedListTest extends TestCase
{

    public function testEmptyListHasZeroCount(): void
    {
        $list = new SortedLinkedList();

        self::assertCount(0, $list);
    }

    public function testEmptyListReturnsEmptyArray(): void
    {
        $list = new SortedLinkedList();

        self::assertSame([], $list->toArray());
    }

    public function testEmptyListIsIterable(): void
    {
        $list = new SortedLinkedList();
        $values = [];

        foreach ($list as $value) {
            $values[] = $value;
        }

        self::assertSame([], $values);
    }

    public function testSingleIntElement(): void
    {
        $list = new SortedLinkedList();
        $list->add(42);

        self::assertCount(1, $list);
        self::assertSame([42], $list->toArray());
    }

    public function testSingleStringElement(): void
    {
        $list = new SortedLinkedList();
        $list->add('hello');

        self::assertCount(1, $list);
        self::assertSame(['hello'], $list->toArray());
    }

    public function testIntegersAreInsertedInSortedOrder(): void
    {
        $list = new SortedLinkedList();
        $list->add(5);
        $list->add(1);
        $list->add(3);
        $list->add(4);
        $list->add(2);

        self::assertSame([1, 2, 3, 4, 5], $list->toArray());
    }

    public function testDuplicateIntegersAreAllowed(): void
    {
        $list = new SortedLinkedList();
        $list->add(3);
        $list->add(1);
        $list->add(3);
        $list->add(1);

        self::assertSame([1, 1, 3, 3], $list->toArray());
    }

    public function testNegativeIntegers(): void
    {
        $list = new SortedLinkedList();
        $list->add(-10);
        $list->add(5);
        $list->add(-3);
        $list->add(0);

        self::assertSame([-10, -3, 0, 5], $list->toArray());
    }

    public function testAlreadySortedIntegers(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->add(3);

        self::assertSame([1, 2, 3], $list->toArray());
    }

    public function testReverseSortedIntegers(): void
    {
        $list = new SortedLinkedList();
        $list->add(3);
        $list->add(2);
        $list->add(1);

        self::assertSame([1, 2, 3], $list->toArray());
    }

    public function testStringsAreInsertedInSortedOrder(): void
    {
        $list = new SortedLinkedList();
        $list->add('cherry');
        $list->add('apple');
        $list->add('banana');

        self::assertSame(['apple', 'banana', 'cherry'], $list->toArray());
    }

    public function testDuplicateStringsAreAllowed(): void
    {
        $list = new SortedLinkedList();
        $list->add('b');
        $list->add('a');
        $list->add('b');

        self::assertSame(['a', 'b', 'b'], $list->toArray());
    }

    public function testStringsAreSortedCaseSensitive(): void
    {
        $list = new SortedLinkedList();
        $list->add('banana');
        $list->add('Apple');
        $list->add('cherry');

        // Uppercase letters (ASCII 65–90) sort before lowercase (ASCII 97–122)
        self::assertSame(['Apple', 'banana', 'cherry'], $list->toArray());
    }

    public function testEmptyStringIsSortedFirst(): void
    {
        $list = new SortedLinkedList();
        $list->add('b');
        $list->add('');
        $list->add('a');

        self::assertSame(['', 'a', 'b'], $list->toArray());
    }

    public function testStringsSortedByAlreadySortedInput(): void
    {
        $list = new SortedLinkedList();
        $list->add('alpha');
        $list->add('bravo');
        $list->add('charlie');

        self::assertSame(['alpha', 'bravo', 'charlie'], $list->toArray());
    }

    public function testStringsSortedByReversedInput(): void
    {
        $list = new SortedLinkedList();
        $list->add('charlie');
        $list->add('bravo');
        $list->add('alpha');

        self::assertSame(['alpha', 'bravo', 'charlie'], $list->toArray());
    }

    public function testAddingStringToIntListThrowsException(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);

        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('Cannot add value of type "string" to a list locked to type "integer".');

        $list->add('hello');
    }

    public function testAddingIntToStringListThrowsException(): void
    {
        $list = new SortedLinkedList();
        $list->add('hello');

        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('Cannot add value of type "integer" to a list locked to type "string".');

        $list->add(1);
    }

    public function testTypeLockResetsAfterRemovingAllElements(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->remove(1);

        // The type lock is released — we can now insert strings.
        $list->add('hello');

        self::assertSame(['hello'], $list->toArray());
    }

    public function testRemoveFromEmptyListReturnsFalse(): void
    {
        $list = new SortedLinkedList();

        self::assertFalse($list->remove(1));
    }

    public function testRemoveNonExistentValueReturnsFalse(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(3);
        $list->add(5);

        // Value beyond all elements — traverses the whole list.
        self::assertFalse($list->remove(99));
        // Value between elements — triggers sorted early return.
        self::assertFalse($list->remove(2));
    }

    public function testRemoveHead(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->add(3);

        self::assertTrue($list->remove(1));
        self::assertSame([2, 3], $list->toArray());
        self::assertCount(2, $list);
    }

    public function testRemoveMiddle(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->add(3);

        self::assertTrue($list->remove(2));
        self::assertSame([1, 3], $list->toArray());
        self::assertCount(2, $list);
    }

    public function testRemoveTail(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->add(3);

        self::assertTrue($list->remove(3));
        self::assertSame([1, 2], $list->toArray());
        self::assertCount(2, $list);
    }

    public function testRemoveOnlyElement(): void
    {
        $list = new SortedLinkedList();
        $list->add(42);

        self::assertTrue($list->remove(42));
        self::assertSame([], $list->toArray());
        self::assertCount(0, $list);
    }

    public function testRemoveDuplicateRemovesOnlyFirstOccurrence(): void
    {
        $list = new SortedLinkedList();
        $list->add(2);
        $list->add(2);
        $list->add(2);

        self::assertTrue($list->remove(2));
        self::assertSame([2, 2], $list->toArray());
    }

    public function testRemoveString(): void
    {
        $list = new SortedLinkedList();
        $list->add('apple');
        $list->add('banana');
        $list->add('cherry');

        self::assertTrue($list->remove('banana'));
        self::assertSame(['apple', 'cherry'], $list->toArray());
    }

    public function testCountReflectsAdditionsAndRemovals(): void
    {
        $list = new SortedLinkedList();

        self::assertCount(0, $list);

        $list->add(10);
        $list->add(20);
        self::assertCount(2, $list);

        $list->remove(10);
        self::assertCount(1, $list);
    }

    public function testForeachIteratesInSortedOrder(): void
    {
        $list = new SortedLinkedList();
        $list->add(3);
        $list->add(1);
        $list->add(2);

        $values = [];

        foreach ($list as $value) {
            $values[] = $value;
        }

        self::assertSame([1, 2, 3], $values);
    }

    public function testIsEmptyOnNewList(): void
    {
        $list = new SortedLinkedList();

        self::assertTrue($list->isEmpty());
    }

    public function testIsEmptyReturnsFalseAfterAdd(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);

        self::assertFalse($list->isEmpty());
    }

    public function testIsEmptyReturnsTrueAfterRemovingAllElements(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->remove(1);
        $list->remove(2);

        self::assertTrue($list->isEmpty());
    }

    public function testContainsOnEmptyList(): void
    {
        $list = new SortedLinkedList();

        self::assertFalse($list->contains(1));
    }

    public function testContainsFindsExistingInt(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->add(3);

        self::assertTrue($list->contains(2));
    }

    public function testContainsReturnsFalseForMissingInt(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(3);

        self::assertFalse($list->contains(2));
    }

    public function testContainsFindsExistingString(): void
    {
        $list = new SortedLinkedList();
        $list->add('apple');
        $list->add('banana');

        self::assertTrue($list->contains('banana'));
    }

    public function testContainsReturnsFalseForMissingString(): void
    {
        $list = new SortedLinkedList();
        $list->add('apple');
        $list->add('cherry');

        self::assertFalse($list->contains('banana'));
    }

    public function testContainsReturnsFalseForWrongType(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);

        self::assertFalse($list->contains('1'));
    }

    public function testContainsFindsHeadAndTail(): void
    {
        $list = new SortedLinkedList();
        $list->add(10);
        $list->add(20);
        $list->add(30);

        self::assertTrue($list->contains(10));
        self::assertTrue($list->contains(30));
    }

    public function testContainsReturnsFalseForValueBeyondTail(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);

        self::assertFalse($list->contains(99));
    }

    public function testFirstOnEmptyListReturnsNull(): void
    {
        $list = new SortedLinkedList();

        self::assertNull($list->first());
    }

    public function testFirstReturnsSingleElement(): void
    {
        $list = new SortedLinkedList();
        $list->add(42);

        self::assertSame(42, $list->first());
    }

    public function testFirstReturnsSmallestInt(): void
    {
        $list = new SortedLinkedList();
        $list->add(5);
        $list->add(1);
        $list->add(3);

        self::assertSame(1, $list->first());
    }

    public function testFirstReturnsSmallestString(): void
    {
        $list = new SortedLinkedList();
        $list->add('cherry');
        $list->add('apple');
        $list->add('banana');

        self::assertSame('apple', $list->first());
    }

    public function testLastOnEmptyListReturnsNull(): void
    {
        $list = new SortedLinkedList();

        self::assertNull($list->last());
    }

    public function testLastReturnsSingleElement(): void
    {
        $list = new SortedLinkedList();
        $list->add(42);

        self::assertSame(42, $list->last());
    }

    public function testLastReturnsLargestInt(): void
    {
        $list = new SortedLinkedList();
        $list->add(5);
        $list->add(1);
        $list->add(3);

        self::assertSame(5, $list->last());
    }

    public function testLastReturnsLargestString(): void
    {
        $list = new SortedLinkedList();
        $list->add('cherry');
        $list->add('apple');
        $list->add('banana');

        self::assertSame('cherry', $list->last());
    }

    public function testRemoveAllFromEmptyListReturnsZero(): void
    {
        $list = new SortedLinkedList();

        self::assertSame(0, $list->removeAll(1));
    }

    public function testRemoveAllNonExistentValueReturnsZero(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);

        self::assertSame(0, $list->removeAll(99));
        self::assertSame([1, 2], $list->toArray());
    }

    public function testRemoveAllSingleOccurrence(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->add(3);

        self::assertSame(1, $list->removeAll(2));
        self::assertSame([1, 3], $list->toArray());
        self::assertCount(2, $list);
    }

    public function testRemoveAllMultipleOccurrences(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->add(2);
        $list->add(2);
        $list->add(3);

        self::assertSame(3, $list->removeAll(2));
        self::assertSame([1, 3], $list->toArray());
        self::assertCount(2, $list);
    }

    public function testRemoveAllFromHead(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(1);
        $list->add(2);
        $list->add(3);

        self::assertSame(2, $list->removeAll(1));
        self::assertSame([2, 3], $list->toArray());
        self::assertCount(2, $list);
    }

    public function testRemoveAllFromTail(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->add(3);
        $list->add(3);

        self::assertSame(2, $list->removeAll(3));
        self::assertSame([1, 2], $list->toArray());
        self::assertCount(2, $list);
    }

    public function testRemoveAllElements(): void
    {
        $list = new SortedLinkedList();
        $list->add(5);
        $list->add(5);
        $list->add(5);

        self::assertSame(3, $list->removeAll(5));
        self::assertSame([], $list->toArray());
        self::assertCount(0, $list);
        self::assertTrue($list->isEmpty());
    }

    public function testRemoveAllResetsTypeLockWhenEmpty(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(1);

        $list->removeAll(1);

        // Type lock should be reset — can now add strings.
        $list->add('hello');

        self::assertSame(['hello'], $list->toArray());
    }

    public function testRemoveAllStrings(): void
    {
        $list = new SortedLinkedList();
        $list->add('apple');
        $list->add('banana');
        $list->add('banana');
        $list->add('cherry');

        self::assertSame(2, $list->removeAll('banana'));
        self::assertSame(['apple', 'cherry'], $list->toArray());
    }

    public function testClearEmptiesTheList(): void
    {
        $list = new SortedLinkedList();
        $list->add(3);
        $list->add(1);
        $list->add(2);

        $list->clear();

        self::assertCount(0, $list);
        self::assertTrue($list->isEmpty());
        self::assertSame([], $list->toArray());
    }

    public function testClearResetsTypeLock(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);

        $list->clear();

        // After clear, we should be able to add strings to a previously int-locked list.
        $list->add('hello');

        self::assertSame(['hello'], $list->toArray());
    }

    public function testClearOnEmptyListIsNoOp(): void
    {
        $list = new SortedLinkedList();

        $list->clear();

        self::assertCount(0, $list);
        self::assertTrue($list->isEmpty());
    }

    public function testClearAllowsReuse(): void
    {
        $list = new SortedLinkedList();
        $list->add(5);
        $list->add(3);
        $list->add(7);

        $list->clear();

        $list->add(10);
        $list->add(20);

        self::assertSame([10, 20], $list->toArray());
        self::assertCount(2, $list);
    }

    public function testNodeCannotReferenceSelf(): void
    {
        $node = new Node(1);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Node cannot reference itself.');

        $node->setNext($node);
    }

    public function testLastUpdatesAfterRemovingTailElement(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->add(3);

        $list->remove(3);

        self::assertSame(2, $list->last());
    }

    public function testLastIsNullAfterRemovingOnlyElement(): void
    {
        $list = new SortedLinkedList();
        $list->add(42);

        $list->remove(42);

        self::assertNull($list->last());
    }

    public function testLastUpdatesAfterRemoveAllFromTail(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->add(3);
        $list->add(3);

        $list->removeAll(3);

        self::assertSame(2, $list->last());
    }

    public function testLastIsNullAfterClear(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->add(3);

        $list->clear();

        self::assertNull($list->last());
    }

    public function testLastCorrectAfterRemovingFirstOfDuplicateTail(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(3);
        $list->add(3);

        self::assertSame(3, $list->last());

        $list->remove(3);

        self::assertSame(3, $list->last());
    }

    public function testLastUnchangedAfterRemovingHead(): void
    {
        $list = new SortedLinkedList();
        $list->add(1);
        $list->add(2);
        $list->add(3);

        $list->remove(1);

        self::assertSame(3, $list->last());
    }

    public function testLargeDatasetRemainsSorted(): void
    {
        $list = new SortedLinkedList();
        $input = range(1, 200);
        shuffle($input);

        foreach ($input as $num) {
            $list->add($num);
        }

        self::assertSame(range(1, 200), $list->toArray());
        self::assertCount(200, $list);
    }

}
