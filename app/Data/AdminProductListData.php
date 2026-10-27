<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ProductStatus;

final readonly class AdminProductListData
{
    public function __construct(
        public ?string $query = null,
        public ?ProductStatus $status = null,
        public ?string $categorySlug = null,
        public int $page = 1,
        public int $perPage = 25,
    ) {}
}
