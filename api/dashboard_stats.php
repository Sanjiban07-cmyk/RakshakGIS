<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| Total Habitations
|--------------------------------------------------------------------------
*/

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM habitations"
);

$totalHabitations = (int) $result->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| Risk Statistics
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Risk Statistics
|--------------------------------------------------------------------------
| Count only the latest risk assessment for each habitation.
| This prevents old/repeated assessments from being counted again.
*/

$result = $conn->query(
    "SELECT
        SUM(CASE WHEN latest.risk_level = 'HIGH' THEN 1 ELSE 0 END) AS high_risk,
        SUM(CASE WHEN latest.risk_level = 'MEDIUM' THEN 1 ELSE 0 END) AS medium_risk,
        SUM(CASE WHEN latest.risk_level = 'LOW' THEN 1 ELSE 0 END) AS low_risk,
        SUM(CASE WHEN latest.red_zone = 1 THEN 1 ELSE 0 END) AS red_zones
     FROM risk_assessments latest
     INNER JOIN (
        SELECT
            habitation_id,
            MAX(id) AS latest_id
        FROM risk_assessments
        GROUP BY habitation_id
     ) latest_ids
        ON latest.id = latest_ids.latest_id"
);

$riskStats = $result->fetch_assoc();


/*
|--------------------------------------------------------------------------
| Relocation Plans
|--------------------------------------------------------------------------
*/

$result = $conn->query(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'PLANNED' THEN 1 ELSE 0 END) AS planned,
        SUM(CASE WHEN status = 'APPROVED' THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN status = 'IN_PROGRESS' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled
     FROM relocation_plans"
);

$relocationStats = $result->fetch_assoc();

$totalRelocationPlans = (int) ($relocationStats['total'] ?? 0);
$plannedRelocationPlans = (int) ($relocationStats['planned'] ?? 0);
$approvedRelocationPlans = (int) ($relocationStats['approved'] ?? 0);
$inProgressRelocationPlans = (int) ($relocationStats['in_progress'] ?? 0);
$completedRelocationPlans = (int) ($relocationStats['completed'] ?? 0);
$cancelledRelocationPlans = (int) ($relocationStats['cancelled'] ?? 0);


/*
|--------------------------------------------------------------------------
| Relocation Site Statistics
|--------------------------------------------------------------------------
*/

$result = $conn->query(
    "SELECT
        COUNT(*) AS total_sites,
        COALESCE(SUM(total_capacity), 0) AS total_capacity,
        COALESCE(
            SUM(total_capacity - occupied_capacity),
            0
        ) AS available_capacity
     FROM relocation_sites"
);
$siteStats = $result->fetch_assoc();


/*
|--------------------------------------------------------------------------
| Recent Risk Assessments
|--------------------------------------------------------------------------
*/

$result = $conn->query(
    "SELECT
        ra.id,
        ra.habitation_id,
        h.name AS habitation_name,
        h.district,
        h.population,
        ra.risk_score,
        ra.risk_level,
        ra.red_zone,
        ra.relocation_priority,
        ra.assessed_at
     FROM risk_assessments ra
     INNER JOIN habitations h
        ON h.id = ra.habitation_id
     ORDER BY ra.assessed_at DESC
     LIMIT 5"
);

$recentAssessments = [];

while ($row = $result->fetch_assoc()) {
    $recentAssessments[] = $row;
}


/*
|--------------------------------------------------------------------------
| Recent Relocation Plans
|--------------------------------------------------------------------------
*/

$result = $conn->query(
    "SELECT
        rp.id,
        rp.habitation_id,
        h.name AS habitation_name,
        rs.site_name,
        rp.population_to_relocate,
        rp.distance_km,
        rp.status,
        rp.created_at
     FROM relocation_plans rp
     INNER JOIN habitations h
        ON h.id = rp.habitation_id
     INNER JOIN relocation_sites rs
        ON rs.id = rp.relocation_site_id
     ORDER BY rp.created_at DESC
     LIMIT 5"
);

$recentRelocations = [];

while ($row = $result->fetch_assoc()) {
    $recentRelocations[] = $row;
}


/*
|--------------------------------------------------------------------------
| Final Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,

    "statistics" => [
        "total_habitations" => $totalHabitations,

        "high_risk" => (int) ($riskStats['high_risk'] ?? 0),

        "medium_risk" => (int) ($riskStats['medium_risk'] ?? 0),

        "low_risk" => (int) ($riskStats['low_risk'] ?? 0),

        "red_zones" => (int) ($riskStats['red_zones'] ?? 0),

       "total_relocation_plans" => $totalRelocationPlans,

"planned_relocation_plans" => (int) ($relocationStats['planned'] ?? 0),

"approved_relocation_plans" => (int) ($relocationStats['approved'] ?? 0),

"in_progress_relocation_plans" => (int) ($relocationStats['in_progress'] ?? 0),

"completed_relocation_plans" => (int) ($relocationStats['completed'] ?? 0),

"cancelled_relocation_plans" => (int) ($relocationStats['cancelled'] ?? 0),

        "total_relocation_sites" => (int) ($siteStats['total_sites'] ?? 0),

        "total_site_capacity" => (int) ($siteStats['total_capacity'] ?? 0),

        "available_site_capacity" => (int) ($siteStats['available_capacity'] ?? 0)
    ],

    "recent_assessments" => $recentAssessments,

    "recent_relocations" => $recentRelocations
]);

?>