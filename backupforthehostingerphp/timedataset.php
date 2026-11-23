<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// DB credentials
$host = "103.174.148.224";
$port = "5432";
$dbname = "rivotcommandcenterdb";
$user = "rivotcommandcenter";
$pass = "123";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Connection failed']);
    error_log('DB Connection failed: ' . $e->getMessage());
    exit();
}

// Get query params
$vin = strtoupper($_GET['vin'] ?? '');
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

if (!$vin || !$start || !$end) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing vin, start, or end parameter']);
    exit();
}

try {
    // Convert UTC to IST
    $dbTimeZone = new DateTimeZone('Asia/Kolkata');

    $startDateTime = new DateTime($start, new DateTimeZone('UTC'));
    $endDateTime   = new DateTime($end, new DateTimeZone('UTC'));

    $startDateTime->setTimezone($dbTimeZone);
    $endDateTime->setTimezone($dbTimeZone);

    $startStr = $startDateTime->format('Y-m-d H:i:s');
    $endStr   = $endDateTime->format('Y-m-d H:i:s');

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT vinNumber, speed_kmph, controllerMosTemp, motorTemp, bmsMosTemp, ntc,
               currentconsumption, batvoltage, remainingCapacity_AH, inAH,
               inAH_by_charger, inAH_by_regen, outAH, ev_power_state, currentRider,
               tirepressure, soc, apuSoc, lat_long, time
        FROM realtimedata
        WHERE vinNumber = :vin
          AND time BETWEEN :start AND :end
        ORDER BY time ASC
    ");
    $stmt->execute([
        ':vin' => $vin,
        ':start' => $startStr,
        ':end' => $endStr,
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed']);
    error_log('Query failed: ' . $e->getMessage());
}
?>
