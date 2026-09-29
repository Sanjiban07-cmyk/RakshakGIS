<?php

/**
 * RakshakGIS Risk Assessment Engine
 *
 * Risk components:
 * Flood Risk              = 30%
 * Landslide Risk          = 25%
 * Hazard History          = 20%
 * Population Vulnerability = 25%
 *
 * Final score: 0 - 100
 *
 * 0-30   = LOW
 * 31-60  = MEDIUM
 * 61-100 = HIGH
 */

function calculateRisk(
    float $floodRisk,
    float $landslideRisk,
    float $hazardHistory,
    float $populationVulnerability
): array {

    // Keep every input within 0-100
    $floodRisk = max(0, min(100, $floodRisk));
    $landslideRisk = max(0, min(100, $landslideRisk));
    $hazardHistory = max(0, min(100, $hazardHistory));
    $populationVulnerability = max(0, min(100, $populationVulnerability));

    // Weighted risk calculation
    $score =
        ($floodRisk * 0.30) +
        ($landslideRisk * 0.25) +
        ($hazardHistory * 0.20) +
        ($populationVulnerability * 0.25);

    $score = round($score, 2);

    // Risk level
    if ($score <= 30) {
        $riskLevel = "LOW";
    } elseif ($score <= 60) {
        $riskLevel = "MEDIUM";
    } else {
        $riskLevel = "HIGH";
    }

    return [
        "score" => $score,
        "level" => $riskLevel,
        "components" => [
            "flood_risk" => $floodRisk,
            "landslide_risk" => $landslideRisk,
            "hazard_history" => $hazardHistory,
            "population_vulnerability" => $populationVulnerability
        ]
    ];
}
