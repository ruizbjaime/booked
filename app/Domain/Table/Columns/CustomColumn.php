<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Column;

class CustomColumn extends Column
{
    protected string $viewName = '';

    public function type(): string
    {
        return 'custom';
    }

    /**
     * @return ($view is null ? string : static)
     */
    public function view(?string $view = null): static|string
    {
        if ($view === null) {
            return $this->viewName;
        }

        $this->viewName = $view;

        return $this;
    }
}
