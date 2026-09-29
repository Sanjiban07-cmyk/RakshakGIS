<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/../config/auth.php";
requireLogin();

require_once __DIR__ . "/../config/database.php";

$user = currentUser();


/*
|--------------------------------------------------------------------------
| HABITATION SUMMARY
|--------------------------------------------------------------------------
*/

$habitationSummary = [
    "total" => 0,
    "population" => 0
];

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(population), 0) AS population
    FROM habitations
");

if ($result) {
    $habitationSummary = $result->fetch_assoc();
}


/*
|--------------------------------------------------------------------------
| RISK SUMMARY
|--------------------------------------------------------------------------
*/

$riskSummary = [
    "high" => 0,
    "medium" => 0,
    "low" => 0,
    "red_zone" => 0
];

$result = $conn->query("
    SELECT
        COALESCE(SUM(
            CASE
                WHEN risk_level = 'HIGH' THEN 1
                ELSE 0
            END
        ), 0) AS high,

        COALESCE(SUM(
            CASE
                WHEN risk_level = 'MEDIUM' THEN 1
                ELSE 0
            END
        ), 0) AS medium,

        COALESCE(SUM(
            CASE
                WHEN risk_level = 'LOW' THEN 1
                ELSE 0
            END
        ), 0) AS low,

        COALESCE(SUM(
            CASE
                WHEN red_zone = 1 THEN 1
                ELSE 0
            END
        ), 0) AS red_zone

    FROM risk_assessments ra

    WHERE ra.id IN (
        SELECT MAX(ra2.id)
        FROM risk_assessments ra2
        GROUP BY ra2.habitation_id
    )
");

if ($result) {
    $riskSummary = $result->fetch_assoc();
}


/*
|--------------------------------------------------------------------------
| HIGH-RISK POPULATION
|--------------------------------------------------------------------------
*/

$highRiskPopulation = 0;

$result = $conn->query("
    SELECT
        COALESCE(SUM(h.population), 0) AS population

    FROM habitations h

    INNER JOIN risk_assessments ra
        ON ra.id = (
            SELECT ra2.id
            FROM risk_assessments ra2
            WHERE ra2.habitation_id = h.id
            ORDER BY ra2.assessed_at DESC, ra2.id DESC
            LIMIT 1
        )

    WHERE ra.risk_level = 'HIGH'
");

if ($result) {
    $row = $result->fetch_assoc();
    $highRiskPopulation = (int) $row["population"];
}


/*
|--------------------------------------------------------------------------
| RELOCATION SUMMARY
|--------------------------------------------------------------------------
*/

$relocationSummary = [
    "total" => 0,
    "planned" => 0,
    "approved" => 0,
    "in_progress" => 0,
    "completed" => 0
];

$result = $conn->query("
    SELECT

        COUNT(*) AS total,

        SUM(
            CASE
                WHEN status = 'PLANNED' THEN 1
                ELSE 0
            END
        ) AS planned,

        SUM(
            CASE
                WHEN status = 'APPROVED' THEN 1
                ELSE 0
            END
        ) AS approved,

        SUM(
            CASE
                WHEN status = 'IN_PROGRESS' THEN 1
                ELSE 0
            END
        ) AS in_progress,

        SUM(
            CASE
                WHEN status = 'COMPLETED' THEN 1
                ELSE 0
            END
        ) AS completed

    FROM relocation_plans
");

if ($result) {
    $row = $result->fetch_assoc();

    $relocationSummary = [
        "total" =>
            (int) ($row["total"] ?? 0),

        "planned" =>
            (int) ($row["planned"] ?? 0),

        "approved" =>
            (int) ($row["approved"] ?? 0),

        "in_progress" =>
            (int) ($row["in_progress"] ?? 0),

        "completed" =>
            (int) ($row["completed"] ?? 0)
    ];
}


/*
|--------------------------------------------------------------------------
| RELOCATION SITE SUMMARY
|--------------------------------------------------------------------------
*/

$siteSummary = [
    "total" => 0,
    "capacity" => 0,
    "available" => 0
];

$result = $conn->query("
    SELECT

        COUNT(*) AS total,

        COALESCE(
            SUM(total_capacity),
            0
        ) AS capacity,

        COALESCE(
            SUM(
                GREATEST(
                    total_capacity - occupied_capacity,
                    0
                )
            ),
            0
        ) AS available

    FROM relocation_sites
");

if ($result) {
    $siteSummary = $result->fetch_assoc();
}


/*
|--------------------------------------------------------------------------
| HIGH-RISK HABITATIONS
|--------------------------------------------------------------------------
*/

$highRiskHabitations = [];

$result = $conn->query("
    SELECT

        h.name,
        h.district,
        h.population,

        ra.risk_score,
        ra.risk_level,
        ra.red_zone,
        ra.relocation_priority,
        ra.assessed_at

    FROM habitations h

    INNER JOIN risk_assessments ra
        ON ra.id = (
            SELECT ra2.id
            FROM risk_assessments ra2
            WHERE ra2.habitation_id = h.id
            ORDER BY ra2.assessed_at DESC, ra2.id DESC
            LIMIT 1
        )

    WHERE ra.risk_level = 'HIGH'

    ORDER BY
        ra.risk_score DESC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $highRiskHabitations[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| RELOCATION SITES
|--------------------------------------------------------------------------
*/

$relocationSites = [];

$result = $conn->query("
    SELECT

        site_name,
        district,
        total_capacity,
        occupied_capacity,
        safety_level,
        distance_from_habitation

    FROM relocation_sites

    ORDER BY site_name
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $relocationSites[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| RISK PERCENTAGE
|--------------------------------------------------------------------------
*/

$totalAssessed =
    (int) $riskSummary["high"]
    +
    (int) $riskSummary["medium"]
    +
    (int) $riskSummary["low"];


$highPercent =
    $totalAssessed > 0
        ? round(
            (
                $riskSummary["high"]
                /
                $totalAssessed
            ) * 100
        )
        : 0;


$mediumPercent =
    $totalAssessed > 0
        ? round(
            (
                $riskSummary["medium"]
                /
                $totalAssessed
            ) * 100
        )
        : 0;


$lowPercent =
    $totalAssessed > 0
        ? round(
            (
                $riskSummary["low"]
                /
                $totalAssessed
            ) * 100
        )
        : 0;


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function riskClass(?string $risk): string
{
    return match ($risk) {

        "HIGH" => "risk-high",

        "MEDIUM" => "risk-medium",

        "LOW" => "risk-low",

        default => "risk-none"
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
    Reports | RakshakGIS
</title>


<link
    rel="stylesheet"
    href="../assets/css/style.css"
>


<style>

/* =====================================================
   REPORT PAGE
   ===================================================== */

.report-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 20px;

    margin-bottom: 24px;
}


.report-heading h1 {

    font-size: 24px;

    font-weight: 800;
}


.report-heading p {

    font-size: 12px;

    color: var(--muted);

    margin-top: 5px;
}


.report-actions {

    display: flex;

    gap: 8px;
}


/* =====================================================
   REPORT DOCUMENT
   ===================================================== */

.report-document {

    background: white;

    border: 1px solid var(--border);

    border-radius: 14px;

    padding: 30px;

    box-shadow:
        0 1px 3px
        rgba(15,23,42,.04);
}


/* =====================================================
   REPORT BRAND
   ===================================================== */

.report-brand {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    padding-bottom: 22px;

    border-bottom: 2px solid #e2e8f0;

    margin-bottom: 25px;
}


.brand-left {

    display: flex;

    align-items: center;

    gap: 12px;
}


.brand-logo {

    width: 46px;

    height: 46px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background: var(--primary);

    color: white;

    font-size: 22px;

    font-weight: 900;
}


.brand-name {

    font-size: 20px;

    font-weight: 900;

    color: #1e3a8a;
}


.brand-subtitle {

    font-size: 10px;

    color: var(--muted);

    margin-top: 2px;
}


.report-meta {

    text-align: right;

    font-size: 10px;

    color: var(--muted);

    line-height: 1.7;
}


/* =====================================================
   REPORT TITLE
   ===================================================== */

.report-title {

    margin-bottom: 22px;
}


.report-title h2 {

    font-size: 20px;

    font-weight: 800;
}


.report-title p {

    color: var(--muted);

    font-size: 11px;

    margin-top: 4px;
}


/* =====================================================
   SUMMARY CARDS
   ===================================================== */

.report-summary {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 14px;

    margin-bottom: 28px;
}


.report-stat {

    border: 1px solid var(--border);

    border-radius: 10px;

    padding: 16px;

    background: #f8fafc;
}


.report-stat-label {

    font-size: 10px;

    color: var(--muted);
}


.report-stat-value {

    font-size: 24px;

    font-weight: 900;

    margin-top: 5px;
}


/* =====================================================
   SECTIONS
   ===================================================== */

.report-section {

    margin-top: 28px;
}


.report-section-title {

    font-size: 15px;

    font-weight: 800;

    padding-bottom: 9px;

    border-bottom: 1px solid var(--border);

    margin-bottom: 15px;
}


/* =====================================================
   RISK DISTRIBUTION
   ===================================================== */

.risk-overview {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 20px;
}


.risk-bars {

    display: flex;

    flex-direction: column;

    gap: 13px;
}


.risk-row {

    display: grid;

    grid-template-columns:
        80px 1fr 35px;

    align-items: center;

    gap: 10px;
}


.risk-label {

    font-size: 11px;

    font-weight: 700;
}


.risk-track {

    height: 9px;

    border-radius: 99px;

    background: #e2e8f0;

    overflow: hidden;
}


.risk-fill {

    height: 100%;

    border-radius: 99px;
}


.risk-fill-high {

    width: <?= $highPercent ?>%;

    background: #ef4444;
}


.risk-fill-medium {

    width: <?= $mediumPercent ?>%;

    background: #f59e0b;
}


.risk-fill-low {

    width: <?= $lowPercent ?>%;

    background: #22c55e;
}


.risk-count {

    font-size: 11px;

    font-weight: 800;

    text-align: right;
}


/* =====================================================
   INFO GRID
   ===================================================== */

.info-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 14px;
}


.info-card {

    border: 1px solid var(--border);

    border-radius: 10px;

    padding: 16px;
}


.info-label {

    font-size: 10px;

    color: var(--muted);
}


.info-value {

    font-size: 22px;

    font-weight: 900;

    margin-top: 4px;
}


.info-description {

    font-size: 10px;

    color: var(--muted);

    margin-top: 4px;
}


/* =====================================================
   TABLE
   ===================================================== */

.report-table {

    width: 100%;

    border-collapse: collapse;
}


.report-table th {

    background: #f8fafc;

    color: #64748b;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .4px;

    text-align: left;
}


.report-table th,
.report-table td {

    padding: 11px 12px;

    border-bottom: 1px solid var(--border);
}


.report-table td {

    font-size: 11px;
}


.report-table tbody tr:last-child td {

    border-bottom: none;
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


.capacity-available {

    font-weight: 800;

    color: #15803d;
}


/* =====================================================
   FOOTER
   ===================================================== */

.report-footer {

    margin-top: 30px;

    padding-top: 15px;

    border-top: 1px solid var(--border);

    display: flex;

    justify-content: space-between;

    font-size: 9px;

    color: var(--muted);
}


/* =====================================================
   EMPTY
   ===================================================== */

.empty-report {

    text-align: center;

    padding: 25px;

    color: var(--muted);

    font-size: 11px;
}


/* =====================================================
   RESPONSIVE
   ===================================================== */

@media (max-width: 900px) {

    .report-summary {

        grid-template-columns:
            repeat(2, 1fr);
    }

    .risk-overview {

        grid-template-columns: 1fr;
    }

    .info-grid {

        grid-template-columns:
            1fr 1fr;
    }

}


@media (max-width: 600px) {

    .report-header {

        flex-direction: column;
    }

    .report-summary {

        grid-template-columns: 1fr;
    }

    .info-grid {

        grid-template-columns: 1fr;
    }

    .report-document {

        padding: 18px;
    }

}


/* =====================================================
   PRINT
   ===================================================== */

@media print {

    body {

        background: white !important;
    }


    .sidebar,
    .topbar,
    .report-actions {

        display: none !important;
    }


    .main {

        margin-left: 0 !important;

        width: 100% !important;
    }


    .content {

        padding: 0 !important;

        max-width: none !important;
    }


    .report-document {

        border: none;

        box-shadow: none;

        border-radius: 0;

        padding: 0;
    }


    .report-header {

        display: none;
    }


    .report-section {

        break-inside: avoid;
    }


    .report-table {

        page-break-inside: auto;
    }


    .report-table tr {

        page-break-inside: avoid;

        page-break-after: auto;
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
    class="nav-link active"
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
    Reports
</div>

<div class="page-subtitle">
    Disaster risk and safe relocation overview
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

<div class="report-header">


<div class="report-heading">

<h1>
    Risk & Relocation Reports
</h1>

<p>
    Generate a consolidated overview of habitation risk,
    population vulnerability and relocation planning.
</p>

</div>


<div class="report-actions">

<button
    type="button"
    class="btn btn-secondary"
    onclick="window.print()"
>
    🖨 Print Report
</button>

</div>


</div>


<!-- =====================================================
     REPORT DOCUMENT
     ===================================================== -->

<div class="report-document">


<!-- BRAND -->

<div class="report-brand">


<div class="brand-left">


<div class="brand-logo">
    R
</div>


<div>

<div class="brand-name">
    RakshakGIS
</div>

<div class="brand-subtitle">
    Disaster Risk & Safe Relocation
</div>

</div>


</div>


<div class="report-meta">

<strong>
    RISK ASSESSMENT REPORT
</strong>

<br>

Generated:
<?= date("d M Y, h:i A") ?>

<br>

Prepared for:
<?= htmlspecialchars(
    $user["name"] ?? "System Administrator"
) ?>

</div>


</div>


<!-- TITLE -->

<div class="report-title">

<h2>
    Disaster Risk & Relocation Assessment
</h2>

<p>
    Consolidated system report based on the latest
    available database records.
</p>

</div>


<!-- =====================================================
     SUMMARY
     ===================================================== -->

<div class="report-summary">


<div class="report-stat">

<div class="report-stat-label">
    Total Habitations
</div>

<div class="report-stat-value">

<?= number_format(
    (int)
    $habitationSummary["total"]
) ?>

</div>

</div>


<div class="report-stat">

<div class="report-stat-label">
    Total Population
</div>

<div class="report-stat-value">

<?= number_format(
    (int)
    $habitationSummary["population"]
) ?>

</div>

</div>


<div class="report-stat">

<div class="report-stat-label">
    High Risk Habitations
</div>

<div
    class="report-stat-value"
    style="color:#dc2626;"
>

<?= number_format(
    (int)
    $riskSummary["high"]
) ?>

</div>

</div>


<div class="report-stat">

<div class="report-stat-label">
    Red Zone Habitations
</div>

<div
    class="report-stat-value"
    style="color:#b91c1c;"
>

<?= number_format(
    (int)
    $riskSummary["red_zone"]
) ?>

</div>

</div>


</div>


<!-- =====================================================
     RISK OVERVIEW
     ===================================================== -->

<div class="report-section">


<div class="report-section-title">
    Risk Overview
</div>


<div class="risk-overview">


<div class="risk-bars">


<div class="risk-row">

<div class="risk-label">
    High
</div>

<div class="risk-track">

<div class="risk-fill risk-fill-high"></div>

</div>

<div class="risk-count">
    <?= $riskSummary["high"] ?>
</div>

</div>


<div class="risk-row">

<div class="risk-label">
    Medium
</div>

<div class="risk-track">

<div class="risk-fill risk-fill-medium"></div>

</div>

<div class="risk-count">
    <?= $riskSummary["medium"] ?>
</div>

</div>


<div class="risk-row">

<div class="risk-label">
    Low
</div>

<div class="risk-track">

<div class="risk-fill risk-fill-low"></div>

</div>

<div class="risk-count">
    <?= $riskSummary["low"] ?>
</div>

</div>


</div>


<div class="info-grid">


<div class="info-card">

<div class="info-label">
    Assessed Habitations
</div>

<div class="info-value">
    <?= $totalAssessed ?>
</div>

<div class="info-description">
    Habitations with a latest risk assessment
</div>

</div>


<div class="info-card">

<div class="info-label">
    High-Risk Population
</div>

<div
    class="info-value"
    style="color:#dc2626;"
>

<?= number_format(
    $highRiskPopulation
) ?>

</div>

<div class="info-description">
    Population living in high-risk habitations
</div>

</div>


<div class="info-card">

<div class="info-label">
    Red Zones
</div>

<div
    class="info-value"
    style="color:#b91c1c;"
>

<?= number_format(
    (int)
    $riskSummary["red_zone"]
) ?>

</div>

<div class="info-description">
    Habitations currently marked as red zone
</div>

</div>


</div>


</div>

</div>


<!-- =====================================================
     HIGH RISK HABITATIONS
     ===================================================== -->

<div class="report-section">


<div class="report-section-title">
    High-Risk Habitations
</div>


<?php if (empty($highRiskHabitations)): ?>


<div class="empty-report">

No high-risk habitations were found.

</div>


<?php else: ?>


<div class="table-wrapper">


<table class="report-table">


<thead>

<tr>

<th>
    Habitation
</th>

<th>
    District
</th>

<th>
    Population
</th>

<th>
    Risk Score
</th>

<th>
    Risk Level
</th>

<th>
    Red Zone
</th>

<th>
    Priority
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $highRiskHabitations
    as $habitation
): ?>


<tr>


<td>

<strong>

<?= htmlspecialchars(
    $habitation["name"]
) ?>

</strong>

</td>


<td>

<?= htmlspecialchars(
    $habitation["district"]
) ?>

</td>


<td>

<?= number_format(
    (int)
    $habitation["population"]
) ?>

</td>


<td>

<strong>

<?= number_format(
    (float)
    $habitation["risk_score"],
    1
) ?>

</strong>

</td>


<td>

<span
    class="risk-badge <?= riskClass(
        $habitation["risk_level"]
    ) ?>"
>

<?= htmlspecialchars(
    $habitation["risk_level"]
) ?>

</span>

</td>


<td>

<?= (int)
    $habitation["red_zone"] === 1
        ? "YES"
        : "NO"
?>

</td>


<td>

<strong>

<?= htmlspecialchars(
    $habitation[
        "relocation_priority"
    ]
) ?>

</strong>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>


</div>


<?php endif; ?>


</div>


<!-- =====================================================
     RELOCATION OVERVIEW
     ===================================================== -->

<div class="report-section">


<div class="report-section-title">
    Relocation Overview
</div>


<div class="info-grid">


<div class="info-card">

<div class="info-label">
    Total Plans
</div>

<div class="info-value">
    <?= $relocationSummary["total"] ?>
</div>

</div>


<div class="info-card">

<div class="info-label">
    Approved
</div>

<div
    class="info-value"
    style="color:#2563eb;"
>

<?= $relocationSummary["approved"] ?>

</div>

</div>


<div class="info-card">

<div class="info-label">
    In Progress
</div>

<div
    class="info-value"
    style="color:#d97706;"
>

<?= $relocationSummary["in_progress"] ?>

</div>

</div>


<div class="info-card">

<div class="info-label">
    Completed
</div>

<div
    class="info-value"
    style="color:#16a34a;"
>

<?= $relocationSummary["completed"] ?>

</div>

</div>


<div class="info-card">

<div class="info-label">
    Available Relocation Capacity
</div>

<div
    class="info-value"
    style="color:#16a34a;"
>

<?= number_format(
    (int)
    $siteSummary["available"]
) ?>

</div>

<div class="info-description">
    Current available capacity across all sites
</div>

</div>


<div class="info-card">

<div class="info-label">
    Total Relocation Sites
</div>

<div class="info-value">

<?= number_format(
    (int)
    $siteSummary["total"]
) ?>

</div>

<div class="info-description">
    Registered safe relocation locations
</div>

</div>


</div>

</div>


<!-- =====================================================
     RELOCATION SITES
     ===================================================== -->

<div class="report-section">


<div class="report-section-title">
    Relocation Site Capacity
</div>


<?php if (empty($relocationSites)): ?>


<div class="empty-report">

No relocation sites have been registered.

</div>


<?php else: ?>


<div class="table-wrapper">


<table class="report-table">


<thead>

<tr>

<th>
    Site
</th>

<th>
    District
</th>

<th>
    Total Capacity
</th>

<th>
    Occupied
</th>

<th>
    Available
</th>

<th>
    Safety
</th>

</tr>

</thead>


<tbody>


<?php foreach (
    $relocationSites
    as $site
): ?>


<?php

$totalCapacity =
    (int)
    $site["total_capacity"];

$occupiedCapacity =
    (int)
    $site["occupied_capacity"];

$availableCapacity =
    max(
        0,
        $totalCapacity
        -
        $occupiedCapacity
    );

?>


<tr>


<td>

<strong>

<?= htmlspecialchars(
    $site["site_name"]
) ?>

</strong>

</td>


<td>

<?= htmlspecialchars(
    $site["district"]
) ?>

</td>


<td>

<?= number_format(
    $totalCapacity
) ?>

</td>


<td>

<?= number_format(
    $occupiedCapacity
) ?>

</td>


<td>

<span class="capacity-available">

<?= number_format(
    $availableCapacity
) ?>

</span>

</td>


<td>

<span
    class="risk-badge <?= riskClass(
        $site["safety_level"]
    ) ?>"
>

<?= htmlspecialchars(
    $site["safety_level"]
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


<!-- =====================================================
     FOOTER
     ===================================================== -->

<div class="report-footer">


<div>

RakshakGIS · Disaster Risk & Safe Relocation

</div>


<div>

Generated from current system data

</div>


</div>


</div>


</div>


</main>


</div>


</body>

</html>