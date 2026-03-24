<?php

namespace App\Console\Commands;

use App\Actions\Calendar\GenerateCalendarDays;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'calendar:generate')]
class GenerateCalendarCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'calendar:generate
        {year? : Full year to generate (e.g. 2026)}
        {--from= : Start date (Y-m-d)}
        {--to= : End date (Y-m-d)}
        {--force : Regenerate existing days}';

    /**
     * @var string
     */
    protected $description = 'Generate or regenerate calendar day analysis for pricing';

    public function handle(GenerateCalendarDays $action): int
    {
        [$from, $to] = $this->resolveDateRange();

        if ($from === null || $to === null) {
            return self::FAILURE;
        }

        $this->info("Generating calendar days from {$from->toDateString()} to {$to->toDateString()}...");

        $bar = $this->output->createProgressBar((int) $from->diffInDays($to) + 1);
        $bar->start();

        $count = $action->handle($from, $to, function (int $processed, int $total) use ($bar): void {
            $bar->setProgress($processed);
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Generated {$count} calendar days.");

        return self::SUCCESS;
    }

    /**
     * @return array{CarbonImmutable|null, CarbonImmutable|null}
     */
    private function resolveDateRange(): array
    {
        $fromOption = $this->option('from');
        $toOption = $this->option('to');

        if (is_string($fromOption) && is_string($toOption)) {
            $from = CarbonImmutable::parse($fromOption);
            $to = CarbonImmutable::parse($toOption);

            if ($from->greaterThan($to)) {
                $this->error('--from must be before --to.');

                return [null, null];
            }

            return [$from, $to];
        }

        $yearArg = $this->argument('year');

        if ($yearArg !== null) {
            $year = (int) $yearArg;
            if ($year < 2000 || $year > 2100) {
                $this->error('Year must be between 2000 and 2100.');

                return [null, null];
            }

            return [
                CarbonImmutable::createStrict($year, 1, 1),
                CarbonImmutable::createStrict($year, 12, 31),
            ];
        }

        $now = CarbonImmutable::now();

        return [
            $now->startOfYear(),
            $now->addYear()->endOfYear()->startOfDay(),
        ];
    }
}
