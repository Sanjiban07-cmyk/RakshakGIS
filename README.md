# RakshakGIS

### Disaster Risk Assessment & Safe Relocation Planner

RakshakGIS is a web-based disaster risk assessment and safe relocation planning system designed to help identify high-risk habitations, assess disaster vulnerability, and recommend suitable relocation sites based on population, capacity, safety, and geographical distance.

---

## 🚨 About the Project

RakshakGIS was developed as a solution for **Smart India Hackathon (SIH) Problem Statement 26191**.

The system provides a centralized platform for:

- Habitation management
- Disaster risk assessment
- Risk-level classification
- Red-zone identification
- Relocation priority calculation
- Safe relocation site recommendation
- Relocation plan management
- Relocation status tracking
- Reports and monitoring
- Interactive geographical visualization

The goal is to support structured, data-driven disaster preparedness and relocation planning.

---

## 🎯 Objectives

- Identify disaster-prone habitations.
- Calculate a combined disaster risk score.
- Classify habitations into LOW, MEDIUM, and HIGH risk.
- Identify red-zone habitations.
- Determine relocation priority.
- Find suitable safe relocation sites.
- Calculate geographical distance between habitations and relocation sites.
- Check available relocation capacity.
- Generate and manage relocation plans.
- Track relocation progress.
- Provide a centralized dashboard for disaster management.

---

## ✨ Key Features

### 🏘️ Habitation Management

Manage habitation information including:

- Habitation name
- District
- State
- Population
- Latitude
- Longitude
- Flood risk
- Landslide risk
- Hazard history
- Population vulnerability

---

### ⚠️ Risk Assessment

RakshakGIS calculates disaster risk using multiple factors:

- Flood Risk
- Landslide Risk
- Hazard History
- Population Vulnerability

The system generates:

- Risk Score
- Risk Level
- Red-Zone Status
- Relocation Priority
- Assessment Notes

---

### 🗺️ Interactive Map

The map module provides geographical visualization of habitations and disaster-risk information.

It helps users understand:

- Habitation locations
- Risk distribution
- Geographical positioning
- Relocation locations

---

### 🚚 Safe Relocation Planner

The relocation planner identifies suitable relocation sites based on:

- Available capacity
- Population requiring relocation
- Geographical distance
- Site safety information

Distance is calculated dynamically using geographical coordinates.

---

### 📍 Safe Site Recommendation

The system calculates the distance between a habitation and available relocation sites using the **Haversine formula**.

Suitable sites are selected based on:

```text
Available Capacity
        +
Geographical Distance
        +
Site Safety Information