<?php
// backend/lib/validators.php

function allowed_roles() {
  return ['server', 'cook', 'manager'];
}

function valid_day($d) {
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;
  $dt = DateTime::createFromFormat('Y-m-d', $d);
  return $dt && $dt->format('Y-m-d') === $d;
}

function valid_time($t) {
  if (!preg_match('/^\d{2}:\d{2}$/', $t)) return false;
  [$h, $m] = array_map('intval', explode(':', $t));
  return $h >= 0 && $h <= 23 && $m >= 0 && $m <= 59;
}

function time_minutes($t) {
  [$h, $m] = array_map('intval', explode(':', $t));
  return $h * 60 + $m;
}

/**
 * Overlap rule: [start, end) overlaps if NOT (existing.end <= start OR existing.start >= end)
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
