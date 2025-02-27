<?php

namespace DrewRoberts\Media\Models;

use DrewRoberts\Media\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as DbCollection;
use Illuminate\Support\Collection;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasCreator;
use Tipoff\Support\Traits\HasPackageFactory;
use Tipoff\Support\Traits\HasUpdater;

class Tag extends BaseModel implements Sortable
{
    use SortableTrait, HasSlug, HasCreator, HasUpdater, HasPackageFactory;

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = $tag->generateSlug();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getPathAttribute()
    {
        return "/tags/{$this->slug}";
    }

    public function scopeWithType(Builder $query, string $type = null): Builder
    {
        if (is_null($type)) {
            return $query;
        }

        return $query->where('type', $type)->ordered();
    }

    /**
     * @param string|array|\ArrayAccess $values
     * @param string|null $type
     *
     * @return \DrewRoberts\Media\Tag|static
     */
    public static function findOrCreate($values, string $type = null)
    {
        $tags = collect($values)->map(function ($value) use ($type) {
            if ($value instanceof self) {
                return $value;
            }

            return static::findOrCreateFromString($value, $type);
        });

        return is_string($values) ? $tags->first() : $tags;
    }

    public static function getWithType(string $type): DbCollection
    {
        return static::withType($type)->ordered()->get();
    }

    public static function findFromString(string $name, string $type = null)
    {
        return static::query()
            ->where('name', $name)
            ->where('type', $type)
            ->first();
    }

    public static function findFromStringOfAnyType(string $name)
    {
        return static::query()
            ->where('name', $name)
            ->first();
    }

    protected static function findOrCreateFromString(string $name, string $type = null)
    {
        $tag = static::findFromString($name, $type);

        if (! $tag) {
            $tag = static::create([
                'name' => $name,
                'type' => $type,
            ]);
        }

        return $tag;
    }

    public static function getTypes(): Collection
    {
        return static::groupBy('type')->pluck('type');
    }
}
