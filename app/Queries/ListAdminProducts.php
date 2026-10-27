<?php

declare(strict_types=1);

namespace App\Queries;

use App\Data\AdminProductListData;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Every product for the admin, drafts included, newest first. The text filter is a case-insensitive substring of
 * the name, the slug or one of the variant SKUs; it behaves the same on Postgres and SQLite.
 */
final readonly class ListAdminProducts
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function handle(AdminProductListData $data): LengthAwarePaginator
    {
        $query = Product::query()->with('category')
            ->withCount(['variants', 'variants as active_variants_count' => fn (Builder $variants) => $variants->where('active', true)]);

        if ($data->query !== null) {
            $needle = '%'.mb_strtolower($data->query).'%';
            $query->where(fn (Builder $q) => $q->whereRaw('LOWER(name) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(slug) LIKE ?', [$needle])
                ->orWhereExists(fn (QueryBuilder $sub): QueryBuilder => $sub->from('variants')
                    ->whereColumn('variants.product_id', 'products.id')
                    ->whereRaw('LOWER(variants.sku) LIKE ?', [$needle])));
        }

        if ($data->status instanceof ProductStatus) {
            $query->where('status', $data->status);
        }

        if ($data->categorySlug !== null) {
            // An unknown slug matches nothing rather than everything.
            $query->whereIn('category_id', Category::query()->where('slug', $data->categorySlug)->first()?->selfAndDescendantIds() ?? []);
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(perPage: $data->perPage, page: $data->page);
    }
}
