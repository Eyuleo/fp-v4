ALTER TABLE payments
    MODIFY order_id INT UNSIGNED NULL,
    MODIFY stripe_payment_intent_id VARCHAR(255) NULL;