<?php
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

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
