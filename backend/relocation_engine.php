<?php

/**
 * RakshakGIS Relocation Recommendation Engine
 *
 * Considers:
 * 1. Capacity — site must accommodate the population
 * 2. Safety — LOW risk is preferred
 * 3. Distance — shorter distance is preferred
 */

function recommendRelocation(
    int $population,
    array $sites
): array {

    $recommendations = [];

    foreach ($sites as $site) {

        $availableCapacity = (int) $site['available_capacity'];

        // Site cannot accommodate the habitation
        if ($availableCapacity < $population) {
            continue;
        }

        $distance = (float) ($site['distance_from_habitation'] ?? 9999);

        // Safety score
        switch (strtoupper($site['safety_level'])) {

            case 'LOW':
                $safetyScore = 100;
                break;

            case 'MEDIUM':
                $safetyScore = 60;
                break;

            case 'HIGH':
                $safetyScore = 20;
                break;

            default:
                $safetyScore = 50;
        }

        // Capacity score
        $capacityScore = min(
            100,
            ($availableCapacity / max($population, 1)) * 100
        );

        // Distance score
        $distanceScore = max(
            0,
            100 - ($distance * 5)
        );

        /*
         * Overall recommendation score
         *
         * Safety   = 50%
         * Capacity = 30%
         * Distance = 20%
         */

        $recommendationScore =
            ($safetyScore * 0.50) +
            ($capacityScore * 0.30) +
            ($distanceScore * 0.20);

        $recommendations[] = [
            "site_id" => (int) $site['id'],
            "site_name" => $site['site_name'],
            "district" => $site['district'],
            "available_capacity" => $availableCapacity,
            "safety_level" => $site['safety_level'],
            "distance_km" => $distance,
            "recommendation_score" => round($recommendationScore, 2),
            "facilities" => $site['facilities']
        ];
    }

    // Highest recommendation score first
    usort(
        $recommendations,
        function ($a, $b) {
            return $b['recommendation_score']
                <=> $a['recommendation_score'];
        }
    );

    return $recommendations;
}