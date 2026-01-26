<?php

namespace App\Middleware;

use App\Core\SessionSecurity;
use App\Exceptions\AuthenticationException;

/**
 * Authentication Middleware
 * 
 * Ensures user is authenticated before accessing protected routes
 */
class AuthMiddleware
{
    /**
     * Check if user is authenticated
     * 
     * @throws AuthenticationException
     * @return bool
     */
    public static function check(): bool
    {
        SessionSecurity::start();

        if (!SessionSecurity::isLoggedIn()) {
            throw new AuthenticationException('Authentication required');
        }

        return true;
    }

    /**
     * Check if user has specific role
     * 
     * @param string|array $roles
     * @throws AuthenticationException
     * @return bool
     */
    public static function checkRole($roles): bool
    {
        self::check();

        $userData = SessionSecurity::get('user_data');
        $userRole = $userData['role'] ?? null;

        $allowedRoles = is_array($roles) ? $roles : [$roles];

        if (!in_array($userRole, $allowedRoles)) {
            throw new AuthenticationException('Insufficient permissions');
        }

        return true;
    }

    /**
     * Check if user is admin
     * 
     * @throws AuthenticationException
     * @return bool
     */
    public static function checkAdmin(): bool
    {
        return self::checkRole('admin');
    }

    /**
     * Check if user is pharmacist or admin
     * 
     * @throws AuthenticationException
     * @return bool
     */
    public static function checkPharmacist(): bool
    {
        return self::checkRole(['admin', 'pharmacist']);
    }

    /**
     * Get current user ID
     * 
     * @return int|null
     */
    public static function getUserId(): ?int
    {
        SessionSecurity::start();
        return SessionSecurity::getUserId();
    }

    /**
     * Get current user data
     * 
     * @return array|null
     */
    public static function getUserData(): ?array
    {
        SessionSecurity::start();
        return SessionSecurity::get('user_data');
    }
}
