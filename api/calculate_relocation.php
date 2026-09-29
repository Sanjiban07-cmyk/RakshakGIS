<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../backend/relocation_engine.php';


/*
|--------------------------------------------------------------------------
| Validate habitation ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET['habitation_id']) || !is_numeric($_GET['habitation_id'])) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid habitation_id is required"
    ]);

    exit;
}

$habitationId = (int) $_GET['habitation_id'];


/*
|--------------------------------------------------------------------------
| Get habitation
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        id,
        name,
        district,
        population
     FROM habitations
     WHERE id = ?"
);

$stmt->bind_param("i", $habitationId);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Habitation not found"
    ]);

    $stmt->close();

    exit;
}

$habitation = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| Get safe relocation sites
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        site_name,
        district,
        total_capacity,
        occupied_capacity,
        (total_capacity - occupied_capacity) AS available_capacity,
        safety_level,
        distance_from_habitation,
        facilities
    FROM relocation_sites
";

$result = $conn->query($sql);

if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch relocation sites",
        "error" => $conn->error
    ]);

    exit;
}

$sites = [];

while ($row = $result->fetch_assoc()) {
    $sites[] = $row;
}


/*
|--------------------------------------------------------------------------
| Run recommendation engine
|--------------------------------------------------------------------------
*/

$recommendations = recommendRelocation(
    (int) $habitation['population'],
    $sites
);


/*
|--------------------------------------------------------------------------
| No suitable site
|--------------------------------------------------------------------------
*/

if (empty($recommendations)) {

    echo json_encode([
        "success" => true,
        "message" => "No suitable relocation site found",
        "habitation" => [
            "id" => $habitation['id'],
            "name" => $habitation['name'],
            "district" => $habitation['district'],
            "population" => $habitation['population']
        ],
        "recommendations" => []
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Best recommendation
|--------------------------------------------------------------------------
*/

$best = $recommendations[0];


/*
|--------------------------------------------------------------------------
| Save relocation plan
|--------------------------------------------------------------------------
*/

$reason =
    "Recommended based on safety level, available capacity and distance.";


$insert = $conn->prepare(
    "INSERT INTO relocation_plans
    (
        habitation_id,
        relocation_site_id,
        population_to_relocate,
        available_capacity,
        distance_km,
        recommendation_reason,
        status
    )
    VALUES (?, ?, ?, ?, ?, ?, 'PLANNED')"
);


$insert->bind_param(
    "iiiids",
    $habitationId,
    $best['site_id'],
    $habitation['population'],
    $best['available_capacity'],
    $best['distance_km'],
    $reason
);


if (!$insert->execute()) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to save relocation plan",
        "error" => $insert->error
    ]);

    $insert->close();

    exit;
}


$planId = $insert->insert_id;

$insert->close();


/*
|--------------------------------------------------------------------------
| Return complete result
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,

    "message" => "Relocation recommendation calculated successfully",

    "plan_id" => $planId,

    "habitation" => [
        "id" => $habitation['id'],
        "name" => $habitation['name'],
        "district" => $habitation['district'],
        "population" => $habitation['population']
    ],

    "recommended_site" => $best,

    "alternative_sites" => array_slice(
        $recommendations,
        1
    )
]);

?>