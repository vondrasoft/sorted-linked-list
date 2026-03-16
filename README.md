# SortedLinkedList

Type-safe, homogeneous sorted singly linked list for PHP 8.4+.

## Installation

```bash
composer require vondrasoft/sorted-linked-list
```

> **Note:** The repository includes a Docker setup for development purposes only (running tests, static analysis, benchmarks). It is not needed when using the library as a dependency in your project.

## Quick start (Docker)

The project uses a pre-built Docker image (`ghcr.io/vondrasoft/php-xdebug:8.4`) with PHP 8.4 + Xdebug + Composer, so you don't need to build anything locally. The `Dockerfile` is kept in the repository for reference.

```bash
# First-time setup (starts container + installs dependencies)
./init.sh

# Install dependencies
docker compose exec php composer install

# Run tests
docker compose exec php composer test

# Run tests with code coverage (Xdebug included in Docker image)
docker compose exec php composer test-coverage

# Run static analysis (PHPStan level 9 + strict rules)
docker compose exec php composer analyze

# Run code style check (PHPCS + ShipMonk Coding Standard)
docker compose exec php composer cs

# Auto-fix code style violations
docker compose exec php composer cs-fix

# Run benchmarks
docker compose exec php composer benchmark
```

## API

```php
use Vondrasoft\DataStructures\SortedLinkedList;

$list = new SortedLinkedList();

$list->add(5);
$list->add(1);
$list->add(3);

count($list);        // 3
$list->toArray();    // [1, 3, 5]
$list->contains(3);  // true
$list->isEmpty();    // false
$list->first();      // 1
$list->last();       // 5

foreach ($list as $value) {
    echo $value;     // 1, 3, 5
}

$list->remove(3);    // true  (removes first occurrence)
$list->removeAll(1); // 1     (returns number of removed elements)
$list->toArray();    // [5]

$list->clear();      // removes all elements, resets type lock
$list->toArray();    // []
count($list);        // 0
```

### Type safety

The list locks its type on first insertion. Mixing types throws `InvalidTypeException`:

```php
$list = new SortedLinkedList();
$list->add(1);
$list->add('hello'); // throws InvalidTypeException
```

The type lock resets when all elements are removed, allowing the list to be reused with a different type.

### String sorting

Strings are sorted using PHP's native `<` operator, which performs byte-by-byte (lexicographic) comparison. This works correctly for ASCII/English strings. For locale-aware sorting (e.g. Czech, German), a `Collator` from the `intl` extension would be needed — this is intentionally out of scope for a generic data structure.

## Project structure

```
src/
  SortedLinkedList.php              Main class (Iterator + Countable)
  Node.php                          Linked list node
  ValueType.php                     Enum for type locking
  Exception/InvalidTypeException.php
tests/
  SortedLinkedListTest.php          69 tests, 107 assertions
benchmarks/
  benchmark.php                     Performance benchmarks incl. array comparison
```

## Code coverage

| Class | Methods | Lines |
|-------|---------|-------|
| `InvalidTypeException` | 100.00% (1/1) | 100.00% (7/7) |
| `Node` | 100.00% (2/2) | 100.00% (4/4) |
| `SortedLinkedList` | 100.00% (12/12) | 100.00% (104/104) |
| `ValueType` | 100.00% (1/1) | 100.00% (3/3) |
| **Total** | **100.00% (16/16)** | **100.00% (118/118)** |

## Quality tools

| Tool | Level | Command |
|------|-------|---------|
| PHPUnit | 69 tests, 107 assertions | `composer test` / `composer test-coverage` |
| PHPStan | Level 9 + strict rules (phpstan-strict-rules, shipmonk/phpstan-rules) | `composer analyze` |
| PHPCS | ShipMonk Coding Standard | `composer cs` |

## Benchmarks

> **Note:** The pre-built Docker image is built for **arm64** (Apple Silicon / Mac). The benchmark results below reflect this architecture. Running the image on **amd64** (e.g. Linux servers) may result in significantly slower performance due to **QEMU userspace emulation** — Docker transparently translates arm64 instructions to amd64 at runtime, which adds substantial overhead. For accurate benchmarks on amd64, rebuild the image natively (`docker build --platform linux/amd64`).

Run `docker compose exec php composer benchmark` to measure performance across different scenarios:

- **Integer insertion (random order)** — demonstrates O(n) per insert, O(n²) total:

| Size | Time |
|------|------|
| 100 elements | 2.64 ms |
| 500 elements | 2.12 ms |
| 1,000 elements | 7.10 ms |
| 5,000 elements | 162.02 ms |
| 10,000 elements | 640.19 ms |

- **String insertion (random order):**

| Size | Time |
|------|------|
| 100 elements | 0.26 ms |
| 500 elements | 3.34 ms |
| 1,000 elements | 11.44 ms |
| 5,000 elements | 261.77 ms |
| 10,000 elements | 1,098.61 ms |

- **Sorted / reverse input** — sorted input appends via tail pointer O(1), reverse always inserts at head O(1). Both avoid traversal:

| Size | Already sorted | Reverse sorted |
|------|---------------|----------------|
| 100 elements | 0.10 ms | 0.11 ms |
| 500 elements | 0.47 ms | 0.45 ms |
| 1,000 elements | 0.86 ms | 0.82 ms |
| 5,000 elements | 4.07 ms | 4.03 ms |
| 10,000 elements | 8.23 ms | 7.90 ms |

- **Removal** (all elements from head):

| Size | Time |
|------|------|
| 100 elements | 0.07 ms |
| 500 elements | 0.22 ms |
| 1,000 elements | 0.46 ms |
| 5,000 elements | 2.18 ms |
| 10,000 elements | 4.45 ms |

- **Iteration** — `toArray()` (direct while loop) vs `foreach` (generator):

| Size | toArray() | foreach |
|------|-----------|---------|
| 100 elements | 0.01 ms | 0.03 ms |
| 500 elements | 0.03 ms | 0.10 ms |
| 1,000 elements | 0.05 ms | 0.20 ms |
| 5,000 elements | 0.24 ms | 0.81 ms |
| 10,000 elements | 0.48 ms | 1.63 ms |

- **`contains()`: while vs foreach vs in_array** — compares direct node traversal (`while`), generator-based iteration (`foreach`), and PHP's native `in_array()` on a plain array for the middle element. Direct node traversal is ~4x faster than the generator, but `in_array()` on a plain array is significantly faster thanks to C-level optimization and cache-friendly contiguous memory:

**Middle element (contains):**

| Size | while | foreach | in_array |
|------|-------|---------|----------|
| 100 elements | 2.35 ms | 7.75 ms | 0.31 ms |
| 500 elements | 9.67 ms | 38.10 ms | 0.48 ms |
| 1,000 elements | 18.99 ms | 76.67 ms | 0.71 ms |
| 5,000 elements | 92.94 ms | 372.95 ms | 2.29 ms |
| 10,000 elements | 186.13 ms | 756.94 ms | 4.38 ms |

- **Last element** — `last()` tail O(1) vs `while` (direct node traversal, simulates old `last()` without tail) vs `foreach` (generator) vs array `end()`. The tail pointer matches array `end()` performance — both are O(1) regardless of list size:

**Last element:**

| Size | tail O(1) | while | foreach | end() |
|------|-----------|-------|---------|-------|
| 100 elements | 0.25 ms | 2.29 ms | 16.09 ms | 0.23 ms |
| 500 elements | 0.26 ms | 9.44 ms | 78.43 ms | 0.23 ms |
| 1,000 elements | 0.25 ms | 18.38 ms | 156.64 ms | 0.24 ms |
| 5,000 elements | 0.25 ms | 86.98 ms | 766.63 ms | 0.25 ms |
| 10,000 elements | 0.26 ms | 174.49 ms | 1,523.88 ms | 0.23 ms |

*1,000 calls per scenario.*

- **Memory usage** — linked list vs plain array. Each node object carries overhead (~3x more memory than a plain array slot):

| Size | LinkedList | array | ratio |
|------|-----------|-------|-------|
| 100 elements | 7.9 KB | 2.6 KB | 3.1x |
| 500 elements | 39.2 KB | 12.1 KB | 3.2x |
| 1,000 elements | 78.2 KB | 20.1 KB | 3.9x |
| 5,000 elements | 390.7 KB | 132.1 KB | 3.0x |
| 10,000 elements | 909.4 KB | 260.1 KB | 3.5x |

---

# SortedLinkedList (CZ)

Typově bezpečný, homogenní seřazený jednosměrný lineární seznam pro PHP 8.4+.

## Instalace

```bash
composer require vondrasoft/sorted-linked-list
```

> **Poznámka:** Repozitář obsahuje Docker setup pouze pro vývojové účely (spouštění testů, statické analýzy, benchmarků). Pro použití knihovny jako závislosti ve vašem projektu není potřeba.

## Spuštění (Docker)

Projekt používá předpřipravený Docker image (`ghcr.io/vondrasoft/php-xdebug:8.4`) s PHP 8.4 + Xdebug + Composer, takže není potřeba nic buildovat lokálně. `Dockerfile` je ponechán v repozitáři pro nahlédnutí.

```bash
# Prvotní spuštění (nastartuje kontejner + nainstaluje závislosti)
./init.sh

# Instalace závislostí
docker compose exec php composer install

# Spuštění testů
docker compose exec php composer test

# Testy s code coverage (Xdebug je součástí Docker image)
docker compose exec php composer test-coverage

# Statická analýza (PHPStan level 9 + strict rules)
docker compose exec php composer analyze

# Kontrola coding standardu (PHPCS + ShipMonk Coding Standard)
docker compose exec php composer cs

# Automatická oprava coding standardu
docker compose exec php composer cs-fix

# Benchmarky
docker compose exec php composer benchmark
```

## API

```php
use Vondrasoft\DataStructures\SortedLinkedList;

$list = new SortedLinkedList();

$list->add(5);
$list->add(1);
$list->add(3);

count($list);        // 3
$list->toArray();    // [1, 3, 5]
$list->contains(3);  // true
$list->isEmpty();    // false
$list->first();      // 1
$list->last();       // 5

foreach ($list as $value) {
    echo $value;     // 1, 3, 5
}

$list->remove(3);    // true  (odebere první výskyt)
$list->removeAll(1); // 1     (vrátí počet odebraných prvků)
$list->toArray();    // [5]

$list->clear();      // odebere všechny prvky, uvolní zámek typu
$list->toArray();    // []
count($list);        // 0
```

### Typová bezpečnost

Seznam zamkne svůj typ při prvním vložení prvku. Pokus o vložení jiného typu vyhodí `InvalidTypeException`:

```php
$list = new SortedLinkedList();
$list->add(1);
$list->add('hello'); // vyhodí InvalidTypeException
```

Po odebrání všech prvků se zámek uvolní a seznam je možné použít s jiným typem.

### Řazení řetězců

Řetězce jsou řazeny pomocí nativního PHP operátoru `<`, který provádí lexikografické (byte-by-byte) porovnání. To funguje správně pro ASCII/anglické řetězce. Pro locale-aware řazení (např. čeština, němčina) by bylo potřeba použít `Collator` z rozšíření `intl` — to je záměrně mimo rozsah této generické datové struktury.

## Struktura projektu

```
src/
  SortedLinkedList.php              Hlavní třída (Iterator + Countable)
  Node.php                          Uzel linked listu
  ValueType.php                     Enum pro zamykání typu
  Exception/InvalidTypeException.php
tests/
  SortedLinkedListTest.php          69 testů, 107 assertions
benchmarks/
  benchmark.php                     Výkonnostní benchmarky včetně porovnání s array
```

## Pokrytí kódu testy

| Třída | Metody | Řádky |
|-------|--------|-------|
| `InvalidTypeException` | 100.00% (1/1) | 100.00% (7/7) |
| `Node` | 100.00% (2/2) | 100.00% (4/4) |
| `SortedLinkedList` | 100.00% (12/12) | 100.00% (104/104) |
| `ValueType` | 100.00% (1/1) | 100.00% (3/3) |
| **Celkem** | **100.00% (16/16)** | **100.00% (118/118)** |

## Nástroje pro kvalitu

| Nástroj | Úroveň | Příkaz |
|---------|--------|--------|
| PHPUnit | 69 testů, 107 assertions | `composer test` / `composer test-coverage` |
| PHPStan | Level 9 + strict rules (phpstan-strict-rules, shipmonk/phpstan-rules) | `composer analyze` |
| PHPCS | ShipMonk Coding Standard | `composer cs` |

## Benchmarky

> **Poznámka:** Předbuilděný Docker image je postaven pro **arm64** (Apple Silicon / Mac). Níže uvedené výsledky benchmarků odpovídají této architektuře. Při spuštění na **amd64** (např. Linux servery) může být výkon výrazně nižší kvůli **QEMU emulaci** — Docker za běhu transparentně překládá arm64 instrukce na amd64, což přidává značný overhead. Pro přesné benchmarky na amd64 je potřeba image přebuildit nativně (`docker build --platform linux/amd64`).

Spuštění: `docker compose exec php composer benchmark`. Měří výkon v různých scénářích:

- **Vkládání celých čísel (náhodné pořadí)** — demonstruje O(n) na jedno vložení, O(n²) celkem:

| Velikost | Čas |
|----------|-----|
| 100 prvků | 2,64 ms |
| 500 prvků | 2,12 ms |
| 1 000 prvků | 7,10 ms |
| 5 000 prvků | 162,02 ms |
| 10 000 prvků | 640,19 ms |

- **Vkládání řetězců (náhodné pořadí):**

| Velikost | Čas |
|----------|-----|
| 100 prvků | 0,26 ms |
| 500 prvků | 3,34 ms |
| 1 000 prvků | 11,44 ms |
| 5 000 prvků | 261,77 ms |
| 10 000 prvků | 1 098,61 ms |

- **Seřazený / obrácený vstup** — seřazený vstup se připojuje přes tail pointer O(1), obrácený vkládá vždy na hlavu O(1). Oba se vyhnou průchodu:

| Velikost | Seřazený | Obrácený |
|----------|----------|----------|
| 100 prvků | 0,10 ms | 0,11 ms |
| 500 prvků | 0,47 ms | 0,45 ms |
| 1 000 prvků | 0,86 ms | 0,82 ms |
| 5 000 prvků | 4,07 ms | 4,03 ms |
| 10 000 prvků | 8,23 ms | 7,90 ms |

- **Odebírání** (všechny prvky od hlavy):

| Velikost | Čas |
|----------|-----|
| 100 prvků | 0,07 ms |
| 500 prvků | 0,22 ms |
| 1 000 prvků | 0,46 ms |
| 5 000 prvků | 2,18 ms |
| 10 000 prvků | 4,45 ms |

- **Iterace** — `toArray()` (přímý while cyklus) vs `foreach` (generátor):

| Velikost | toArray() | foreach |
|----------|-----------|---------|
| 100 prvků | 0,01 ms | 0,03 ms |
| 500 prvků | 0,03 ms | 0,10 ms |
| 1 000 prvků | 0,05 ms | 0,20 ms |
| 5 000 prvků | 0,24 ms | 0,81 ms |
| 10 000 prvků | 0,48 ms | 1,63 ms |

- **`contains()`: while vs foreach vs in_array** — porovnává přímý průchod uzly (`while`), iteraci přes generátor (`foreach`) a PHP nativní `in_array()` na poli pro prostřední prvek. Přímý průchod uzly je ~4x rychlejší než generátor, ale `in_array()` na poli je výrazně rychlejší díky C-level optimalizaci a cache-friendly souvislé paměti:

**Prostřední prvek (contains):**

| Velikost | while | foreach | in_array |
|----------|-------|---------|----------|
| 100 prvků | 2,35 ms | 7,75 ms | 0,31 ms |
| 500 prvků | 9,67 ms | 38,10 ms | 0,48 ms |
| 1 000 prvků | 18,99 ms | 76,67 ms | 0,71 ms |
| 5 000 prvků | 92,94 ms | 372,95 ms | 2,29 ms |
| 10 000 prvků | 186,13 ms | 756,94 ms | 4,38 ms |

- **Poslední prvek** — `last()` tail O(1) vs `while` (přímý průchod uzly, simuluje starý `last()` bez tailu) vs `foreach` (generátor) vs array `end()`. Tail pointer dosahuje stejného výkonu jako array `end()` — obojí je O(1) bez ohledu na velikost seznamu:

**Poslední prvek:**

| Velikost | tail O(1) | while | foreach | end() |
|----------|-----------|-------|---------|-------|
| 100 prvků | 0,25 ms | 2,29 ms | 16,09 ms | 0,23 ms |
| 500 prvků | 0,26 ms | 9,44 ms | 78,43 ms | 0,23 ms |
| 1 000 prvků | 0,25 ms | 18,38 ms | 156,64 ms | 0,24 ms |
| 5 000 prvků | 0,25 ms | 86,98 ms | 766,63 ms | 0,25 ms |
| 10 000 prvků | 0,26 ms | 174,49 ms | 1 523,88 ms | 0,23 ms |

*1 000 volání na scénář.*

- **Paměťová náročnost** — linked list vs pole. Každý objekt uzlu nese overhead (~3x více paměti než slot v poli):

| Velikost | LinkedList | array | poměr |
|----------|-----------|-------|-------|
| 100 prvků | 7,9 KB | 2,6 KB | 3,1x |
| 500 prvků | 39,2 KB | 12,1 KB | 3,2x |
| 1 000 prvků | 78,2 KB | 20,1 KB | 3,9x |
| 5 000 prvků | 390,7 KB | 132,1 KB | 3,0x |
| 10 000 prvků | 909,4 KB | 260,1 KB | 3,5x |
