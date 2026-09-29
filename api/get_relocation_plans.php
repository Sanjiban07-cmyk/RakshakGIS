<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| Optional habitation filter
|--------------------------------------------------------------------------
*/

$habitationId = null;

if (isset($_GET['habitation_id']) && is_numeric($_GET['habitation_id'])) {
    $habitationId = (int) $_GET['habitation_id'];
}


/*
|--------------------------------------------------------------------------
| Get relocation plans
|--------------------------------------------------------------------------
*/

if ($habitationId !== null) {

    $stmt = $conn->prepare(
        "SELECT
            rp.id,
            rp.habitation_id,
            h.name AS habitation_name,
            h.district AS habitation_district,
            h.population AS habitation_population,

            rp.relocation_site_id,
            rs.site_name,
            rs.district AS site_district,

            rp.population_to_relocate,
            rp.available_capacity,
            rp.distance_km,
            rp.recommendation_reason,
            rp.status,
            rp.created_at

         FROM relocation_plans rp

         INNER JOIN habitations h
            ON h.id = rp.habitation_id

         INNER JOIN relocation_sites rs
            ON rs.id = rp.relocation_site_id

         WHERE rp.habitation_id = ?

         ORDER BY rp.created_at DESC"
    );

    $stmt->bind_param("i", $habitationId);

    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $result = $conn->query(
        "SELECT
            rp.id,
            rp.habitation_id,
            h.name AS habitation_name,
            h.district AS habitation_district,
            h.population AS habitation_population,

            rp.relocation_site_id,
            rs.site_name,
            rs.district AS site_district,

            rp.population_to_relocate,
            rp.available_capacity,
            rp.distance_km,
            rp.recommendation_reason,
            rp.status,
            rp.created_at

         FROM relocation_plans rp

         INNER JOIN habitations h
            ON h.id = rp.habitation_id

         INNER JOIN relocation_sites rs
            ON rs.id = rp.relocation_site_id

         ORDER BY rp.created_at DESC"
    );
}


/*
|--------------------------------------------------------------------------
| Check query
|--------------------------------------------------------------------------
*/

if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch relocation plans",
        "error" => $conn->error
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Build response
|--------------------------------------------------------------------------
*/

$plans = [];

while ($row = $result->fetch_assoc()) {
    $plans[] = $row;
}


/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "count" => count($plans),
    "data" => $plans
]);

?>