<?php

use Technically\SearchQuery\QueryTokenizer;
use Technically\SearchQuery\Tokens\Literal;
use Technically\SearchQuery\Tokens\Operator;
use Technically\SearchQuery\Tokens\QuotedString;
use Technically\SearchQuery\Tokens\Whitespace;

describe('QueryTokenizer', function () {
    it('should tokenize empty string to an empty sequence of tokens', function () {
        $tokenizer = new QueryTokenizer('');

        expect([...$tokenizer->tokenize()])->toBe([]);
    });

    it('should tokenize whitespace-only sequence to a single whitespace token', function () {
        $tokenizer = new QueryTokenizer('   ');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Whitespace('   '),
        ]);
    });

    it('should tokenize a non-whitespace sequence to a single literal token', function () {
        $tokenizer = new QueryTokenizer('hello-world');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('hello-world'),
        ]);
    });

    it('should tokenize a mix of whitespace and non-whitespace sequences', function () {
        $tokenizer = new QueryTokenizer('  hello   world  ');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Whitespace('  '),
            new Literal('hello'),
            new Whitespace('   '),
            new Literal('world'),
            new Whitespace('  '),
        ]);
    });

    it('should tokenize a quoted sequence as a single quoted string token', function () {
        $tokenizer = new QueryTokenizer('"hello world"');

        expect([...$tokenizer->tokenize()])->toEqual([
            new QuotedString('hello world'),
        ]);
    });

    it('should tokenize series of quoted sequences as quoted strings tokens', function () {
        $tokenizer = new QueryTokenizer('"hello world" "this is a sequence" "of quoted strings"');

        expect([...$tokenizer->tokenize()])->toEqual([
            new QuotedString('hello world'),
            new Whitespace(),
            new QuotedString('this is a sequence'),
            new Whitespace(),
            new QuotedString('of quoted strings'),
        ]);
    });

    it('should allow escaping quotes inside quoted strings', function () {
        $tokenizer = new QueryTokenizer('"hello \"new\" world"');

        expect([...$tokenizer->tokenize()])->toEqual([
            new QuotedString('hello "new" world'),
        ]);
    });

    it('should allow using escape character with any character', function () {
        $tokenizer = new QueryTokenizer('"\h\e\l\l\o \"\n\e\w\" \w\o\r\l\d"');

        expect([...$tokenizer->tokenize()])->toEqual([
            new QuotedString('hello "new" world'),
        ]);
    });

    it('should gracefully handle unclosed quoted literals', function () {
        $tokenizer = new QueryTokenizer('hello "new world');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('hello'),
            new Whitespace(),
            new QuotedString('new world'),
        ]);
    });

    it('should allow quoting characters outside of quoted strings', function () {
        $tokenizer = new QueryTokenizer('display 55\"');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('display'),
            new Whitespace(),
            new Literal('55"'),
        ]);
    });

    it('should allow escaping quotes in unquoted literals', function () {
        $tokenizer = new QueryTokenizer('\"display\"');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('"display"'),
        ]);
    });

    it('should allow escaping whitespace in unquoted literals', function () {
        $tokenizer = new QueryTokenizer('hello\ world');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('hello world'),
        ]);
    });

    it('should tokenize `:` operator ', function () {
        $tokenizer = new QueryTokenizer('name:ivan');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('name'),
            new Operator(':'),
            new Literal('ivan'),
        ]);
    });

    it('should tokenize "greater" operator ', function () {
        $tokenizer = new QueryTokenizer('year>2020');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('year'),
            new Operator('>'),
            new Literal('2020'),
        ]);
    });

    it('should tokenize "greater equals" operator ', function () {
        $tokenizer = new QueryTokenizer('year>=2020');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('year'),
            new Operator('>='),
            new Literal('2020'),
        ]);
    });

    it('should tokenize "less" operator ', function () {
        $tokenizer = new QueryTokenizer('year<2020');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('year'),
            new Operator('<'),
            new Literal('2020'),
        ]);
    });

    it('should tokenize "less equal" operator ', function () {
        $tokenizer = new QueryTokenizer('year<=2020');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('year'),
            new Operator('<='),
            new Literal('2020'),
        ]);
    });

    it('should tokenize invalid usage of `:` operator preceding a literal', function () {
        $tokenizer = new QueryTokenizer(':ivan');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Operator(':'),
            new Literal('ivan'),
        ]);
    });

    it('should tokenize leading `-` operator', function () {
        $tokenizer = new QueryTokenizer('-name:ivan');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Operator('-'),
            new Literal('name'),
            new Operator(':'),
            new Literal('ivan'),
        ]);
    });

    it('should tokenize in-value `-` operator ', function () {
        $tokenizer = new QueryTokenizer('name:-ivan');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('name'),
            new Operator(':'),
            new Operator('-'),
            new Literal('ivan'),
        ]);
    });

    it('should allow unquoted literals with minus characters', function () {
        $tokenizer = new QueryTokenizer('hello-world status--active');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('hello-world'),
            new Whitespace(),
            new Literal('status--active'),
        ]);
    });

    it('should tokenize invalid usages of operators', function () {
        $tokenizer = new QueryTokenizer(': - :: -- name:--ivan -tag:-user --status:--active');

        expect([...$tokenizer->tokenize()])->toEqual([
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
        $tokenizer = new QueryTokenizer('name:ivan:john status:<=active year>=<2020');

        expect([...$tokenizer->tokenize()])->toEqual([
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