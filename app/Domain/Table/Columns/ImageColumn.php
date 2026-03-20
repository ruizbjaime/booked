<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Column;
use Closure;
use Illuminate\Database\Eloquent\Model;

class ImageColumn extends Column
{
    protected ?Closure $srcCallback = null;

    protected Closure|string $altValue = '';

    protected int $widthValue = 40;

    protected int $heightValue = 40;

    protected bool $roundedEnabled = false;

    public function type(): string
    {
        return 'image';
    }

    public function src(Closure $callback): static
    {
        $this->srcCallback = $callback;

        return $this;
    }

    public function resolveSrc(Model $record): ?string
    {
        if ($this->srcCallback === null) {
            return null;
        }

        $result = ($this->srcCallback)($record);

        return is_string($result) ? $result : null;
    }

    public function alt(Closure|string $alt): static
    {
        $this->altValue = $alt;

        return $this;
    }

    public function resolveAlt(Model $record): string
    {
        if ($this->altValue instanceof Closure) {
            $result = ($this->altValue)($record);

            return is_string($result) ? $result : '';
        }

        return $this->altValue;
    }

    /**
     * @return ($width is null ? int : static)
     */
    public function width(?int $width = null): static|int
    {
        if ($width === null) {
            return $this->widthValue;
        }

        $this->widthValue = $width;

        return $this;
    }

    /**
     * @return ($height is null ? int : static)
     */
    public function height(?int $height = null): static|int
    {
        if ($height === null) {
            return $this->heightValue;
        }

        $this->heightValue = $height;

        return $this;
    }

    public function rounded(bool $rounded = true): static
    {
        $this->roundedEnabled = $rounded;

        return $this;
    }

    public function isRounded(): bool
    {
        return $this->roundedEnabled;
    }
}
