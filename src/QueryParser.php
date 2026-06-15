<?php
declare(strict_types=1);

namespace Technically\SearchQuery;

use LogicException;
use Technically\SearchQuery\Contracts\Parser;
use Technically\SearchQuery\Contracts\Tokenizer;
use Technically\SearchQuery\Filters\FieldFilter;
use Technically\SearchQuery\Filters\Filter;
use Technically\SearchQuery\Filters\KeywordFilter;
use Technically\SearchQuery\Tokens\Operator;
use Technically\SearchQuery\Tokens\Support\TokenSequence;
use Technically\SearchQuery\Tokens\Token;
use Technically\SearchQuery\Tokens\Whitespace;

final readonly class QueryParser implements Parser
{
    private const int PHASE_INITIAL  = 0;
    private const int PHASE_KEYWORD  = 1;
    private const int PHASE_OPERATOR = 2;
    private const int PHASE_VALUE    = 3;

    public function __construct(
        private Tokenizer $tokenizer = new QueryTokenizer(),
    ) {
        // Nothing
    }

    public function parse(string $query): Query
    {
        if (empty($query)) {
            return Query::empty();
        }

        $tokens = iterator_to_array($this->tokenizer->tokenize($query));

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
        $length   = count($tokens);

        $phase = self::PHASE_INITIAL;

        $exclude = false;
        $keyword = new TokenSequence();
        /** @var Operator|null $operator */
        $operator = null;
        $value    = new TokenSequence();

        while (++$position < $length) {
            $token = $tokens[$position] ?? null;

            if ($token instanceof Whitespace) {
                throw new LogicException('This should never happen.');
            }

            if ($phase === self::PHASE_INITIAL) {
                if ( ! $exclude && $token instanceof Operator && $token->operator === Operator::MINUS) {
                    $exclude = true;
                    continue;
                }

                if ($token instanceof Operator) {
                    // Misplaced operator. Ignore it.
                    continue;
                }

                $phase = self::PHASE_KEYWORD;
                $keyword->append($token);
                continue;
            }

            if ($phase === self::PHASE_KEYWORD) {
                if ($token instanceof Operator && $token->operator !== Operator::MINUS) {
                    $phase    = self::PHASE_OPERATOR;
                    $operator = $token;
                    continue;
                }

                $keyword->append($token);
                continue;
            }

            if ($phase === self::PHASE_OPERATOR) {
                $phase = self::PHASE_VALUE;
                $value->append($token);
                continue;
            }

            if ($phase === self::PHASE_VALUE) {
                $value->append($token);
                continue;
            }

            throw new LogicException('This should never happen.');
        }

        if ( ! $keyword->isEmpty() && $operator !== null && ! $value->isEmpty()) {
            // Field filter with a value, with optional negation (minus) prefix:
            //  [-]{field}{operator}{value}
            return new FieldFilter(
                $keyword->toString(),
                $operator->operator,
                $value->toString(),
                quoted : $value->isQuoted(),
                exclude: $exclude,
            );
        }

        if ( ! $keyword->isEmpty() && $operator !== null && $value->isEmpty()) {
            // Field filter without a value, with optional negation (minus) prefix:
            //  [-]{field}{operator}
            // Convert it to a keyword filter - the best we can do.
            $keyword->append($operator);
            return new KeywordFilter($keyword->toString(), quoted: $keyword->isQuoted(), exclude: $exclude);
        }

        if ( ! $keyword->isEmpty() && $value->isEmpty()) {
            // No field, no operator, only value -> keyword filter
            return new KeywordFilter($keyword->toString(), quoted: $keyword->isQuoted(), exclude: $exclude);
        }

        if ($keyword->isEmpty() && $value->isEmpty()) {
            // Lone operator -> ignore it
            return null;
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
        $length   = count($tokens);
        $group    = [];

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