<?php

$currentPage = 'map';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Interactive Map | RakshakGIS</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Leaflet CSS -->
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>

        .map-page {
            padding: 30px;
        }

        .map-header {
            margin-bottom: 20px;
        }

        .map-header h1 {
            margin-bottom: 5px;
        }

        .map-header p {
            color: #64748b;
            margin: 0;
        }

        .map-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
        }

        .map-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
        }

        #riskMap {
            width: 100%;
            height: 620px;
        }

        .map-side {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 22px;
        }

        .map-side h3 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #334155;
        }

        .legend-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot-high {
            background: #dc2626;
        }

        .dot-medium {
            background: #f59e0b;
        }

        .dot-low {
            background: #16a34a;
        }

        .dot-site {
            background: #2563eb;
        }

        .map-stat {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .map-stat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 13px;
        }

        .map-stat-label {
            color: #64748b;
        }

        .map-stat-value {
            font-weight: 700;
        }

        .map-loading {
            padding: 30px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 900px) {

            .map-layout {
                grid-template-columns: 1fr;
            }

            #riskMap {
                height: 500px;
            }

        }

    </style>

</head>


<body>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">

            <div class="logo-icon">R</div>

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


            <a href="risk_assessment.php" class="nav-link">
                <span class="nav-icon">⚠</span>
                Risk Assessment
            </a>


            <a href="map.php" class="nav-link active">
                <span class="nav-icon">●</span>
                Interactive Map
            </a>


            <a href="relocation.php" class="nav-link">
                <span class="nav-icon">⌖</span>
                Relocation Planner
            </a>


            <a href="relocation_details.php" class="nav-link">
                <span class="nav-icon">▣</span>
                Relocation Plans
            </a>


            <a href="reports.php" class="nav-link">
                <span class="nav-icon">▤</span>
                Reports
            </a>


            <div class="nav-section">
                Information
            </div>


            <a href="about.php" class="nav-link">
                <span class="nav-icon">ⓘ</span>
                About
            </a>


            <a href="contact.php" class="nav-link">
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
                    Interactive Map
                </div>

                <div class="page-subtitle">
                    Visualize habitation risk and safe relocation sites
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
        <section class="map-page">


            <div class="map-header">

                <h1>
                    Risk & Relocation Map
                </h1>

                <p>
                    Geographic view of registered habitations and available relocation sites.
                </p>

            </div>


            <div class="map-layout">


                <!-- MAP -->
                <div class="map-card">

                    <div id="riskMap">

                        <div class="map-loading">
                            Loading map...
                        </div>

                    </div>

                </div>


                <!-- MAP LEGEND -->
                <div class="map-side">

                    <h3>
                        Map Legend
                    </h3>


                    <div class="legend-item">

                        <span class="legend-dot dot-high"></span>

                        High Risk / Red Zone

                    </div>


                    <div class="legend-item">

                        <span class="legend-dot dot-medium"></span>

                        Medium Risk

                    </div>


                    <div class="legend-item">

                        <span class="legend-dot dot-low"></span>

                        Low Risk

                    </div>


                    <div class="legend-item">

                        <span class="legend-dot dot-site"></span>

                        Safe Relocation Site

                    </div>


                    <div class="map-stat">

                        <div class="map-stat-row">

                            <span class="map-stat-label">
                                Total Habitations
                            </span>

                            <span
                                class="map-stat-value"
                                id="totalHabitations"
                            >
                                —
                            </span>

                        </div>


                        <div class="map-stat-row">

                            <span class="map-stat-label">
                                High Risk
                            </span>

                            <span
                                class="map-stat-value"
                                id="highRisk"
                            >
                                —
                            </span>

                        </div>


                        <div class="map-stat-row">

                            <span class="map-stat-label">
                                Medium Risk
                            </span>

                            <span
                                class="map-stat-value"
                                id="mediumRisk"
                            >
                                —
                            </span>

                        </div>


                        <div class="map-stat-row">

                            <span class="map-stat-label">
                                Low Risk
                            </span>

                            <span
                                class="map-stat-value"
                                id="lowRisk"
                            >
                                —
                            </span>

                        </div>


                        <div class="map-stat-row">

                            <span class="map-stat-label">
                                Relocation Sites
                            </span>

                            <span
                                class="map-stat-value"
                                id="totalSites"
                            >
                                —
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </section>

    </main>

</div>


<!-- Leaflet JS -->
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
</script>


<script>

document.addEventListener("DOMContentLoaded", function () {

    initializeMap();

});


let map;


function initializeMap() {

    /*
     * Default center:
     * Maharashtra
     */
    map = L.map("riskMap").setView(
        [19.7515, 75.7139],
        7
    );


    /*
     * OpenStreetMap
     */
    L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            maxZoom: 19,
            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);


    loadMapData();

}


function loadMapData() {

    fetch("../api/get_map_data.php")

        .then(response => {

            if (!response.ok) {
                throw new Error("Map API request failed");
            }

            return response.json();

        })

        .then(data => {

            if (!data.success) {

                console.error(data.message);

                return;

            }

            renderMapData(data);

        })

        .catch(error => {

            console.error(
                "Map API Error:",
                error
            );

        });

}


function renderMapData(data) {

    const habitations =
        data.habitations || [];

    const sites =
        data.relocation_sites || [];


    let high = 0;
    let medium = 0;
    let low = 0;


    /*
     * HABITATION MARKERS
     */

    habitations.forEach(habitation => {

        const latitude =
            parseFloat(habitation.latitude);

        const longitude =
            parseFloat(habitation.longitude);


        if (
            Number.isNaN(latitude) ||
            Number.isNaN(longitude)
        ) {
            return;
        }


        const risk =
            (
                habitation.risk_level ||
                "NOT ASSESSED"
            ).toUpperCase();


        let markerColor =
            "#64748b";


        if (risk === "HIGH") {

            markerColor = "#dc2626";

            high++;

        }
        else if (risk === "MEDIUM") {

            markerColor = "#f59e0b";

            medium++;

        }
        else if (risk === "LOW") {

            markerColor = "#16a34a";

            low++;

        }


        const marker =
            L.circleMarker(
                [latitude, longitude],
                {
                    radius: 9,
                    fillColor: markerColor,
                    color: "#ffffff",
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.9
                }
            );


        marker.bindPopup(`

            <div style="min-width:220px">

                <h3 style="margin-top:0">
                    ${escapeHtml(habitation.name)}
                </h3>

                <p>
                    <strong>District:</strong>
                    ${escapeHtml(
                        habitation.district || "—"
                    )}
                </p>

                <p>
                    <strong>Population:</strong>
                    ${Number(
                        habitation.population || 0
                    ).toLocaleString()}
                </p>

                <p>
                    <strong>Risk:</strong>
                    ${escapeHtml(risk)}
                </p>

                ${
                    habitation.risk_score
                    ?
                    `<p>
                        <strong>Risk Score:</strong>
                        ${escapeHtml(
                            habitation.risk_score
                        )}
                    </p>`
                    :
                    ""
                }
                            ${
            habitation.relocation_priority
                ? `
                    <p>
                        <strong>Relocation Priority:</strong>
                        ${escapeHtml(
                            habitation.relocation_priority
                        )}
                    </p>
                  `
                : ""
        }

            </div>

        `);


        marker.addTo(map);

    });


    /*
     * SAFE RELOCATION SITE MARKERS
     */

    sites.forEach(site => {

        const latitude =
            parseFloat(site.latitude);

        const longitude =
            parseFloat(site.longitude);


        if (
            Number.isNaN(latitude) ||
            Number.isNaN(longitude)
        ) {
            return;
        }


        const available =
            Number(
                site.available_capacity || 0
            );


        const marker =
            L.circleMarker(
                [latitude, longitude],
                {
                    radius: 8,
                    fillColor: "#2563eb",
                    color: "#ffffff",
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.9
                }
            );


        marker.bindPopup(`

            <div style="min-width:220px">

                <h3 style="margin-top:0">
                    ${escapeHtml(
                        site.site_name || "Relocation Site"
                    )}
                </h3>

                <p>
                    <strong>District:</strong>
                    ${escapeHtml(
                        site.district || "—"
                    )}
                </p>

                <p>
                    <strong>Total Capacity:</strong>
                    ${Number(
                        site.total_capacity || 0
                    ).toLocaleString()}
                </p>

                <p>
                    <strong>Available:</strong>
                    ${available.toLocaleString()}
                </p>

                <p>
                    <strong>Safety:</strong>
                    ${escapeHtml(
                        site.safety_level || "—"
                    )}
                </p>

            </div>

        `);


        marker.addTo(map);

    });


    /*
     * UPDATE STATISTICS
     */

    document.getElementById(
        "totalHabitations"
    ).textContent =
        habitations.length;


    document.getElementById(
        "highRisk"
    ).textContent =
        high;


    document.getElementById(
        "mediumRisk"
    ).textContent =
        medium;


    document.getElementById(
        "lowRisk"
    ).textContent =
        low;


    document.getElementById(
        "totalSites"
    ).textContent =
        sites.length;


    /*
     * Automatically fit map to available markers
     */

    const allLayers = [];


    map.eachLayer(layer => {

        if (
            layer instanceof L.CircleMarker
        ) {
            allLayers.push(layer);
        }

    });


    if (allLayers.length > 0) {

        const group =
            L.featureGroup(allLayers);

        map.fitBounds(
            group.getBounds().pad(0.2)
        );

    }

}


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