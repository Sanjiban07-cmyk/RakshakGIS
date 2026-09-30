<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$user = currentUser();
$isAdmin = (($user['role'] ?? '') === 'ADMIN');

$message = '';
$error = '';

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =========================================================
   UPDATE STATUS
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {

    if (!$isAdmin) {

        $error = 'Only administrators can change plan status.';

    } else {

        $planId = (int)($_POST['plan_id'] ?? 0);

        $status = strtoupper(trim($_POST['status'] ?? 'PLANNED'));

        $allowed = [
            'PLANNED',
            'APPROVED',
            'IN_PROGRESS',
            'COMPLETED',
            'CANCELLED'
        ];

        if ($planId <= 0) {

            $error = 'Invalid relocation plan.';

        } elseif (!in_array($status, $allowed, true)) {

            $error = 'Invalid status.';

        } else {

            $stmt = $conn->prepare("
                UPDATE relocation_plans
                SET status = ?
                WHERE id = ?
            ");

            if (!$stmt) {

                $error = 'Database error: ' . $conn->error;

            } else {

                $stmt->bind_param(
                    'si',
                    $status,
                    $planId
                );

                if ($stmt->execute()) {

                    $message = 'Plan status updated successfully.';

                } else {

                    $error = 'Unable to update status: ' . $stmt->error;
                }

                $stmt->close();
            }
        }
    }
}


/* =========================================================
   DELETE PLAN
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_plan'])) {

    if (!$isAdmin) {

        $error = 'Only administrators can delete relocation plans.';

    } else {

        $planId = (int)$_POST['delete_plan'];

        if ($planId <= 0) {

            $error = 'Invalid relocation plan.';

        } else {

            $stmt = $conn->prepare("
                DELETE FROM relocation_plans
                WHERE id = ?
            ");

            if (!$stmt) {

                $error = 'Database error: ' . $conn->error;

            } else {

                $stmt->bind_param(
                    'i',
                    $planId
                );

                if ($stmt->execute()) {

                    $message = 'Relocation plan deleted successfully.';

                } else {

                    $error = 'Unable to delete plan: ' . $stmt->error;
                }

                $stmt->close();
            }
        }
    }
}


/* =========================================================
   UPDATE PLAN
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_plan'])) {

    if (!$isAdmin) {

        $error = 'Only administrators can edit relocation plans.';

    } else {

        $planId = (int)($_POST['plan_id'] ?? 0);

        $siteId = (int)($_POST['relocation_site_id'] ?? 0);

        $population = max(
            0,
            (int)($_POST['population_to_relocate'] ?? 0)
        );

        $distanceRaw = trim($_POST['distance_km'] ?? '');

        $distance = ($distanceRaw === '')
            ? null
            : (float)$distanceRaw;

        $status = strtoupper(
            trim($_POST['status'] ?? 'PLANNED')
        );

        $reason = trim(
            $_POST['recommendation_reason'] ?? ''
        );

        $allowed = [
            'PLANNED',
            'APPROVED',
            'IN_PROGRESS',
            'COMPLETED',
            'CANCELLED'
        ];

        if ($planId <= 0) {

            $error = 'Invalid relocation plan.';

        } elseif ($siteId <= 0) {

            $error = 'Please select a relocation site.';

        } elseif (!in_array($status, $allowed, true)) {

            $error = 'Invalid status.';

        } elseif ($distance !== null && $distance < 0) {

            $error = 'Distance cannot be negative.';

        } else {

            /*
             * Get available capacity.
             */

            $siteStmt = $conn->prepare("
                SELECT
                    total_capacity,
                    occupied_capacity
                FROM relocation_sites
                WHERE id = ?
                LIMIT 1
            ");

            if (!$siteStmt) {

                $error = 'Unable to check relocation site: ' . $conn->error;

            } else {

                $siteStmt->bind_param(
                    'i',
                    $siteId
                );

                $siteStmt->execute();

                $siteResult = $siteStmt->get_result();

                $site = $siteResult->fetch_assoc();

                $siteStmt->close();

                if (!$site) {

                    $error = 'Relocation site not found.';

                } else {

                    $availableCapacity =
                        max(
                            0,
                            (int)$site['total_capacity']
                            -
                            (int)$site['occupied_capacity']
                        );


                    $stmt = $conn->prepare("
                        UPDATE relocation_plans
                        SET
                            relocation_site_id = ?,
                            population_to_relocate = ?,
                            available_capacity = ?,
                            distance_km = ?,
                            recommendation_reason = ?,
                            status = ?
                        WHERE id = ?
                    ");

                    if (!$stmt) {

                        $error =
                            'Unable to prepare update: '
                            . $conn->error;

                    } else {

                        $stmt->bind_param(
                            'iiidssi',
                            $siteId,
                            $population,
                            $availableCapacity,
                            $distance,
                            $reason,
                            $status,
                            $planId
                        );

                        if ($stmt->execute()) {

                            $message =
                                'Relocation plan updated successfully.';

                        } else {

                            $error =
                                'Unable to update plan: '
                                . $stmt->error;
                        }

                        $stmt->close();
                    }
                }
            }
        }
    }
}


/* =========================================================
   SUMMARY
   ========================================================= */

$totalPlans = 0;
$plannedPlans = 0;
$approvedPlans = 0;
$progressPlans = 0;
$completedPlans = 0;

$summary = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'PLANNED') AS planned,
        SUM(status = 'APPROVED') AS approved,
        SUM(status = 'IN_PROGRESS') AS progress,
        SUM(status = 'COMPLETED') AS completed
    FROM relocation_plans
");

if ($summary) {

    $s = $summary->fetch_assoc();

    $totalPlans =
        (int)($s['total'] ?? 0);

    $plannedPlans =
        (int)($s['planned'] ?? 0);

    $approvedPlans =
        (int)($s['approved'] ?? 0);

    $progressPlans =
        (int)($s['progress'] ?? 0);

    $completedPlans =
        (int)($s['completed'] ?? 0);
}


/* =========================================================
   LOAD RELOCATION SITES
   ========================================================= */

$sites = [];

$siteResult = $conn->query("
    SELECT
        id,
        site_name,
        district,
        total_capacity,
        occupied_capacity,
        safety_level,
        latitude,
        longitude
    FROM relocation_sites
    ORDER BY site_name ASC
");

if ($siteResult) {

    while ($row = $siteResult->fetch_assoc()) {

        $sites[] = $row;
    }
}


/* =========================================================
   LOAD PLANS
   ========================================================= */

$plans = [];

$sql = "
    SELECT

        rp.id,
        rp.habitation_id,
        rp.relocation_site_id,
        rp.population_to_relocate,
        rp.available_capacity,
        rp.distance_km,
        rp.recommendation_reason,
        rp.status,
        rp.created_at,

        h.name AS habitation_name,
        h.district AS habitation_district,
        h.state AS habitation_state,
        h.population AS habitation_population,

        rs.site_name,
        rs.district AS site_district,
        rs.total_capacity,
        rs.occupied_capacity,
        rs.safety_level,

        ra.risk_level,
        ra.risk_score,
        ra.red_zone,
        ra.relocation_priority

    FROM relocation_plans rp

    LEFT JOIN habitations h
        ON h.id = rp.habitation_id

    LEFT JOIN relocation_sites rs
        ON rs.id = rp.relocation_site_id

    LEFT JOIN risk_assessments ra
        ON ra.id = (
            SELECT MAX(ra2.id)
            FROM risk_assessments ra2
            WHERE ra2.habitation_id = rp.habitation_id
        )

    ORDER BY rp.id DESC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $plans[] = $row;
    }
}


/* =========================================================
   SEARCH
   ========================================================= */

$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $needle = strtolower($search);

    $plans = array_values(
        array_filter(
            $plans,
            function ($plan) use ($needle) {

                return
                    str_contains(
                        strtolower(
                            (string)$plan['habitation_name']
                        ),
                        $needle
                    )

                    ||

                    str_contains(
                        strtolower(
                            (string)$plan['habitation_district']
                        ),
                        $needle
                    )

                    ||

                    str_contains(
                        strtolower(
                            (string)$plan['site_name']
                        ),
                        $needle
                    )

                    ||

                    str_contains(
                        strtolower(
                            (string)$plan['status']
                        ),
                        $needle
                    );
            }
        )
    );
}


/* =========================================================
   PRIORITY CLASS
   ========================================================= */

function priorityClass($priority)
{
    switch (strtoupper($priority)) {

        case 'IMMEDIATE':
            return 'priority-high';

        case 'SHORT-TERM':
            return 'priority-medium';

        case 'MEDIUM-TERM':
            return 'priority-low';

        default:
            return 'priority-none';
    }
}


/* =========================================================
   STATUS CLASS
   ========================================================= */

function statusClass($status)
{
    switch (strtoupper($status)) {

        case 'APPROVED':
            return 'status-approved';

        case 'IN_PROGRESS':
            return 'status-progress';

        case 'COMPLETED':
            return 'status-completed';

        case 'CANCELLED':
            return 'status-cancelled';

        default:
            return 'status-planned';
    }
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

<title>Relocation Plans | RakshakGIS</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css"
>

<style>

/* =========================================================
   PAGE
   ========================================================= */

.plans-page {
    width: 100%;
}


/* =========================================================
   ALERT
   ========================================================= */

.plan-alert {
    padding: 13px 16px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 13px;
    font-weight: 700;
}

.plan-alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}

.plan-alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}


/* =========================================================
   HEADER
   ========================================================= */

.plan-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 22px;
}

.plan-heading h1 {
    margin: 0;
    color: #102a56;
    font-size: 24px;
}

.plan-heading p {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 13px;
}

.create-plan-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 11px 16px;
    background: #2563eb;
    color: #fff;
    text-decoration: none;
    border-radius: 9px;
    font-weight: 800;
    font-size: 13px;
}

.create-plan-btn:hover {
    background: #1d4ed8;
}


/* =========================================================
   SUMMARY
   ========================================================= */

.plan-summary {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.summary-box {
    background: #fff;
    border: 1px solid #dfe6ef;
    border-radius: 13px;
    padding: 18px;
}

.summary-title {
    color: #64748b;
    font-size: 11px;
    margin-bottom: 9px;
    text-transform: uppercase;
    font-weight: 700;
}

.summary-number {
    font-size: 27px;
    font-weight: 900;
    color: #172033;
}

.summary-blue {
    color: #2563eb;
}

.summary-orange {
    color: #d97706;
}

.summary-green {
    color: #16a34a;
}


/* =========================================================
   TABLE CARD
   ========================================================= */

.plan-card {
    background: #fff;
    border: 1px solid #dfe6ef;
    border-radius: 14px;
    overflow: hidden;
}

.plan-card-header {
    padding: 18px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.plan-card-title {
    font-size: 16px;
    font-weight: 900;
    color: #172033;
}

.plan-card-subtitle {
    margin-top: 4px;
    font-size: 11px;
    color: #64748b;
}

.plan-search {
    width: 270px;
    padding: 10px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
}

.plan-search:focus {
    border-color: #2563eb;
}


/* =========================================================
   TABLE
   ========================================================= */

.plan-table-wrap {
    overflow-x: auto;
}

.plan-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1050px;
}

.plan-table th {
    background: #f8fafc;
    color: #64748b;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .5px;
    padding: 13px 14px;
    text-align: left;
    white-space: nowrap;
}

.plan-table td {
    padding: 14px;
    border-top: 1px solid #edf1f5;
    color: #172033;
    font-size: 12px;
    white-space: nowrap;
}

.plan-table tr:hover td {
    background: #f8fafc;
}

.habitation-link {
    color: #2563eb;
    text-decoration: none;
    font-weight: 800;
}

.habitation-link:hover {
    text-decoration: underline;
}

.site-name {
    font-weight: 800;
}


/* =========================================================
   BADGES
   ========================================================= */

.badge {
    display: inline-block;
    padding: 6px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
}

.priority-high {
    background: #fee2e2;
    color: #dc2626;
}

.priority-medium {
    background: #fef3c7;
    color: #b45309;
}

.priority-low {
    background: #dcfce7;
    color: #15803d;
}

.priority-none {
    background: #f1f5f9;
    color: #64748b;
}


/* =========================================================
   STATUS
   ========================================================= */

.status-select {
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    padding: 7px 9px;
    background: #fff;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.status-select:focus {
    outline: none;
    border-color: #2563eb;
}

.status-badge {
    display: inline-block;
    padding: 6px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
}

.status-planned {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-approved {
    background: #e0e7ff;
    color: #4338ca;
}

.status-progress {
    background: #ffedd5;
    color: #c2410c;
}

.status-completed {
    background: #dcfce7;
    color: #15803d;
}

.status-cancelled {
    background: #fee2e2;
    color: #dc2626;
}


/* =========================================================
   ACTION BUTTONS
   ========================================================= */

.plan-actions {
    display: flex;
    gap: 6px;
    align-items: center;
}

.plan-action {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    border: 1px solid #dbe3ef;
    background: #fff;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
}

.plan-action-view {
    color: #2563eb;
}

.plan-action-view:hover {
    background: #eff6ff;
}

.plan-action-edit {
    color: #d97706;
}

.plan-action-edit:hover {
    background: #fffbeb;
}

.plan-action-delete {
    color: #dc2626;
}

.plan-action-delete:hover {
    background: #fef2f2;
}


/* =========================================================
   EMPTY
   ========================================================= */

.plan-empty {
    text-align: center;
    padding: 50px 20px;
    color: #64748b;
}


/* =========================================================
   MODAL
   ========================================================= */

.plan-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(15, 23, 42, .55);
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.plan-modal.show {
    display: flex;
}

.plan-modal-box {
    width: min(700px, 100%);
    max-height: 90vh;
    overflow-y: auto;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 25px 70px rgba(0,0,0,.25);
}

.plan-modal-header {
    padding: 18px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.plan-modal-header h2 {
    margin: 0;
    font-size: 18px;
    color: #102a56;
}

.plan-modal-close {
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 8px;
    background: #f1f5f9;
    cursor: pointer;
    font-size: 20px;
}

.plan-modal-body {
    padding: 20px;
}

.plan-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.plan-form-group {
    display: flex;
    flex-direction: column;
}

.plan-form-full {
    grid-column: 1 / -1;
}

.plan-label {
    margin-bottom: 7px;
    font-size: 12px;
    font-weight: 800;
    color: #475569;
}

.plan-input,
.plan-select,
.plan-textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 11px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    color: #172033;
    background: #fff;
}

.plan-input:focus,
.plan-select:focus,
.plan-textarea:focus {
    outline: none;
    border-color: #2563eb;
}

.plan-textarea {
    min-height: 100px;
    resize: vertical;
}

.plan-capacity {
    margin-top: 6px;
    font-size: 10px;
    color: #64748b;
}

.plan-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.plan-cancel {
    padding: 10px 16px;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
}

.plan-save {
    padding: 10px 16px;
    border: 0;
    background: #2563eb;
    color: #fff;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 800;
}

.plan-save:hover {
    background: #1d4ed8;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1100px) {

    .plan-summary {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 750px) {

    .plan-heading {
        flex-direction: column;
        align-items: flex-start;
    }

    .plan-summary {
        grid-template-columns: 1fr 1fr;
    }

    .plan-card-header {
        flex-direction: column;
        align-items: stretch;
    }

    .plan-search {
        width: 100%;
    }

    .plan-form-grid {
        grid-template-columns: 1fr;
    }

    .plan-form-full {
        grid-column: auto;
    }
}

@media (max-width: 500px) {

    .plan-summary {
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
            MAIN
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
            RELOCATION
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
            SYSTEM
        </div>

        <a
            href="reports.php"
            class="nav-link"
        >
            <span class="nav-icon">▤</span>
            Reports
        </a>

        <?php if ($isAdmin): ?>

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

                <?= e(
                    strtoupper(
                        substr(
                            $user['name'] ?? 'A',
                            0,
                            1
                        )
                    )
                ) ?>

            </div>

            <div class="sidebar-user-info">

                <div class="sidebar-user-name">
                    <?= e(
                        $user['name'] ?? 'Administrator'
                    ) ?>
                </div>

                <div class="sidebar-user-role">
                    <?= e(
                        $user['role'] ?? 'USER'
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


<section class="content">

<div class="plans-page">


<!-- ALERTS -->

<?php if ($message): ?>

    <div class="plan-alert plan-alert-success">
        ✓ <?= e($message) ?>
    </div>

<?php endif; ?>


<?php if ($error): ?>

    <div class="plan-alert plan-alert-error">
        ⚠ <?= e($error) ?>
    </div>

<?php endif; ?>


<!-- HEADER -->

<div class="plan-heading">

    <div>

        <h1>
            Relocation Plans
        </h1>

        <p>
            Manage and monitor safe population relocation plans.
        </p>

    </div>


    <a
        href="habitations.php"
        class="create-plan-btn"
    >
        ＋ Create Plan
    </a>

</div>


<!-- SUMMARY -->

<div class="plan-summary">


    <div class="summary-box">

        <div class="summary-title">
            Total Plans
        </div>

        <div class="summary-number">
            <?= $totalPlans ?>
        </div>

    </div>


    <div class="summary-box">

        <div class="summary-title">
            Planned
        </div>

        <div class="summary-number">
            <?= $plannedPlans ?>
        </div>

    </div>


    <div class="summary-box">

        <div class="summary-title">
            Approved
        </div>

        <div class="summary-number summary-blue">
            <?= $approvedPlans ?>
        </div>

    </div>


    <div class="summary-box">

        <div class="summary-title">
            In Progress
        </div>

        <div class="summary-number summary-orange">
            <?= $progressPlans ?>
        </div>

    </div>


    <div class="summary-box">

        <div class="summary-title">
            Completed
        </div>

        <div class="summary-number summary-green">
            <?= $completedPlans ?>
        </div>

    </div>


</div>


<!-- PLANS TABLE -->

<div class="plan-card">


    <div class="plan-card-header">

        <div>

            <div class="plan-card-title">
                All Relocation Plans
            </div>

            <div class="plan-card-subtitle">
                Registered relocation plans and current operational status.
            </div>

        </div>


        <form
            method="GET"
            style="margin:0;"
        >

            <input
                type="text"
                name="search"
                value="<?= e($search) ?>"
                class="plan-search"
                placeholder="Search habitation or site..."
            >

        </form>

    </div>


    <div class="plan-table-wrap">

        <table class="plan-table">


            <thead>

                <tr>

                    <th>
                        Habitation
                    </th>

                    <th>
                        District
                    </th>

                    <th>
                        Relocation Site
                    </th>

                    <th>
                        People
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

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php if (empty($plans)): ?>

                <tr>

                    <td
                        colspan="8"
                        class="plan-empty"
                    >
                        No relocation plans found.
                    </td>

                </tr>

            <?php else: ?>


                <?php foreach ($plans as $plan): ?>


                    <?php

                    $priority =
                        strtoupper(
                            $plan['relocation_priority']
                            ?? 'NONE'
                        );

                    $status =
                        strtoupper(
                            $plan['status']
                            ?? 'PLANNED'
                        );

                    $editData = [

                        'id' =>
                            (int)$plan['id'],

                        'habitation' =>
                            $plan['habitation_name']
                            ?? '',

                        'site' =>
                            (int)$plan['relocation_site_id'],

                        'population' =>
                            (int)$plan['population_to_relocate'],

                        'distance' =>
                            $plan['distance_km'],

                        'status' =>
                            $status,

                        'reason' =>
                            $plan['recommendation_reason']
                            ?? ''
                    ];

                    ?>


                    <tr>


                        <!-- HABITATION -->

                        <td>

                            <a
                                href="relocation_plans.php?id=<?= (int)$plan['id'] ?>"
                                class="habitation-link"
                            >

                                <?= e(
                                    $plan['habitation_name']
                                    ?? 'Unknown'
                                ) ?>

                            </a>

                        </td>


                        <!-- DISTRICT -->

                        <td>

                            <?= e(
                                $plan['habitation_district']
                                ?? '—'
                            ) ?>

                        </td>


                        <!-- SITE -->

                        <td class="site-name">

                            <?= e(
                                $plan['site_name']
                                ?? 'Unknown'
                            ) ?>

                        </td>


                        <!-- PEOPLE -->

                        <td>

                            <?= number_format(
                                (int)(
                                    $plan[
                                        'population_to_relocate'
                                    ] ?? 0
                                )
                            ) ?>

                        </td>


                        <!-- DISTANCE -->

                        <td>

                            <?php if (
                                $plan['distance_km']
                                !== null
                                &&
                                $plan['distance_km']
                                !== ''
                            ): ?>

                                <?= number_format(
                                    (float)$plan['distance_km'],
                                    2
                                ) ?>

                                km

                            <?php else: ?>

                                —

                            <?php endif; ?>

                        </td>


                        <!-- PRIORITY -->

                        <td>

                            <span
                                class="badge <?= priorityClass($priority) ?>"
                            >
                                <?= e($priority) ?>
                            </span>

                        </td>


                        <!-- STATUS -->

                        <td>


                            <?php if ($isAdmin): ?>


                                <form
                                    method="POST"
                                    style="margin:0;"
                                >

                                    <input
                                        type="hidden"
                                        name="change_status"
                                        value="1"
                                    >

                                    <input
                                        type="hidden"
                                        name="plan_id"
                                        value="<?= (int)$plan['id'] ?>"
                                    >


                                    <select
                                        name="status"
                                        class="status-select"
                                        onchange="this.form.submit()"
                                    >

                                        <option
                                            value="PLANNED"
                                            <?= $status === 'PLANNED'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            PLANNED
                                        </option>

                                        <option
                                            value="APPROVED"
                                            <?= $status === 'APPROVED'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            APPROVED
                                        </option>

                                        <option
                                            value="IN_PROGRESS"
                                            <?= $status === 'IN_PROGRESS'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            IN PROGRESS
                                        </option>

                                        <option
                                            value="COMPLETED"
                                            <?= $status === 'COMPLETED'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            COMPLETED
                                        </option>

                                        <option
                                            value="CANCELLED"
                                            <?= $status === 'CANCELLED'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            CANCELLED
                                        </option>

                                    </select>

                                </form>


                            <?php else: ?>


                                <span
                                    class="status-badge <?= statusClass($status) ?>"
                                >

                                    <?= e(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $status
                                        )
                                    ) ?>

                                </span>


                            <?php endif; ?>


                        </td>


                        <!-- ACTION -->

                        <td>


                            <div class="plan-actions">


                                <!-- VIEW -->

                                <a
                                    href="relocation_details.php?id=<?= (int)$plan['id'] ?>"
                                    class="plan-action plan-action-view"
                                    title="View"
                                >
                                    👁
                                </a>


                                <?php if ($isAdmin): ?>


                                    <!-- EDIT -->

                                    <button
                                        type="button"
                                        class="plan-action plan-action-edit"
                                        title="Edit"
                                        onclick='openPlanEdit(<?= json_encode(
                                            $editData,
                                            JSON_HEX_TAG |
                                            JSON_HEX_APOS |
                                            JSON_HEX_QUOT |
                                            JSON_HEX_AMP
                                        ) ?>)'
                                    >
                                        ✏️
                                    </button>


                                    <!-- DELETE -->

                                    <form
                                        method="POST"
                                        style="margin:0;"
                                        onsubmit="return confirm('Are you sure you want to delete this relocation plan?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="delete_plan"
                                            value="<?= (int)$plan['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="plan-action plan-action-delete"
                                            title="Delete"
                                        >
                                            🗑️
                                        </button>

                                    </form>


                                <?php endif; ?>


                            </div>


                        </td>


                    </tr>


                <?php endforeach; ?>


            <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>


</div>

</section>

</main>

</div>


<!-- =====================================================
     EDIT MODAL
     ===================================================== -->

<?php if ($isAdmin): ?>


<div
    class="plan-modal"
    id="planEditModal"
>


    <div class="plan-modal-box">


        <div class="plan-modal-header">

            <h2>
                ✏️ Edit Relocation Plan
            </h2>

            <button
                type="button"
                class="plan-modal-close"
                onclick="closePlanEdit()"
            >
                ×
            </button>

        </div>


        <form method="POST">


            <input
                type="hidden"
                name="update_plan"
                value="1"
            >


            <input
                type="hidden"
                name="plan_id"
                id="editPlanId"
            >


            <div class="plan-modal-body">


                <div
                    style="
                    margin-bottom:18px;
                    color:#64748b;
                    font-size:12px;
                    "
                >

                    Editing plan for

                    <strong
                        id="editPlanHabitation"
                        style="color:#172033;"
                    ></strong>

                </div>


                <div class="plan-form-grid">


                    <!-- SITE -->

                    <div
                        class="plan-form-group plan-form-full"
                    >

                        <label class="plan-label">
                            Relocation Site
                        </label>


                        <select
                            name="relocation_site_id"
                            id="editSiteId"
                            class="plan-select"
                            required
                            onchange="updatePlanCapacity()"
                        >


                            <?php foreach ($sites as $site): ?>


                                <?php

                                $available =
                                    max(
                                        0,
                                        (int)$site[
                                            'total_capacity'
                                        ]
                                        -
                                        (int)$site[
                                            'occupied_capacity'
                                        ]
                                    );

                                ?>


                                <option
                                    value="<?= (int)$site['id'] ?>"
                                    data-available="<?= $available ?>"
                                >

                                    <?= e(
                                        $site['site_name']
                                    ) ?>

                                    —

                                    <?= e(
                                        $site['district']
                                    ) ?>

                                    (

                                    <?= number_format(
                                        $available
                                    ) ?>

                                    available)

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <div
                            class="plan-capacity"
                            id="editCapacity"
                        ></div>


                    </div>


                    <!-- PEOPLE -->

                    <div class="plan-form-group">

                        <label class="plan-label">
                            People to Relocate
                        </label>

                        <input
                            type="number"
                            name="population_to_relocate"
                            id="editPopulation"
                            class="plan-input"
                            min="0"
                            required
                        >

                    </div>


                    <!-- DISTANCE -->

                    <div class="plan-form-group">

                        <label class="plan-label">
                            Distance (km)
                        </label>

                        <input
                            type="number"
                            name="distance_km"
                            id="editDistance"
                            class="plan-input"
                            min="0"
                            step="0.01"
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="plan-form-group">

                        <label class="plan-label">
                            Status
                        </label>

                        <select
                            name="status"
                            id="editStatus"
                            class="plan-select"
                        >

                            <option value="PLANNED">
                                PLANNED
                            </option>

                            <option value="APPROVED">
                                APPROVED
                            </option>

                            <option value="IN_PROGRESS">
                                IN PROGRESS
                            </option>

                            <option value="COMPLETED">
                                COMPLETED
                            </option>

                            <option value="CANCELLED">
                                CANCELLED
                            </option>

                        </select>

                    </div>


                    <!-- REASON -->

                    <div
                        class="plan-form-group plan-form-full"
                    >

                        <label class="plan-label">
                            Recommendation Reason
                        </label>

                        <textarea
                            name="recommendation_reason"
                            id="editReason"
                            class="plan-textarea"
                            placeholder="Explain why this relocation site was selected..."
                        ></textarea>

                    </div>


                </div>

            </div>


            <div class="plan-modal-footer">

                <button
                    type="button"
                    class="plan-cancel"
                    onclick="closePlanEdit()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="plan-save"
                >
                    ✓ Save Changes
                </button>

            </div>


        </form>

    </div>

</div>


<?php endif; ?>


<!-- =====================================================
     JAVASCRIPT
     ===================================================== -->

<script>

function openPlanEdit(plan)
{
    const modal =
        document.getElementById(
            'planEditModal'
        );

    if (!modal)
    {
        return;
    }


    document.getElementById(
        'editPlanId'
    ).value =
        plan.id || '';


    document.getElementById(
        'editPlanHabitation'
    ).textContent =
        plan.habitation || 'Relocation Plan';


    document.getElementById(
        'editSiteId'
    ).value =
        plan.site || '';


    document.getElementById(
        'editPopulation'
    ).value =
        plan.population ?? 0;


    document.getElementById(
        'editDistance'
    ).value =
        plan.distance ?? '';


    document.getElementById(
        'editStatus'
    ).value =
        plan.status || 'PLANNED';


    document.getElementById(
        'editReason'
    ).value =
        plan.reason || '';


    updatePlanCapacity();


    modal.classList.add('show');

    document.body.style.overflow =
        'hidden';
}


function closePlanEdit()
{
    const modal =
        document.getElementById(
            'planEditModal'
        );

    if (!modal)
    {
        return;
    }


    modal.classList.remove('show');

    document.body.style.overflow =
        '';
}


function updatePlanCapacity()
{
    const select =
        document.getElementById(
            'editSiteId'
        );

    const note =
        document.getElementById(
            'editCapacity'
        );

    if (!select || !note)
    {
        return;
    }


    const option =
        select.options[
            select.selectedIndex
        ];


    const available =
        Number(
            option?.dataset?.available || 0
        );


    note.textContent =
        'Current available capacity: '
        +
        available.toLocaleString()
        +
        ' people';
}


const editModal =
    document.getElementById(
        'planEditModal'
    );


if (editModal)
{
    editModal.addEventListener(
        'click',
        function(event)
        {
            if (
                event.target ===
                editModal
            )
            {
                closePlanEdit();
            }
        }
    );
}


document.addEventListener(
    'keydown',
    function(event)
    {
        if (
            event.key ===
            'Escape'
        )
        {
            closePlanEdit();
        }
    }
);

</script>


</body>

</html>