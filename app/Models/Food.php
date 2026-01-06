<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Food extends Model
{
    use HasFactory;

    protected $table = 'foods';

    public $incrementing = true;

    protected $fillable = [
        'name',
        'slug',
        'protein_per_100g',
        'fat_per_100g',
        'carbs_per_100g',
        'calories_per_100g',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($food) {
            // Only generate slug if it's not already set
            if (empty($food->slug) && !empty($food->name)) {
                $food->slug = static::generateUniqueSlug($food->name);
            }
        });

        static::updating(function ($food) {
            // Regenerate slug if name changed and slug wasn't manually set
            if ($food->isDirty('name') && empty($food->slug)) {
                $food->slug = static::generateUniqueSlug($food->name, $food->id);
            }
        });
    }

    /**
     * Generate a unique slug from the given string.
     */
    protected static function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)
            ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function scopeSearch($query, string $term, bool $withTrashed = false)
    {
        return $query->when($withTrashed, fn($q) => $q->withTrashed())
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
    }
}
