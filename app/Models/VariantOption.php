<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $variant_id
 * @property string $name
 * @property string $value
 */
#[Fillable(['name', 'value'])]
#[WithoutTimestamps]
final class VariantOption extends Model {}
