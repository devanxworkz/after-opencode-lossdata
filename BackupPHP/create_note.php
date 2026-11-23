<?php
header("Content-Type: application/json");
require "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$vin = trim($data["vinnumber"] ?? "");
$note = trim($data["note"] ?? "");

if ($vin == "" || $note == "") {
    echo json_encode([
        "status" => "error",
        "message" => "VIN and Note are required"
    ]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        INSERT INTO vehicle_notes
        (vinnumber, note)
        VALUES
        (:vin, :note)
        RETURNING id
    ");

    $stmt->execute([
        ":vin" => $vin,
        ":note" => $note
    ]);

    $id = $stmt->fetch();

    echo json_encode([
        "status" => "success",
        "id" => $id["id"]
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}