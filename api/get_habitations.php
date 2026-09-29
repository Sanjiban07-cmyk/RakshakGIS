<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';


$sql = "SELECT
            h.id,
            h.name,
            h.district,
            h.state,
            h.population,
            h.latitude,
            h.longitude,
            h.flood_risk,
            h.landslide_risk,
            h.hazard_history,
            h.population_vulnerability,
            h.created_at,

            ra.risk_score,
            ra.risk_level,
            ra.red_zone,
            ra.relocation_priority,
            ra.assessed_at

        FROM habitations h

        LEFT JOIN risk_assessments ra
            ON ra.id = (
                SELECT ra2.id
                FROM risk_assessments ra2
                WHERE ra2.habitation_id = h.id
                ORDER BY ra2.assessed_at DESC, ra2.id DESC
                LIMIT 1
            )

        ORDER BY h.id DESC";


$result = $conn->query($sql);


if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch habitations",
        "error" => $conn->error
    ]);

    exit;
}


$habitations = [];


while ($row = $result->fetch_assoc()) {

    $habitations[] = $row;

}


echo json_encode([
    "success" => true,
    "count" => count($habitations),
    "data" => $habitations
]);

?>