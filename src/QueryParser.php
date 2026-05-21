<?php
declare(strict_types=1);

namespace Technically\SearchQuery;

use Technically\SearchQuery\Filters\FieldFilter;
use Technically\SearchQuery\Filters\Filter;
use Technically\SearchQuery\Filters\KeywordFilter;
use Technically\SearchQuery\Tokens\Literal;
use Technically\SearchQuery\Tokens\Operator;
use Technically\SearchQuery\Tokens\Token;
use Technically\SearchQuery\Tokens\Whitespace;

final readonly class QueryParser
{
    public function parse(string $query): Query
    {
        if (empty($query)) {
            return Query::empty();
        }

        $tokenizer = new QueryTokenizer($query);

        $tokens = iterator_to_array($tokenizer->tokenize());

        if (empty($tokens)) {
            return Query::empty();
        }

        foreach ($this->splitGroups($tokens) as $group) {
            if ($filter = $this->parseGroup($group)) {
                $filters[] = $filter;
            }
        }

        return new Query($filters);
    }

    /**
     * @param Token[] $tokens
     */
    private function parseGroup(array $tokens): Filter | null
    {
        $position = -1;
        $length = count($tokens);

        $field = null;
        $negatedField = false;
        $value = null;
        $negatedValue = false;

        while (++$position < $length) {
            $token = $tokens[$position] ?? null;

            if ($token instanceof Whitespace) {
                // Ignore whitespaces.
                continue;
            }

            if ($token instanceof Literal) {
                // If `value` is vacant, fill it.
                //
                // This happens in one of the two cases:
                //  - It's the first literal in the query  -> both `field` and `value` are empty.
                //  - It's the first literal after a colon -> `field` is set, `value` is empty.
                if ($value === null) {
                    $value = $token;
                    continue;
                }

                // Append trailing literals to the value
                $value = $value->with(value: $value->value . $token->toString());
                continue;
            }

            if ($token instanceof Operator && $token->operator === Operator::MINUS) {
                if ($field === null && $value === null) {
                    // If it's a leading `-` -> treat it as negating the value.
                    // Once there is a colon operator, the value and its negation flag will be moved to become the field.
                    $negatedValue = true;
                    continue;
                }
                if ($field !== null && $value === null) {
                    // If it's following a colon operator (but preceding value) -> treat it as negating the value
                    $negatedValue = true;
                    continue;
                }

                if ($value !== null) {
                    // If it's following field, colon, and a value -> append it to the value
                    $value = $value->with(value: $value->value . $token->toString());
                    continue;
                }

                // Misplaced `-` operator. Ignore it.
                continue;
            }

            if ($token instanceof Operator && $token->operator === Operator::COLON) {
                if ($field === null && $value !== null) {
                    // If it's a colon after a lone keyword (initially stored in value)
                    // Move `value` into `field`.
                    $field = $value;
                    $value = null;
                    // And also carry the negation flag over too.
                    $negatedField = $negatedValue;
                    $negatedValue = false;
                    continue;
                }

                if ($field !== null && $value !== null) {
                    // If it's following field, colon, and a value, append it to the value
                    $value = $value->with(value: $value->value . $token->toString());
                    continue;
                }

                // Misplaced `-` operator. Ignore it.
                continue;
            }
        }

        if ($field !== null) {
            // Field filter with or without a value, with optional negation (minus) on any side.
            //  `[-]field:[-][value]`
            return new FieldFilter(
                field  : $field->value,
                value  : $value?->value ?? '',
                exact  : $value?->quoted ?? false,
                exclude: $negatedValue xor $negatedField,
            );
        }

        if ($value !== null) {
            // No field, only value -> keyword filter
            return new KeywordFilter(
                keyword: $value->value,
                exact  : $value->quoted,
                exclude: $negatedValue,
            );
        }

        // Invalid filter. Ignore it.
        return null;
    }

    /**
     * @param Token[] $tokens
     * @return iterable<Token[]>
     */
    private function splitGroups(array $tokens): iterable
    {
        $position = -1;
        $length = count($tokens);
        $group = [];

        if ($length === 0) {
            return [];
        }

        // Note: we're intentionally iteration one item too far to have the last `null` token as EOF
        while (++$position < $length + 1) {
            $token = $tokens[$position] ?? null;

            if ($token instanceof Whitespace || $token === null) {
                yield $group;
                $group = [];
                continue;
            }

            $group[] = $token;
        }
    }
}