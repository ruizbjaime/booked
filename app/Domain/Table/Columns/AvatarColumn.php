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

    protected Closure|string|null $colorValue = null;

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

    public function color(Closure|string $color): static
    {
        $this->colorValue = $color;

        return $this;
    }

    public function resolveColor(Model $record): ?string
    {
        if ($this->colorValue instanceof Closure) {
            $result = ($this->colorValue)($record);

            return is_string($result) ? $result : null;
        }

        return $this->colorValue;
    }

    public function hasColor(): bool
    {
        return $this->colorValue !== null;
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

    /**
     * Resolve Tailwind classes for a custom hex-color avatar matching flux:avatar sizing.
     *
     * @return array{container: string, after: string}
     */
    public static function hexAvatarClasses(string $size): array
    {
        $container = match ($size) {
            'xl' => 'size-16 text-base',
            'lg' => 'size-12 text-base',
            'sm' => 'size-8 text-sm',
            'xs' => 'size-6 text-xs',
            default => 'size-10 text-sm',
        };

        $radius = match ($size) {
            'xl' => '[--avatar-radius:var(--radius-xl)]',
            'lg' => '[--avatar-radius:var(--radius-lg)]',
            'sm' => '[--avatar-radius:var(--radius-md)]',
            'xs' => '[--avatar-radius:var(--radius-sm)]',
            default => '[--avatar-radius:var(--radius-lg)]',
        };

        $afterRadius = match ($size) {
            'xl' => 'after:rounded-xl',
            'lg' => 'after:rounded-lg',
            'sm' => 'after:rounded-md',
            'xs' => 'after:rounded-sm',
            default => 'after:rounded-lg',
        };

        return [
            'container' => "relative flex-none isolate flex {$container} items-center justify-center rounded-[var(--avatar-radius)] font-medium {$radius}",
            'after' => "after:absolute after:inset-0 {$afterRadius} after:inset-ring-[1px] after:inset-ring-black/7 dark:after:inset-ring-white/10",
        ];
    }
}
