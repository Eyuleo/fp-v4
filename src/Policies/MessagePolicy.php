<?php

/**
 * Message Policy
 *
 * Handles authorization for message-related actions
 */
class MessagePolicy implements Policy
{
    /**
     * Check if user can send a message in an order thread
     *
     * @param array $user The authenticated user
     * @param array $order The order to check
     * @return bool
     */
    public function canSend(array $user, array $order): bool
    {
        // Admin can send messages in any order
        if ($user['role'] === 'admin') {
            return true;
        }

        // Client can send messages in their own orders
        if ($user['role'] === 'client' && $order['client_id'] == $user['id']) {
            return true;
        }

        // Student can send messages in orders for their services
        if ($user['role'] === 'student' && $order['student_id'] == $user['id']) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can view messages in an order thread
     *
     * @param array $user The authenticated user
     * @param array $order The order to check
     * @return bool
     */
    public function canView(array $user, array $order): bool
    {
        // Admin can view all messages
        if ($user['role'] === 'admin') {
            return true;
        }

        // Client can view messages in their own orders
        if ($user['role'] === 'client' && $order['client_id'] == $user['id']) {
            return true;
        }

        // Student can view messages in orders for their services
        if ($user['role'] === 'student' && $order['student_id'] == $user['id']) {
            return true;
        }

        return false;
    }
}
