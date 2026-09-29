<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['habitation_id']) || !is_numeric($_GET['habitation_id'])) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid habitation_id is required"
    ]);

    exit;
}

$habitationId = (int) $_GET['habitation_id'];

$stmt = $conn->prepare(
    "SELECT
        ra.id AS assessment_id,
        ra.habitation_id,

        h.name AS habitation_name,
        h.district,
        h.state,
        h.population,
        h.latitude,
        h.longitude,

        ra.flood_score,
        ra.landslide_score,
        ra.hazard_history_score,
        ra.vulnerability_score,

        ra.risk_score,
        ra.risk_level,
        ra.red_zone,
        ra.relocation_priority,
        ra.assessment_notes,
        ra.assessed_at

     FROM risk_assessments ra

     INNER JOIN habitations h
        ON h.id = ra.habitation_id

     WHERE ra.habitation_id = ?

     ORDER BY ra.id DESC

     LIMIT 1"
);

if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare risk assessment query",
        "error" => $conn->error
    ]);

    exit;
}

$stmt->bind_param("i", $habitationId);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "No risk assessment found for this habitation"
    ]);

    $stmt->close();

    exit;
}

$risk = $result->fetch_assoc();

$stmt->close();

echo json_encode([
    "success" => true,
    "data" => $risk
]);

?>
