<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Column;
use Closure;
use Illuminate\Database\Eloquent\Model;

class LinkColumn extends Column
{
    protected ?Closure $hrefCallback = null;

    protected bool $wireNavigateEnabled = false;

    protected ?string $targetValue = null;

    protected ?string $linkClassValue = null;

    public function type(): string
    {
        return 'link';
    }

    public function href(Closure $callback): static
    {
        $this->hrefCallback = $callback;

        return $this;
    }

    public function resolveHref(Model $record): ?string
    {
        if ($this->hrefCallback === null) {
            return null;
        }

        $result = ($this->hrefCallback)($record);

        return is_string($result) ? $result : null;
    }

    public function wireNavigate(bool $enabled = true): static
    {
        $this->wireNavigateEnabled = $enabled;

        return $this;
    }

    public function shouldWireNavigate(): bool
    {
        return $this->wireNavigateEnabled;
    }

    /**
     * @return ($target is null ? string|null : static)
     */
    public function target(?string $target = null): static|string|null
    {
        if (func_num_args() === 0) {
            return $this->targetValue;
        }

        $this->targetValue = $target;

        return $this;
    }

    /**
     * @return ($class is null ? string|null : static)
     */
    public function linkClass(?string $class = null): static|string|null
    {
        if (func_num_args() === 0) {
            return $this->linkClassValue;
        }

        $this->linkClassValue = $class;

        return $this;
    }
}
