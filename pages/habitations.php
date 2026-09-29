<?php

$currentPage = 'habitations';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Habitations | RakshakGIS</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">

            <div class="logo-icon">R</div>

            <div>
                <div class="logo-text">RakshakGIS</div>

                <div class="logo-subtitle">
                    Disaster Risk & Relocation
                </div>
            </div>

        </div>


        <nav class="sidebar-nav">

            <div class="nav-section">Main</div>

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


            <div class="nav-section">Information</div>

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
                    Habitations
                </div>

                <div class="page-subtitle">
                    View and manage registered habitations
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

                    <h1>Registered Habitations</h1>

                    <p>
                        Monitor population, location and disaster risk status.
                    </p>

                </div>

                <a href="add_habitation.php" class="btn btn-primary">
                    ＋ Add Habitation
                </a>

            </div>


            <div class="card">

                <div class="card-header">

                    <div>

                        <div class="card-title">
                            All Habitations
                        </div>

                        <div class="card-subtitle">
                            Registered habitation records
                        </div>

                    </div>


                    <input
                        type="text"
                        id="searchHabitation"
                        class="search-input"
                        placeholder="Search habitation..."
                    >

                </div>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>Name</th>

                                <th>District</th>

                                <th>State</th>

                                <th>Population</th>

                                <th>Risk</th>

                                <th>Priority</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody id="habitationTable">

                            <tr>

                                <td colspan="7" class="empty-state">
                                    Loading...
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </main>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {

    loadHabitations();

});


let habitationData = [];


function loadHabitations() {

    fetch("../api/get_habitations.php")

        .then(response => response.json())

        .then(data => {

            if (!data.success) {

                console.error(data);

                showError();

                return;

            }

           habitationData = data.data || [];

            renderHabitations(habitationData);

        })

        .catch(error => {

            console.error("Habitations API Error:", error);

            showError();

        });

}


function renderHabitations(data) {

    const tbody = document.getElementById("habitationTable");

    if (!tbody) return;


    if (data.length === 0) {

        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-state">
                    No habitations found
                </td>
            </tr>
        `;

        return;

    }


    tbody.innerHTML = data.map(habitation => {

        const risk = habitation.risk_level || "NOT ASSESSED";

        let badgeClass = "badge-low";

        if (risk === "HIGH") {

            badgeClass = "badge-high";

        } else if (risk === "MEDIUM") {

            badgeClass = "badge-medium";

        } else if (risk === "NOT ASSESSED") {

            badgeClass = "badge-none";

        }


        const priority =
            habitation.relocation_priority || "—";


        return `

            <tr>

                <td>
                    <strong>
                        ${escapeHtml(habitation.name)}
                    </strong>
                </td>

                <td>
                    ${escapeHtml(habitation.district || "—")}
                </td>

                <td>
                    ${escapeHtml(habitation.state || "—")}
                </td>

                <td>
                    ${Number(
                        habitation.population || 0
                    ).toLocaleString()}
                </td>

                <td>

                    <span class="badge ${badgeClass}">
                        ${escapeHtml(risk)}
                    </span>

                </td>

                <td>
                    ${escapeHtml(priority)}
                </td>

<td>

    <a
        href="habitation_details.php?id=${habitation.id}"
        class="btn btn-sm"
    >
        View
    </a>
    

    <a
        href="relocation.php?id=${habitation.id}"
        class="btn btn-sm"
        style="margin-left:8px;"
    >
        Relocation
    </a>

</td>

            </tr>

        `;

    }).join("");

}


document
    .getElementById("searchHabitation")
    .addEventListener("input", function () {

        const query =
            this.value.toLowerCase().trim();


        const filtered =
            habitationData.filter(habitation => {

                return (

                    (habitation.name || "")
                        .toLowerCase()
                        .includes(query)

                    ||

                    (habitation.district || "")
                        .toLowerCase()
                        .includes(query)

                    ||

                    (habitation.state || "")
                        .toLowerCase()
                        .includes(query)

                );

            });


        renderHabitations(filtered);

    });


function showError() {

    document.getElementById("habitationTable").innerHTML = `

        <tr>

            <td colspan="7" class="empty-state">

                Unable to load habitation data.

            </td>

        </tr>

    `;

}


function escapeHtml(value) {

    const div = document.createElement("div");

    div.textContent = value ?? "";

    return div.innerHTML;

}

</script>

</body>

</html>