<?php

namespace App\Domain\Table;

use App\Domain\Table\Columns\ActionsColumn;
use Illuminate\Database\Eloquent\Model;

final class CardLayout
{
    /**
     * @param  list<Column>  $columns
     * @return array{header: list<Column>, body: list<Column>, footer: list<Column>}
     */
    public static function columnsByZone(array $columns): array
    {
        $columnsByZone = [
            CardZone::Header->value => [],
            CardZone::Body->value => [],
            CardZone::Footer->value => [],
        ];

        foreach ($columns as $column) {
            $zone = $column->cardZone();

            if ($zone === CardZone::Hidden) {
                continue;
            }

            $columnsByZone[$zone->value][] = $column;
        }

        return $columnsByZone;
    }

    /**
     * @param  list<Column>  $columns
     * @return list<ActionsColumn>
     */
    public static function actionColumns(array $columns): array
    {
        return array_values(
            array_filter(
                $columns,
                fn (Column $column): bool => $column instanceof ActionsColumn
                    && $column->cardZone() === CardZone::Hidden,
            ),
        );
    }

    /**
     * @param  list<ActionsColumn>  $actionColumns
     * @return list<ActionItem>
     */
    public static function actionItems(array $actionColumns, Model $record): array
    {
        $actionItems = [];

        foreach ($actionColumns as $column) {
            foreach ($column->resolveActions($record) as $action) {
                if ($action->isSeparator()) {
                    continue;
                }

                $actionItems[] = $action;
            }
        }

        return $actionItems;
    }
}
