<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../backend/risk_engine.php';


/*
|--------------------------------------------------------------------------
| Get habitation ID
|--------------------------------------------------------------------------
| Accept:
| 1. GET: ?habitation_id=1
| 2. POST form: habitation_id=1
| 3. POST JSON: {"habitation_id":1}
|--------------------------------------------------------------------------
*/

$habitationId = null;


/* GET */
if (isset($_GET['habitation_id'])) {
    $habitationId = $_GET['habitation_id'];
}


/* POST form */
if ($habitationId === null && isset($_POST['habitation_id'])) {
    $habitationId = $_POST['habitation_id'];
}


/* POST JSON */
if ($habitationId === null) {

    $rawInput = file_get_contents("php://input");

    if (!empty($rawInput)) {

        $jsonData = json_decode($rawInput, true);

        if (is_array($jsonData) && isset($jsonData['habitation_id'])) {
            $habitationId = $jsonData['habitation_id'];
        }
    }
}


/*
|--------------------------------------------------------------------------
| Validate habitation ID
|--------------------------------------------------------------------------
*/

if ($habitationId === null || !is_numeric($habitationId)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid habitation_id is required"
    ]);

    exit;
}


$habitationId = (int) $habitationId;


/*
|--------------------------------------------------------------------------
| Get habitation data
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        id,
        name,
        flood_risk,
        landslide_risk,
        hazard_history,
        population_vulnerability
     FROM habitations
     WHERE id = ?"
);

if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare habitation query",
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
        "message" => "Habitation not found"
    ]);

    $stmt->close();

    exit;
}


$habitation = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| Calculate risk
|--------------------------------------------------------------------------
*/

$risk = calculateRisk(
    (float) $habitation['flood_risk'],
    (float) $habitation['landslide_risk'],
    (float) $habitation['hazard_history'],
    (float) $habitation['population_vulnerability']
);


/*
|--------------------------------------------------------------------------
| Determine Red Zone
|--------------------------------------------------------------------------
*/

$redZone = ($risk['level'] === 'HIGH') ? 1 : 0;


/*
|--------------------------------------------------------------------------
| Determine Relocation Priority
|--------------------------------------------------------------------------
*/

if ($risk['level'] === 'HIGH') {

    $relocationPriority = "IMMEDIATE";

} elseif ($risk['level'] === 'MEDIUM') {

    $relocationPriority = "SHORT-TERM";

} else {

    $relocationPriority = "NONE";
}


/*
|--------------------------------------------------------------------------
| Save Risk Assessment
|--------------------------------------------------------------------------
*/

$insert = $conn->prepare(
    "INSERT INTO risk_assessments
    (
        habitation_id,
        flood_score,
        landslide_score,
        hazard_history_score,
        vulnerability_score,
        risk_score,
        risk_level,
        red_zone,
        relocation_priority
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);


if (!$insert) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare risk assessment query",
        "error" => $conn->error
    ]);

    exit;
}


$insert->bind_param(
    "idddddsis",
    $habitationId,
    $risk['components']['flood_risk'],
    $risk['components']['landslide_risk'],
    $risk['components']['hazard_history'],
    $risk['components']['population_vulnerability'],
    $risk['score'],
    $risk['level'],
    $redZone,
    $relocationPriority
);


if (!$insert->execute()) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to save risk assessment",
        "error" => $insert->error
    ]);

    $insert->close();

    exit;
}


$assessmentId = $insert->insert_id;

$insert->close();


/*
|--------------------------------------------------------------------------
| Return Result
|--------------------------------------------------------------------------
*/

echo json_encode([

    "success" => true,

    "message" => "Risk assessment calculated successfully",

    "assessment_id" => $assessmentId,

    "habitation" => [
        "id" => $habitation['id'],
        "name" => $habitation['name']
    ],

    "risk" => [
        "score" => $risk['score'],
        "level" => $risk['level'],
        "red_zone" => $redZone,
        "relocation_priority" => $relocationPriority
    ],

    "components" => $risk['components']

]);

?>