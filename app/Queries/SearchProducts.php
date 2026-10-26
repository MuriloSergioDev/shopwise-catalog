<?php

declare(strict_types=1);

namespace App\Queries;

use App\Data\ProductSearchData;
use App\Data\ProductSearchResult;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Storefront browse and search over published products. Postgres uses the `products.search` tsvector
 * (websearch syntax, ranked); SQLite (local tests) falls back to LIKE on every word.
 */
final readonly class SearchProducts
{
    public function handle(ProductSearchData $data): ProductSearchResult
    {
        $categoryIds = $this->categoryIds($data->categorySlug);

        $query = $this->filtered($data, $categoryIds)->with('category');
        if ($data->query !== null && $this->fullText()) {
            $query->orderByRaw("ts_rank(products.search, websearch_to_tsquery('english', ?)) DESC", [$data->query]);
        }

        $products = $query->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(perPage: $data->perPage, page: $data->page);

        return new ProductSearchResult(
            products: $products,
            categoryFacets: $this->categoryFacets($this->filtered($data, null)),
            optionFacets: $this->optionFacets($data, $categoryIds),
        );
    }

    /**
     * Published products matching every filter. A facet passes its own filter as "skipped" (null category ids,
     * or the option name in $skipOption) so shoppers see what widening it would give.
     *
     * @param  list<string>|null  $categoryIds
     * @return Builder<Product>
     */
    private function filtered(ProductSearchData $data, ?array $categoryIds, ?string $skipOption = null): Builder
    {
        $query = Product::query()->published();

        if ($data->query !== null) {
            if ($this->fullText()) {
                $query->whereRaw("products.search @@ websearch_to_tsquery('english', ?)", [$data->query]);
            } else {
                foreach (preg_split('/\s+/', $data->query, flags: PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                    $query->where(fn (Builder $q) => $q->where('name', 'like', "%{$word}%")->orWhere('description', 'like', "%{$word}%"));
                }
            }
        }

        if ($categoryIds !== null) {
            $query->whereIn('category_id', $categoryIds);
        }

        foreach ($data->options as $name => $value) {
            if ($name === $skipOption) {
                continue;
            }

            $query->whereExists(fn (QueryBuilder $sub): QueryBuilder => $sub->from('variants')
                ->join('variant_options', 'variant_options.variant_id', '=', 'variants.id')
                ->whereColumn('variants.product_id', 'products.id')
                ->where('variants.active', true)
                ->where('variant_options.name', $name)
                ->where('variant_options.value', $value));
        }

        return $query;
    }

    /**
     * @return list<string>|null null when no category filter is set
     */
    private function categoryIds(?string $slug): ?array
    {
        if ($slug === null) {
            return null;
        }

        // An unknown slug matches nothing rather than everything.
        return Category::query()->where('slug', $slug)->first()?->selfAndDescendantIds() ?? [];
    }

    /**
     * @param  Builder<Product>  $products
     * @return list<array{slug: string, name: string, count: int}>
     */
    private function categoryFacets(Builder $products): array
    {
        /** @var array<string, int|string> $counts */
        $counts = $products->toBase()->select('category_id', DB::raw('COUNT(*) AS count'))->groupBy('category_id')
            ->pluck('count', 'category_id')->all();

        $facets = [];
        foreach (Category::query()->whereIn('id', array_keys($counts))->orderBy('name')->get() as $category) {
            $facets[] = ['slug' => $category->slug, 'name' => $category->name, 'count' => (int) $counts[$category->id]];
        }

        return $facets;
    }

    /**
     * @param  list<string>|null  $categoryIds
     * @return list<array{name: string, values: list<array{value: string, count: int}>}>
     */
    private function optionFacets(ProductSearchData $data, ?array $categoryIds): array
    {
        $facets = [];
        /** @var list<string> $names */
        $names = DB::table('variant_options')->distinct()->orderBy('name')->pluck('name')->all();
        foreach ($names as $name) {
            $products = $this->filtered($data, $categoryIds, skipOption: $name)->select('products.id');

            /** @var list<object{value: string, count: int|string}> $rows */
            $rows = DB::table('variant_options')
                ->join('variants', 'variants.id', '=', 'variant_options.variant_id')
                ->where('variants.active', true)
                ->where('variant_options.name', $name)
                ->whereIn('variants.product_id', $products)
                ->groupBy('variant_options.value')
                ->orderBy('variant_options.value')
                ->select('variant_options.value', DB::raw('COUNT(DISTINCT variants.product_id) AS count'))
                ->get()->all();

            if ($rows !== []) {
                $facets[] = [
                    'name' => $name,
                    'values' => array_map(fn (object $row): array => ['value' => $row->value, 'count' => (int) $row->count], $rows),
                ];
            }
        }

        return $facets;
    }

    private function fullText(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }
}
