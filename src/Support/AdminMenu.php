<?php

namespace Shehroz\CrudGenerator\Support;

use Illuminate\Support\Collection;

class AdminMenu
{
    protected static array $runtimeItems = [];

    public static function add(array $item): void
    {
        static::$runtimeItems[] = static::normalize($item);
    }

    public static function all(): Collection
    {
        $configItems = collect(config('admin-menu.items', []))
            ->map(fn ($item) => static::normalize($item));

        return $configItems
            ->merge(collect(static::$runtimeItems))
            ->sortBy('order')
            ->values();
    }

    public static function tree(): Collection
    {
        $items = static::all();
        $parents = $items->whereNull('parent');
        $children = $items->whereNotNull('parent')->groupBy('parent');

        return $parents->map(function ($item) use ($children) {
            $item['children'] = $children->get($item['key'], collect())->values()->all();

            return $item;
        });
    }

    public static function visible(): Collection
    {
        return static::tree()->filter(function ($item) {
            if ($item['permission'] && auth()->check() && ! auth()->user()->can($item['permission'])) {
                return false;
            }

            if (! empty($item['children'])) {
                $item['children'] = collect($item['children'])->filter(function ($child) {
                    return ! $child['permission'] || (auth()->check() && auth()->user()->can($child['permission']));
                })->values()->all();
            }

            return true;
        })->values();
    }

    public static function isActive(string $routeName): bool
    {
        return request()->routeIs($routeName) || request()->routeIs($routeName . '.*');
    }

    protected static function normalize(array $item): array
    {
        return [
            'key' => $item['key'] ?? str($item['label'] ?? 'menu')->slug()->toString(),
            'label' => $item['label'] ?? 'Menu',
            'icon' => $item['icon'] ?? 'fas fa-circle',
            'route' => $item['route'] ?? null,
            'url' => $item['url'] ?? null,
            'permission' => $item['permission'] ?? null,
            'order' => (int) ($item['order'] ?? 100),
            'parent' => $item['parent'] ?? null,
            'children' => [],
        ];
    }
}
