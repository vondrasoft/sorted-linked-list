<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Vondrasoft\DataStructures\SortedLinkedList;
use Random\RandomException;

/**
 * @param callable(): void $callback
 */
function benchmark(string $label, callable $callback, bool $silent = false): float
{
    $start = hrtime(true);
    $callback();
    $elapsed = (hrtime(true) - $start) / 1_000_000;

    if ($silent === false) {
        printf("  %-40s %8.2f ms\n", $label, $elapsed);
    }

    return $elapsed;
}

/** @return list<int> */
function generateRandomIntegers(int $count): array
{
    $data = range(1, $count);
    shuffle($data);

    return $data;
}

/** @return list<string>
 * @throws RandomException
 */
function generateRandomStrings(int $count): array
{
    $data = [];

    for ($i = 0; $i < $count; $i++) {
        $data[] = bin2hex(random_bytes(8));
    }

    return $data;
}

/**
 * @param list<int|string> $data
 */
function benchmarkInsertion(string $label, array $data, bool $silent = false): float
{
    return benchmark($label, static function () use ($data): void {
        $list = new SortedLinkedList();

        foreach ($data as $value) {
            $list->add($value);
        }
    }, $silent);
}

$sizes = [100, 500, 1_000, 5_000, 10_000];

echo "=== SortedLinkedList Benchmark ===\n\n";

// ── Integer insertion (random order) ──
echo "Integer insertion (random order):\n";
$prevTime = null;

foreach ($sizes as $size) {
    $time = benchmarkInsertion(sprintf('add() x %s', number_format($size)), generateRandomIntegers($size));

    if ($prevTime !== null && $prevTime > 0) {
        printf("    ratio vs previous size: %.2fx\n", $time / $prevTime);
    }

    $prevTime = $time;
}

// ── String insertion (random order) ──
echo "\nString insertion (random order):\n";
$prevTime = null;

foreach ($sizes as $size) {
    $time = benchmarkInsertion(sprintf('add() x %s', number_format($size)), generateRandomStrings($size));

    if ($prevTime !== null && $prevTime > 0) {
        printf("    ratio vs previous size: %.2fx\n", $time / $prevTime);
    }

    $prevTime = $time;
}

// ── Integer insertion (already sorted — worst case) ──
echo "\nInteger insertion (already sorted — worst case for traversal):\n";

foreach ($sizes as $size) {
    benchmarkInsertion(sprintf('add() x %s', number_format($size)), range(1, $size));
}

// ── Integer insertion (reverse sorted — best case) ──
echo "\nInteger insertion (reverse sorted — best case, always head):\n";

foreach ($sizes as $size) {
    benchmarkInsertion(sprintf('add() x %s', number_format($size)), range($size, 1, -1));
}

// ── Removal ──
echo "\nRemoval (remove all elements from head):\n";

foreach ($sizes as $size) {
    $list = new SortedLinkedList();

    for ($i = 1; $i <= $size; $i++) {
        $list->add($i);
    }

    benchmark(sprintf('remove() x %s', number_format($size)), static function () use ($list, $size): void {
        for ($i = 1; $i <= $size; $i++) {
            $list->remove($i);
        }
    });
}

// ── Iteration ──
echo "\nIteration (toArray + foreach):\n";

foreach ($sizes as $size) {
    $list = new SortedLinkedList();
    $data = generateRandomIntegers($size);

    foreach ($data as $value) {
        $list->add($value);
    }

    benchmark(sprintf('toArray() on %s elements', number_format($size)), static function () use ($list): void {
        $result = $list->toArray();
        assert($result !== []);
    });

    benchmark(sprintf('foreach on %s elements', number_format($size)), static function () use ($list): void {
        $last = null;

        foreach ($list as $value) {
            $last = $value;
        }

        assert($last !== null);
    });
}

// ── contains(): middle element ──
echo "\nContains — middle element — while (node traversal) vs foreach (generator) vs in_array:\n";
printf("  %-20s %12s %12s %12s\n", 'Size', 'while', 'foreach', 'in_array');
printf("  %s\n", str_repeat('-', 60));

foreach ($sizes as $size) {
    $list = new SortedLinkedList();
    $data = generateRandomIntegers($size);

    foreach ($data as $value) {
        $list->add($value);
    }

    $array = $list->toArray();

    $needle = (int) ceil($size / 2);
    $iterations = 1_000;

    $whileTime = benchmark('', static function () use ($list, $needle, $iterations): void {
        for ($i = 0; $i < $iterations; $i++) {
            $list->contains($needle);
        }
    }, true);

    $foreachTime = benchmark('', static function () use ($list, $needle, $iterations): void {
        for ($i = 0; $i < $iterations; $i++) {
            foreach ($list as $value) {
                if ($value === $needle) {
                    break;
                }
            }
        }
    }, true);

    $inArrayTime = benchmark('', static function () use ($array, $needle, $iterations): void {
        for ($i = 0; $i < $iterations; $i++) {
            $found = in_array($needle, $array, true);
            assert($found);
        }
    }, true);

    printf(
        "  %-20s %10.2f ms %10.2f ms %10.2f ms\n",
        sprintf('%s elements', number_format($size)),
        $whileTime,
        $foreachTime,
        $inArrayTime,
    );
}

// ── last(): tail O(1) vs while vs foreach vs array end() ──
echo "\nLast element — last() tail O(1) vs while (node traversal) vs foreach (generator) vs array end():\n";
printf("  %-20s %12s %12s %12s %12s\n", 'Size', 'tail O(1)', 'while', 'foreach', 'end()');
printf("  %s\n", str_repeat('-', 72));

$headProperty = new ReflectionProperty(SortedLinkedList::class, 'head');

foreach ($sizes as $size) {
    $list = new SortedLinkedList();
    $data = range(1, $size);

    foreach ($data as $value) {
        $list->add($value);
    }

    $array = $list->toArray();
    $iterations = 1_000;

    $tailTime = benchmark('', static function () use ($list, $iterations): void {
        for ($i = 0; $i < $iterations; $i++) {
            $last = $list->last();
            assert($last !== null);
        }
    }, true);

    $whileTime = benchmark('', static function () use ($list, $headProperty, $iterations): void {
        for ($i = 0; $i < $iterations; $i++) {
            $current = $headProperty->getValue($list);
            assert($current !== null);

            while ($current->next !== null) {
                $current = $current->next;
            }

            $last = $current->value;
            assert($last !== null);
        }
    }, true);

    $foreachTime = benchmark('', static function () use ($list, $iterations): void {
        for ($i = 0; $i < $iterations; $i++) {
            $last = null;

            foreach ($list as $value) {
                $last = $value;
            }

            assert($last !== null);
        }
    }, true);

    $endTime = benchmark('', static function () use ($array, $iterations): void {
        for ($i = 0; $i < $iterations; $i++) {
            $last = end($array);
            assert($last !== false);
        }
    }, true);

    printf(
        "  %-20s %10.2f ms %10.2f ms %10.2f ms %10.2f ms\n",
        sprintf('%s elements', number_format($size)),
        $tailTime,
        $whileTime,
        $foreachTime,
        $endTime,
    );
}

// ── Memory: SortedLinkedList vs array ──
echo "\nMemory — SortedLinkedList vs plain array:\n";
printf("  %-20s %16s %16s %8s\n", 'Size', 'LinkedList', 'array', 'ratio');
printf("  %s\n", str_repeat('-', 66));

/**
 * Measures memory consumed by a callable that returns the structure to keep alive.
 *
 * @param callable(): mixed $factory
 * @return array{int, mixed}
 */
function measureMemory(callable $factory): array
{
    gc_collect_cycles();
    gc_disable();
    $before = memory_get_usage();
    $ref = $factory();
    $after = memory_get_usage();
    gc_enable();

    return [$after - $before, $ref];
}

foreach ($sizes as $size) {
    $data = range(1, $size);

    [$listMem] = measureMemory(static function () use ($data): SortedLinkedList {
        $list = new SortedLinkedList();

        foreach ($data as $value) {
            $list->add($value);
        }

        return $list;
    });

    [$arrayMem] = measureMemory(static function () use ($data): array {
        $array = [];

        foreach ($data as $value) {
            $array[] = $value;
        }

        return $array;
    });

    printf(
        "  %-20s %12s %12s %7.1fx\n",
        sprintf('%s elements', number_format($size)),
        sprintf('%.1f KB', $listMem / 1024),
        sprintf('%.1f KB', $arrayMem / 1024),
        $listMem / max($arrayMem, 1),
    );
}

// ── Comparison: SortedLinkedList vs array + sort() ──
echo "\nComparison — SortedLinkedList vs array + sort() (random integers):\n";
printf("  %-20s %12s %12s %8s\n", 'Size', 'LinkedList', 'array+sort', 'ratio');
printf("  %s\n", str_repeat('-', 56));

foreach ($sizes as $size) {
    $data = generateRandomIntegers($size);

    $listTime = benchmarkInsertion('', $data, true);

    $arrayTime = benchmark('', static function () use ($data): void {
        $array = [];

        foreach ($data as $value) {
            $array[] = $value;
        }

        sort($array);
    }, true);

    printf(
        "  %-20s %10.2f ms %10.2f ms %7.1fx\n",
        sprintf('%s elements', number_format($size)),
        $listTime,
        $arrayTime,
        $listTime / max($arrayTime, 0.001),
    );
}

echo "\nDone.\n";
