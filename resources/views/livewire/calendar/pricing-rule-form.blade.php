@use(App\Domain\Calendar\Enums\PricingRuleType)

<form wire:submit="save" autocomplete="off" class="space-y-5">
    <flux:tab.group>
        <flux:tabs variant="segmented" size="sm">
            <flux:tab name="basics" icon="cog-6-tooth">{{ __('calendar.settings.rule_form.tabs.basics') }}</flux:tab>
            <flux:tab name="conditions" icon="funnel">{{ __('calendar.settings.rule_form.tabs.conditions') }}</flux:tab>
            <flux:tab name="preview" icon="chart-bar-square">{{ __('calendar.settings.rule_form.tabs.preview') }}</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="basics" class="space-y-5">
            <div class="grid items-start gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label class="inline-flex items-center gap-1.5">
                        <flux:icon.tag class="size-4 text-amber-500 dark:text-amber-300" />
                        {{ __('calendar.settings.fields.name') }}
                    </flux:label>
                    <flux:input wire:model.live.blur="name" name="name" id="pricing-rule-name" required />
                    <flux:description>{{ __('calendar.settings.rule_form.fields.name_help') }}</flux:description>
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label class="inline-flex items-center gap-1.5">
                        <flux:icon.adjustments-horizontal class="size-4 text-violet-500 dark:text-violet-300" />
                        {{ __('calendar.settings.fields.rule_type') }}
                    </flux:label>
                    <flux:select wire:model.live="rule_type" variant="listbox" name="rule_type" id="pricing-rule-type">
                        @foreach ($this->ruleTypeOptions as $option)
                            <flux:select.option :value="$option['value']" wire:key="rule-type-{{ $option['value'] }}">
                                {{ $option['label'] }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="rule_type" />
                </flux:field>
            </div>

            <div class="grid items-start gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label class="inline-flex items-center gap-1.5">
                        <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                        {{ __('calendar.settings.fields.en_description') }}
                    </flux:label>
                    <flux:textarea wire:model.live.blur="en_description" name="en_description" id="pricing-rule-en-description" rows="3" />
                    <flux:error name="en_description" />
                </flux:field>

                <flux:field>
                    <flux:label class="inline-flex items-center gap-1.5">
                        <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-300" />
                        {{ __('calendar.settings.fields.es_description') }}
                    </flux:label>
                    <flux:textarea wire:model.live.blur="es_description" name="es_description" id="pricing-rule-es-description" rows="3" />
                    <flux:error name="es_description" />
                </flux:field>
            </div>

            <div class="grid items-start gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label class="inline-flex items-center gap-1.5">
                        <flux:icon.currency-dollar class="size-4 text-sky-500 dark:text-sky-300" />
                        {{ __('calendar.settings.fields.pricing_category') }}
                    </flux:label>
                    <flux:select wire:model.live="pricing_category_id" variant="listbox" name="pricing_category_id" id="pricing-rule-category">
                        @foreach ($this->availablePricingCategories as $category)
                            <flux:select.option :value="$category['id']" wire:key="pricing-category-{{ $category['id'] }}">
                                {{ $category['label'] }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="pricing_category_id" />
                </flux:field>

                <flux:field>
                    <flux:label class="inline-flex items-center gap-1.5">
                        <flux:icon.arrows-up-down class="size-4 text-violet-500 dark:text-violet-300" />
                        {{ __('calendar.settings.fields.priority') }}
                    </flux:label>
                    <flux:input wire:model.live.blur="priority" type="number" min="0" max="9999" name="priority" id="pricing-rule-priority" required />
                    <flux:description>{{ __('calendar.settings.rule_form.fields.priority_help') }}</flux:description>
                    <flux:error name="priority" />
                </flux:field>
            </div>

            <div class="rounded-2xl border border-white/8 bg-white/3 px-4 py-3.5 shadow-sm ring-1 ring-inset ring-white/4">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                            <flux:icon.power class="size-4 text-emerald-600 dark:text-emerald-300" />
                            {{ __('calendar.settings.fields.is_active') }}
                        </flux:heading>
                        <flux:text size="sm" class="max-w-xl text-zinc-500 dark:text-white/60">
                            {{ __('calendar.settings.rule_form.active_help') }}
                        </flux:text>
                    </div>

                    <flux:switch
                        wire:model.live="is_active"
                        name="is_active"
                        id="pricing-rule-active"
                        :aria-label="__('calendar.settings.fields.is_active')"
                    />
                </div>

                <div class="mt-3 flex items-center gap-2">
                    <span class="inline-flex size-2.5 rounded-full {{ $is_active ? 'bg-emerald-400 shadow-[0_0_0_4px_rgb(52_211_153_/_0.12)]' : 'bg-zinc-500/80 shadow-[0_0_0_4px_rgb(113_113_122_/_0.12)]' }}"></span>
                    <flux:text size="sm" class="font-medium text-zinc-200 dark:text-zinc-100">
                        {{ $is_active ? __('calendar.settings.rule_form.active_enabled') : __('calendar.settings.rule_form.active_disabled') }}
                    </flux:text>
                </div>
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="conditions" class="space-y-5">
            @if ($rule_type === PricingRuleType::SeasonDays->value)
                <div class="grid items-start gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label class="inline-flex items-center gap-1.5">
                            <flux:icon.adjustments-horizontal class="size-4 text-violet-500 dark:text-violet-300" />
                            {{ __('calendar.settings.rule_form.fields.season_mode') }}
                        </flux:label>
                        <flux:select wire:model.live="season_mode" variant="listbox" name="season_mode" id="pricing-rule-season-mode">
                            <flux:select.option value="season">{{ __('calendar.settings.rule_form.season_modes.season') }}</flux:select.option>
                            <flux:select.option value="dates">{{ __('calendar.settings.rule_form.season_modes.dates') }}</flux:select.option>
                        </flux:select>
                        <flux:error name="season_mode" />
                    </flux:field>
                </div>

                @if ($season_mode === 'season')
                    <div class="grid items-start gap-4 md:grid-cols-2">
                        <flux:field>
                            <flux:label class="inline-flex items-center gap-1.5">
                                <flux:icon.sun class="size-4 text-amber-500 dark:text-amber-300" />
                                {{ __('calendar.settings.rule_form.fields.season') }}
                            </flux:label>
                            <flux:select wire:model.live="season" variant="listbox" name="season" id="pricing-rule-season">
                                <flux:select.option value="">{{ __('actions.select') }}</flux:select.option>
                                @foreach ($this->availableSeasonBlocks as $seasonOption)
                                    <flux:select.option :value="$seasonOption['value']" wire:key="season-{{ $seasonOption['value'] }}">
                                        {{ $seasonOption['label'] }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="season" />
                        </flux:field>

                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <flux:field>
                                <flux:label class="inline-flex items-center gap-1.5">
                                    <flux:icon.funnel class="size-4 text-violet-500 dark:text-violet-300" />
                                    {{ __('calendar.settings.rule_form.fields.only_last_n_days') }}
                                </flux:label>
                                <flux:input wire:model.live.blur="only_last_n_days" type="number" min="1" max="31" />
                                <flux:error name="only_last_n_days" />
                            </flux:field>

                            <flux:field>
                                <flux:label class="inline-flex items-center gap-1.5">
                                    <flux:icon.funnel class="size-4 text-violet-500 dark:text-violet-300" />
                                    {{ __('calendar.settings.rule_form.fields.exclude_last_n_days') }}
                                </flux:label>
                                <flux:input wire:model.live.blur="exclude_last_n_days" type="number" min="1" max="31" />
                                <flux:error name="exclude_last_n_days" />
                            </flux:field>
                        </div>
                    </div>

                    <flux:field>
                        <flux:label class="inline-flex items-center gap-1.5">
                            <flux:icon.calendar-days class="size-4 text-sky-500 dark:text-sky-300" />
                            {{ __('calendar.settings.rule_form.fields.day_of_week') }}
                        </flux:label>
                        <flux:description>{{ __('calendar.settings.rule_form.fields.day_of_week_help') }}</flux:description>
                        <flux:checkbox.group wire:model.live="day_of_week" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($this->dayOptions as $dayOption)
                                <div wire:key="season-day-{{ $dayOption['value'] }}" class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-white/8 dark:bg-white/3">
                                    <flux:checkbox :value="$dayOption['value']" :label="$dayOption['label']" />
                                </div>
                            @endforeach
                        </flux:checkbox.group>
                        <flux:error name="day_of_week" />
                    </flux:field>
                @else
                    <div class="grid items-start gap-4 md:grid-cols-[1fr_auto_auto]">
                        <flux:field>
                            <flux:label class="inline-flex items-center gap-1.5">
                                <flux:icon.arrow-path class="size-4 text-sky-500 dark:text-sky-300" />
                                {{ __('calendar.settings.rule_form.fields.recurring_dates') }}
                            </flux:label>
                            <flux:description>{{ __('calendar.settings.rule_form.fields.recurring_dates_help') }}</flux:description>
                            <div class="flex flex-wrap gap-2 rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-white/8 dark:bg-white/3">
                                @forelse ($recurring_dates as $date)
                                    <flux:badge wire:key="recurring-date-{{ $date }}" size="sm" color="sky">
                                        <span>{{ $date }}</span>
                                        <button type="button" wire:click="removeRecurringDate('{{ $date }}')" class="ml-2 text-xs">
                                            ×
                                        </button>
                                    </flux:badge>
                                @empty
                                    <flux:text size="sm" class="text-zinc-500">{{ __('calendar.settings.rule_form.empty_recurring_dates') }}</flux:text>
                                @endforelse
                            </div>
                            <flux:error name="recurring_dates" />
                        </flux:field>

                        <flux:field>
                            <flux:label class="inline-flex items-center gap-1.5">
                                <flux:icon.calendar class="size-4 text-sky-500 dark:text-sky-300" />
                                {{ __('calendar.settings.rule_form.fields.month') }}
                            </flux:label>
                            <flux:select wire:model.live="recurring_month" variant="listbox">
                                <flux:select.option value="">{{ __('actions.select') }}</flux:select.option>
                                @foreach (range(1, 12) as $month)
                                    <flux:select.option :value="$month" wire:key="recurring-month-{{ $month }}">
                                        {{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="recurring_month" />
                        </flux:field>

                        <flux:field>
                            <flux:label class="inline-flex items-center gap-1.5">
                                <flux:icon.calendar class="size-4 text-sky-500 dark:text-sky-300" />
                                {{ __('calendar.settings.rule_form.fields.day') }}
                            </flux:label>
                            <flux:select wire:model.live="recurring_day" variant="listbox">
                                <flux:select.option value="">{{ __('actions.select') }}</flux:select.option>
                                @foreach (range(1, 31) as $day)
                                    <flux:select.option :value="$day" wire:key="recurring-day-{{ $day }}">
                                        {{ str_pad((string) $day, 2, '0', STR_PAD_LEFT) }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="recurring_day" />
                        </flux:field>
                    </div>

                    <div class="flex justify-end">
                        <flux:button type="button" variant="ghost" icon="plus" wire:click="addRecurringDate">
                            {{ __('calendar.settings.rule_form.actions.add_date') }}
                        </flux:button>
                    </div>
                @endif
            @elseif ($rule_type === PricingRuleType::HolidayBridge->value)
                <div class="grid items-start gap-4 md:grid-cols-2">
                    <flux:switch wire:model.live="is_bridge_weekend" :label="__('calendar.settings.rule_form.fields.is_bridge_weekend')" />
                    <flux:switch wire:model.live="is_first_bridge_day" :label="__('calendar.settings.rule_form.fields.is_first_bridge_day')" />
                </div>

                <flux:field>
                    <flux:label class="inline-flex items-center gap-1.5">
                        <flux:icon.calendar-days class="size-4 text-sky-500 dark:text-sky-300" />
                        {{ __('calendar.settings.rule_form.fields.day_of_week') }}
                    </flux:label>
                    <flux:checkbox.group wire:model.live="day_of_week" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($this->dayOptions as $dayOption)
                            <div wire:key="bridge-day-{{ $dayOption['value'] }}" class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-white/8 dark:bg-white/3">
                                <flux:checkbox :value="$dayOption['value']" :label="$dayOption['label']" />
                            </div>
                        @endforeach
                    </flux:checkbox.group>
                    <flux:error name="day_of_week" />
                </flux:field>
            @elseif ($rule_type === PricingRuleType::NormalWeekend->value)
                <flux:field>
                    <flux:label class="inline-flex items-center gap-1.5">
                        <flux:icon.calendar-days class="size-4 text-sky-500 dark:text-sky-300" />
                        {{ __('calendar.settings.rule_form.fields.day_of_week') }}
                    </flux:label>
                    <flux:checkbox.group wire:model.live="day_of_week" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($this->dayOptions as $dayOption)
                            <div wire:key="weekend-day-{{ $dayOption['value'] }}" class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-white/8 dark:bg-white/3">
                                <flux:checkbox :value="$dayOption['value']" :label="$dayOption['label']" />
                            </div>
                        @endforeach
                    </flux:checkbox.group>
                    <flux:error name="day_of_week" />
                </flux:field>

                <div class="grid items-start gap-4 md:grid-cols-2">
                    <flux:switch wire:model.live="outside_season" :label="__('calendar.settings.rule_form.fields.outside_season')" />
                    <flux:switch wire:model.live="not_bridge" :label="__('calendar.settings.rule_form.fields.not_bridge')" />
                </div>
            @else
                <flux:callout icon="information-circle" color="amber">
                    <flux:callout.heading>{{ __('calendar.settings.rule_form.fallback_title') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('calendar.settings.rule_form.fallback_description') }}</flux:callout.text>
                </flux:callout>
            @endif
        </flux:tab.panel>

        <flux:tab.panel name="preview" class="space-y-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                        <flux:icon.sparkles class="size-4 text-violet-500 dark:text-violet-300" />
                        {{ __('calendar.settings.preview.title') }}
                    </flux:heading>
                    <flux:subheading>{{ __('calendar.settings.preview.description') }}</flux:subheading>
                </div>

                <flux:button type="button" variant="primary" icon="sparkles" wire:click="runPreview">
                    {{ __('calendar.settings.preview.run') }}
                </flux:button>
            </div>

            @if (($preview['warnings'] ?? []) !== [])
                <div class="space-y-3">
                    @foreach ($preview['warnings'] as $warning)
                        <flux:callout wire:key="preview-warning-{{ md5($warning) }}" icon="exclamation-triangle" variant="warning" inline>
                            <flux:callout.text>{{ $warning }}</flux:callout.text>
                        </flux:callout>
                    @endforeach
                </div>
            @endif

            <div class="grid items-start gap-4 md:grid-cols-2">
                <flux:card class="space-y-2">
                    <flux:text size="sm" class="inline-flex items-center gap-1.5 text-zinc-500">
                        <flux:icon.moon class="size-4 text-sky-500 dark:text-sky-300" />
                        {{ __('calendar.settings.preview.affected_nights') }}
                    </flux:text>
                    <flux:heading size="xl">{{ $preview['affectedCount'] ?? 0 }}</flux:heading>
                </flux:card>

                <flux:card class="space-y-2">
                    <flux:text size="sm" class="inline-flex items-center gap-1.5 text-zinc-500">
                        <flux:icon.calendar-days class="size-4 text-violet-500 dark:text-violet-300" />
                        {{ __('calendar.settings.preview.range') }}
                    </flux:text>
                    <flux:text class="font-medium">{{ $this->previewRangeFrom }} → {{ $this->previewRangeTo }}</flux:text>
                </flux:card>
            </div>

            <flux:card class="space-y-4">
                <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                    <flux:icon.arrow-path class="size-4 text-amber-500 dark:text-amber-300" />
                    {{ __('calendar.settings.preview.transitions') }}
                </flux:heading>
                <flux:separator variant="subtle" />

                @if (($preview['changesByCategory'] ?? []) === [])
                    <flux:text class="text-zinc-500">{{ __('calendar.settings.preview.no_transitions') }}</flux:text>
                @else
                    <div class="space-y-2">
                        @foreach ($preview['changesByCategory'] as $transition => $count)
                            <div wire:key="transition-{{ md5($transition) }}" class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-white/8 dark:bg-white/3">
                                <flux:text>{{ $transition }}</flux:text>
                                <flux:badge size="sm" color="sky">{{ $count }}</flux:badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="sm" class="inline-flex items-center gap-1.5">
                    <flux:icon.table-cells class="size-4 text-emerald-600 dark:text-emerald-300" />
                    {{ __('calendar.settings.preview.sample_dates') }}
                </flux:heading>
                <flux:separator variant="subtle" />

                @if (($preview['sampleDates'] ?? []) === [])
                    <flux:text class="text-zinc-500">{{ __('calendar.settings.preview.no_sample_dates') }}</flux:text>
                @else
                    <div class="space-y-2">
                        @foreach ($preview['sampleDates'] as $sample)
                            <div wire:key="sample-{{ $sample['date'] }}" class="grid items-start gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 md:grid-cols-[8rem_1fr_1fr] dark:border-white/8 dark:bg-white/3">
                                <flux:text class="font-medium">{{ $sample['date'] }}</flux:text>
                                <flux:text>{{ $sample['fromCategory'] }}</flux:text>
                                <flux:text>{{ $sample['toCategory'] }}</flux:text>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>
        </flux:tab.panel>
    </flux:tab.group>

    <flux:separator variant="subtle" />

    <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:items-center sm:justify-end">
        <flux:spacer class="hidden sm:block" />

        <flux:modal.close>
            <flux:button variant="ghost" size="sm" type="button" wire:click="$dispatch('close-form-modal')" class="modal-service-action w-full sm:w-auto">
                {{ __('actions.cancel') }}
            </flux:button>
        </flux:modal.close>

        <flux:button variant="primary" size="sm" type="submit" class="modal-service-action w-full sm:w-auto">
            {{ __('calendar.settings.rule_form.submit') }}
        </flux:button>
    </div>
</form>
