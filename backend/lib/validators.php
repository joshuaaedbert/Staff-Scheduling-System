<?php
/**
 * validators.php — Small validation/utility helpers for the scheduling API.
 *
 * - allowed_roles() : canonical (lowercase) role list
 * - valid_day()     : validate 'YYYY-MM-DD' using DateTime round-trip
 * - valid_time()    : validate 'HH:MM' 24-hour format
 * - time_minutes()  : convert 'HH:MM' → minutes since midnight
 * - has_overlap()   : check if a staff member has a shift overlapping [start, end) on a day
 */

function allowed_roles() {
  return ['server', 'cook', 'manager'];
}

/**
 * Validate a calendar day string as 'YYYY-MM-DD'.
 * Uses DateTime round-trip to reject impossible dates (e.g., 2025-02-30).
 * @param string $d
 * @return bool
 */
function valid_day($d) {
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;
  $dt = DateTime::createFromFormat('Y-m-d', $d);
  return $dt && $dt->format('Y-m-d') === $d;
}

/**
 * Validate a time string as 'HH:MM' (24-hour clock).
 * @param string $t
 * @return bool
 */
function valid_time($t) {
  if (!preg_match('/^\d{2}:\d{2}$/', $t)) return false;
  [$h, $m] = array_map('intval', explode(':', $t));
  return $h >= 0 && $h <= 23 && $m >= 0 && $m <= 59;
}

/**
 * Convert 'HH:MM' to minutes since midnight.
 * @param string $t
 * @return int
 */
function time_minutes($t) {
  [$h, $m] = array_map('intval', explode(':', $t));
  return $h * 60 + $m;
}

/**
 * Determine if an existing shift overlaps a proposed time window for a staff member.
 *
 * Overlap rule (half-open intervals): [start, end) overlaps iff
 * NOT (existing.end <= start OR existing.start >= end)
 *
 * @param PDO        $pdo
 * @param string     $day   'YYYY-MM-DD'
 * @param string     $start 'HH:MM'
 * @param string     $end   'HH:MM'
 * @param int|null   $staff_id           Staff to check; null → no overlap check
 * @param int|null   $exclude_shift_id   Ignore this shift (useful when updating/assigning)
 * @return bool      True if any overlapping shift exists.
 */
function has_overlap(PDO $pdo, $day, $start, $end, $staff_id, $exclude_shift_id = null) {
  if ($staff_id === null) return false;
  $q = "SELECT id FROM shifts WHERE day = :day AND staff_id = :sid";
  if ($exclude_shift_id !== null) $q .= " AND id <> :ex";
  $q .= " AND NOT (end_time <= :start OR start_time >= :end) LIMIT 1";

  $stmt = $pdo->prepare($q);
  $stmt->bindValue(':day', $day);
  $stmt->bindValue(':sid', $staff_id, PDO::PARAM_INT);
  if ($exclude_shift_id !== null) $stmt->bindValue(':ex', $exclude_shift_id, PDO::PARAM_INT);
  $stmt->bindValue(':start', $start);
  $stmt->bindValue(':end', $end);
  $stmt->execute();
  return (bool)$stmt->fetchColumn();
}
