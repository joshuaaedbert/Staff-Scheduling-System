<?php
use PHPUnit\Framework\TestCase;

final class ValidatorsTest extends TestCase
{
    public function testAllowedRoles(): void {
        $roles = allowed_roles();
        $this->assertContains('server', $roles);
        $this->assertContains('cook', $roles);
        $this->assertContains('manager', $roles);
    }

    public function testValidDay(): void {
        $this->assertTrue(valid_day('2025-09-01'));
        $this->assertFalse(valid_day('2025-13-01'));
        $this->assertFalse(valid_day('09-01-2025'));
        $this->assertFalse(valid_day('2025-9-1'));
    }

    public function testValidTime(): void {
        $this->assertTrue(valid_time('00:00'));
        $this->assertTrue(valid_time('23:59'));
        $this->assertFalse(valid_time('24:00'));
        $this->assertFalse(valid_time('7:30'));
        $this->assertFalse(valid_time('07-30'));
    }

    public function testTimeMinutes(): void {
        $this->assertSame(0, time_minutes('00:00'));
        $this->assertSame(9*60 + 30, time_minutes('09:30'));
        $this->assertSame(23*60 + 59, time_minutes('23:59'));
    }

    public function testHasOverlap(): void {
        // In-memory SQLite
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("
            CREATE TABLE staff (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, role TEXT, phone TEXT);
            CREATE TABLE shifts (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              day TEXT NOT NULL,
              start_time TEXT NOT NULL,
              end_time TEXT NOT NULL,
              role TEXT NOT NULL,
              staff_id INTEGER
            );
        ");

        // One staff
        $pdo->exec("INSERT INTO staff (name, role) VALUES ('Alice', 'server')");
        $staffId = (int)$pdo->lastInsertId();

        // Existing shift: 09:00-12:00
        $stmt = $pdo->prepare("INSERT INTO shifts (day, start_time, end_time, role, staff_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['2025-09-01', '09:00', '12:00', 'server', $staffId]);
        $existingId = (int)$pdo->lastInsertId();

        // Overlaps (11-13 overlaps), (08-09 no), (12-14 no)
        $this->assertTrue(has_overlap($pdo, '2025-09-01', '11:00', '13:00', $staffId));
        $this->assertFalse(has_overlap($pdo, '2025-09-01', '08:00', '09:00', $staffId));
        $this->assertFalse(has_overlap($pdo, '2025-09-01', '12:00', '14:00', $staffId));

        // Excluding the existing shift id should remove overlap if same times
        $this->assertFalse(has_overlap($pdo, '2025-09-01', '09:00', '12:00', $staffId, $existingId));
    }
}
