<?php
/**
 * A deliberately tiny test framework — this project has no Composer
 * dependencies anywhere, and PHPUnit needs Composer, so this is a
 * hand-rolled equivalent covering exactly what's needed: named test
 * cases, a handful of assertions, and a pass/fail summary with a
 * non-zero exit code on failure (so it works in CI).
 */

class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];
    private string $currentTest = '';

    public function run(string $name, callable $fn): void
    {
        $this->currentTest = $name;
        try {
            $fn($this);
            $this->passed++;
            echo "  \033[32m✓\033[0m {$name}\n";
        } catch (Throwable $e) {
            $this->failed++;
            $this->failures[] = "{$name}: {$e->getMessage()}";
            echo "  \033[31m✗\033[0m {$name}\n    {$e->getMessage()}\n";
        }
    }

    public function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $exp = var_export($expected, true);
            $act = var_export($actual, true);
            throw new RuntimeException($message ?: "Expected {$exp}, got {$act}");
        }
    }

    public function assertTrue($value, string $message = ''): void
    {
        if ($value !== true) {
            throw new RuntimeException($message ?: 'Expected true, got ' . var_export($value, true));
        }
    }

    public function assertFalse($value, string $message = ''): void
    {
        if ($value !== false) {
            throw new RuntimeException($message ?: 'Expected false, got ' . var_export($value, true));
        }
    }

    public function assertNull($value, string $message = ''): void
    {
        if ($value !== null) {
            throw new RuntimeException($message ?: 'Expected null, got ' . var_export($value, true));
        }
    }

    public function summary(): int
    {
        $total = $this->passed + $this->failed;
        echo "\n" . str_repeat('-', 50) . "\n";
        echo "{$this->passed}/{$total} passed\n";
        if ($this->failures) {
            echo "\nFailures:\n";
            foreach ($this->failures as $f) {
                echo "  - {$f}\n";
            }
        }
        return $this->failed > 0 ? 1 : 0;
    }
}
