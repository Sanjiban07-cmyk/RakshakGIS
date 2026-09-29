<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| Habitations + latest risk assessment
|--------------------------------------------------------------------------
*/

$habitationQuery = "
    SELECT
        h.id,
        h.name,
        h.district,
        h.state,
        h.population,
        h.latitude,
        h.longitude,

        ra.risk_score,
        ra.risk_level,
        ra.red_zone,
        ra.relocation_priority

    FROM habitations h

    LEFT JOIN risk_assessments ra
        ON ra.id = (
            SELECT MAX(ra2.id)
            FROM risk_assessments ra2
            WHERE ra2.habitation_id = h.id
        )

    ORDER BY h.id ASC
";

$habitationResult = $conn->query($habitationQuery);

if (!$habitationResult) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load habitation map data",
        "error" => $conn->error
    ]);

    exit;
}

$habitations = [];

while ($row = $habitationResult->fetch_assoc()) {

    $habitations[] = [
        "id" => (int) $row["id"],
        "name" => $row["name"],
        "district" => $row["district"],
        "state" => $row["state"],
        "population" => (int) $row["population"],
        "latitude" => (float) $row["latitude"],
        "longitude" => (float) $row["longitude"],
        "risk_score" => $row["risk_score"] !== null
            ? (float) $row["risk_score"]
            : null,
        "risk_level" => $row["risk_level"],
        "red_zone" => $row["red_zone"] !== null
            ? (int) $row["red_zone"]
            : 0,
        "relocation_priority" => $row["relocation_priority"]
    ];
}


/*
|--------------------------------------------------------------------------
| Relocation Sites
|--------------------------------------------------------------------------
*/

$siteQuery = "
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
        facilities

    FROM relocation_sites

    ORDER BY id ASC
";

$siteResult = $conn->query($siteQuery);

if (!$siteResult) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load relocation site map data",
        "error" => $conn->error
    ]);

    exit;
}

$relocationSites = [];

while ($row = $siteResult->fetch_assoc()) {

    $relocationSites[] = [
        "id" => (int) $row["id"],
        "site_name" => $row["site_name"],
        "district" => $row["district"],
        "latitude" => (float) $row["latitude"],
        "longitude" => (float) $row["longitude"],
        "total_capacity" => (int) $row["total_capacity"],
        "occupied_capacity" => (int) $row["occupied_capacity"],
        "available_capacity" => (int) $row["available_capacity"],
        "safety_level" => $row["safety_level"],
        "facilities" => $row["facilities"]
    ];
}


/*
|--------------------------------------------------------------------------
| Final response
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "habitations" => $habitations,
    "relocation_sites" => $relocationSites
]);

?>