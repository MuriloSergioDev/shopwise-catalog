<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape defined in shopwise-contracts/openapi/catalog.yaml (components.schemas.Category).
 *
 * @mixin Category
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array{id: string, slug: string, name: string, parentId: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'parentId' => $this->parent_id,
        ];
    }
}
