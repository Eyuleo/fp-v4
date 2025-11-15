<?php

/**
 * Order Policy
 *
 * Handles authorization for order-related actions
 */
class OrderPolicy implements Policy
{
    /**
     * Check if user can view an order
     *
     * @param array $user The authenticated user
     * @param array $order The order to check
     * @return bool
     */
    public function canView(array $user, array $order): bool
    {
        // Admin can view all orders
        if ($user['role'] === 'admin') {
            return true;
        }

        // Client can view their own orders
        if ($user['role'] === 'client' && $order['client_id'] == $user['id']) {
            return true;
        }

        // Student can view orders for their services
        if ($user['role'] === 'student' && $order['student_id'] == $user['id']) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can deliver an order
     *
     * @param array $user The authenticated user
     * @param array $order The order to check
     * @return bool
     */
    public function canDeliver(array $user, array $order): bool
    {
        // Only students can deliver orders
        if ($user['role'] !== 'student') {
            return false;
        }

        // Must be the student who owns the service
        if ($order['student_id'] != $user['id']) {
            return false;
        }

        // Order must be in in_progress or revision_requested status
        if (! in_array($order['status'], ['in_progress', 'revision_requested'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can request revision on an order
     *
     * @param array $user The authenticated user
     * @param array $order The order to check
     * @return bool
     */
    public function canRequestRevision(array $user, array $order): bool
    {
        // Only clients can request revisions
        if ($user['role'] !== 'client') {
            return false;
        }

        // Must be the client who placed the order
        if ($order['client_id'] != $user['id']) {
            return false;
        }

        // Order must be in delivered status
        if ($order['status'] !== 'delivered') {
            return false;
        }

        // Must not exceed maximum revisions
        $maxRevisions = $order['max_revisions'] ?? 3;
        if ($order['revision_count'] >= $maxRevisions) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can complete an order
     *
     * @param array $user The authenticated user
     * @param array $order The order to check
     * @return bool
     */
    public function canComplete(array $user, array $order): bool
    {
        // Only clients can complete orders
        if ($user['role'] !== 'client') {
            return false;
        }

        // Must be the client who placed the order
        if ($order['client_id'] != $user['id']) {
            return false;
        }

        // Order must be in delivered status
        if ($order['status'] !== 'delivered') {
            return false;
        }

        return true;
    }

    /**
     * Check if user can cancel an order
     *
     * @param array $user The authenticated user
     * @param array $order The order to check
     * @return bool
     */
    public function canCancel(array $user, array $order): bool
    {
        return $user['role'] === 'admin';
    }
}
