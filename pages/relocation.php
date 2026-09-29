<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$currentPage = 'relocation';

require_once __DIR__ . '/../config/database.php';


/* =========================================================
   GET HABITATION ID
========================================================= */

$habitationId = isset($_GET['id']) && is_numeric($_GET['id'])
    ? (int) $_GET['id']
    : 0;


/* =========================================================
   CREATE RELOCATION PLAN
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postHabitationId = isset($_POST['habitation_id']) && is_numeric($_POST['habitation_id'])
        ? (int) $_POST['habitation_id']
        : 0;

    $siteId = isset($_POST['relocation_site_id']) && is_numeric($_POST['relocation_site_id'])
        ? (int) $_POST['relocation_site_id']
        : 0;


    /* ---------- Validate input ---------- */

    if ($postHabitationId <= 0 || $siteId <= 0) {

        header(
            "Location: relocation.php?id=" .
            $postHabitationId .
            "&error=invalid"
        );

        exit;
    }


    /* =====================================================
       GET HABITATION
    ===================================================== */

    $habitationStmt = $conn->prepare("
        SELECT
            id,
            name,
            population,
            latitude,
            longitude
        FROM habitations
        WHERE id = ?
        LIMIT 1
    ");

    if (!$habitationStmt) {

        header(
            "Location: relocation.php?id=" .
            $postHabitationId .
            "&error=save"
        );

        exit;
    }

    $habitationStmt->bind_param(
        "i",
        $postHabitationId
    );

    $habitationStmt->execute();

    $habitationResult = $habitationStmt->get_result();

    $habitation = $habitationResult->fetch_assoc();

    $habitationStmt->close();


    if (!$habitation) {

        header(
            "Location: relocation.php?id=" .
            $postHabitationId .
            "&error=habitation"
        );

        exit;
    }


    /* =====================================================
       GET LATEST RISK ASSESSMENT
    ===================================================== */

    $riskStmt = $conn->prepare("
        SELECT
            risk_level,
            relocation_priority
        FROM risk_assessments
        WHERE habitation_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    if (!$riskStmt) {

        header(
            "Location: relocation.php?id=" .
            $postHabitationId .
            "&error=no_risk"
        );

        exit;
    }

    $riskStmt->bind_param(
        "i",
        $postHabitationId
    );

    $riskStmt->execute();

    $riskResult = $riskStmt->get_result();

    $risk = $riskResult->fetch_assoc();

    $riskStmt->close();


    if (!$risk) {

        header(
            "Location: relocation.php?id=" .
            $postHabitationId .
            "&error=no_risk"
        );

        exit;
    }


    /* =====================================================
       GET SELECTED RELOCATION SITE
    ===================================================== */

    $siteStmt = $conn->prepare("
        SELECT
            id,
            site_name,
            latitude,
            longitude,
            total_capacity,
            occupied_capacity,
            safety_level
        FROM relocation_sites
        WHERE id = ?
        LIMIT 1
    ");

    if (!$siteStmt) {

        header(
            "Location: relocation.php?id=" .
            $postHabitationId .
            "&error=site"
        );

        exit;
    }

    $siteStmt->bind_param(
        "i",
        $siteId
    );

    $siteStmt->execute();

    $siteResult = $siteStmt->get_result();

    $site = $siteResult->fetch_assoc();

    $siteStmt->close();


    if (!$site) {

        header(
            "Location: relocation.php?id=" .
            $postHabitationId .
            "&error=site"
        );

        exit;
    }


    /* =====================================================
       CHECK AVAILABLE CAPACITY
    ===================================================== */

    $totalCapacity = (int) $site['total_capacity'];

    $occupiedCapacity = (int) $site['occupied_capacity'];

    $availableCapacity =
        $totalCapacity - $occupiedCapacity;

    $population =
        (int) $habitation['population'];


    if ($availableCapacity < $population) {

        header(
            "Location: relocation.php?id=" .
            $postHabitationId .
            "&error=capacity"
        );

        exit;
    }


    /* =====================================================
       CALCULATE DISTANCE
    ===================================================== */

    $distanceKm = null;

    if (
        $habitation['latitude'] !== null &&
        $habitation['longitude'] !== null &&
        $site['latitude'] !== null &&
        $site['longitude'] !== null
    ) {

        $earthRadius = 6371;

        $lat1 =
            deg2rad((float) $habitation['latitude']);

        $lon1 =
            deg2rad((float) $habitation['longitude']);

        $lat2 =
            deg2rad((float) $site['latitude']);

        $lon2 =
            deg2rad((float) $site['longitude']);

        $latDifference =
            $lat2 - $lat1;

        $lonDifference =
            $lon2 - $lon1;

        $a =
            sin($latDifference / 2)
            *
            sin($latDifference / 2)
            +
            cos($lat1)
            *
            cos($lat2)
            *
            sin($lonDifference / 2)
            *
            sin($lonDifference / 2);

        $c =
            2 *
            atan2(
                sqrt($a),
                sqrt(1 - $a)
            );

        $distanceKm =
            round(
                $earthRadius * $c,
                2
            );
    }


    /* =====================================================
       PREVENT DUPLICATE PLANNED PLAN
    ===================================================== */

    $duplicateStmt = $conn->prepare("
        SELECT id
        FROM relocation_plans
        WHERE habitation_id = ?
          AND status = 'PLANNED'
        LIMIT 1
    ");

    if ($duplicateStmt) {

        $duplicateStmt->bind_param(
            "i",
            $postHabitationId
        );

        $duplicateStmt->execute();

        $duplicateResult =
            $duplicateStmt->get_result();

        $existingPlan =
            $duplicateResult->fetch_assoc();

        $duplicateStmt->close();


        if ($existingPlan) {

            header(
                "Location: relocation_details.php?id=" .
                (int) $existingPlan['id']
            );

            exit;
        }
    }


    /* =====================================================
       RECOMMENDATION REASON
    ===================================================== */

    $recommendationReason =
        "Selected based on available capacity";

    if ($distanceKm !== null) {

        $recommendationReason .=
            ", calculated distance of " .
            number_format(
                $distanceKm,
                2
            ) .
            " km";
    }

    $recommendationReason .=
        ", safety level " .
        strtoupper($site['safety_level']) .
        ", and suitability for " .
        number_format($population) .
        " people.";


    /* =====================================================
       INSERT RELOCATION PLAN
    ===================================================== */

    $status = 'PLANNED';


    $insertStmt = $conn->prepare("
        INSERT INTO relocation_plans
        (
            habitation_id,
            relocation_site_id,
            population_to_relocate,
            available_capacity,
            distance_km,
            recommendation_reason,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");


    if (!$insertStmt) {

        header(
            "Location: relocation.php?id=" .
            $postHabitationId .
            "&error=save"
        );

        exit;
    }


    $insertStmt->bind_param(
        "iiiidss",
        $postHabitationId,
        $siteId,
        $population,
        $availableCapacity,
        $distanceKm,
        $recommendationReason,
        $status
    );


    if (!$insertStmt->execute()) {

        $insertStmt->close();

        header(
            "Location: relocation.php?id=" .
            $postHabitationId .
            "&error=save"
        );

        exit;
    }


    $planId =
        $insertStmt->insert_id;

    $insertStmt->close();


    /* =====================================================
       SUCCESS
    ===================================================== */

    header(
        "Location: relocation_details.php?id=" .
        $planId
    );

    exit;
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
        Relocation Planner | RakshakGIS
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .relocation-grid {

            display: grid;

            grid-template-columns:
                1fr 1.5fr;

            gap: 20px;

            margin-top: 20px;

        }


        .info-box {

            padding: 20px;

            background: #f8fafc;

            border:
                1px solid #e2e8f0;

            border-radius: 12px;

            margin-bottom: 15px;

        }


        .info-label {

            font-size: 12px;

            color: #64748b;

            text-transform: uppercase;

            margin-bottom: 6px;

        }


        .info-value {

            font-size: 20px;

            font-weight: 700;

            color: #0f172a;

        }


        .site-card {

            border:
                1px solid #e2e8f0;

            border-radius: 12px;

            padding: 20px;

            margin-bottom: 15px;

            background: #fff;

        }


        .site-card.recommended {

            border:
                2px solid #2563eb;

            background: #f8fbff;

        }


        .site-header {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            margin-bottom: 15px;

        }


        .site-name {

            font-size: 18px;

            font-weight: 700;

            color: #0f172a;

        }


        .recommended-badge {

            background: #dbeafe;

            color: #1d4ed8;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 700;

        }


        .site-stats {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 12px;

        }


        .site-stat {

            background: #f8fafc;

            padding: 12px;

            border-radius: 8px;

        }


        .site-stat-label {

            font-size: 11px;

            color: #64748b;

        }


        .site-stat-value {

            margin-top: 4px;

            font-weight: 700;

            color: #0f172a;

        }


        .plan-button {

            margin-top: 18px;

            width: 100%;

            border: none;

            background: #2563eb;

            color: white;

            padding: 12px 16px;

            border-radius: 8px;

            font-size: 14px;

            font-weight: 700;

            cursor: pointer;

        }


        .plan-button:hover {

            background: #1d4ed8;

        }


        .plan-button.secondary {

            background: #eff6ff;

            color: #1d4ed8;

            border:
                1px solid #bfdbfe;

        }


        .plan-button.secondary:hover {

            background: #dbeafe;

        }


        .success-box {

            background: #dcfce7;

            border:
                1px solid #86efac;

            color: #166534;

            padding: 16px;

            border-radius: 10px;

            margin-bottom: 20px;

        }


        .error-box {

            background: #fee2e2;

            border:
                1px solid #fecaca;

            color: #991b1b;

            padding: 16px;

            border-radius: 10px;

            margin-bottom: 20px;

        }


        .loading {

            text-align: center;

            padding: 40px;

            color: #64748b;

        }


        @media (max-width: 900px) {

            .relocation-grid {

                grid-template-columns: 1fr;

            }

            .site-stats {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>


<body>

<div class="app">


    <!-- SIDEBAR -->

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
                href="<?php
                    echo $habitationId > 0
                        ? 'relocation.php?id=' . $habitationId
                        : 'habitations.php';
                ?>"
                class="nav-link active"
            >
                <span class="nav-icon">⌖</span>
                Relocation Planner
            </a>


            <a
                href="relocation_details.php"
                class="nav-link"
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


    <!-- MAIN -->

    <main class="main">


        <!-- TOPBAR -->

        <header class="topbar">

            <div>

                <div class="page-title">
                    Relocation Planner
                </div>

                <div class="page-subtitle">
                    Find suitable safe relocation sites
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


            <div class="page-header">

                <div>

                    <h1>
                        Relocation Planner
                    </h1>

                    <p>
                        Identify suitable relocation sites based on
                        population, safety, capacity and distance.
                    </p>

                </div>

            </div>


            <div id="message"></div>


            <div class="relocation-grid">


                <!-- LEFT -->

                <div>

                    <div class="card">

                        <div class="card-title">
                            Habitation Details
                        </div>

                        <div class="card-subtitle">
                            Relocation requirement
                        </div>


                        <div style="margin-top:20px;">


                            <div class="info-box">

                                <div class="info-label">
                                    Habitation
                                </div>

                                <div
                                    class="info-value"
                                    id="habitationName"
                                >
                                    Loading...
                                </div>

                            </div>


                            <div class="info-box">

                                <div class="info-label">
                                    Population
                                </div>

                                <div
                                    class="info-value"
                                    id="population"
                                >
                                    -
                                </div>

                            </div>


                            <div class="info-box">

                                <div class="info-label">
                                    Risk Level
                                </div>

                                <div
                                    class="info-value"
                                    id="riskLevel"
                                >
                                    -
                                </div>

                            </div>


                            <div class="info-box">

                                <div class="info-label">
                                    Relocation Priority
                                </div>

                                <div
                                    class="info-value"
                                    id="priority"
                                >
                                    -
                                </div>

                            </div>


                        </div>

                    </div>

                </div>


                <!-- RIGHT -->

                <div>

                    <div class="card">

                        <div class="card-title">
                            Recommended Relocation Sites
                        </div>

                        <div class="card-subtitle">
                            Select a suitable site to create a relocation plan
                        </div>


                        <div
                            id="sitesContainer"
                            style="margin-top:20px;"
                        >

                            <div class="loading">
                                Finding suitable relocation sites...
                            </div>

                        </div>

                    </div>

                </div>


            </div>

        </section>

    </main>

</div>


<script>


const habitationId =
    <?php echo $habitationId; ?>;


/* =========================================================
   PAGE LOAD
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        if (
            !habitationId ||
            habitationId <= 0
        ) {

            showError(
                "No habitation selected. Please open relocation planner from a habitation."
            );

            return;
        }

        loadRelocationData();

    }
);


/* =========================================================
   LOAD RELOCATION DATA
========================================================= */

async function loadRelocationData() {

    const habitationName =
        document.getElementById(
            "habitationName"
        );

    const population =
        document.getElementById(
            "population"
        );

    const riskLevel =
        document.getElementById(
            "riskLevel"
        );

    const priority =
        document.getElementById(
            "priority"
        );


    try {


        /* =================================================
           STEP 1: LOAD HABITATION
        ================================================= */

        const habitationResponse =
            await fetch(
                "../api/get_habitation.php?id=" +
                encodeURIComponent(habitationId),
                {
                    cache: "no-store"
                }
            );


        if (!habitationResponse.ok) {

            throw new Error(
                "Habitation API returned HTTP " +
                habitationResponse.status
            );

        }


        const habitationResult =
            await habitationResponse.json();


        if (!habitationResult.success) {

            throw new Error(
                habitationResult.message ||
                "Failed to load habitation"
            );

        }


        const habitation =
            habitationResult.data;


        habitationName.textContent =
            habitation.name || "Unknown";


        population.textContent =
            Number(
                habitation.population || 0
            ).toLocaleString();


        /* =================================================
           STEP 2: LOAD RISK ASSESSMENT
        ================================================= */

        const riskResponse =
            await fetch(
                "../api/get_risk.php?habitation_id=" +
                encodeURIComponent(habitationId),
                {
                    cache: "no-store"
                }
            );


        if (riskResponse.ok) {

            const riskResult =
                await riskResponse.json();


            if (
                riskResult.success &&
                riskResult.data
            ) {

                const risk =
                    riskResult.data;


                riskLevel.textContent =
                    risk.risk_level ||
                    "NOT ASSESSED";


                priority.textContent =
                    risk.relocation_priority ||
                    "—";

            } else {

                riskLevel.textContent =
                    "NOT ASSESSED";

                priority.textContent =
                    "—";
            }

        } else {

            riskLevel.textContent =
                "NOT ASSESSED";

            priority.textContent =
                "—";

        }


        /* =================================================
           STEP 3: LOAD SAFE RELOCATION SITES
        ================================================= */

        const sitesResponse =
            await fetch(
                "../api/get_safe_sites.php?habitation_id=" +
                encodeURIComponent(habitationId),
                {
                    cache: "no-store"
                }
            );


        if (!sitesResponse.ok) {

            throw new Error(
                "Safe Sites API returned HTTP " +
                sitesResponse.status
            );

        }


        const sitesResult =
            await sitesResponse.json();


        if (!sitesResult.success) {

            throw new Error(
                sitesResult.message ||
                "Failed to load safe relocation sites"
            );

        }


        renderSites(
            sitesResult.data || []
        );


    }
    catch (error) {

        console.error(
            "Relocation Planner Error:",
            error
        );


        showError(
            error.message ||
            "Unable to load relocation data."
        );

    }

}


/* =========================================================
   RENDER RELOCATION SITES
========================================================= */

function renderSites(sites) {

    const container =
        document.getElementById(
            "sitesContainer"
        );


    if (!container) {
        return;
    }


    if (
        !sites ||
        sites.length === 0
    ) {

        container.innerHTML = `

            <div class="error-box">

                No suitable relocation sites
                are currently available.

            </div>

        `;

        return;
    }


    const populationElement =
        document.getElementById(
            "population"
        );


    const habitationPopulation =
        populationElement
            ? Number(
                populationElement.textContent
                    .replace(/,/g, "")
              ) || 0
            : 0;


    container.innerHTML =

        sites.map(
            (site, index) => {


                const totalCapacity =
                    Number(
                        site.total_capacity || 0
                    );


                const occupiedCapacity =
                    Number(
                        site.occupied_capacity || 0
                    );


                const availableCapacity =
                    Number(
                        site.available_capacity ??
                        (
                            totalCapacity -
                            occupiedCapacity
                        )
                    );


                const distance =
                    site.distance_from_habitation !== null &&
                    site.distance_from_habitation !== undefined &&
                    site.distance_from_habitation !== ""

                    ?

                    Number(
                        site.distance_from_habitation
                    ).toFixed(2) + " km"

                    :

                    "—";


                const safetyLevel =
                    site.safety_level ||
                    "LOW";


                const status =
                    availableCapacity >=
                    habitationPopulation

                        ? "AVAILABLE"

                        : "INSUFFICIENT CAPACITY";


                const canAccommodate =
                    availableCapacity >=
                    habitationPopulation;


                return `

                    <div
                        class="site-card ${
                            index === 0
                                ? "recommended"
                                : ""
                        }"
                    >


                        <div class="site-header">

                            <div class="site-name">

                                ${escapeHtml(
                                    site.site_name ||
                                    "Relocation Site"
                                )}

                            </div>


                            ${
                                index === 0

                                ?

                                `

                                    <span
                                        class="recommended-badge"
                                    >
                                        RECOMMENDED
                                    </span>

                                `

                                :

                                ""
                            }

                        </div>


                        <div class="site-stats">


                            <div class="site-stat">

                                <div class="site-stat-label">
                                    Total Capacity
                                </div>

                                <div class="site-stat-value">
                                    ${totalCapacity.toLocaleString()}
                                </div>

                            </div>


                            <div class="site-stat">

                                <div class="site-stat-label">
                                    Occupied
                                </div>

                                <div class="site-stat-value">
                                    ${occupiedCapacity.toLocaleString()}
                                </div>

                            </div>


                            <div class="site-stat">

                                <div class="site-stat-label">
                                    Available
                                </div>

                                <div class="site-stat-value">
                                    ${availableCapacity.toLocaleString()}
                                </div>

                            </div>


                            <div class="site-stat">

                                <div class="site-stat-label">
                                    Distance
                                </div>

                                <div class="site-stat-value">
                                    ${distance}
                                </div>

                            </div>


                            <div class="site-stat">

                                <div class="site-stat-label">
                                    Safety Level
                                </div>

                                <div class="site-stat-value">
                                    ${escapeHtml(
                                        safetyLevel
                                    )}
                                </div>

                            </div>


                            <div class="site-stat">

                                <div class="site-stat-label">
                                    Status
                                </div>

                                <div class="site-stat-value">
                                    ${status}
                                </div>

                            </div>


                        </div>


                        ${
                            canAccommodate

                            ?

                            `

                                <form
                                    method="POST"
                                    onsubmit="return confirmPlan(
                                        '${escapeHtml(
                                            site.site_name ||
                                            "this site"
                                        )}'
                                    );"
                                >


                                    <input
                                        type="hidden"
                                        name="habitation_id"
                                        value="${habitationId}"
                                    >


                                    <input
                                        type="hidden"
                                        name="relocation_site_id"
                                        value="${Number(site.id)}"
                                    >


                                    <button
                                        type="submit"
                                        class="plan-button ${
                                            index === 0
                                                ? ""
                                                : "secondary"
                                        }"
                                    >

                                        ${
                                            index === 0
                                                ? "Create Relocation Plan"
                                                : "Select This Site"
                                        }

                                    </button>

                                </form>

                            `

                            :

                            `

                                <button
                                    type="button"
                                    class="plan-button secondary"
                                    disabled
                                    style="
                                        cursor:not-allowed;
                                        opacity:0.6;
                                    "
                                >
                                    Insufficient Capacity
                                </button>

                            `
                        }


                    </div>

                `;

            }

        ).join("");

}


/* =========================================================
   CONFIRM PLAN
========================================================= */

function confirmPlan(siteName) {

    return confirm(
        "Create relocation plan for this habitation using " +
        siteName +
        "?"
    );

}


/* =========================================================
   ERROR MESSAGE
========================================================= */

function showError(message) {

    const messageBox =
        document.getElementById(
            "message"
        );


    if (!messageBox) {
        return;
    }


    messageBox.innerHTML = `

        <div class="error-box">

            ${escapeHtml(message)}

        </div>

    `;

}


/* =========================================================
   URL ERROR MESSAGE
========================================================= */

const urlParams =
    new URLSearchParams(
        window.location.search
    );


const error =
    urlParams.get("error");


if (error) {

    let message =
        "Something went wrong.";


    if (error === "invalid") {

        message =
            "Invalid relocation information.";

    }

    else if (error === "habitation") {

        message =
            "Habitation not found.";

    }

    else if (error === "no_risk") {

        message =
            "Please complete a risk assessment before creating a relocation plan.";

    }

    else if (error === "site") {

        message =
            "Selected relocation site was not found.";

    }

    else if (error === "capacity") {

        message =
            "The selected relocation site does not have enough available capacity.";

    }

    else if (error === "save") {

        message =
            "Unable to save the relocation plan.";

    }


    document.addEventListener(
        "DOMContentLoaded",
        function () {

            showError(message);

        }
    );

}


/* =========================================================
   HTML ESCAPE
========================================================= */

function escapeHtml(value) {

    const div =
        document.createElement(
            "div"
        );

    div.textContent =
        value ?? "";

    return div.innerHTML;

}

</script>


</body>

</html>