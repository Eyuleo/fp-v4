<?php

require_once __DIR__ . '/../Services/PaymentService.php';
require_once __DIR__ . '/../Repositories/PaymentRepository.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Webhook Controller
 *
 * Handles webhook callbacks from external services
 */
class WebhookController
{
    private PaymentService $paymentService;
    private PDO $db;

    public function __construct()
    {
        $this->db             = getDatabaseConnection();
        $paymentRepository    = new PaymentRepository($this->db);
        $this->paymentService = new PaymentService($paymentRepository, $this->db);
    }

    /**
     * Handle Stripe webhook
     *
     * POST /webhooks/stripe
     */
    public function stripe(): void
    {
        // Get raw POST body
        $payload = @file_get_contents('php://input');

        // Get Stripe signature header
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        if (empty($payload) || empty($signature)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing payload or signature']);
            exit;
        }

        // Process webhook
        $result = $this->paymentService->processWebhook($payload, $signature);

        if (! $result['success']) {
            // Return appropriate error code
            if (isset($result['errors']['webhook'])) {
                if ($result['errors']['webhook'] === 'Invalid signature' ||
                    $result['errors']['webhook'] === 'Invalid payload') {
                    http_response_code(401);
                } else {
                    http_response_code(500);
                }
            } else {
                http_response_code(500);
            }

            echo json_encode(['error' => $result['errors']['webhook'] ?? 'Processing failed']);
            exit;
        }

        // Return 200 OK for successful processing
        http_response_code(200);
        echo json_encode(['received' => true]);
        exit;
    }
}
