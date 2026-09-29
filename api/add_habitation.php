<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);

    exit;
}

$name = trim($_POST['name'] ?? '');
$district = trim($_POST['district'] ?? '');
$state = trim($_POST['state'] ?? 'Maharashtra');
$population = $_POST['population'] ?? null;

$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;

$floodRisk = $_POST['flood_risk'] ?? 0;
$landslideRisk = $_POST['landslide_risk'] ?? 0;
$hazardHistory = $_POST['hazard_history'] ?? 0;
$populationVulnerability = $_POST['population_vulnerability'] ?? 0;


if ($name === '' || $district === '' || $population === null) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Name, district and population are required"
    ]);

    exit;
}


if (!is_numeric($population) || $population < 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Population must be a valid positive number"
    ]);

    exit;
}


if ($latitude !== null && $latitude !== '' && !is_numeric($latitude)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Latitude must be numeric"
    ]);

    exit;
}


if ($longitude !== null && $longitude !== '' && !is_numeric($longitude)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Longitude must be numeric"
    ]);

    exit;
}


$sql = "INSERT INTO habitations
(
    name,
    district,
    state,
    population,
    latitude,
    longitude,
    flood_risk,
    landslide_risk,
    hazard_history,
    population_vulnerability
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare database query",
        "error" => $conn->error
    ]);

    exit;
}


$stmt->bind_param(
    "sssidddddd",
    $name,
    $district,
    $state,
    $population,
    $latitude,
    $longitude,
    $floodRisk,
    $landslideRisk,
    $hazardHistory,
    $populationVulnerability
);


if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Habitation added successfully",
        "habitation_id" => $stmt->insert_id
    ]);

} else {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to add habitation",
        "error" => $stmt->error
    ]);
}


$stmt->close();

?>
