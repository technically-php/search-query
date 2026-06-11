<?php

use Technically\SearchQuery\Tokens\Literal;

describe('Literal', function (): void {
    it('should instantiate', function (): void {
        $literal = new Literal('hello');

        expect($literal)->toBeInstanceOf(Literal::class);
        expect($literal->value)->toBe('hello');
    });

    it('should disallow empty literals', function (): void {
        expect(fn () => new Literal(''))->toThrow(InvalidArgumentException::class);
    });

    it('should allow values with whitespace', function (): void {
        $literal = new Literal('hello world');

        expect($literal->value)->toBe('hello world');
    });

    it('should serialize back to string', function (): void {
        $literal = new Literal('hello');

        expect($literal->toString())->toBe('hello');
    });

    it('should serialize values with whitespace back to string', function (): void {
        $literal = new Literal('hello world');

        expect($literal->toString())->toBe('hello\ world');
    });
});