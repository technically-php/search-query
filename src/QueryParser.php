<?php
declare(strict_types=1);

namespace Technically\SearchQuery;

use LogicException;
use Technically\SearchQuery\Filters\FieldFilter;
use Technically\SearchQuery\Filters\Filter;
use Technically\SearchQuery\Filters\KeywordFilter;
use Technically\SearchQuery\Tokens\Literal;
use Technically\SearchQuery\Tokens\Operator;
use Technically\SearchQuery\Tokens\QuotedString;
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

        $filters = [];

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

        $exclude = false;
        /** @var Literal|QuotedString $keyword */
        $keyword = null;
        /** @var Operator|null $operator */
        $operator = null;
        /** @var Literal|QuotedString $value */
        $value = null;

        while (++$position < $length) {
            $token = $tokens[$position] ?? null;

            if ($token instanceof Whitespace) {
                throw new LogicException('This should never happen.');
            }

            if ($token instanceof Literal || $token instanceof QuotedString) {
                // It's the first literal in the group -> store it as "keyword"
                if ($keyword === null) {
                    $keyword = $token;
                    continue;
                }

                // If it's a consequent literal in a group before we encountered an operator -> append it to the keyword.
                if ($operator === null) {
                    $keyword = $keyword->append($token);
                    continue;
                }

                // If it's a first literal after an operator -> store it as "value"
                if ($value === null) {
                    $value = $token;
                    continue;
                }

                // If it's a consequent literal after an operator -> append it to the value.
                $value = $value->append($token);
                continue;
            }

            if ($token instanceof Operator && $token->operator === Operator::MINUS) {
                if ($keyword === null && $value === null && ! $exclude) {
                    // If it's a leading `-`, treat it as negating the value.
                    $exclude = true;
                    continue;
                }

                if ($keyword === null) {
                    // If there's no keyword yet -> treat it as a (beginning of) keyword.
                    $keyword = new Literal($token->toString());
                    continue;
                }

                if ($operator === null) {
                    $keyword = $keyword->append($token);
                    continue;
                }

                if ($value === null) {
                    $value = new Literal($token->toString());
                    continue;
                }

                // in any other scenario, append it to the value
                $value = $value->append($token);
                continue;
            }

            if ($token instanceof Operator && $token->operator !== Operator::MINUS) {
                if ($keyword === null) {
                    // Misplaced leading binary operator. The best we can do is to treat it as a part of the keyword.
                    $keyword = new Literal($token->toString());
                    continue;
                }

                if ($operator === null) {
                    // If it's a binary operator after a keyword -> store it as "operator"
                    $operator = $token;
                    continue;
                }

                if ($value === null) {
                    // If it's following an operator, but there's no "value" yet -> treat it as "value"
                    $value = new Literal($token->toString());
                    continue;
                }

                // in any other scenario, append it to the value
                $value = $value->append($token);
            }
        }

        if ($keyword !== null && $operator !== null && $value !== null) {
            // Field filter with a value, with optional negation (minus) prefix:
            //  [-]{field}{operator}{value}
            return new FieldFilter($keyword, $operator, $value, exclude : $exclude);
        }

        if ($keyword !== null && $operator !== null && $value === null) {
            // Field filter without a value, with optional negation (minus) prefix:
            //  [-]{field}{operator}
            // Convert it to a keyword filter - the best we can do.
            return new KeywordFilter($keyword->append($operator), exclude: $exclude);
        }

        if ($keyword !== null && $value === null) {
            // No field, no operator, only value -> keyword filter
            return new KeywordFilter($keyword, exclude: $exclude);
        }

        if ($keyword === null && $value === null && $exclude === true) {
            // Lone `-` operator -> treat it as a keyword filter
            return new KeywordFilter(new Literal(Operator::MINUS));
        }

        throw new LogicException('This should never happen.');
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
                if (count($group) > 0) {
                    yield $group;
                }

                $group = [];
                continue;
            }

            $group[] = $token;
        }
    }
}