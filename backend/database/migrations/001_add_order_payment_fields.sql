ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS customer_email VARCHAR(255),
    ADD COLUMN IF NOT EXISTS payment_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(100),
    ADD COLUMN IF NOT EXISTS delivery_status VARCHAR(30) NOT NULL DEFAULT 'pending';

CREATE UNIQUE INDEX IF NOT EXISTS idx_orders_payment_reference
    ON orders(payment_reference)
    WHERE payment_reference IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_digital_keys_offer_status
    ON digital_keys(offer_id, status);