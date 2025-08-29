<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/validators.php';

$method = $_SERVER['REQUEST_METHOD'];

function json_input() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}


if ($method === 'GET') {
  // Optional filter: ?day=YYYY-MM-DD
  $day = $_GET['day'] ?? null;

  if ($day !== null && !valid_day($day)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid 'day' format. Use YYYY-MM-DD"]);
    exit;
  }

  if ($day) {
    $stmt = $pdo->prepare("
      SELECT s.id, s.day, s.start_time, s.end_time, s.role, s.staff_id, st.name AS staff_name
      FROM shifts s
      LEFT JOIN staff st ON st.id = s.staff_id
      WHERE s.day = ?
      ORDER BY s.start_time ASC, s.id ASC
    ");
    $stmt->execute([$day]);
  } else {
    $stmt = $pdo->query("
      SELECT s.id, s.day, s.start_time, s.end_time, s.role, s.staff_id, st.name AS staff_name
      FROM shifts s
      LEFT JOIN staff st ON st.id = s.staff_id
      ORDER BY s.day ASC, s.start_time ASC, s.id ASC
    ");
  }

  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}

if ($method === 'POST') {
  $body = json_input();
  $action = $_GET['action'] ?? ($body['action'] ?? null);

  // --- Assign shift to staff ---
  if ($action === 'assign') {
    $shift_id = isset($body['shift_id']) ? (int)$body['shift_id'] : 0;
    $staff_id = isset($body['staff_id']) ? (int)$body['staff_id'] : 0;

    if ($shift_id <= 0 || $staff_id <= 0) {
      http_response_code(400);
      echo json_encode(["error" => "'shift_id' and 'staff_id' are required"]);
      exit;
    }

    // Load shift
    $s = $pdo->prepare("SELECT id, day, start_time, end_time, role FROM shifts WHERE id = ?");
    $s->execute([$shift_id]);
    $shift = $s->fetch(PDO::FETCH_ASSOC);
    if (!$shift) {
      http_response_code(404);
      echo json_encode(["error" => "Shift not found"]);
      exit;
    }

    // Load staff
    $st = $pdo->prepare("SELECT id, name, role FROM staff WHERE id = ?");
    $st->execute([$staff_id]);
    $staff = $st->fetch(PDO::FETCH_ASSOC);
    if (!$staff) {
      http_response_code(404);
      echo json_encode(["error" => "Staff not found"]);
      exit;
    }

    if (strtolower($staff['role']) !== strtolower($shift['role'])) {
      http_response_code(400);
      echo json_encode(["error" => "Role mismatch: shift requires '{$shift['role']}', staff is '{$staff['role']}'"]);
      exit;
    }

    if (has_overlap($pdo, $shift['day'], $shift['start_time'], $shift['end_time'], $staff_id, $shift_id)) {
      http_response_code(409);
      echo json_encode(["error" => "Staff already has an overlapping shift on {$shift['day']}"]);
      exit;
    }

    $upd = $pdo->prepare("UPDATE shifts SET staff_id = ? WHERE id = ?");
    $upd->execute([$staff_id, $shift_id]);

    $out = $pdo->prepare("
      SELECT s.id, s.day, s.start_time, s.end_time, s.role, s.staff_id, st.name AS staff_name
      FROM shifts s
      LEFT JOIN staff st ON st.id = s.staff_id
      WHERE s.id = ?
    ");
    $out->execute([$shift_id]);
    echo json_encode($out->fetch(PDO::FETCH_ASSOC));
    exit;
  }

  // --- Unassign staff from shift ---
  if ($action === 'unassign') {
    $shift_id = isset($body['shift_id']) ? (int)$body['shift_id'] : 0;
    if ($shift_id <= 0) {
      http_response_code(400);
      echo json_encode(["error" => "'shift_id' is required"]);
      exit;
    }
    $upd = $pdo->prepare("UPDATE shifts SET staff_id = NULL WHERE id = ?");
    $upd->execute([$shift_id]);

    $out = $pdo->prepare("SELECT id, day, start_time, end_time, role, staff_id FROM shifts WHERE id = ?");
    $out->execute([$shift_id]);
    echo json_encode($out->fetch(PDO::FETCH_ASSOC));
    exit;
  }

  // --- Create a new shift ---
  $day = isset($body['day']) ? trim($body['day']) : '';
  $start = isset($body['start_time']) ? trim($body['start_time']) : '';
  $end = isset($body['end_time']) ? trim($body['end_time']) : '';
  $role = isset($body['role']) ? strtolower(trim($body['role'])) : '';
  $staff_id = isset($body['staff_id']) && $body['staff_id'] !== '' ? (int)$body['staff_id'] : null;

  if (!valid_day($day) || !valid_time($start) || !valid_time($end) || $role === '') {
    http_response_code(400);
    echo json_encode(["error" => "Required fields: day (YYYY-MM-DD), start_time (HH:MM), end_time (HH:MM), role"]);
    exit;
  }

  if (time_minutes($start) >= time_minutes($end)) {
    http_response_code(400);
    echo json_encode(["error" => "start_time must be earlier than end_time"]);
    exit;
  }

  if (!in_array($role, allowed_roles(), true)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid role. Allowed: " . implode(', ', allowed_roles())]);
    exit;
  }

  if ($staff_id !== null) {
    // Validate staff exists and matches role
    $st = $pdo->prepare("SELECT id, name, role FROM staff WHERE id = ?");
    $st->execute([$staff_id]);
    $staff = $st->fetch(PDO::FETCH_ASSOC);
    if (!$staff) {
      http_response_code(404);
      echo json_encode(["error" => "Staff not found"]);
      exit;
    }
    if (strtolower($staff['role']) !== $role) {
      http_response_code(400);
      echo json_encode(["error" => "Role mismatch: shift requires '$role', staff is '{$staff['role']}'"]);
      exit;
    }
    if (has_overlap($pdo, $day, $start, $end, $staff_id)) {
      http_response_code(409);
      echo json_encode(["error" => "Staff already has an overlapping shift on $day"]);
      exit;
    }
  }

  $ins = $pdo->prepare("INSERT INTO shifts (day, start_time, end_time, role, staff_id) VALUES (?, ?, ?, ?, ?)");
  $ins->execute([$day, $start, $end, $role, $staff_id]);
  $id = (int)$pdo->lastInsertId();

  $out = $pdo->prepare("
    SELECT s.id, s.day, s.start_time, s.end_time, s.role, s.staff_id, st.name AS staff_name
    FROM shifts s
    LEFT JOIN staff st ON st.id = s.staff_id
    WHERE s.id = ?
  ");
  $out->execute([$id]);
  http_response_code(201);
  echo json_encode($out->fetch(PDO::FETCH_ASSOC));
  exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
