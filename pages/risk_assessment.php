<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/../config/auth.php";
requireLogin();

require_once __DIR__ . "/../config/database.php";

$user = currentUser();

$message = "";
$error = "";


/*
|--------------------------------------------------------------------------
| SAVE RISK ASSESSMENT
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_assessment"])) {

    /*
    |--------------------------------------------------------------------------
    | Only ADMIN and ASSESSOR can create assessments
    |--------------------------------------------------------------------------
    */

    if (
        !isset($user["role"]) ||
        !in_array($user["role"], ["ADMIN", "ASSESSOR"], true)
    ) {

        $error = "You do not have permission to create risk assessments.";

    } else {

        $habitationId = (int) ($_POST["habitation_id"] ?? 0);

        $floodScore = (float) ($_POST["flood_score"] ?? 0);
        $landslideScore = (float) ($_POST["landslide_score"] ?? 0);
        $hazardHistoryScore = (float) ($_POST["hazard_history_score"] ?? 0);
        $vulnerabilityScore = (float) ($_POST["vulnerability_score"] ?? 0);

        $assessmentNotes = trim(
            $_POST["assessment_notes"] ?? ""
        );


        /*
        |--------------------------------------------------------------------------
        | Validate Scores
        |--------------------------------------------------------------------------
        */

        if ($habitationId <= 0) {

            $error = "Please select a habitation.";

        } elseif (
            $floodScore < 0 ||
            $floodScore > 100 ||
            $landslideScore < 0 ||
            $landslideScore > 100 ||
            $hazardHistoryScore < 0 ||
            $hazardHistoryScore > 100 ||
            $vulnerabilityScore < 0 ||
            $vulnerabilityScore > 100
        ) {

            $error = "All risk scores must be between 0 and 100.";

        } else {


            /*
            |--------------------------------------------------------------------------
            | Verify Habitation
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                SELECT
                    id,
                    name,
                    district,
                    population
                FROM habitations
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "i",
                $habitationId
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $habitation = $result->fetch_assoc();

            $stmt->close();


            if (!$habitation) {

                $error = "Selected habitation does not exist.";

            } else {


                /*
                |--------------------------------------------------------------------------
                | Calculate Risk Score
                |--------------------------------------------------------------------------
                */

                $riskScore = (
                    $floodScore +
                    $landslideScore +
                    $hazardHistoryScore +
                    $vulnerabilityScore
                ) / 4;


                /*
                |--------------------------------------------------------------------------
                | Determine Risk Level
                |--------------------------------------------------------------------------
                */

                if ($riskScore >= 70) {

                    $riskLevel = "HIGH";

                } elseif ($riskScore >= 40) {

                    $riskLevel = "MEDIUM";

                } else {

                    $riskLevel = "LOW";
                }


                /*
                |--------------------------------------------------------------------------
                | Determine Red Zone
                |--------------------------------------------------------------------------
                */

                $redZone = $riskScore >= 70 ? 1 : 0;


                /*
                |--------------------------------------------------------------------------
                | Determine Relocation Priority
                |--------------------------------------------------------------------------
                */

                if ($riskScore >= 85) {

                    $relocationPriority = "IMMEDIATE";

                } elseif ($riskScore >= 70) {

                    $relocationPriority = "SHORT-TERM";

                } elseif ($riskScore >= 50) {

                    $relocationPriority = "MEDIUM-TERM";

                } else {

                    $relocationPriority = "NONE";
                }


                /*
                |--------------------------------------------------------------------------
                | Save Assessment
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    INSERT INTO risk_assessments
                    (
                        habitation_id,
                        flood_score,
                        landslide_score,
                        hazard_history_score,
                        vulnerability_score,
                        risk_score,
                        risk_level,
                        red_zone,
                        relocation_priority,
                        assessment_notes
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");


                $stmt->bind_param(
                    "idddddsiss",
                    $habitationId,
                    $floodScore,
                    $landslideScore,
                    $hazardHistoryScore,
                    $vulnerabilityScore,
                    $riskScore,
                    $riskLevel,
                    $redZone,
                    $relocationPriority,
                    $assessmentNotes
                );


                if ($stmt->execute()) {

                    $message =
                        "Risk assessment saved successfully for " .
                        $habitation["name"] .
                        ".";

                } else {

                    $error =
                        "Unable to save the risk assessment: " .
                        $stmt->error;
                }


                $stmt->close();
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| FETCH HABITATIONS
|--------------------------------------------------------------------------
*/

$habitations = [];

$result = $conn->query("
    SELECT
        id,
        name,
        district,
        state,
        population
    FROM habitations
    ORDER BY name ASC
");


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $habitations[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| FETCH LATEST ASSESSMENT FOR EACH HABITATION
|--------------------------------------------------------------------------
*/

$assessments = [];

$result = $conn->query("
    SELECT
        ra.id,
        ra.habitation_id,
        ra.flood_score,
        ra.landslide_score,
        ra.hazard_history_score,
        ra.vulnerability_score,
        ra.risk_score,
        ra.risk_level,
        ra.red_zone,
        ra.relocation_priority,
        ra.assessment_notes,
        ra.assessed_at,

        h.name AS habitation_name,
        h.district

    FROM risk_assessments ra

    INNER JOIN habitations h
        ON h.id = ra.habitation_id

    INNER JOIN (
        SELECT
            habitation_id,
            MAX(id) AS latest_id
        FROM risk_assessments
        GROUP BY habitation_id
    ) latest

        ON latest.latest_id = ra.id

    ORDER BY ra.assessed_at DESC
");


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $assessments[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$totalAssessments = count($assessments);

$highRiskCount = 0;
$mediumRiskCount = 0;
$lowRiskCount = 0;
$redZoneCount = 0;
$immediateCount = 0;


foreach ($assessments as $assessment) {

    if ($assessment["risk_level"] === "HIGH") {
        $highRiskCount++;
    }

    if ($assessment["risk_level"] === "MEDIUM") {
        $mediumRiskCount++;
    }

    if ($assessment["risk_level"] === "LOW") {
        $lowRiskCount++;
    }

    if ((int) $assessment["red_zone"] === 1) {
        $redZoneCount++;
    }

    if ($assessment["relocation_priority"] === "IMMEDIATE") {
        $immediateCount++;
    }
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function riskClass(string $level): string
{
    return match ($level) {

        "HIGH" => "badge-high",

        "MEDIUM" => "badge-medium",

        "LOW" => "badge-low",

        default => ""
    };
}


function priorityClass(string $priority): string
{
    return match ($priority) {

        "IMMEDIATE" => "priority-immediate",

        "SHORT-TERM" => "priority-short",

        "MEDIUM-TERM" => "priority-medium",

        default => "priority-none"
    };
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Risk Assessment | RakshakGIS</title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        /* =========================================
           RISK ASSESSMENT PAGE
           ========================================= */

        .assessment-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 24px;

        }


        .assessment-heading h1 {

            font-size: 24px;

            font-weight: 800;

        }


        .assessment-heading p {

            margin-top: 5px;

            color: var(--muted);

            font-size: 12px;

        }


        .assessment-summary {

            display: grid;

            grid-template-columns:
                repeat(5, 1fr);

            gap: 14px;

            margin-bottom: 20px;

        }


        .assessment-stat {

            background: white;

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 17px;

        }


        .assessment-stat-label {

            font-size: 11px;

            color: var(--muted);

        }


        .assessment-stat-value {

            font-size: 25px;

            font-weight: 800;

            margin-top: 6px;

        }


        .assessment-stat.high
        .assessment-stat-value {

            color: #dc2626;

        }


        .assessment-stat.medium
        .assessment-stat-value {

            color: #d97706;

        }


        .assessment-stat.low
        .assessment-stat-value {

            color: #16a34a;

        }


        .assessment-stat.red
        .assessment-stat-value {

            color: #991b1b;

        }


        .assessment-layout {

            display: grid;

            grid-template-columns:
                390px 1fr;

            gap: 20px;

            align-items: start;

        }


        .assessment-form-card,
        .assessment-list-card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 12px;

            overflow: hidden;

        }


        .assessment-card-header {

            padding: 18px 20px;

            border-bottom: 1px solid var(--border);

        }


        .assessment-card-header h2 {

            font-size: 15px;

            font-weight: 800;

        }


        .assessment-card-header p {

            font-size: 11px;

            color: var(--muted);

            margin-top: 4px;

        }


        .assessment-card-body {

            padding: 20px;

        }


        .score-group {

            margin-top: 17px;

        }


        .score-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 7px;

        }


        .score-label {

            font-size: 12px;

            font-weight: 700;

        }


        .score-number {

            font-size: 12px;

            font-weight: 800;

            color: var(--primary);

        }


        .score-range {

            width: 100%;

            accent-color: var(--primary);

            cursor: pointer;

        }


        .score-help {

            display: flex;

            justify-content: space-between;

            font-size: 9px;

            color: #94a3b8;

            margin-top: 3px;

        }


        .risk-preview {

            margin-top: 22px;

            padding: 16px;

            background: #f8fafc;

            border: 1px solid var(--border);

            border-radius: 10px;

        }


        .risk-preview-label {

            font-size: 10px;

            color: var(--muted);

        }


        .risk-preview-score {

            font-size: 32px;

            font-weight: 800;

            margin-top: 4px;

        }


        .risk-preview-level {

            display: inline-flex;

            margin-top: 6px;

            padding: 5px 10px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 800;

        }


        .preview-low {

            background: #dcfce7;

            color: #15803d;

        }


        .preview-medium {

            background: #fef3c7;

            color: #b45309;

        }


        .preview-high {

            background: #fee2e2;

            color: #b91c1c;

        }


        .assessment-table-wrapper {

            overflow-x: auto;

        }


        .assessment-table {

            min-width: 850px;

        }


        .assessment-table td {

            vertical-align: middle;

        }


        .assessment-habitation {

            font-weight: 700;

        }


        .assessment-location {

            font-size: 10px;

            color: var(--muted);

            margin-top: 3px;

        }


        .assessment-score {

            font-size: 15px;

            font-weight: 800;

        }


        .priority-badge {

            display: inline-flex;

            padding: 5px 8px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 800;

        }


        .priority-immediate {

            background: #fee2e2;

            color: #b91c1c;

        }


        .priority-short {

            background: #ffedd5;

            color: #c2410c;

        }


        .priority-medium {

            background: #fef3c7;

            color: #a16207;

        }


        .priority-none {

            background: #f1f5f9;

            color: #64748b;

        }


        .assessment-notes {

            max-width: 220px;

            font-size: 11px;

            color: var(--muted);

            line-height: 1.5;

        }


        .empty-assessments {

            text-align: center;

            padding: 45px 20px;

            color: var(--muted);

        }


        .empty-assessments strong {

            display: block;

            color: var(--text);

            margin-bottom: 5px;

        }


        @media (max-width: 1150px) {

            .assessment-summary {

                grid-template-columns:
                    repeat(3, 1fr);

            }

            .assessment-layout {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 700px) {

            .assessment-summary {

                grid-template-columns: 1fr 1fr;

            }

            .assessment-header {

                align-items: flex-start;

                flex-direction: column;

            }

        }

    </style>

</head>


<body>


<div class="app">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="sidebar">


        <a
            href="../index.php"
            class="logo"
        >

            <div class="logo-icon">
                R
            </div>


            <div>

                <div class="logo-text">
                    RakshakGIS
                </div>

                <div class="logo-subtitle">
                    Disaster Risk & Relocation
                </div>

            </div>

        </a>


        <nav class="sidebar-nav">


            <div class="nav-section">
                Main
            </div>


            <a
                href="../index.php"
                class="nav-link"
            >
                <span class="nav-icon">⌂</span>
                Dashboard
            </a>


            <a
                href="habitations.php"
                class="nav-link"
            >
                <span class="nav-icon">⌖</span>
                Habitations
            </a>


            <a
                href="risk_assessment.php"
                class="nav-link active"
            >
                <span class="nav-icon">⚠</span>
                Risk Assessment
            </a>


            <a
                href="risk_map.php"
                class="nav-link"
            >
                <span class="nav-icon">◎</span>
                Risk Map
            </a>


            <div class="nav-section">
                Relocation
            </div>


            <a
                href="relocation_sites.php"
                class="nav-link"
            >
                <span class="nav-icon">⌂</span>
                Relocation Sites
            </a>


            <a
                href="relocation_plans.php"
                class="nav-link"
            >
                <span class="nav-icon">→</span>
                Relocation Plans
            </a>


            <div class="nav-section">
                System
            </div>


            <a
                href="reports.php"
                class="nav-link"
            >
                <span class="nav-icon">▤</span>
                Reports
            </a>


            <?php if (($user["role"] ?? "") === "ADMIN"): ?>

                <a
                    href="admin.php"
                    class="nav-link"
                >
                    <span class="nav-icon">⚙</span>
                    Administration
                </a>

            <?php endif; ?>


        </nav>


        <div class="sidebar-user">

            <div class="sidebar-user-inner">

                <div class="sidebar-avatar">

                    <?= strtoupper(
                        substr(
                            $user["name"] ?? "A",
                            0,
                            1
                        )
                    ) ?>

                </div>


                <div class="sidebar-user-info">

                    <div class="sidebar-user-name">

                        <?= htmlspecialchars(
                            $user["name"] ?? "Administrator"
                        ) ?>

                    </div>


                    <div class="sidebar-user-role">

                        <?= htmlspecialchars(
                            $user["role"] ?? "USER"
                        ) ?>

                    </div>

                </div>


                <a
                    href="../logout.php"
                    class="sidebar-logout"
                    title="Logout"
                >
                    ⎋
                </a>

            </div>

        </div>

    </aside>



    <!-- =====================================================
         MAIN
         ===================================================== -->

    <main class="main">


        <!-- TOPBAR -->

        <header class="topbar">

            <div>

                <div class="page-title">
                    Risk Assessment
                </div>

                <div class="page-subtitle">
                    Evaluate habitation-level disaster risk
                </div>

            </div>


            <div class="topbar-right">

                <div class="status">

                    <span class="status-dot"></span>

                    System Operational

                </div>

            </div>

        </header>



        <!-- CONTENT -->

        <div class="content">


            <!-- HEADER -->

            <div class="assessment-header">

                <div class="assessment-heading">

                    <h1>
                        Disaster Risk Assessment
                    </h1>

                    <p>
                        Assess flood, landslide, hazard history and
                        population vulnerability.
                    </p>

                </div>

            </div>



            <!-- ALERTS -->

            <?php if ($message): ?>

                <div class="alert alert-success">

                    <?= htmlspecialchars($message) ?>

                </div>

            <?php endif; ?>


            <?php if ($error): ?>

                <div class="alert alert-danger">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>



            <!-- SUMMARY -->

            <div class="assessment-summary">


                <div class="assessment-stat">

                    <div class="assessment-stat-label">
                        Assessments
                    </div>

                    <div class="assessment-stat-value">
                        <?= $totalAssessments ?>
                    </div>

                </div>


                <div class="assessment-stat high">

                    <div class="assessment-stat-label">
                        High Risk
                    </div>

                    <div class="assessment-stat-value">
                        <?= $highRiskCount ?>
                    </div>

                </div>


                <div class="assessment-stat medium">

                    <div class="assessment-stat-label">
                        Medium Risk
                    </div>

                    <div class="assessment-stat-value">
                        <?= $mediumRiskCount ?>
                    </div>

                </div>


                <div class="assessment-stat low">

                    <div class="assessment-stat-label">
                        Low Risk
                    </div>

                    <div class="assessment-stat-value">
                        <?= $lowRiskCount ?>
                    </div>

                </div>


                <div class="assessment-stat red">

                    <div class="assessment-stat-label">
                        Red Zones
                    </div>

                    <div class="assessment-stat-value">
                        <?= $redZoneCount ?>
                    </div>

                </div>


            </div>



            <!-- =================================================
                 FORM + ASSESSMENT LIST
                 ================================================= -->

            <div class="assessment-layout">


                <!-- =============================================
                     NEW ASSESSMENT FORM
                     ============================================= -->

                <div class="assessment-form-card">


                    <div class="assessment-card-header">

                        <h2>
                            New Assessment
                        </h2>

                        <p>
                            Create a risk assessment for a habitation.
                        </p>

                    </div>


                    <form
                        method="POST"
                        id="assessmentForm"
                    >


                        <div class="assessment-card-body">


                            <!-- HABITATION -->

                            <div class="form-group">

                                <label
                                    class="form-label"
                                    for="habitation_id"
                                >
                                    Habitation
                                </label>


                                <select
                                    name="habitation_id"
                                    id="habitation_id"
                                    class="form-control"
                                    required
                                >

                                    <option value="">
                                        Select habitation
                                    </option>


                                    <?php foreach ($habitations as $habitation): ?>

                                        <option
                                            value="<?= (int) $habitation["id"] ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $habitation["name"]
                                            ) ?>

                                            —
                                            <?= htmlspecialchars(
                                                $habitation["district"]
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>



                            <!-- FLOOD -->

                            <div class="score-group">

                                <div class="score-header">

                                    <label
                                        class="score-label"
                                        for="flood_score"
                                    >
                                        Flood Risk
                                    </label>


                                    <span
                                        class="score-number"
                                        id="floodValue"
                                    >
                                        0
                                    </span>

                                </div>


                                <input
                                    type="range"
                                    class="score-range"
                                    id="flood_score"
                                    name="flood_score"
                                    min="0"
                                    max="100"
                                    value="0"
                                >


                                <div class="score-help">

                                    <span>
                                        Low
                                    </span>

                                    <span>
                                        High
                                    </span>

                                </div>

                            </div>



                            <!-- LANDSLIDE -->

                            <div class="score-group">

                                <div class="score-header">

                                    <label
                                        class="score-label"
                                        for="landslide_score"
                                    >
                                        Landslide Risk
                                    </label>


                                    <span
                                        class="score-number"
                                        id="landslideValue"
                                    >
                                        0
                                    </span>

                                </div>


                                <input
                                    type="range"
                                    class="score-range"
                                    id="landslide_score"
                                    name="landslide_score"
                                    min="0"
                                    max="100"
                                    value="0"
                                >


                                <div class="score-help">

                                    <span>
                                        Low
                                    </span>

                                    <span>
                                        High
                                    </span>

                                </div>

                            </div>



                            <!-- HAZARD HISTORY -->

                            <div class="score-group">

                                <div class="score-header">

                                    <label
                                        class="score-label"
                                        for="hazard_history_score"
                                    >
                                        Hazard History
                                    </label>


                                    <span
                                        class="score-number"
                                        id="hazardValue"
                                    >
                                        0
                                    </span>

                                </div>


                                <input
                                    type="range"
                                    class="score-range"
                                    id="hazard_history_score"
                                    name="hazard_history_score"
                                    min="0"
                                    max="100"
                                    value="0"
                                >


                                <div class="score-help">

                                    <span>
                                        Low
                                    </span>

                                    <span>
                                        Low history
                                    </span>

                                    <span>
                                        High history
                                    </span>

                                </div>

                            </div>



                            <!-- VULNERABILITY -->

                            <div class="score-group">

                                <div class="score-header">

                                    <label
                                        class="score-label"
                                        for="vulnerability_score"
                                    >
                                        Population Vulnerability
                                    </label>


                                    <span
                                        class="score-number"
                                        id="vulnerabilityValue"
                                    >
                                        0
                                    </span>

                                </div>


                                <input
                                    type="range"
                                    class="score-range"
                                    id="vulnerability_score"
                                    name="vulnerability_score"
                                    min="0"
                                    max="100"
                                    value="0"
                                >


                                <div class="score-help">

                                    <span>
                                        Low
                                    </span>

                                    <span>
                                        High
                                    </span>

                                </div>

                            </div>



                            <!-- RISK PREVIEW -->

                            <div class="risk-preview">

                                <div class="risk-preview-label">

                                    Calculated Risk Score

                                </div>


                                <div
                                    class="risk-preview-score"
                                    id="riskPreviewScore"
                                >
                                    0.0
                                </div>


                                <span
                                    id="riskPreviewLevel"
                                    class="risk-preview-level preview-low"
                                >
                                    LOW
                                </span>

                            </div>



                            <!-- NOTES -->

                            <div
                                class="form-group"
                                style="margin-top:18px;"
                            >

                                <label
                                    class="form-label"
                                    for="assessment_notes"
                                >
                                    Assessment Notes
                                </label>


                                <textarea
                                    name="assessment_notes"
                                    id="assessment_notes"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Add observations, evidence or assessment notes..."
                                ></textarea>

                            </div>


                        </div>



                        <!-- FORM FOOTER -->

                        <div
                            style="
                                padding:16px 20px;
                                border-top:1px solid var(--border);
                            "
                        >

                            <?php if (
                                in_array(
                                    $user["role"] ?? "",
                                    ["ADMIN", "ASSESSOR"],
                                    true
                                )
                            ): ?>

                                <button
                                    type="submit"
                                    name="save_assessment"
                                    class="btn btn-primary"
                                    style="width:100%;"
                                >

                                    Save Risk Assessment

                                </button>

                            <?php else: ?>

                                <div class="alert alert-warning">

                                    Your account does not have permission
                                    to create assessments.

                                </div>

                            <?php endif; ?>

                        </div>


                    </form>

                </div>



                <!-- =============================================
                     ASSESSMENT LIST
                     ============================================= -->

                <div class="assessment-list-card">


                    <div class="assessment-card-header">

                        <h2>
                            Latest Assessments
                        </h2>

                        <p>
                            Most recent assessment for each habitation.
                        </p>

                    </div>


                    <?php if (empty($assessments)): ?>

                        <div class="empty-assessments">

                            <strong>
                                No risk assessments yet
                            </strong>

                            Create the first assessment using
                            the form on the left.

                        </div>

                    <?php else: ?>


                        <div class="assessment-table-wrapper">

                            <table class="assessment-table">

                                <thead>

                                    <tr>

                                        <th>
                                            Habitation
                                        </th>

                                        <th>
                                            Risk Score
                                        </th>

                                        <th>
                                            Level
                                        </th>

                                        <th>
                                            Red Zone
                                        </th>

                                        <th>
                                            Priority
                                        </th>

                                        <th>
                                            Assessed
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                <?php foreach ($assessments as $assessment): ?>

                                    <tr>


                                        <td>

                                            <div class="assessment-habitation">

                                                <?= htmlspecialchars(
                                                    $assessment["habitation_name"]
                                                ) ?>

                                            </div>


                                            <div class="assessment-location">

                                                <?= htmlspecialchars(
                                                    $assessment["district"]
                                                ) ?>

                                            </div>

                                        </td>



                                        <td>

                                            <div class="assessment-score">

                                                <?= number_format(
                                                    (float) $assessment["risk_score"],
                                                    1
                                                ) ?>

                                            </div>

                                        </td>



                                        <td>

                                            <span
                                                class="badge <?= riskClass(
                                                    $assessment["risk_level"]
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $assessment["risk_level"]
                                                ) ?>

                                            </span>

                                        </td>



                                        <td>

                                            <?php if (
                                                (int) $assessment["red_zone"] === 1
                                            ): ?>

                                                <span class="badge badge-high">
                                                    YES
                                                </span>

                                            <?php else: ?>

                                                <span class="badge badge-low">
                                                    NO
                                                </span>

                                            <?php endif; ?>

                                        </td>



                                        <td>

                                            <span
                                                class="priority-badge <?= priorityClass(
                                                    $assessment["relocation_priority"]
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $assessment["relocation_priority"]
                                                ) ?>

                                            </span>

                                        </td>



                                        <td>

                                            <span
                                                style="
                                                    font-size:11px;
                                                    color:var(--muted);
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    date(
                                                        "d M Y",
                                                        strtotime(
                                                            $assessment["assessed_at"]
                                                        )
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                    </tr>

                                <?php endforeach; ?>


                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>


                </div>


            </div>


        </div>

    </main>

</div>



<script>

/*
|--------------------------------------------------------------------------
| Risk Score Preview
|--------------------------------------------------------------------------
*/

const flood =
    document.getElementById("flood_score");

const landslide =
    document.getElementById("landslide_score");

const hazard =
    document.getElementById("hazard_history_score");

const vulnerability =
    document.getElementById("vulnerability_score");


const floodValue =
    document.getElementById("floodValue");

const landslideValue =
    document.getElementById("landslideValue");

const hazardValue =
    document.getElementById("hazardValue");

const vulnerabilityValue =
    document.getElementById("vulnerabilityValue");


const riskScore =
    document.getElementById("riskPreviewScore");

const riskLevel =
    document.getElementById("riskPreviewLevel");


function updateRiskPreview() {

    const floodScore =
        Number(flood.value);

    const landslideScore =
        Number(landslide.value);

    const hazardScore =
        Number(hazard.value);

    const vulnerabilityScore =
        Number(vulnerability.value);


    floodValue.textContent =
        floodScore;

    landslideValue.textContent =
        landslideScore;

    hazardValue.textContent =
        hazardScore;

    vulnerabilityValue.textContent =
        vulnerabilityScore;


    const total =
        (
            floodScore +
            landslideScore +
            hazardScore +
            vulnerabilityScore
        ) / 4;


    riskScore.textContent =
        total.toFixed(1);


    let level = "LOW";

    let className = "preview-low";


    if (total >= 70) {

        level = "HIGH";

        className = "preview-high";

    } else if (total >= 40) {

        level = "MEDIUM";

        className = "preview-medium";

    }


    riskLevel.textContent =
        level;


    riskLevel.className =
        "risk-preview-level " + className;

}


flood.addEventListener(
    "input",
    updateRiskPreview
);

landslide.addEventListener(
    "input",
    updateRiskPreview
);

hazard.addEventListener(
    "input",
    updateRiskPreview
);

vulnerability.addEventListener(
    "input",
    updateRiskPreview
);


updateRiskPreview();

</script>


</body>

</html>