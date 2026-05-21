<?php
declare(strict_types=1);

namespace Technically\SearchQuery;

use Technically\SearchQuery\Filters\FieldFilter;
use Technically\SearchQuery\Filters\KeywordFilter;
use Technically\SearchQuery\Tokenizer\QueryTokenizer;
use Technically\SearchQuery\Tokenizer\Tokens\Literal;
use Technically\SearchQuery\Tokenizer\Tokens\Operator;
use Technically\SearchQuery\Tokenizer\Tokens\Token;
use Technically\SearchQuery\Tokenizer\Tokens\Whitespace;

final readonly class QueryParser
{
    public function parse(string $query): Query
    {
        if (empty($query)) {
            return Query::empty();
        }

        $tokenizer = new QueryTokenizer($query);

        $tokens = [...$tokenizer->tokenize()];

        if (empty($tokens)) {
            return Query::empty();
        }

        foreach ($this->splitGroups($tokens) as $group) {
            $position = -1;
            $length = count($group);

            $field = null;
            $negatedField = false;
            $value = null;
            $negatedValue = false;

            while (++$position < $length) {
                $token = $group[$position] ?? null;

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
                        $negatedValue = true;
                        continue;
                    }
                    if ($field !== null && $value === null) {
                        $negatedValue = true;
                        continue;
                    }

                    if ($value !== null) {
                        // Append trailing minus to the value
                        $value = $value->with(value: $value->value . $token->toString());
                        continue;
                    }

                    // Misplaced `-` operator. Ignore it.
                    continue;
                }

                if ($token instanceof Operator && $token->operator === Operator::COLON) {
                    if ($field === null && $value !== null) {
                        // Move `value` into `field`
                        $field = $value;
                        $value = null;
                        // Carry the negation flag over too.
                        $negatedField = $negatedValue;
                        $negatedValue = false;
                        continue;
                    }

                    if ($field !== null && $value !== null) {
                        // Treat trailing colon operator as part of the value
                        $value = $value->with(value: $value->value . $token->toString());
                        continue;
                    }

                    // Misplaced `-` operator. Ignore it.
                    continue;
                }
            }

            if ($field !== null) {
                // Field filter with or without a value, with optional negation (minus) on any side.
                //  `(-)field:(-)(value)`
                $filters[] = new FieldFilter(
                    field  : $field->value,
                    value  : $value?->value ?? '',
                    exact  : $value?->quoted ?? false,
                    exclude: $negatedValue xor $negatedField,
                );
                continue;
            }

            if ($value !== null) {
                // No field, only value -> keyword filter
                $filters[] = new KeywordFilter(
                    keyword: $value->value,
                    exact  : $value->quoted,
                    exclude: $negatedValue,
                );
                continue;
            }

            // Invalid filter. Ignore it.
        }

        return new Query($filters);
    }

    /**
     * @param Token[] $tokens
     * @return Token[][]
     */
    private function splitGroups(array $tokens): array
    {
        $groups = [];

        $position = -1;
        $length = count($tokens);
        $group = [];

        if ($length === 0) {
            return [];
        }

        while (++$position < $length + 1) {
            $token = $tokens[$position] ?? null;

            if ($token instanceof Whitespace || $token === null) {
                $groups[] = $group;
                $group = [];
                continue;
            }

            $group[] = $token;
        }

        return $groups;
    }
}