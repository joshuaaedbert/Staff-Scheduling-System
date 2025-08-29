<?php
/**
 * index.php — Tiny JSON front controller / router.
 *
 * Sets JSON + permissive CORS headers, handles preflight (OPTIONS),
 * and routes by `?path=` to endpoint scripts:
 *   • ?path=staff  → staff.php
 *   • ?path=shifts → shifts.php
 *
 * Notes:
 * - CORS is wide open (Access-Control-Allow-Origin: *); tighten for production.
 * - Unknown paths return a JSON message; consider HTTP 404 for invalid endpoints.
 * - Downstream scripts handle their own methods and status codes.
 */

// CORS (adjust origins/methods/headers for your deployment)
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") exit;

$path = $_GET['path'] ?? '';

switch ($path) {
  // Staff listing/creation
  case "staff":
    require "staff.php";
    break;
  // Shift CRUD/assignment endpoints
  case "shifts":
    require "shifts.php";
    break;
  // Unknown route (optionally: http_response_code(404);)
  default:
    echo json_encode(["message" => "Invalid endpoint"]);
}
