<?php

declare(strict_types=1);

namespace Frostbitten\FlexibleUrl;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Validator as ValidatorInstance;

class FlexibleUrl implements ValidationRule, ValidatorAwareRule
{
    protected ValidatorInstance $validator;

    /** @var ?Closure(string, string): void */
    private ?Closure $afterNormalization;

    public function __construct(?Closure $afterNormalization = null)
    {
        $this->afterNormalization = $afterNormalization;
    }

    public function setValidator(ValidatorInstance $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $normalized = $this->normalize($value);

        if (Validator::make([$attribute => $normalized], [$attribute => 'url'])->fails()) {
            $fail('validation.url')->translate();

            return;
        }

        if ($normalized !== $value) {
            $this->validator->after(function () use ($attribute, $normalized): void {
                $data = $this->validator->getData();
                Arr::set($data, $attribute, $normalized);
                $this->validator->setData($data);
            });

            if ($this->afterNormalization) {
                ($this->afterNormalization)($attribute, $normalized);
            }
        }
    }

    private function normalize(string $value): string
    {
        if (! preg_match('#^https?://#i', $value)) {
            return "https://$value";
        }

        return $value;
    }
}
