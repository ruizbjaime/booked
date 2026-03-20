<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Column;
use NumberFormatter;

class MoneyColumn extends Column
{
    protected string $currencyCode = 'USD';

    protected ?string $localeValue = null;

    public function type(): string
    {
        return 'money';
    }

    /**
     * @return ($currency is null ? string : static)
     */
    public function currency(?string $currency = null): static|string
    {
        if ($currency === null) {
            return $this->currencyCode;
        }

        $this->currencyCode = $currency;

        return $this;
    }

    /**
     * @return ($locale is null ? string|null : static)
     */
    public function locale(?string $locale = null): static|string|null
    {
        if (func_num_args() === 0) {
            return $this->localeValue;
        }

        $this->localeValue = $locale;

        return $this;
    }

    public function formatMoney(int|float $value): string
    {
        $formatter = new NumberFormatter(
            $this->localeValue ?? app()->getLocale(),
            NumberFormatter::CURRENCY,
        );

        $result = $formatter->formatCurrency($value, $this->currencyCode);

        return $result !== false ? $result : '';
    }
}
