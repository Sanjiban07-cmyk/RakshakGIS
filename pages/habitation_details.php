<?php

$currentPage = 'habitations';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Habitation Details | RakshakGIS</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>

        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .info-item {
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .info-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .risk-score {
            font-size: 48px;
            font-weight: 800;
            color: #dc2626;
            margin: 10px 0;
        }

        .risk-card {
            text-align: center;
            padding: 25px;
        }

        .risk-level {
            display: inline-block;
            padding: 7px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
        }

        .risk-high {
            background: #fee2e2;
            color: #b91c1c;
        }

        .risk-medium {
            background: #fef3c7;
            color: #b45309;
        }

        .risk-low {
            background: #dcfce7;
            color: #15803d;
        }

        .risk-none {
            background: #e2e8f0;
            color: #475569;
        }

        .factor {
            margin-bottom: 18px;
        }

        .factor-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 600;
        }

        .progress {
            height: 9px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: #2563eb;
            border-radius: 10px;
        }

        .action-row {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .red-zone {
            margin-top: 20px;
            padding: 16px;
            border-radius: 10px;
            background: #fee2e2;
            color: #991b1b;
            font-weight: 700;
        }

        .safe-zone {
            margin-top: 20px;
            padding: 16px;
            border-radius: 10px;
            background: #dcfce7;
            color: #166534;
            font-weight: 700;
        }

        .loading {
            padding: 40px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 900px) {

            .details-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
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


            <a href="habitations.php" class="nav-link active">
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


            <a href="map.php" class="nav-link">
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
                    Habitation Details
                </div>

                <div class="page-subtitle">
                    Disaster risk and relocation information
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

                    <h1 id="habitationName">
                        Loading...
                    </h1>

                    <p id="habitationLocation">
                        Loading habitation information...
                    </p>

                </div>


                <a href="habitations.php" class="btn btn-sm">
                    ← Back to Habitations
                </a>

            </div>


            <div id="loading" class="card loading">
                Loading habitation details...
            </div>


            <div id="detailsContent" style="display:none;">


                <!-- BASIC INFORMATION -->

                <div class="card">

                    <div class="card-header">

                        <div>

                            <div class="card-title">
                                Habitation Information
                            </div>

                            <div class="card-subtitle">
                                Registered habitation details
                            </div>

                        </div>

                    </div>


                    <div class="info-grid">

                        <div class="info-item">

                            <div class="info-label">
                                Population
                            </div>

                            <div class="info-value" id="population">
                                —
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                District
                            </div>

                            <div class="info-value" id="district">
                                —
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                State
                            </div>

                            <div class="info-value" id="state">
                                —
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Coordinates
                            </div>

                            <div class="info-value" id="coordinates">
                                —
                            </div>

                        </div>

                    </div>

                </div>


                <!-- RISK -->

                <div class="details-grid">


                    <div class="card">

                        <div class="card-header">

                            <div>

                                <div class="card-title">
                                    Hazard Factors
                                </div>

                                <div class="card-subtitle">
                                    Inputs used for risk assessment
                                </div>

                            </div>

                        </div>


                        <div class="factor">

                            <div class="factor-header">

                                <span>
                                    Flood Risk
                                </span>

                                <span id="floodValue">
                                    0
                                </span>

                            </div>

                            <div class="progress">

                                <div
                                    id="floodBar"
                                    class="progress-bar"
                                    style="width:0%"
                                ></div>

                            </div>

                        </div>


                        <div class="factor">

                            <div class="factor-header">

                                <span>
                                    Landslide Risk
                                </span>

                                <span id="landslideValue">
                                    0
                                </span>

                            </div>

                            <div class="progress">

                                <div
                                    id="landslideBar"
                                    class="progress-bar"
                                    style="width:0%"
                                ></div>

                            </div>

                        </div>


                        <div class="factor">

                            <div class="factor-header">

                                <span>
                                    Hazard History
                                </span>

                                <span id="historyValue">
                                    0
                                </span>

                            </div>

                            <div class="progress">

                                <div
                                    id="historyBar"
                                    class="progress-bar"
                                    style="width:0%"
                                ></div>

                            </div>

                        </div>


                        <div class="factor">

                            <div class="factor-header">

                                <span>
                                    Population Vulnerability
                                </span>

                                <span id="vulnerabilityValue">
                                    0
                                </span>

                            </div>

                            <div class="progress">

                                <div
                                    id="vulnerabilityBar"
                                    class="progress-bar"
                                    style="width:0%"
                                ></div>

                            </div>

                        </div>

                    </div>


                    <!-- RISK SUMMARY -->

                    <div class="card risk-card">

                        <div class="card-title">
                            Risk Assessment
                        </div>


                        <div
                            id="riskScore"
                            class="risk-score"
                        >
                            —
                        </div>


                        <div
                            id="riskLevel"
                            class="risk-level risk-none"
                        >
                            NOT ASSESSED
                        </div>


                        <div id="zoneStatus">
                            —
                        </div>


                        <div class="action-row">

                            <a
                                id="riskButton"
                                href="#"
                                class="btn btn-primary"
                            >
                                Assess Risk
                            </a>

                            <a
                                id="relocationButton"
                                href="#"
                                class="btn btn-sm"
                            >
                                Plan Relocation
                            </a>

                        </div>

                    </div>

                </div>


                <!-- RED ZONE / PRIORITY -->

                <div id="zoneBox"></div>


            </div>


        </section>

    </main>

</div>


<script>

const params = new URLSearchParams(
    window.location.search
);

const habitationId = params.get("id");


document.addEventListener(
    "DOMContentLoaded",
    loadHabitation
);


function loadHabitation() {

    if (!habitationId) {

        showError(
            "No habitation ID was provided."
        );

        return;

    }


    fetch(
        "../api/get_habitation.php?id="
        + encodeURIComponent(habitationId)
    )

        .then(response => response.json())

        .then(data => {

            if (!data.success) {

                showError(
                    data.message ||
                    "Unable to load habitation."
                );

                return;

            }


            const habitation =
                data.data || data.habitation;


            if (!habitation) {

                showError(
                    "Habitation data not found."
                );

                return;

            }


            renderHabitation(habitation);


            loadRisk(habitation);

        })

        .catch(error => {

            console.error(error);

            showError(
                "Unable to connect to habitation API."
            );

        });

}


function loadRisk(habitation) {

fetch(
    "../api/get_risk.php?habitation_id="
    + encodeURIComponent(habitation.id)
)

        .then(response => response.json())

        .then(data => {

            if (
                data.success &&
                data.data
            ) {

                renderRisk(data.data);

            } else {

                renderRiskFromHabitation(
                    habitation
                );

            }

        })

        .catch(() => {

            renderRiskFromHabitation(
                habitation
            );

        });

}


function renderHabitation(habitation) {

    document.getElementById(
        "habitationName"
    ).textContent =
        habitation.name || "Habitation";


    document.getElementById(
        "habitationLocation"
    ).textContent =
        (
            habitation.district || "—"
        )
        + ", "
        +
        (
            habitation.state || "—"
        );


    document.getElementById(
        "population"
    ).textContent =
        Number(
            habitation.population || 0
        ).toLocaleString();


    document.getElementById(
        "district"
    ).textContent =
        habitation.district || "—";


    document.getElementById(
        "state"
    ).textContent =
        habitation.state || "—";


    document.getElementById(
        "coordinates"
    ).textContent =
        (
            habitation.latitude || "—"
        )
        + " , "
        +
        (
            habitation.longitude || "—"
        );


    setFactor(
        "flood",
        habitation.flood_risk
    );


    setFactor(
        "landslide",
        habitation.landslide_risk
    );


    setFactor(
        "history",
        habitation.hazard_history
    );


    setFactor(
        "vulnerability",
        habitation.population_vulnerability
    );


    document.getElementById(
        "riskButton"
    ).href =
        "risk_assessment.php?id="
        + habitation.id;


    document.getElementById(
        "relocationButton"
    ).href =
        "relocation.php?id="
        + habitation.id;


    document.getElementById(
        "loading"
    ).style.display =
        "none";


    document.getElementById(
        "detailsContent"
    ).style.display =
        "block";

}


function renderRisk(risk) {

    const score =
        Number(
            risk.risk_score || 0
        );


    const level =
        risk.risk_level ||
        "NOT ASSESSED";


    document.getElementById(
        "riskScore"
    ).textContent =
        score.toFixed(2);


    const levelElement =
        document.getElementById(
            "riskLevel"
        );


    levelElement.textContent =
        level;


    levelElement.className =
        "risk-level "
        +
        getRiskClass(level);


    const redZone =
        Number(
            risk.red_zone || 0
        );


    const priority =
        risk.relocation_priority ||
        "—";


    const zoneStatus =
        document.getElementById(
            "zoneStatus"
        );


    zoneStatus.innerHTML =
        redZone === 1

        ? "<strong style='color:#dc2626;'>🔴 RED ZONE</strong>"

        : "<strong style='color:#16a34a;'>🟢 NORMAL ZONE</strong>";


    const zoneBox =
        document.getElementById(
            "zoneBox"
        );


    if (redZone === 1) {

        zoneBox.innerHTML = `

            <div class="red-zone">

                🔴 RED ZONE IDENTIFIED

                <br><br>

                Relocation Priority:
                ${escapeHtml(priority)}

            </div>

        `;

    } else {

        zoneBox.innerHTML = `

            <div class="safe-zone">

                🟢 This habitation is not currently
                classified as a Red Zone.

                <br><br>

                Relocation Priority:
                ${escapeHtml(priority)}

            </div>

        `;

    }

}


function renderRiskFromHabitation(
    habitation
) {

    if (
        habitation.risk_score !== null &&
        habitation.risk_score !== undefined
    ) {

        renderRisk({

            risk_score:
                habitation.risk_score,

            risk_level:
                habitation.risk_level,

            red_zone:
                habitation.red_zone,

            relocation_priority:
                habitation.relocation_priority

        });

        return;

    }


    document.getElementById(
        "riskScore"
    ).textContent =
        "—";


    document.getElementById(
        "riskLevel"
    ).textContent =
        "NOT ASSESSED";

}


function setFactor(
    name,
    value
) {

    const numericValue =
        Number(value || 0);


    document.getElementById(
        name + "Value"
    ).textContent =
        numericValue;


    document.getElementById(
        name + "Bar"
    ).style.width =
        Math.min(
            Math.max(numericValue, 0),
            100
        ) + "%";

}


function getRiskClass(level) {

    if (level === "HIGH") {

        return "risk-high";

    }

    if (level === "MEDIUM") {

        return "risk-medium";

    }

    if (level === "LOW") {

        return "risk-low";

    }

    return "risk-none";

}


function showError(message) {

    document.getElementById(
        "loading"
    ).innerHTML = `

        <strong>
            ${escapeHtml(message)}
        </strong>

        <br><br>

        <a
            href="habitations.php"
            class="btn btn-primary"
        >
            ← Back to Habitations
        </a>

    `;

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