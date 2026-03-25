<?php

namespace App\Domain\Calendar\Contracts;

interface PricingRuleConditionSchema
{
    /**
     * @return array<string, mixed>
     */
    public function fields(): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function rules(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalize(array $input): array;

    /**
     * @param  array<string, mixed>  $conditions
     */
    public function summary(array $conditions): string;
}
