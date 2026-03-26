<section class="container mx-auto space-y-6">
    <x-heading :heading="__('calendar.settings.title')" :subheading="__('calendar.settings.description')" />

    @if ($this->canRegenerateCalendar)
        {{-- Regenerate Button --}}
        <div class="flex justify-end">
            <flux:button variant="primary" icon="arrow-path" wire:click="confirmRegenerate">
                {{ __('calendar.settings.regenerate.button') }}
            </flux:button>
        </div>
    @endif

    @if ($this->canViewHolidays)
        {{-- Holiday Definitions --}}
        <flux:card class="space-y-6">
            <div class="flex items-start gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-red-500/15 text-red-300">
                    <flux:icon.calendar class="size-5" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('calendar.settings.sections.holidays') }}</flux:heading>
                    <flux:subheading>{{ __('calendar.settings.sections.holidays_description') }}</flux:subheading>
                </div>
            </div>

            <flux:separator variant="subtle" />

            <x-table.data-table
                :columns="$this->holidayColumns"
                :records="$this->holidays"
                :simple="true"
                key-prefix="holidays-table"
            />
        </flux:card>
    @endif

    @if ($this->canViewSeasonBlocks)
        {{-- Season Blocks --}}
        <flux:card class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                        <flux:icon.sun class="size-5" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('calendar.settings.sections.seasons') }}</flux:heading>
                        <flux:subheading>{{ __('calendar.settings.sections.seasons_description') }}</flux:subheading>
                    </div>
                </div>

                @if ($this->canCreateSeasonBlocks)
                    <div class="flex justify-end">
                        <flux:button variant="primary" icon="plus" wire:click="openCreateSeasonBlockModal">
                            {{ __('calendar.settings.season_block_form.create_action') }}
                        </flux:button>
                    </div>
                @endif
            </div>

            <flux:separator variant="subtle" />

            <x-table.data-table
                :columns="$this->seasonBlockColumns"
                :records="$this->seasonBlocks"
                :simple="true"
                key-prefix="season-blocks-table"
            />
        </flux:card>
    @endif

    @if ($this->canViewPricingCategories)
        {{-- Pricing Categories --}}
        <flux:card class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-500/15 text-sky-300">
                        <flux:icon.tag class="size-5" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('calendar.settings.sections.categories') }}</flux:heading>
                        <flux:subheading>{{ __('calendar.settings.sections.categories_description') }}</flux:subheading>
                    </div>
                </div>

                @if ($this->canCreatePricingCategories)
                    <div class="flex justify-end">
                        <flux:button variant="primary" icon="plus" wire:click="openCreatePricingCategoryModal">
                            {{ __('calendar.settings.pricing_category_form.create_action') }}
                        </flux:button>
                    </div>
                @endif
            </div>

            <flux:separator variant="subtle" />

            <x-table.data-table
                :columns="$this->pricingCategoryColumns"
                :records="$this->pricingCategories"
                :simple="true"
                key-prefix="pricing-categories-table"
            />
        </flux:card>
    @endif

    @if ($this->canViewPricingRules)
        {{-- Pricing Rules --}}
        <flux:card class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                        <flux:icon.adjustments-horizontal class="size-5" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('calendar.settings.sections.rules') }}</flux:heading>
                        <flux:subheading>{{ __('calendar.settings.sections.rules_description') }}</flux:subheading>
                    </div>
                </div>

                @if ($this->canCreatePricingRules)
                    <div class="flex justify-end">
                        <flux:button variant="primary" icon="plus" wire:click="openCreatePricingRuleModal">
                            {{ __('calendar.settings.rule_form.create_action') }}
                        </flux:button>
                    </div>
                @endif
            </div>

            @if ($this->isCalendarStale)
                <flux:callout icon="arrow-path" color="amber">
                    <flux:callout.heading>{{ __('calendar.settings.stale.title') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('calendar.settings.stale.description') }}</flux:callout.text>
                </flux:callout>
            @endif

            <flux:separator variant="subtle" />

            <x-table.data-table
                :columns="$this->pricingRuleColumns"
                :records="$this->pricingRules"
                :sortable="true"
                :sortable-active="$this->canSortPricingRules"
                sort-method="reorderPricingRules"
                :simple="true"
                key-prefix="pricing-rules-table"
            />
        </flux:card>
    @endif
</section>
