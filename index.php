<?php



error_reporting(E_ALL);

ini_set('display_errors', '1');



require_once __DIR__ . "/config/auth.php";

requireLogin();



require_once __DIR__ . "/config/database.php";



$user = currentUser();





/*

|--------------------------------------------------------------------------

| DASHBOARD DATA

|--------------------------------------------------------------------------

*/



/*

 * Latest risk assessment for each habitation.

 * We use the latest assessment ID so repeated assessments

 * do not get counted as separate habitations.

 */



$latestAssessmentJoin = "

    LEFT JOIN (

        SELECT ra1.*

        FROM risk_assessments ra1

        INNER JOIN (

            SELECT

                habitation_id,

                MAX(id) AS latest_id

            FROM risk_assessments

            GROUP BY habitation_id

        ) latest

        ON latest.latest_id = ra1.id

    ) ra

    ON ra.habitation_id = h.id

";





/*

|--------------------------------------------------------------------------

| HABITATION STATISTICS

|--------------------------------------------------------------------------

*/



$totalHabitations = 0;

$totalPopulation = 0;

$highRisk = 0;

$mediumRisk = 0;

$lowRisk = 0;

$unassessed = 0;

$redZones = 0;



$sql = "

    SELECT

        COUNT(*) AS total_habitations,

        COALESCE(SUM(h.population), 0) AS total_population,



        COALESCE(

            SUM(

                CASE

                    WHEN ra.risk_level = 'HIGH'

                    THEN 1

                    ELSE 0

                END

            ),

            0

        ) AS high_risk,



        COALESCE(

            SUM(

                CASE

                    WHEN ra.risk_level = 'MEDIUM'

                    THEN 1

                    ELSE 0

                END

            ),

            0

        ) AS medium_risk,



        COALESCE(

            SUM(

                CASE

                    WHEN ra.risk_level = 'LOW'

                    THEN 1

                    ELSE 0

                END

            ),

            0

        ) AS low_risk,



        COALESCE(

            SUM(

                CASE

                    WHEN ra.id IS NULL

                    THEN 1

                    ELSE 0

                END

            ),

            0

        ) AS unassessed,



        COALESCE(

            SUM(

                CASE

                    WHEN ra.red_zone = 1

                    THEN 1

                    ELSE 0

                END

            ),

            0

        ) AS red_zones



    FROM habitations h



    $latestAssessmentJoin

";



$result = $conn->query($sql);



if ($result) {



    $row = $result->fetch_assoc();



    $totalHabitations =

        (int) $row["total_habitations"];



    $totalPopulation =

        (int) $row["total_population"];



    $highRisk =

        (int) $row["high_risk"];



    $mediumRisk =

        (int) $row["medium_risk"];



    $lowRisk =

        (int) $row["low_risk"];



    $unassessed =

        (int) $row["unassessed"];



    $redZones =

        (int) $row["red_zones"];

}





/*

|--------------------------------------------------------------------------

| RISK PERCENTAGES

|--------------------------------------------------------------------------

*/



$assessedTotal =

    $highRisk +

    $mediumRisk +

    $lowRisk;



$highPercent =

    $assessedTotal > 0

        ? round(

            ($highRisk / $assessedTotal) * 100

        )

        : 0;



$mediumPercent =

    $assessedTotal > 0

        ? round(

            ($mediumRisk / $assessedTotal) * 100

        )

        : 0;



$lowPercent =

    $assessedTotal > 0

        ? round(

            ($lowRisk / $assessedTotal) * 100

        )

        : 0;





/*

|--------------------------------------------------------------------------

| RELOCATION STATISTICS

|--------------------------------------------------------------------------

*/



$totalPlans = 0;

$plannedPlans = 0;

$approvedPlans = 0;

$inProgressPlans = 0;

$completedPlans = 0;

$cancelledPlans = 0;



$result = $conn->query("

    SELECT

        COUNT(*) AS total,

        COALESCE(

            SUM(status = 'PLANNED'),

            0

        ) AS planned,

        COALESCE(

            SUM(status = 'APPROVED'),

            0

        ) AS approved,

        COALESCE(

            SUM(status = 'IN_PROGRESS'),

            0

        ) AS in_progress,

        COALESCE(

            SUM(status = 'COMPLETED'),

            0

        ) AS completed,

        COALESCE(

            SUM(status = 'CANCELLED'),

            0

        ) AS cancelled

    FROM relocation_plans

");



if ($result) {



    $row = $result->fetch_assoc();



    $totalPlans =

        (int) $row["total"];



    $plannedPlans =

        (int) $row["planned"];



    $approvedPlans =

        (int) $row["approved"];



    $inProgressPlans =

        (int) $row["in_progress"];



    $completedPlans =

        (int) $row["completed"];



    $cancelledPlans =

        (int) $row["cancelled"];

}





/*

|--------------------------------------------------------------------------

| RELOCATION SITE CAPACITY

|--------------------------------------------------------------------------

*/



$totalSites = 0;

$totalCapacity = 0;

$occupiedCapacity = 0;

$availableCapacity = 0;



$result = $conn->query("

    SELECT

        COUNT(*) AS total_sites,

        COALESCE(

            SUM(total_capacity),

            0

        ) AS total_capacity,

        COALESCE(

            SUM(occupied_capacity),

            0

        ) AS occupied_capacity,

        COALESCE(

            SUM(

                GREATEST(

                    total_capacity - occupied_capacity,

                    0

                )

            ),

            0

        ) AS available_capacity

    FROM relocation_sites

");



if ($result) {



    $row = $result->fetch_assoc();



    $totalSites =

        (int) $row["total_sites"];



    $totalCapacity =

        (int) $row["total_capacity"];



    $occupiedCapacity =

        (int) $row["occupied_capacity"];



    $availableCapacity =

        (int) $row["available_capacity"];

}





/*

|--------------------------------------------------------------------------

| HIGH RISK HABITATIONS

|--------------------------------------------------------------------------

*/



$highRiskHabitations = [];



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



    $latestAssessmentJoin



    WHERE ra.risk_level = 'HIGH'



    ORDER BY

        ra.risk_score DESC



    LIMIT 5

";



$result = $conn->query($sql);



if ($result) {



    while ($row = $result->fetch_assoc()) {



        $highRiskHabitations[] = $row;

    }

}





/*

|--------------------------------------------------------------------------

| RECENT ASSESSMENTS

|--------------------------------------------------------------------------

*/



$recentAssessments = [];



$sql = "

    SELECT

        h.name,

        h.district,

        ra.risk_score,

        ra.risk_level,

        ra.red_zone,

        ra.relocation_priority,

        ra.assessed_at



    FROM risk_assessments ra



    INNER JOIN habitations h

        ON h.id = ra.habitation_id



    ORDER BY

        ra.assessed_at DESC,

        ra.id DESC



    LIMIT 5

";



$result = $conn->query($sql);



if ($result) {



    while ($row = $result->fetch_assoc()) {



        $recentAssessments[] = $row;

    }

}





/*

|--------------------------------------------------------------------------

| HELPERS

|--------------------------------------------------------------------------

*/



function riskClass($level)

{

    return match ($level) {



        "HIGH" =>

            "risk-high",



        "MEDIUM" =>

            "risk-medium",



        "LOW" =>

            "risk-low",



        default =>

            "risk-unassessed"

    };

}





function priorityClass($priority)

{

    return match ($priority) {



        "IMMEDIATE" =>

            "priority-immediate",



        "SHORT-TERM" =>

            "priority-short",



        "MEDIUM-TERM" =>

            "priority-medium",



        default =>

            "priority-none"

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



<title>

    Dashboard | RakshakGIS

</title>





<link

    rel="stylesheet"

    href="assets/css/style.css"

>





<style>



/* =====================================================

   DASHBOARD

   ===================================================== */



.dashboard-heading {



    display: flex;



    justify-content: space-between;



    align-items: flex-start;



    gap: 20px;



    margin-bottom: 22px;

}





.dashboard-heading h1 {



    font-size: 25px;



    font-weight: 850;

}





.dashboard-heading p {



    margin-top: 5px;



    font-size: 12px;



    color: var(--muted);

}





.dashboard-date {



    background: white;



    border: 1px solid var(--border);



    border-radius: 9px;



    padding: 9px 13px;



    font-size: 11px;



    color: var(--muted);

}





/* =====================================================

   WELCOME

   ===================================================== */



.welcome-card {



    background:

        linear-gradient(

            135deg,

            #0f172a 0%,

            #172554 55%,

            #1e40af 100%

        );



    color: white;



    border-radius: 14px;



    padding: 24px 26px;



    margin-bottom: 22px;



    position: relative;



    overflow: hidden;

}





.welcome-card::before {



    content: "";



    position: absolute;



    width: 260px;



    height: 260px;



    border: 1px solid

        rgba(255,255,255,.08);



    border-radius: 50%;



    right: -80px;



    top: -130px;

}





.welcome-card::after {



    content: "";



    position: absolute;



    width: 180px;



    height: 180px;



    border: 1px solid

        rgba(255,255,255,.06);



    border-radius: 50%;



    right: 80px;



    bottom: -130px;

}





.welcome-content {



    position: relative;



    z-index: 1;

}





.welcome-label {



    font-size: 10px;



    text-transform: uppercase;



    letter-spacing: 1px;



    color: #93c5fd;



    font-weight: 800;

}





.welcome-title {



    font-size: 22px;



    font-weight: 850;



    margin-top: 6px;

}





.welcome-description {



    font-size: 12px;



    color: #cbd5e1;



    margin-top: 6px;



    max-width: 650px;

}





/* =====================================================

   MAIN STAT CARDS

   ===================================================== */



.dashboard-stats {



    display: grid;



    grid-template-columns:

        repeat(4, 1fr);



    gap: 16px;



    margin-bottom: 22px;

}





.dashboard-stat {



    background: white;



    border: 1px solid var(--border);



    border-radius: 12px;



    padding: 19px;



    position: relative;



    transition: .2s ease;

}





.dashboard-stat:hover {



    transform: translateY(-2px);



    box-shadow:

        0 8px 24px

        rgba(15,23,42,.06);

}





.dashboard-stat-top {



    display: flex;



    align-items: center;



    justify-content: space-between;

}





.dashboard-stat-icon {



    width: 39px;



    height: 39px;



    border-radius: 10px;



    display: flex;



    align-items: center;



    justify-content: center;



    background: #eff6ff;



    color: var(--primary);



    font-weight: 900;



    font-size: 17px;

}





.dashboard-stat-danger {



    background: #fef2f2;



    color: #dc2626;

}





.dashboard-stat-warning {



    background: #fffbeb;



    color: #d97706;

}





.dashboard-stat-success {



    background: #f0fdf4;



    color: #16a34a;

}





.dashboard-stat-label {



    margin-top: 13px;



    font-size: 11px;



    color: var(--muted);

}





.dashboard-stat-value {



    font-size: 28px;



    font-weight: 900;



    margin-top: 3px;

}





.dashboard-stat-description {



    font-size: 9px;



    color: var(--muted);



    margin-top: 3px;

}





/* =====================================================

   CONTENT GRID

   ===================================================== */



.dashboard-grid {



    display: grid;



    grid-template-columns:

        1.45fr 1fr;



    gap: 20px;



    align-items: start;

}





.dashboard-card {



    background: white;



    border: 1px solid var(--border);



    border-radius: 12px;



    overflow: hidden;



    margin-bottom: 20px;

}





.dashboard-card-header {



    padding: 17px 19px;



    border-bottom: 1px solid var(--border);



    display: flex;



    justify-content: space-between;



    align-items: center;



    gap: 15px;

}





.dashboard-card-title {



    font-size: 14px;



    font-weight: 800;

}





.dashboard-card-subtitle {



    font-size: 10px;



    color: var(--muted);



    margin-top: 3px;

}





.dashboard-card-link {



    font-size: 10px;



    color: var(--primary);



    text-decoration: none;



    font-weight: 700;

}





.dashboard-card-body {



    padding: 19px;

}





/* =====================================================

   RISK OVERVIEW

   ===================================================== */



.risk-overview-row {



    margin-bottom: 16px;

}





.risk-overview-row:last-child {



    margin-bottom: 0;

}





.risk-overview-header {



    display: flex;



    justify-content: space-between;



    align-items: center;



    margin-bottom: 7px;

}





.risk-overview-label {



    display: flex;



    align-items: center;



    gap: 7px;



    font-size: 11px;



    font-weight: 700;

}





.risk-dot {



    width: 8px;



    height: 8px;



    border-radius: 50%;

}





.risk-dot-high {

    background: #dc2626;

}



.risk-dot-medium {

    background: #f59e0b;

}



.risk-dot-low {

    background: #16a34a;

}



.risk-dot-gray {

    background: #64748b;

}





.risk-overview-number {



    font-size: 11px;



    font-weight: 800;

}





.progress {



    height: 8px;



    border-radius: 999px;



    background: #e2e8f0;



    overflow: hidden;

}





.progress-bar {



    height: 100%;



    border-radius: inherit;



    min-width: 3px;

}





.progress-high {

    background: #ef4444;

}



.progress-medium {

    background: #f59e0b;

}



.progress-low {

    background: #22c55e;

}





/* =====================================================

   RISK TABLE

   ===================================================== */



.risk-table {



    width: 100%;



    border-collapse: collapse;

}





.risk-table th {



    font-size: 9px;



    text-transform: uppercase;



    letter-spacing: .5px;



    color: var(--muted);



    background: #f8fafc;



    text-align: left;

}





.risk-table th,

.risk-table td {



    padding: 11px 12px;



    border-bottom: 1px solid var(--border);

}





.risk-table tr:last-child td {



    border-bottom: none;

}





.risk-table td {



    font-size: 10px;

}





.habitation-name {



    font-weight: 800;

}





.habitation-location {



    color: var(--muted);



    font-size: 9px;



    margin-top: 2px;

}





.score {



    font-size: 12px;



    font-weight: 900;

}





.badge {



    display: inline-flex;



    align-items: center;



    padding: 4px 8px;



    border-radius: 999px;



    font-size: 8px;



    font-weight: 800;

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





.risk-unassessed {



    background: #f1f5f9;



    color: #64748b;

}





.priority-immediate {



    background: #fee2e2;



    color: #b91c1c;

}





.priority-short {



    background: #ffedd5;



    color: #c2410c;

}





.priority-medium {



    background: #fef3c7;



    color: #a16207;

}





.priority-none {



    background: #f1f5f9;



    color: #64748b;

}





.red-zone {



    color: #dc2626;



    font-weight: 800;

}





.no-red-zone {



    color: #64748b;

}





/* =====================================================

   RELOCATION OVERVIEW

   ===================================================== */



.relocation-grid {



    display: grid;



    grid-template-columns:

        repeat(2, 1fr);



    gap: 10px;

}





.relocation-box {



    padding: 14px;



    border-radius: 9px;



    background: #f8fafc;

}





.relocation-label {



    font-size: 9px;



    color: var(--muted);

}





.relocation-value {



    font-size: 21px;



    font-weight: 900;



    margin-top: 3px;

}





.relocation-blue {

    color: #2563eb;

}



.relocation-orange {

    color: #d97706;

}



.relocation-green {

    color: #16a34a;

}





/* =====================================================

   CAPACITY

   ===================================================== */



.capacity-number {



    display: flex;



    justify-content: space-between;



    align-items: baseline;



    margin-bottom: 9px;

}





.capacity-value {



    font-size: 25px;



    font-weight: 900;



    color: #16a34a;

}





.capacity-label {



    font-size: 10px;



    color: var(--muted);

}





.capacity-bar {



    height: 10px;



    background: #e2e8f0;



    border-radius: 999px;



    overflow: hidden;

}





.capacity-fill {



    height: 100%;



    background: #22c55e;



    border-radius: inherit;

}





.capacity-meta {



    display: flex;



    justify-content: space-between;



    margin-top: 8px;



    font-size: 9px;



    color: var(--muted);

}





/* =====================================================

   QUICK ACTIONS

   ===================================================== */



.quick-grid {



    display: grid;



    grid-template-columns:

        1fr 1fr;



    gap: 9px;

}





.quick-link {



    display: block;



    padding: 12px;



    border: 1px solid var(--border);



    border-radius: 9px;



    text-decoration: none;



    color: var(--text);



    transition: .2s ease;

}





.quick-link:hover {



    background: #eff6ff;



    border-color: #bfdbfe;



    transform: translateY(-1px);

}





.quick-icon {



    font-size: 16px;



    margin-bottom: 6px;

}





.quick-title {



    font-size: 10px;



    font-weight: 800;

}





.quick-description {



    font-size: 8px;



    color: var(--muted);



    margin-top: 2px;

}





/* =====================================================

   RECENT ASSESSMENTS

   ===================================================== */



.assessment-item {



    display: flex;



    justify-content: space-between;



    align-items: center;



    gap: 12px;



    padding: 11px 0;



    border-bottom: 1px solid var(--border);

}





.assessment-item:last-child {



    border-bottom: none;



    padding-bottom: 0;

}





.assessment-item:first-child {



    padding-top: 0;

}





.assessment-name {



    font-size: 10px;



    font-weight: 800;

}





.assessment-date {



    font-size: 8px;



    color: var(--muted);



    margin-top: 3px;

}





.assessment-right {



    text-align: right;

}





/* =====================================================

   EMPTY

   ===================================================== */



.empty-state {



    text-align: center;



    padding: 25px;



    color: var(--muted);



    font-size: 11px;

}





/* =====================================================

   RESPONSIVE

   ===================================================== */



@media (max-width: 1100px) {



    .dashboard-stats {



        grid-template-columns:

            repeat(2, 1fr);

    }



    .dashboard-grid {



        grid-template-columns: 1fr;

    }



}

/* =====================================================

   SIDEBAR USER / FOOTER FIX

   ===================================================== */



.sidebar {

    display: flex;

    flex-direction: column;

    height: 100vh;

    min-height: 100vh;

    overflow: hidden;

}



.sidebar-nav {

    flex: 1;

    min-height: 0;

    overflow-y: auto;

    overflow-x: hidden;

}



.sidebar-user {

    flex-shrink: 0;

    margin-top: auto;

    padding: 14px 16px;

    border-top: 1px solid rgba(255,255,255,.08);

    background: #0f172a;

}



.sidebar-user-inner {

    display: flex;

    align-items: center;

    gap: 10px;

    min-width: 0;

}



.sidebar-user-info {

    flex: 1;

    min-width: 0;

}



.sidebar-user-name {

    color: #ffffff;

    font-size: 11px;

    font-weight: 700;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

}



.sidebar-user-role {

    color: #94a3b8;

    font-size: 9px;

    margin-top: 3px;

    text-transform: uppercase;

    letter-spacing: .5px;

}



.sidebar-avatar {

    flex-shrink: 0;

    width: 34px;

    height: 34px;

    border-radius: 50%;

    background: #2563eb;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 12px;

    font-weight: 800;

}



.sidebar-logout {

    flex-shrink: 0;

    width: 30px;

    height: 30px;

    border-radius: 8px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #94a3b8;

    text-decoration: none;

    font-size: 17px;

    transition: .2s ease;

}



.sidebar-logout:hover {

    background: rgba(255,255,255,.08);

    color: #ffffff;

}

/* =====================================================

   RAKSHAKGIS DASHBOARD POLISH

   ===================================================== */



/* Main dashboard spacing */

.dashboard-content {

    padding-bottom: 40px;

}



/* Dashboard heading */

.dashboard-heading {

    margin-bottom: 20px;

}



.dashboard-heading h1 {

    margin-bottom: 5px;

}



.dashboard-heading p {

    margin-top: 0;

}



/* Welcome banner */

.dashboard-welcome {

    margin-bottom: 22px;

    min-height: 125px;

    display: flex;

    align-items: center;

}



/* Statistics cards */

.dashboard-stats {

    gap: 16px;

    margin-bottom: 24px;

}



.dashboard-stat {

    min-height: 145px;

    transition: transform .2s ease, box-shadow .2s ease;

}



.dashboard-stat:hover {

    transform: translateY(-2px);

    box-shadow: 0 8px 24px rgba(15, 23, 42, .08);

}



/* Main two-column area */

.dashboard-grid {
    display: grid;
    grid-template-columns: 1.25fr 1fr;
    gap: 20px;
    align-items: start;
}

.dashboard-grid > div {
    display: contents;
}
.dashboard-grid .dashboard-card {
    margin-bottom: 0;
}



/* Dashboard cards */

.dashboard-card {

    overflow: hidden;

    border-radius: 14px;

    transition: box-shadow .2s ease, border-color .2s ease;

}



.dashboard-card:hover {

    box-shadow: 0 6px 20px rgba(15, 23, 42, .06);

}



/* Card headers */

.dashboard-card-header {

    min-height: 72px;

    display: flex;

    align-items: center;

}



/* Risk rows */

.risk-overview-row {

    margin-bottom: 20px;

}



.risk-overview-row:last-child {

    margin-bottom: 0;

}



/* Progress bars */

.progress {

    height: 8px;

    border-radius: 999px;

    overflow: hidden;

    background: #e2e8f0;

}



.progress-bar {

    height: 100%;

    border-radius: 999px;

    transition: width .4s ease;

}



/* Tables inside dashboard */

.risk-table {

    width: 100%;

}



.risk-table th {

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .5px;

}



.risk-table td {

    font-size: 11px;

}



/* Empty states */

.empty-state {

    padding: 32px 20px;

    text-align: center;

    color: #64748b;

    font-size: 12px;

}



/* Dashboard links */

.dashboard-card-link {

    font-weight: 700;

    transition: color .2s ease;

}



.dashboard-card-link:hover {

    color: #1e3a8a;

}



/* Better spacing between dashboard sections */

.dashboard-grid > div {

    display: flex;

    flex-direction: column;

    gap: 20px;

}



/* Recent assessment / lower cards */

.dashboard-card + .dashboard-card {

    margin-top: 0;

}



/* Smooth page appearance */

.dashboard-content > * {

    animation: dashboardFadeIn .35s ease both;

}



@keyframes dashboardFadeIn {

    from {

        opacity: 0;

        transform: translateY(5px);

    }



    to {

        opacity: 1;

        transform: translateY(0);

    }

}
/* =====================================================
   RAKSHAK GIS BRANDING
   ===================================================== */

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    padding: 20px 18px;
}

.logo-icon {
    width: 42px;
    height: 46px;
    flex-shrink: 0;

    background: linear-gradient(
        145deg,
        #2563eb,
        #1d4ed8
    );

    color: #ffffff;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 12px 12px 14px 14px;

    position: relative;

    box-shadow:
        0 6px 16px rgba(37, 99, 235, .28);

    clip-path: polygon(
        50% 0%,
        92% 15%,
        92% 55%,
        82% 76%,
        50% 100%,
        18% 76%,
        8% 55%,
        8% 15%
    );
}

.logo-icon span {
    font-size: 20px;
    font-weight: 900;
    font-family: Arial, sans-serif;
    color: #ffffff;
}

.logo-brand {
    min-width: 0;
}

.logo-text {
    font-size: 17px;
    line-height: 1;
    font-weight: 900;
    letter-spacing: .4px;
    color: #ffffff;
    white-space: nowrap;
}

.logo-text span {
    color: #60a5fa;
}

.logo-subtitle {
    margin-top: 6px;
    font-size: 7.5px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: 1.1px;
    color: #94a3b8;
    white-space: nowrap;
}







@media (max-width: 700px) {



    .dashboard-heading {



        flex-direction: column;

    }



    .dashboard-stats {



        grid-template-columns: 1fr;

    }



    .relocation-grid,

    .quick-grid {



        grid-template-columns: 1fr;

    }



}
/* =====================================================
   LOWER DASHBOARD POLISH
   ===================================================== */

/* -----------------------------------------------------
   HIGH-RISK HABITATIONS
   ----------------------------------------------------- */

.high-risk-item {
    padding: 14px 0;
    border-bottom: 1px solid #eef2f7;
    transition: background .2s ease, padding .2s ease;
}

.high-risk-item:first-child {
    padding-top: 0;
}

.high-risk-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.high-risk-item:hover {
    padding-left: 6px;
    background: #fafcff;
}

.high-risk-name {
    font-size: 12px;
    font-weight: 800;
    color: #0f172a;
}

.high-risk-meta {
    margin-top: 4px;
    font-size: 10px;
    color: #64748b;
}

.high-risk-score {
    font-size: 13px;
    font-weight: 800;
}

/* -----------------------------------------------------
   SAFE RELOCATION CAPACITY
   ----------------------------------------------------- */

.capacity-number {
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.capacity-value {
    font-size: 32px;
    font-weight: 900;
    line-height: 1;
    color: #0f172a;
}

.capacity-label {
    font-size: 10px;
    color: #64748b;
}

.capacity-bar {
    height: 10px;
    margin-top: 16px;
    border-radius: 999px;
    overflow: hidden;
    background: #e2e8f0;
}

.capacity-fill {
    height: 100%;
    border-radius: 999px;
    transition: width .5s ease;
}

.capacity-meta {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 10px;
    color: #64748b;
    font-size: 9px;
}

/* -----------------------------------------------------
   QUICK ACTIONS
   ----------------------------------------------------- */

.quick-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.quick-link {
    display: block;
    padding: 15px;
    border: 1px solid #e5eaf0;
    border-radius: 12px;
    background: #ffffff;
    text-decoration: none;
    transition:
        transform .2s ease,
        border-color .2s ease,
        background .2s ease,
        box-shadow .2s ease;
}

.quick-link:hover {
    transform: translateY(-2px);
    border-color: #bfdbfe;
    background: #f8fbff;
    box-shadow: 0 6px 18px rgba(37, 99, 235, .08);
}

.quick-icon {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    border-radius: 9px;
    background: #eff6ff;
    color: #2563eb;
    font-size: 16px;
}

.quick-title {
    color: #0f172a;
    font-size: 11px;
    font-weight: 800;
}

.quick-description {
    margin-top: 4px;
    color: #64748b;
    font-size: 9px;
    line-height: 1.4;
}

/* -----------------------------------------------------
   RECENT ASSESSMENTS
   ----------------------------------------------------- */

.assessment-item {
    padding: 13px 0;
    border-bottom: 1px solid #eef2f7;
    transition: background .2s ease, padding .2s ease;
}

.assessment-item:first-child {
    padding-top: 0;
}

.assessment-item:last-child {
    padding-bottom: 0;
    border-bottom: none;
}

.assessment-item:hover {
    padding-left: 6px;
    background: #fafcff;
}

.assessment-name {
    font-size: 11px;
    font-weight: 800;
    color: #0f172a;
}

.assessment-meta {
    margin-top: 4px;
    color: #64748b;
    font-size: 9px;
}

/* -----------------------------------------------------
   RISK / PRIORITY BADGES
   ----------------------------------------------------- */

.risk-badge,
.priority-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 999px;
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .3px;
}

.risk-high {
    background: #fef2f2;
    color: #dc2626;
}

.risk-medium {
    background: #fff7ed;
    color: #ea580c;
}

.risk-low {
    background: #f0fdf4;
    color: #16a34a;
}

.risk-unassessed {
    background: #f1f5f9;
    color: #64748b;
}

.priority-immediate {
    background: #fef2f2;
    color: #dc2626;
}

.priority-short {
    background: #fff7ed;
    color: #ea580c;
}

.priority-medium {
    background: #eff6ff;
    color: #2563eb;
}

.priority-none {
    background: #f1f5f9;
    color: #64748b;
}

/* -----------------------------------------------------
   LOWER CARD HEADERS
   ----------------------------------------------------- */

.dashboard-card-title {
    color: #0f172a;
    font-size: 14px;
    font-weight: 800;
}

.dashboard-card-subtitle {
    margin-top: 3px;
    color: #64748b;
    font-size: 9px;
}

.dashboard-card-link {
    color: #2563eb;
    font-size: 9px;
    font-weight: 800;
    text-decoration: none;
}

.dashboard-card-link:hover {
    color: #1e3a8a;
}

/* -----------------------------------------------------
   LOWER SECTION RESPONSIVE
   ----------------------------------------------------- */

@media (max-width: 700px) {

    .quick-grid {
        grid-template-columns: 1fr;
    }

    .capacity-meta {
        flex-direction: column;
        gap: 4px;
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

    href="index.php"

    class="nav-link active"

>



<span class="nav-icon">

    ⌂

</span>



Dashboard



</a>





<a

    href="pages/habitations.php"

    class="nav-link"

>



<span class="nav-icon">

    ⌖

</span>



Habitations



</a>





<a

    href="pages/risk_assessment.php"

    class="nav-link"

>



<span class="nav-icon">

    ⚠

</span>



Risk Assessment



</a>





<a

    href="pages/risk_map.php"

    class="nav-link"

>



<span class="nav-icon">

    ◎

</span>



Risk Map



</a>





<div class="nav-section">

    Relocation

</div>





<a

    href="pages/relocation_sites.php"

    class="nav-link"

>



<span class="nav-icon">

    ⌂

</span>



Relocation Sites



</a>





<a

    href="pages/relocation_plans.php"

    class="nav-link"

>



<span class="nav-icon">

    →

</span>



Relocation Plans



</a>





<div class="nav-section">

    System

</div>





<a

    href="pages/reports.php"

    class="nav-link"

>



<span class="nav-icon">

    ▤

</span>



Reports



</a>





<?php if (

    ($user["role"] ?? "") === "ADMIN"

): ?>



<a

    href="pages/admin.php"

    class="nav-link"

>



<span class="nav-icon">

    ⚙

</span>



Administration



</a>



<?php endif; ?>





</nav>





<!-- SIDEBAR USER -->



<div class="sidebar-user">



<div class="sidebar-user-inner">





<div class="sidebar-avatar">



<?= htmlspecialchars(

    strtoupper(

        substr(

            $user["name"] ?? "A",

            0,

            1

        )

    )

) ?>



</div>





<div class="sidebar-user-info">



<div class="sidebar-user-name">



<?= htmlspecialchars(

    $user["name"]

    ??

    "Administrator"

) ?>



</div>





<div class="sidebar-user-role">



<?= htmlspecialchars(

    $user["role"]

    ??

    "USER"

) ?>



</div>



</div>





<a

    href="logout.php"

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

    Dashboard

</div>



<div class="page-subtitle">

    Disaster risk & safe relocation overview

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





<!-- =====================================================

     HEADING

     ===================================================== -->



<div class="dashboard-heading">





<div>



<h1>

    Dashboard

</h1>



<p>

    Monitor habitation risk, population vulnerability and relocation planning.

</p>



</div>





<div class="dashboard-date">



<?= date("d M Y") ?>



</div>





</div>





<!-- =====================================================

     WELCOME

     ===================================================== -->



<div class="welcome-card">





<div class="welcome-content">





<div class="welcome-label">

    RakshakGIS Command Center

</div>





<div class="welcome-title">



Welcome back,

<?= htmlspecialchars(

    $user["name"]

    ??

    "Administrator"

) ?>



</div>





<div class="welcome-description">



Monitor disaster risk across registered habitations,

review high-risk areas and coordinate safe relocation

planning from one central dashboard.



</div>





</div>





</div>





<!-- =====================================================

     STATISTICS

     ===================================================== -->



<div class="dashboard-stats">





<!-- HABITATIONS -->



<div class="dashboard-stat">



<div class="dashboard-stat-top">



<div class="dashboard-stat-icon">

    ⌖

</div>



</div>





<div class="dashboard-stat-label">

    Total Habitations

</div>





<div class="dashboard-stat-value">



<?= number_format(

    $totalHabitations

) ?>



</div>





<div class="dashboard-stat-description">

    Registered locations

</div>



</div>





<!-- POPULATION -->



<div class="dashboard-stat">



<div class="dashboard-stat-top">



<div class="dashboard-stat-icon">

    👥

</div>



</div>





<div class="dashboard-stat-label">

    Total Population

</div>





<div class="dashboard-stat-value">



<?= number_format(

    $totalPopulation

) ?>



</div>





<div class="dashboard-stat-description">

    Population across habitations

</div>



</div>





<!-- HIGH RISK -->



<div class="dashboard-stat">



<div class="dashboard-stat-top">



<div class="

    dashboard-stat-icon

    dashboard-stat-danger

">



    ⚠



</div>



</div>





<div class="dashboard-stat-label">

    High Risk

</div>





<div class="

    dashboard-stat-value

    <?= $highRisk > 0

        ? "risk-high"

        : "" ?>"

    style="

        background:none;

        padding:0;

    "

>



<?= number_format(

    $highRisk

) ?>



</div>





<div class="dashboard-stat-description">

    High-risk habitations

</div>



</div>





<!-- RED ZONES -->



<div class="dashboard-stat">



<div class="dashboard-stat-top">



<div class="

    dashboard-stat-icon

    dashboard-stat-warning

">



    ●



</div>



</div>





<div class="dashboard-stat-label">

    Red Zones

</div>





<div class="

    dashboard-stat-value

    <?= $redZones > 0

        ? "risk-high"

        : "" ?>"

    style="

        background:none;

        padding:0;

    "

>



<?= number_format(

    $redZones

) ?>



</div>





<div class="dashboard-stat-description">

    Habitations requiring attention

</div>



</div>





</div>





<!-- =====================================================

     MAIN GRID

     ===================================================== -->



<div class="dashboard-grid">





<!-- =====================================================

     LEFT COLUMN

     ===================================================== -->



<div>





<!-- RISK OVERVIEW -->



<div class="dashboard-card">





<div class="dashboard-card-header">



<div>



<div class="dashboard-card-title">

    Risk Overview

</div>



<div class="dashboard-card-subtitle">

    Latest assessment by habitation

</div>



</div>





<a

    href="pages/risk_assessment.php"

    class="dashboard-card-link"

>

    View Assessments →

</a>



</div>





<div class="dashboard-card-body">





<div class="risk-overview-row">





<div class="risk-overview-header">



<div class="risk-overview-label">



<span class="risk-dot risk-dot-high"></span>



High Risk



</div>



<div class="risk-overview-number">



<?= $highRisk ?>



</div>



</div>





<div class="progress">



<div

    class="progress-bar progress-high"

    style="

        width: <?= max(

            $highPercent,

            $highRisk > 0 ? 3 : 0

        ) ?>%;

    "

></div>



</div>



</div>





<div class="risk-overview-row">





<div class="risk-overview-header">



<div class="risk-overview-label">



<span class="risk-dot risk-dot-medium"></span>



Medium Risk



</div>



<div class="risk-overview-number">



<?= $mediumRisk ?>



</div>



</div>





<div class="progress">



<div

    class="progress-bar progress-medium"

    style="

        width: <?= max(

            $mediumPercent,

            $mediumRisk > 0 ? 3 : 0

        ) ?>%;

    "

></div>



</div>



</div>





<div class="risk-overview-row">





<div class="risk-overview-header">



<div class="risk-overview-label">



<span class="risk-dot risk-dot-low"></span>



Low Risk



</div>



<div class="risk-overview-number">



<?= $lowRisk ?>



</div>



</div>





<div class="progress">



<div

    class="progress-bar progress-low"

    style="

        width: <?= max(

            $lowPercent,

            $lowRisk > 0 ? 3 : 0

        ) ?>%;

    "

></div>



</div>



</div>





<?php if ($unassessed > 0): ?>





<div

    style="

        margin-top:16px;

        padding:10px 12px;

        border-radius:8px;

        background:#f8fafc;

        font-size:10px;

        color:#64748b;

    "

>



<strong>

    <?= $unassessed ?>

</strong>



habitation(s) currently have no risk assessment.



</div>





<?php endif; ?>





</div>



</div>





<!-- HIGH RISK HABITATIONS -->



<div class="dashboard-card">





<div class="dashboard-card-header">



<div>



<div class="dashboard-card-title">

    High-Risk Habitations

</div>



<div class="dashboard-card-subtitle">

    Areas requiring priority attention

</div>



</div>





<a

    href="pages/habitations.php"

    class="dashboard-card-link"

>

    View All →

</a>



</div>





<?php if (

    empty($highRiskHabitations)

): ?>





<div class="empty-state">



No high-risk habitations found.



</div>





<?php else: ?>





<div

    class="table-wrapper"

>





<table class="risk-table">





<thead>



<tr>



<th>

    Habitation

</th>



<th>

    Population

</th>



<th>

    Score

</th>



<th>

    Status

</th>



<th>

    Priority

</th>



</tr>



</thead>





<tbody>





<?php foreach (

    $highRiskHabitations

    as $habitation

):

?>





<tr>





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





<td>



<?= number_format(

    (int)

    $habitation["population"]

) ?>



</td>





<td>



<span class="score">



<?= number_format(

    (float)

    $habitation["risk_score"],

    1

) ?>



</span>



</td>





<td>



<span class="

    badge

    <?= riskClass(

        $habitation["risk_level"]

    ) ?>"

>



<?= htmlspecialchars(

    $habitation["risk_level"]

) ?>



</span>





<?php if (

    (int)

    $habitation["red_zone"] === 1

): ?>



<div

    class="red-zone"

    style="

        font-size:8px;

        margin-top:4px;

    "

>

    ● RED ZONE

</div>



<?php endif; ?>





</td>





<td>



<span class="

    badge

    <?= priorityClass(

        $habitation["relocation_priority"]

    ) ?>"

>



<?= htmlspecialchars(

    $habitation[

        "relocation_priority"

    ]

) ?>



</span>



</td>





</tr>





<?php endforeach; ?>





</tbody>



</table>





</div>





<?php endif; ?>





</div>





</div>





<!-- =====================================================

     RIGHT COLUMN

     ===================================================== -->



<div>





<!-- RELOCATION OVERVIEW -->



<div class="dashboard-card">





<div class="dashboard-card-header">



<div>



<div class="dashboard-card-title">

    Relocation Overview

</div>



<div class="dashboard-card-subtitle">

    Current relocation plan status

</div>



</div>





<a

    href="pages/relocation_plans.php"

    class="dashboard-card-link"

>

    View Plans →

</a>



</div>





<div class="dashboard-card-body">





<div class="relocation-grid">





<div class="relocation-box">



<div class="relocation-label">

    Total Plans

</div>



<div class="relocation-value">

    <?= $totalPlans ?>

</div>



</div>





<div class="relocation-box">



<div class="relocation-label">

    Approved

</div>



<div class="

    relocation-value

    relocation-blue

">



<?= $approvedPlans ?>



</div>



</div>





<div class="relocation-box">



<div class="relocation-label">

    In Progress

</div>



<div class="

    relocation-value

    relocation-orange

">



<?= $inProgressPlans ?>



</div>



</div>





<div class="relocation-box">



<div class="relocation-label">

    Completed

</div>



<div class="

    relocation-value

    relocation-green

">



<?= $completedPlans ?>



</div>



</div>





</div>





<?php if (

    $cancelledPlans > 0

): ?>



<div

    style="

        margin-top:10px;

        font-size:9px;

        color:#64748b;

    "

>



<?= $cancelledPlans ?>



cancelled plan(s)



</div>



<?php endif; ?>





</div>



</div>





<!-- CAPACITY -->



<div class="dashboard-card">





<div class="dashboard-card-header">



<div>



<div class="dashboard-card-title">

    Safe Relocation Capacity

</div>



<div class="dashboard-card-subtitle">

    Available across registered sites

</div>



</div>





<a

    href="pages/relocation_sites.php"

    class="dashboard-card-link"

>

    View Sites →

</a>



</div>





<div class="dashboard-card-body">





<div class="capacity-number">



<div class="capacity-value">



<?= number_format(

    $availableCapacity

) ?>



</div>





<div class="capacity-label">

    people available

</div>



</div>





<?php



$capacityPercent =

    $totalCapacity > 0

        ? round(

            ($occupiedCapacity /

            $totalCapacity) * 100

        )

        : 0;



?>





<div class="capacity-bar">



<div

    class="capacity-fill"

    style="

        width: <?= min(

            max(

                $capacityPercent,

                0

            ),

            100

        ) ?>%;

    "

></div>



</div>





<div class="capacity-meta">



<span>



<?= number_format(

    $occupiedCapacity

) ?>



occupied



</span>





<span>



<?= number_format(

    $totalCapacity

) ?>



total capacity



</span>



</div>





</div>



</div>





<!-- QUICK ACTIONS -->



<div class="dashboard-card">





<div class="dashboard-card-header">



<div>



<div class="dashboard-card-title">

    Quick Actions

</div>



<div class="dashboard-card-subtitle">

    Frequently used modules

</div>



</div>



</div>





<div class="dashboard-card-body">





<div class="quick-grid">





<a

    href="pages/habitations.php"

    class="quick-link"

>



<div class="quick-icon">

    ⌖

</div>



<div class="quick-title">

    Habitations

</div>



<div class="quick-description">

    Manage locations

</div>



</a>





<a

    href="pages/risk_assessment.php"

    class="quick-link"

>



<div class="quick-icon">

    ⚠

</div>



<div class="quick-title">

    Assess Risk

</div>



<div class="quick-description">

    Evaluate disaster risk

</div>



</a>





<a

    href="pages/risk_map.php"

    class="quick-link"

>



<div class="quick-icon">

    ◎

</div>



<div class="quick-title">

    Risk Map

</div>



<div class="quick-description">

    View geographic risk

</div>



</a>





<a

    href="pages/reports.php"

    class="quick-link"

>



<div class="quick-icon">

    ▤

</div>



<div class="quick-title">

    Reports

</div>



<div class="quick-description">

    Generate reports

</div>



</a>





</div>





</div>



</div>





<!-- RECENT ASSESSMENTS -->



<div class="dashboard-card">





<div class="dashboard-card-header">



<div>



<div class="dashboard-card-title">

    Recent Assessments

</div>



<div class="dashboard-card-subtitle">

    Latest risk evaluation activity

</div>



</div>





<a

    href="pages/risk_assessment.php"

    class="dashboard-card-link"

>

    View All →

</a>



</div>





<div class="dashboard-card-body">





<?php if (

    empty($recentAssessments)

): ?>





<div class="empty-state">



No assessments available yet.



</div>





<?php else: ?>





<?php foreach (

    $recentAssessments

    as $assessment

):

?>





<div class="assessment-item">





<div>



<div class="assessment-name">



<?= htmlspecialchars(

    $assessment["name"]

) ?>



</div>





<div class="assessment-date">



<?= htmlspecialchars(

    $assessment["district"]

) ?>



•



<?= date(

    "d M Y",

    strtotime(

        $assessment["assessed_at"]

    )

) ?>



</div>



</div>





<div class="assessment-right">





<div

    style="

        font-size:12px;

        font-weight:900;

        margin-bottom:4px;

    "

>



<?= number_format(

    (float)

    $assessment["risk_score"],

    1

) ?>



</div>





<span class="

    badge

    <?= riskClass(

        $assessment["risk_level"]

    ) ?>"

>



<?= htmlspecialchars(

    $assessment["risk_level"]

) ?>



</span>





</div>





</div>





<?php endforeach; ?>





<?php endif; ?>





</div>



</div>





</div>





</div>





</div>





</main>





</div>





</body>



</html>
