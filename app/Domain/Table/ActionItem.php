<?php

namespace App\Domain\Table;

use Closure;
use Illuminate\Database\Eloquent\Model;

class ActionItem
{
    private function __construct(
        private ActionItemType $type,
        private string $label = '',
        private string $href = '',
        private ?string $wireClick = null,
        private string $icon = '',
        private string $variant = 'default',
        private bool $wireNavigate = false,
        private Closure|bool $visible = true,
    ) {}

    public static function link(string $label, string $href, string $icon, bool $wireNavigate = false): self
    {
        return new self(type: ActionItemType::Link, label: $label, href: $href, icon: $icon, wireNavigate: $wireNavigate);
    }

    public static function button(string $label, string $wireClick, string $icon, string $variant = 'default'): self
    {
        return new self(type: ActionItemType::Button, label: $label, wireClick: $wireClick, icon: $icon, variant: $variant);
    }

    public static function separator(): self
    {
        return new self(type: ActionItemType::Separator);
    }

    public function visible(Closure|bool $condition): self
    {
        $this->visible = $condition;

        return $this;
    }

    public function isLink(): bool
    {
        return $this->type === ActionItemType::Link;
    }

    public function isButton(): bool
    {
        return $this->type === ActionItemType::Button;
    }

    public function isSeparator(): bool
    {
        return $this->type === ActionItemType::Separator;
    }

    public function isVisible(Model $record): bool
    {
        if ($this->visible instanceof Closure) {
            return (bool) ($this->visible)($record);
        }

        return $this->visible;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function href(): string
    {
        return $this->href;
    }

    public function wireClick(): ?string
    {
        return $this->wireClick;
    }

    public function icon(): string
    {
        return $this->icon;
    }

    public function variant(): string
    {
        return $this->variant;
    }

    public function shouldWireNavigate(): bool
    {
        return $this->wireNavigate;
    }
}
