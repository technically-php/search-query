<?php

use Technically\SearchQuery\QueryTokenizer;
use Technically\SearchQuery\Tokens\Literal;
use Technically\SearchQuery\Tokens\Operator;
use Technically\SearchQuery\Tokens\QuotedString;
use Technically\SearchQuery\Tokens\Whitespace;

describe('QueryTokenizer', function () {
    it('should tokenize empty string to an empty sequence of tokens', function () {
        $tokens = new QueryTokenizer()->tokenize('');

        expect([...$tokens])->toBe([]);
    });

    it('should tokenize whitespace-only sequence to a single whitespace token', function () {
        $tokens = new QueryTokenizer()->tokenize('   ');

        expect([...$tokens])->toEqual([
            new Whitespace('   '),
        ]);
    });

    it('should tokenize a non-whitespace sequence to a single literal token', function () {
        $tokens = new QueryTokenizer()->tokenize('hello-world');

        expect([...$tokens])->toEqual([
            new Literal('hello-world'),
        ]);
    });

    it('should tokenize a mix of whitespace and non-whitespace sequences', function () {
        $tokens = new QueryTokenizer()->tokenize('  hello   world  ');

        expect([...$tokens])->toEqual([
            new Whitespace('  '),
            new Literal('hello'),
            new Whitespace('   '),
            new Literal('world'),
            new Whitespace('  '),
        ]);
    });

    it('should tokenize a quoted sequence as a single quoted string token', function () {
        $tokens = new QueryTokenizer()->tokenize('"hello world"');

        expect([...$tokens])->toEqual([
            new QuotedString('hello world'),
        ]);
    });

    it('should tokenize series of quoted sequences as quoted strings tokens', function () {
        $tokens = new QueryTokenizer()->tokenize('"hello world" "this is a sequence" "of quoted strings"');

        expect([...$tokens])->toEqual([
            new QuotedString('hello world'),
            new Whitespace(),
            new QuotedString('this is a sequence'),
            new Whitespace(),
            new QuotedString('of quoted strings'),
        ]);
    });

    it('should allow escaping quotes inside quoted strings', function () {
        $tokens = new QueryTokenizer()->tokenize('"hello \"new\" world"');

        expect([...$tokens])->toEqual([
            new QuotedString('hello "new" world'),
        ]);
    });

    it('should allow using escape character with any character', function () {
        $tokens = new QueryTokenizer()->tokenize('"\h\e\l\l\o \"\n\e\w\" \w\o\r\l\d"');

        expect([...$tokens])->toEqual([
            new QuotedString('hello "new" world'),
        ]);
    });

    it('should gracefully handle unclosed quoted literals', function () {
        $tokens = new QueryTokenizer()->tokenize('hello "new world');

        expect([...$tokens])->toEqual([
            new Literal('hello'),
            new Whitespace(),
            new QuotedString('new world'),
        ]);
    });

    it('should allow quoting characters outside of quoted strings', function () {
        $tokens = new QueryTokenizer()->tokenize('display 55\"');

        expect([...$tokens])->toEqual([
            new Literal('display'),
            new Whitespace(),
            new Literal('55"'),
        ]);
    });

    it('should allow escaping quotes in unquoted literals', function () {
        $tokens = new QueryTokenizer()->tokenize('\"display\"');

        expect([...$tokens])->toEqual([
            new Literal('"display"'),
        ]);
    });

    it('should allow escaping whitespace in unquoted literals', function () {
        $tokens = new QueryTokenizer()->tokenize('hello\ world');

        expect([...$tokens])->toEqual([
            new Literal('hello world'),
        ]);
    });

    it('should tokenize `:` operator ', function () {
        $tokens = new QueryTokenizer()->tokenize('name:ivan');

        expect([...$tokens])->toEqual([
            new Literal('name'),
            new Operator(':'),
            new Literal('ivan'),
        ]);
    });

    it('should tokenize "greater" operator ', function () {
        $tokens = new QueryTokenizer()->tokenize('year>2020');

        expect([...$tokens])->toEqual([
            new Literal('year'),
            new Operator('>'),
            new Literal('2020'),
        ]);
    });

    it('should tokenize "greater equals" operator ', function () {
        $tokens = new QueryTokenizer()->tokenize('year>=2020');

        expect([...$tokens])->toEqual([
            new Literal('year'),
            new Operator('>='),
            new Literal('2020'),
        ]);
    });

    it('should tokenize "less" operator ', function () {
        $tokens = new QueryTokenizer()->tokenize('year<2020');

        expect([...$tokens])->toEqual([
            new Literal('year'),
            new Operator('<'),
            new Literal('2020'),
        ]);
    });

    it('should tokenize "less equal" operator ', function () {
        $tokens = new QueryTokenizer()->tokenize('year<=2020');

        expect([...$tokens])->toEqual([
            new Literal('year'),
            new Operator('<='),
            new Literal('2020'),
        ]);
    });

    it('should tokenize invalid usage of `:` operator preceding a literal', function () {
        $tokens = new QueryTokenizer()->tokenize(':ivan');

        expect([...$tokens])->toEqual([
            new Operator(':'),
            new Literal('ivan'),
        ]);
    });

    it('should tokenize leading `-` operator', function () {
        $tokens = new QueryTokenizer()->tokenize('-name:ivan');

        expect([...$tokens])->toEqual([
            new Operator('-'),
            new Literal('name'),
            new Operator(':'),
            new Literal('ivan'),
        ]);
    });

    it('should tokenize in-value `-` operator ', function () {
        $tokens = new QueryTokenizer()->tokenize('name:-ivan');

        expect([...$tokens])->toEqual([
            new Literal('name'),
            new Operator(':'),
            new Operator('-'),
            new Literal('ivan'),
        ]);
    });

    it('should allow unquoted literals with minus characters', function () {
        $tokens = new QueryTokenizer()->tokenize('hello-world status--active');

        expect([...$tokens])->toEqual([
            new Literal('hello-world'),
            new Whitespace(),
            new Literal('status--active'),
        ]);
    });

    it('should tokenize invalid usages of operators', function () {
        $tokens = new QueryTokenizer()->tokenize(': - :: -- name:--ivan -tag:-user --status:--active');

        expect([...$tokens])->toEqual([
            new Operator(':'),
            new Whitespace(),
            new Operator('-'),
            new Whitespace(),
            new Operator(':'),
            new Operator(':'),
            new Whitespace(),
            new Operator('-'),
            new Operator('-'),
            new Whitespace(),
            new Literal('name'),
            new Operator(':'),
            new Operator('-'),
            new Operator('-'),
            new Literal('ivan'),
            new Whitespace(),
            new Operator('-'),
            new Literal('tag'),
            new Operator(':'),
            new Operator('-'),
            new Literal('user'),
            new Whitespace(),
            new Operator('-'),
            new Operator('-'),
            new Literal('status'),
            new Operator(':'),
            new Operator('-'),
            new Operator('-'),
            new Literal('active'),
        ]);
    });

    it('should tokenize invalid multi-operator expressions', function () {
        $tokens = new QueryTokenizer()->tokenize('name:ivan:john status:<=active year>=<2020');

        expect([...$tokens])->toEqual([
            new Literal('name'),
            new Operator(':'),
            new Literal('ivan'),
            new Operator(':'),
            new Literal('john'),
            new Whitespace(),
            new Literal('status'),
            new Operator(':'),
            new Operator('<='),
            new Literal('active'),
            new Whitespace(),
            new Literal('year'),
            new Operator('>='),
            new Operator('<'),
            new Literal('2020'),
        ]);
    });
});