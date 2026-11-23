<?php
header("Content-Type: application/json");
require "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"] ?? "";

if ($id == "") {

    echo json_encode([
        "status"=>"error",
        "message"=>"Note id required"
    ]);
    exit;
}

try{

    $stmt = $pdo->prepare("
        DELETE FROM vehicle_notes
        WHERE id = :id
    ");

    $stmt->execute([
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