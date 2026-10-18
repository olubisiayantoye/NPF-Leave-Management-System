<?php
/**
 * Run with:  php tests/run.php
 *
 * Only covers pure logic (no database, no filesystem beyond a tempfile
 * in the xlsx round-trip test) — deliberately, so it needs nothing but
 * a PHP CLI to run. See README.md "Testing" section for what this does
 * and doesn't cover, and the manual QA checklist for everything that
 * genuinely needs a live database to verify.
 */

require_once __DIR__ . '/TestRunner.php';

$runner = new TestRunner();

$testFiles = glob(__DIR__ . '/*_test.php');
sort($testFiles);

foreach ($testFiles as $file) {
    echo basename($file) . "\n";
    $testSuite = require $file;
    $testSuite($runner);
    echo "\n";
}

exit($runner->summary());
