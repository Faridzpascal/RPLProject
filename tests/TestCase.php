<?php
/**
 * Lightweight Zero-Dependency Unit Test Runner for PHP
 * RPL Project - Antigravity Test Harness
 */

class TestCase {
    protected int $assertionsCount = 0;
    protected int $passedCount = 0;
    protected int $failedCount = 0;
    protected array $failures = [];

    public function assertEquals($expected, $actual, string $message = ''): void {
        $this->assertionsCount++;
        if ($expected === $actual) {
            $this->passedCount++;
        } else {
            $this->failedCount++;
            $this->failures[] = [
                'type'     => 'assertEquals',
                'message'  => $message,
                'expected' => $expected,
                'actual'   => $actual
            ];
        }
    }

    public function assertTrue($condition, string $message = ''): void {
        $this->assertEquals(true, (bool)$condition, $message ?: 'Expected condition to be TRUE');
    }

    public function assertFalse($condition, string $message = ''): void {
        $this->assertEquals(false, (bool)$condition, $message ?: 'Expected condition to be FALSE');
    }

    public function assertGreaterThanOrEqual($expected, $actual, string $message = ''): void {
        $this->assertionsCount++;
        if ($actual >= $expected) {
            $this->passedCount++;
        } else {
            $this->failedCount++;
            $this->failures[] = [
                'type'     => 'assertGreaterThanOrEqual',
                'message'  => $message ?: "Expected {$actual} to be >= {$expected}",
                'expected' => ">= {$expected}",
                'actual'   => $actual
            ];
        }
    }

    public function assertLessThanOrEqual($expected, $actual, string $message = ''): void {
        $this->assertionsCount++;
        if ($actual <= $expected) {
            $this->passedCount++;
        } else {
            $this->failedCount++;
            $this->failures[] = [
                'type'     => 'assertLessThanOrEqual',
                'message'  => $message ?: "Expected {$actual} to be <= {$expected}",
                'expected' => "<= {$expected}",
                'actual'   => $actual
            ];
        }
    }

    public function getAssertionsCount(): int {
        return $this->assertionsCount;
    }

    public function getPassedCount(): int {
        return $this->passedCount;
    }

    public function getFailedCount(): int {
        return $this->failedCount;
    }

    public function getFailures(): array {
        return $this->failures;
    }
}
