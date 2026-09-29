<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid habitation ID is required"
    ]);

    exit;
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare(
    "SELECT
        id,
        name,
        district,
        state,
        population,
        latitude,
        longitude,
        flood_risk,
        landslide_risk,
        hazard_history,
        population_vulnerability,
        created_at,
        updated_at
     FROM habitations
     WHERE id = ?"
);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Habitation not found"
    ]);

    exit;
}

$habitation = $result->fetch_assoc();

echo json_encode([
    "success" => true,
    "data" => $habitation
]);

$stmt->close();

?>