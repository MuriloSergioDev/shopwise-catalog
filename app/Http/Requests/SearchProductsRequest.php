<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\ProductSearchData;
use App\Rules\OptionNames;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class SearchProductsRequest extends FormRequest
{
    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'],
            'options' => ['nullable', 'array', new OptionNames],
            'options.*' => ['string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:48'],
        ];
    }

    public function toData(): ProductSearchData
    {
        /** @var array<string, string> $options */
        $options = $this->validated('options') ?? [];
        $query = $this->string('q')->trim()->toString();
        $category = $this->string('category')->toString();

        return new ProductSearchData(
            query: $query === '' ? null : $query,
            categorySlug: $category === '' ? null : $category,
            options: $options,
            page: $this->integer('page', 1),
            perPage: $this->integer('perPage', 24),
        );
    }
}
