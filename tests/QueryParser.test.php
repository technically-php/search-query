<?php

use Technically\SearchQuery\Filters\FieldFilter;
use Technically\SearchQuery\Filters\KeywordFilter;
use Technically\SearchQuery\QueryParser;
use Technically\SearchQuery\Query;

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
                new KeywordFilter('hello world', exact: true),
            ]),
        );
    });

    it('should parse queries with negated quoted keywords', function () {
        $query = (new QueryParser())->parse('-"hello world"');

        expect($query)->toEqual(
            new Query([
                new KeywordFilter('hello world', exact: true, exclude: true),
            ]),
        );
    });

    it('should parse field filter', function () {
        $query = (new QueryParser())->parse('tag:apples');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('tag', 'apples'),
            ]),
        );
    });

    it('should parse negated field filter', function () {
        $query = (new QueryParser())->parse('-tag:apples');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('tag', 'apples', exclude: true),
            ]),
        );
    });

    it('should parse field filter with negated value', function () {
        $query = (new QueryParser())->parse('tag:-apples');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('tag', 'apples', exclude: true),
            ]),
        );
    });

    it('should parse empty field filter', function () {
        $query = (new QueryParser())->parse('tag:');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('tag', ''),
            ]),
        );
    });

    it('should parse negated empty field filter', function () {
        $query = (new QueryParser())->parse('-tag:');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('tag', '', exclude: true),
            ]),
        );
    });

    it('should parse field filter with negated empty value', function () {
        $query = (new QueryParser())->parse('tag:-');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('tag', '', exclude: true),
            ]),
        );
    });

    it('should parse double-negated empty field filter', function () {
        $query = (new QueryParser())->parse('-tag:-');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('tag', ''), // double negation eliminates itself
            ]),
        );
    });

    it('should parse field filter with quoted name', function () {
        $query = (new QueryParser())->parse('"custom field":hello');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('custom field', 'hello'),
            ]),
        );
    });

    it('should parse field filter with quoted value', function () {
        $query = (new QueryParser())->parse('field:"hello world"');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', 'hello world', exact: true),
            ]),
        );
    });

    it('should parse field filter with quoted name and negated value', function () {
        $query = (new QueryParser())->parse('"custom field":-hello');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('custom field', 'hello', exclude: true),
            ]),
        );
    });

    it('should parse field filter with quoted name and negated quoted value', function () {
        $query = (new QueryParser())->parse('"custom field":-"hello world"');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('custom field', 'hello world', exact: true, exclude: true),
            ]),
        );
    });

    it('should parse field filters quoted on both sides', function () {
        $query = (new QueryParser())->parse('"custom field":"hello world"');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('custom field', 'hello world', exact: true),
            ]),
        );
    });
    
    it('should ignore multiple negation operators', function () {
        $query = (new QueryParser())->parse('----field:value');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', 'value', exclude: true),
            ]),
        );

        $query = (new QueryParser())->parse('field:------value');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', 'value', exclude: true),
            ]),
        );

        $query = (new QueryParser())->parse('-------field:------value');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', 'value'), // double negation eliminates itself
            ]),
        );
    });

    it('should handle multiple colon operators', function () {
        $query = (new QueryParser())->parse('field:::value');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', 'value'),
            ]),
        );

        $query = (new QueryParser())->parse('field::value:');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', 'value:'),
            ]),
        );

        $query = (new QueryParser())->parse('field:value:another');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', 'value:another'),
            ]),
        );
    });

    it('should ignore misplaced operators', function () {
        $query = (new QueryParser())->parse('- : --- field:value --- : -');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', 'value'),
            ]),
        );

        $query = (new QueryParser())->parse(': :: --  field::value --- -- -');

        expect($query)->toEqual(
            new Query([
                new FieldFilter('field', 'value'),
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