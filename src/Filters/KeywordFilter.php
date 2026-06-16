<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Filters;

use Technically\SearchQuery\Tokens\Literal;
use Technically\SearchQuery\Tokens\Operator;
use Technically\SearchQuery\Tokens\QuotedString;

final readonly class KeywordFilter implements Filter
{
    public function __construct(
        public string $keyword,
        public bool   $quoted = false,
        public bool   $exclude = false,
    ) {
        // Nothing
    }

    /**
     * Get a new `KeywordFilter` instance with the value unquoted.
     */
    public function unquote(): self
    {
        return new self($this->keyword, false, $this->exclude);
    }

    /**
     * Serialize the filter back to the query syntax string.
     *
     * The returned presentation is normalized and might be different
     * from the original source string used to construct this filter.
     */
    public function toString(): string
    {
        $keyword = $this->quoted || empty($this->keyword)
            ? new QuotedString($this->keyword)
            : new Literal($this->keyword);

        return ($this->exclude ? Operator::MINUS : '') . $keyword->toString();
    }
}