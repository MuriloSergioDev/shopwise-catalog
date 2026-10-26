<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ProductSearchData
{
    /**
     * @param  array<string, string>  $options  option name => required value
     */
    public function __construct(
        public ?string $query = null,
        public ?string $categorySlug = null,
        public array $options = [],
        public int $page = 1,
        public int $perPage = 24,
    ) {}
}
