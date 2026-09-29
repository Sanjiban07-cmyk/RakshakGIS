<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/config/auth.php";
requireLogin();

require_once __DIR__ . "/config/database.php";

$user = currentUser();

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$totalHabitations = 0;
$totalRelocationSites = 0;
$totalPlans = 0;
$totalAssessments = 0;
$highRisk = 0;
$redZones = 0;
$immediateRelocation = 0;


/* Total habitations */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM habitations
");

if ($result) {
    $totalHabitations = (int) $result->fetch_assoc()['total'];
}


/* Total relocation sites */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM relocation_sites
");

if ($result) {
    $totalRelocationSites = (int) $result->fetch_assoc()['total'];
}


/* Total relocation plans */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM relocation_plans
");

if ($result) {
    $totalPlans = (int) $result->fetch_assoc()['total'];
}


/* Total assessments */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM risk_assessments
");

if ($result) {
    $totalAssessments = (int) $result->fetch_assoc()['total'];
}


/* High risk */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM risk_assessments
    WHERE risk_level = 'HIGH'
");

if ($result) {
    $highRisk = (int) $result->fetch_assoc()['total'];
}


/* Red zones */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM risk_assessments
    WHERE red_zone = 1
");

if ($result) {
    $redZones = (int) $result->fetch_assoc()['total'];
}


/* Immediate relocation */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM risk_assessments
    WHERE relocation_priority = 'IMMEDIATE'
");

if ($result) {
    $immediateRelocation = (int) $result->fetch_assoc()['total'];
}


/*
|--------------------------------------------------------------------------
| Recent Habitations
|--------------------------------------------------------------------------
*/

$recentHabitations = [];

$result = $conn->query("
    SELECT
        h.id,
        h.name,
        h.district,
        h.state,
        h.population,

        ra.risk_score,
        ra.risk_level,
        ra.red_zone,
        ra.relocation_priority,
        ra.assessed_at

    FROM habitations h

    LEFT JOIN risk_assessments ra
        ON ra.id = (
            SELECT ra2.id
            FROM risk_assessments ra2
            WHERE ra2.habitation_id = h.id
            ORDER BY ra2.assessed_at DESC
            LIMIT 1
        )

    ORDER BY h.created_at DESC

    LIMIT 8
");

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $recentHabitations[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function riskBadgeClass(?string $risk): string
{
    return match ($risk) {
        'HIGH' => 'badge-high',
        'MEDIUM' => 'badge-medium',
        'LOW' => 'badge-low',
        default => ''
    };
}


function priorityClass(?string $priority): string
{
    return match ($priority) {
        'IMMEDIATE' => 'priority-immediate',
        'SHORT-TERM' => 'priority-short',
        'MEDIUM-TERM' => 'priority-medium',
        default => 'priority-none'
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

    <meta
        name="description"
        content="RakshakGIS Disaster Risk Assessment Dashboard"
    >

    <title>Dashboard | RakshakGIS</title>


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        /* =========================================
           DASHBOARD EXTENSIONS
           ========================================= */

        .dashboard-welcome {
            margin-bottom: 25px;
        }

        .dashboard-welcome h1 {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .dashboard-welcome p {
            margin-top: 5px;
            font-size: 13px;
            color: var(--muted);
        }


        /* Sidebar logo */

        .logo {
            text-decoration: none;
            color: white;
        }

        .logo-icon {
            position: relative;
            overflow: hidden;
        }

        .logo-icon::after {
            content: "";
            position: absolute;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.7);
            border-radius: 50%;
        }


        /* Stat card */

        .stat-card {
            transition: 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(15,23,42,0.06);
        }


        .stat-icon.danger {
            background: #fef2f2;
            color: var(--danger);
        }

        .stat-icon.warning {
            background: #fffbeb;
            color: var(--warning);
        }

        .stat-icon.success {
            background: #f0fdf4;
            color: var(--success);
        }


        /* Dashboard grid */

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }


        .dashboard-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }


        .dashboard-card-header {
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }


        .dashboard-card-header h3 {
            font-size: 14px;
            font-weight: 700;
        }


        .dashboard-card-header span {
            font-size: 11px;
            color: var(--muted);
        }


        .dashboard-card-body {
            padding: 20px;
        }


        /* Risk overview */

        .risk-overview {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }


        .risk-box {
            border-radius: 10px;
            padding: 17px;
            border: 1px solid var(--border);
        }


        .risk-box-label {
            font-size: 11px;
            color: var(--muted);
        }


        .risk-box-value {
            font-size: 25px;
            font-weight: 800;
            margin-top: 6px;
        }


        .risk-box.high {
            background: #fff7f7;
            border-color: #fecaca;
        }

        .risk-box.high .risk-box-value {
            color: #dc2626;
        }


        .risk-box.red {
            background: #fff7f7;
            border-color: #fecaca;
        }

        .risk-box.red .risk-box-value {
            color: #991b1b;
        }


        .risk-box.relocation {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .risk-box.relocation .risk-box-value {
            color: #d97706;
        }


        /* Quick actions */

        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }


        .quick-action {
            display: flex;
            align-items: center;
            gap: 10px;

            padding: 13px;

            border: 1px solid var(--border);
            border-radius: 9px;

            color: var(--text);
            text-decoration: none;

            font-size: 12px;
            font-weight: 600;

            transition: 0.2s ease;
        }


        .quick-action:hover {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: var(--primary);
        }


        .quick-action-icon {
            width: 32px;
            height: 32px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: #eff6ff;
            color: var(--primary);
        }


        /* Table */

        .risk-table {
            width: 100%;
        }


        .risk-table td {
            vertical-align: middle;
        }


        .habitation-name {
            font-weight: 700;
            color: var(--text);
        }


        .habitation-location {
            font-size: 11px;
            color: var(--muted);
            margin-top: 3px;
        }


        .no-assessment {
            color: #94a3b8;
            font-size: 11px;
        }


        /* Priority */

        .priority-badge {
            display: inline-flex;
            padding: 5px 8px;
            border-radius: 999px;

            font-size: 10px;
            font-weight: 700;
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


        /* User menu */

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }


        .user-avatar {
            width: 36px;
            height: 36px;

            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #dbeafe;
            color: var(--primary);

            font-size: 13px;
            font-weight: 800;
        }


        .user-details {
            line-height: 1.2;
        }


        .user-name {
            font-size: 12px;
            font-weight: 700;
        }


        .user-role {
            font-size: 10px;
            color: var(--muted);
            margin-top: 3px;
        }


        /* Sidebar bottom */

        .sidebar-user {
            padding: 15px 14px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }


        .sidebar-user-inner {
            display: flex;
            align-items: center;
            gap: 10px;
        }


        .sidebar-avatar {
            width: 34px;
            height: 34px;

            border-radius: 9px;

            background: #2563eb;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 12px;
            font-weight: 800;
        }


        .sidebar-user-info {
            min-width: 0;
            flex: 1;
        }


        .sidebar-user-name {
            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        .sidebar-user-role {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 3px;
        }


        .sidebar-logout {
            color: #94a3b8;
            text-decoration: none;
            font-size: 16px;
        }


        .sidebar-logout:hover {
            color: #f87171;
        }


        /* Mobile */

        @media (max-width: 1000px) {

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 700px) {

            .topbar-right .status {
                display: none;
            }

            .user-details {
                display: none;
            }

            .risk-overview {
                grid-template-columns: 1fr;
            }

            .quick-actions {
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


        <!-- LOGO -->

        <a
            href="index.php"
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



        <!-- NAVIGATION -->

        <nav class="sidebar-nav">


            <div class="nav-section">
                Main
            </div>


            <a
                href="index.php"
                class="nav-link active"
            >

                <span class="nav-icon">⌂</span>

                Dashboard

            </a>


            <a
                href="pages/habitations.php"
                class="nav-link"
            >

                <span class="nav-icon">⌖</span>

                Habitations

            </a>


            <a
                href="pages/risk_assessment.php"
                class="nav-link"
            >

                <span class="nav-icon">⚠</span>

                Risk Assessment

            </a>


            <a
                href="pages/risk_map.php"
                class="nav-link"
            >

                <span class="nav-icon">◎</span>

                Risk Map

            </a>



            <div class="nav-section">
                Relocation
            </div>


            <a
                href="pages/relocation_sites.php"
                class="nav-link"
            >

                <span class="nav-icon">⌂</span>

                Relocation Sites

            </a>


            <a
                href="pages/relocation_plans.php"
                class="nav-link"
            >

                <span class="nav-icon">→</span>

                Relocation Plans

            </a>



            <div class="nav-section">
                System
            </div>


            <a
                href="pages/reports.php"
                class="nav-link"
            >

                <span class="nav-icon">▤</span>

                Reports

            </a>


            <?php if ($user && $user['role'] === 'ADMIN'): ?>

                <a
                    href="pages/admin.php"
                    class="nav-link"
                >

                    <span class="nav-icon">⚙</span>

                    Administration

                </a>

            <?php endif; ?>


        </nav>



        <!-- SIDEBAR USER -->

        <div class="sidebar-user">

            <div class="sidebar-user-inner">


                <div class="sidebar-avatar">

                    <?= strtoupper(
                        substr(
                            $user['name'] ?? 'A',
                            0,
                            1
                        )
                    ) ?>

                </div>


                <div class="sidebar-user-info">

                    <div class="sidebar-user-name">

                        <?= htmlspecialchars(
                            $user['name'] ?? 'Administrator'
                        ) ?>

                    </div>


                    <div class="sidebar-user-role">

                        <?= htmlspecialchars(
                            $user['role'] ?? 'USER'
                        ) ?>

                    </div>

                </div>


                <a
                    href="logout.php"
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
                    Dashboard
                </div>

                <div class="page-subtitle">
                    Disaster risk monitoring and relocation overview
                </div>

            </div>


            <div class="topbar-right">


                <div class="status">

                    <span class="status-dot"></span>

                    System Operational

                </div>


                <div class="user-profile">


                    <div class="user-avatar">

                        <?= strtoupper(
                            substr(
                                $user['name'] ?? 'A',
                                0,
                                1
                            )
                        ) ?>

                    </div>


                    <div class="user-details">

                        <div class="user-name">

                            <?= htmlspecialchars(
                                $user['name'] ?? 'Administrator'
                            ) ?>

                        </div>


                        <div class="user-role">

                            <?= htmlspecialchars(
                                $user['role'] ?? 'USER'
                            ) ?>

                        </div>

                    </div>

                </div>


            </div>

        </header>



        <!-- CONTENT -->

        <div class="content">


            <!-- WELCOME -->

            <div class="dashboard-welcome">

                <h1>
                    Welcome back, <?= htmlspecialchars(
                        $user['name'] ?? 'Administrator'
                    ) ?>
                </h1>

                <p>
                    Here's the current overview of your disaster
                    risk management system.
                </p>

            </div>



            <!-- =================================================
                 STATISTICS
                 ================================================= -->

            <div class="stats-grid">


                <!-- HABITATIONS -->

                <div class="stat-card">

                    <div class="stat-header">

                        <div class="stat-label">
                            Total Habitations
                        </div>

                        <div class="stat-icon">

                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >

                                <path d="M3 21h18"/>

                                <path d="M5 21V7l7-4 7 4v14"/>

                                <path d="M9 21v-6h6v6"/>

                            </svg>

                        </div>

                    </div>


                    <div class="stat-value">
                        <?= $totalHabitations ?>
                    </div>


                    <div class="stat-description">
                        Registered habitations
                    </div>

                </div>



                <!-- HIGH RISK -->

                <div class="stat-card">

                    <div class="stat-header">

                        <div class="stat-label">
                            High Risk
                        </div>

                        <div class="stat-icon danger">

                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >

                                <path d="M12 3L22 20H2L12 3Z"/>

                                <path d="M12 9v5"/>

                                <path d="M12 17h.01"/>

                            </svg>

                        </div>

                    </div>


                    <div class="stat-value">
                        <?= $highRisk ?>
                    </div>


                    <div class="stat-description">
                        Assessed high-risk locations
                    </div>

                </div>



                <!-- RED ZONE -->

                <div class="stat-card">

                    <div class="stat-header">

                        <div class="stat-label">
                            Red Zones
                        </div>

                        <div class="stat-icon danger">

                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                />

                                <path d="M12 8v4"/>

                                <path d="M12 16h.01"/>

                            </svg>

                        </div>

                    </div>


                    <div class="stat-value">
                        <?= $redZones ?>
                    </div>


                    <div class="stat-description">
                        Habitations marked red zone
                    </div>

                </div>



                <!-- RELOCATION -->

                <div class="stat-card">

                    <div class="stat-header">

                        <div class="stat-label">
                            Relocation Plans
                        </div>

                        <div class="stat-icon warning">

                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >

                                <path d="M5 12h14"/>

                                <path d="M13 6l6 6-6 6"/>

                            </svg>

                        </div>

                    </div>


                    <div class="stat-value">
                        <?= $totalPlans ?>
                    </div>


                    <div class="stat-description">
                        Active relocation plans
                    </div>

                </div>


            </div>



            <!-- =================================================
                 SECONDARY GRID
                 ================================================= -->

            <div class="dashboard-grid">


                <!-- RISK OVERVIEW -->

                <div class="dashboard-card">


                    <div class="dashboard-card-header">

                        <h3>
                            Risk Overview
                        </h3>

                        <span>
                            Latest assessments
                        </span>

                    </div>


                    <div class="dashboard-card-body">


                        <div class="risk-overview">


                            <div class="risk-box high">

                                <div class="risk-box-label">
                                    High Risk
                                </div>

                                <div class="risk-box-value">
                                    <?= $highRisk ?>
                                </div>

                            </div>


                            <div class="risk-box red">

                                <div class="risk-box-label">
                                    Red Zone
                                </div>

                                <div class="risk-box-value">
                                    <?= $redZones ?>
                                </div>

                            </div>


                            <div class="risk-box relocation">

                                <div class="risk-box-label">
                                    Immediate Relocation
                                </div>

                                <div class="risk-box-value">
                                    <?= $immediateRelocation ?>
                                </div>

                            </div>


                        </div>


                    </div>

                </div>



                <!-- QUICK ACTIONS -->

                <div class="dashboard-card">


                    <div class="dashboard-card-header">

                        <h3>
                            Quick Actions
                        </h3>

                    </div>


                    <div class="dashboard-card-body">


                        <div class="quick-actions">


                            <a
                                href="pages/habitations.php"
                                class="quick-action"
                            >

                                <div class="quick-action-icon">
                                    +
                                </div>

                                Add Habitation

                            </a>


                            <a
                                href="pages/risk_assessment.php"
                                class="quick-action"
                            >

                                <div class="quick-action-icon">
                                    ⚠
                                </div>

                                New Assessment

                            </a>


                            <a
                                href="pages/relocation_sites.php"
                                class="quick-action"
                            >

                                <div class="quick-action-icon">
                                    ⌖
                                </div>

                                View Safe Sites

                            </a>


                            <a
                                href="pages/reports.php"
                                class="quick-action"
                            >

                                <div class="quick-action-icon">
                                    ▤
                                </div>

                                Generate Report

                            </a>


                        </div>


                    </div>

                </div>


            </div>



            <!-- =================================================
                 HABITATION TABLE
                 ================================================= -->

            <div class="dashboard-card" style="margin-top:20px;">


                <div class="dashboard-card-header">

                    <div>

                        <h3>
                            Habitation Risk Status
                        </h3>

                        <span>
                            Latest available assessment for each habitation
                        </span>

                    </div>


                    <a
                        href="pages/habitations.php"
                        class="btn btn-secondary"
                    >
                        View All
                    </a>

                </div>



                <div class="table-wrapper">


                    <table class="risk-table">

                        <thead>

                            <tr>

                                <th>
                                    Habitation
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
                                    Relocation
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($recentHabitations)): ?>

                            <tr>

                                <td
                                    colspan="6"
                                    style="text-align:center;padding:30px;"
                                >

                                    No habitation records found.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($recentHabitations as $habitation): ?>

                                <tr>


                                    <!-- NAME -->

                                    <td>

                                        <div class="habitation-name">

                                            <?= htmlspecialchars(
                                                $habitation['name']
                                            ) ?>

                                        </div>


                                        <div class="habitation-location">

                                            <?= htmlspecialchars(
                                                $habitation['district']
                                            ) ?>,
                                            <?= htmlspecialchars(
                                                $habitation['state']
                                            ) ?>

                                        </div>

                                    </td>



                                    <!-- POPULATION -->

                                    <td>

                                        <?= number_format(
                                            (int) $habitation['population']
                                        ) ?>

                                    </td>



                                    <!-- SCORE -->

                                    <td>

                                        <?php if (
                                            $habitation['risk_score'] !== null
                                        ): ?>

                                            <strong>
                                                <?= number_format(
                                                    (float) $habitation['risk_score'],
                                                    1
                                                ) ?>
                                            </strong>

                                        <?php else: ?>

                                            <span class="no-assessment">
                                                Not assessed
                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- RISK -->

                                    <td>

                                        <?php if (
                                            $habitation['risk_level']
                                        ): ?>

                                            <span
                                                class="badge <?= riskBadgeClass(
                                                    $habitation['risk_level']
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $habitation['risk_level']
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="no-assessment">
                                                Pending
                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- RED ZONE -->

                                    <td>

                                        <?php if (
                                            $habitation['red_zone'] !== null
                                        ): ?>

                                            <?php if (
                                                (int) $habitation['red_zone'] === 1
                                            ): ?>

                                                <span class="badge badge-high">
                                                    YES
                                                </span>

                                            <?php else: ?>

                                                <span class="badge badge-low">
                                                    NO
                                                </span>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            <span class="no-assessment">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- RELOCATION -->

                                    <td>

                                        <?php if (
                                            $habitation['relocation_priority']
                                        ): ?>

                                            <span
                                                class="priority-badge <?= priorityClass(
                                                    $habitation['relocation_priority']
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $habitation['relocation_priority']
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="no-assessment">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>



            <!-- SYSTEM SUMMARY -->

            <div
                style="
                    margin-top:18px;
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:15px;
                    flex-wrap:wrap;
                "
            >

                <div
                    style="
                        font-size:11px;
                        color:var(--muted);
                    "
                >

                    <?= $totalAssessments ?>
                    risk assessments recorded ·
                    <?= $totalRelocationSites ?>
                    relocation sites available

                </div>


                <div
                    style="
                        font-size:11px;
                        color:var(--muted);
                    "
                >

                    RakshakGIS Dashboard

                </div>

            </div>


        </div>

    </main>

</div>


</body>

</html>