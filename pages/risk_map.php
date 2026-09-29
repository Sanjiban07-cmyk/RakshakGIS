<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/../config/auth.php";
requireLogin();

require_once __DIR__ . "/../config/database.php";

$user = currentUser();


/*
|--------------------------------------------------------------------------
| FETCH HABITATIONS + LATEST RISK ASSESSMENT
|--------------------------------------------------------------------------
*/

$locations = [];

$sql = "
    SELECT
        h.id,
        h.name,
        h.district,
        h.state,
        h.population,
        h.latitude,
        h.longitude,

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
            ORDER BY ra2.assessed_at DESC, ra2.id DESC
            LIMIT 1
        )

    WHERE
        h.latitude IS NOT NULL
        AND h.longitude IS NOT NULL

    ORDER BY h.name ASC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $locations[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| MAP SUMMARY
|--------------------------------------------------------------------------
*/

$totalLocations = count($locations);

$highRisk = 0;
$mediumRisk = 0;
$lowRisk = 0;
$redZones = 0;
$unassessed = 0;

foreach ($locations as $location) {

    if ($location["risk_level"] === "HIGH") {
        $highRisk++;
    } elseif ($location["risk_level"] === "MEDIUM") {
        $mediumRisk++;
    } elseif ($location["risk_level"] === "LOW") {
        $lowRisk++;
    } else {
        $unassessed++;
    }

    if ((int) $location["red_zone"] === 1) {
        $redZones++;
    }
}


/*
|--------------------------------------------------------------------------
| JSON FOR JAVASCRIPT
|--------------------------------------------------------------------------
*/

$locationsJson = json_encode(
    $locations,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Risk Map | RakshakGIS</title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <!-- Leaflet -->

   <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>


    <style>

        /* =========================================
           RISK MAP
           ========================================= */

        .map-header {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 22px;

        }


        .map-heading h1 {

            font-size: 24px;

            font-weight: 800;

        }


        .map-heading p {

            margin-top: 5px;

            color: var(--muted);

            font-size: 12px;

        }


        .map-stats {

            display: grid;

            grid-template-columns:
                repeat(5, 1fr);

            gap: 12px;

            margin-bottom: 18px;

        }


        .map-stat {

            background: white;

            border: 1px solid var(--border);

            border-radius: 11px;

            padding: 15px 17px;

        }


        .map-stat-label {

            font-size: 10px;

            color: var(--muted);

        }


        .map-stat-value {

            font-size: 22px;

            font-weight: 800;

            margin-top: 5px;

        }


        .map-stat.high .map-stat-value {
            color: #dc2626;
        }


        .map-stat.medium .map-stat-value {
            color: #d97706;
        }


        .map-stat.low .map-stat-value {
            color: #16a34a;
        }


        .map-stat.red .map-stat-value {
            color: #991b1b;
        }


        .map-layout {

            display: grid;

            grid-template-columns:
                1fr 300px;

            gap: 18px;

        }


        .map-card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 12px;

            overflow: hidden;

            position: relative;

        }


        #riskMap {

            width: 100%;

            height: 650px;

        }


        .map-toolbar {

            position: absolute;

            top: 15px;

            left: 15px;

            right: 15px;

            z-index: 500;

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            pointer-events: none;

        }


        .map-filter {

            pointer-events: auto;

            background: rgba(255,255,255,0.96);

            border: 1px solid var(--border);

            border-radius: 9px;

            padding: 10px;

            box-shadow:
                0 4px 18px
                rgba(15,23,42,0.12);

        }


        .map-filter select {

            border: none;

            outline: none;

            background: transparent;

            font-family: inherit;

            font-size: 12px;

            font-weight: 600;

            color: var(--text);

        }


        .map-location-count {

            pointer-events: auto;

            background: rgba(255,255,255,0.96);

            border: 1px solid var(--border);

            border-radius: 9px;

            padding: 10px 13px;

            font-size: 11px;

            color: var(--muted);

            box-shadow:
                0 4px 18px
                rgba(15,23,42,0.12);

        }


        .map-location-count strong {

            color: var(--text);

        }


        /* =========================================
           SIDE PANEL
           ========================================= */

        .map-side {

            display: flex;

            flex-direction: column;

            gap: 15px;

        }


        .map-panel {

            background: white;

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 18px;

        }


        .map-panel h3 {

            font-size: 14px;

            font-weight: 800;

            margin-bottom: 15px;

        }


        .legend-item {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 9px 0;

            border-bottom: 1px solid #f1f5f9;

        }


        .legend-item:last-child {

            border-bottom: none;

        }


        .legend-left {

            display: flex;

            align-items: center;

            gap: 9px;

            font-size: 12px;

        }


        .legend-dot {

            width: 12px;

            height: 12px;

            border-radius: 50%;

            border: 2px solid white;

            box-shadow:
                0 0 0 1px rgba(15,23,42,0.12);

        }


        .legend-dot.high {
            background: #dc2626;
        }


        .legend-dot.medium {
            background: #f59e0b;
        }


        .legend-dot.low {
            background: #16a34a;
        }


        .legend-dot.unassessed {
            background: #64748b;
        }


        .legend-count {

            font-size: 11px;

            color: var(--muted);

            font-weight: 700;

        }


        /* =========================================
           MAP INFO
           ========================================= */

        .map-info {

            font-size: 12px;

            color: var(--muted);

            line-height: 1.7;

        }


        .map-info strong {

            color: var(--text);

        }


        .map-note {

            margin-top: 12px;

            padding: 10px;

            border-radius: 8px;

            background: #eff6ff;

            color: #1e40af;

            font-size: 10px;

            line-height: 1.5;

        }


        /* =========================================
           LEAFLET POPUP
           ========================================= */

        .leaflet-popup-content-wrapper {

            border-radius: 11px;

        }


        .popup-title {

            font-size: 15px;

            font-weight: 800;

            color: #0f172a;

            margin-bottom: 3px;

        }


        .popup-location {

            font-size: 10px;

            color: #64748b;

            margin-bottom: 10px;

        }


        .popup-row {

            display: flex;

            justify-content: space-between;

            gap: 25px;

            padding: 4px 0;

            font-size: 11px;

        }


        .popup-label {

            color: #64748b;

        }


        .popup-value {

            color: #0f172a;

            font-weight: 700;

        }


        .popup-risk {

            margin-top: 9px;

            padding-top: 9px;

            border-top: 1px solid #e2e8f0;

        }


        .popup-badge {

            display: inline-flex;

            padding: 4px 8px;

            border-radius: 999px;

            font-size: 9px;

            font-weight: 800;

        }


        .popup-high {

            background: #fee2e2;

            color: #b91c1c;

        }


        .popup-medium {

            background: #fef3c7;

            color: #b45309;

        }


        .popup-low {

            background: #dcfce7;

            color: #15803d;

        }


        .popup-none {

            background: #f1f5f9;

            color: #64748b;

        }


        .map-empty {

            height: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            color: #64748b;

            background: #f8fafc;

            text-align: center;

            padding: 30px;

        }


        @media (max-width: 1100px) {

            .map-layout {

                grid-template-columns: 1fr;

            }

            .map-side {

                display: grid;

                grid-template-columns: 1fr 1fr;

            }

        }


        @media (max-width: 850px) {

            .map-stats {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .map-header {

                flex-direction: column;

            }

            .map-side {

                grid-template-columns: 1fr;

            }

            #riskMap {

                height: 550px;

            }

        }


        @media (max-width: 600px) {

            .map-stats {

                grid-template-columns: 1fr;

            }

            #riskMap {

                height: 450px;

            }

            .map-toolbar {

                flex-direction: column;

                gap: 8px;

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
                class="nav-link active"
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


        <!-- TOPBAR -->

        <header class="topbar">

            <div>

                <div class="page-title">
                    Risk Map
                </div>

                <div class="page-subtitle">
                    Geographic view of habitation disaster risk
                </div>

            </div>


            <div class="topbar-right">

                <div class="status">

                    <span class="status-dot"></span>

                    System Operational

                </div>

            </div>

        </header>



        <!-- CONTENT -->

        <div class="content">


            <!-- HEADER -->

            <div class="map-header">

                <div class="map-heading">

                    <h1>
                        Disaster Risk Map
                    </h1>

                    <p>
                        View registered habitations and their
                        latest risk assessment geographically.
                    </p>

                </div>

            </div>



            <!-- =================================================
                 STATS
                 ================================================= -->

            <div class="map-stats">


                <div class="map-stat">

                    <div class="map-stat-label">
                        Mapped Locations
                    </div>

                    <div class="map-stat-value">
                        <?= $totalLocations ?>
                    </div>

                </div>


                <div class="map-stat high">

                    <div class="map-stat-label">
                        High Risk
                    </div>

                    <div class="map-stat-value">
                        <?= $highRisk ?>
                    </div>

                </div>


                <div class="map-stat medium">

                    <div class="map-stat-label">
                        Medium Risk
                    </div>

                    <div class="map-stat-value">
                        <?= $mediumRisk ?>
                    </div>

                </div>


                <div class="map-stat low">

                    <div class="map-stat-label">
                        Low Risk
                    </div>

                    <div class="map-stat-value">
                        <?= $lowRisk ?>
                    </div>

                </div>


                <div class="map-stat red">

                    <div class="map-stat-label">
                        Red Zones
                    </div>

                    <div class="map-stat-value">
                        <?= $redZones ?>
                    </div>

                </div>


            </div>



            <!-- =================================================
                 MAP LAYOUT
                 ================================================= -->

            <div class="map-layout">


                <!-- MAP -->

                <div class="map-card">


                    <div class="map-toolbar">


                        <div class="map-filter">

                            <select id="riskFilter">

                                <option value="ALL">
                                    Show All Locations
                                </option>

                                <option value="HIGH">
                                    High Risk
                                </option>

                                <option value="MEDIUM">
                                    Medium Risk
                                </option>

                                <option value="LOW">
                                    Low Risk
                                </option>

                                <option value="UNASSESSED">
                                    Unassessed
                                </option>

                            </select>

                        </div>


                        <div class="map-location-count">

                            Showing:
                            <strong id="visibleCount">
                                <?= $totalLocations ?>
                            </strong>

                        </div>


                    </div>


                    <div id="riskMap">

                        <?php if (empty($locations)): ?>

                            <div class="map-empty">

                                <div>

                                    <strong>
                                        No mapped habitations
                                    </strong>

                                    <br>

                                    Add latitude and longitude
                                    coordinates to your habitations.

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>


                </div>



                <!-- SIDE PANEL -->

                <aside class="map-side">


                    <!-- LEGEND -->

                    <div class="map-panel">


                        <h3>
                            Risk Legend
                        </h3>


                        <div class="legend-item">

                            <div class="legend-left">

                                <span class="legend-dot high"></span>

                                High Risk

                            </div>

                            <span class="legend-count">
                                <?= $highRisk ?>
                            </span>

                        </div>


                        <div class="legend-item">

                            <div class="legend-left">

                                <span class="legend-dot medium"></span>

                                Medium Risk

                            </div>

                            <span class="legend-count">
                                <?= $mediumRisk ?>
                            </span>

                        </div>


                        <div class="legend-item">

                            <div class="legend-left">

                                <span class="legend-dot low"></span>

                                Low Risk

                            </div>

                            <span class="legend-count">
                                <?= $lowRisk ?>
                            </span>

                        </div>


                        <div class="legend-item">

                            <div class="legend-left">

                                <span class="legend-dot unassessed"></span>

                                Unassessed

                            </div>

                            <span class="legend-count">
                                <?= $unassessed ?>
                            </span>

                        </div>


                    </div>



                    <!-- MAP INFORMATION -->

                    <div class="map-panel">


                        <h3>
                            Map Information
                        </h3>


                        <div class="map-info">

                            <strong>
                                Data source
                            </strong>

                            <br>

                            RakshakGIS database


                            <br><br>


                            <strong>
                                Coordinates
                            </strong>

                            <br>

                            Latitude / Longitude


                            <br><br>


                            <strong>
                                Assessment
                            </strong>

                            <br>

                            Latest assessment per habitation


                            <div class="map-note">

                                Markers are generated from the
                                latitude and longitude stored in
                                the habitations table.

                            </div>

                        </div>

                    </div>



                    <!-- RED ZONE -->

                    <div class="map-panel">


                        <h3>
                            Red Zone
                        </h3>


                        <div class="map-info">

                            <strong>
                                <?= $redZones ?>
                            </strong>

                            habitation(s) are currently marked
                            as red zone according to their latest
                            assessment.

                        </div>

                    </div>


                </aside>


            </div>


        </div>

    </main>

</div>



<!-- =====================================================
     LEAFLET JS
     ===================================================== -->
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>


<script>

/*
|--------------------------------------------------------------------------
| Database Locations
|--------------------------------------------------------------------------
*/

const locations =
    <?= $locationsJson ?: "[]" ?>;


/*
|--------------------------------------------------------------------------
| Create Map
|--------------------------------------------------------------------------
*/

let map;


if (locations.length > 0) {


    map = L.map("riskMap");


    /*
    |--------------------------------------------------------------------------
    | OpenStreetMap Tiles
    |--------------------------------------------------------------------------
    */

    L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            maxZoom: 19,
            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);


} else {

    /*
    |--------------------------------------------------------------------------
    | No locations
    |--------------------------------------------------------------------------
    */

    document.getElementById("riskMap").innerHTML = `
        <div class="map-empty">
            <div>
                <strong>No mapped habitations</strong>
                <br>
                Add latitude and longitude coordinates
                to your habitations.
            </div>
        </div>
    `;

}


/*
|--------------------------------------------------------------------------
| Marker Storage
|--------------------------------------------------------------------------
*/

const markers = [];


/*
|--------------------------------------------------------------------------
| Risk Color
|--------------------------------------------------------------------------
*/

function riskColor(level) {

    switch (level) {

        case "HIGH":
            return "#dc2626";

        case "MEDIUM":
            return "#f59e0b";

        case "LOW":
            return "#16a34a";

        default:
            return "#64748b";
    }

}


/*
|--------------------------------------------------------------------------
| Risk Class
|--------------------------------------------------------------------------
*/

function riskPopupClass(level) {

    switch (level) {

        case "HIGH":
            return "popup-high";

        case "MEDIUM":
            return "popup-medium";

        case "LOW":
            return "popup-low";

        default:
            return "popup-none";
    }

}


/*
|--------------------------------------------------------------------------
| Create Popup
|--------------------------------------------------------------------------
*/

function createPopup(location) {

    const riskLevel =
        location.risk_level || "UNASSESSED";


    const score =
        location.risk_score !== null
            ? Number(location.risk_score).toFixed(1)
            : "—";


    const redZone =
        Number(location.red_zone) === 1
            ? "YES"
            : "NO";


    const priority =
        location.relocation_priority || "—";


    return `

        <div>

            <div class="popup-title">

                ${escapeHtml(location.name)}

            </div>


            <div class="popup-location">

                ${escapeHtml(location.district)},
                ${escapeHtml(location.state)}

            </div>


            <div class="popup-row">

                <span class="popup-label">
                    Population
                </span>

                <span class="popup-value">
                    ${Number(location.population).toLocaleString()}
                </span>

            </div>


            <div class="popup-row">

                <span class="popup-label">
                    Risk Score
                </span>

                <span class="popup-value">
                    ${score}
                </span>

            </div>


            <div class="popup-row">

                <span class="popup-label">
                    Red Zone
                </span>

                <span class="popup-value">
                    ${redZone}
                </span>

            </div>


            <div class="popup-row">

                <span class="popup-label">
                    Priority
                </span>

                <span class="popup-value">
                    ${escapeHtml(priority)}
                </span>

            </div>


            <div class="popup-risk">

                <span
                    class="popup-badge ${riskPopupClass(riskLevel)}"
                >

                    ${escapeHtml(riskLevel)}

                </span>

            </div>

        </div>

    `;

}


/*
|--------------------------------------------------------------------------
| Escape HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {

    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

}


/*
|--------------------------------------------------------------------------
| Add Markers
|--------------------------------------------------------------------------
*/

if (locations.length > 0) {


    const bounds = [];


    locations.forEach(function(location) {


        const latitude =
            Number(location.latitude);


        const longitude =
            Number(location.longitude);


        if (
            !Number.isFinite(latitude) ||
            !Number.isFinite(longitude)
        ) {

            return;

        }


        const riskLevel =
            location.risk_level || "UNASSESSED";


        const marker = L.circleMarker(
            [latitude, longitude],
            {

                radius:
                    riskLevel === "HIGH"
                        ? 10
                        : 8,

                fillColor:
                    riskColor(riskLevel),

                color: "#ffffff",

                weight: 2,

                opacity: 1,

                fillOpacity: 0.9

            }
        );


        marker.bindPopup(
            createPopup(location)
        );


        marker.addTo(map);


        markers.push({

            marker: marker,

            data: location

        });


        bounds.push([
            latitude,
            longitude
        ]);

    });


    /*
    |--------------------------------------------------------------------------
    | Fit Map To Locations
    |--------------------------------------------------------------------------
    */

    if (bounds.length === 1) {

        map.setView(
            bounds[0],
            12
        );

    } else if (bounds.length > 1) {

        map.fitBounds(
            bounds,
            {
                padding: [40, 40]
            }
        );

    }

}


/*
|--------------------------------------------------------------------------
| Filter
|--------------------------------------------------------------------------
*/

const riskFilter =
    document.getElementById("riskFilter");


const visibleCount =
    document.getElementById("visibleCount");


riskFilter.addEventListener(
    "change",
    function() {


        const selected =
            this.value;


        let count = 0;


        markers.forEach(function(item) {


            const risk =
                item.data.risk_level
                || "UNASSESSED";


            const show =
                selected === "ALL"
                || (
                    selected === "UNASSESSED"
                    && !item.data.risk_level
                )
                || risk === selected;


            if (show) {

                item.marker.addTo(map);

                count++;

            } else {

                map.removeLayer(
                    item.marker
                );

            }

        });


        visibleCount.textContent =
            count;

    }
);

</script>


</body>

</html>