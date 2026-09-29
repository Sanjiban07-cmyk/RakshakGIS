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
| DISTANCE CALCULATOR
|--------------------------------------------------------------------------
| Haversine formula
| Returns distance in kilometres.
|--------------------------------------------------------------------------
*/

function calculateDistance(
    float $lat1,
    float $lon1,
    float $lat2,
    float $lon2
): float {

    $earthRadius = 6371;

    $latDifference =
        deg2rad($lat2 - $lat1);

    $lonDifference =
        deg2rad($lon2 - $lon1);

    $a =
        sin($latDifference / 2) ** 2
        +
        cos(deg2rad($lat1))
        *
        cos(deg2rad($lat2))
        *
        sin($lonDifference / 2) ** 2;

    $c =
        2 * atan2(
            sqrt($a),
            sqrt(1 - $a)
        );

    return $earthRadius * $c;
}


/*
|--------------------------------------------------------------------------
| SAFETY SCORE
|--------------------------------------------------------------------------
*/

function safetyScore(string $level): int
{
    return match ($level) {

        "HIGH" => 60,

        "MEDIUM" => 40,

        default => 20
    };
}


/*
|--------------------------------------------------------------------------
| GET HABITATIONS
|--------------------------------------------------------------------------
*/

$habitations = [];

$habitationResult = $conn->query("
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
            SELECT ra2.id
            FROM risk_assessments ra2
            WHERE ra2.habitation_id = h.id
            ORDER BY ra2.assessed_at DESC, ra2.id DESC
            LIMIT 1
        )

    ORDER BY
        CASE
            WHEN ra.relocation_priority = 'IMMEDIATE' THEN 1
            WHEN ra.relocation_priority = 'SHORT-TERM' THEN 2
            WHEN ra.relocation_priority = 'MEDIUM-TERM' THEN 3
            ELSE 4
        END,
        h.name
");


if ($habitationResult) {

    while ($row = $habitationResult->fetch_assoc()) {

        $habitations[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| GET RELOCATION SITES
|--------------------------------------------------------------------------
*/

$sites = [];

$siteResult = $conn->query("
    SELECT
        *,
        GREATEST(
            total_capacity - occupied_capacity,
            0
        ) AS available_capacity

    FROM relocation_sites

    ORDER BY site_name
");


if ($siteResult) {

    while ($row = $siteResult->fetch_assoc()) {

        $sites[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| FIND RECOMMENDED SITE
|--------------------------------------------------------------------------
*/

function recommendSite(
    array $habitation,
    array $sites
): ?array {

    if (
        $habitation["latitude"] === null
        ||
        $habitation["longitude"] === null
    ) {

        return null;
    }


    $population =
        (int) $habitation["population"];


    $candidates = [];


    foreach ($sites as $site) {

        $available =
            (int) $site["available_capacity"];


        if (
            $available <= 0
            ||
            $site["latitude"] === null
            ||
            $site["longitude"] === null
        ) {

            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | Calculate actual distance
        |--------------------------------------------------------------------------
        */

        $distance =
            calculateDistance(
                (float) $habitation["latitude"],
                (float) $habitation["longitude"],
                (float) $site["latitude"],
                (float) $site["longitude"]
            );


        /*
        |--------------------------------------------------------------------------
        | Capacity score
        |--------------------------------------------------------------------------
        */

        if ($available >= $population) {

            $capacityScore = 25;

        } else {

            /*
             * Site does not have enough capacity.
             * Keep it as a fallback but reduce its score.
             */

            $capacityScore = 5;
        }


        /*
        |--------------------------------------------------------------------------
        | Distance score
        |--------------------------------------------------------------------------
        */

        if ($distance <= 5) {

            $distanceScore = 15;

        } elseif ($distance <= 10) {

            $distanceScore = 12;

        } elseif ($distance <= 20) {

            $distanceScore = 8;

        } elseif ($distance <= 50) {

            $distanceScore = 4;

        } else {

            $distanceScore = 1;
        }


        /*
        |--------------------------------------------------------------------------
        | Safety score
        |--------------------------------------------------------------------------
        */

        $safety =
            safetyScore(
                $site["safety_level"]
            );


        /*
        |--------------------------------------------------------------------------
        | Total recommendation score
        |--------------------------------------------------------------------------
        */

        $totalScore =
            $safety
            +
            $capacityScore
            +
            $distanceScore;


        $candidates[] = [

            "site" => $site,

            "distance" => $distance,

            "score" => $totalScore,

            "capacity_score" => $capacityScore,

            "distance_score" => $distanceScore,

            "safety_score" => $safety,

            "enough_capacity" =>
                $available >= $population
        ];
    }


    if (empty($candidates)) {

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Prefer sites with enough capacity
    |--------------------------------------------------------------------------
    */

    usort(
        $candidates,
        function ($a, $b) {

            if (
                $a["enough_capacity"]
                !==
                $b["enough_capacity"]
            ) {

                return
                    $a["enough_capacity"]
                    ? -1
                    : 1;
            }


            if (
                $a["score"]
                !==
                $b["score"]
            ) {

                return
                    $b["score"]
                    <=>
                    $a["score"];
            }


            return
                $a["distance"]
                <=>
                $b["distance"];
        }
    );


    return $candidates[0];
}


/*
|--------------------------------------------------------------------------
| CREATE RELOCATION PLAN
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["create_plan"])
) {

    if (($user["role"] ?? "") !== "ADMIN") {

        $error =
            "Only administrators can create relocation plans.";

    } else {

        $habitationId =
            (int) ($_POST["habitation_id"] ?? 0);

        $siteId =
            (int) ($_POST["site_id"] ?? 0);


        /*
        |--------------------------------------------------------------------------
        | Find habitation
        |--------------------------------------------------------------------------
        */

        $selectedHabitation = null;

        foreach ($habitations as $habitation) {

            if (
                (int) $habitation["id"]
                ===
                $habitationId
            ) {

                $selectedHabitation =
                    $habitation;

                break;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Find site
        |--------------------------------------------------------------------------
        */

        $selectedSite = null;

        foreach ($sites as $site) {

            if (
                (int) $site["id"]
                ===
                $siteId
            ) {

                $selectedSite =
                    $site;

                break;
            }
        }


        if (
            !$selectedHabitation
            ||
            !$selectedSite
        ) {

            $error =
                "Please select a valid habitation and relocation site.";

        } elseif (
            $selectedHabitation["latitude"] === null
            ||
            $selectedHabitation["longitude"] === null
            ||
            $selectedSite["latitude"] === null
            ||
            $selectedSite["longitude"] === null
        ) {

            $error =
                "Both habitation and relocation site must have coordinates.";

        } else {

            $population =
                (int)
                $selectedHabitation["population"];


            $available =
                (int)
                $selectedSite["available_capacity"];


            if ($available <= 0) {

                $error =
                    "The selected relocation site has no available capacity.";

            } elseif ($available < $population) {

                $error =
                    "The selected site does not have enough capacity for "
                    .
                    number_format($population)
                    .
                    " people.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Actual distance
                |--------------------------------------------------------------------------
                */

                $distance =
                    calculateDistance(
                        (float)
                        $selectedHabitation["latitude"],

                        (float)
                        $selectedHabitation["longitude"],

                        (float)
                        $selectedSite["latitude"],

                        (float)
                        $selectedSite["longitude"]
                    );


                /*
                |--------------------------------------------------------------------------
                | Recommendation reason
                |--------------------------------------------------------------------------
                */

                $riskLevel =
                    $selectedHabitation["risk_level"]
                    ??
                    "UNASSESSED";


                $priority =
                    $selectedHabitation["relocation_priority"]
                    ??
                    "NONE";


                $reason =
                    "Selected based on "
                    .
                    $selectedSite["safety_level"]
                    .
                    " safety, sufficient available capacity of "
                    .
                    number_format($available)
                    .
                    " people, and calculated distance of "
                    .
                    number_format($distance, 2)
                    .
                    " km from "
                    .
                    $selectedHabitation["name"]
                    .
                    ". Current habitation risk is "
                    .
                    $riskLevel
                    .
                    " with relocation priority "
                    .
                    $priority
                    .
                    ".";


                /*
                |--------------------------------------------------------------------------
                | CREATE PLAN
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    INSERT INTO relocation_plans
                    (
                        habitation_id,
                        relocation_site_id,
                        population_to_relocate,
                        available_capacity,
                        distance_km,
                        recommendation_reason,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, 'PLANNED')
                ");


                $stmt->bind_param(
                    "iiiids",
                    $habitationId,
                    $siteId,
                    $population,
                    $available,
                    $distance,
                    $reason
                );


                if ($stmt->execute()) {

                    $message =
                        "Relocation plan created successfully.";

                } else {

                    $error =
                        "Unable to create relocation plan: "
                        .
                        $stmt->error;
                }


                $stmt->close();
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE PLAN STATUS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["update_status"])
) {

    if (($user["role"] ?? "") !== "ADMIN") {

        $error =
            "Only administrators can update relocation plans.";

    } else {

        $planId =
            (int) ($_POST["plan_id"] ?? 0);

        $status =
            strtoupper(
                trim(
                    $_POST["status"] ?? ""
                )
            );


        $allowedStatuses = [

            "PLANNED",

            "APPROVED",

            "IN_PROGRESS",

            "COMPLETED",

            "CANCELLED"
        ];


        if (
            $planId <= 0
            ||
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            $error =
                "Invalid relocation plan status.";

        } else {

            $stmt = $conn->prepare("
                UPDATE relocation_plans
                SET status = ?
                WHERE id = ?
            ");


            $stmt->bind_param(
                "si",
                $status,
                $planId
            );


            if ($stmt->execute()) {

                $message =
                    "Relocation plan status updated.";

            } else {

                $error =
                    "Unable to update plan status.";
            }


            $stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET EXISTING PLANS
|--------------------------------------------------------------------------
*/

$plans = [];


$planResult = $conn->query("
    SELECT

        rp.*,

        h.name AS habitation_name,
        h.district AS habitation_district,
        h.population AS habitation_population,

        ra.risk_score,
        ra.risk_level,
        ra.red_zone,
        ra.relocation_priority,

        rs.site_name,
        rs.district AS site_district,
        rs.safety_level

    FROM relocation_plans rp

    INNER JOIN habitations h
        ON h.id = rp.habitation_id

    INNER JOIN relocation_sites rs
        ON rs.id = rp.relocation_site_id

    LEFT JOIN risk_assessments ra
        ON ra.id = (
            SELECT ra2.id
            FROM risk_assessments ra2
            WHERE ra2.habitation_id = h.id
            ORDER BY ra2.assessed_at DESC, ra2.id DESC
            LIMIT 1
        )

    ORDER BY
        CASE rp.status
            WHEN 'IN_PROGRESS' THEN 1
            WHEN 'APPROVED' THEN 2
            WHEN 'PLANNED' THEN 3
            WHEN 'COMPLETED' THEN 4
            WHEN 'CANCELLED' THEN 5
        END,

        rp.created_at DESC
");


if ($planResult) {

    while ($row = $planResult->fetch_assoc()) {

        $plans[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$totalPlans = count($plans);

$plannedCount = 0;
$approvedCount = 0;
$inProgressCount = 0;
$completedCount = 0;


foreach ($plans as $plan) {

    switch ($plan["status"]) {

        case "PLANNED":
            $plannedCount++;
            break;

        case "APPROVED":
            $approvedCount++;
            break;

        case "IN_PROGRESS":
            $inProgressCount++;
            break;

        case "COMPLETED":
            $completedCount++;
            break;
    }
}


/*
|--------------------------------------------------------------------------
| RECOMMENDATIONS
|--------------------------------------------------------------------------
*/

$recommendations = [];


foreach ($habitations as $habitation) {

    $recommendation =
        recommendSite(
            $habitation,
            $sites
        );


    if ($recommendation) {

        $recommendations[
            $habitation["id"]
        ] = $recommendation;
    }
}


/*
|--------------------------------------------------------------------------
| CSS HELPERS
|--------------------------------------------------------------------------
*/

function riskBadgeClass(
    ?string $risk
): string {

    return match ($risk) {

        "HIGH" => "risk-high",

        "MEDIUM" => "risk-medium",

        "LOW" => "risk-low",

        default => "risk-none"
    };
}


function statusBadgeClass(
    string $status
): string {

    return match ($status) {

        "APPROVED" => "status-approved",

        "IN_PROGRESS" => "status-progress",

        "COMPLETED" => "status-completed",

        "CANCELLED" => "status-cancelled",

        default => "status-planned"
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

<title>
    Relocation Plans | RakshakGIS
</title>


<link
    rel="stylesheet"
    href="../assets/css/style.css"
>


<style>

/* =========================================
   PAGE
   ========================================= */

.plan-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 20px;

    margin-bottom: 22px;
}


.plan-heading h1 {

    font-size: 24px;

    font-weight: 800;
}


.plan-heading p {

    margin-top: 5px;

    font-size: 12px;

    color: var(--muted);
}


/* =========================================
   SUMMARY
   ========================================= */

.plan-summary {

    display: grid;

    grid-template-columns:
        repeat(5, 1fr);

    gap: 14px;

    margin-bottom: 22px;
}


.plan-stat {

    background: white;

    border: 1px solid var(--border);

    border-radius: 12px;

    padding: 17px;
}


.plan-stat-label {

    font-size: 11px;

    color: var(--muted);
}


.plan-stat-value {

    font-size: 24px;

    font-weight: 800;

    margin-top: 5px;
}


.plan-stat.approved
.plan-stat-value {

    color: #2563eb;
}


.plan-stat.progress
.plan-stat-value {

    color: #d97706;
}


.plan-stat.completed
.plan-stat-value {

    color: #16a34a;
}


/* =========================================
   RECOMMENDATIONS
   ========================================= */

.section-title {

    font-size: 16px;

    font-weight: 800;

    margin-bottom: 5px;
}


.section-subtitle {

    font-size: 11px;

    color: var(--muted);

    margin-bottom: 15px;
}


.recommendation-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;

    margin-bottom: 25px;
}


.recommendation-card {

    background: white;

    border: 1px solid var(--border);

    border-radius: 12px;

    padding: 18px;

    transition: 0.2s ease;
}


.recommendation-card:hover {

    transform: translateY(-2px);

    box-shadow:
        0 8px 25px
        rgba(15,23,42,0.07);
}


.recommendation-top {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 10px;
}


.habitation-name {

    font-size: 14px;

    font-weight: 800;
}


.habitation-location {

    font-size: 10px;

    color: var(--muted);

    margin-top: 3px;
}


.risk-badge {

    display: inline-flex;

    padding: 4px 8px;

    border-radius: 999px;

    font-size: 9px;

    font-weight: 800;
}


.risk-high {

    background: #fee2e2;

    color: #b91c1c;
}


.risk-medium {

    background: #fef3c7;

    color: #b45309;
}


.risk-low {

    background: #dcfce7;

    color: #15803d;
}


.risk-none {

    background: #f1f5f9;

    color: #64748b;
}


.recommendation-arrow {

    text-align: center;

    color: #94a3b8;

    font-size: 14px;

    margin: 10px 0;
}


.recommended-site {

    background: #eff6ff;

    border: 1px solid #dbeafe;

    border-radius: 9px;

    padding: 12px;
}


.recommended-label {

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: .5px;

    color: #64748b;
}


.recommended-name {

    font-size: 13px;

    font-weight: 800;

    color: #1d4ed8;

    margin-top: 3px;
}


.recommendation-details {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 8px;

    margin-top: 12px;
}


.detail-box {

    background: #f8fafc;

    border-radius: 7px;

    padding: 8px;
}


.detail-label {

    font-size: 8px;

    color: var(--muted);
}


.detail-value {

    font-size: 11px;

    font-weight: 800;

    margin-top: 3px;
}


.recommendation-reason {

    font-size: 10px;

    color: var(--muted);

    line-height: 1.5;

    margin-top: 11px;
}


/* =========================================
   PLANS TABLE
   ========================================= */

.plans-card {

    background: white;

    border: 1px solid var(--border);

    border-radius: 12px;

    overflow: hidden;
}


.plan-habitation {

    font-weight: 800;
}


.plan-location {

    font-size: 10px;

    color: var(--muted);

    margin-top: 3px;
}


.plan-site {

    font-weight: 700;

    color: #1d4ed8;
}


.plan-site-location {

    font-size: 10px;

    color: var(--muted);

    margin-top: 3px;
}


.plan-risk {

    display: inline-flex;

    padding: 4px 8px;

    border-radius: 999px;

    font-size: 9px;

    font-weight: 800;
}


.status-badge {

    display: inline-flex;

    padding: 5px 9px;

    border-radius: 999px;

    font-size: 9px;

    font-weight: 800;
}


.status-planned {

    background: #f1f5f9;

    color: #475569;
}


.status-approved {

    background: #dbeafe;

    color: #1d4ed8;
}


.status-progress {

    background: #fef3c7;

    color: #b45309;
}


.status-completed {

    background: #dcfce7;

    color: #15803d;
}


.status-cancelled {

    background: #fee2e2;

    color: #b91c1c;
}


.distance {

    font-weight: 800;
}


.distance-unit {

    font-size: 10px;

    color: var(--muted);
}


.status-form select {

    border: 1px solid var(--border);

    border-radius: 7px;

    padding: 6px;

    font-size: 10px;

    background: white;

    outline: none;
}


/* =========================================
   MODAL
   ========================================= */

.modal {

    position: fixed;

    inset: 0;

    display: none;

    align-items: center;

    justify-content: center;

    padding: 20px;

    background:
        rgba(15,23,42,.55);

    backdrop-filter: blur(3px);

    z-index: 5000;
}


.modal.active {

    display: flex;
}


.modal-card {

    width: 100%;

    max-width: 650px;

    max-height: 90vh;

    overflow-y: auto;

    background: white;

    border-radius: 15px;

    box-shadow:
        0 25px 60px
        rgba(15,23,42,.25);
}


.modal-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 20px 22px;

    border-bottom: 1px solid var(--border);
}


.modal-header h2 {

    font-size: 17px;

    font-weight: 800;
}


.modal-close {

    width: 32px;

    height: 32px;

    border: none;

    border-radius: 8px;

    background: #f1f5f9;

    cursor: pointer;

    font-size: 18px;
}


.modal-body {

    padding: 22px;
}


.modal-footer {

    display: flex;

    justify-content: flex-end;

    gap: 10px;

    padding: 16px 22px;

    border-top: 1px solid var(--border);
}


.selection-info {

    margin-top: 12px;

    padding: 12px;

    background: #f8fafc;

    border-radius: 9px;

    font-size: 11px;

    color: var(--muted);

    line-height: 1.6;
}


@media (max-width: 1100px) {

    .plan-summary {

        grid-template-columns:
            repeat(3, 1fr);
    }

    .recommendation-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media (max-width: 700px) {

    .plan-header {

        flex-direction: column;
    }

    .plan-summary {

        grid-template-columns: 1fr 1fr;
    }

    .recommendation-grid {

        grid-template-columns: 1fr;
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


<a href="../index.php" class="logo">
    <div class="logo-icon">
        <span>R</span>
    </div>

    <div class="logo-brand">
        <div class="logo-text">
            RAKSHAK <span>GIS</span>
        </div>

        <div class="logo-subtitle">
            DISASTER RISK INTELLIGENCE
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
    class="nav-link"
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
    class="nav-link active"
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


<header class="topbar">

<div>

<div class="page-title">
    Relocation Plans
</div>

<div class="page-subtitle">
    Plan and monitor safe population relocation
</div>

</div>


<div class="topbar-right">

<div class="status">

<span class="status-dot"></span>

System Operational

</div>

</div>

</header>


<div class="content">


<!-- PAGE HEADER -->

<div class="plan-header">

<div class="plan-heading">

<h1>
    Relocation Planning
</h1>

<p>
    Connect high-risk habitations with suitable relocation sites
    using capacity, safety and geographic distance.
</p>

</div>


<?php if (($user["role"] ?? "") === "ADMIN"): ?>

<button
    class="btn btn-primary"
    onclick="openPlanModal()"
>
    + Create Relocation Plan
</button>

<?php endif; ?>

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

<div class="plan-summary">


<div class="plan-stat">

<div class="plan-stat-label">
    Total Plans
</div>

<div class="plan-stat-value">
    <?= $totalPlans ?>
</div>

</div>


<div class="plan-stat">

<div class="plan-stat-label">
    Planned
</div>

<div class="plan-stat-value">
    <?= $plannedCount ?>
</div>

</div>


<div class="plan-stat approved">

<div class="plan-stat-label">
    Approved
</div>

<div class="plan-stat-value">
    <?= $approvedCount ?>
</div>

</div>


<div class="plan-stat progress">

<div class="plan-stat-label">
    In Progress
</div>

<div class="plan-stat-value">
    <?= $inProgressCount ?>
</div>

</div>


<div class="plan-stat completed">

<div class="plan-stat-label">
    Completed
</div>

<div class="plan-stat-value">
    <?= $completedCount ?>
</div>

</div>


</div>


<!-- =====================================================
     RECOMMENDATIONS
     ===================================================== -->

<div class="section-title">
    Relocation Recommendations
</div>

<div class="section-subtitle">
    Recommended sites are calculated from safety,
    available capacity and actual geographic distance.
</div>


<div class="recommendation-grid">


<?php foreach ($habitations as $habitation): ?>


<?php

$recommendation =
    $recommendations[
        $habitation["id"]
    ]
    ??
    null;

?>


<div class="recommendation-card">


<div class="recommendation-top">

<div>

<div class="habitation-name">

<?= htmlspecialchars(
    $habitation["name"]
) ?>

</div>

<div class="habitation-location">

<?= htmlspecialchars(
    $habitation["district"]
) ?>

·

Population:
<?= number_format(
    (int)
    $habitation["population"]
) ?>

</div>

</div>


<span
    class="risk-badge <?= riskBadgeClass(
        $habitation["risk_level"]
    ) ?>"
>

<?= htmlspecialchars(
    $habitation["risk_level"]
    ??
    "UNASSESSED"
) ?>

</span>

</div>


<div class="recommendation-arrow">
    ↓ Recommended relocation site
</div>


<?php if ($recommendation): ?>


<div class="recommended-site">


<div class="recommended-label">
    Recommended Site
</div>


<div class="recommended-name">

<?= htmlspecialchars(
    $recommendation["site"]["site_name"]
) ?>

</div>


<div class="recommendation-details">


<div class="detail-box">

<div class="detail-label">
    Distance
</div>

<div class="detail-value">

<?= number_format(
    $recommendation["distance"],
    2
) ?>

km

</div>

</div>


<div class="detail-box">

<div class="detail-label">
    Available
</div>

<div class="detail-value">

<?= number_format(
    (int)
    $recommendation[
        "site"
    ][
        "available_capacity"
    ]
) ?>

</div>

</div>


<div class="detail-box">

<div class="detail-label">
    Safety
</div>

<div class="detail-value">

<?= htmlspecialchars(
    $recommendation[
        "site"
    ][
        "safety_level"
    ]
) ?>

</div>

</div>


</div>


<div class="recommendation-reason">

Recommendation score:
<strong>
    <?= $recommendation["score"] ?>
</strong>

<br>

Priority:
<strong>
    <?= htmlspecialchars(
        $habitation[
            "relocation_priority"
        ]
        ??
        "NONE"
    ) ?>
</strong>

</div>


</div>


<?php else: ?>


<div class="selection-info">

No suitable relocation site could be calculated.
Make sure the habitation and relocation sites have
valid coordinates and available capacity.

</div>


<?php endif; ?>


</div>


<?php endforeach; ?>


</div>


<!-- =====================================================
     EXISTING PLANS
     ===================================================== -->

<div class="section-title">
    Existing Relocation Plans
</div>

<div class="section-subtitle">
    Track the progress of population relocation plans.
</div>


<div class="plans-card">


<div class="table-wrapper">

<table>


<thead>

<tr>

<th>
    Habitation
</th>

<th>
    Risk
</th>

<th>
    Relocation Site
</th>

<th>
    Population
</th>

<th>
    Distance
</th>

<th>
    Priority
</th>

<th>
    Status
</th>

<?php if (($user["role"] ?? "") === "ADMIN"): ?>

<th>
    Update
</th>

<?php endif; ?>

</tr>

</thead>


<tbody>


<?php if (empty($plans)): ?>


<tr>

<td
    colspan="8"
    style="
        text-align:center;
        padding:45px;
        color:#64748b;
    "
>

No relocation plans have been created yet.

</td>

</tr>


<?php else: ?>


<?php foreach ($plans as $plan): ?>


<tr>


<td>

<div class="plan-habitation">

<?= htmlspecialchars(
    $plan["habitation_name"]
) ?>

</div>


<div class="plan-location">

<?= htmlspecialchars(
    $plan["habitation_district"]
) ?>

</div>

</td>


<td>

<span
    class="plan-risk <?= riskBadgeClass(
        $plan["risk_level"]
    ) ?>"
>

<?= htmlspecialchars(
    $plan["risk_level"]
    ??
    "UNASSESSED"
) ?>

</span>

</td>


<td>

<div class="plan-site">

<?= htmlspecialchars(
    $plan["site_name"]
) ?>

</div>


<div class="plan-site-location">

<?= htmlspecialchars(
    $plan["site_district"]
) ?>

·

<?= htmlspecialchars(
    $plan["safety_level"]
) ?>

</div>

</td>


<td>

<strong>

<?= number_format(
    (int)
    $plan[
        "population_to_relocate"
    ]
) ?>

</strong>

</td>


<td>

<span class="distance">

<?= number_format(
    (float)
    $plan["distance_km"],
    2
) ?>

</span>

<span class="distance-unit">
    km
</span>

</td>


<td>

<?= htmlspecialchars(
    $plan[
        "relocation_priority"
    ]
    ??
    "NONE"
) ?>

</td>


<td>

<span
    class="status-badge <?= statusBadgeClass(
        $plan["status"]
    ) ?>"
>

<?= htmlspecialchars(
    str_replace(
        "_",
        " ",
        $plan["status"]
    )
) ?>

</span>

</td>


<?php if (($user["role"] ?? "") === "ADMIN"): ?>


<td>

<form
    method="POST"
    class="status-form"
>

<input
    type="hidden"
    name="plan_id"
    value="<?= (int) $plan["id"] ?>"
>


<select
    name="status"
    onchange="this.form.submit()"
>

<option
    value="PLANNED"
    <?= $plan["status"] === "PLANNED"
        ? "selected"
        : "" ?>
>
    Planned
</option>


<option
    value="APPROVED"
    <?= $plan["status"] === "APPROVED"
        ? "selected"
        : "" ?>
>
    Approved
</option>


<option
    value="IN_PROGRESS"
    <?= $plan["status"] === "IN_PROGRESS"
        ? "selected"
        : "" ?>
>
    In Progress
</option>


<option
    value="COMPLETED"
    <?= $plan["status"] === "COMPLETED"
        ? "selected"
        : "" ?>
>
    Completed
</option>


<option
    value="CANCELLED"
    <?= $plan["status"] === "CANCELLED"
        ? "selected"
        : "" ?>
>
    Cancelled
</option>

</select>


<input
    type="hidden"
    name="update_status"
    value="1"
>

</form>

<?php endif; ?>


</td>


</tr>


<?php endforeach; ?>


<?php endif; ?>


</tbody>

</table>

</div>

</div>


</div>

</main>

</div>


<!-- =====================================================
     CREATE PLAN MODAL
     ===================================================== -->

<div
    class="modal"
    id="planModal"
>


<div class="modal-card">


<div class="modal-header">

<h2>
    Create Relocation Plan
</h2>


<button
    type="button"
    class="modal-close"
    onclick="closePlanModal()"
>
    ×
</button>

</div>


<form method="POST">


<div class="modal-body">


<div class="form-group">

<label class="form-label">
    Select Habitation *
</label>


<select
    name="habitation_id"
    id="habitationSelect"
    class="form-control"
    required
    onchange="updateHabitationInfo()"
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

·

<?= htmlspecialchars(
    $habitation["district"]
) ?>

·

<?= htmlspecialchars(
    $habitation["risk_level"]
    ??
    "UNASSESSED"
) ?>

</option>


<?php endforeach; ?>


</select>

</div>


<div
    class="selection-info"
    id="habitationInfo"
>

Select a habitation to view its
population, risk and relocation priority.

</div>


<br>


<div class="form-group">

<label class="form-label">
    Select Relocation Site *
</label>


<select
    name="site_id"
    class="form-control"
    required
>


<option value="">
    Select relocation site
</option>


<?php foreach ($sites as $site): ?>


<?php

$available =
    (int)
    $site["available_capacity"];

?>


<option
    value="<?= (int) $site["id"] ?>"
    <?= $available <= 0
        ? "disabled"
        : "" ?>
>

<?= htmlspecialchars(
    $site["site_name"]
) ?>

·

<?= htmlspecialchars(
    $site["district"]
) ?>

·

<?= number_format(
    $available
) ?>

 available

</option>


<?php endforeach; ?>


</select>

</div>


<div class="selection-info">

The system will calculate the actual geographic
distance from the habitation to the selected site
using their latitude and longitude.

</div>


</div>


<div class="modal-footer">


<button
    type="button"
    class="btn btn-secondary"
    onclick="closePlanModal()"
>
    Cancel
</button>


<button
    type="submit"
    name="create_plan"
    class="btn btn-primary"
>
    Create Plan
</button>


</div>


</form>

</div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| HABITATION DATA
|--------------------------------------------------------------------------
*/

const habitationData =
<?= json_encode(
    $habitations,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
) ?>;


/*
|--------------------------------------------------------------------------
| MODAL
|--------------------------------------------------------------------------
*/

function openPlanModal() {

    document
        .getElementById("planModal")
        .classList
        .add("active");

}


function closePlanModal() {

    document
        .getElementById("planModal")
        .classList
        .remove("active");

}


/*
|--------------------------------------------------------------------------
| HABITATION INFORMATION
|--------------------------------------------------------------------------
*/

function updateHabitationInfo() {

    const select =
        document.getElementById(
            "habitationSelect"
        );


    const info =
        document.getElementById(
            "habitationInfo"
        );


    const id =
        Number(select.value);


    const habitation =
        habitationData.find(
            item =>
                Number(item.id) === id
        );


    if (!habitation) {

        info.innerHTML =
            "Select a habitation to view its " +
            "population, risk and relocation priority.";

        return;
    }


    info.innerHTML = `

        <strong>
            ${escapeHtml(habitation.name)}
        </strong>

        <br>

        Population:
        <strong>
            ${Number(
                habitation.population
            ).toLocaleString()}
        </strong>

        <br>

        Risk:
        <strong>
            ${escapeHtml(
                habitation.risk_level
                || "UNASSESSED"
            )}
        </strong>

        <br>

        Relocation Priority:
        <strong>
            ${escapeHtml(
                habitation.relocation_priority
                || "NONE"
            )}
        </strong>

    `;

}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {

    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

}


/*
|--------------------------------------------------------------------------
| CLOSE MODAL ON BACKDROP CLICK
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "click",
    function(event) {

        const modal =
            document.getElementById(
                "planModal"
            );


        if (event.target === modal) {

            closePlanModal();

        }

    }
);

</script>


</body>

</html>