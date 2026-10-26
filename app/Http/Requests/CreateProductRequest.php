<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\NewProductData;
use App\Data\NewVariantData;
use App\Rules\OptionNames;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class CreateProductRequest extends FormRequest
{
    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:products,slug'],
            'description' => ['nullable', 'string', 'max:5000'],
            'categoryId' => ['required', 'string', 'exists:categories,id'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*.url' => ['required', 'url', 'max:2048'],
            'images.*.alt' => ['required', 'string', 'max:200'],
            'variants' => ['required', 'array', 'min:1', 'max:100'],
            'variants.*.sku' => ['required', 'string', 'max:64', 'distinct', 'unique:variants,sku'],
            'variants.*.name' => ['required', 'string', 'max:200'],
            'variants.*.options' => ['nullable', 'array', new OptionNames],
            'variants.*.options.*' => ['string', 'max:50'],
        ];
    }

    public function toData(): NewProductData
    {
        /** @var list<array{url: string, alt: string}> $images */
        $images = $this->validated('images') ?? [];
        /** @var list<array{sku: string, name: string, options?: array<string, string>|null}> $variants */
        $variants = $this->validated('variants');

        return new NewProductData(
            name: $this->string('name')->trim()->toString(),
            slug: $this->string('slug')->toString(),
            description: $this->string('description')->toString(),
            categoryId: $this->string('categoryId')->toString(),
            images: array_map(fn (array $image): array => ['url' => $image['url'], 'alt' => $image['alt']], $images),
            variants: array_map(fn (array $variant): NewVariantData => new NewVariantData(
                sku: $variant['sku'],
                name: $variant['name'],
                options: $variant['options'] ?? [],
            ), $variants),
        );
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && is_string($this->input('name'))) {
            $this->merge(['slug' => Str::slug($this->string('name')->toString())]);
        }
    }
}
