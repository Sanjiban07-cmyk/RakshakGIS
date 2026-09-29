<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| GET SELECTED HABITATION
|--------------------------------------------------------------------------
*/

$selectedHabitationId = isset($_GET['id']) && is_numeric($_GET['id'])
    ? (int) $_GET['id']
    : 0;

/*
|--------------------------------------------------------------------------
| LOAD HABITATIONS
|--------------------------------------------------------------------------
| Risk factor values are loaded from the habitations table so that
| selecting a habitation automatically fills the sliders.
|--------------------------------------------------------------------------
*/

$habitations = [];

$sql = "
    SELECT
        id,
        name,
        district,
        population,
        flood_risk,
        landslide_risk,
        hazard_history,
        population_vulnerability
    FROM habitations
    ORDER BY name ASC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $habitations[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Risk Assessment | RakshakGIS</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f8fafc;
            color: #172554;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* =========================================================
           SIDEBAR
        ========================================================= */

        .sidebar {
            width: 268px;
            background: #0f172a;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            padding: 18px 14px;
            overflow-y: auto;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 10px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            border-radius: 9px;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }

        .brand-name {
            font-size: 19px;
            font-weight: 700;
        }

        .brand-subtitle {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 3px;
        }

        .menu-title {
            color: #64748b;
            font-size: 11px;
            margin: 36px 12px 14px;
            letter-spacing: .5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #dbeafe;
            text-decoration: none;
            padding: 13px 14px;
            margin-bottom: 4px;
            border-radius: 8px;
            font-size: 15px;
        }

        .nav-link:hover {
            background: rgba(255,255,255,.06);
        }

        .nav-link.active {
            background: #2563eb;
            color: white;
        }

        .nav-icon {
            width: 20px;
            text-align: center;
        }

        /* =========================================================
           MAIN
        ========================================================= */

        .main {
            margin-left: 268px;
            width: calc(100% - 268px);
        }

        .topbar {
            height: 78px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
        }

        .top-title {
            font-size: 20px;
            font-weight: 700;
        }

        .top-subtitle {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }

        .admin-area {
            display: flex;
            align-items: center;
            gap: 20px;
            font-weight: 700;
        }

        .online {
            color: #16a34a;
            font-size: 13px;
            font-weight: 500;
        }

        .online::before {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #16a34a;
            border-radius: 50%;
            margin-right: 7px;
        }

        .content {
            padding: 38px 32px 60px;
            max-width: 1400px;
        }

        .page-title {
            font-size: 34px;
            margin-bottom: 6px;
        }

        .page-description {
            color: #475569;
            font-size: 16px;
            margin-bottom: 30px;
        }

        /* =========================================================
           CARDS
        ========================================================= */

        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 1px 2px rgba(15,23,42,.03);
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .card-description {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #334155;
        }

        select,
        input,
        textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 13px;
            font-size: 14px;
            background: white;
            outline: none;
        }

        select:focus,
        input:focus,
        textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }

        /* =========================================================
           SELECTED HABITATION INFO
        ========================================================= */

        .selected-info {
            display: none;
            margin-top: 14px;
            padding: 13px 16px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            color: #1d4ed8;
            font-size: 14px;
        }

        .selected-info strong {
            font-weight: 700;
        }

        /* =========================================================
           RANGE INPUTS
        ========================================================= */

        .range-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        input[type="range"] {
            padding: 0;
            accent-color: #2563eb;
        }

        .score-value {
            min-width: 48px;
            height: 38px;
            border-radius: 7px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .hint {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 6px;
        }

        /* =========================================================
           BUTTONS
        ========================================================= */

        .button-row {
            margin-top: 28px;
            display: flex;
            gap: 12px;
        }

        button {
            border: none;
            border-radius: 8px;
            padding: 13px 22px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .primary-btn {
            background: #2563eb;
            color: white;
        }

        .primary-btn:hover {
            background: #1d4ed8;
        }

        .secondary-btn {
            background: #e2e8f0;
            color: #334155;
        }

        /* =========================================================
           RESULT
        ========================================================= */

        #result {
            display: none;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .risk-score-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .score-label {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .score-number {
            font-size: 48px;
            font-weight: 800;
            color: #dc2626;
        }

        .risk-badge {
            padding: 9px 18px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 800;
        }

        .high {
            background: #fee2e2;
            color: #b91c1c;
        }

        .medium {
            background: #fef3c7;
            color: #b45309;
        }

        .low {
            background: #dcfce7;
            color: #15803d;
        }

        .red-zone {
            margin-top: 15px;
            padding: 16px 18px;
            border-radius: 9px;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-weight: 700;
        }

        .safe-zone {
            margin-top: 15px;
            padding: 16px 18px;
            border-radius: 9px;
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #15803d;
            font-weight: 700;
        }

        .priority-box {
            margin-top: 20px;
            padding: 18px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 10px;
            color: #c2410c;
            font-weight: 700;
        }

        /* =========================================================
           HAZARD FACTORS
        ========================================================= */

        .factor-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-top: 20px;
        }

        .factor {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px;
        }

        .factor-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .progress {
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: #2563eb;
            border-radius: 10px;
        }

        /* =========================================================
           PLAN RELOCATION BUTTON
        ========================================================= */

        .result-actions {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .plan-relocation-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 13px 24px;
            background: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .plan-relocation-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .plan-relocation-btn.disabled {
            pointer-events: none;
            opacity: 0.5;
        }

        .error-box {
            display: none;
            margin-top: 20px;
            padding: 15px;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 8px;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 900px) {

            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
                width: calc(100% - 220px);
            }

            .form-grid,
            .factor-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 650px) {

            .sidebar {
                width: 0;
                padding: 0;
                overflow: hidden;
            }

            .main {
                margin-left: 0;
                width: 100%;
            }

            .topbar {
                padding: 0 18px;
            }

            .content {
                padding: 25px 18px 40px;
            }

            .admin-area {
                gap: 8px;
            }

            .risk-score-box {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

        }

    </style>

</head>

<body>

<div class="layout">

    <!-- =========================================================
         SIDEBAR
    ========================================================= -->

    <aside class="sidebar">

        <div class="brand">

            <div class="brand-logo">
                R
            </div>

            <div>

                <div class="brand-name">
                    RakshakGIS
                </div>

                <div class="brand-subtitle">
                    Disaster Risk & Relocation
                </div>

            </div>

        </div>

        <div class="menu-title">
            MAIN
        </div>

        <a href="dashboard.php" class="nav-link">

            <span class="nav-icon">⌂</span>

            Dashboard

        </a>

        <a href="habitations.php" class="nav-link">

            <span class="nav-icon">⌂</span>

            Habitations

        </a>

        <a href="add_habitation.php" class="nav-link">

            <span class="nav-icon">＋</span>

            Add Habitation

        </a>

        <a href="risk_assessment.php" class="nav-link active">

            <span class="nav-icon">⚠</span>

            Risk Assessment

        </a>

        <a href="map.php" class="nav-link">

            <span class="nav-icon">●</span>

            Interactive Map

        </a>

        <a
            href="<?php echo $selectedHabitationId > 0
                ? 'relocation.php?id=' . $selectedHabitationId
                : 'habitations.php'; ?>"
            class="nav-link"
        >

            <span class="nav-icon">↕</span>

            Relocation Planner

        </a>

        <a href="relocation_plans.php" class="nav-link">

            <span class="nav-icon">▣</span>

            Relocation Plans

        </a>

        <a href="reports.php" class="nav-link">

            <span class="nav-icon">▤</span>

            Reports

        </a>

        <div class="menu-title">
            INFORMATION
        </div>

        <a href="about.php" class="nav-link">

            <span class="nav-icon">ⓘ</span>

            About

        </a>

        <a href="contact.php" class="nav-link">

            <span class="nav-icon">✉</span>

            Contact

        </a>

    </aside>

    <!-- =========================================================
         MAIN
    ========================================================= -->

    <main class="main">

        <header class="topbar">

            <div>

                <div class="top-title">
                    Risk Assessment
                </div>

                <div class="top-subtitle">
                    Evaluate disaster risk for registered habitations
                </div>

            </div>

            <div class="admin-area">

                <span class="online">
                    System Online
                </span>

                <span>🔔</span>

                <span>Admin</span>

            </div>

        </header>

        <section class="content">

            <h1 class="page-title">
                Risk Assessment
            </h1>

            <p class="page-description">
                Calculate disaster risk using hazard intensity,
                disaster history and population vulnerability.
            </p>

            <!-- =====================================================
                 ASSESSMENT FORM
            ====================================================== -->

            <div class="card">

                <div class="card-title">
                    New Risk Assessment
                </div>

                <div class="card-description">
                    Select a habitation and provide the current risk
                    factors. Each factor is evaluated on a scale of 0–100.
                </div>

                <form id="riskForm">

                    <div class="form-grid">

                        <!-- HABITATION -->

                        <div class="full">

                            <label for="habitation_id">
                                Select Habitation
                            </label>

                            <select
                                id="habitation_id"
                                name="habitation_id"
                                required
                            >

                                <option value="">
                                    -- Select habitation --
                                </option>

                                <?php foreach ($habitations as $habitation): ?>

                                    <option
                                        value="<?= (int)$habitation['id'] ?>"
                                        data-population="<?= (int)$habitation['population'] ?>"
                                        data-flood="<?= (float)$habitation['flood_risk'] ?>"
                                        data-landslide="<?= (float)$habitation['landslide_risk'] ?>"
                                        data-history="<?= (float)$habitation['hazard_history'] ?>"
                                        data-vulnerability="<?= (float)$habitation['population_vulnerability'] ?>"
                                        <?= $selectedHabitationId === (int)$habitation['id'] ? 'selected' : '' ?>
                                    >

                                        <?= htmlspecialchars($habitation['name']) ?>

                                        —

                                        <?= htmlspecialchars($habitation['district']) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <div
                                id="selectedInfo"
                                class="selected-info"
                            ></div>

                        </div>

                        <!-- FLOOD -->

                        <div>

                            <label for="flood_score">
                                Flood Risk
                            </label>

                            <div class="range-row">

                                <input
                                    type="range"
                                    id="flood_score"
                                    name="flood_score"
                                    min="0"
                                    max="100"
                                    value="50"
                                >

                                <div
                                    class="score-value"
                                    id="flood_value"
                                >
                                    50
                                </div>

                            </div>

                            <div class="hint">
                                0 = very low risk, 100 = extreme risk
                            </div>

                        </div>

                        <!-- LANDSLIDE -->

                        <div>

                            <label for="landslide_score">
                                Landslide Risk
                            </label>

                            <div class="range-row">

                                <input
                                    type="range"
                                    id="landslide_score"
                                    name="landslide_score"
                                    min="0"
                                    max="100"
                                    value="50"
                                >

                                <div
                                    class="score-value"
                                    id="landslide_value"
                                >
                                    50
                                </div>

                            </div>

                            <div class="hint">
                                0 = very low risk, 100 = extreme risk
                            </div>

                        </div>

                        <!-- HISTORY -->

                        <div>

                            <label for="hazard_history_score">
                                Hazard History
                            </label>

                            <div class="range-row">

                                <input
                                    type="range"
                                    id="hazard_history_score"
                                    name="hazard_history_score"
                                    min="0"
                                    max="100"
                                    value="50"
                                >

                                <div
                                    class="score-value"
                                    id="history_value"
                                >
                                    50
                                </div>

                            </div>

                            <div class="hint">
                                Historical frequency and severity of disasters
                            </div>

                        </div>

                        <!-- VULNERABILITY -->

                        <div>

                            <label for="vulnerability_score">
                                Population Vulnerability
                            </label>

                            <div class="range-row">

                                <input
                                    type="range"
                                    id="vulnerability_score"
                                    name="vulnerability_score"
                                    min="0"
                                    max="100"
                                    value="50"
                                >

                                <div
                                    class="score-value"
                                    id="vulnerability_value"
                                >
                                    50
                                </div>

                            </div>

                            <div class="hint">
                                Exposure of vulnerable population groups
                            </div>

                        </div>

                        <!-- NOTES -->

                        <div class="full">

                            <label for="assessment_notes">
                                Assessment Notes
                            </label>

                            <textarea
                                id="assessment_notes"
                                name="assessment_notes"
                                rows="4"
                                placeholder="Add any additional observations..."
                            ></textarea>

                        </div>

                    </div>

                    <div class="button-row">

                        <button
                            type="submit"
                            class="primary-btn"
                            id="assessButton"
                        >
                            Calculate Risk
                        </button>

                        <button
                            type="reset"
                            class="secondary-btn"
                            id="resetButton"
                        >
                            Reset
                        </button>

                    </div>

                </form>

                <div
                    id="errorBox"
                    class="error-box"
                ></div>

            </div>

            <!-- =====================================================
                 RESULT
            ====================================================== -->

            <div
                class="card"
                id="result"
            >

                <div class="result-header">

                    <div>

                        <div class="card-title">
                            Risk Assessment Result
                        </div>

                        <div class="card-description">
                            Calculated disaster risk for the selected habitation.
                        </div>

                    </div>

                </div>

                <!-- SCORE -->

                <div class="risk-score-box">

                    <div>

                        <div class="score-label">
                            OVERALL RISK SCORE
                        </div>

                        <div
                            class="score-number"
                            id="riskScore"
                        >
                            0
                        </div>

                    </div>

                    <div
                        class="risk-badge high"
                        id="riskBadge"
                    >
                        UNKNOWN
                    </div>

                </div>

                <!-- RED ZONE -->

                <div id="zoneBox"></div>

                <!-- PRIORITY -->

                <div class="priority-box">

                    Relocation Priority:

                    <span id="priority">
                        -
                    </span>

                </div>

                <!-- HAZARD FACTORS -->

                <div
                    class="card-title"
                    style="margin-top:30px;"
                >
                    Hazard Factors
                </div>

                <div class="factor-grid">

                    <!-- FLOOD -->

                    <div class="factor">

                        <div class="factor-top">

                            <span>
                                Flood Risk
                            </span>

                            <span id="resultFlood">
                                0
                            </span>

                        </div>

                        <div class="progress">

                            <div
                                class="progress-bar"
                                id="barFlood"
                                style="width:0%"
                            ></div>

                        </div>

                    </div>

                    <!-- LANDSLIDE -->

                    <div class="factor">

                        <div class="factor-top">

                            <span>
                                Landslide Risk
                            </span>

                            <span id="resultLandslide">
                                0
                            </span>

                        </div>

                        <div class="progress">

                            <div
                                class="progress-bar"
                                id="barLandslide"
                                style="width:0%"
                            ></div>

                        </div>

                    </div>

                    <!-- HISTORY -->

                    <div class="factor">

                        <div class="factor-top">

                            <span>
                                Hazard History
                            </span>

                            <span id="resultHistory">
                                0
                            </span>

                        </div>

                        <div class="progress">

                            <div
                                class="progress-bar"
                                id="barHistory"
                                style="width:0%"
                            ></div>

                        </div>

                    </div>

                    <!-- VULNERABILITY -->

                    <div class="factor">

                        <div class="factor-top">

                            <span>
                                Population Vulnerability
                            </span>

                            <span id="resultVulnerability">
                                0
                            </span>

                        </div>

                        <div class="progress">

                            <div
                                class="progress-bar"
                                id="barVulnerability"
                                style="width:0%"
                            ></div>

                        </div>

                    </div>

                </div>

                <!-- =================================================
                     NEW PLAN RELOCATION BUTTON
                ================================================== -->

                <div class="result-actions">

                    <a
                        href="#"
                        id="planRelocationButton"
                        class="plan-relocation-btn disabled"
                    >
                        Plan Relocation
                    </a>

                </div>

            </div>

        </section>

    </main>

</div>

<script>

/*
|--------------------------------------------------------------------------
| DOM ELEMENTS
|--------------------------------------------------------------------------
*/

const habitationSelect =
    document.getElementById('habitation_id');

const selectedInfo =
    document.getElementById('selectedInfo');

const floodSlider =
    document.getElementById('flood_score');

const landslideSlider =
    document.getElementById('landslide_score');

const historySlider =
    document.getElementById('hazard_history_score');

const vulnerabilitySlider =
    document.getElementById('vulnerability_score');

const floodValue =
    document.getElementById('flood_value');

const landslideValue =
    document.getElementById('landslide_value');

const historyValue =
    document.getElementById('history_value');

const vulnerabilityValue =
    document.getElementById('vulnerability_value');

const planRelocationButton =
    document.getElementById('planRelocationButton');


/*
|--------------------------------------------------------------------------
| UPDATE SLIDER DISPLAY
|--------------------------------------------------------------------------
*/

function updateSliderDisplays() {

    floodValue.textContent =
        floodSlider.value;

    landslideValue.textContent =
        landslideSlider.value;

    historyValue.textContent =
        historySlider.value;

    vulnerabilityValue.textContent =
        vulnerabilitySlider.value;

}


/*
|--------------------------------------------------------------------------
| LOAD HABITATION DATA INTO FORM
|--------------------------------------------------------------------------
*/

function loadSelectedHabitation() {

    const option =
        habitationSelect.options[
            habitationSelect.selectedIndex
        ];

    if (!option || !option.value) {

        selectedInfo.style.display = 'none';
        selectedInfo.innerHTML = '';

        floodSlider.value = 50;
        landslideSlider.value = 50;
        historySlider.value = 50;
        vulnerabilitySlider.value = 50;

        updateSliderDisplays();

        planRelocationButton.href = '#';
        planRelocationButton.classList.add('disabled');

        return;
    }

    const habitationId =
        option.value;

    const habitationName =
        option.textContent.trim();

    const population =
        Number(option.dataset.population || 0);

    const flood =
        Number(option.dataset.flood || 0);

    const landslide =
        Number(option.dataset.landslide || 0);

    const history =
        Number(option.dataset.history || 0);

    const vulnerability =
        Number(option.dataset.vulnerability || 0);


    /*
    |--------------------------------------------------------------------------
    | Fill sliders
    |--------------------------------------------------------------------------
    */

    floodSlider.value =
        flood;

    landslideSlider.value =
        landslide;

    historySlider.value =
        history;

    vulnerabilitySlider.value =
        vulnerability;

    updateSliderDisplays();


    /*
    |--------------------------------------------------------------------------
    | Selected information
    |--------------------------------------------------------------------------
    */

    selectedInfo.innerHTML =
        'Selected: <strong>' +
        escapeHtml(habitationName) +
        '</strong>' +
        ' — Population: <strong>' +
        population.toLocaleString() +
        '</strong>';

    selectedInfo.style.display =
        'block';


    /*
    |--------------------------------------------------------------------------
    | PLAN RELOCATION LINK
    |--------------------------------------------------------------------------
    */

    planRelocationButton.href =
        'relocation.php?id=' +
        encodeURIComponent(habitationId);

    planRelocationButton.classList.remove('disabled');

}


/*
|--------------------------------------------------------------------------
| HABITATION SELECTION
|--------------------------------------------------------------------------
*/

habitationSelect.addEventListener(
    'change',
    function() {

        loadSelectedHabitation();

        /*
        | If a previous result exists, hide it because the
        | selected habitation has changed.
        */

        document.getElementById('result').style.display =
            'none';

    }
);


/*
|--------------------------------------------------------------------------
| RANGE VALUE DISPLAYS
|--------------------------------------------------------------------------
*/

floodSlider.addEventListener(
    'input',
    function() {
        floodValue.textContent =
            this.value;
    }
);

landslideSlider.addEventListener(
    'input',
    function() {
        landslideValue.textContent =
            this.value;
    }
);

historySlider.addEventListener(
    'input',
    function() {
        historyValue.textContent =
            this.value;
    }
);

vulnerabilitySlider.addEventListener(
    'input',
    function() {
        vulnerabilityValue.textContent =
            this.value;
    }
);


/*
|--------------------------------------------------------------------------
| RISK ASSESSMENT
|--------------------------------------------------------------------------
*/

document
    .getElementById('riskForm')
    .addEventListener(
        'submit',
        async function(e) {

            e.preventDefault();

            const button =
                document.getElementById('assessButton');

            const errorBox =
                document.getElementById('errorBox');

            const resultBox =
                document.getElementById('result');

            errorBox.style.display =
                'none';

            const habitationId =
                habitationSelect.value;


            /*
            |--------------------------------------------------------------------------
            | Validate habitation
            |--------------------------------------------------------------------------
            */

            if (!habitationId) {

                errorBox.textContent =
                    'Please select a habitation first.';

                errorBox.style.display =
                    'block';

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Disable button
            |--------------------------------------------------------------------------
            */

            button.disabled = true;

            button.textContent =
                'Calculating...';


            /*
            |--------------------------------------------------------------------------
            | Prepare API data
            |--------------------------------------------------------------------------
            */

            const formData =
                new URLSearchParams();

            formData.append(
                'habitation_id',
                habitationId
            );

            formData.append(
                'flood_score',
                floodSlider.value
            );

            formData.append(
                'landslide_score',
                landslideSlider.value
            );

            formData.append(
                'hazard_history_score',
                historySlider.value
            );

            formData.append(
                'vulnerability_score',
                vulnerabilitySlider.value
            );

            formData.append(
                'assessment_notes',
                document.getElementById(
                    'assessment_notes'
                ).value
            );


            /*
            |--------------------------------------------------------------------------
            | CALL RISK API
            |--------------------------------------------------------------------------
            */

            try {

                const response =
                    await fetch(
                        '../api/calculate_risk.php',
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type':
                                    'application/x-www-form-urlencoded'
                            },

                            body:
                                formData.toString()
                        }
                    );


                const text =
                    await response.text();


                let data;


                try {

                    data =
                        JSON.parse(text);

                } catch (jsonError) {

                    throw new Error(
                        'Risk API returned an invalid response: ' +
                        text.substring(0, 300)
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | API ERROR
                |--------------------------------------------------------------------------
                */

                if (!data.success) {

                    throw new Error(
                        data.message ||
                        'Risk calculation failed.'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | DISPLAY RESULT
                |--------------------------------------------------------------------------
                */

                displayResult(data);


            } catch (error) {

                errorBox.textContent =
                    error.message;

                errorBox.style.display =
                    'block';

                resultBox.style.display =
                    'none';

            }


            /*
            |--------------------------------------------------------------------------
            | ENABLE BUTTON
            |--------------------------------------------------------------------------
            */

            button.disabled =
                false;

            button.textContent =
                'Calculate Risk';

        }
    );


/*
|--------------------------------------------------------------------------
| DISPLAY RESULT
|--------------------------------------------------------------------------
*/

function displayResult(data) {

    const resultBox =
        document.getElementById('result');

    resultBox.style.display =
        'block';


    /*
    |--------------------------------------------------------------------------
    | API DATA
    |--------------------------------------------------------------------------
    */

    const risk =
        data.risk || {};

    const components =
        data.components || {};


    /*
    |--------------------------------------------------------------------------
    | RISK VALUES
    |--------------------------------------------------------------------------
    */

    const score =
        parseFloat(
            risk.score ?? 0
        );

    const level =
        String(
            risk.level ?? 'UNKNOWN'
        ).toUpperCase();

    const priority =
        String(
            risk.relocation_priority ?? '-'
        ).toUpperCase();


    /*
    |--------------------------------------------------------------------------
    | SCORE
    |--------------------------------------------------------------------------
    */

    document
        .getElementById('riskScore')
        .textContent =
        score.toFixed(2);


    /*
    |--------------------------------------------------------------------------
    | RISK BADGE
    |--------------------------------------------------------------------------
    */

    const badge =
        document.getElementById(
            'riskBadge'
        );

    badge.textContent =
        level;

    badge.className =
        'risk-badge ' +
        (
            level === 'HIGH'
                ? 'high'
                : level === 'MEDIUM'
                    ? 'medium'
                    : 'low'
        );


    /*
    |--------------------------------------------------------------------------
    | RED ZONE
    |--------------------------------------------------------------------------
    */

    const zoneBox =
        document.getElementById(
            'zoneBox'
        );

    const redZone =
        Number(
            risk.red_zone ?? 0
        );


    if (
        redZone === 1 ||
        redZone === true
    ) {

        zoneBox.innerHTML = `
            <div class="red-zone">
                🔴 RED ZONE IDENTIFIED
            </div>
        `;

    } else {

        zoneBox.innerHTML = `
            <div class="safe-zone">
                🟢 RED ZONE NOT IDENTIFIED
            </div>
        `;

    }


    /*
    |--------------------------------------------------------------------------
    | PRIORITY
    |--------------------------------------------------------------------------
    */

    document
        .getElementById('priority')
        .textContent =
        priority;


    /*
    |--------------------------------------------------------------------------
    | FACTORS
    |--------------------------------------------------------------------------
    */

    updateFactor(
        'resultFlood',
        'barFlood',
        components.flood_risk
    );

    updateFactor(
        'resultLandslide',
        'barLandslide',
        components.landslide_risk
    );

    updateFactor(
        'resultHistory',
        'barHistory',
        components.hazard_history
    );

    updateFactor(
        'resultVulnerability',
        'barVulnerability',
        components.population_vulnerability
    );


    /*
    |--------------------------------------------------------------------------
    | ENABLE PLAN RELOCATION
    |--------------------------------------------------------------------------
    */

    const habitationId =
        habitationSelect.value;

    if (habitationId) {

        planRelocationButton.href =
            'relocation.php?id=' +
            encodeURIComponent(
                habitationId
            );

        planRelocationButton.classList.remove(
            'disabled'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | SCROLL TO RESULT
    |--------------------------------------------------------------------------
    */

    resultBox.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });

}


/*
|--------------------------------------------------------------------------
| UPDATE FACTOR
|--------------------------------------------------------------------------
*/

function updateFactor(
    valueId,
    barId,
    value
) {

    const number =
        parseFloat(
            value ?? 0
        );


    document
        .getElementById(valueId)
        .textContent =
        number.toFixed(0);


    document
        .getElementById(barId)
        .style.width =
        Math.min(
            Math.max(
                number,
                0
            ),
            100
        ) + '%';

}


/*
|--------------------------------------------------------------------------
| RESET
|--------------------------------------------------------------------------
*/

document
    .getElementById('resetButton')
    .addEventListener(
        'click',
        function() {

            setTimeout(
                function() {

                    document
                        .getElementById('result')
                        .style.display =
                        'none';

                    document
                        .getElementById('errorBox')
                        .style.display =
                        'none';

                    /*
                    | Reset sliders to default 50
                    */

                    floodSlider.value = 50;
                    landslideSlider.value = 50;
                    historySlider.value = 50;
                    vulnerabilitySlider.value = 50;

                    updateSliderDisplays();

                    /*
                    | Remove selected information
                    */

                    selectedInfo.style.display =
                        'none';

                    selectedInfo.innerHTML =
                        '';

                    /*
                    | Disable relocation button
                    */

                    planRelocationButton.href =
                        '#';

                    planRelocationButton.classList.add(
                        'disabled'
                    );

                },
                0
            );

        }
    );


/*
|--------------------------------------------------------------------------
| HTML ESCAPE
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

}


/*
|--------------------------------------------------------------------------
| LOAD ID FROM URL
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function() {

        /*
        | If risk_assessment.php?id=6 is opened,
        | Demo Village is automatically selected.
        */

        if (
            habitationSelect.value
        ) {

            loadSelectedHabitation();

        }

    }
);

</script>

</body>

</html>