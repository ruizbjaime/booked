<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\ActionItem;
use App\Domain\Table\CardZone;
use App\Domain\Table\Column;
use Closure;
use Illuminate\Database\Eloquent\Model;

class ActionsColumn extends Column
{
    protected CardZone $cardZoneValue = CardZone::Hidden;

    /** @var (Closure(mixed): list<ActionItem>)|null */
    protected ?Closure $actionsCallback = null;

    protected string $dropdownPositionValue = 'bottom';

    protected string $dropdownAlignValue = 'end';

    protected string $align = 'end';

    public function type(): string
    {
        return 'actions';
    }

    public function actions(Closure $callback): static
    {
        $this->actionsCallback = $callback;

        return $this;
    }

    /**
     * @return list<ActionItem>
     */
    public function resolveActions(Model $record): array
    {
        if ($this->actionsCallback === null) {
            return [];
        }

        $actions = array_values(
            array_filter(
                ($this->actionsCallback)($record),
                fn (ActionItem $action) => $action->isVisible($record),
            )
        );

        return $this->normalizeSeparators($actions);
    }

    /**
     * @return ($position is null ? string : static)
     */
    public function dropdownPosition(?string $position = null): static|string
    {
        if ($position === null) {
            return $this->dropdownPositionValue;
        }

        $this->dropdownPositionValue = $position;

        return $this;
    }

    /**
     * @return ($align is null ? string : static)
     */
    public function dropdownAlign(?string $align = null): static|string
    {
        if ($align === null) {
            return $this->dropdownAlignValue;
        }

        $this->dropdownAlignValue = $align;

        return $this;
    }

    /**
     * @param  list<ActionItem>  $actions
     * @return list<ActionItem>
     */
    private function normalizeSeparators(array $actions): array
    {
        $normalizedActions = [];

        foreach ($actions as $action) {
            if ($action->isSeparator() && ($normalizedActions === [] || end($normalizedActions)->isSeparator())) {
                continue;
            }

            $normalizedActions[] = $action;
        }

        if ($normalizedActions !== [] && end($normalizedActions)->isSeparator()) {
            array_pop($normalizedActions);
        }

        return $normalizedActions;
    }
}
