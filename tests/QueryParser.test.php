<?php

use Technically\SearchQuery\Filters\BooleanFilter;
use Technically\SearchQuery\Filters\FieldFilter;
use Technically\SearchQuery\Filters\KeywordFilter;
use Technically\SearchQuery\QueryParser;
use Technically\SearchQuery\Query;
use Technically\SearchQuery\Schema;
use Technically\SearchQuery\Tokens\Literal;
use Technically\SearchQuery\Tokens\Operator;
use Technically\SearchQuery\Tokens\QuotedString;

describe('QueryParser', function (): void {
    it('should parse empty queries', function (): void {
        $query = (new QueryParser())->parse('');

        expect($query->isEmpty())->toBeTrue();
    });

    it('should parse keyword', function () {
        $query = (new QueryParser())->parse('apples');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter('apples'),
            ]),
        );
    });

    it('should parse multiple keywords', function () {
        $query = (new QueryParser())->parse('apples fruits');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter('apples'),
                new KeywordFilter('fruits'),
            ]),
        );
    });

    it('should parse keywords with escaped whitespace', function () {
        $query = (new QueryParser())->parse('apples\ fruits');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter('apples fruits'),
            ]),
        );
    });

    it('should parse negated keyword', function () {
        $query = (new QueryParser())->parse('-apples');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter('apples', exclude: true),
            ]),
        );
    });

    it('should parse multiple keywords with some keywords negated', function () {
        $query = (new QueryParser())->parse('apple -fruit technology');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter('apple'),
                new KeywordFilter('fruit', exclude: true),
                new KeywordFilter('technology'),
            ]),
        );
    });

    it('should parse quoted keywords', function () {
        $query = (new QueryParser())->parse('"hello world"');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter(new QuotedString('hello world')),
            ]),
        );
    });

    it('should parse keyword filters with quotes', function () {
        $query = (new QueryParser())->parse('display 55"');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter('display'),
                new KeywordFilter('55"'),
            ]),
        );
    });

    it('should parse queries with negated quoted keywords', function () {
        $query = (new QueryParser())->parse('-"hello world"');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter(new QuotedString('hello world'), exclude: true),
            ]),
        );
    });

    it('should parse field filter', function () {
        $query = (new QueryParser())->parse('tag:apples');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('tag', ':' ,'apples'),
            ]),
        );
    });

    it('should parse negated field filter', function () {
        $query = (new QueryParser())->parse('-tag:apples');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('tag', ':', 'apples', exclude: true),
            ]),
        );
    });

    it('should parse field filter with a minus prefix', function () {
        $query = (new QueryParser())->parse('tag:-apples');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('tag', ':', '-apples'),
            ]),
        );
    });

    it('should parse empty field filter to a keyword filter', function () {
        $query = (new QueryParser())->parse('tag:');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter('tag:'),
            ]),
        );
    });

    it('should parse negated empty field filter', function () {
        $query = (new QueryParser())->parse('-tag:');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter(new Literal('tag:'), exclude: true),
            ]),
        );
    });

    it('should parse field filter with minus as value', function () {
        $query = (new QueryParser())->parse('tag:-');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('tag', ':', '-'),
            ]),
        );
    });

    it('should parse negated field filter with minus as value', function () {
        $query = (new QueryParser())->parse('-tag:-');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('tag', ':', '-', exclude: true),
            ]),
        );
    });

    it('should parse field filter with quoted name', function () {
        $query = (new QueryParser())->parse('"custom field":hello');

        expect($query)->toEqual(
            new Query([
                new FieldFilter(new QuotedString('custom field'), ':', 'hello'),
            ]),
        );
    });

    it('should parse field filter with quoted value', function () {
        $query = (new QueryParser())->parse('field:"hello world"');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', ':', new QuotedString('hello world')),
            ]),
        );
    });

    it('should parse field filter with quoted name and minus as value', function () {
        $query = (new QueryParser())->parse('"custom field":-hello');

        expect($query)->toEqual(
            new Query([
                new FieldFilter(new QuotedString('custom field'), ':', '-hello'),
            ]),
        );
    });

    it('should parse field filter with quoted name and minus-prefixed quoted value', function () {
        $query = (new QueryParser())->parse('"custom field":-"hello world"');

        expect($query)->toEqual(
            new Query([
                new FieldFilter(new QuotedString('custom field'), ':', '-"hello world"'),
            ]),
        );
    });

    it('should parse field filters quoted on both sides', function () {
        $query = (new QueryParser())->parse('"custom field":"hello world"');

        expect($query)->toEqual(
            new Query([
                new FieldFilter(new QuotedString('custom field'), ':', new QuotedString('hello world')),
            ]),
        );
    });
    
    it('should gracefully handle multiple negation operators', function () {
        $query = (new QueryParser())->parse('----field:value');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('---field', ':', 'value', exclude: true),
            ]),
        );

        $query = (new QueryParser())->parse('field:------value');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', ':', '------value'),
            ]),
        );

        $query = (new QueryParser())->parse('----field:------value');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('---field', ':', '------value', exclude: true),
            ]),
        );
    });

    it('should handle multiple colon operators', function () {
        $query = (new QueryParser())->parse('field:::value');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', ':', '::value'),
            ]),
        );

        $query = (new QueryParser())->parse('field::value:');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', ':', ':value:'),
            ]),
        );

        $query = (new QueryParser())->parse('field:value:another');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', ':', 'value:another'),
            ]),
        );
    });

    it('should gracefully handle misplaced operators', function () {
        $query = (new QueryParser())->parse('- : --- field:value --- : -');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter('-'),
                new KeywordFilter(':'),
                new KeywordFilter('--', exclude: true),
                new FieldFilter('field', ':', 'value'),
                new KeywordFilter('--', exclude: true),
                new KeywordFilter(':'),
                new KeywordFilter('-'),
            ]),
        );

        $query = (new QueryParser())->parse(': :: --  field::value --- -- -');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter(':'),
                new KeywordFilter('::'),
                new KeywordFilter('-', exclude: true),
                new FieldFilter('field', ':', ':value'),
                new KeywordFilter('--', exclude: true),
                new KeywordFilter('-', exclude: true),
                new KeywordFilter('-'),
            ]),
        );
    });

    it('should handle technically invalid sequence of quoted and unquoted words', function () {
        $query = (new QueryParser())->parse('keyword"another quoted keyword"end');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter('keyword"another'),
                new KeywordFilter('quoted'),
                new KeywordFilter('keyword"end'),
            ]),
        );
    });
});