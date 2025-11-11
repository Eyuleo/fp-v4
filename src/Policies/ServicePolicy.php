<?php

/**
 * Service Policy
 *
 * Handles authorization for service-related actions
 */
class ServicePolicy implements Policy
{
    /**
     * Check if user can edit a service
     *
     * @param array $user The authenticated user
     * @param array $service The service to check
     * @return bool
     */
    public function canEdit(array $user, array $service): bool
    {
        // Admin can edit any service
        if ($user['role'] === 'admin') {
            return true;
        }

        // Only students can edit services
        if ($user['role'] !== 'student') {
            return false;
        }

        // Must be the owner of the service
        if ($service['student_id'] != $user['id']) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can delete a service
     *
     * @param array $user The authenticated user
     * @param array $service The service to check
     * @return bool
     */
    public function canDelete(array $user, array $service): bool
    {
        // Admin can delete any service
        if ($user['role'] === 'admin') {
            return true;
        }

        // Only students can delete services
        if ($user['role'] !== 'student') {
            return false;
        }

        // Must be the owner of the service
        if ($service['student_id'] != $user['id']) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can activate/pause a service
     *
     * @param array $user The authenticated user
     * @param array $service The service to check
     * @return bool
     */
    public function canActivate(array $user, array $service): bool
    {
        // Admin can activate/pause any service
        if ($user['role'] === 'admin') {
            return true;
        }

        // Only students can activate/pause services
        if ($user['role'] !== 'student') {
            return false;
        }

        // Must be the owner of the service
        if ($service['student_id'] != $user['id']) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can create a service
     *
     * @param array $user The authenticated user
     * @return bool
     */
    public function canCreate(array $user): bool
    {
        // Only students can create services
        return $user['role'] === 'student';
    }
}
