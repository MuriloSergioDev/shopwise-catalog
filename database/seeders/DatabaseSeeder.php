<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\CreateProduct;
use App\Actions\PublishProduct;
use App\Data\NewProductData;
use App\Data\NewVariantData;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo catalog for the storefront. Goes through the actions, so the cluster also sees the catalog events.
 * Runs once: a second run is a no-op.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(CreateProduct $createProduct, PublishProduct $publishProduct): void
    {
        if (Category::query()->exists()) {
            return;
        }

        $apparel = Category::query()->create(['slug' => 'apparel', 'name' => 'Apparel']);
        $tops = Category::query()->create(['slug' => 'tops', 'name' => 'Tops', 'parent_id' => $apparel->id]);
        $bottoms = Category::query()->create(['slug' => 'bottoms', 'name' => 'Bottoms', 'parent_id' => $apparel->id]);
        $home = Category::query()->create(['slug' => 'home', 'name' => 'Home']);
        $kitchen = Category::query()->create(['slug' => 'kitchen', 'name' => 'Kitchen', 'parent_id' => $home->id]);

        $products = [
            [$tops, 'Merino Crew Tee', 'TEE-MER', 'Soft merino wool t-shirt that keeps you warm in winter and cool in summer.', ['Black', 'Navy'], ['S', 'M', 'L']],
            [$tops, 'Linen Camp Shirt', 'SHI-LIN', 'Breathable linen shirt with a relaxed camp collar.', ['White', 'Sage'], ['M', 'L']],
            [$tops, 'Heavyweight Hoodie', 'HOO-HVY', 'Brushed cotton fleece hoodie for cold mornings.', ['Grey', 'Black'], ['S', 'M', 'L', 'XL']],
            [$bottoms, 'Selvedge Denim Jeans', 'JEA-SEL', 'Raw selvedge denim in a straight cut.', ['Indigo'], ['30', '32', '34']],
            [$bottoms, 'Cotton Chino Shorts', 'SHO-CHI', 'Lightweight cotton shorts for warm days.', ['Khaki', 'Navy'], ['30', '32']],
            [$kitchen, 'Cast Iron Skillet', 'SKL-CST', 'Pre-seasoned 26 cm cast iron pan that lasts a lifetime.', [], []],
            [$kitchen, 'Ceramic Pour-Over Coffee Set', 'COF-POUR', 'Hand-glazed dripper and carafe for slow coffee mornings.', ['White', 'Charcoal'], []],
        ];

        foreach ($products as [$category, $name, $skuPrefix, $description, $colors, $sizes]) {
            $product = $createProduct->handle(new NewProductData(
                name: $name,
                slug: Str::slug($name),
                description: $description,
                categoryId: $category->id,
                images: [['url' => 'https://picsum.photos/seed/'.Str::slug($name).'/800/800', 'alt' => $name]],
                variants: $this->variants($skuPrefix, $colors, $sizes),
            ));
            $publishProduct->handle($product);
        }
    }

    /**
     * Every color × size combination; a product without options gets a single variant.
     *
     * @param  list<string>  $colors
     * @param  list<string>  $sizes
     * @return list<NewVariantData>
     */
    private function variants(string $skuPrefix, array $colors, array $sizes): array
    {
        $variants = [];
        foreach ($colors === [] ? [null] : $colors as $color) {
            foreach ($sizes === [] ? [null] : $sizes as $size) {
                $options = array_filter(['color' => $color, 'size' => $size], fn (?string $value): bool => $value !== null);
                $variants[] = new NewVariantData(
                    sku: implode('-', [$skuPrefix, ...array_map(fn (string $value): string => mb_strtoupper(mb_substr($value, 0, 3)), $options)]),
                    name: $options === [] ? 'Standard' : implode(' / ', $options),
                    options: $options,
                );
            }
        }

        return $variants;
    }
}
