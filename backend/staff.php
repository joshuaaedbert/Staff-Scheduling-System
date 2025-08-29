<?php
/**
 * staff.php — Minimal JSON API for staff records.
 *
 * GET
 *   200 → JSON array of staff: [{id, name, role, phone}, ...]
 *
 * POST
 *   Body: { name:string, role:string, phone?:string }
 *   Valid roles: server | cook | manager (case-insensitive; stored lowercase)
 *   201 → JSON of created staff; 400 on validation; 405 on unsupported method.
 *
 * Requires: db.php (PDO $pdo). Outputs JSON.
 */

require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Parse JSON request body.
 * @return array Decoded assoc array; [] if missing/invalid.
 */

function json_input() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

if ($method === 'GET') {
  // List all staff
  $stmt = $pdo->query("SELECT id, name, role, phone FROM staff ORDER BY id DESC");
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}

if ($method === 'POST') {
  // Create staff { name, role, phone? }
  $body = json_input();

  $name = isset($body['name']) ? trim($body['name']) : '';
  $role = isset($body['role']) ? trim($body['role']) : '';
  $phone = isset($body['phone']) ? trim($body['phone']) : null;

  if ($name === '' || $role === '') {
    http_response_code(400);
    echo json_encode(["error" => "Fields 'name' and 'role' are required"]);
    exit;
  }

  // Optional: validate role
  $allowedRoles = ['server', 'cook', 'manager'];
  if (!in_array(strtolower($role), $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid role. Allowed: server, cook, manager"]);
    exit;
  }

  $stmt = $pdo->prepare("INSERT INTO staff (name, role, phone) VALUES (?, ?, ?)");
  $stmt->execute([$name, strtolower($role), $phone]);
  $id = (int)$pdo->lastInsertId();

  http_response_code(201);
  echo json_encode([
    "id" => $id,
    "name" => $name,
    "role" => strtolower($role),
    "phone" => $phone
  ]);
  exit;
}

http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
