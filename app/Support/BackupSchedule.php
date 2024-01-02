<?php

namespace App\Support;

use Carbon\Carbon;
use Cron\CronExpression;
use DateTime;

class BackupSchedule {
    const TWICE_DAILY = '0 */12 * * *';
    const DAILY = '0 0 * * *';
    const WEEKLY = '0 0 * * 1';
    const MONTHLY = '0 0 1 * *';
    const QUARTERLY = '0 0 1 */3 *';
    const YEARLY = '0 0 1 1 *';

    const AVAILABLE_SCHEDULES = [
        self::TWICE_DAILY,
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
        self::QUARTERLY,
        self::YEARLY,
    ];

    public readonly CronExpression $cronExpression;

    public function __construct(string $cronExpression) {
        $this->cronExpression = new CronExpression($cronExpression);
    }

    public function getName(): ?string {
        return match ($this->getValue()) {
            static::TWICE_DAILY => __('Twice Daily'),
            static::DAILY => __('Daily'),
            static::WEEKLY => __('Weekly'),
            static::MONTHLY => __('Monthly'),
            static::QUARTERLY => __('Quarterly'),
            static::YEARLY => __('Yearly'),

            default => null,
        };
    }

    public function getValue(): string {
        return $this->cronExpression->getExpression();
    }

    public function getNextRunDate(): Carbon {
        return Carbon::createFromInterface(
            $this->cronExpression->getNextRunDate()
        );
    }

    public function getMultipleRunDates(int $amount): array {
        return array_map(
            fn(DateTime $date) => Carbon::createFromInterface($date),
            $this->cronExpression->getMultipleRunDates($amount)
        );
    }

    public static function createAllAvailable(): array {
        return array_map(
            fn(string $expression) => new BackupSchedule($expression),
            static::AVAILABLE_SCHEDULES
        );
    }
}
