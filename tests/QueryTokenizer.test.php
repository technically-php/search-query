<?php

use Technically\SearchQuery\Tokenizer\QueryTokenizer;
use Technically\SearchQuery\Tokenizer\Tokens\Literal;
use Technically\SearchQuery\Tokenizer\Tokens\Operator;
use Technically\SearchQuery\Tokenizer\Tokens\Whitespace;

describe('QueryTokenizer', function () {
    it('should tokenize empty string to an empty sequence of tokens', function () {
        $tokenizer = new QueryTokenizer('');

        expect([...$tokenizer->tokenize()])->toBe([]);
    });

    it('should tokenize whitespace-only squence to a single whitespace token', function () {
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

    it('should tokenize a quoted sequence as a single literal token', function () {
        $tokenizer = new QueryTokenizer('"hello world"');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('hello world', quoted: true),
        ]);
    });

    it('should tokenize series of quoted sequences as literal tokens', function () {
        $tokenizer = new QueryTokenizer('"hello world" "this is a sequence" "of literals"');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('hello world', quoted: true),
            new Whitespace(),
            new Literal('this is a sequence', quoted: true),
            new Whitespace(),
            new Literal('of literals', quoted: true),
        ]);
    });

    it('should allow escaped quotes inside quoted literals', function () {
        $tokenizer = new QueryTokenizer('"hello \"new\" world"');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('hello "new" world', quoted: true),
        ]);
    });

    it('should allow using escape character before any character', function () {
        $tokenizer = new QueryTokenizer('"\h\e\l\l\o \"\n\e\w\" \w\o\r\l\d"');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('hello "new" world', quoted: true),
        ]);
    });

    it('should gracefully handle unclosed quoted literals', function () {
        $tokenizer = new QueryTokenizer('hello "new world');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('hello'),
            new Whitespace(),
            new Literal('new world', quoted: true),
        ]);
    });

    it('should allow quoting characters outside of quoted literals', function () {
        $tokenizer = new QueryTokenizer('display 55\"');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('display'),
            new Whitespace(),
            new Literal('55"'),
        ]);
    });

    it('should allow escaped quotes in unquoted characters ', function () {
        $tokenizer = new QueryTokenizer('\"display\"');

        expect([...$tokenizer->tokenize()])->toEqual([
            new Literal('"display"'),
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

    it('should tokenize potentially usage of `:` operator preceding a literal', function () {
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
});