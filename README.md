# Flexible URL

[![tests](https://github.com/snoe-labs/flexible-url/actions/workflows/tests.yml/badge.svg)](https://github.com/snoe-labs/flexible-url/actions/workflows/tests.yml)

A Laravel validation rule that accepts URLs with or without a scheme. When the scheme is missing, it normalizes the value to `https://` automatically.

Users type `google.com`, not `https://google.com`. This rule handles that gracefully.

## Installation

```bash
composer require frostbitten/flexible-url
```

## Usage

Use it as a drop-in replacement for Laravel's `url` rule:

```php
use Frostbitten\FlexibleUrl\FlexibleUrl;

public function rules(): array
{
    return [
        'website' => ['nullable', new FlexibleUrl],
    ];
}
```

When a user submits `example.com`, the rule:
1. Prepends `https://` to get `https://example.com`
2. Validates the normalized URL using Laravel's built-in `url` rule
3. Updates the validator data so `$request->validated()` returns the normalized value

URLs that already include `http://` or `https://` are left untouched.

## How normalization works

The normalized value is written back to the validator's internal data via an `after` callback. This means `$validator->validated()` (and by extension `$request->validated()` in Form Requests) returns the normalized URL.

This works in all contexts:

- Form Requests
- Livewire validation
- Manual `Validator::make()` calls
- Artisan commands

### After-normalization callback

For cases where you need additional control, pass a callback:

```php
new FlexibleUrl(function (string $attribute, string $normalizedValue) {
    // e.g. update a Livewire property
    $this->$attribute = $normalizedValue;
})
```

The callback is only invoked when normalization actually occurs (i.e. a scheme was prepended). It is not called when the URL already has a scheme.

## Requirements

- PHP 8.2+ (8.3+ for Laravel 13)
- Laravel 12 or 13

## License

MIT