CREATE DATABASE IF NOT EXISTS rakshakgis
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE rakshakgis;

-- =========================================
-- HABITATIONS
-- =========================================

CREATE TABLE habitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    district VARCHAR(100) NOT NULL,
    state VARCHAR(100) DEFAULT 'Maharashtra',
    population INT NOT NULL DEFAULT 0,

    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,

    flood_risk DECIMAL(5,2) NOT NULL DEFAULT 0,
    landslide_risk DECIMAL(5,2) NOT NULL DEFAULT 0,
    hazard_history DECIMAL(5,2) NOT NULL DEFAULT 0,
    population_vulnerability DECIMAL(5,2) NOT NULL DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================
-- RISK ASSESSMENTS
-- =========================================

CREATE TABLE risk_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    habitation_id INT NOT NULL,

    flood_score DECIMAL(5,2) DEFAULT 0,
    landslide_score DECIMAL(5,2) DEFAULT 0,
    hazard_history_score DECIMAL(5,2) DEFAULT 0,
    vulnerability_score DECIMAL(5,2) DEFAULT 0,

    risk_score DECIMAL(5,2) NOT NULL DEFAULT 0,

    risk_level ENUM(
        'LOW',
        'MEDIUM',
        'HIGH'
    ) NOT NULL DEFAULT 'LOW',

    red_zone TINYINT(1) NOT NULL DEFAULT 0,

    relocation_priority ENUM(
        'NONE',
        'MEDIUM-TERM',
        'SHORT-TERM',
        'IMMEDIATE'
    ) NOT NULL DEFAULT 'NONE',

    assessment_notes TEXT,

    assessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_risk_habitation
        FOREIGN KEY (habitation_id)
        REFERENCES habitations(id)
        ON DELETE CASCADE
);


-- =========================================
-- SAFE RELOCATION SITES
-- =========================================

CREATE TABLE relocation_sites (
    id INT AUTO_INCREMENT PRIMARY KEY,

    site_name VARCHAR(150) NOT NULL,
    district VARCHAR(100) NOT NULL,

    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,

    total_capacity INT NOT NULL DEFAULT 0,
    occupied_capacity INT NOT NULL DEFAULT 0,

    safety_level ENUM(
        'LOW',
        'MEDIUM',
        'HIGH'
    ) NOT NULL DEFAULT 'LOW',

    distance_from_habitation DECIMAL(8,2) DEFAULT NULL,

    facilities TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================
-- RELOCATION PLANS
-- =========================================

CREATE TABLE relocation_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,

    habitation_id INT NOT NULL,
    relocation_site_id INT NOT NULL,

    population_to_relocate INT NOT NULL DEFAULT 0,

    available_capacity INT NOT NULL DEFAULT 0,

    distance_km DECIMAL(8,2) DEFAULT NULL,

    recommendation_reason TEXT,

    status ENUM(
        'PLANNED',
        'APPROVED',
        'IN_PROGRESS',
        'COMPLETED',
        'CANCELLED'
    ) NOT NULL DEFAULT 'PLANNED',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_plan_habitation
        FOREIGN KEY (habitation_id)
        REFERENCES habitations(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_plan_site
        FOREIGN KEY (relocation_site_id)
        REFERENCES relocation_sites(id)
        ON DELETE CASCADE
);


-- =========================================
-- SAMPLE HABITATIONS
-- =========================================

INSERT INTO habitations
(
    name,
    district,
    state,
    population,
    latitude,
    longitude,
    flood_risk,
    landslide_risk,
    hazard_history,
    population_vulnerability
)
VALUES
(
    'Khelarai',
    'Pune',
    'Maharashtra',
    1250,
    18.5204,
    73.8567,
    85,
    70,
    80,
    75
),
(
    'Shivapur',
    'Nashik',
    'Maharashtra',
    850,
    19.9975,
    73.7898,
    55,
    40,
    50,
    45
),
(
    'Dhanora',
    'Nagpur',
    'Maharashtra',
    620,
    21.1458,
    79.0882,
    25,
    20,
    30,
    25
);


-- =========================================
-- SAMPLE RELOCATION SITES
-- =========================================

INSERT INTO relocation_sites
(
    site_name,
    district,
    latitude,
    longitude,
    total_capacity,
    occupied_capacity,
    safety_level,
    distance_from_habitation,
    facilities
)
VALUES
(
    'Safe Site A',
    'Pune',
    18.5700,
    73.9000,
    3000,
    1200,
    'LOW',
    5.20,
    'Hospital, School, Water Supply, Electricity, Road Access'
),
(
    'Safe Site B',
    'Pune',
    18.4800,
    73.8200,
    1800,
    900,
    'LOW',
    8.50,
    'Hospital, Water Supply, Electricity'
),
(
    'Safe Site C',
    'Nashik',
    20.0100,
    73.7500,
    2500,
    700,
    'LOW',
    12.30,
    'School, Hospital, Water Supply, Transport'
);