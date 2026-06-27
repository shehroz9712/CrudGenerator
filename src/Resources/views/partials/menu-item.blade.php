@php
    $isActive = $item['route'] ? \Shehroz\CrudGenerator\Support\AdminMenu::isActive($item['route']) : false;
    $href = $item['route'] ? route($item['route']) : ($item['url'] ?? '#');
@endphp

<a href="{{ $href }}"
   class="flex items-center gap-2 px-3 py-2 rounded {{ $isActive ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }} {{ !empty($nested) ? 'ml-4' : '' }}">
    @if($item['icon'])<i class="{{ $item['icon'] }} w-5 text-center"></i>@endif
    <span>{{ $item['label'] }}</span>
</a>
