<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\AdminProductListData;
use App\Enums\ProductStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class ListAdminProductsRequest extends FormRequest
{
    /**
     * @return array<string, list<string|ValidationRule|Enum>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'category' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toData(): AdminProductListData
    {
        $query = $this->string('q')->trim()->toString();
        $category = $this->string('category')->toString();

        return new AdminProductListData(
            query: $query === '' ? null : $query,
            status: $this->enum('status', ProductStatus::class),
            categorySlug: $category === '' ? null : $category,
            page: $this->integer('page', 1),
            perPage: $this->integer('perPage', 25),
        );
    }
}
