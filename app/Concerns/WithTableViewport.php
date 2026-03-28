<?php

namespace App\Concerns;

trait WithTableViewport
{
    public ?bool $tableIsMobileViewport = null;

    public function mountWithTableViewport(): void
    {
        $value = session('tableIsMobileViewport');
        $this->tableIsMobileViewport = is_bool($value) ? $value : null;
    }

    public function syncTableViewport(bool $isMobile): void
    {
        if ($this->tableIsMobileViewport === $isMobile) {
            return;
        }

        $this->tableIsMobileViewport = $isMobile;
        session(['tableIsMobileViewport' => $isMobile]);
    }

    public function tableMobileViewport(): ?bool
    {
        return $this->tableIsMobileViewport;
    }
}
