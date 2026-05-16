@extends('layouts.main-navigation')


@section('navlinks')
<div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
    <x-nav-link href="{{ route('admin.manage_books') }}" :active="request()->routeIs('admin.manage_books')">
        {{ __('Manage Books') }}
    </x-nav-link>

    <x-nav-link href="{{ route('admin.manage_categories') }}" :active="request()->routeIs('admin.manage_categories')">
        {{ __('Manage Categories') }}
    </x-nav-link>

    <x-dropdown align="left" width="48">
        <x-slot name="trigger">
            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                {{ __('Categories') }}
                <div class="ms-1">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </button>
        </x-slot>

        <x-slot name="content">
            @forelse(\App\Models\Category::all() as $category)
                <x-dropdown-link :href="route('books.by_category', $category->id)">
                    {{ $category->name }}
                </x-dropdown-link>
            @empty
                <x-dropdown-link href="#">
                    {{ __('No categories') }}
                </x-dropdown-link>
            @endforelse
        </x-slot>
    </x-dropdown>
</div>
@endsection

@section('right-navlinks')
<div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
    <x-nav-link href="{{ route('admin.customer_orders') }}" :active="request()->routeIs('admin.customer_orders')">
        {{ __('Customer Orders') }}
    </x-nav-link>
</div>
@endsection

@section('responsive-navlinks')
    <x-responsive-nav-link :href="route('admin.manage_books')" :active="request()->routeIs('admin.manage_books')">
        {{ __('Manage Books') }}
    </x-responsive-nav-link>

    <x-responsive-nav-link :href="route('admin.manage_categories')" :active="request()->routeIs('admin.manage_categories')">
        {{ __('Manage Categories') }}
    </x-responsive-nav-link>

    <x-responsive-nav-link :href="route('admin.customer_orders')" :active="request()->routeIs('admin.customer_orders')">
        {{ __('Customer Orders') }}
    </x-responsive-nav-link>
@endsection
