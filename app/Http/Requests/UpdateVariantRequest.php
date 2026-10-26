<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\VariantChangesData;
use App\Rules\OptionNames;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateVariantRequest extends FormRequest
{
    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required_without_all:options,active', 'string', 'max:200'],
            'options' => ['array', new OptionNames],
            'options.*' => ['string', 'max:50'],
            'active' => ['boolean'],
        ];
    }

    public function toData(): VariantChangesData
    {
        /** @var array<string, string>|null $options */
        $options = $this->has('options') ? ($this->validated('options') ?? []) : null;

        return new VariantChangesData(
            name: $this->has('name') ? $this->string('name')->trim()->toString() : null,
            options: $options,
            active: $this->has('active') ? $this->boolean('active') : null,
        );
    }
}
