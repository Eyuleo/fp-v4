ALTER TABLE payments
    MODIFY COLUMN order_id INT UNSIGNED NULL,
    MODIFY COLUMN stripe_payment_intent_id VARCHAR(255) NULL;