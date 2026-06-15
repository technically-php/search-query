# Search Query Parser

Parse plaintext search queries into easy-to-use filter structures.

This library takes a human-typed search query string and parses it into a structured `Query` object containing typed filters (`KeywordFilter`, `FieldFilter`). It supports quoted strings, negation, comparison operators, and field-based filtering.

---

## Installation

```bash
composer require technically/search-query
```

Requires PHP ^8.4.

---

## Quick Start

```php
use Technically\SearchQuery\QueryParser;

$parser = new QueryParser();
$query  = $parser->parse('tag:php -legacy "best practices"');

foreach ($query->filters as $filter) {
    // Filter instances...
}
```

---

## Supported Query Syntax

| Syntax                 | Parsed As                                           |
|------------------------|-----------------------------------------------------|
| `hello`                | `KeywordFilter('hello')`                            |
| `"hello world"`        | `KeywordFilter('hello world', quoted: true)`        |
| `-hello`               | `KeywordFilter('hello', exclude: true)`             |
| `tag:php`              | `FieldFilter('tag', ':', 'php')`                    |
| `-tag:php`             | `FieldFilter('tag', ':', 'php', exclude: true)`     |
| `year>2020`            | `FieldFilter('year', '>', '2020')`                  |
| `year>=2020`           | `FieldFilter('year', '>=', '2020')`                 |
| `year<2020`            | `FieldFilter('year', '<', '2020')`                  |
| `year<=2020`           | `FieldFilter('year', '<=', '2020')`                 |
| `hello\ world`         | `KeywordFilter('hello world')` (escaped whitespace) |
| `"custom field":value` | `FieldFilter('custom field', ':', 'value')`         |

### Negation

A leading `-` (minus) before a keyword or field filter negates it. Multiple minuses are gracefully collapsed into a single negation.

```
-apple                    -> KeywordFilter('apple', exclude: true)
-tag:legacy               -> FieldFilter('tag', ':', 'legacy', exclude: true)
```

### Quoting

Double quotes group multiple words into a single token. Quotes can be escaped with `\`.

```
"hello world"            -> KeywordFilter('hello world', quoted: true)
field:"hello world"      -> FieldFilter('field', ':', 'hello world', quoted: true)
```

### Escaping

The backslash `\` escape character works both inside and outside quoted strings:

```
apples\ fruits            -> KeywordFilter('apples fruits')
55\"                      -> KeywordFilter('55"')
"hello \"world\""         -> KeywordFilter('hello "world"', quoted: true)
```

## The Tolerant Reader

The parser is built using the [Tolerant Reader](https://martinfowler.com/bliki/TolerantReader.html) 
design pattern — to be forgiving with malformed input. It never throws.

---

## API Reference

### `QueryParser`

The main entry point for parsing query strings.

```php
use Technically\SearchQuery\QueryParser;

$parser = new QueryParser();
$query  = $parser->parse('your search query');
```

The parser accepts an optional `Tokenizer` instance in its constructor. By default, it uses `QueryTokenizer`.

#### Methods

- `parse(string $query): Query` — Parses a query string into a `Query` object.

---

### `Query`

An immutable value object representing the parsed search query.

```php
use Technically\SearchQuery\Query;

$query = new Query([
    new KeywordFilter('php'),
    new FieldFilter('tag', ':', 'tutorial'),
]);
```

#### Properties

- `public readonly array $filters` — Array of `Filter` instances.

#### Methods

- `static empty(): self` — Create a new empty query.
- `isEmpty(): bool` — Check if the query is empty (has no filters).
- `toString(): string` — Serializes the query back to the search query syntax string.

---

### Filters

All filters implement the `Technically\SearchQuery\Filters\Filter` marker interface.

#### `KeywordFilter`

Represents a free-text keyword search term.

```php
use Technically\SearchQuery\Filters\KeywordFilter;

new KeywordFilter('php');
new KeywordFilter('hello world', quoted: true);
new KeywordFilter('legacy', exclude: true);
```

**Properties:**
- `public readonly string $keyword` — The keyword value.
- `public readonly bool $quoted` — Whether the keyword was originally quoted.
- `public readonly bool $exclude` — Whether the keyword is negated.

**Methods:**
- `toString(): string` — Serializes the filter back to query syntax.

#### `FieldFilter`

Represents a field-based filter (`field:operator:value`).

```php
use Technically\SearchQuery\Filters\FieldFilter;

new FieldFilter('year', '>', '2020');
new FieldFilter('status', ':', 'active', quoted: true);
new FieldFilter('tag', ':', 'legacy', exclude: true);
```

**Properties:**
- `public readonly string $field` — The field name.
- `public readonly FilterOperator $operator` — The comparison operator.
- `public readonly string $value` — The filter value.
- `public readonly bool $quoted` — Whether the value was originally quoted.
- `public readonly bool $exclude` — Whether the filter is negated.

**Methods:**
- `toString(): string` — Serializes the filter back to query syntax.

---

## Examples

### Parse a complex query

```php
use Technically\SearchQuery\QueryParser;
use Technically\SearchQuery\Filters\KeywordFilter;
use Technically\SearchQuery\Filters\FieldFilter;

$parser = new QueryParser();
$query  = $parser->parse('php -legacy "best practices" year>=2020');

foreach ($query->filters as $filter) {
    if ($filter instanceof KeywordFilter) {
        echo "Keyword: {$filter->keyword}"
           . ($filter->exclude ? ' (excluded)' : '')
           . ($filter->quoted ? ' (quoted)' : '')
           . "\n";
    } elseif ($filter instanceof FieldFilter) {
        echo "Field: {$filter->field} {$filter->operator->value} {$filter->value}"
           . ($filter->exclude ? ' (excluded)' : '')
           . ($filter->quoted ? ' (quoted)' : '')
           . "\n";
    }
}
// Output:
// Keyword: php
// Keyword: legacy (excluded)
// Keyword: best practices (quoted)
// Field: year >= 2020
```

### Serialize filters back to strings

```php
$filter = new FieldFilter('tag', ':', 'hello world', quoted: true, exclude: true);
echo $filter->toString(); // -tag:"hello world"

// Or serialize an entire Query back to string:
$query = new Query([
    new KeywordFilter('php'),
    new FieldFilter('year', '>', '2020', exclude: true),
]);
echo $query->toString(); // php -year>2020
```

### Custom tokenization

```php
use Technically\SearchQuery\QueryParser;
use Technically\SearchQuery\Contracts\Tokenizer;

class MyCustomTokenizer implements Tokenizer
{
    public function tokenize(string $query): iterable
    {
        // Custom tokenization logic...
    }
}

$parser = new QueryParser(new MyCustomTokenizer());
```

---

## Running Tests

```bash
composer tests
```

Tests are written with [Pest PHP](https://pestphp.com/).

---

## License

MIT


## Credits

Implemented by :space_invader: [Ivan Voskoboinyk](https://voskoboinyk.com/).
