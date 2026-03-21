@props([
    'columns' => [],
    'records' => null,
    'sortBy' => '',
    'sortDirection' => '',
    'striped' => true,
    'hoverable' => true,
    'filters' => [],
    'actions' => [],
    'searchPlaceholder' => '',
    'searchName' => 'table_search',
    'perPageOptions' => [10, 15, 25, 50, 100],
    'activeFilterCount' => 0,
    'mobileViewport' => null,
    'viewportSyncMethod' => 'syncTableViewport',
    'keyPrefix' => 'table-layout',
    'sortable' => false,
    'sortableActive' => false,
    'sortMethod' => 'reorderRows',
])

@php
    use App\Domain\Table\CardLayout;
    use Illuminate\Support\Js;

    $hasSearch = filled($searchPlaceholder);
    $hasFilters = $filters !== [];
    $hasActions = $actions !== [];
    $hasToolbar = $hasSearch || $hasFilters || $hasActions;
@endphp

<div
    x-data="tableViewportSync({{ Js::from($viewportSyncMethod) }}, {{ Js::from($mobileViewport) }})"
    class="space-y-2"
>
    @if ($hasToolbar)
        <flux:card
            class="bg-zinc-50 shadow-md dark:bg-white/10"
            :x-data="$hasFilters ? '{ showFilters: '.Js::from($activeFilterCount > 0)->toHtml().' }' : null"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 flex-1 items-center gap-2">
                    @if ($hasSearch)
                        <form autocomplete="off" class="w-auto! sm:max-w-sm">
                            <flux:input
                                wire:model.live.debounce.300ms="search"
                                :name="$searchName"
                                :id="Str::slug($searchName)"
                                autocomplete="off"
                                icon="magnifying-glass"
                                clearable
                                :placeholder="$searchPlaceholder"
                            />
                        </form>
                    @endif

                    <flux:select variant="listbox" wire:model.live="perPage" class="w-auto!" aria-label="{{ __('pagination.per_page') }}">
                        @foreach ($perPageOptions as $option)
                            <flux:select.option :value="$option">{{ $option }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    @if ($hasFilters)
                        <div class="relative">
                            <flux:button
                                icon="funnel"
                                x-on:click="showFilters = !showFilters"
                                x-bind:aria-expanded="showFilters"
                                aria-label="{{ __('actions.toggle_filters') }}"
                            />

                            @if ($activeFilterCount > 0)
                                <flux:badge size="sm" color="blue" class="pointer-events-none absolute -top-2 -right-2 size-5 items-center justify-center rounded-full p-0 text-xs">
                                    {{ $activeFilterCount }}
                                </flux:badge>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($hasActions)
                    <div class="flex items-center gap-2">
                        @foreach ($actions as $action)
                            <flux:button
                                :variant="$action->variant()"
                                :icon="$action->icon() !== '' ? $action->icon() : null"
                                wire:click="{{ $action->wireClick() }}"
                                @class(['w-full sm:w-auto' => $action->isResponsive()])
                            >
                                {{ $action->label() }}
                            </flux:button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($hasFilters)
                <flux:separator x-show="showFilters" x-cloak variant="subtle" class="my-6" />

                <div x-show="showFilters" x-transition x-cloak class="flex items-start gap-4">
                    @foreach ($filters as $filter)
                        @include("components.table.filters.{$filter->type()}", ['filter' => $filter])
                    @endforeach
                </div>
            @endif
        </flux:card>
    @endif

    {{-- Skeleton: visible while loading --}}
    <div
        x-show="loading"
        @if ($mobileViewport !== null) x-cloak @endif
    >
        <flux:skeleton.group animate="shimmer">
            {{-- Mobile --}}
            <div class="space-y-3 md:hidden">
                @foreach (range(1, 3) as $i)
                    <flux:card class="space-y-3 bg-zinc-50 shadow-md dark:bg-white/10">
                        <div class="flex items-center gap-3">
                            <flux:skeleton class="size-8 rounded-full" />
                            <flux:skeleton.line />
                        </div>
                        <flux:skeleton.line />
                        <flux:skeleton.line />
                    </flux:card>
                @endforeach
            </div>
            {{-- Desktop --}}
            <div class="hidden md:block">
                <flux:card class="space-y-4 bg-zinc-50 shadow-md dark:bg-white/10">
                    @foreach (range(1, 5) as $i)
                        <flux:skeleton.line />
                    @endforeach
                </flux:card>
            </div>
        </flux:skeleton.group>
    </div>

    @if ($mobileViewport === true)
        @php
            $actionColumns = CardLayout::actionColumns($columns);
            $columnsByZone = CardLayout::columnsByZone($columns);
        @endphp

        <div
            wire:key="{{ $keyPrefix }}-mobile"
            x-show="!loading"
            x-cloak
            data-table-viewport-mobile
            class="space-y-3"
        >
            @foreach ($records as $record)
                <x-table.card-item
                    :record="$record"
                    :columns-by-zone="$columnsByZone"
                    :action-columns="$actionColumns"
                />
            @endforeach

            @if ($records->hasPages())
                <flux:pagination :paginator="$records" class="pt-2" />
            @endif
        </div>
    @else
        <div
            wire:key="{{ $keyPrefix }}-desktop"
            x-show="!loading"
            x-cloak
            data-table-viewport-desktop
        >
            <flux:card class="bg-zinc-50 shadow-md dark:bg-white/10">
                <flux:table :paginate="$records">
                    <flux:table.columns>
                        @if ($sortable && $sortableActive)
                            <flux:table.column class="w-8" />
                        @endif

                        @foreach ($columns as $column)
                            <x-table.column-header
                                :column="$column"
                                :sort-by="$sortBy"
                                :sort-direction="$sortDirection"
                            />
                        @endforeach
                    </flux:table.columns>

                    <flux:table.rows :wire:sort="$sortable && $sortableActive ? $sortMethod : null">
                        @foreach ($records as $record)
                            <flux:table.row
                                :key="$record->getKey()"
                                :wire:sort:item="$sortable && $sortableActive ? $record->getKey() : null"
                                @class([
                                    'transition-colors',
                                    'hover:bg-zinc-200/80 dark:hover:bg-white/[0.06]' => $hoverable,
                                    'bg-zinc-200/40 dark:bg-white/[0.03]' => $striped && $loop->even,
                                ])
                            >
                                @if ($sortable && $sortableActive)
                                    @include('components.table.cells.sort-handle')
                                @endif

                                @foreach ($columns as $column)
                                    @include("components.table.cells.{$column->type()}", [
                                        'column' => $column,
                                        'record' => $record,
                                    ])
                                @endforeach
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        </div>
    @endif
</div>

@script
<script>
window.tableViewportSync ??= (syncMethod, lastKnownViewport = null) => ({
    mediaQuery: null,
    onMediaQueryChange: null,
    lastKnownViewport,
    loading: lastKnownViewport === null,

    init() {
        this.mediaQuery = window.matchMedia('(max-width: 47.999rem)');
        this.sync(this.mediaQuery.matches);

        this.onMediaQueryChange = (event) => {
            this.sync(event.matches);
        };

        this.mediaQuery.addEventListener('change', this.onMediaQueryChange);
    },

    destroy() {
        this.mediaQuery?.removeEventListener('change', this.onMediaQueryChange);
    },

    sync(isMobile) {
        if (this.lastKnownViewport === isMobile) {
            return;
        }

        this.loading = true;
        this.lastKnownViewport = isMobile;
        this.$wire.call(syncMethod, isMobile).finally(() => {
            this.loading = false;
        });
    },
});
</script>
@endscript

@script
<script>
window.cardSwipe ??= (recordId, actionCount) => ({
    offsetX: 0,
    snapping: false,
    isOpen: false,
    startX: 0,
    startY: 0,
    tracking: false,
    actionButtonWidth: 56,
    _onSwipeOpened: null,

    get maxOffset() {
        return -(actionCount * this.actionButtonWidth);
    },

    init() {
        this._onSwipeOpened = (e) => {
            if (e.detail !== recordId && this.isOpen) {
                this.snapping = true;
                this.close();
            }
        };

        window.addEventListener('card-swipe-opened', this._onSwipeOpened);
    },

    destroy() {
        window.removeEventListener('card-swipe-opened', this._onSwipeOpened);
    },

    onTouchStart(e) {
        if (e.touches.length !== 1) {
            this.tracking = false;
            return;
        }

        this.snapping = false;
        this.startX = e.touches[0].clientX;
        this.startY = e.touches[0].clientY;
        this.tracking = null;
    },

    onTouchMove(e) {
        if (e.touches.length !== 1) {
            this.tracking = false;
            return;
        }

        const dx = e.touches[0].clientX - this.startX;
        const dy = e.touches[0].clientY - this.startY;

        if (this.tracking === null) {
            this.tracking = Math.abs(dx) >= Math.abs(dy);
        }

        if (!this.tracking) return;

        const base = this.isOpen ? this.maxOffset : 0;
        this.offsetX = Math.min(0, Math.max(this.maxOffset, base + dx));
    },

    onTouchEnd() {
        if (!this.tracking) return;

        const threshold = Math.abs(this.maxOffset) * 0.4;
        this.snapping = true;

        if (this.isOpen) {
            if (this.offsetX > this.maxOffset + threshold) {
                this.close();
            } else {
                this.open();
            }
        } else {
            if (this.offsetX < -threshold) {
                this.open();
            } else {
                this.close();
            }
        }
    },

    open() {
        this.offsetX = this.maxOffset;
        this.isOpen = true;
        window.dispatchEvent(new CustomEvent('card-swipe-opened', { detail: recordId }));
    },

    close() {
        this.offsetX = 0;
        this.isOpen = false;
    },
});
</script>
@endscript
