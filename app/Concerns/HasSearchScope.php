<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Grammars\PostgresGrammar;

trait HasSearchScope
{
    private const string LIKE_ESCAPE_CHARACTER = '!';

    protected static function escapeLikeTerm(string $value): string
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
    protected static function applyLikeSearch(Builder $query, string $field, string $term, bool $useOr = true): void
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
