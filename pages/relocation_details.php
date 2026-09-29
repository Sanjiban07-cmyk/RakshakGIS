<?php

$currentPage = 'relocation_details';

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function priorityClass($priority)
{
    $priority = strtoupper((string) $priority);

    if ($priority === 'IMMEDIATE') {
        return 'badge-high';
    }

    if ($priority === 'SHORT-TERM') {
        return 'badge-medium';
    }

    if ($priority === 'MEDIUM-TERM') {
        return 'badge-low';
    }

    return 'badge-none';
}

function statusClass($status)
{
    return strtolower(str_replace('_', '-', (string) $status));
}

/*
|--------------------------------------------------------------------------
| Selected Plan ID
|--------------------------------------------------------------------------
*/

$selectedPlanId = isset($_GET['id']) && is_numeric($_GET['id'])
    ? (int) $_GET['id']
    : 0;

/*
|--------------------------------------------------------------------------
| Update Relocation Plan Status
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $planId = isset($_POST['plan_id']) && is_numeric($_POST['plan_id'])
        ? (int) $_POST['plan_id']
        : 0;

    $newStatus = strtoupper(trim($_POST['status'] ?? ''));

    $allowedStatuses = [
        'PLANNED',
        'APPROVED',
        'IN_PROGRESS',
        'COMPLETED',
        'CANCELLED'
    ];

    if (
        $planId > 0 &&
        in_array($newStatus, $allowedStatuses, true)
    ) {

        $updateStmt = $conn->prepare("
            UPDATE relocation_plans
            SET status = ?
            WHERE id = ?
        ");

        if ($updateStmt) {

            $updateStmt->bind_param(
                "si",
                $newStatus,
                $planId
            );

            $updateStmt->execute();
            $updateStmt->close();
        }
    }

    header(
        "Location: relocation_details.php?id=" .
        $planId .
        "&updated=1"
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Get One Plan
|--------------------------------------------------------------------------
*/

$selectedPlan = null;

if ($selectedPlanId > 0) {

    $detailSql = "
        SELECT
            rp.id,
            rp.habitation_id,
            rp.relocation_site_id,

            h.name AS habitation_name,
            h.district,
            h.state,
            h.population,
            h.latitude AS habitation_latitude,
            h.longitude AS habitation_longitude,

            rs.site_name,
            rs.district AS site_district,
            rs.latitude AS site_latitude,
            rs.longitude AS site_longitude,
            rs.total_capacity,
            rs.occupied_capacity,
            (
                rs.total_capacity - rs.occupied_capacity
            ) AS site_available_capacity,
            rs.safety_level,
            rs.facilities,

            rp.population_to_relocate,
            rp.available_capacity AS plan_available_capacity,
            rp.distance_km,
            rp.recommendation_reason,
            rp.status,
            rp.created_at,

            ra.risk_score,
            ra.risk_level,
            ra.red_zone,
            ra.relocation_priority,
            ra.assessment_notes

        FROM relocation_plans rp

        INNER JOIN habitations h
            ON h.id = rp.habitation_id

        INNER JOIN relocation_sites rs
            ON rs.id = rp.relocation_site_id

        LEFT JOIN risk_assessments ra
            ON ra.id = (
                SELECT MAX(ra2.id)
                FROM risk_assessments ra2
                WHERE ra2.habitation_id = rp.habitation_id
            )

        WHERE rp.id = ?

        LIMIT 1
    ";

    $detailStmt = $conn->prepare($detailSql);

    if ($detailStmt) {

        $detailStmt->bind_param(
            "i",
            $selectedPlanId
        );

        $detailStmt->execute();

        $detailResult = $detailStmt->get_result();

        $selectedPlan = $detailResult->fetch_assoc();

        $detailStmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| Get All Plans
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        rp.id,
        rp.habitation_id,
        rp.relocation_site_id,

        h.name AS habitation_name,
        h.district,
        h.state,
        h.population,

        rs.site_name,
        rs.district AS site_district,
        rs.total_capacity,
        rs.occupied_capacity,
        (
            rs.total_capacity - rs.occupied_capacity
        ) AS available_capacity,
        rs.safety_level,

        rp.population_to_relocate,
        rp.available_capacity AS plan_available_capacity,
        rp.distance_km,
        rp.recommendation_reason,
        rp.status,
        rp.created_at,

        ra.risk_level,
        ra.risk_score,
        ra.red_zone,
        ra.relocation_priority

    FROM relocation_plans rp

    INNER JOIN habitations h
        ON h.id = rp.habitation_id

    INNER JOIN relocation_sites rs
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

$plans = [];

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $plans[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

$totalPlans = count($plans);

$immediatePlans = 0;
$totalPeople = 0;

foreach ($plans as $plan) {

    if (
        strtoupper($plan['relocation_priority'] ?? '') ===
        'IMMEDIATE'
    ) {
        $immediatePlans++;
    }

    $totalPeople += (int) (
        $plan['population_to_relocate'] ?? 0
    );
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

        .detail-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .detail-card {
            background: #ffffff;
            border: 1px solid #dfe6ef;
            border-radius: 14px;
            padding: 24px;
        }

        .detail-title {
            font-size: 20px;
            font-weight: 700;
            color: #102a56;
            margin-bottom: 6px;
        }

        .detail-subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
        }

        .info-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 7px;
        }

        .info-value {
            font-size: 17px;
            font-weight: 700;
            color: #102a56;
        }

        .large-value {
            font-size: 28px;
        }

        .priority-box {
            padding: 18px;
            border-radius: 10px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            margin-bottom: 18px;
        }

        .priority-label {
            font-size: 12px;
            color: #9a3412;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .priority-value {
            font-size: 20px;
            font-weight: 700;
            color: #c2410c;
        }

        .status-box {
            padding: 18px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .status-box label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }

        .status-select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            font-size: 14px;
            font-weight: 600;
            color: #172033;
        }

        .reason-box {
            margin-top: 18px;
            padding: 18px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            color: #1e3a8a;
            line-height: 1.6;
        }

        .risk-box {
            padding: 20px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-bottom: 18px;
        }

        .risk-score {
            font-size: 38px;
            font-weight: 800;
            color: #dc2626;
        }

        .risk-level {
            display: inline-block;
            margin-top: 8px;
            padding: 7px 13px;
            border-radius: 20px;
            background: #fef3c7;
            color: #b45309;
            font-size: 12px;
            font-weight: 700;
        }

        .red-zone {
            margin-top: 12px;
            font-size: 14px;
            font-weight: 600;
        }

        .red-zone.yes {
            color: #dc2626;
        }

        .red-zone.no {
            color: #16a34a;
        }

        .action-row {
            display: flex;
            gap: 12px;
            margin-top: 22px;
            flex-wrap: wrap;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        .success-message {
            margin-bottom: 18px;
            padding: 13px 16px;
            border-radius: 8px;
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            font-weight: 600;
        }

        .not-found {
            padding: 50px 20px;
            text-align: center;
        }

        .plan-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .plan-link:hover {
            text-decoration: underline;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .plans-table {
            width: 100%;
            border-collapse: collapse;
        }

        .plans-table th,
        .plans-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            white-space: nowrap;
        }

        .plans-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: 700;
        }

        .plans-table td {
            color: #334155;
            font-size: 14px;
        }

        .plans-table tr:hover {
            background: #f8fafc;
        }

        @media (max-width: 900px) {

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 600px) {

            .plans-table th,
            .plans-table td {
                padding: 10px 8px;
            }

        }

    </style>

</head>

<body>

<div class="app">

  <aside class="sidebar">

    <div class="logo">

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

    </div>

    <nav class="sidebar-nav">

        <div class="nav-section">
            Main
        </div>

        <a
            href="dashboard.php"
            class="nav-link"
        >
            <span class="nav-icon">⌂</span>
            Dashboard
        </a>

        <a
            href="habitations.php"
            class="nav-link"
        >
            <span class="nav-icon">⌂</span>
            Habitations
        </a>

        <a
            href="add_habitation.php"
            class="nav-link"
        >
            <span class="nav-icon">＋</span>
            Add Habitation
        </a>

        <a
            href="risk_assessment.php"
            class="nav-link"
        >
            <span class="nav-icon">⚠</span>
            Risk Assessment
        </a>

        <a
            href="map.php"
            class="nav-link"
        >
            <span class="nav-icon">●</span>
            Interactive Map
        </a>

        <a
            href="relocation.php"
            class="nav-link"
        >
            <span class="nav-icon">⌖</span>
            Relocation Planner
        </a>

        <a
            href="relocation_details.php"
            class="nav-link active"
        >
            <span class="nav-icon">▣</span>
            Relocation Plans
        </a>

        <a
            href="reports.php"
            class="nav-link"
        >
            <span class="nav-icon">▤</span>
            Reports
        </a>

        <div class="nav-section">
            Information
        </div>

        <a
            href="about.php"
            class="nav-link"
        >
            <span class="nav-icon">ⓘ</span>
            About
        </a>

        <a
            href="contact.php"
            class="nav-link"
        >
            <span class="nav-icon">✉</span>
            Contact
        </a>

    </nav>

</aside>
    <main class="main">

        <header class="topbar">

            <div>

                <h1>
                    Relocation Plans
                </h1>

                <p>
                    Manage and track planned relocation activities
                </p>

            </div>

            <div class="topbar-right">

                <span class="system-status">
                    ● System Online
                </span>

                <span class="notification">
                    🔔
                </span>

                <span class="admin-name">
                    Admin
                </span>

            </div>

        </header>

        <section class="content">

            <?php if (isset($_GET['updated'])): ?>

                <div class="success-message">
                    Relocation plan status updated successfully.
                </div>

            <?php endif; ?>

            <?php if ($selectedPlan): ?>

                <div class="detail-grid">

                    <div class="detail-card">

                        <div class="detail-title">
                            <?= e($selectedPlan['habitation_name']) ?>
                        </div>

                        <div class="detail-subtitle">
                            <?= e($selectedPlan['district']) ?>,
                            <?= e($selectedPlan['state']) ?>
                        </div>

                        <div class="info-grid">

                            <div class="info-box">

                                <div class="info-label">
                                    Population
                                </div>

                                <div class="info-value large-value">
                                    <?= number_format(
                                        (int) $selectedPlan['population']
                                    ) ?>
                                </div>

                            </div>

                            <div class="info-box">

                                <div class="info-label">
                                    People to Relocate
                                </div>

                                <div class="info-value large-value">
                                    <?= number_format(
                                        (int) $selectedPlan['population_to_relocate']
                                    ) ?>
                                </div>

                            </div>

                            <div class="info-box">

                                <div class="info-label">
                                    Relocation Site
                                </div>

                                <div class="info-value">
                                    <?= e($selectedPlan['site_name']) ?>
                                </div>

                            </div>

                            <div class="info-box">

                                <div class="info-label">
                                    Distance
                                </div>

                                <div class="info-value">

                                    <?php if (
                                        $selectedPlan['distance_km'] !== null
                                    ): ?>

                                        <?= number_format(
                                            (float) $selectedPlan['distance_km'],
                                            2
                                        ) ?>
                                        km

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                        <div class="priority-box">

                            <div class="priority-label">
                                Relocation Priority
                            </div>

                            <div class="priority-value">

                                <?= e(
                                    strtoupper(
                                        $selectedPlan['relocation_priority']
                                        ?? 'NONE'
                                    )
                                ) ?>

                            </div>

                        </div>

                        <div class="status-box">

                            <form
                                method="POST"
                                action="relocation_details.php?id=<?= (int) $selectedPlan['id'] ?>"
                            >

                                <input
                                    type="hidden"
                                    name="plan_id"
                                    value="<?= (int) $selectedPlan['id'] ?>"
                                >

                                <label for="detailStatus">
                                    Update Plan Status
                                </label>

                                <select
                                    id="detailStatus"
                                    name="status"
                                    class="status-select"
                                >

                                    <?php
                                    $selectedStatus = strtoupper(
                                        $selectedPlan['status'] ?? 'PLANNED'
                                    );
                                    ?>

                                    <option
                                        value="PLANNED"
                                        <?= $selectedStatus === 'PLANNED'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        PLANNED
                                    </option>

                                    <option
                                        value="APPROVED"
                                        <?= $selectedStatus === 'APPROVED'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        APPROVED
                                    </option>

                                    <option
                                        value="IN_PROGRESS"
                                        <?= $selectedStatus === 'IN_PROGRESS'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        IN PROGRESS
                                    </option>

                                    <option
                                        value="COMPLETED"
                                        <?= $selectedStatus === 'COMPLETED'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        COMPLETED
                                    </option>

                                    <option
                                        value="CANCELLED"
                                        <?= $selectedStatus === 'CANCELLED'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        CANCELLED
                                    </option>

                                </select>

                                <div class="action-row">

                                    <button
                                        type="submit"
                                        class="btn-primary"
                                    >
                                        Update Status
                                    </button>

                                    <a
                                        href="relocation_details.php"
                                        class="btn-secondary"
                                    >
                                        Back to All Plans
                                    </a>

                                </div>

                            </form>

                        </div>

                        <?php if (
                            !empty(
                                $selectedPlan['recommendation_reason']
                            )
                        ): ?>

                            <div class="reason-box">

                                <strong>
                                    Recommendation:
                                </strong>

                                <br>

                                <?= e(
                                    $selectedPlan['recommendation_reason']
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                    <div>

                        <div class="detail-card">

                            <div class="detail-title">
                                Risk Assessment
                            </div>

                            <div class="detail-subtitle">
                                Latest risk assessment for this habitation
                            </div>

                            <div class="risk-box">

                                <div class="info-label">
                                    Risk Score
                                </div>

                                <div class="risk-score">

                                    <?= number_format(
                                        (float) (
                                            $selectedPlan['risk_score']
                                            ?? 0
                                        ),
                                        2
                                    ) ?>

                                </div>

                                <span class="risk-level">

                                    <?= e(
                                        strtoupper(
                                            $selectedPlan['risk_level']
                                            ?? 'UNKNOWN'
                                        )
                                    ) ?>

                                </span>

                                <?php if (
                                    (int) (
                                        $selectedPlan['red_zone']
                                        ?? 0
                                    ) === 1
                                ): ?>

                                    <div class="red-zone yes">
                                        ⚠ Red Zone
                                    </div>

                                <?php else: ?>

                                    <div class="red-zone no">
                                        ✓ Outside Red Zone
                                    </div>

                                <?php endif; ?>

                            </div>

                            <div class="info-grid">

                                <div class="info-box">

                                    <div class="info-label">
                                        Site Capacity
                                    </div>

                                    <div class="info-value">

                                        <?= number_format(
                                            (int) (
                                                $selectedPlan[
                                                    'total_capacity'
                                                ] ?? 0
                                            )
                                        ) ?>

                                    </div>

                                </div>

                                <div class="info-box">

                                    <div class="info-label">
                                        Available Capacity
                                    </div>

                                    <div class="info-value">

                                        <?= number_format(
                                            (int) (
                                                $selectedPlan[
                                                    'site_available_capacity'
                                                ] ?? 0
                                            )
                                        ) ?>

                                    </div>

                                </div>

                                <div class="info-box">

                                    <div class="info-label">
                                        Safety Level
                                    </div>

                                    <div class="info-value">

                                        <?= e(
                                            strtoupper(
                                                $selectedPlan[
                                                    'safety_level'
                                                ] ?? 'UNKNOWN'
                                            )
                                        ) ?>

                                    </div>

                                </div>

                                <div class="info-box">

                                    <div class="info-label">
                                        Status
                                    </div>

                                    <div class="info-value">

                                        <?= e(
                                            strtoupper(
                                                $selectedPlan['status']
                                                ?? 'PLANNED'
                                            )
                                        ) ?>

                                    </div>

                                </div>

                            </div>

                            <?php if (
                                !empty(
                                    $selectedPlan['assessment_notes']
                                )
                            ): ?>

                                <div class="reason-box">

                                    <strong>
                                        Assessment Notes:
                                    </strong>

                                    <br>

                                    <?= e(
                                        $selectedPlan['assessment_notes']
                                    ) ?>

                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="detail-card">

                            <div class="detail-title">
                                Relocation Site
                            </div>

                            <div class="detail-subtitle">
                                Selected safe relocation destination
                            </div>

                            <div class="info-grid">

                                <div class="info-box">

                                    <div class="info-label">
                                        Site Name
                                    </div>

                                    <div class="info-value">
                                        <?= e(
                                            $selectedPlan['site_name']
                                        ) ?>
                                    </div>

                                </div>

                                <div class="info-box">

                                    <div class="info-label">
                                        District
                                    </div>

                                    <div class="info-value">
                                        <?= e(
                                            $selectedPlan['site_district']
                                        ) ?>
                                    </div>

                                </div>

                                <div class="info-box">

                                    <div class="info-label">
                                        Total Capacity
                                    </div>

                                    <div class="info-value">
                                        <?= number_format(
                                            (int) (
                                                $selectedPlan[
                                                    'total_capacity'
                                                ] ?? 0
                                            )
                                        ) ?>
                                    </div>

                                </div>

                                <div class="info-box">

                                    <div class="info-label">
                                        Occupied Capacity
                                    </div>

                                    <div class="info-value">
                                        <?= number_format(
                                            (int) (
                                                $selectedPlan[
                                                    'occupied_capacity'
                                                ] ?? 0
                                            )
                                        ) ?>
                                    </div>

                                </div>

                            </div>

                            <?php if (
                                !empty(
                                    $selectedPlan['facilities']
                                )
                            ): ?>

                                <div class="reason-box">

                                    <strong>
                                        Facilities:
                                    </strong>

                                    <br>

                                    <?= e(
                                        $selectedPlan['facilities']
                                    ) ?>

                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="action-row">

                            <a
                                href="relocation.php?id=<?= (int) $selectedPlan['habitation_id'] ?>"
                                class="btn-secondary"
                            >
                                View Relocation Planner
                            </a>

                            <a
                                href="habitation_details.php?id=<?= (int) $selectedPlan['habitation_id'] ?>"
                                class="btn-secondary"
                            >
                                View Habitation
                            </a>

                        </div>

                    </div>

                </div>

            <?php else: ?>

                <div class="card">

                    <div
                        style="
                            display:flex;
                            justify-content:space-between;
                            align-items:center;
                            gap:20px;
                            flex-wrap:wrap;
                        "
                    >

                        <div>

                            <h2>
                                All Relocation Plans
                            </h2>

                            <p>
                                View and manage relocation plans created by
                                RakshakGIS.
                            </p>

                        </div>

                        <div>

                            <input
                                type="text"
                                id="searchPlan"
                                placeholder="Search plans..."
                                style="
                                    padding:11px 14px;
                                    border:1px solid #cbd5e1;
                                    border-radius:8px;
                                    min-width:240px;
                                "
                            >

                        </div>

                    </div>

                    <div class="table-wrapper">

                        <table class="plans-table">

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

                                    <!-- NEW ACTION COLUMN -->
                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>

                            <tbody id="plansTable">

                                <?php if (empty($plans)): ?>

                                    <tr>

                                        <td
                                            colspan="8"
                                            style="
                                                text-align:center;
                                                padding:40px;
                                                color:#64748b;
                                            "
                                        >
                                            No relocation plans found.
                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($plans as $plan): ?>

                                        <?php

                                        $priority = strtoupper(
                                            $plan[
                                                'relocation_priority'
                                            ] ?? 'NONE'
                                        );

                                        $status = strtoupper(
                                            $plan['status'] ?? 'PLANNED'
                                        );

                                        ?>

                                        <tr>

                                            <!-- HABITATION -->
                                            <td>

                                                <a
                                                    href="relocation_details.php?id=<?= (int) $plan['id'] ?>"
                                                    class="plan-link"
                                                >
                                                    <?= e(
                                                        $plan[
                                                            'habitation_name'
                                                        ]
                                                    ) ?>
                                                </a>

                                            </td>

                                            <!-- DISTRICT -->
                                            <td>

                                                <?= e(
                                                    $plan['district']
                                                ) ?>

                                            </td>

                                            <!-- RELOCATION SITE -->
                                            <td>

                                                <strong>

                                                    <?= e(
                                                        $plan['site_name']
                                                    ) ?>

                                                </strong>

                                            </td>

                                            <!-- PEOPLE -->
                                            <td>

                                                <?= number_format(
                                                    (int) $plan[
                                                        'population_to_relocate'
                                                    ]
                                                ) ?>

                                            </td>

                                            <!-- DISTANCE -->
                                            <td>

                                                <?php if (
                                                    $plan['distance_km'] !== null
                                                ): ?>

                                                    <?= number_format(
                                                        (float) $plan[
                                                            'distance_km'
                                                        ],
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

                                                <form
                                                    method="POST"
                                                    action="relocation_details.php"
                                                    style="margin:0;"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="plan_id"
                                                        value="<?= (int) $plan['id'] ?>"
                                                    >

                                                    <select
                                                        name="status"
                                                        onchange="this.form.submit()"
                                                        style="
                                                            border:1px solid #d7deea;
                                                            border-radius:8px;
                                                            padding:7px 10px;
                                                            font-size:13px;
                                                            font-weight:600;
                                                            background:#fff;
                                                            cursor:pointer;
                                                        "
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

                                            </td>

                                            <!-- ACTION -->
                                            <td>

                                                <a
                                                    href="relocation_details.php?id=<?= (int) $plan['id'] ?>"
                                                    class="plan-link"
                                                >
                                                    View Details →
                                                </a>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

                <!-- SUMMARY CARDS -->

                <div
                    style="
                        display:grid;
                        grid-template-columns:repeat(3,1fr);
                        gap:20px;
                        margin-top:20px;
                    "
                >

                    <div class="card">

                        <div class="card-subtitle">
                            TOTAL PLANS
                        </div>

                        <div
                            style="
                                font-size:30px;
                                font-weight:700;
                                margin-top:8px;
                            "
                        >
                            <?= $totalPlans ?>
                        </div>

                    </div>

                    <div class="card">

                        <div class="card-subtitle">
                            IMMEDIATE PRIORITY
                        </div>

                        <div
                            style="
                                font-size:30px;
                                font-weight:700;
                                margin-top:8px;
                                color:#dc2626;
                            "
                        >
                            <?= $immediatePlans ?>
                        </div>

                    </div>

                    <div class="card">

                        <div class="card-subtitle">
                            PEOPLE TO RELOCATE
                        </div>

                        <div
                            style="
                                font-size:30px;
                                font-weight:700;
                                margin-top:8px;
                            "
                        >
                            <?= number_format($totalPeople) ?>
                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

<script>

const searchInput =
    document.getElementById("searchPlan");

const table =
    document.getElementById("plansTable");

if (searchInput && table) {

    searchInput.addEventListener(
        "input",
        function () {

            const query =
                this.value.toLowerCase().trim();

            const rows =
                table.querySelectorAll("tr");

            rows.forEach(function (row) {

                const text =
                    row.innerText.toLowerCase();

                row.style.display =
                    text.includes(query)
                        ? ""
                        : "none";

            });

        }
    );

}

</script>

</body>

</html>