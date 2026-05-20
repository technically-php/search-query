<?php

use Technically\SearchQuery\QueryParser;

describe('QueryParser', function (): void {
    it('should parse empty text query into an empty SearchQuery object', function (): void {
        $parser = new QueryParser();

        $query = $parser->parse('');

        expect($query->isEmpty())->toBeTrue();
    });
});