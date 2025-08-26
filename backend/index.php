<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") exit;

$path = $_GET['path'] ?? '';

switch ($path) {
  case "staff":
    require "staff.php";
    break;
  case "shifts":
    require "shifts.php";
    break;
  default:
    echo json_encode(["message" => "Invalid endpoint"]);
}
