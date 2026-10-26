<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $parent_id
 * @property string $slug
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['parent_id', 'slug', 'name'])]
final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * Ids of this category and every category below it.
     *
     * @return list<string>
     */
    public function selfAndDescendantIds(): array
    {
        /** @var array<string, list<string>> $children */
        $children = [];
        foreach (self::query()->whereNotNull('parent_id')->get(['id', 'parent_id']) as $category) {
            $children[(string) $category->parent_id][] = $category->id;
        }

        $ids = [];
        $queue = [$this->id];
        while ($queue !== []) {
            $id = array_shift($queue);
            $ids[] = $id;
            array_push($queue, ...($children[$id] ?? []));
        }

        return $ids;
    }
}
