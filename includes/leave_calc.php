<?php
/**
 * Leave date calculation.
 * Requires db.php to already be loaded (reads system_settings and
 * public_holidays) — EXCEPT calculate_resumption_date_pure() below,
 * which takes the mode and holiday list explicitly and has no
 * dependencies at all, specifically so it can be unit-tested without a
 * database. See tests/leave_calc_test.php.
 *
 * Kept as functions in one place so the business rule lives in exactly
 * one spot. Default mode excludes Saturdays, Sundays, and configured
 * public holidays from the leave-day count (spec: "the following days
 * must be excluded from the calculation of leave days: Saturdays,
 * Sundays, Public Holidays").
 */

/** The actual date arithmetic — pure, no I/O, fully unit-testable. */
function calculate_resumption_date_pure(string $proceedingDate, int $daysRequested, string $mode = 'working_days', array $holidayDates = []): ?string
{
    if ($daysRequested < 1 || !$proceedingDate) {
        return null;
    }

    try {
        $date = new DateTime($proceedingDate);
    } catch (Exception $e) {
        return null;
    }

    if ($mode === 'working_days') {
        $holidaySet = array_flip($holidayDates); // O(1) lookup
        $remaining = $daysRequested;
        while ($remaining > 0) {
            $date->modify('+1 day');
            $weekday = (int) $date->format('w'); // 0 = Sunday, 6 = Saturday
            $isWeekend = $weekday === 0 || $weekday === 6;
            $isHoliday = isset($holidaySet[$date->format('Y-m-d')]);
            if (!$isWeekend && !$isHoliday) {
                $remaining--;
            }
        }
    } else {
        $date->modify('+' . $daysRequested . ' days');
    }

    return $date->format('Y-m-d');
}

/** Looks up the configured mode + public holidays from the database, then delegates to the pure function above. */
function calculate_resumption_date(string $proceedingDate, int $daysRequested): ?string
{
    $mode = 'working_days'; // 'working_days' (Sat/Sun/holidays excluded) | 'calendar'
    $holidayDates = [];

    try {
        $stmt = db()->prepare('SELECT setting_value FROM system_settings WHERE setting_key = "leave_calculation_mode"');
        $stmt->execute();
        $configured = $stmt->fetchColumn();
        if ($configured) {
            $mode = $configured;
        }

        if ($mode === 'working_days') {
            $holidayDates = db()->query('SELECT holiday_date FROM public_holidays')->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (PDOException $e) {
        // fall back to defaults silently
    }

    return calculate_resumption_date_pure($proceedingDate, $daysRequested, $mode, $holidayDates);
}

