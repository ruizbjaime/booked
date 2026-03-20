<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\CardZone;
use App\Domain\Table\Column;
use Closure;
use Illuminate\Database\Eloquent\Model;

class AvatarColumn extends Column
{
    protected CardZone $cardZoneValue = CardZone::Header;

    protected ?Closure $avatarSrcCallback = null;

    protected ?Closure $initialsCallback = null;

    protected ?Closure $colorSeedCallback = null;

    protected ?Closure $recordUrlCallback = null;

    protected bool $wireNavigateEnabled = false;

    public function type(): string
    {
        return 'avatar';
    }

    public function avatarSrc(Closure $callback): static
    {
        $this->avatarSrcCallback = $callback;

        return $this;
    }

    public function resolveAvatarSrc(Model $record): ?string
    {
        if ($this->avatarSrcCallback === null) {
            return null;
        }

        $result = ($this->avatarSrcCallback)($record);

        return is_string($result) ? $result : null;
    }

    public function initials(Closure $callback): static
    {
        $this->initialsCallback = $callback;

        return $this;
    }

    public function resolveInitials(Model $record): ?string
    {
        if ($this->initialsCallback === null) {
            return null;
        }

        $result = ($this->initialsCallback)($record);

        return is_string($result) ? $result : null;
    }

    public function colorSeed(Closure $callback): static
    {
        $this->colorSeedCallback = $callback;

        return $this;
    }

    public function resolveColorSeed(Model $record): mixed
    {
        return $this->colorSeedCallback ? ($this->colorSeedCallback)($record) : null;
    }

    public function recordUrl(Closure $callback): static
    {
        $this->recordUrlCallback = $callback;

        return $this;
    }

    public function resolveRecordUrl(Model $record): ?string
    {
        if ($this->recordUrlCallback === null) {
            return null;
        }

        $result = ($this->recordUrlCallback)($record);

        return is_string($result) ? $result : null;
    }

    public function hasRecordUrl(): bool
    {
        return $this->recordUrlCallback !== null;
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
}
