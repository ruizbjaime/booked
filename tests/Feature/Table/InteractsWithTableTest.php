<?php

use App\Concerns\InteractsWithTable;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\Filter;

it('counts active table filters across scalar and array values', function () {
    $component = new class
    {
        use InteractsWithTable;

        public string $status = 'active';

        /** @var list<string> */
        public array $roles = ['admin', 'user'];

        protected function columns(): array
        {
            return [TextColumn::make('name')];
        }

        protected function searchableFields(): array
        {
            return [];
        }

        protected function defaultSortBy(): string
        {
            return 'name';
        }

        protected function defaultSortDirection(): string
        {
            return 'asc';
        }

        protected function filters(): array
        {
            return [
                new class('status') extends Filter
                {
                    public function type(): string
                    {
                        return 'select';
                    }

                    public function countActive(mixed $value): int
                    {
                        return filled($value) ? 1 : 0;
                    }
                },
                new class('roles') extends Filter
                {
                    public function type(): string
                    {
                        return 'multi-select';
                    }

                    public function countActive(mixed $value): int
                    {
                        return is_array($value) ? count($value) : 0;
                    }
                },
            ];
        }
    };

    expect($component->tableActiveFilterCount())->toBe(3);
});
