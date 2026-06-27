<aside class="w-64 bg-gray-900 text-gray-100 min-h-screen">
    <div class="p-4 text-lg font-semibold border-b border-gray-800">
        {{ config('app.name', 'Admin Panel') }}
    </div>

    <nav class="p-3 space-y-1">
        @foreach(\Shehroz\CrudGenerator\Support\AdminMenu::visible() as $item)
            @if(!empty($item['children']))
                <div class="mb-2">
                    <div class="px-3 py-2 text-xs uppercase tracking-wide text-gray-400">
                        @if($item['icon'])<i class="{{ $item['icon'] }} mr-2"></i>@endif
                        {{ $item['label'] }}
                    </div>
                    @foreach($item['children'] as $child)
                        @include('crud-generator::partials.menu-item', ['item' => $child, 'nested' => true])
                    @endforeach
                </div>
            @else
                @include('crud-generator::partials.menu-item', ['item' => $item, 'nested' => false])
            @endif
        @endforeach
    </nav>
</aside>
