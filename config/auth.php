<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/**
 * Check whether user is logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}


/**
 * Require authentication.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit;
    }
}


/**
 * Get logged-in user.
 */
function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}


/**
 * Require specific role.
 */
function requireRole(array $roles): void
{
    requireLogin();

    $user = currentUser();

    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);

        echo "Access denied.";
        exit;
    }
}