<?php
declare(strict_types=1);

namespace Technically\SearchQuery\Filters;

use Technically\SearchQuery\Tokens\Literal;
use Technically\SearchQuery\Tokens\Operator;
use Technically\SearchQuery\Tokens\QuotedString;

final readonly class KeywordFilter implements Filter
{
    public Literal | QuotedString $keyword;

    public bool $exclude;

    public function __construct(
        Literal | QuotedString | string $keyword,
        bool                            $exclude = false,
    ) {
        $this->keyword = is_string($keyword) ? new Literal($keyword) : $keyword;
        $this->exclude = $exclude;
    }

    public function toString(): string
    {
        return ($this->exclude ? Operator::MINUS : '')
               . $this->keyword->toString();
    }
}