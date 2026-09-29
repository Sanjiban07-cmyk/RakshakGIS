<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| GET HABITATION ID
|--------------------------------------------------------------------------
*/

$habitationId = isset($_GET['habitation_id']) && is_numeric($_GET['habitation_id'])
    ? (int) $_GET['habitation_id']
    : 0;

if ($habitationId <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid habitation ID"
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| GET HABITATION LOCATION + POPULATION
|--------------------------------------------------------------------------
*/

$habitationStmt = $conn->prepare("
    SELECT
        id,
        name,
        population,
        latitude,
        longitude
    FROM habitations
    WHERE id = ?
    LIMIT 1
");

if (!$habitationStmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare habitation query"
    ]);

    exit;
}

$habitationStmt->bind_param(
    "i",
    $habitationId
);

$habitationStmt->execute();

$habitationResult = $habitationStmt->get_result();

$habitation = $habitationResult->fetch_assoc();

$habitationStmt->close();


if (!$habitation) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Habitation not found"
    ]);

    exit;
}


$population = (int) $habitation['population'];

$habitationLatitude = $habitation['latitude'];
$habitationLongitude = $habitation['longitude'];


/*
|--------------------------------------------------------------------------
| GET RELOCATION SITES
|--------------------------------------------------------------------------
|
| Distance is calculated dynamically using the coordinates of:
|
| 1. Selected habitation
| 2. Relocation site
|
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        site_name,
        district,
        latitude,
        longitude,
        total_capacity,
        occupied_capacity,
        (total_capacity - occupied_capacity) AS available_capacity,
        safety_level,
        facilities,
        created_at,

        (
            6371 * ACOS(
                LEAST(
                    1,
                    GREATEST(
                        -1,
                        COS(RADIANS(?))
                        *
                        COS(RADIANS(latitude))
                        *
                        COS(
                            RADIANS(longitude)
                            -
                            RADIANS(?)
                        )
                        +
                        SIN(RADIANS(?))
                        *
                        SIN(RADIANS(latitude))
                    )
                )
            )
        ) AS calculated_distance

    FROM relocation_sites

    WHERE
        (total_capacity - occupied_capacity) >= ?

    ORDER BY
        calculated_distance ASC,
        available_capacity DESC
";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare relocation site query",
        "error" => $conn->error
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Bind Parameters
|--------------------------------------------------------------------------
|
| latitude
| longitude
| latitude
| population
|
*/

$stmt->bind_param(
    "dddi",
    $habitationLatitude,
    $habitationLongitude,
    $habitationLatitude,
    $population
);


if (!$stmt->execute()) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch relocation sites",
        "error" => $stmt->error
    ]);

    $stmt->close();

    exit;
}


$result = $stmt->get_result();

$sites = [];


while ($row = $result->fetch_assoc()) {

    $availableCapacity =
        (int) $row['available_capacity'];

    $distance =
        $row['calculated_distance'] !== null
            ? round(
                (float) $row['calculated_distance'],
                2
            )
            : null;


    $sites[] = [

        "id" =>
            (int) $row['id'],

        "site_name" =>
            $row['site_name'],

        "district" =>
            $row['district'],

        "latitude" =>
            $row['latitude'],

        "longitude" =>
            $row['longitude'],

        "total_capacity" =>
            (int) $row['total_capacity'],

        "occupied_capacity" =>
            (int) $row['occupied_capacity'],

        "available_capacity" =>
            $availableCapacity,

        "safety_level" =>
            $row['safety_level'],

        "distance_from_habitation" =>
            $distance,

        "facilities" =>
            $row['facilities'],

        "created_at" =>
            $row['created_at']
    ];
}


$stmt->close();


/*
|--------------------------------------------------------------------------
| RESPONSE
|--------------------------------------------------------------------------
*/

echo json_encode([

    "success" => true,

    "habitation_id" =>
        $habitationId,

    "habitation_name" =>
        $habitation['name'],

    "population" =>
        $population,

    "count" =>
        count($sites),

    "data" =>
        $sites
]);

?>