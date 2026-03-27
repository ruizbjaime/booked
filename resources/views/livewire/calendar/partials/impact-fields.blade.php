<div class="grid items-start gap-4 md:grid-cols-2">
    <flux:field>
        <flux:label>{{ __('calendar.settings.rule_form.fields.min_impact') }}</flux:label>
        <flux:input wire:model.live.blur="min_impact" type="number" min="0" max="10" step="0.5" />
        <flux:description>{{ __('calendar.settings.rule_form.fields.min_impact_help') }}</flux:description>
        <flux:error name="min_impact" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('calendar.settings.rule_form.fields.max_impact') }}</flux:label>
        <flux:input wire:model.live.blur="max_impact" type="number" min="0" max="10" step="0.5" />
        <flux:description>{{ __('calendar.settings.rule_form.fields.max_impact_help') }}</flux:description>
        <flux:error name="max_impact" />
    </flux:field>
</div>
