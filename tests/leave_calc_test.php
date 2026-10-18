<?php
require_once __DIR__ . '/../includes/leave_calc.php';

return function (TestRunner $t) {

    $t->run('calendar mode adds days_requested calendar days', function (TestRunner $t) {
        $t->assertEquals('2026-08-26', calculate_resumption_date_pure('2026-08-01', 25, 'calendar'));
    });

    $t->run('calendar mode crosses a month boundary correctly', function (TestRunner $t) {
        $t->assertEquals('2026-02-01', calculate_resumption_date_pure('2026-01-28', 4, 'calendar'));
    });

    $t->run('calendar mode crosses a leap-year February correctly', function (TestRunner $t) {
        // 2028 is a leap year — Feb has 29 days
        $t->assertEquals('2028-03-01', calculate_resumption_date_pure('2028-02-28', 2, 'calendar'));
    });

    $t->run('working_days mode excludes both Saturdays and Sundays', function (TestRunner $t) {
        // 2026-08-24 is a Monday. 6 working days later, skipping BOTH the
        // Saturday (08-29) and Sunday (08-30) in between, lands on
        // Tuesday 2026-09-01 — not 08-31, which is what you'd get if only
        // Sunday were excluded.
        $t->assertEquals('2026-09-01', calculate_resumption_date_pure('2026-08-24', 6, 'working_days'));
    });

    $t->run('working_days mode from a Saturday skips the following Saturday and Sunday', function (TestRunner $t) {
        // 2026-08-29 is a Saturday (not counted as a working day itself,
        // since it's the proceeding date). +1 working day should land on
        // Monday 2026-08-31, skipping Sunday 08-30.
        $t->assertEquals('2026-08-31', calculate_resumption_date_pure('2026-08-29', 1, 'working_days'));
    });

    $t->run('working_days mode also excludes a configured public holiday', function (TestRunner $t) {
        // 2026-08-24 is a Monday. 3 working days later would normally be
        // Thursday 2026-08-27 — but with 2026-08-26 (Wednesday) configured
        // as a public holiday, that day doesn't count either, pushing the
        // resumption date to Friday 2026-08-28.
        $withoutHoliday = calculate_resumption_date_pure('2026-08-24', 3, 'working_days', []);
        $withHoliday = calculate_resumption_date_pure('2026-08-24', 3, 'working_days', ['2026-08-26']);

        $t->assertEquals('2026-08-27', $withoutHoliday);
        $t->assertEquals('2026-08-28', $withHoliday);
    });

    $t->run('a public holiday that falls on a weekend does not double-count', function (TestRunner $t) {
        // A holiday landing on a Saturday/Sunday is already excluded as a
        // weekend — listing it as a holiday too should change nothing.
        $withoutHolidayList = calculate_resumption_date_pure('2026-08-24', 4, 'working_days', []);
        $withRedundantHoliday = calculate_resumption_date_pure('2026-08-24', 4, 'working_days', ['2026-08-29']); // a Saturday
        $t->assertEquals($withoutHolidayList, $withRedundantHoliday);
    });

    $t->run('returns null for zero or negative days requested', function (TestRunner $t) {
        $t->assertNull(calculate_resumption_date_pure('2026-08-01', 0));
        $t->assertNull(calculate_resumption_date_pure('2026-08-01', -5));
    });

    $t->run('returns null for an empty proceeding date', function (TestRunner $t) {
        $t->assertNull(calculate_resumption_date_pure('', 10));
    });

    $t->run('returns null for an unparseable proceeding date', function (TestRunner $t) {
        $t->assertNull(calculate_resumption_date_pure('not-a-date', 10));
    });

    $t->run('defaults to working_days mode (Sat/Sun excluded) when none is specified', function (TestRunner $t) {
        // 2026-08-01 is a Saturday. +10 working days, by default, should
        // NOT be the naive calendar-days answer (2026-08-11) — it should
        // land later because weekends along the way don't count.
        $t->assertEquals('2026-08-14', calculate_resumption_date_pure('2026-08-01', 10));
    });

};
