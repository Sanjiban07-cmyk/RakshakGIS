<?php

$currentPage = 'add_habitation';

require_once __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $state = trim($_POST['state'] ?? 'Maharashtra');

    $population = isset($_POST['population']) && is_numeric($_POST['population'])
        ? (int) $_POST['population']
        : 0;

    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== ''
        ? (float) $_POST['latitude']
        : null;

    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== ''
        ? (float) $_POST['longitude']
        : null;

    $floodRisk = isset($_POST['flood_risk']) && is_numeric($_POST['flood_risk'])
        ? (float) $_POST['flood_risk']
        : 0;

    $landslideRisk = isset($_POST['landslide_risk']) && is_numeric($_POST['landslide_risk'])
        ? (float) $_POST['landslide_risk']
        : 0;

    $hazardHistory = isset($_POST['hazard_history']) && is_numeric($_POST['hazard_history'])
        ? (float) $_POST['hazard_history']
        : 0;

    $populationVulnerability = isset($_POST['population_vulnerability']) && is_numeric($_POST['population_vulnerability'])
        ? (float) $_POST['population_vulnerability']
        : 0;

    if ($name === '' || $district === '' || $population <= 0) {

        $message = 'Please enter habitation name, district and a valid population.';
        $messageType = 'error';

    } elseif (
        $floodRisk < 0 || $floodRisk > 100 ||
        $landslideRisk < 0 || $landslideRisk > 100 ||
        $hazardHistory < 0 || $hazardHistory > 100 ||
        $populationVulnerability < 0 || $populationVulnerability > 100
    ) {

        $message = 'Risk values must be between 0 and 100.';
        $messageType = 'error';

    } else {

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

        if (!$stmt) {

            $message = 'Unable to prepare habitation data.';
            $messageType = 'error';

        } else {

            $stmt->bind_param(
                "sssiddssss",
                $name,
                $district,
                $state,
                $population,
                $latitude,
                $longitude,
                $floodRisk,
                $landslideRisk,
                $hazardHistory,
                $populationVulnerability
            );

            if ($stmt->execute()) {

                $newHabitationId = $stmt->insert_id;

                $stmt->close();

                header(
                    "Location: habitation_details.php?id=" .
                    $newHabitationId
                );
                exit;

            } else {

                $message = 'Unable to save habitation. Please try again.';
                $messageType = 'error';

                $stmt->close();
            }
        }
    }
}

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
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

    <title>Add Habitation | RakshakGIS</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f8fc;
            color: #12233f;
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */

        .sidebar {
            width: 270px;
            background: #0d172b;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
        }

        .brand {
            height: 207px;
            height: 207px;
            padding: 18px 22px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            background: #2864e8;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
        }

        .brand-text {
            padding-top: 2px;
        }

        .brand-name {
            font-size: 20px;
            font-weight: 700;
        }

        .brand-subtitle {
            font-size: 11px;
            color: #aebbd0;
            margin-top: 4px;
        }

        .nav-section {
            padding: 34px 14px 0;
        }

        .nav-title {
            color: #73829c;
            font-size: 11px;
            letter-spacing: 1px;
            margin: 0 12px 15px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 15px;
            margin-bottom: 3px;
            border-radius: 8px;
            color: #dce4f2;
            text-decoration: none;
            font-size: 15px;
        }

        .nav-link:hover {
            background: #172541;
        }

        .nav-link.active {
            background: #2864e8;
            color: white;
        }

        .nav-icon {
            width: 18px;
            text-align: center;
            font-size: 16px;
        }

        /* MAIN */

        .main {
            margin-left: 270px;
            width: calc(100% - 270px);
            min-height: 100vh;
        }

        .topbar {
            height: 77px;
            background: white;
            border-bottom: 1px solid #dfe6ef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
        }

        .top-title {
            font-size: 23px;
            font-weight: 700;
        }

        .top-subtitle {
            color: #70809a;
            font-size: 13px;
            margin-top: 5px;
        }

        .top-right {
            display: flex;
            align-items: center;
            gap: 28px;
            font-weight: 600;
        }

        .online {
            color: #129b4b;
            font-size: 13px;
            font-weight: 500;
        }

        .online-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #16a34a;
            margin-right: 7px;
        }

        .content {
            padding: 38px 32px 60px;
        }

        .page-title {
            font-size: 34px;
            margin: 0;
        }

        .page-description {
            margin: 8px 0 28px;
            font-size: 16px;
            color: #274365;
        }

        /* FORM */

        .form-card {
            background: white;
            border: 1px solid #dce4ee;
            border-radius: 14px;
            padding: 28px;
            max-width: 1100px;
            box-shadow: 0 2px 8px rgba(18,35,63,0.03);
        }

        .section-title {
            font-size: 19px;
            margin: 0 0 6px;
        }

        .section-description {
            margin: 0 0 22px;
            color: #72829a;
            font-size: 13px;
        }

        .form-section {
            padding-bottom: 28px;
            margin-bottom: 28px;
            border-bottom: 1px solid #e5eaf1;
        }

        .form-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            color: #52647d;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input,
        select {
            width: 100%;
            height: 46px;
            border: 1px solid #cfd9e6;
            border-radius: 8px;
            padding: 0 13px;
            font-size: 15px;
            color: #12233f;
            background: white;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: #2864e8;
            box-shadow: 0 0 0 3px rgba(40,100,232,0.10);
        }

        .hint {
            color: #8795a9;
            font-size: 11px;
            margin-top: 6px;
        }

        .risk-input {
            position: relative;
        }

        .risk-input input {
            padding-right: 45px;
        }

        .risk-unit {
            position: absolute;
            right: 14px;
            top: 14px;
            color: #73829a;
            font-size: 13px;
        }

        .message {
            max-width: 1100px;
            padding: 14px 17px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .message.error {
            background: #fff0f0;
            border: 1px solid #ffcaca;
            color: #b42318;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 26px;
        }

        .btn {
            height: 45px;
            padding: 0 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-secondary {
            border: 1px solid #cfd9e6;
            background: white;
            color: #31445f;
        }

        .btn-primary {
            border: none;
            background: #2864e8;
            color: white;
        }

        .btn-primary:hover {
            background: #1f57d2;
        }

        @media (max-width: 900px) {

            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
                width: calc(100% - 220px);
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }
        }

        @media (max-width: 700px) {

            .sidebar {
                display: none;
            }

            .main {
                margin-left: 0;
                width: 100%;
            }

            .content {
                padding: 25px 18px;
            }

            .topbar {
                padding: 0 18px;
            }

            .top-right {
                display: none;
            }

            .page-title {
                font-size: 28px;
            }

        }

    </style>

</head>

<body>

<div class="app">

    <aside class="sidebar">

        <div class="brand">

            <div class="brand-logo">R</div>

            <div class="brand-text">
                <div class="brand-name">RakshakGIS</div>
                <div class="brand-subtitle">
                    Disaster Risk & Relocation
                </div>
            </div>

        </div>

        <div class="nav-section">

            <div class="nav-title">MAIN</div>

            <a
                class="nav-link"
                href="dashboard.php"
            >
                <span class="nav-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a
                class="nav-link"
                href="habitations.php"
            >
                <span class="nav-icon">⌂</span>
                <span>Habitations</span>
            </a>

            <a
                class="nav-link active"
                href="add_habitation.php"
            >
                <span class="nav-icon">＋</span>
                <span>Add Habitation</span>
            </a>

            <a
                class="nav-link"
                href="risk_assessment.php"
            >
                <span class="nav-icon">⚠</span>
                <span>Risk Assessment</span>
            </a>

            <a
                class="nav-link"
                href="map.php"
            >
                <span class="nav-icon">●</span>
                <span>Interactive Map</span>
            </a>

            <a
                class="nav-link"
                href="relocation.php"
            >
                <span class="nav-icon">⌖</span>
                <span>Relocation Planner</span>
            </a>

            <a
                class="nav-link"
                href="relocation_details.php"
            >
                <span class="nav-icon">▣</span>
                <span>Relocation Plans</span>
            </a>

            <a
                class="nav-link"
                href="reports.php"
            >
                <span class="nav-icon">▤</span>
                <span>Reports</span>
            </a>

        </div>

        <div class="nav-section">

            <div class="nav-title">INFORMATION</div>

            <a
                class="nav-link"
                href="about.php"
            >
                <span class="nav-icon">ⓘ</span>
                <span>About</span>
            </a>

            <a
                class="nav-link"
                href="contact.php"
            >
                <span class="nav-icon">✉</span>
                <span>Contact</span>
            </a>

        </div>

    </aside>


    <main class="main">

        <header class="topbar">

            <div>
                <div class="top-title">
                    Add Habitation
                </div>

                <div class="top-subtitle">
                    Register a new habitation for disaster risk assessment
                </div>
            </div>

            <div class="top-right">

                <div class="online">
                    <span class="online-dot"></span>
                    System Online
                </div>

                <div>🔔</div>

                <div>Admin</div>

            </div>

        </header>


        <section class="content">

            <h1 class="page-title">
                Add Habitation
            </h1>

            <p class="page-description">
                Register habitation details and risk factors in the RakshakGIS system.
            </p>


            <?php if ($message !== ''): ?>

                <div class="message <?= e($messageType) ?>">
                    <?= e($message) ?>
                </div>

            <?php endif; ?>


            <form
                class="form-card"
                method="POST"
                action="add_habitation.php"
            >

                <!-- BASIC INFORMATION -->

                <div class="form-section">

                    <h2 class="section-title">
                        Habitation Information
                    </h2>

                    <p class="section-description">
                        Enter the basic information of the habitation.
                    </p>

                    <div class="form-grid">

                        <div class="form-group">

                            <label for="name">
                                Habitation Name *
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                required
                                maxlength="150"
                                placeholder="e.g. Khelarai"
                                value="<?= e($_POST['name'] ?? '') ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="district">
                                District *
                            </label>

                            <input
                                type="text"
                                id="district"
                                name="district"
                                required
                                maxlength="100"
                                placeholder="e.g. Pune"
                                value="<?= e($_POST['district'] ?? '') ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="state">
                                State
                            </label>

                            <input
                                type="text"
                                id="state"
                                name="state"
                                maxlength="100"
                                value="<?= e($_POST['state'] ?? 'Maharashtra') ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="population">
                                Population *
                            </label>

                            <input
                                type="number"
                                id="population"
                                name="population"
                                required
                                min="1"
                                placeholder="e.g. 1250"
                                value="<?= e($_POST['population'] ?? '') ?>"
                            >

                        </div>

                    </div>

                </div>


                <!-- LOCATION -->

                <div class="form-section">

                    <h2 class="section-title">
                        Geographic Location
                    </h2>

                    <p class="section-description">
                        Enter the coordinates used by the interactive map and relocation engine.
                    </p>

                    <div class="form-grid">

                        <div class="form-group">

                            <label for="latitude">
                                Latitude
                            </label>

                            <input
                                type="number"
                                id="latitude"
                                name="latitude"
                                step="any"
                                min="-90"
                                max="90"
                                placeholder="e.g. 18.5204"
                                value="<?= e($_POST['latitude'] ?? '') ?>"
                            >

                            <div class="hint">
                                Range: -90 to 90
                            </div>

                        </div>


                        <div class="form-group">

                            <label for="longitude">
                                Longitude
                            </label>

                            <input
                                type="number"
                                id="longitude"
                                name="longitude"
                                step="any"
                                min="-180"
                                max="180"
                                placeholder="e.g. 73.8567"
                                value="<?= e($_POST['longitude'] ?? '') ?>"
                            >

                            <div class="hint">
                                Range: -180 to 180
                            </div>

                        </div>

                    </div>

                </div>


                <!-- RISK FACTORS -->

                <div class="form-section">

                    <h2 class="section-title">
                        Disaster Risk Factors
                    </h2>

                    <p class="section-description">
                        Enter risk values from 0 to 100. These values are used for risk assessment.
                    </p>

                    <div class="form-grid">

                        <div class="form-group">

                            <label for="flood_risk">
                                Flood Risk
                            </label>

                            <div class="risk-input">

                                <input
                                    type="number"
                                    id="flood_risk"
                                    name="flood_risk"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    placeholder="0 - 100"
                                    value="<?= e($_POST['flood_risk'] ?? '0') ?>"
                                >

                                <span class="risk-unit">%</span>

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="landslide_risk">
                                Landslide Risk
                            </label>

                            <div class="risk-input">

                                <input
                                    type="number"
                                    id="landslide_risk"
                                    name="landslide_risk"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    placeholder="0 - 100"
                                    value="<?= e($_POST['landslide_risk'] ?? '0') ?>"
                                >

                                <span class="risk-unit">%</span>

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="hazard_history">
                                Hazard History
                            </label>

                            <div class="risk-input">

                                <input
                                    type="number"
                                    id="hazard_history"
                                    name="hazard_history"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    placeholder="0 - 100"
                                    value="<?= e($_POST['hazard_history'] ?? '0') ?>"
                                >

                                <span class="risk-unit">%</span>

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="population_vulnerability">
                                Population Vulnerability
                            </label>

                            <div class="risk-input">

                                <input
                                    type="number"
                                    id="population_vulnerability"
                                    name="population_vulnerability"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    placeholder="0 - 100"
                                    value="<?= e($_POST['population_vulnerability'] ?? '0') ?>"
                                >

                                <span class="risk-unit">%</span>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="form-actions">

                    <a
                        href="habitations.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Habitation
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

</body>
</html>