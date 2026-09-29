/* =====================================================
   RAKSHAKGIS DASHBOARD POLISH
   ===================================================== */

/* Main dashboard spacing */
.dashboard-content {
    padding-bottom: 40px;
}

/* Dashboard heading */
.dashboard-heading {
    margin-bottom: 20px;
}

.dashboard-heading h1 {
    margin-bottom: 5px;
}

.dashboard-heading p {
    margin-top: 0;
}

/* =====================================================
   WELCOME BANNER
   ===================================================== */

.welcome-card {
    min-height: 125px;
    display: flex;
    align-items: center;
    margin-bottom: 22px;
}

.welcome-content {
    width: 100%;
}

/* =====================================================
   STATISTICS
   ===================================================== */

.dashboard-stats {
    gap: 16px;
    margin-bottom: 24px;
}

.dashboard-stat {
    min-height: 145px;
    transition:
        transform .2s ease,
        box-shadow .2s ease,
        border-color .2s ease;
}

.dashboard-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
    border-color: #dbe4f0;
}

/* =====================================================
   MAIN DASHBOARD GRID
   ===================================================== */

.dashboard-grid {
    grid-template-columns: 1.6fr 1fr;
    gap: 20px;
    align-items: start;
}

.dashboard-grid > div {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.dashboard-grid .dashboard-card {
    margin-bottom: 0;
}

/* =====================================================
   DASHBOARD CARDS
   ===================================================== */

.dashboard-card {
    overflow: hidden;
    border-radius: 14px;
    transition:
        box-shadow .2s ease,
        border-color .2s ease;
}

.dashboard-card:hover {
    box-shadow: 0 7px 22px rgba(15, 23, 42, .06);
}

.dashboard-card-header {
    min-height: 72px;
    display: flex;
    align-items: center;
}

.dashboard-card-body {
    padding: 20px;
}

/* =====================================================
   RISK OVERVIEW
   ===================================================== */

.risk-overview-row {
    margin-bottom: 20px;
}

.risk-overview-row:last-child {
    margin-bottom: 0;
}

.risk-overview-header {
    margin-bottom: 8px;
}

.progress {
    height: 8px;
    border-radius: 999px;
    overflow: hidden;
    background: #e2e8f0;
}

.progress-bar {
    height: 100%;
    border-radius: 999px;
    transition: width .4s ease;
}

/* =====================================================
   HIGH-RISK TABLE
   ===================================================== */

.risk-table {
    width: 100%;
}

.risk-table th {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: .6px;
}

.risk-table td {
    font-size: 10px;
}

.risk-table tbody tr {
    transition: background .15s ease;
}

.risk-table tbody tr:hover {
    background: #f8fafc;
}

/* =====================================================
   RELOCATION OVERVIEW
   ===================================================== */

.relocation-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.relocation-box {
    padding: 16px;
    border-radius: 11px;
    background: #f8fafc;
    border: 1px solid #edf2f7;
    transition:
        transform .2s ease,
        background .2s ease,
        border-color .2s ease;
}

.relocation-box:hover {
    background: #f1f5f9;
    border-color: #e2e8f0;
    transform: translateY(-1px);
}

.relocation-label {
    font-size: 9px;
    color: var(--muted);
}

.relocation-value {
    font-size: 23px;
    font-weight: 900;
    margin-top: 5px;
}

/* =====================================================
   SAFE RELOCATION CAPACITY
   ===================================================== */

.capacity-number {
    margin-bottom: 12px;
}

.capacity-value {
    font-size: 28px;
    font-weight: 900;
}

.capacity-label {
    font-size: 10px;
    color: var(--muted);
}

.capacity-bar {
    height: 10px;
    border-radius: 999px;
    overflow: hidden;
    background: #e2e8f0;
}

.capacity-fill {
    height: 100%;
    border-radius: inherit;
    transition: width .5s ease;
}

.capacity-meta {
    margin-top: 9px;
    font-size: 9px;
}

/* =====================================================
   QUICK ACTIONS
   ===================================================== */

.quick-grid {
    gap: 10px;
}

.quick-link {
    padding: 13px;
    border-radius: 10px;
    transition:
        transform .2s ease,
        background .2s ease,
        border-color .2s ease,
        box-shadow .2s ease;
}

.quick-link:hover {
    transform: translateY(-2px);
    background: #eff6ff;
    border-color: #bfdbfe;
    box-shadow: 0 5px 15px rgba(37, 99, 235, .08);
}

/* =====================================================
   RECENT ASSESSMENTS
   ===================================================== */

.assessment-item {
    padding: 12px 0;
    transition: padding-left .15s ease;
}

.assessment-item:hover {
    padding-left: 4px;
}

.assessment-item:first-child {
    padding-top: 0;
}

.assessment-item:last-child {
    padding-bottom: 0;
    border-bottom: none;
}

/* =====================================================
   EMPTY STATE
   ===================================================== */

.empty-state {
    padding: 32px 20px;
    text-align: center;
    color: #64748b;
    font-size: 11px;
}

/* =====================================================
   DASHBOARD LINKS
   ===================================================== */

.dashboard-card-link {
    font-weight: 700;
    transition:
        color .2s ease,
        transform .2s ease;
}

.dashboard-card-link:hover {
    color: #1e3a8a;
}

/* =====================================================
   SUBTLE DASHBOARD ANIMATION
   ===================================================== */

.dashboard-heading,
.welcome-card,
.dashboard-stats,
.dashboard-grid {
    animation: dashboardFadeIn .35s ease both;
}

@keyframes dashboardFadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* =====================================================
   RESPONSIVE LOWER GRID
   ===================================================== */

@media (max-width: 1100px) {

    .dashboard-grid {
        grid-template-columns: 1fr;
    }

}

@media (max-width: 700px) {

    .dashboard-heading {
        flex-direction: column;
    }

    .dashboard-stats {
        grid-template-columns: 1fr;
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }

    .relocation-grid,
    .quick-grid {
        grid-template-columns: 1fr;
    }

    .dashboard-card-body {
        padding: 16px;
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


<nav class="sidebar-nav">


<div class="nav-section">
    Main
</div>


<a
    href="index.php"
    class="nav-link active"
>

<span class="nav-icon">
    ⌂
</span>

Dashboard

</a>


<a
    href="pages/habitations.php"
    class="nav-link"
>

<span class="nav-icon">
    ⌖
</span>

Habitations

</a>


<a
    href="pages/risk_assessment.php"
    class="nav-link"
>

<span class="nav-icon">
    ⚠
</span>

Risk Assessment

</a>


<a
    href="pages/risk_map.php"
    class="nav-link"
>

<span class="nav-icon">
    ◎
</span>

Risk Map

</a>


<div class="nav-section">
    Relocation
</div>


<a
    href="pages/relocation_sites.php"
    class="nav-link"
>

<span class="nav-icon">
    ⌂
</span>

Relocation Sites

</a>


<a
    href="pages/relocation_plans.php"
    class="nav-link"
>

<span class="nav-icon">
    →
</span>

Relocation Plans

</a>


<div class="nav-section">
    System
</div>


<a
    href="pages/reports.php"
    class="nav-link"
>

<span class="nav-icon">
    ▤
</span>

Reports

</a>


<?php if (
    ($user["role"] ?? "") === "ADMIN"
): ?>

<a
    href="pages/admin.php"
    class="nav-link"
>

<span class="nav-icon">
    ⚙
</span>

Administration

</a>

<?php endif; ?>


</nav>


<!-- SIDEBAR USER -->

<div class="sidebar-user">

<div class="sidebar-user-inner">


<div class="sidebar-avatar">

<?= htmlspecialchars(
    strtoupper(
        substr(
            $user["name"] ?? "A",
            0,
            1
        )
    )
) ?>

</div>


<div class="sidebar-user-info">

<div class="sidebar-user-name">

<?= htmlspecialchars(
    $user["name"]
    ??
    "Administrator"
) ?>

</div>


<div class="sidebar-user-role">

<?= htmlspecialchars(
    $user["role"]
    ??
    "USER"
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


<header class="topbar">


<div>

<div class="page-title">
    Dashboard
</div>

<div class="page-subtitle">
    Disaster risk & safe relocation overview
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


<!-- =====================================================
     HEADING
     ===================================================== -->

<div class="dashboard-heading">


<div>

<h1>
    Dashboard
</h1>

<p>
    Monitor habitation risk, population vulnerability and relocation planning.
</p>

</div>


<div class="dashboard-date">

<?= date("d M Y") ?>

</div>


</div>


<!-- =====================================================
     WELCOME
     ===================================================== -->

<div class="welcome-card">


<div class="welcome-content">


<div class="welcome-label">
    RakshakGIS Command Center
</div>


<div class="welcome-title">

Welcome back,
<?= htmlspecialchars(
    $user["name"]
    ??
    "Administrator"
) ?>

</div>


<div class="welcome-description">

Monitor disaster risk across registered habitations,
review high-risk areas and coordinate safe relocation
planning from one central dashboard.

</div>


</div>


</div>


<!-- =====================================================
     STATISTICS
     ===================================================== -->

<div class="dashboard-stats">


<!-- HABITATIONS -->

<div class="dashboard-stat">

<div class="dashboard-stat-top">

<div class="dashboard-stat-icon">
    ⌖
</div>

</div>


<div class="dashboard-stat-label">
    Total Habitations
</div>


<div class="dashboard-stat-value">

<?= number_format(
    $totalHabitations
) ?>

</div>


<div class="dashboard-stat-description">
    Registered locations
</div>

</div>


<!-- POPULATION -->

<div class="dashboard-stat">

<div class="dashboard-stat-top">

<div class="dashboard-stat-icon">
    👥
</div>

</div>


<div class="dashboard-stat-label">
    Total Population
</div>


<div class="dashboard-stat-value">

<?= number_format(
    $totalPopulation
) ?>

</div>


<div class="dashboard-stat-description">
    Population across habitations
</div>

</div>


<!-- HIGH RISK -->

<div class="dashboard-stat">

<div class="dashboard-stat-top">

<div class="
    dashboard-stat-icon
    dashboard-stat-danger
">

    ⚠

</div>

</div>


<div class="dashboard-stat-label">
    High Risk
</div>


<div class="
    dashboard-stat-value
    <?= $highRisk > 0
        ? "risk-high"
        : "" ?>"
    style="
        background:none;
        padding:0;
    "
>

<?= number_format(
    $highRisk
) ?>

</div>


<div class="dashboard-stat-description">
    High-risk habitations
</div>

</div>


<!-- RED ZONES -->

<div class="dashboard-stat">

<div class="dashboard-stat-top">

<div class="
    dashboard-stat-icon
    dashboard-stat-warning
">

    ●

</div>

</div>


<div class="dashboard-stat-label">
    Red Zones
</div>


<div class="
    dashboard-stat-value
    <?= $redZones > 0
        ? "risk-high"
        : "" ?>"
    style="
        background:none;
        padding:0;
    "
>

<?= number_format(
    $redZones
) ?>

</div>


<div class="dashboard-stat-description">
    Habitations requiring attention
</div>

</div>


</div>


<!-- =====================================================
     MAIN GRID
     ===================================================== -->

<div class="dashboard-grid">


<!-- =====================================================
     LEFT COLUMN
     ===================================================== -->

<div>


<!-- RISK OVERVIEW -->

<div class="dashboard-card">


<div class="dashboard-card-header">

<div>

<div class="dashboard-card-title">
    Risk Overview
</div>

<div class="dashboard-card-subtitle">
    Latest assessment by habitation
</div>

</div>


<a
    href="pages/risk_assessment.php"
    class="dashboard-card-link"
>
    View Assessments →
</a>

</div>


<div class="dashboard-card-body">


<div class="risk-overview-row">


<div class="risk-overview-header">

<div class="risk-overview-label">

<span class="risk-dot risk-dot-high"></span>

High Risk

</div>

<div class="risk-overview-number">

<?= $highRisk ?>

</div>

</div>


<div class="progress">

<div
    class="progress-bar progress-high"
    style="
        width: <?= max(
            $highPercent,
            $highRisk > 0 ? 3 : 0
        ) ?>%;
    "
></div>

</div>

</div>


<div class="risk-overview-row">


<div class="risk-overview-header">

<div class="risk-overview-label">

<span class="risk-dot risk-dot-medium"></span>

Medium Risk

</div>

<div class="risk-overview-number">

<?= $mediumRisk ?>

</div>

</div>


<div class="progress">

<div
    class="progress-bar progress-medium"
    style="
        width: <?= max(
            $mediumPercent,
            $mediumRisk > 0 ? 3 : 0
        ) ?>%;
    "
></div>

</div>

</div>


<div class="risk-overview-row">


<div class="risk-overview-header">

<div class="risk-overview-label">

<span class="risk-dot risk-dot-low"></span>

Low Risk

</div>

<div class="risk-overview-number">

<?= $lowRisk ?>

</div>

</div>


<div class="progress">

<div
    class="progress-bar progress-low"
    style="
        width: <?= max(
            $lowPercent,
            $lowRisk > 0 ? 3 : 0
        ) ?>%;
    "
></div>

</div>

</div>


<?php if ($unassessed > 0): ?>


<div
    style="
        margin-top:16px;
        padding:10px 12px;
        border-radius:8px;
        background:#f8fafc;
        font-size:10px;
        color:#64748b;
    "
>

<strong>
    <?= $unassessed ?>
</strong>

habitation(s) currently have no risk assessment.

</div>


<?php endif; ?>


</div>

</div>


<!-- HIGH RISK HABITATIONS -->

<div class="dashboard-card">


<div class="dashboard-card-header">

<div>

<div class="dashboard-card-title">
    High-Risk Habitations
</div>

<div class="dashboard-card-subtitle">
    Areas requiring priority attention
</div>

</div>


<a
    href="pages/habitations.php"
    class="dashboard-card-link"
>
    View All →
</a>

</div>


<?php if (
    empty($highRiskHabitations)
): ?>


<div class="empty-state">

No high-risk habitations found.

</div>


<?php else: ?>


<div
    class="table-wrapper"
>


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
    Score
</th>

<th>
    Status
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
):
?>


<tr>


<td>

<div class="habitation-name">

<?= htmlspecialchars(
    $habitation["name"]
) ?>

</div>


<div class="habitation-location">

<?= htmlspecialchars(
    $habitation["district"]
) ?>,
<?= htmlspecialchars(
    $habitation["state"]
) ?>

</div>

</td>


<td>

<?= number_format(
    (int)
    $habitation["population"]
) ?>

</td>


<td>

<span class="score">

<?= number_format(
    (float)
    $habitation["risk_score"],
    1
) ?>

</span>

</td>


<td>

<span class="
    badge
    <?= riskClass(
        $habitation["risk_level"]
    ) ?>"
>

<?= htmlspecialchars(
    $habitation["risk_level"]
) ?>

</span>


<?php if (
    (int)
    $habitation["red_zone"] === 1
): ?>

<div
    class="red-zone"
    style="
        font-size:8px;
        margin-top:4px;
    "
>
    ● RED ZONE
</div>

<?php endif; ?>


</td>


<td>

<span class="
    badge
    <?= priorityClass(
        $habitation["relocation_priority"]
    ) ?>"
>

<?= htmlspecialchars(
    $habitation[
        "relocation_priority"
    ]
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


<!-- =====================================================
     RIGHT COLUMN
     ===================================================== -->

<div>


<!-- RELOCATION OVERVIEW -->

<div class="dashboard-card">


<div class="dashboard-card-header">

<div>

<div class="dashboard-card-title">
    Relocation Overview
</div>

<div class="dashboard-card-subtitle">
    Current relocation plan status
</div>

</div>


<a
    href="pages/relocation_plans.php"
    class="dashboard-card-link"
>
    View Plans →
</a>

</div>


<div class="dashboard-card-body">


<div class="relocation-grid">


<div class="relocation-box">

<div class="relocation-label">
    Total Plans
</div>

<div class="relocation-value">
    <?= $totalPlans ?>
</div>

</div>


<div class="relocation-box">

<div class="relocation-label">
    Approved
</div>

<div class="
    relocation-value
    relocation-blue
">

<?= $approvedPlans ?>

</div>

</div>


<div class="relocation-box">

<div class="relocation-label">
    In Progress
</div>

<div class="
    relocation-value
    relocation-orange
">

<?= $inProgressPlans ?>

</div>

</div>


<div class="relocation-box">

<div class="relocation-label">
    Completed
</div>

<div class="
    relocation-value
    relocation-green
">

<?= $completedPlans ?>

</div>

</div>


</div>


<?php if (
    $cancelledPlans > 0
): ?>

<div
    style="
        margin-top:10px;
        font-size:9px;
        color:#64748b;
    "
>

<?= $cancelledPlans ?>

cancelled plan(s)

</div>

<?php endif; ?>


</div>

</div>


<!-- CAPACITY -->

<div class="dashboard-card">


<div class="dashboard-card-header">

<div>

<div class="dashboard-card-title">
    Safe Relocation Capacity
</div>

<div class="dashboard-card-subtitle">
    Available across registered sites
</div>

</div>


<a
    href="pages/relocation_sites.php"
    class="dashboard-card-link"
>
    View Sites →
</a>

</div>


<div class="dashboard-card-body">


<div class="capacity-number">

<div class="capacity-value">

<?= number_format(
    $availableCapacity
) ?>

</div>


<div class="capacity-label">
    people available
</div>

</div>


<?php

$capacityPercent =
    $totalCapacity > 0
        ? round(
            ($occupiedCapacity /
            $totalCapacity) * 100
        )
        : 0;

?>


<div class="capacity-bar">

<div
    class="capacity-fill"
    style="
        width: <?= min(
            max(
                $capacityPercent,
                0
            ),
            100
        ) ?>%;
    "
></div>

</div>


<div class="capacity-meta">

<span>

<?= number_format(
    $occupiedCapacity
) ?>

occupied

</span>


<span>

<?= number_format(
    $totalCapacity
) ?>

total capacity

</span>

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
    Frequently used modules
</div>

</div>

</div>


<div class="dashboard-card-body">


<div class="quick-grid">


<a
    href="pages/habitations.php"
    class="quick-link"
>

<div class="quick-icon">
    ⌖
</div>

<div class="quick-title">
    Habitations
</div>

<div class="quick-description">
    Manage locations
</div>

</a>


<a
    href="pages/risk_assessment.php"
    class="quick-link"
>

<div class="quick-icon">
    ⚠
</div>

<div class="quick-title">
    Assess Risk
</div>

<div class="quick-description">
    Evaluate disaster risk
</div>

</a>


<a
    href="pages/risk_map.php"
    class="quick-link"
>

<div class="quick-icon">
    ◎
</div>

<div class="quick-title">
    Risk Map
</div>

<div class="quick-description">
    View geographic risk
</div>

</a>


<a
    href="pages/reports.php"
    class="quick-link"
>

<div class="quick-icon">
    ▤
</div>

<div class="quick-title">
    Reports
</div>

<div class="quick-description">
    Generate reports
</div>

</a>


</div>


</div>

</div>


<!-- RECENT ASSESSMENTS -->

<div class="dashboard-card">


<div class="dashboard-card-header">

<div>

<div class="dashboard-card-title">
    Recent Assessments
</div>

<div class="dashboard-card-subtitle">
    Latest risk evaluation activity
</div>

</div>


<a
    href="pages/risk_assessment.php"
    class="dashboard-card-link"
>
    View All →
</a>

</div>


<div class="dashboard-card-body">


<?php if (
    empty($recentAssessments)
): ?>


<div class="empty-state">

No assessments available yet.

</div>


<?php else: ?>


<?php foreach (
    $recentAssessments
    as $assessment
):
?>


<div class="assessment-item">


<div>

<div class="assessment-name">

<?= htmlspecialchars(
    $assessment["name"]
) ?>

</div>


<div class="assessment-date">

<?= htmlspecialchars(
    $assessment["district"]
) ?>

•

<?= date(
    "d M Y",
    strtotime(
        $assessment["assessed_at"]
    )
) ?>

</div>

</div>


<div class="assessment-right">


<div
    style="
        font-size:12px;
        font-weight:900;
        margin-bottom:4px;
    "
>

<?= number_format(
    (float)
    $assessment["risk_score"],
    1
) ?>

</div>


<span class="
    badge
    <?= riskClass(
        $assessment["risk_level"]
    ) ?>"
>

<?= htmlspecialchars(
    $assessment["risk_level"]
) ?>

</span>


</div>


</div>


<?php endforeach; ?>


<?php endif; ?>


</div>

</div>


</div>


</div>


</div>


</main>


</div>


</body>

</html>