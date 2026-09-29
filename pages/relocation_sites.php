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
| ADD RELOCATION SITE
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_site"])) {

    if (($user["role"] ?? "") !== "ADMIN") {

        $error = "Only administrators can add relocation sites.";

    } else {

        $siteName = trim($_POST["site_name"] ?? "");
        $district = trim($_POST["district"] ?? "");

        $latitude = trim($_POST["latitude"] ?? "");
        $longitude = trim($_POST["longitude"] ?? "");

        $totalCapacity = (int) ($_POST["total_capacity"] ?? 0);
        $occupiedCapacity = (int) ($_POST["occupied_capacity"] ?? 0);

        $safetyLevel = strtoupper(
            trim($_POST["safety_level"] ?? "LOW")
        );

        $distance = trim(
            $_POST["distance_from_habitation"] ?? ""
        );

        $facilities = trim(
            $_POST["facilities"] ?? ""
        );


        if ($siteName === "" || $district === "") {

            $error = "Site name and district are required.";

        } elseif ($totalCapacity < 0 || $occupiedCapacity < 0) {

            $error = "Capacity cannot be negative.";

        } elseif ($occupiedCapacity > $totalCapacity) {

            $error =
                "Occupied capacity cannot be greater than total capacity.";

        } elseif (
            !in_array(
                $safetyLevel,
                ["LOW", "MEDIUM", "HIGH"],
                true
            )
        ) {

            $error = "Invalid safety level.";

        } else {

            $latitudeValue =
                $latitude === ""
                    ? null
                    : (float) $latitude;

            $longitudeValue =
                $longitude === ""
                    ? null
                    : (float) $longitude;

            $distanceValue =
                $distance === ""
                    ? null
                    : (float) $distance;


            $stmt = $conn->prepare("
                INSERT INTO relocation_sites
                (
                    site_name,
                    district,
                    latitude,
                    longitude,
                    total_capacity,
                    occupied_capacity,
                    safety_level,
                    distance_from_habitation,
                    facilities
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");


            $stmt->bind_param(
                "ssddiidss",
                $siteName,
                $district,
                $latitudeValue,
                $longitudeValue,
                $totalCapacity,
                $occupiedCapacity,
                $safetyLevel,
                $distanceValue,
                $facilities
            );


            if ($stmt->execute()) {

                $message =
                    "Relocation site added successfully.";

            } else {

                $error =
                    "Unable to add relocation site: " .
                    $stmt->error;
            }


            $stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE RELOCATION SITE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["update_site"])
) {

    if (($user["role"] ?? "") !== "ADMIN") {

        $error = "Only administrators can edit relocation sites.";

    } else {

        $id = (int) ($_POST["id"] ?? 0);

        $siteName = trim($_POST["site_name"] ?? "");
        $district = trim($_POST["district"] ?? "");

        $latitude = trim($_POST["latitude"] ?? "");
        $longitude = trim($_POST["longitude"] ?? "");

        $totalCapacity = (int) ($_POST["total_capacity"] ?? 0);
        $occupiedCapacity = (int) ($_POST["occupied_capacity"] ?? 0);

        $safetyLevel = strtoupper(
            trim($_POST["safety_level"] ?? "LOW")
        );

        $distance = trim(
            $_POST["distance_from_habitation"] ?? ""
        );

        $facilities = trim(
            $_POST["facilities"] ?? ""
        );


        if ($id <= 0) {

            $error = "Invalid relocation site.";

        } elseif ($siteName === "" || $district === "") {

            $error = "Site name and district are required.";

        } elseif ($totalCapacity < 0 || $occupiedCapacity < 0) {

            $error = "Capacity cannot be negative.";

        } elseif ($occupiedCapacity > $totalCapacity) {

            $error =
                "Occupied capacity cannot be greater than total capacity.";

        } elseif (
            !in_array(
                $safetyLevel,
                ["LOW", "MEDIUM", "HIGH"],
                true
            )
        ) {

            $error = "Invalid safety level.";

        } else {

            $latitudeValue =
                $latitude === ""
                    ? null
                    : (float) $latitude;

            $longitudeValue =
                $longitude === ""
                    ? null
                    : (float) $longitude;

            $distanceValue =
                $distance === ""
                    ? null
                    : (float) $distance;


            $stmt = $conn->prepare("
                UPDATE relocation_sites

                SET
                    site_name = ?,
                    district = ?,
                    latitude = ?,
                    longitude = ?,
                    total_capacity = ?,
                    occupied_capacity = ?,
                    safety_level = ?,
                    distance_from_habitation = ?,
                    facilities = ?

                WHERE id = ?
            ");


            $stmt->bind_param(
                "ssddiids si",
                $siteName,
                $district,
                $latitudeValue,
                $longitudeValue,
                $totalCapacity,
                $occupiedCapacity,
                $safetyLevel,
                $distanceValue,
                $facilities,
                $id
            );


            /*
             * Correct the bind type string without spaces.
             */

            $stmt->close();

            $stmt = $conn->prepare("
                UPDATE relocation_sites

                SET
                    site_name = ?,
                    district = ?,
                    latitude = ?,
                    longitude = ?,
                    total_capacity = ?,
                    occupied_capacity = ?,
                    safety_level = ?,
                    distance_from_habitation = ?,
                    facilities = ?

                WHERE id = ?
            ");


            $stmt->bind_param(
                "ssddiids si",
                $siteName,
                $district,
                $latitudeValue,
                $longitudeValue,
                $totalCapacity,
                $occupiedCapacity,
                $safetyLevel,
                $distanceValue,
                $facilities,
                $id
            );

            /*
             * Rebuild with the exact mysqli type definition.
             *
             * s = string
             * d = decimal
             * i = integer
             */

            $stmt->close();

            $stmt = $conn->prepare("
                UPDATE relocation_sites

                SET
                    site_name = ?,
                    district = ?,
                    latitude = ?,
                    longitude = ?,
                    total_capacity = ?,
                    occupied_capacity = ?,
                    safety_level = ?,
                    distance_from_habitation = ?,
                    facilities = ?

                WHERE id = ?
            ");

            $stmt->bind_param(
                "ssddiidsdi",
                $siteName,
                $district,
                $latitudeValue,
                $longitudeValue,
                $totalCapacity,
                $occupiedCapacity,
                $safetyLevel,
                $distanceValue,
                $facilities,
                $id
            );


            if ($stmt->execute()) {

                $message =
                    "Relocation site updated successfully.";

            } else {

                $error =
                    "Unable to update relocation site: " .
                    $stmt->error;
            }


            $stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| DELETE RELOCATION SITE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["delete_site"])
) {

    if (($user["role"] ?? "") !== "ADMIN") {

        $error = "Only administrators can delete relocation sites.";

    } else {

        $deleteId = (int) $_POST["delete_site"];


        if ($deleteId > 0) {

            $stmt = $conn->prepare("
                DELETE FROM relocation_sites
                WHERE id = ?
            ");

            $stmt->bind_param(
                "i",
                $deleteId
            );


            if ($stmt->execute()) {

                $message =
                    "Relocation site deleted successfully.";

            } else {

                $error =
                    "Unable to delete relocation site. " .
                    "It may already be used by a relocation plan.";
            }


            $stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim(
    $_GET["search"] ?? ""
);


if ($search !== "") {

    $searchParam =
        "%" . $search . "%";


    $stmt = $conn->prepare("
        SELECT *
        FROM relocation_sites

        WHERE
            site_name LIKE ?
            OR district LIKE ?
            OR facilities LIKE ?

        ORDER BY created_at DESC
    ");


    $stmt->bind_param(
        "sss",
        $searchParam,
        $searchParam,
        $searchParam
    );


    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $result = $conn->query("
        SELECT *
        FROM relocation_sites
        ORDER BY created_at DESC
    ");
}


$sites = [];


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $sites[] = $row;
    }
}


if (isset($stmt)) {
    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$totalSites = count($sites);

$totalCapacity = 0;
$occupiedCapacity = 0;
$availableCapacity = 0;

$highSafetySites = 0;


foreach ($sites as $site) {

    $total = (int) $site["total_capacity"];

    $occupied = (int) $site["occupied_capacity"];

    $available =
        max(
            0,
            $total - $occupied
        );


    $totalCapacity += $total;

    $occupiedCapacity += $occupied;

    $availableCapacity += $available;


    if ($site["safety_level"] === "HIGH") {
        $highSafetySites++;
    }
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function safetyClass(string $level): string
{
    return match ($level) {

        "HIGH" => "safety-high",

        "MEDIUM" => "safety-medium",

        default => "safety-low"
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

    <title>Relocation Sites | RakshakGIS</title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        /* =========================================
           RELOCATION SITES
           ========================================= */

        .site-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 22px;

        }


        .site-heading h1 {

            font-size: 24px;

            font-weight: 800;

        }


        .site-heading p {

            margin-top: 5px;

            font-size: 12px;

            color: var(--muted);

        }


        .site-summary {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;

            margin-bottom: 20px;

        }


        .site-stat {

            background: white;

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 18px;

        }


        .site-stat-label {

            font-size: 11px;

            color: var(--muted);

        }


        .site-stat-value {

            font-size: 25px;

            font-weight: 800;

            margin-top: 5px;

        }


        .site-stat.available
        .site-stat-value {

            color: #16a34a;

        }


        .site-stat.safety
        .site-stat-value {

            color: #2563eb;

        }


        .site-toolbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 18px;

        }


        .search-form {

            display: flex;

            gap: 8px;

            width: 100%;

            max-width: 480px;

        }


        .search-input {

            flex: 1;

            border: 1px solid var(--border);

            border-radius: 8px;

            padding: 10px 13px;

            font-size: 13px;

            font-family: inherit;

            outline: none;

        }


        .search-input:focus {

            border-color: var(--primary);

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,0.1);

        }


        .site-table-card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 12px;

            overflow: hidden;

        }


        .site-name {

            font-weight: 700;

        }


        .site-location {

            font-size: 11px;

            color: var(--muted);

            margin-top: 3px;

        }


        .capacity-main {

            font-weight: 800;

        }


        .capacity-sub {

            font-size: 10px;

            color: var(--muted);

            margin-top: 3px;

        }


        .capacity-bar {

            width: 100%;

            min-width: 80px;

            height: 5px;

            background: #e2e8f0;

            border-radius: 99px;

            margin-top: 7px;

            overflow: hidden;

        }


        .capacity-fill {

            height: 100%;

            border-radius: 99px;

            background: var(--primary);

        }


        .safety-badge {

            display: inline-flex;

            align-items: center;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 800;

        }


        .safety-high {

            background: #dcfce7;

            color: #15803d;

        }


        .safety-medium {

            background: #fef3c7;

            color: #b45309;

        }


        .safety-low {

            background: #fee2e2;

            color: #b91c1c;

        }


        .coordinate {

            font-family: monospace;

            font-size: 10px;

            color: var(--muted);

            white-space: nowrap;

        }


        .facility-list {

            max-width: 220px;

            font-size: 11px;

            line-height: 1.5;

            color: var(--muted);

        }


        .action-buttons {

            display: flex;

            gap: 6px;

        }


        .btn-small {

            padding: 7px 10px;

            font-size: 11px;

        }


        .btn-edit {

            background: #eff6ff;

            color: #1d4ed8;

        }


        .btn-edit:hover {

            background: #dbeafe;

        }


        .btn-delete {

            background: #fef2f2;

            color: #b91c1c;

        }


        .btn-delete:hover {

            background: #fee2e2;

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
                rgba(15,23,42,0.55);

            backdrop-filter: blur(3px);

            z-index: 5000;

        }


        .modal.active {

            display: flex;

        }


        .modal-card {

            width: 100%;

            max-width: 720px;

            max-height: 90vh;

            overflow-y: auto;

            background: white;

            border-radius: 15px;

            box-shadow:
                0 25px 60px
                rgba(15,23,42,0.25);

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

            color: var(--muted);

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


        .facility-help {

            font-size: 10px;

            color: var(--muted);

            margin-top: 5px;

        }


        @media (max-width: 1000px) {

            .site-summary {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 700px) {

            .site-header,
            .site-toolbar {

                flex-direction: column;

                align-items: stretch;

            }

            .site-summary {

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
                class="nav-link active"
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


        <header class="topbar">

            <div>

                <div class="page-title">
                    Relocation Sites
                </div>

                <div class="page-subtitle">
                    Manage safe locations for population relocation
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

            <div class="site-header">

                <div class="site-heading">

                    <h1>
                        Safe Relocation Sites
                    </h1>

                    <p>
                        Manage locations, capacity, safety and
                        facilities available for relocation.
                    </p>

                </div>


                <?php if (($user["role"] ?? "") === "ADMIN"): ?>

                    <button
                        class="btn btn-primary"
                        onclick="openAddModal()"
                    >

                        + Add Relocation Site

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

            <div class="site-summary">


                <div class="site-stat">

                    <div class="site-stat-label">
                        Total Sites
                    </div>

                    <div class="site-stat-value">
                        <?= $totalSites ?>
                    </div>

                </div>


                <div class="site-stat">

                    <div class="site-stat-label">
                        Total Capacity
                    </div>

                    <div class="site-stat-value">
                        <?= number_format($totalCapacity) ?>
                    </div>

                </div>


                <div class="site-stat available">

                    <div class="site-stat-label">
                        Available Capacity
                    </div>

                    <div class="site-stat-value">
                        <?= number_format($availableCapacity) ?>
                    </div>

                </div>


                <div class="site-stat safety">

                    <div class="site-stat-label">
                        High Safety Sites
                    </div>

                    <div class="site-stat-value">
                        <?= $highSafetySites ?>
                    </div>

                </div>


            </div>



            <!-- TOOLBAR -->

            <div class="site-toolbar">


                <form
                    method="GET"
                    class="search-form"
                >

                    <input
                        type="text"
                        name="search"
                        class="search-input"
                        placeholder="Search site, district or facility..."
                        value="<?= htmlspecialchars($search) ?>"
                    >


                    <button
                        type="submit"
                        class="btn btn-secondary"
                    >
                        Search
                    </button>


                    <?php if ($search !== ""): ?>

                        <a
                            href="relocation_sites.php"
                            class="btn btn-secondary"
                        >
                            Clear
                        </a>

                    <?php endif; ?>

                </form>


            </div>



            <!-- TABLE -->

            <div class="site-table-card">

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Site
                                </th>

                                <th>
                                    Coordinates
                                </th>

                                <th>
                                    Capacity
                                </th>

                                <th>
                                    Safety
                                </th>

                                <th>
                                    Distance
                                </th>

                                <th>
                                    Facilities
                                </th>

                                <?php if (($user["role"] ?? "") === "ADMIN"): ?>

                                    <th>
                                        Actions
                                    </th>

                                <?php endif; ?>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($sites)): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    style="
                                        text-align:center;
                                        padding:45px;
                                        color:#64748b;
                                    "
                                >

                                    No relocation sites found.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($sites as $site): ?>


                                <?php

                                $total =
                                    (int) $site["total_capacity"];

                                $occupied =
                                    (int) $site["occupied_capacity"];

                                $available =
                                    max(
                                        0,
                                        $total - $occupied
                                    );

                                $occupancyPercent =
                                    $total > 0
                                        ? min(
                                            100,
                                            ($occupied / $total) * 100
                                        )
                                        : 0;

                                ?>


                                <tr>


                                    <!-- SITE -->

                                    <td>

                                        <div class="site-name">

                                            <?= htmlspecialchars(
                                                $site["site_name"]
                                            ) ?>

                                        </div>


                                        <div class="site-location">

                                            <?= htmlspecialchars(
                                                $site["district"]
                                            ) ?>

                                        </div>

                                    </td>



                                    <!-- COORDINATES -->

                                    <td>

                                        <?php if (
                                            $site["latitude"] !== null &&
                                            $site["longitude"] !== null
                                        ): ?>

                                            <div class="coordinate">

                                                <?= htmlspecialchars(
                                                    $site["latitude"]
                                                ) ?>

                                                ,

                                                <?= htmlspecialchars(
                                                    $site["longitude"]
                                                ) ?>

                                            </div>

                                        <?php else: ?>

                                            <span
                                                style="
                                                    color:#94a3b8;
                                                    font-size:11px;
                                                "
                                            >
                                                Not available
                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- CAPACITY -->

                                    <td>

                                        <div class="capacity-main">

                                            <?= number_format(
                                                $available
                                            ) ?>

                                            available

                                        </div>


                                        <div class="capacity-sub">

                                            <?= number_format(
                                                $occupied
                                            ) ?>

                                            occupied /
                                            <?= number_format(
                                                $total
                                            ) ?>

                                            total

                                        </div>


                                        <div class="capacity-bar">

                                            <div
                                                class="capacity-fill"
                                                style="
                                                    width:
                                                    <?= $occupancyPercent ?>%;
                                                "
                                            ></div>

                                        </div>

                                    </td>



                                    <!-- SAFETY -->

                                    <td>

                                        <span
                                            class="safety-badge <?= safetyClass(
                                                $site["safety_level"]
                                            ) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $site["safety_level"]
                                            ) ?>

                                        </span>

                                    </td>



                                    <!-- DISTANCE -->

                                    <td>

                                        <?php if (
                                            $site["distance_from_habitation"]
                                            !== null
                                        ): ?>

                                            <strong>

                                                <?= number_format(
                                                    (float)
                                                    $site[
                                                        "distance_from_habitation"
                                                    ],
                                                    2
                                                ) ?>

                                            </strong>

                                            km

                                        <?php else: ?>

                                            <span
                                                style="
                                                    color:#94a3b8;
                                                    font-size:11px;
                                                "
                                            >
                                                Not specified
                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- FACILITIES -->

                                    <td>

                                        <div class="facility-list">

                                            <?= htmlspecialchars(
                                                $site["facilities"]
                                                ?: "No facilities listed."
                                            ) ?>

                                        </div>

                                    </td>



                                    <!-- ACTIONS -->

                                    <?php if (($user["role"] ?? "") === "ADMIN"): ?>

                                        <td>

                                            <div class="action-buttons">


                                                <button
                                                    type="button"
                                                    class="btn btn-small btn-edit"
                                                    onclick='openEditModal(
                                                        <?= json_encode(
                                                            $site,
                                                            JSON_HEX_TAG |
                                                            JSON_HEX_APOS |
                                                            JSON_HEX_QUOT |
                                                            JSON_HEX_AMP
                                                        ) ?>
                                                    )'
                                                >
                                                    Edit
                                                </button>


                                                <form
                                                    method="POST"
                                                    onsubmit="
                                                        return confirmDelete();
                                                    "
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="delete_site"
                                                        value="<?= (int) $site["id"] ?>"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="btn btn-small btn-delete"
                                                    >
                                                        Delete
                                                    </button>

                                                </form>


                                            </div>

                                        </td>

                                    <?php endif; ?>


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
     ADD MODAL
     ===================================================== -->

<div
    class="modal"
    id="addModal"
>

    <div class="modal-card">


        <div class="modal-header">

            <h2>
                Add Relocation Site
            </h2>


            <button
                type="button"
                class="modal-close"
                onclick="closeAddModal()"
            >
                ×
            </button>

        </div>


        <form method="POST">


            <div class="modal-body">


                <div class="form-grid">


                    <div class="form-group">

                        <label class="form-label">
                            Site Name *
                        </label>

                        <input
                            type="text"
                            name="site_name"
                            class="form-control"
                            placeholder="e.g. Safe Site A"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            District *
                        </label>

                        <input
                            type="text"
                            name="district"
                            class="form-control"
                            placeholder="Enter district"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Latitude
                        </label>

                        <input
                            type="number"
                            name="latitude"
                            class="form-control"
                            step="any"
                            placeholder="e.g. 18.5204"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Longitude
                        </label>

                        <input
                            type="number"
                            name="longitude"
                            class="form-control"
                            step="any"
                            placeholder="e.g. 73.8567"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Total Capacity
                        </label>

                        <input
                            type="number"
                            name="total_capacity"
                            class="form-control"
                            min="0"
                            value="0"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Occupied Capacity
                        </label>

                        <input
                            type="number"
                            name="occupied_capacity"
                            class="form-control"
                            min="0"
                            value="0"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Safety Level
                        </label>

                        <select
                            name="safety_level"
                            class="form-control"
                            required
                        >

                            <option value="HIGH">
                                HIGH
                            </option>

                            <option value="MEDIUM">
                                MEDIUM
                            </option>

                            <option value="LOW">
                                LOW
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Distance from Habitation (km)
                        </label>

                        <input
                            type="number"
                            name="distance_from_habitation"
                            class="form-control"
                            min="0"
                            step="0.01"
                            placeholder="e.g. 5.20"
                        >

                    </div>


                    <div class="form-group full">

                        <label class="form-label">
                            Facilities
                        </label>

                        <textarea
                            name="facilities"
                            class="form-control"
                            rows="3"
                            placeholder="Hospital, School, Water Supply, Electricity, Road Access"
                        ></textarea>


                        <div class="facility-help">

                            Separate facilities with commas.

                        </div>

                    </div>


                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeAddModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    name="add_site"
                    class="btn btn-primary"
                >
                    Add Site
                </button>

            </div>


        </form>

    </div>

</div>



<!-- =====================================================
     EDIT MODAL
     ===================================================== -->

<div
    class="modal"
    id="editModal"
>

    <div class="modal-card">


        <div class="modal-header">

            <h2>
                Edit Relocation Site
            </h2>


            <button
                type="button"
                class="modal-close"
                onclick="closeEditModal()"
            >
                ×
            </button>

        </div>


        <form method="POST">


            <input
                type="hidden"
                name="id"
                id="edit_id"
            >


            <div class="modal-body">


                <div class="form-grid">


                    <div class="form-group">

                        <label class="form-label">
                            Site Name *
                        </label>

                        <input
                            type="text"
                            name="site_name"
                            id="edit_site_name"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            District *
                        </label>

                        <input
                            type="text"
                            name="district"
                            id="edit_district"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Latitude
                        </label>

                        <input
                            type="number"
                            name="latitude"
                            id="edit_latitude"
                            class="form-control"
                            step="any"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Longitude
                        </label>

                        <input
                            type="number"
                            name="longitude"
                            id="edit_longitude"
                            class="form-control"
                            step="any"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Total Capacity
                        </label>

                        <input
                            type="number"
                            name="total_capacity"
                            id="edit_total_capacity"
                            class="form-control"
                            min="0"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Occupied Capacity
                        </label>

                        <input
                            type="number"
                            name="occupied_capacity"
                            id="edit_occupied_capacity"
                            class="form-control"
                            min="0"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Safety Level
                        </label>

                        <select
                            name="safety_level"
                            id="edit_safety_level"
                            class="form-control"
                            required
                        >

                            <option value="HIGH">
                                HIGH
                            </option>

                            <option value="MEDIUM">
                                MEDIUM
                            </option>

                            <option value="LOW">
                                LOW
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Distance from Habitation (km)
                        </label>

                        <input
                            type="number"
                            name="distance_from_habitation"
                            id="edit_distance"
                            class="form-control"
                            min="0"
                            step="0.01"
                        >

                    </div>


                    <div class="form-group full">

                        <label class="form-label">
                            Facilities
                        </label>

                        <textarea
                            name="facilities"
                            id="edit_facilities"
                            class="form-control"
                            rows="3"
                        ></textarea>

                    </div>


                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeEditModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    name="update_site"
                    class="btn btn-primary"
                >
                    Save Changes
                </button>

            </div>


        </form>

    </div>

</div>



<script>

/*
|--------------------------------------------------------------------------
| ADD MODAL
|--------------------------------------------------------------------------
*/

function openAddModal() {

    document
        .getElementById("addModal")
        .classList
        .add("active");

}


function closeAddModal() {

    document
        .getElementById("addModal")
        .classList
        .remove("active");

}


/*
|--------------------------------------------------------------------------
| EDIT MODAL
|--------------------------------------------------------------------------
*/

function openEditModal(data) {

    document.getElementById("edit_id").value =
        data.id ?? "";

    document.getElementById("edit_site_name").value =
        data.site_name ?? "";

    document.getElementById("edit_district").value =
        data.district ?? "";

    document.getElementById("edit_latitude").value =
        data.latitude ?? "";

    document.getElementById("edit_longitude").value =
        data.longitude ?? "";

    document.getElementById("edit_total_capacity").value =
        data.total_capacity ?? 0;

    document.getElementById("edit_occupied_capacity").value =
        data.occupied_capacity ?? 0;

    document.getElementById("edit_safety_level").value =
        data.safety_level ?? "LOW";

    document.getElementById("edit_distance").value =
        data.distance_from_habitation ?? "";

    document.getElementById("edit_facilities").value =
        data.facilities ?? "";


    document
        .getElementById("editModal")
        .classList
        .add("active");

}


function closeEditModal() {

    document
        .getElementById("editModal")
        .classList
        .remove("active");

}


/*
|--------------------------------------------------------------------------
| DELETE CONFIRMATION
|--------------------------------------------------------------------------
*/

function confirmDelete() {

    return confirm(
        "Are you sure you want to delete this relocation site? " +
        "If it is already used by a relocation plan, deletion may fail."
    );

}


/*
|--------------------------------------------------------------------------
| CLOSE MODALS OUTSIDE CLICK
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "click",
    function(event) {

        const addModal =
            document.getElementById("addModal");

        const editModal =
            document.getElementById("editModal");


        if (event.target === addModal) {

            closeAddModal();

        }


        if (event.target === editModal) {

            closeEditModal();

        }

    }
);

</script>


</body>

</html>