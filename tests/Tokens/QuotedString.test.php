<?php

use Technically\SearchQuery\Tokens\QuotedString;

describe('QuotedString', function (): void {
    it('should instantiate', function (): void {
        $string = new QuotedString('hello');

        expect($string)->toBeInstanceOf(QuotedString::class);
        expect($string->value)->toBe('hello');
    });

    it('should empty values', function (): void {
        $string = new QuotedString('');
        
        expect($string->value)->toBe('');
    });

    it('should allow values with whitespace', function (): void {
        $string = new QuotedString('hello world');

        expect($string->value)->toBe('hello world');
    });

    it('should serialize back to string', function (): void {
        $string = new QuotedString('hello');

        expect($string->toString())->toBe('"hello"');
    });

    it('should serialize values with whitespace back to string', function (): void {
        $string = new QuotedString('hello world');

        expect($string->toString())->toBe('"hello world"');
    });

    it('should serialize values with quotes back to string', function (): void {
        $string = new QuotedString('hello "kind" world');

        expect($string->toString())->toBe('"hello \"kind\" world"');
    });
});