<?php
header("Content-Type: application/json");
require "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"] ?? "";
$note = trim($data["note"] ?? "");

if ($id == "") {
    echo json_encode([
        "status"=>"error",
        "message"=>"Note id required"
    ]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        UPDATE vehicle_notes
        SET
            note = :note,
            updated_at = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        ":note"=>$note,
        ":id"=>$id
    ]);

    echo json_encode([
        "status"=>"success"
    ]);

}catch(Exception $e){

    echo json_encode([
        "status"=>"error",
        "message"=>$e->getMessage()
    ]);

}