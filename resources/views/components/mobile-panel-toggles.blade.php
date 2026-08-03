@props([
    'searchOpen' => false,
    'addOpen' => false,
])

@php
    $hasSearch = isset($search);
    $hasAdd = isset($add);
    $addOpen = $addOpen || ($hasAdd && $errors->any());
@endphp

<div
    {{ $attributes->class('mobile-panel-group') }}
    x-data="{
        searchOpen: @js((bool) $searchOpen),
        addOpen: @js((bool) $addOpen),
        toggleSearch() {
            this.searchOpen = !this.searchOpen;
            if (this.searchOpen) this.addOpen = false;
        },
        toggleAdd() {
            this.addOpen = !this.addOpen;
            if (this.addOpen) this.searchOpen = false;
        },
    }"
>
    @if($hasSearch || $hasAdd)
        <div class="mobile-panel-toolbar d-md-none mb-3" role="toolbar" aria-label="Search and add">
            @if($hasSearch)
                <button
                    type="button"
                    class="mobile-panel-toggle"
                    @click="toggleSearch()"
                    :class="{ 'is-active': searchOpen }"
                    :aria-expanded="searchOpen.toString()"
                    aria-controls="mobile-panel-search"
                    aria-label="Toggle search"
                    title="Search / filter"
                >
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </button>
            @endif
            @if($hasAdd)
                <button
                    type="button"
                    class="mobile-panel-toggle"
                    @click="toggleAdd()"
                    :class="{ 'is-active': addOpen }"
                    :aria-expanded="addOpen.toString()"
                    aria-controls="mobile-panel-add"
                    aria-label="Toggle add form"
                    title="Add"
                >
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                </button>
            @endif
        </div>
    @endif

    @if($hasSearch)
        <div
            id="mobile-panel-search"
            class="mobile-panel mobile-panel-search"
            :class="{ 'is-open': searchOpen }"
        >
            {{ $search }}
        </div>
    @endif

    @if($hasAdd)
        <div
            id="mobile-panel-add"
            class="mobile-panel mobile-panel-add"
            :class="{ 'is-open': addOpen }"
        >
            {{ $add }}
        </div>
    @endif
</div>
