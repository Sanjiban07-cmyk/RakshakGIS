<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/../config/auth.php";
requireLogin();

require_once __DIR__ . "/../config/database.php";

$user = currentUser();

/*
|--------------------------------------------------------------------------
| ADMIN ONLY
|--------------------------------------------------------------------------
*/

if (($user["role"] ?? "") !== "ADMIN") {
    http_response_code(403);
    die("Access denied.");
}


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$message = "";
$error = "";


/*
|--------------------------------------------------------------------------
| CURRENT ADMIN
|--------------------------------------------------------------------------
*/

$currentAdmin = null;

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        username,
        email,
        role,
        status,
        created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $user["id"]
);

$stmt->execute();

$result = $stmt->get_result();

$currentAdmin = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| USER STATUS UPDATE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["update_user_status"])
) {

    $userId =
        (int) ($_POST["user_id"] ?? 0);

    $newStatus =
        strtoupper(
            trim(
                $_POST["status"] ?? ""
            )
        );


    $allowedStatuses = [
        "ACTIVE",
        "INACTIVE"
    ];


    if (
        $userId <= 0
        ||
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $error = "Invalid user status.";

    } elseif (
        $userId === (int) $user["id"]
        &&
        $newStatus === "INACTIVE"
    ) {

        $error =
            "You cannot deactivate your own administrator account.";

    } else {

        $stmt = $conn->prepare("
            UPDATE users
            SET status = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "si",
            $newStatus,
            $userId
        );


        if ($stmt->execute()) {

            $message =
                "User status updated successfully.";

        } else {

            $error =
                "Unable to update user status.";
        }


        $stmt->close();
    }
}


/*
|--------------------------------------------------------------------------
| ALL USERS
|--------------------------------------------------------------------------
*/

$users = [];

$result = $conn->query("
    SELECT
        id,
        name,
        username,
        email,
        role,
        status,
        created_at
    FROM users
    ORDER BY
        CASE
            WHEN role = 'ADMIN' THEN 1
            ELSE 2
        END,
        name ASC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $users[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| SYSTEM STATISTICS
|--------------------------------------------------------------------------
*/

$stats = [
    "users" => 0,
    "habitations" => 0,
    "assessments" => 0,
    "sites" => 0,
    "plans" => 0,
    "population" => 0
];


$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats["users"] =
        (int) $row["total"];
}


$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(population), 0) AS population
    FROM habitations
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats["habitations"] =
        (int) $row["total"];

    $stats["population"] =
        (int) $row["population"];
}


$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM risk_assessments
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats["assessments"] =
        (int) $row["total"];
}


$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM relocation_sites
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats["sites"] =
        (int) $row["total"];
}


$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM relocation_plans
");

if ($result) {

    $row = $result->fetch_assoc();

    $stats["plans"] =
        (int) $row["total"];
}


/*
|--------------------------------------------------------------------------
| ACTIVE / INACTIVE USERS
|--------------------------------------------------------------------------
*/

$activeUsers = 0;
$inactiveUsers = 0;

foreach ($users as $account) {

    if ($account["status"] === "ACTIVE") {

        $activeUsers++;

    } else {

        $inactiveUsers++;
    }
}


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION CHECK
|--------------------------------------------------------------------------
*/

$databaseConnected = false;

$connectionCheck =
    $conn->query("SELECT 1");

if ($connectionCheck) {

    $databaseConnected = true;
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
    Administration | RakshakGIS
</title>


<link
    rel="stylesheet"
    href="../assets/css/style.css"
>


<style>

/* =====================================================
   ADMIN PAGE
   ===================================================== */

.admin-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 20px;

    margin-bottom: 24px;
}


.admin-heading h1 {

    font-size: 24px;

    font-weight: 800;
}


.admin-heading p {

    margin-top: 5px;

    font-size: 12px;

    color: var(--muted);
}


/* =====================================================
   ADMIN PROFILE
   ===================================================== */

.admin-profile {

    background:
        linear-gradient(
            135deg,
            #0f172a,
            #172554
        );

    color: white;

    border-radius: 14px;

    padding: 24px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 22px;

    overflow: hidden;

    position: relative;
}


.admin-profile::after {

    content: "";

    position: absolute;

    width: 220px;

    height: 220px;

    border-radius: 50%;

    border: 1px solid
        rgba(255,255,255,.08);

    right: -70px;

    top: -80px;
}


.admin-profile-left {

    display: flex;

    align-items: center;

    gap: 15px;

    position: relative;

    z-index: 1;
}


.admin-avatar {

    width: 58px;

    height: 58px;

    border-radius: 15px;

    background: #2563eb;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 24px;

    font-weight: 900;

    box-shadow:
        0 8px 20px
        rgba(37,99,235,.35);
}


.admin-name {

    font-size: 18px;

    font-weight: 800;
}


.admin-username {

    font-size: 11px;

    color: #cbd5e1;

    margin-top: 3px;
}


.admin-role {

    display: inline-flex;

    margin-top: 8px;

    padding: 4px 8px;

    border-radius: 999px;

    background:
        rgba(37,99,235,.25);

    color: #bfdbfe;

    font-size: 9px;

    font-weight: 800;

    letter-spacing: .5px;
}


.admin-profile-right {

    position: relative;

    z-index: 1;

    text-align: right;

    font-size: 11px;

    color: #cbd5e1;

    line-height: 1.7;
}


/* =====================================================
   SYSTEM STATS
   ===================================================== */

.admin-stats {

    display: grid;

    grid-template-columns:
        repeat(5, 1fr);

    gap: 14px;

    margin-bottom: 22px;
}


.admin-stat {

    background: white;

    border: 1px solid var(--border);

    border-radius: 12px;

    padding: 17px;

    transition: .2s ease;
}


.admin-stat:hover {

    transform: translateY(-2px);

    box-shadow:
        0 8px 25px
        rgba(15,23,42,.06);
}


.admin-stat-icon {

    width: 34px;

    height: 34px;

    border-radius: 9px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eff6ff;

    color: var(--primary);

    font-weight: 900;

    margin-bottom: 10px;
}


.admin-stat-label {

    font-size: 10px;

    color: var(--muted);
}


.admin-stat-value {

    font-size: 23px;

    font-weight: 900;

    margin-top: 4px;
}


/* =====================================================
   GRID
   ===================================================== */

.admin-grid {

    display: grid;

    grid-template-columns:
        1.6fr 1fr;

    gap: 20px;

    align-items: start;
}


.admin-card {

    background: white;

    border: 1px solid var(--border);

    border-radius: 12px;

    overflow: hidden;
}


.admin-card-header {

    padding: 17px 19px;

    border-bottom: 1px solid var(--border);

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;
}


.admin-card-title {

    font-size: 14px;

    font-weight: 800;
}


.admin-card-subtitle {

    font-size: 10px;

    color: var(--muted);

    margin-top: 3px;
}


.admin-card-body {

    padding: 19px;
}


/* =====================================================
   USERS
   ===================================================== */

.user-table-wrapper {

    overflow-x: auto;
}


.user-table {

    width: 100%;

    border-collapse: collapse;
}


.user-table th {

    background: #f8fafc;

    color: #64748b;

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: .5px;

    text-align: left;
}


.user-table th,
.user-table td {

    padding: 12px;

    border-bottom: 1px solid var(--border);

    white-space: nowrap;
}


.user-table td {

    font-size: 11px;
}


.user-table tr:last-child td {

    border-bottom: none;
}


.user-name {

    font-weight: 800;
}


.user-username {

    color: var(--muted);

    font-size: 9px;

    margin-top: 2px;
}


.user-email {

    color: var(--muted);
}


.role-badge,
.account-badge {

    display: inline-flex;

    align-items: center;

    padding: 4px 8px;

    border-radius: 999px;

    font-size: 8px;

    font-weight: 800;
}


.role-admin {

    background: #dbeafe;

    color: #1d4ed8;
}


.role-user {

    background: #f1f5f9;

    color: #475569;
}


.account-active {

    background: #dcfce7;

    color: #15803d;
}


.account-inactive {

    background: #fee2e2;

    color: #b91c1c;
}


.status-form {

    display: flex;

    gap: 5px;

    align-items: center;
}


.status-form select {

    border: 1px solid var(--border);

    border-radius: 6px;

    padding: 5px 6px;

    font-size: 9px;

    background: white;

    outline: none;
}


.status-form button {

    border: none;

    border-radius: 6px;

    padding: 6px 8px;

    font-size: 9px;

    font-weight: 700;

    cursor: pointer;

    background: #eff6ff;

    color: #1d4ed8;
}


/* =====================================================
   SYSTEM STATUS
   ===================================================== */

.system-status {

    display: flex;

    flex-direction: column;

    gap: 10px;
}


.system-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding: 11px 12px;

    border-radius: 8px;

    background: #f8fafc;
}


.system-label {

    font-size: 11px;

    color: var(--muted);
}


.system-value {

    font-size: 11px;

    font-weight: 800;
}


.system-online {

    color: #15803d;

    display: inline-flex;

    align-items: center;

    gap: 6px;
}


.system-dot {

    width: 7px;

    height: 7px;

    border-radius: 50%;

    background: #16a34a;
}


/* =====================================================
   QUICK ACTIONS
   ===================================================== */

.quick-actions {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 9px;

    margin-top: 18px;
}


.quick-action {

    text-decoration: none;

    border: 1px solid var(--border);

    border-radius: 9px;

    padding: 12px;

    color: var(--text);

    background: white;

    transition: .2s ease;
}


.quick-action:hover {

    border-color: #bfdbfe;

    background: #eff6ff;

    transform: translateY(-1px);
}


.quick-action-icon {

    font-size: 16px;

    margin-bottom: 6px;
}


.quick-action-title {

    font-size: 10px;

    font-weight: 800;
}


.quick-action-description {

    font-size: 8px;

    color: var(--muted);

    margin-top: 2px;
}


/* =====================================================
   USER SUMMARY
   ===================================================== */

.user-summary {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 10px;

    margin-top: 18px;
}


.user-summary-box {

    border-radius: 9px;

    padding: 12px;

    background: #f8fafc;
}


.user-summary-label {

    font-size: 9px;

    color: var(--muted);
}


.user-summary-value {

    font-size: 20px;

    font-weight: 900;

    margin-top: 3px;
}


/* =====================================================
   ALERT
   ===================================================== */

.admin-alert {

    padding: 12px 14px;

    border-radius: 8px;

    font-size: 11px;

    margin-bottom: 18px;
}


.admin-alert-success {

    background: #dcfce7;

    color: #166534;
}


.admin-alert-danger {

    background: #fee2e2;

    color: #991b1b;
}


/* =====================================================
   RESPONSIVE
   ===================================================== */

@media (max-width: 1100px) {

    .admin-stats {

        grid-template-columns:
            repeat(3, 1fr);
    }

    .admin-grid {

        grid-template-columns: 1fr;
    }

}


@media (max-width: 700px) {

    .admin-header {

        flex-direction: column;
    }

    .admin-profile {

        flex-direction: column;

        align-items: flex-start;
    }

    .admin-profile-right {

        text-align: left;
    }

    .admin-stats {

        grid-template-columns:
            1fr 1fr;
    }

}


@media (max-width: 450px) {

    .admin-stats {

        grid-template-columns: 1fr;
    }

}


/* =====================================================
   PRINT
   ===================================================== */

@media print {

    .sidebar,
    .topbar,
    .admin-header,
    .status-form,
    .quick-actions {

        display: none !important;
    }

    .main {

        margin-left: 0 !important;

        width: 100% !important;
    }

}
/* =====================================================
   RAKSHAK GIS BRANDING
   ===================================================== */

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    padding: 20px 18px;
}

.logo-icon {
    width: 42px;
    height: 46px;
    flex-shrink: 0;

    background: linear-gradient(
        145deg,
        #2563eb,
        #1d4ed8
    );

    color: #ffffff;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 12px;

    position: relative;

    box-shadow:
        0 6px 16px rgba(37, 99, 235, .28);

    clip-path: polygon(
        50% 0%,
        92% 15%,
        92% 55%,
        82% 76%,
        50% 100%,
        18% 76%,
        8% 55%,
        8% 15%
    );
}

.logo-icon span {
    font-size: 20px;
    font-weight: 900;
    font-family: Arial, sans-serif;
    color: #ffffff;
}

.logo-brand {
    min-width: 0;
}

.logo-text {
    font-size: 17px;
    line-height: 1;
    font-weight: 900;
    letter-spacing: .4px;
    color: #ffffff;
    white-space: nowrap;
}

.logo-text span {
    color: #60a5fa;
}

.logo-subtitle {
    margin-top: 6px;
    font-size: 7.5px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: 1.1px;
    color: #94a3b8;
    white-space: nowrap;
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


<a
    href="admin.php"
    class="nav-link active"
>
    <span class="nav-icon">⚙</span>
    Administration
</a>


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
    $user["role"] ?? "ADMIN"
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
    Administration
</div>

<div class="page-subtitle">
    System and user management
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


<!-- HEADER -->

<div class="admin-header">

<div class="admin-heading">

<h1>
    Administration
</h1>

<p>
    Manage system accounts and monitor RakshakGIS services.
</p>

</div>

</div>


<!-- ALERT -->

<?php if ($message): ?>

<div class="admin-alert admin-alert-success">

<?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="admin-alert admin-alert-danger">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>


<!-- =====================================================
     ADMIN PROFILE
     ===================================================== -->

<div class="admin-profile">


<div class="admin-profile-left">


<div class="admin-avatar">

<?= strtoupper(
    substr(
        $currentAdmin["name"] ?? "A",
        0,
        1
    )
) ?>

</div>


<div>

<div class="admin-name">

<?= htmlspecialchars(
    $currentAdmin["name"]
    ??
    "System Administrator"
) ?>

</div>


<div class="admin-username">

@<?= htmlspecialchars(
    $currentAdmin["username"]
    ??
    "admin"
) ?>

</div>


<div class="admin-role">
    SYSTEM ADMINISTRATOR
</div>

</div>


</div>


<div class="admin-profile-right">

Account Status:

<strong>
    <?= htmlspecialchars(
        $currentAdmin["status"]
        ??
        "ACTIVE"
    ) ?>
</strong>

<br>

Email:
<?= htmlspecialchars(
    $currentAdmin["email"]
    ??
    "Not provided"
) ?>

<br>

Member since:
<?= !empty(
    $currentAdmin["created_at"]
)
    ? date(
        "d M Y",
        strtotime(
            $currentAdmin["created_at"]
        )
    )
    : "—"
?>

</div>


</div>


<!-- =====================================================
     STATISTICS
     ===================================================== -->

<div class="admin-stats">


<div class="admin-stat">

<div class="admin-stat-icon">
    👤
</div>

<div class="admin-stat-label">
    Users
</div>

<div class="admin-stat-value">
    <?= $stats["users"] ?>
</div>

</div>


<div class="admin-stat">

<div class="admin-stat-icon">
    ⌖
</div>

<div class="admin-stat-label">
    Habitations
</div>

<div class="admin-stat-value">
    <?= $stats["habitations"] ?>
</div>

</div>


<div class="admin-stat">

<div class="admin-stat-icon">
    ⚠
</div>

<div class="admin-stat-label">
    Assessments
</div>

<div class="admin-stat-value">
    <?= $stats["assessments"] ?>
</div>

</div>


<div class="admin-stat">

<div class="admin-stat-icon">
    ⌂
</div>

<div class="admin-stat-label">
    Relocation Sites
</div>

<div class="admin-stat-value">
    <?= $stats["sites"] ?>
</div>

</div>


<div class="admin-stat">

<div class="admin-stat-icon">
    →
</div>

<div class="admin-stat-label">
    Relocation Plans
</div>

<div class="admin-stat-value">
    <?= $stats["plans"] ?>
</div>

</div>


</div>


<!-- =====================================================
     MAIN GRID
     ===================================================== -->

<div class="admin-grid">


<!-- =====================================================
     USER MANAGEMENT
     ===================================================== -->

<div class="admin-card">


<div class="admin-card-header">

<div>

<div class="admin-card-title">
    User Management
</div>

<div class="admin-card-subtitle">
    Manage registered RakshakGIS accounts
</div>

</div>


<div>

<span class="account-badge account-active">

<?= $activeUsers ?>

Active

</span>

</div>

</div>


<div class="admin-card-body">


<div class="user-table-wrapper">


<table class="user-table">


<thead>

<tr>

<th>
    User
</th>

<th>
    Email
</th>

<th>
    Role
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


<?php if (empty($users)): ?>


<tr>

<td
    colspan="5"
    style="
        text-align:center;
        padding:30px;
        color:#64748b;
    "
>

No users found.

</td>

</tr>


<?php else: ?>


<?php foreach ($users as $account): ?>


<tr>


<td>

<div class="user-name">

<?= htmlspecialchars(
    $account["name"]
) ?>

</div>


<div class="user-username">

@<?= htmlspecialchars(
    $account["username"]
) ?>

</div>

</td>


<td>

<div class="user-email">

<?= htmlspecialchars(
    $account["email"]
    ??
    "—"
) ?>

</div>

</td>


<td>

<span
    class="role-badge
    <?= $account["role"] === "ADMIN"
        ? "role-admin"
        : "role-user" ?>"
>

<?= htmlspecialchars(
    $account["role"]
) ?>

</span>

</td>


<td>

<span
    class="account-badge
    <?= $account["status"] === "ACTIVE"
        ? "account-active"
        : "account-inactive" ?>"
>

<?= htmlspecialchars(
    $account["status"]
) ?>

</span>

</td>


<td>


<?php if (
    (int)
    $account["id"]
    ===
    (int)
    $user["id"]
): ?>


<span
    style="
        font-size:9px;
        color:#64748b;
    "
>
    Current account
</span>


<?php else: ?>


<form
    method="POST"
    class="status-form"
>


<input
    type="hidden"
    name="user_id"
    value="<?= (int)
        $account["id"] ?>"
>


<select
    name="status"
>

<option
    value="ACTIVE"
    <?= $account["status"] === "ACTIVE"
        ? "selected"
        : "" ?>
>
    Active
</option>


<option
    value="INACTIVE"
    <?= $account["status"] === "INACTIVE"
        ? "selected"
        : "" ?>
>
    Inactive
</option>

</select>


<button
    type="submit"
    name="update_user_status"
>
    Update
</button>


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


<!-- =====================================================
     RIGHT COLUMN
     ===================================================== -->

<div>


<!-- SYSTEM STATUS -->

<div class="admin-card">


<div class="admin-card-header">

<div>

<div class="admin-card-title">
    System Status
</div>

<div class="admin-card-subtitle">
    RakshakGIS service health
</div>

</div>

</div>


<div class="admin-card-body">


<div class="system-status">


<div class="system-row">

<span class="system-label">
    Database
</span>

<span
    class="system-value system-online"
>

<span class="system-dot"></span>

<?= $databaseConnected
    ? "Connected"
    : "Offline"
?>

</span>

</div>


<div class="system-row">

<span class="system-label">
    Database
</span>

<span class="system-value">
    rakshakgis
</span>

</div>


<div class="system-row">

<span class="system-label">
    Users
</span>

<span class="system-value">
    <?= $activeUsers ?> active
</span>

</div>


<div class="system-row">

<span class="system-label">
    Population
</span>

<span class="system-value">
    <?= number_format(
        $stats["population"]
    ) ?>
</span>

</div>


<div class="system-row">

<span class="system-label">
    System
</span>

<span class="system-value system-online">

<span class="system-dot"></span>

Operational

</span>

</div>


</div>


<!-- USER SUMMARY -->

<div class="user-summary">


<div class="user-summary-box">

<div class="user-summary-label">
    Active Accounts
</div>

<div
    class="user-summary-value"
    style="color:#16a34a;"
>

<?= $activeUsers ?>

</div>

</div>


<div class="user-summary-box">

<div class="user-summary-label">
    Inactive Accounts
</div>

<div
    class="user-summary-value"
    style="color:#dc2626;"
>

<?= $inactiveUsers ?>

</div>

</div>


</div>


</div>

</div>


<!-- QUICK ACTIONS -->

<div
    class="admin-card"
    style="margin-top:20px;"
>


<div class="admin-card-header">

<div>

<div class="admin-card-title">
    Quick Actions
</div>

<div class="admin-card-subtitle">
    Navigate to important system modules
</div>

</div>

</div>


<div class="admin-card-body">


<div class="quick-actions">


<a
    href="reports.php"
    class="quick-action"
>

<div class="quick-action-icon">
    ▤
</div>

<div class="quick-action-title">
    Reports
</div>

<div class="quick-action-description">
    View system reports
</div>

</a>


<a
    href="risk_assessment.php"
    class="quick-action"
>

<div class="quick-action-icon">
    ⚠
</div>

<div class="quick-action-title">
    Risk Assessment
</div>

<div class="quick-action-description">
    Assess habitation risk
</div>

</a>


<a
    href="relocation_plans.php"
    class="quick-action"
>

<div class="quick-action-icon">
    →
</div>

<div class="quick-action-title">
    Relocation Plans
</div>

<div class="quick-action-description">
    Manage relocation
</div>

</a>


<a
    href="habitations.php"
    class="quick-action"
>

<div class="quick-action-icon">
    ⌖
</div>

<div class="quick-action-title">
    Habitations
</div>

<div class="quick-action-description">
    Manage locations
</div>

</a>


</div>


</div>

</div>


</div>


</div>


</div>

</main>

</div>


</body>

</html>