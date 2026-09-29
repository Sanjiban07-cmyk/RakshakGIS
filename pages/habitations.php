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
| DELETE HABITATION
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_id"])) {

    if (($user["role"] ?? "") !== "ADMIN") {

        $error = "Only administrators can delete habitations.";

    } else {

        $deleteId = (int) $_POST["delete_id"];

        if ($deleteId > 0) {

            $stmt = $conn->prepare("
                DELETE FROM habitations
                WHERE id = ?
            ");

            $stmt->bind_param("i", $deleteId);

            if ($stmt->execute()) {

                $message = "Habitation deleted successfully.";

            } else {

                $error = "Unable to delete the habitation.";
            }

            $stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| ADD HABITATION
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_habitation"])) {

    if (($user["role"] ?? "") !== "ADMIN") {

        $error = "Only administrators can add habitations.";

    } else {

        $name = trim($_POST["name"] ?? "");
        $district = trim($_POST["district"] ?? "");
        $state = trim($_POST["state"] ?? "Maharashtra");

        $population = (int) ($_POST["population"] ?? 0);

        $latitude = trim($_POST["latitude"] ?? "");
        $longitude = trim($_POST["longitude"] ?? "");

        $floodRisk = (float) ($_POST["flood_risk"] ?? 0);
        $landslideRisk = (float) ($_POST["landslide_risk"] ?? 0);
        $hazardHistory = (float) ($_POST["hazard_history"] ?? 0);
        $populationVulnerability = (float) ($_POST["population_vulnerability"] ?? 0);


        if ($name === "" || $district === "") {

            $error = "Habitation name and district are required.";

        } elseif ($population < 0) {

            $error = "Population cannot be negative.";

        } else {

            $latitudeValue =
                $latitude === ""
                ? null
                : (float) $latitude;

            $longitudeValue =
                $longitude === ""
                ? null
                : (float) $longitude;


            $stmt = $conn->prepare("
                INSERT INTO habitations
                (
                    name,
                    district,
                    state,
                    population,
                    latitude,
                    longitude,
                    flood_risk,
                    landslide_risk,
                    hazard_history,
                    population_vulnerability
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");


            $stmt->bind_param(
                "sssidddddd",
                $name,
                $district,
                $state,
                $population,
                $latitudeValue,
                $longitudeValue,
                $floodRisk,
                $landslideRisk,
                $hazardHistory,
                $populationVulnerability
            );


            if ($stmt->execute()) {

                $message = "Habitation added successfully.";

            } else {

                $error = "Unable to add habitation.";
            }

            $stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE HABITATION
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_habitation"])) {

    if (($user["role"] ?? "") !== "ADMIN") {

        $error = "Only administrators can edit habitations.";

    } else {

        $id = (int) ($_POST["id"] ?? 0);

        $name = trim($_POST["name"] ?? "");
        $district = trim($_POST["district"] ?? "");
        $state = trim($_POST["state"] ?? "Maharashtra");

        $population = (int) ($_POST["population"] ?? 0);

        $latitude = trim($_POST["latitude"] ?? "");
        $longitude = trim($_POST["longitude"] ?? "");

        $floodRisk = (float) ($_POST["flood_risk"] ?? 0);
        $landslideRisk = (float) ($_POST["landslide_risk"] ?? 0);
        $hazardHistory = (float) ($_POST["hazard_history"] ?? 0);
        $populationVulnerability = (float) ($_POST["population_vulnerability"] ?? 0);


        if ($id <= 0) {

            $error = "Invalid habitation.";

        } elseif ($name === "" || $district === "") {

            $error = "Habitation name and district are required.";

        } else {

            $latitudeValue =
                $latitude === ""
                ? null
                : (float) $latitude;

            $longitudeValue =
                $longitude === ""
                ? null
                : (float) $longitude;


            $stmt = $conn->prepare("
                UPDATE habitations

                SET
                    name = ?,
                    district = ?,
                    state = ?,
                    population = ?,
                    latitude = ?,
                    longitude = ?,
                    flood_risk = ?,
                    landslide_risk = ?,
                    hazard_history = ?,
                    population_vulnerability = ?

                WHERE id = ?
            ");


            $stmt->bind_param(
                "sssiddddddi",
                $name,
                $district,
                $state,
                $population,
                $latitudeValue,
                $longitudeValue,
                $floodRisk,
                $landslideRisk,
                $hazardHistory,
                $populationVulnerability,
                $id
            );


            if ($stmt->execute()) {

                $message = "Habitation updated successfully.";

            } else {

                $error = "Unable to update habitation.";
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

$search = trim($_GET["search"] ?? "");

$searchSql = "";
$searchParam = "";

if ($search !== "") {

    $searchSql = "
        WHERE
            name LIKE ?
            OR district LIKE ?
            OR state LIKE ?
    ";

    $searchParam = "%" . $search . "%";
}


/*
|--------------------------------------------------------------------------
| FETCH HABITATIONS
|--------------------------------------------------------------------------
*/

$habitations = [];


if ($search !== "") {

    $stmt = $conn->prepare("
        SELECT *
        FROM habitations
        $searchSql
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
        FROM habitations
        ORDER BY created_at DESC
    ");
}


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $habitations[] = $row;
    }
}




/*
|--------------------------------------------------------------------------
| TOTAL POPULATION
|--------------------------------------------------------------------------
*/

$totalPopulation = 0;

foreach ($habitations as $habitation) {

    $totalPopulation += (int) $habitation["population"];
}


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function calculateRisk(array $habitation): float
{
    return (
        (float) $habitation["flood_risk"] +
        (float) $habitation["landslide_risk"] +
        (float) $habitation["hazard_history"] +
        (float) $habitation["population_vulnerability"]
    ) / 4;
}


function getRiskLevel(float $score): string
{
    if ($score >= 70) {
        return "HIGH";
    }

    if ($score >= 40) {
        return "MEDIUM";
    }

    return "LOW";
}


function getRiskClass(string $level): string
{
    return match ($level) {
        "HIGH" => "badge-high",
        "MEDIUM" => "badge-medium",
        default => "badge-low"
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

    <title>Habitations | RakshakGIS</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        /* =========================================
           HABITATIONS PAGE
           ========================================= */

        .page-actions {

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            margin-bottom: 22px;

        }


        .page-heading h1 {

            font-size: 24px;
            font-weight: 800;

        }


        .page-heading p {

            margin-top: 5px;

            color: var(--muted);

            font-size: 12px;

        }


        .habitation-summary {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;

            margin-bottom: 20px;

        }


        .summary-card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 18px;

        }


        .summary-label {

            font-size: 11px;

            color: var(--muted);

        }


        .summary-value {

            font-size: 24px;

            font-weight: 800;

            margin-top: 6px;

        }


        .toolbar {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 18px;

        }


        .search-form {

            display: flex;

            gap: 8px;

            width: 100%;

            max-width: 430px;

        }


        .search-input {

            flex: 1;

            border: 1px solid var(--border);

            border-radius: 8px;

            padding: 10px 13px;

            font-family: inherit;

            font-size: 13px;

            outline: none;

        }


        .search-input:focus {

            border-color: var(--primary);

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,0.1);

        }


        .table-card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 12px;

            overflow: hidden;

        }


        .habitation-name {

            font-weight: 700;

        }


        .habitation-location {

            color: var(--muted);

            font-size: 11px;

            margin-top: 3px;

        }


        .risk-mini {

            font-weight: 700;

        }


        .coordinate {

            font-family: monospace;

            font-size: 11px;

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


        .alert {

            margin-bottom: 18px;

        }


        /* Modal */

        .modal {

            position: fixed;

            inset: 0;

            background: rgba(15,23,42,0.55);

            display: none;

            align-items: center;

            justify-content: center;

            padding: 20px;

            z-index: 5000;

            backdrop-filter: blur(3px);

        }


        .modal.active {

            display: flex;

        }


        .modal-card {

            background: white;

            width: 100%;

            max-width: 720px;

            max-height: 90vh;

            overflow-y: auto;

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

            color: var(--muted);

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


        .range-value {

            color: var(--primary);

            font-weight: 700;

        }


        @media (max-width: 900px) {

            .habitation-summary {

                grid-template-columns: 1fr;

            }

            .page-actions {

                align-items: flex-start;

                flex-direction: column;

            }

            .toolbar {

                flex-direction: column;

                align-items: stretch;

            }

        }


        @media (max-width: 700px) {

            .form-grid {

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
                class="nav-link active"
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
                    Habitations
                </div>

                <div class="page-subtitle">
                    Manage registered habitations and risk factors
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

            <div class="page-actions">

                <div class="page-heading">

                    <h1>
                        Habitation Management
                    </h1>

                    <p>
                        Register and maintain habitation information
                        used for disaster risk assessment.
                    </p>

                </div>


                <?php if (($user["role"] ?? "") === "ADMIN"): ?>

                    <button
                        class="btn btn-primary"
                        onclick="openAddModal()"
                    >

                        + Add Habitation

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

            <div class="habitation-summary">


                <div class="summary-card">

                    <div class="summary-label">
                        Total Habitations
                    </div>

                    <div class="summary-value">
                        <?= count($habitations) ?>
                    </div>

                </div>


                <div class="summary-card">

                    <div class="summary-label">
                        Total Population
                    </div>

                    <div class="summary-value">
                        <?= number_format($totalPopulation) ?>
                    </div>

                </div>


                <div class="summary-card">

                    <div class="summary-label">
                        Current Records
                    </div>

                    <div class="summary-value">
                        <?= count($habitations) ?>
                    </div>

                </div>


            </div>



            <!-- TOOLBAR -->

            <div class="toolbar">


                <form
                    method="GET"
                    class="search-form"
                >

                    <input
                        type="text"
                        name="search"
                        class="search-input"
                        placeholder="Search habitation, district or state..."
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
                            href="habitations.php"
                            class="btn btn-secondary"
                        >
                            Clear
                        </a>

                    <?php endif; ?>

                </form>


            </div>



            <!-- TABLE -->

            <div class="table-card">

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Habitation
                                </th>

                                <th>
                                    Population
                                </th>

                                <th>
                                    Coordinates
                                </th>

                                <th>
                                    Risk Factors
                                </th>

                                <th>
                                    Overall Risk
                                </th>

                                <?php if (($user["role"] ?? "") === "ADMIN"): ?>

                                    <th>
                                        Actions
                                    </th>

                                <?php endif; ?>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($habitations)): ?>

                            <tr>

                                <td
                                    colspan="6"
                                    style="
                                        text-align:center;
                                        padding:40px;
                                        color:#64748b;
                                    "
                                >

                                    No habitations found.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($habitations as $habitation): ?>


                                <?php

                                $riskScore =
                                    calculateRisk($habitation);

                                $riskLevel =
                                    getRiskLevel($riskScore);

                                ?>


                                <tr>


                                    <!-- NAME -->

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



                                    <!-- POPULATION -->

                                    <td>

                                        <strong>

                                            <?= number_format(
                                                (int) $habitation["population"]
                                            ) ?>

                                        </strong>

                                    </td>



                                    <!-- COORDINATES -->

                                    <td>

                                        <?php if (
                                            $habitation["latitude"] !== null &&
                                            $habitation["longitude"] !== null
                                        ): ?>

                                            <div class="coordinate">

                                                <?= htmlspecialchars(
                                                    $habitation["latitude"]
                                                ) ?>

                                                ,

                                                <?= htmlspecialchars(
                                                    $habitation["longitude"]
                                                ) ?>

                                            </div>

                                        <?php else: ?>

                                            <span class="no-assessment">
                                                Not available
                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- RISK FACTORS -->

                                    <td>

                                        <div style="font-size:11px;line-height:1.8;">

                                            Flood:
                                            <strong>
                                                <?= number_format(
                                                    (float) $habitation["flood_risk"],
                                                    0
                                                ) ?>
                                            </strong>

                                            ·

                                            Landslide:
                                            <strong>
                                                <?= number_format(
                                                    (float) $habitation["landslide_risk"],
                                                    0
                                                ) ?>
                                            </strong>

                                            <br>

                                            History:
                                            <strong>
                                                <?= number_format(
                                                    (float) $habitation["hazard_history"],
                                                    0
                                                ) ?>
                                            </strong>

                                            ·

                                            Vulnerability:
                                            <strong>
                                                <?= number_format(
                                                    (float) $habitation["population_vulnerability"],
                                                    0
                                                ) ?>
                                            </strong>

                                        </div>

                                    </td>



                                    <!-- OVERALL RISK -->

                                    <td>

                                        <div
                                            class="risk-mini"
                                            style="margin-bottom:5px;"
                                        >

                                            <?= number_format(
                                                $riskScore,
                                                1
                                            ) ?>

                                        </div>


                                        <span
                                            class="badge <?= getRiskClass(
                                                $riskLevel
                                            ) ?>"
                                        >

                                            <?= $riskLevel ?>

                                        </span>

                                    </td>



                                    <!-- ACTIONS -->

                                    <?php if (($user["role"] ?? "") === "ADMIN"): ?>

                                        <td>

                                            <div class="action-buttons">


                                               <a
    href="edit_habitation.php?id=<?= (int)$habitation['id'] ?>"
    class="btn btn-small btn-edit"
>
    Edit
</a>


                                                <form
                                                    method="POST"
                                                    onsubmit="return confirmDelete();"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="delete_id"
                                                        value="<?= (int) $habitation["id"] ?>"
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
                Add New Habitation
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
                            Habitation Name *
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            placeholder="Enter habitation name"
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
                            State
                        </label>

                        <input
                            type="text"
                            name="state"
                            class="form-control"
                            value="Maharashtra"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Population
                        </label>

                        <input
                            type="number"
                            name="population"
                            class="form-control"
                            min="0"
                            value="0"
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
                            Flood Risk
                            <span class="range-value">
                                0–100
                            </span>
                        </label>

                        <input
                            type="number"
                            name="flood_risk"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.01"
                            value="0"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Landslide Risk
                            <span class="range-value">
                                0–100
                            </span>
                        </label>

                        <input
                            type="number"
                            name="landslide_risk"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.01"
                            value="0"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Hazard History
                        </label>

                        <input
                            type="number"
                            name="hazard_history"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.01"
                            value="0"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Population Vulnerability
                        </label>

                        <input
                            type="number"
                            name="population_vulnerability"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.01"
                            value="0"
                        >

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
                    name="add_habitation"
                    class="btn btn-primary"
                >
                    Add Habitation
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
                Edit Habitation
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
                            Habitation Name *
                        </label>

                        <input
                            type="text"
                            name="name"
                            id="edit_name"
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
                            State
                        </label>

                        <input
                            type="text"
                            name="state"
                            id="edit_state"
                            class="form-control"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Population
                        </label>

                        <input
                            type="number"
                            name="population"
                            id="edit_population"
                            class="form-control"
                            min="0"
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
                            Flood Risk
                        </label>

                        <input
                            type="number"
                            name="flood_risk"
                            id="edit_flood_risk"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.01"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Landslide Risk
                        </label>

                        <input
                            type="number"
                            name="landslide_risk"
                            id="edit_landslide_risk"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.01"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Hazard History
                        </label>

                        <input
                            type="number"
                            name="hazard_history"
                            id="edit_hazard_history"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.01"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Population Vulnerability
                        </label>

                        <input
                            type="number"
                            name="population_vulnerability"
                            id="edit_population_vulnerability"
                            class="form-control"
                            min="0"
                            max="100"
                            step="0.01"
                        >

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
                    name="update_habitation"
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
| Add Modal
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
| Edit Modal
|--------------------------------------------------------------------------
*/

function openEditModal(data) {

    document.getElementById("edit_id").value =
        data.id ?? "";

    document.getElementById("edit_name").value =
        data.name ?? "";

    document.getElementById("edit_district").value =
        data.district ?? "";

    document.getElementById("edit_state").value =
        data.state ?? "";

    document.getElementById("edit_population").value =
        data.population ?? 0;

    document.getElementById("edit_latitude").value =
        data.latitude ?? "";

    document.getElementById("edit_longitude").value =
        data.longitude ?? "";

    document.getElementById("edit_flood_risk").value =
        data.flood_risk ?? 0;

    document.getElementById("edit_landslide_risk").value =
        data.landslide_risk ?? 0;

    document.getElementById("edit_hazard_history").value =
        data.hazard_history ?? 0;

    document.getElementById("edit_population_vulnerability").value =
        data.population_vulnerability ?? 0;


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
| Delete Confirmation
|--------------------------------------------------------------------------
*/

function confirmDelete() {

    return confirm(
        "Are you sure you want to delete this habitation? " +
        "All related risk assessments and relocation plans " +
        "may also be deleted because of the database relationships."
    );

}


/*
|--------------------------------------------------------------------------
| Close modal when clicking outside
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