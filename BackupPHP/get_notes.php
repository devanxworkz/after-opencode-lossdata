<?php
header("Content-Type: application/json");
require "db.php";

if (!isset($_GET["vin"])) {
    echo json_encode([
        "status" => "error",
        "message" => "VIN is required"
    ]);
    exit;
}

$vin = trim($_GET["vin"]);

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            vinnumber,
            note,
            created_at,
            updated_at
        FROM vehicle_notes
        WHERE vinnumber = :vin
        ORDER BY created_at DESC
    ");

    $stmt->execute([
        ":vin" => $vin
    ]);

    $notes = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "data" => $notes
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}