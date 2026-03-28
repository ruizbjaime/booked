<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Livewire\Attributes\Url;

trait WithTableSearch
{
    protected const LIKE_ESCAPE_CHARACTER = '!';

    #[Url(as: 'search', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
        $this->resetPage();
    }

    /**
     * @return list<string>
     */
    abstract protected function searchableFields(): array;

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applySearch(Builder $query): Builder
    {
        $search = trim($this->search);

        if ($search === '') {
            return $query;
        }

        $fields = $this->searchableFields();

        if ($fields === []) {
            return $query;
        }

        $term = $this->escapedLikeTerm($search);

        return $query->where(function (Builder $q) use ($fields, $term): void {
            foreach ($fields as $field) {
                if (str_contains($field, '.')) {
                    [$relation, $column] = explode('.', $field, 2);

                    $q->orWhereHas($relation, function (Builder $relationQuery) use ($column, $term): void {
                        $this->applyLikeConstraint($relationQuery, $column, $term, useOr: false);
                    });

                    continue;
                }

                $this->applyLikeConstraint($q, $field, $term);
            }
        });
    }

    protected function escapedLikeTerm(string $value): string
    {
        $escaped = str_replace(
            [self::LIKE_ESCAPE_CHARACTER, '%', '_'],
            [self::LIKE_ESCAPE_CHARACTER.self::LIKE_ESCAPE_CHARACTER, self::LIKE_ESCAPE_CHARACTER.'%', self::LIKE_ESCAPE_CHARACTER.'_'],
            $value,
        );

        return "%{$escaped}%";
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    protected function applyLikeConstraint(Builder $query, string $field, string $term, bool $useOr = true): void
    {
        $grammar = $query->getQuery()->grammar;
        $wrappedColumn = $grammar->wrap($query->qualifyColumn($field));

        $operator = $grammar instanceof PostgresGrammar ? 'ILIKE' : 'LIKE';
        $sql = "{$wrappedColumn} {$operator} ? ESCAPE '".self::LIKE_ESCAPE_CHARACTER."'";

        if ($useOr) {
            $query->orWhereRaw($sql, [$term]);

            return;
        }

        $query->whereRaw($sql, [$term]);
    }
}
