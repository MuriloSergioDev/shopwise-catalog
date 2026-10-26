<?php

declare(strict_types=1);

namespace App\Data;

final readonly class NewProductData
{
    /**
     * @param  list<array{url: string, alt: string}>  $images
     * @param  list<NewVariantData>  $variants
     */
    public function __construct(
        public string $name,
        public string $slug,
        public string $description,
        public string $categoryId,
        public array $images,
        public array $variants,
    ) {}
}
