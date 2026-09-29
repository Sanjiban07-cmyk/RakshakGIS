<?php
$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | RakshakGIS</title>

    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* ================================
           DASHBOARD SPECIFIC
        ================================= */

        .hero {
            height: 170px;
            border-radius: 14px;
            margin-bottom: 22px;

            background:
                linear-gradient(
                    90deg,
                    rgba(15, 23, 42, .88),
                    rgba(15, 23, 42, .45)
                ),
                url('https://images.unsplash.com/photo-1547683905-f686c993aae5?auto=format&fit=crop&w=1600&q=80');

            background-size: cover;
            background-position: center;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 30px;
            color: white;
        }

        .hero-content h1 {
            font-size: 34px;
            margin-bottom: 5px;
        }

        .hero-content p {
            color: #dbeafe;
            font-size: 15px;
        }

        .hero-tags {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }

        .hero-tag {
            font-size: 10px;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: #dbeafe;
        }

        .hero-message {
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            padding: 18px 22px;
            border-radius: 12px;
            min-width: 210px;
            backdrop-filter: blur(8px);
        }

        .hero-message strong {
            display: block;
            font-size: 15px;
            margin-bottom: 5px;
        }

        .hero-message span {
            color: #dbeafe;
            font-size: 12px;
        }

        /* STAT CARDS */

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .dashboard-stat {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px;
            min-height: 125px;
        }

        .dashboard-stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 18px;
        }

        .blue-icon {
            background: #dbeafe;
            color: #2563eb;
        }

        .red-icon {
            background: #fee2e2;
            color: #dc2626;
        }

        .orange-icon {
            background: #fef3c7;
            color: #d97706;
        }

        .green-icon {
            background: #dcfce7;
            color: #16a34a;
        }

        .purple-icon {
            background: #ede9fe;
            color: #7c3aed;
        }

        .dashboard-stat-label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 600;
        }

        .dashboard-stat-value {
            font-size: 27px;
            font-weight: 750;
            margin-top: 12px;
        }

        .dashboard-stat-description {
            font-size: 10px;
            color: var(--muted);
            margin-top: 4px;
        }

        /* DASHBOARD ROW */

        .dashboard-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 18px;
            margin-bottom: 18px;
        }

        .dashboard-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }

        .dashboard-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .dashboard-card-title {
            font-size: 15px;
            font-weight: 700;
        }

        .dashboard-card-subtitle {
            font-size: 11px;
            color: var(--muted);
            margin-top: 3px;
        }

        /* RISK DONUT */

        .risk-layout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
        }

        .risk-donut {
            width: 145px;
            height: 145px;
            border-radius: 50%;

            background:
                conic-gradient(
                    #dc2626 0deg 90deg,
                    #f59e0b 90deg 90deg,
                    #16a34a 90deg 90deg,
                    #cbd5e1 90deg 360deg
                );

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .risk-donut-inner {
            width: 92px;
            height: 92px;
            background: white;
            border-radius: 50%;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .risk-donut-inner strong {
            font-size: 25px;
        }

        .risk-donut-inner span {
            color: var(--muted);
            font-size: 10px;
        }

        .risk-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .risk-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }

        .risk-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .risk-high {
            background: #dc2626;
        }

        .risk-medium {
            background: #f59e0b;
        }

        .risk-low {
            background: #16a34a;
        }

        .risk-none {
            background: #cbd5e1;
        }

        .risk-item strong {
            margin-left: auto;
        }

        /* CAPACITY */

        .capacity-container {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .capacity-ring {
            width: 145px;
            height: 145px;
            border-radius: 50%;

            background:
                conic-gradient(
                    #16a34a 0deg 137deg,
                    #e2e8f0 137deg 360deg
                );

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .capacity-inner {
            width: 92px;
            height: 92px;
            border-radius: 50%;
            background: white;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .capacity-inner strong {
            font-size: 23px;
        }

        .capacity-inner span {
            font-size: 10px;
            color: var(--muted);
        }

        .capacity-values {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .capacity-value {
            font-size: 12px;
        }

        .capacity-value strong {
            display: block;
            font-size: 17px;
            margin-top: 2px;
        }

        .capacity-label {
            color: var(--muted);
        }

        /* QUICK ACTIONS */

        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .quick-action {
            padding: 15px;
            border-radius: 10px;
            text-decoration: none;
            border: 1px solid transparent;
            transition: .2s;
        }

        .quick-action:hover {
            transform: translateY(-2px);
        }

        .quick-action-icon {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .quick-action strong {
            display: block;
            font-size: 12px;
        }

        .quick-action span {
            display: block;
            font-size: 10px;
            margin-top: 3px;
            opacity: .8;
        }

        .qa-blue {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .qa-green {
            background: #ecfdf5;
            color: #15803d;
        }

        .qa-purple {
            background: #f5f3ff;
            color: #6d28d9;
        }

        .qa-orange {
            background: #fff7ed;
            color: #c2410c;
        }

        /* MAP */

        .map-card {
            grid-column: span 2;
        }

        #dashboardMap {
            height: 350px;
            border-radius: 10px;
            overflow: hidden;
        }

        .map-legend {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 12px;
            font-size: 11px;
            color: var(--muted);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .legend-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
        }

        /* TABLE */

        .table-card {
            margin-bottom: 18px;
        }

        .view-all {
            color: var(--primary);
            font-size: 11px;
            text-decoration: none;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 25px;
            color: var(--muted);
            font-size: 12px;
        }

        /* SIDEBAR BRAND */

        .brand-link {
            text-decoration: none;
            color: white;
        }

        /* MOBILE */

        @media (max-width: 1200px) {

            .dashboard-stats {
                grid-template-columns: repeat(3, 1fr);
            }

            .dashboard-row {
                grid-template-columns: 1fr 1fr;
            }

            .map-card {
                grid-column: span 2;
            }
        }

        @media (max-width: 800px) {

            .dashboard-stats {
                grid-template-columns: 1fr 1fr;
            }

            .dashboard-row {
                grid-template-columns: 1fr;
            }

            .map-card {
                grid-column: span 1;
            }

            .hero {
                height: auto;
                gap: 20px;
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 500px) {

            .dashboard-stats {
                grid-template-columns: 1fr;
            }

            .risk-layout,
            .capacity-container {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="app">

    <!-- ==========================================
         SIDEBAR
    =========================================== -->

    <aside class="sidebar">

        <div class="logo">

            <div class="logo-icon">R</div>

            <div>
                <div class="logo-text">RakshakGIS</div>
                <div class="logo-subtitle">
                    Disaster Risk & Relocation
                </div>
            </div>

        </div>

        <nav class="sidebar-nav">

            <div class="nav-section">Main</div>

            <a href="dashboard.php"
               class="nav-link active">
                <span class="nav-icon">⌂</span>
                Dashboard
            </a>

            <a href="habitations.php"
               class="nav-link">
                <span class="nav-icon">⌂</span>
                Habitations
            </a>

            <a href="add_habitation.php"
               class="nav-link">
                <span class="nav-icon">＋</span>
                Add Habitation
            </a>

            <a href="risk_assessment.php"
               class="nav-link">
                <span class="nav-icon">⚠</span>
                Risk Assessment
            </a>

            <a href="map.php"
               class="nav-link">
                <span class="nav-icon">●</span>
                Interactive Map
            </a>

            <a href="relocation.php"
               class="nav-link">
                <span class="nav-icon">⌖</span>
                Relocation Planner
            </a>

            <a href="relocation_details.php"
               class="nav-link">
                <span class="nav-icon">▣</span>
                Relocation Plans
            </a>

            <a href="reports.php"
               class="nav-link">
                <span class="nav-icon">▤</span>
                Reports
            </a>

            <div class="nav-section">Information</div>

            <a href="about.php"
               class="nav-link">
                <span class="nav-icon">ⓘ</span>
                About
            </a>

            <a href="contact.php"
               class="nav-link">
                <span class="nav-icon">✉</span>
                Contact
            </a>

        </nav>

    </aside>


    <!-- ==========================================
         MAIN
    =========================================== -->

    <main class="main">

        <!-- TOPBAR -->

        <header class="topbar">

            <div>

                <div class="page-title">
                    Dashboard
                </div>

                <div class="page-subtitle">
                    Disaster risk overview & relocation intelligence
                </div>

            </div>

            <div class="topbar-right">

                <div class="status">
                    <span class="status-dot"></span>
                    System Online
                </div>

                <div>
                    🔔
                </div>

                <div>
                    <strong>Admin</strong>
                </div>

            </div>

        </header>


        <!-- CONTENT -->

        <section class="content">

            <!-- HERO -->

            <div class="hero">

                <div class="hero-content">

                    <div class="hero-tags">
                        <span class="hero-tag">DATA</span>
                        <span>•</span>
                        <span class="hero-tag">MAPPING</span>
                        <span>•</span>
                        <span class="hero-tag">RISK ANALYSIS</span>
                        <span>•</span>
                        <span class="hero-tag">SAFE RELOCATION</span>
                    </div>

                    <h1>RakshakGIS</h1>

                    <p>
                        Towards Safer and More Resilient Communities
                    </p>

                </div>

                <div class="hero-message">
                    <strong>〰 Early Insight.</strong>
                    <span>Safer Tomorrow.</span>
                </div>

            </div>


            <!-- ==================================
                 STATISTICS
            =================================== -->

            <div class="dashboard-stats">

                <div class="dashboard-stat">

                    <div class="dashboard-stat-top">

                        <span class="dashboard-stat-label">
                            Total Habitations
                        </span>

                        <div class="dashboard-stat-icon blue-icon">
                            ⌂
                        </div>

                    </div>

                    <div class="dashboard-stat-value"
                         id="totalHabitations">
                        —
                    </div>

                    <div class="dashboard-stat-description">
                        Mapped habitations in the system
                    </div>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-top">

                        <span class="dashboard-stat-label">
                            High Risk Habitations
                        </span>

                        <div class="dashboard-stat-icon red-icon">
                            !
                        </div>

                    </div>

                    <div class="dashboard-stat-value"
                         id="highRisk">
                        —
                    </div>

                    <div class="dashboard-stat-description">
                        Requiring immediate attention
                    </div>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-top">

                        <span class="dashboard-stat-label">
                            Red Zones
                        </span>

                        <div class="dashboard-stat-icon orange-icon">
                            ⚑
                        </div>

                    </div>

                    <div class="dashboard-stat-value"
                         id="redZones">
                        —
                    </div>

                    <div class="dashboard-stat-description">
                        High-risk areas identified
                    </div>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-top">

                        <span class="dashboard-stat-label">
                            Relocation Plans
                        </span>

                        <div class="dashboard-stat-icon green-icon">
                            ▣
                        </div>

                    </div>

                    <div class="dashboard-stat-value"
                         id="relocationPlans">
                        —
                    </div>

                    <div class="dashboard-stat-description">
                        Active relocation plans
                    </div>

                </div>


                <div class="dashboard-stat">

                    <div class="dashboard-stat-top">

                        <span class="dashboard-stat-label">
                            Relocation Sites
                        </span>

                        <div class="dashboard-stat-icon purple-icon">
                            ●
                        </div>

                    </div>

                    <div class="dashboard-stat-value"
                         id="relocationSites">
                        —
                    </div>

                    <div class="dashboard-stat-description">
                        Available safe sites
                    </div>

                </div>

            </div>


            <!-- ==================================
                 ANALYTICS ROW
            =================================== -->

            <div class="dashboard-row">

                <!-- RISK -->

                <div class="dashboard-card">

                    <div class="dashboard-card-header">

                        <div>

                            <div class="dashboard-card-title">
                                Habitations by Risk Level
                            </div>

                            <div class="dashboard-card-subtitle">
                                Current risk assessment distribution
                            </div>

                        </div>

                    </div>

                    <div class="risk-layout">

                        <div class="risk-donut"
                             id="riskDonut">

                            <div class="risk-donut-inner">

                                <strong id="riskTotal">
                                    —
                                </strong>

                                <span>Total</span>

                            </div>

                        </div>

                        <div class="risk-list">

                            <div class="risk-item">

                                <span class="risk-dot risk-high"></span>

                                High Risk

                                <strong id="riskHigh">
                                    0
                                </strong>

                            </div>

                            <div class="risk-item">

                                <span class="risk-dot risk-medium"></span>

                                Medium Risk

                                <strong id="riskMedium">
                                    0
                                </strong>

                            </div>

                            <div class="risk-item">

                                <span class="risk-dot risk-low"></span>

                                Low Risk

                                <strong id="riskLow">
                                    0
                                </strong>

                            </div>

                            <div class="risk-item">

                                <span class="risk-dot risk-none"></span>

                                Not Assessed

                                <strong id="riskNone">
                                    0
                                </strong>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- CAPACITY -->

                <div class="dashboard-card">

                    <div class="dashboard-card-header">

                        <div>

                            <div class="dashboard-card-title">
                                Relocation Sites Capacity
                            </div>

                            <div class="dashboard-card-subtitle">
                                Current available capacity
                            </div>

                        </div>

                    </div>

                    <div class="capacity-container">

                        <div class="capacity-ring"
                             id="capacityRing">

                            <div class="capacity-inner">

                                <strong id="capacityPercent">
                                    —
                                </strong>

                                <span>Available</span>

                            </div>

                        </div>

                        <div class="capacity-values">

                            <div class="capacity-value">

                                <span class="capacity-label">
                                    Total Capacity
                                </span>

                                <strong id="totalCapacity">
                                    —
                                </strong>

                            </div>

                            <div class="capacity-value">

                                <span class="capacity-label">
                                    Available Capacity
                                </span>

                                <strong id="availableCapacity">
                                    —
                                </strong>

                            </div>

                            <div class="capacity-value">

                                <span class="capacity-label">
                                    Occupied Capacity
                                </span>

                                <strong id="occupiedCapacity">
                                    —
                                </strong>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- QUICK ACTIONS -->

                <div class="dashboard-card">

                    <div class="dashboard-card-header">

                        <div>

                            <div class="dashboard-card-title">
                                Quick Actions
                            </div>

                            <div class="dashboard-card-subtitle">
                                Frequently used operations
                            </div>

                        </div>

                    </div>

                    <div class="quick-actions">

                        <a href="add_habitation.php"
                           class="quick-action qa-blue">

                            <div class="quick-action-icon">
                                ＋
                            </div>

                            <strong>Add Habitation</strong>

                            <span>
                                Enter new habitation data
                            </span>

                        </a>


                        <a href="risk_assessment.php"
                           class="quick-action qa-green">

                            <div class="quick-action-icon">
                                ♟
                            </div>

                            <strong>Assess Risk</strong>

                            <span>
                                Calculate risk level
                            </span>

                        </a>


                        <a href="map.php"
                           class="quick-action qa-purple">

                            <div class="quick-action-icon">
                                ⌖
                            </div>

                            <strong>View Map</strong>

                            <span>
                                Explore spatial data
                            </span>

                        </a>


                        <a href="relocation.php"
                           class="quick-action qa-orange">

                            <div class="quick-action-icon">
                                ⚠
                            </div>

                            <strong>Plan Relocation</strong>

                            <span>
                                Find safe relocation sites
                            </span>

                        </a>

                    </div>

                </div>

            </div>


            <!-- ==================================
                 MAP + RECENT DATA
            =================================== -->

            <div class="dashboard-row">

                <!-- MAP -->

                <div class="dashboard-card map-card">

                    <div class="dashboard-card-header">

                        <div>

                            <div class="dashboard-card-title">
                                Habitation & Relocation Sites
                            </div>

                            <div class="dashboard-card-subtitle">
                                Spatial overview of risk and safe sites
                            </div>

                        </div>

                        <a href="map.php"
                           class="view-all">
                            View Full Map →
                        </a>

                    </div>

                    <div id="dashboardMap"></div>

                    <div class="map-legend">

                        <div class="legend-item">
                            <span class="legend-dot"
                                  style="background:#dc2626"></span>
                            High Risk
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot"
                                  style="background:#f59e0b"></span>
                            Medium Risk
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot"
                                  style="background:#16a34a"></span>
                            Low Risk
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot"
                                  style="background:#2563eb"></span>
                            Relocation Site
                        </div>

                    </div>

                </div>


                <!-- RECENT HABITATIONS -->

                <div class="dashboard-card">

                    <div class="dashboard-card-header">

                        <div>

                            <div class="dashboard-card-title">
                                Recent Habitations
                            </div>

                            <div class="dashboard-card-subtitle">
                                Latest records
                            </div>

                        </div>

                        <a href="habitations.php"
                           class="view-all">
                            View All →
                        </a>

                    </div>

                    <div class="table-wrapper">

                        <table>

                            <thead>

                                <tr>
                                    <th>Name</th>
                                    <th>District</th>
                                    <th>Risk</th>
                                    <th>Population</th>
                                </tr>

                            </thead>

                            <tbody id="recentHabitations">

                                <tr>
                                    <td colspan="4"
                                        class="empty-state">
                                        Loading...
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- ==================================
                 RECENT RELOCATION PLANS
            =================================== -->

            <div class="dashboard-card table-card">

                <div class="dashboard-card-header">

                    <div>

                        <div class="dashboard-card-title">
                            Recent Relocation Plans
                        </div>

                        <div class="dashboard-card-subtitle">
                            Latest relocation recommendations
                        </div>

                    </div>

                    <a href="relocation_details.php"
                       class="view-all">
                        View All →
                    </a>

                </div>

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>Habitation</th>
                                <th>Relocation Site</th>
                                <th>People</th>
                                <th>Distance</th>
                                <th>Status</th>

                            </tr>

                        </thead>

                        <tbody id="recentPlans">

                            <tr>
                                <td colspan="5"
                                    class="empty-state">
                                    Loading...
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </main>

</div>


<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

document.addEventListener("DOMContentLoaded", function () {

    loadDashboard();
    loadMap();

});


/* =========================================
   DASHBOARD API
========================================= */

function loadDashboard() {

    fetch("../api/dashboard_stats.php")

        .then(response => response.json())

        .then(data => {

            if (!data.success) {
                console.error(data);
                return;
            }

            const stats = data.statistics;

            document.getElementById("totalHabitations").textContent =
                stats.total_habitations ?? 0;

            document.getElementById("highRisk").textContent =
                stats.high_risk ?? 0;

            document.getElementById("redZones").textContent =
                stats.red_zones ?? 0;

           const activeRelocationPlans =
    Number(stats.planned_relocation_plans ?? 0) +
    Number(stats.approved_relocation_plans ?? 0) +
    Number(stats.in_progress_relocation_plans ?? 0);

document.getElementById("relocationPlans").textContent =
    activeRelocationPlans;

            document.getElementById("relocationSites").textContent =
                stats.total_relocation_sites ?? 0;


            /* RISK */

            const total = Number(stats.total_habitations ?? 0);

            const high = Number(stats.high_risk ?? 0);
            const medium = Number(stats.medium_risk ?? 0);
            const low = Number(stats.low_risk ?? 0);

            const assessed = high + medium + low;

            const notAssessed =
                Math.max(total - assessed, 0);

            document.getElementById("riskTotal").textContent =
                total;

            document.getElementById("riskHigh").textContent =
                high;

            document.getElementById("riskMedium").textContent =
                medium;

            document.getElementById("riskLow").textContent =
                low;

            document.getElementById("riskNone").textContent =
                notAssessed;


            /* CAPACITY */

            const totalCapacity =
                Number(stats.total_site_capacity ?? 0);

            const availableCapacity =
                Number(stats.available_site_capacity ?? 0);

            const occupiedCapacity =
                Math.max(totalCapacity - availableCapacity, 0);

            document.getElementById("totalCapacity").textContent =
                totalCapacity.toLocaleString();

            document.getElementById("availableCapacity").textContent =
                availableCapacity.toLocaleString();

            document.getElementById("occupiedCapacity").textContent =
                occupiedCapacity.toLocaleString();


            let percentage = 0;

            if (totalCapacity > 0) {

                percentage =
                    Math.round(
                        (availableCapacity / totalCapacity) * 100
                    );

            }

            document.getElementById("capacityPercent").textContent =
                percentage + "%";

            document.getElementById("capacityRing").style.background =
                `conic-gradient(
                    #16a34a 0deg ${percentage * 3.6}deg,
                    #e2e8f0 ${percentage * 3.6}deg 360deg
                )`;


            /* RECENT ASSESSMENTS */

            const recentAssessments =
                data.recent_assessments || [];

            const habitationBody =
                document.getElementById("recentHabitations");

            if (recentAssessments.length === 0) {

                habitationBody.innerHTML = `
                    <tr>
                        <td colspan="4"
                            class="empty-state">
                            No assessments available
                        </td>
                    </tr>
                `;

            } else {

                habitationBody.innerHTML =
                    recentAssessments.map(row => {

                        const level =
                            row.risk_level || "NOT ASSESSED";

                        let badgeClass = "badge-low";

                        if (level === "HIGH") {
                            badgeClass = "badge-high";
                        }
                        else if (level === "MEDIUM") {
                            badgeClass = "badge-medium";
                        }

                        return `
                            <tr>

                                <td>
                                    <strong>
                                        ${escapeHtml(row.habitation_name)}
                                    </strong>
                                </td>

                                <td>
                                    ${escapeHtml(row.district)}
                                </td>

                                <td>
                                    <span class="badge ${badgeClass}">
                                        ${escapeHtml(level)}
                                    </span>
                                </td>

                                <td>
                                    ${Number(row.population || 0).toLocaleString()}
                                </td>

                            </tr>
                        `;

                    }).join("");

            }


            /* RECENT PLANS */

            const recentPlans =
                data.recent_relocations || [];

            const plansBody =
                document.getElementById("recentPlans");

            if (recentPlans.length === 0) {

                plansBody.innerHTML = `
                    <tr>
                        <td colspan="5"
                            class="empty-state">
                            No relocation plans available
                        </td>
                    </tr>
                `;

            } else {

                plansBody.innerHTML =
                    recentPlans.map(row => {

                        return `
                            <tr>

                                <td>
                                    <strong>
                                        ${escapeHtml(row.habitation_name)}
                                    </strong>
                                </td>

                                <td>
                                    ${escapeHtml(row.site_name)}
                                </td>

                                <td>
                                    ${Number(
                                        row.population_to_relocate || 0
                                    ).toLocaleString()}
                                </td>

                                <td>
                                    ${row.distance_km ?? "—"} km
                                </td>

                                <td>
                                    <span class="badge badge-low">
                                        ${escapeHtml(row.status || "PLANNED")}
                                    </span>
                                </td>

                            </tr>
                        `;

                    }).join("");

            }

        })

        .catch(error => {

            console.error(
                "Dashboard API Error:",
                error
            );

        });

}


/* =========================================
   MAP API
========================================= */

function loadMap() {

    const map =
        L.map("dashboardMap").setView(
            [19.2, 75.0],
            6
        );


    L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            maxZoom: 19,
            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);


    fetch("../api/get_map_data.php")

        .then(response => response.json())

        .then(data => {

            if (!data.success) {
                return;
            }


            const allPoints = [];


            /* HABITATIONS */

            (data.habitations || []).forEach(habitation => {

                const lat =
                    Number(habitation.latitude);

                const lng =
                    Number(habitation.longitude);

                if (!lat || !lng) {
                    return;
                }

                allPoints.push([lat, lng]);


                let markerColor = "#64748b";

                if (habitation.risk_level === "HIGH") {
                    markerColor = "#dc2626";
                }
                else if (habitation.risk_level === "MEDIUM") {
                    markerColor = "#f59e0b";
                }
                else if (habitation.risk_level === "LOW") {
                    markerColor = "#16a34a";
                }


                const icon =
                    L.divIcon({

                        className: "",

                        html: `
                            <div style="
                                width:20px;
                                height:20px;
                                border-radius:50%;
                                background:${markerColor};
                                border:3px solid white;
                                box-shadow:0 2px 8px rgba(0,0,0,.35);
                            "></div>
                        `,

                        iconSize: [20,20],
                        iconAnchor: [10,10]

                    });


                L.marker(
                    [lat, lng],
                    { icon: icon }
                )

                .addTo(map)

                .bindPopup(`
                    <strong>
                        ${escapeHtml(habitation.name)}
                    </strong>
                    <br>
                    District:
                    ${escapeHtml(habitation.district)}
                    <br>
                    Population:
                    ${Number(
                        habitation.population || 0
                    ).toLocaleString()}
                    <br>
                    Risk:
                    ${escapeHtml(
                        habitation.risk_level || "NOT ASSESSED"
                    )}
                `);

            });


            /* RELOCATION SITES */

            (data.relocation_sites || []).forEach(site => {

                const lat =
                    Number(site.latitude);

                const lng =
                    Number(site.longitude);

                if (!lat || !lng) {
                    return;
                }

                allPoints.push([lat, lng]);


                const icon =
                    L.divIcon({

                        className: "",

                        html: `
                            <div style="
                                width:18px;
                                height:18px;
                                border-radius:4px;
                                background:#2563eb;
                                border:3px solid white;
                                box-shadow:0 2px 8px rgba(0,0,0,.35);
                                transform:rotate(45deg);
                            "></div>
                        `,

                        iconSize: [18,18],
                        iconAnchor: [9,9]

                    });


                L.marker(
                    [lat, lng],
                    { icon: icon }
                )

                .addTo(map)

                .bindPopup(`
                    <strong>
                        ${escapeHtml(site.site_name)}
                    </strong>
                    <br>
                    District:
                    ${escapeHtml(site.district)}
                    <br>
                    Available:
                    ${Number(
                        site.available_capacity || 0
                    ).toLocaleString()}
                    <br>
                    Safety:
                    ${escapeHtml(site.safety_level || "—")}
                `);

            });


            if (allPoints.length > 0) {

                map.fitBounds(
                    allPoints,
                    {
                        padding: [30,30]
                    }
                );

            }

        })

        .catch(error => {

            console.error(
                "Map API Error:",
                error
            );

        });

}


/* =========================================
   SECURITY HELPER
========================================= */

function escapeHtml(value) {

    const div =
        document.createElement("div");

    div.textContent =
        value ?? "";

    return div.innerHTML;

}

</script>

</body>
</html>