<?php

$currentPage = 'reports';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reports | RakshakGIS</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .reports-page {
            padding: 30px;
        }

        .report-controls {
            display: flex;
            align-items: end;
            gap: 15px;
            margin-bottom: 25px;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .control-group label {
            font-size: 14px;
            font-weight: 600;
            color: #475569;
        }

        .report-select {
            min-width: 300px;
            padding: 11px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            font-size: 15px;
        }

        .report-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
        }

        .report-header {
            padding: 25px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .report-title h2 {
            margin: 0 0 5px;
        }

        .report-title p {
            margin: 0;
            color: #64748b;
        }

        .report-body {
            padding: 30px;
        }

        .report-section {
            margin-bottom: 30px;
        }

        .report-section h3 {
            margin-bottom: 18px;
            color: #0f172a;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .report-info {
            padding: 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .report-info-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 7px;
        }

        .report-info-value {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
        }

        .risk-score-box {
            display: flex;
            align-items: center;
            gap: 30px;
            padding: 25px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .risk-score {
            font-size: 42px;
            font-weight: 800;
            color: #dc2626;
        }

        .risk-badge {
            display: inline-block;
            padding: 7px 15px;
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

        .factor-row {
            margin-bottom: 18px;
        }

        .factor-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 7px;
        }

        .factor-name {
            font-weight: 600;
        }

        .factor-value {
            font-weight: 700;
        }

        .factor-bar {
            height: 9px;
            background: #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
        }

        .factor-fill {
            height: 100%;
            background: #2563eb;
            border-radius: 20px;
        }

        .red-zone {
            padding: 18px 20px;
            background: #fee2e2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #991b1b;
            font-weight: 700;
        }

        .priority-box {
            padding: 18px 20px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 10px;
            color: #9a3412;
            font-weight: 700;
            margin-top: 12px;
        }

        .report-actions {
            display: flex;
            gap: 10px;
        }

        .empty-report {
            padding: 60px 20px;
            text-align: center;
            color: #64748b;
        }

        .loading {
            padding: 40px;
            text-align: center;
            color: #64748b;
        }

        .notes-box {
            padding: 18px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            color: #475569;
            line-height: 1.6;
        }

        @media (max-width: 800px) {

            .report-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .report-select {
                min-width: 0;
                width: 100%;
            }

            .report-grid {
                grid-template-columns: 1fr;
            }

            .report-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

        }

        @media print {

            .sidebar,
            .topbar,
            .report-controls,
            .report-actions {
                display: none !important;
            }

            .main {
                margin: 0 !important;
                width: 100% !important;
            }

            .reports-page {
                padding: 0;
            }

            .report-card {
                border: none;
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


            <a href="dashboard.php" class="nav-link">

                <span class="nav-icon">
                    ⌂
                </span>

                Dashboard

            </a>


            <a href="habitations.php" class="nav-link">

                <span class="nav-icon">
                    ⌂
                </span>

                Habitations

            </a>


            <a href="add_habitation.php" class="nav-link">

                <span class="nav-icon">
                    ＋
                </span>

                Add Habitation

            </a>


            <a href="risk_assessment.php" class="nav-link">

                <span class="nav-icon">
                    ⚠
                </span>

                Risk Assessment

            </a>


            <a href="map.php" class="nav-link">

                <span class="nav-icon">
                    ●
                </span>

                Interactive Map

            </a>


            <a href="relocation.php" class="nav-link">

                <span class="nav-icon">
                    ⌖
                </span>

                Relocation Planner

            </a>


            <a href="relocation_details.php" class="nav-link">

                <span class="nav-icon">
                    ▣
                </span>

                Relocation Plans

            </a>


            <a href="reports.php" class="nav-link active">

                <span class="nav-icon">
                    ▤
                </span>

                Reports

            </a>


            <div class="nav-section">
                Information
            </div>


            <a href="about.php" class="nav-link">

                <span class="nav-icon">
                    ⓘ
                </span>

                About

            </a>


            <a href="contact.php" class="nav-link">

                <span class="nav-icon">
                    ✉
                </span>

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
                    Reports
                </div>

                <div class="page-subtitle">
                    Generate disaster risk assessment reports
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

        <section class="reports-page">


            <div class="page-header">

                <div>

                    <h1>
                        Risk Assessment Reports
                    </h1>

                    <p>
                        Generate a detailed risk report for a registered habitation.
                    </p>

                </div>

            </div>


            <!-- SELECT HABITATION -->

            <div class="card report-controls">

                <div class="control-group">

                    <label for="habitationSelect">
                        Select Habitation
                    </label>

                    <select
                        id="habitationSelect"
                        class="report-select"
                    >

                        <option value="">
                            Loading habitations...
                        </option>

                    </select>

                </div>


                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="generateReport()"
                >
                    Generate Report
                </button>

            </div>


            <!-- REPORT -->

            <div
                id="reportContainer"
                class="report-card"
            >

                <div class="empty-report">

                    <h3>
                        No Report Generated
                    </h3>

                    <p>
                        Select a habitation and click
                        <strong>Generate Report</strong>.
                    </p>

                </div>

            </div>


        </section>

    </main>

</div>


<script>

let habitations = [];

let selectedHabitation = null;


/*
 * Load habitations
 */

document.addEventListener(
    "DOMContentLoaded",
    loadHabitations
);


function loadHabitations() {

    fetch("../api/get_habitations.php")

        .then(response => response.json())

        .then(data => {

            if (!data.success) {

                throw new Error(
                    "Unable to load habitations"
                );

            }

            habitations =
                data.data || [];


            const select =
                document.getElementById(
                    "habitationSelect"
                );


            select.innerHTML = `
                <option value="">
                    Select a habitation
                </option>
            `;


            habitations.forEach(habitation => {

                const option =
                    document.createElement("option");

                option.value =
                    habitation.id;

                option.textContent =
                    habitation.name +
                    " — " +
                    habitation.district;

                select.appendChild(option);

            });

        })

        .catch(error => {

            console.error(error);

            document.getElementById(
                "habitationSelect"
            ).innerHTML = `
                <option value="">
                    Failed to load habitations
                </option>
            `;

        });

}


/*
 * Generate report
 */

function generateReport() {

    const select =
        document.getElementById(
            "habitationSelect"
        );


    const habitationId =
        select.value;


    if (!habitationId) {

        alert(
            "Please select a habitation first."
        );

        return;

    }


    selectedHabitation =
        habitations.find(
            habitation =>
                String(habitation.id) ===
                String(habitationId)
        );


    if (!selectedHabitation) {

        return;

    }


    const container =
        document.getElementById(
            "reportContainer"
        );


    container.innerHTML = `

        <div class="loading">

            Loading risk assessment...

        </div>

    `;


    fetch(
        "../api/get_risk_assessment.php?habitation_id="
        +
        encodeURIComponent(habitationId)
    )

        .then(response => {

            if (response.status === 404) {

                return {
                    success: false,
                    notAssessed: true
                };

            }

            return response.json();

        })

        .then(data => {

            if (
                data.notAssessed ||
                !data.success
            ) {

                showNotAssessedReport();

                return;

            }


            renderReport(
                selectedHabitation,
                data.data
            );

        })

        .catch(error => {

            console.error(error);

            container.innerHTML = `

                <div class="empty-report">

                    <h3>
                        Unable to generate report
                    </h3>

                    <p>
                        Please check the risk assessment API.
                    </p>

                </div>

            `;

        });

}


/*
 * Not assessed
 */

function showNotAssessedReport() {

    const container =
        document.getElementById(
            "reportContainer"
        );


    container.innerHTML = `

        <div class="empty-report">

            <h3>
                Risk Assessment Not Available
            </h3>

            <p>
                This habitation has not been assessed yet.
            </p>

            <br>

            <a
                href="risk_assessment.php"
                class="btn btn-primary"
            >
                Assess Risk
            </a>

        </div>

    `;

}


/*
 * Render report
 */

function renderReport(
    habitation,
    risk
) {

    const container =
        document.getElementById(
            "reportContainer"
        );


    const riskScore =
        parseFloat(
            risk.risk_score || 0
        );


    const riskLevel =
        (
            risk.risk_level ||
            "NOT ASSESSED"
        ).toUpperCase();


    let riskClass =
        "risk-none";


    if (riskLevel === "HIGH") {

        riskClass =
            "risk-high";

    }
    else if (riskLevel === "MEDIUM") {

        riskClass =
            "risk-medium";

    }
    else if (riskLevel === "LOW") {

        riskClass =
            "risk-low";

    }


    const redZone =
        Number(risk.red_zone) === 1 ||
        risk.red_zone === "1" ||
        risk.red_zone === "YES";


    const priority =
        risk.relocation_priority ||
        "—";


    container.innerHTML = `

        <div class="report-header">

            <div class="report-title">

                <h2>
                    Disaster Risk Assessment Report
                </h2>

                <p>
                    RakshakGIS • Generated report
                </p>

            </div>


            <div class="report-actions">

                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="window.print()"
                >
                    Print / Save PDF
                </button>

            </div>

        </div>


        <div class="report-body">


            <!-- HABITATION INFORMATION -->

            <div class="report-section">

                <h3>
                    Habitation Information
                </h3>


                <div class="report-grid">


                    <div class="report-info">

                        <div class="report-info-label">
                            Habitation
                        </div>

                        <div class="report-info-value">
                            ${escapeHtml(
                                habitation.name
                            )}
                        </div>

                    </div>


                    <div class="report-info">

                        <div class="report-info-label">
                            District
                        </div>

                        <div class="report-info-value">
                            ${escapeHtml(
                                habitation.district
                            )}
                        </div>

                    </div>


                    <div class="report-info">

                        <div class="report-info-label">
                            State
                        </div>

                        <div class="report-info-value">
                            ${escapeHtml(
                                habitation.state
                            )}
                        </div>

                    </div>


                    <div class="report-info">

                        <div class="report-info-label">
                            Population
                        </div>

                        <div class="report-info-value">
                            ${Number(
                                habitation.population || 0
                            ).toLocaleString()}
                        </div>

                    </div>


                    <div class="report-info">

                        <div class="report-info-label">
                            Latitude
                        </div>

                        <div class="report-info-value">
                            ${escapeHtml(
                                habitation.latitude
                            )}
                        </div>

                    </div>


                    <div class="report-info">

                        <div class="report-info-label">
                            Longitude
                        </div>

                        <div class="report-info-value">
                            ${escapeHtml(
                                habitation.longitude
                            )}
                        </div>

                    </div>

                </div>

            </div>


            <!-- RISK SUMMARY -->

            <div class="report-section">

                <h3>
                    Risk Assessment Summary
                </h3>


                <div class="risk-score-box">

                    <div>

                        <div class="report-info-label">
                            Overall Risk Score
                        </div>

                        <div class="risk-score">
                            ${riskScore.toFixed(2)}
                        </div>

                    </div>


                    <div>

                        <span
                            class="risk-badge ${riskClass}"
                        >
                            ${escapeHtml(
                                riskLevel
                            )}
                        </span>

                    </div>

                </div>


                ${
                    redZone
                    ?
                    `
                    <div class="red-zone">
                        🔴 RED ZONE IDENTIFIED
                    </div>
                    `
                    :
                    `
                    <div
                        class="notes-box"
                        style="margin-top:12px"
                    >
                        Red Zone:
                        <strong>Not identified</strong>
                    </div>
                    `
                }


                <div class="priority-box">

                    Relocation Priority:
                    ${escapeHtml(priority)}

                </div>

            </div>


            <!-- HAZARD FACTORS -->

            <div class="report-section">

                <h3>
                    Hazard Factors
                </h3>


                ${createFactor(
                    "Flood Risk",
                    risk.flood_score
                )}


                ${createFactor(
                    "Landslide Risk",
                    risk.landslide_score
                )}


                ${createFactor(
                    "Hazard History",
                    risk.hazard_history_score
                )}


                ${createFactor(
                    "Population Vulnerability",
                    risk.vulnerability_score
                )}

            </div>


            <!-- NOTES -->

            <div class="report-section">

                <h3>
                    Assessment Notes
                </h3>


                <div class="notes-box">

                    ${
                        risk.assessment_notes
                        ?
                        escapeHtml(
                            risk.assessment_notes
                        )
                        :
                        "No additional assessment notes recorded."
                    }

                </div>

            </div>


            <!-- RELOCATION -->

            <div class="report-section">

                <h3>
                    Relocation Planning
                </h3>


                <div class="notes-box">

                    ${
                        redZone
                        ?
                        `
                        This habitation has been identified
                        for relocation planning based on the
                        current risk assessment.
                        `
                        :
                        `
                        Relocation is not currently flagged
                        as immediate based on this assessment.
                        `
                    }

                    <br><br>

                    <a
                        href="relocation.php?id=${habitation.id}"
                        class="btn btn-primary"
                    >
                        Open Relocation Planner
                    </a>

                </div>

            </div>


            <div
                style="
                    border-top:1px solid #e2e8f0;
                    padding-top:20px;
                    color:#64748b;
                    font-size:13px;
                "
            >

                Report generated by RakshakGIS.

                <br>

                This prototype report is based on the
                registered habitation and assessment data
                stored in the system.

            </div>


        </div>

    `;

}


/*
 * Create factor bar
 */

function createFactor(
    name,
    value
) {

    const numericValue =
        Math.max(
            0,
            Math.min(
                100,
                parseFloat(value || 0)
            )
        );


    return `

        <div class="factor-row">

            <div class="factor-top">

                <span class="factor-name">
                    ${name}
                </span>

                <span class="factor-value">
                    ${numericValue}
                </span>

            </div>


            <div class="factor-bar">

                <div
                    class="factor-fill"
                    style="width:${numericValue}%"
                ></div>

            </div>

        </div>

    `;

}


/*
 * Escape HTML
 */

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