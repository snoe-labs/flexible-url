<?php

declare(strict_types=1);

use Frostbitten\FlexibleUrl\FlexibleUrl;
use Illuminate\Support\Facades\Validator;

it('passes for a url with https scheme', function () {
    $validator = Validator::make(
        ['website' => 'https://google.com'],
        ['website' => ['required', new FlexibleUrl]],
    );

    expect($validator->passes())->toBeTrue()
        ->and($validator->validated()['website'])->toBe('https://google.com');
});

it('passes for a url with http scheme', function () {
    $validator = Validator::make(
        ['website' => 'http://google.com'],
        ['website' => ['required', new FlexibleUrl]],
    );

    expect($validator->passes())->toBeTrue()
        ->and($validator->validated()['website'])->toBe('http://google.com');
});

it('prepends https when scheme is missing', function () {
    $validator = Validator::make(
        ['website' => 'google.com'],
        ['website' => ['required', new FlexibleUrl]],
    );

    expect($validator->passes())->toBeTrue()
        ->and($validator->validated()['website'])->toBe('https://google.com');
});

it('prepends https for a url with path and query', function () {
    $validator = Validator::make(
        ['website' => 'example.com/some/path?q=1'],
        ['website' => ['required', new FlexibleUrl]],
    );

    expect($validator->passes())->toBeTrue()
        ->and($validator->validated()['website'])->toBe('https://example.com/some/path?q=1');
});

it('does not double-prepend when https is already present', function () {
    $validator = Validator::make(
        ['website' => 'https://example.com'],
        ['website' => ['required', new FlexibleUrl]],
    );

    expect($validator->passes())->toBeTrue()
        ->and($validator->validated()['website'])->toBe('https://example.com');
});

it('normalizes multiple url fields independently', function () {
    $validator = Validator::make(
        ['website' => 'google.com', 'facebook' => 'facebook.com/page'],
        ['website' => ['required', new FlexibleUrl], 'facebook' => ['required', new FlexibleUrl]],
    );

    expect($validator->passes())->toBeTrue()
        ->and($validator->validated()['website'])->toBe('https://google.com')
        ->and($validator->validated()['facebook'])->toBe('https://facebook.com/page');
});

it('fails for a completely invalid url', function () {
    $validator = Validator::make(
        ['website' => 'not a valid url'],
        ['website' => new FlexibleUrl],
    );

    expect($validator->fails())->toBeTrue();
});

it('skips validation for empty strings', function () {
    $validator = Validator::make(
        ['website' => ''],
        ['website' => ['nullable', new FlexibleUrl]],
    );

    expect($validator->passes())->toBeTrue();
});

it('skips validation for null values', function () {
    $validator = Validator::make(
        ['website' => null],
        ['website' => ['nullable', new FlexibleUrl]],
    );

    expect($validator->passes())->toBeTrue();
});

it('invokes the after-normalization callback', function () {
    $callbackCalls = [];

    $rule = new FlexibleUrl(function (string $attribute, string $normalized) use (&$callbackCalls) {
        $callbackCalls[] = [$attribute, $normalized];
    });

    $validator = Validator::make(
        ['website' => 'example.com'],
        ['website' => ['required', $rule]],
    );

    $validator->passes();

    expect($callbackCalls)->toHaveCount(1)
        ->and($callbackCalls[0])->toBe(['website', 'https://example.com']);
});

it('does not invoke the callback when no normalization is needed', function () {
    $called = false;

    $rule = new FlexibleUrl(function () use (&$called) {
        $called = true;
    });

    $validator = Validator::make(
        ['website' => 'https://example.com'],
        ['website' => ['required', $rule]],
    );

    $validator->passes();

    expect($called)->toBeFalse();
});