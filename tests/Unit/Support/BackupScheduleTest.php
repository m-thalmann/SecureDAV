<?php

namespace Tests\Unit\Support;

use App\Support\BackupSchedule;
use Carbon\Carbon;
use Tests\TestCase;

class BackupScheduleTest extends TestCase {
    public function testItCanBeCreatedFromAValidCronExpression(): void {
        $expression = '0 0 * * *';

        $schedule = new BackupSchedule($expression);

        $this->assertInstanceOf(BackupSchedule::class, $schedule);
    }

    public function testGetNameReturnsNameOfKnownExpression(): void {
        $expression = BackupSchedule::DAILY;

        $schedule = new BackupSchedule($expression);

        $this->assertEquals('Daily', $schedule->getName());
    }

    public function testGetNameReturnsNullForUnknownExpression(): void {
        $expression = '0 0 */2 1 *';

        $schedule = new BackupSchedule($expression);

        $this->assertNull($schedule->getName());
    }

    public function testGetValueReturnsTheExpression(): void {
        $expression = '0 0 * * *';

        $schedule = new BackupSchedule($expression);

        $this->assertEquals($expression, $schedule->getValue());
    }

    public function testGetNextRunDateReturnsTheNextRunDate(): void {
        $expression = BackupSchedule::DAILY;

        $schedule = new BackupSchedule($expression);

        $nextRunDate = $schedule->getNextRunDate();

        $this->assertEquals(
            now()
                ->addDay()
                ->startOfDay(),
            $nextRunDate
        );

        $this->assertInstanceOf(Carbon::class, $nextRunDate);
    }

    public function testGetMultipleRunDatesReturnsTheNextRunDates(): void {
        $expression = BackupSchedule::DAILY;

        $schedule = new BackupSchedule($expression);

        $nextRunDates = $schedule->getMultipleRunDates(3);

        $this->assertCount(3, $nextRunDates);

        foreach ($nextRunDates as $index => $nextRunDate) {
            $this->assertInstanceOf(Carbon::class, $nextRunDate);

            $this->assertEquals(
                now()
                    ->addDays($index + 1)
                    ->startOfDay(),
                $nextRunDate
            );
        }
    }

    public function testCreateAllAvailableReturnsAllAvailableSchedules(): void {
        $schedules = BackupSchedule::createAllAvailable();

        $this->assertCount(
            count(BackupSchedule::AVAILABLE_SCHEDULES),
            $schedules
        );

        foreach ($schedules as $schedule) {
            $this->assertContains(
                $schedule->getValue(),
                BackupSchedule::AVAILABLE_SCHEDULES
            );
        }
    }
}
