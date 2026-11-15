<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use PDO;
use Throwable;

final class OrderController
{
    public function index(): array
    {
        $authenticatedUser = (new AuthController())->authenticatedUser();
        if (!$authenticatedUser) {
            return [
                '_http_status' => 401,
                'error' => 'Please log in to view your orders.',
            ];
        }

        try {
            $pdo = Connection::getConnection();
            $orderStatement = $pdo->prepare(
                'SELECT
                    id,
                    customer_email,
                    total,
                    status,
                    payment_status,
                    payment_reference,
                    delivery_status,
                    created_at
                FROM orders
                WHERE user_id = :user_id
                ORDER BY created_at DESC'
            );

            $orderStatement->execute([
                'user_id' => (int) $authenticatedUser['id'],
            ]);

            $orders = $orderStatement->fetchAll(PDO::FETCH_ASSOC);
            $itemStatement = $pdo->prepare(
                'SELECT id, offer_id, quantity, unit_price
                FROM order_items
                WHERE order_id = :order_id
                ORDER BY id'
            );

            $keyStatement = $pdo->prepare(
                "SELECT key_value
                FROM digital_keys
                WHERE order_item_id = :order_item_id
                AND status = 'assigned'
                ORDER BY id"
            );

            foreach ($orders as &$order) {
                $itemStatement->execute([
                    'order_id' => $order['id'],
                ]);
                $items = $itemStatement->fetchAll(PDO::FETCH_ASSOC);
                foreach ($items as &$item) {
                    $keyStatement->execute([
                        'order_item_id' => $item['id'],
                    ]);

                    $item['digital_keys'] = $keyStatement->fetchAll(
                        PDO::FETCH_COLUMN
                    );
                }
                unset($item);
                $order['items'] = $items;
                $order['total'] = (float) $order['total'];
            }

            unset($order);
            return [
                'success' => true,
                'orders' => $orders,
            ];
        } catch (Throwable $exception) {
            error_log(
                'Order history failed: ' . $exception->getMessage()
            );
            return [
                '_http_status' => 500,
                'error' => 'Could not retrieve order history.',
            ];
        }
    }

    public function store(): array
    {
        $authenticatedUser = (new AuthController())->authenticatedUser();

        if (!$authenticatedUser) {
            return [
                '_http_status' => 401,
                'error' => 'Please log in before placing an order.',
            ];
        }

        $userId = (int) $authenticatedUser['id'];
        $email = $authenticatedUser['email'];
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody ?: '', true);

        if (!is_array($data)) {
            return [
                '_http_status' => 400,
                'error' => 'Request body must contain valid JSON.',
            ];
        }

        $email = trim((string) $authenticatedUser['email']);
        $items = $data['items'] ?? null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                '_http_status' => 422,
                'error' => 'A valid email address is required.',
            ];
        }

        if (!is_array($items) || $items === []) {
            return [
                '_http_status' => 422,
                'error' => 'The order must contain at least one item.',
            ];
        }

        $quantities = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                return [
                    '_http_status' => 422,
                    'error' => 'Invalid order item.',
                ];
            }

            $offerId = filter_var(
                $item['offer_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $quantity = filter_var(
                $item['quantity'] ?? 1,
                FILTER_VALIDATE_INT
            );

            if (
                $offerId === false ||
                $offerId < 1 ||
                $quantity === false ||
                $quantity < 1 ||
                $quantity > 20
            ) {
                return [
                    '_http_status' => 422,
                    'error' => 'Invalid offer ID or quantity.',
                ];
            }

            $quantities[$offerId] = ($quantities[$offerId] ?? 0) + $quantity;

            if ($quantities[$offerId] > 20) {
                return [
                    '_http_status' => 422,
                    'error' => 'Maximum quantity per offer is 20.',
                ];
            }
        }

        $pdo = null;

        try {
            $pdo = Connection::getConnection();
            $pdo->beginTransaction();

            $validatedItems = [];
            $total = 0.0;

            // Lock offers and verify current prices and stock.
            $offerStatement = $pdo->prepare(
                'SELECT id, price, stock
                 FROM offers
                 WHERE id = :id
                 FOR UPDATE'
            );

            foreach ($quantities as $offerId => $quantity) {
                $offerStatement->execute(['id' => $offerId]);
                $offer = $offerStatement->fetch(PDO::FETCH_ASSOC);

                if (!$offer) {
                    $pdo->rollBack();

                    return [
                        '_http_status' => 404,
                        'error' => "Offer {$offerId} was not found.",
                    ];
                }

                if ((int) $offer['stock'] < $quantity) {
                    $pdo->rollBack();

                    return [
                        '_http_status' => 409,
                        'error' => "Not enough stock for offer {$offerId}.",
                    ];
                }
                $keyAvailability = $pdo->prepare(
                    "SELECT COUNT(*)
                    FROM digital_keys
                    WHERE offer_id = :offer_id
                    AND status = 'available'"
                );

                $keyAvailability->execute([
                    'offer_id' => $offerId,
                ]);

                $availableKeys = (int) $keyAvailability->fetchColumn();

                if ($availableKeys < $quantity) {
                    $pdo->rollBack();

                    return [
                        '_http_status' => 409,
                        'error' => "Not enough digital keys available for offer {$offerId}.",
                    ];
                }

                $unitPrice = (float) $offer['price'];
                $total += $unitPrice * $quantity;

                $validatedItems[] = [
                    'offer_id' => (int) $offer['id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ];
            }

            // Simulated payment reference generated by the backend.
            $paymentReference = 'SIM-' . strtoupper(
                bin2hex(random_bytes(8))
            );

            $orderStatement = $pdo->prepare(
                "INSERT INTO orders
                    (
                        user_id,
                        customer_email,
                        total,
                        status,
                        payment_status,
                        payment_reference,
                        delivery_status
                    )
                 VALUES
                    (
                        :user_id,
                        :email,
                        :total,
                        'paid',
                        'succeeded',
                        :payment_reference,
                        'pending'
                    )
                 RETURNING id, created_at"
            );

            $orderStatement->execute([
                'user_id' => $userId,
                'email' => $email,
                'total' => number_format($total, 2, '.', ''),
                'payment_reference' => $paymentReference,
            ]);

            $order = $orderStatement->fetch(PDO::FETCH_ASSOC);
            $orderId = (int) $order['id'];

            $itemStatement = $pdo->prepare(
                'INSERT INTO order_items
                    (order_id, offer_id, quantity, unit_price)
                 VALUES
                    (:order_id, :offer_id, :quantity, :unit_price)
                 RETURNING id'
            );

            $stockStatement = $pdo->prepare(
                'UPDATE offers
                 SET stock = stock - :quantity
                 WHERE id = :offer_id'
            );

            $keySelect = $pdo->prepare(
                "SELECT id, key_value
                 FROM digital_keys
                 WHERE offer_id = :offer_id
                   AND status = 'available'
                 ORDER BY id
                 LIMIT :quantity
                 FOR UPDATE"
            );

            $keyUpdate = $pdo->prepare(
                "UPDATE digital_keys
                 SET order_item_id = :order_item_id,
                     status = 'assigned'
                 WHERE id = :id"
            );

            $deliveredKeys = [];
            $allKeysAssigned = true;

            foreach ($validatedItems as $item) {
                $itemStatement->execute([
                    'order_id' => $orderId,
                    'offer_id' => $item['offer_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => number_format(
                        $item['unit_price'],
                        2,
                        '.',
                        ''
                    ),
                ]);

                $orderItemId = (int) $itemStatement->fetchColumn();

                $stockStatement->execute([
                    'quantity' => $item['quantity'],
                    'offer_id' => $item['offer_id'],
                ]);

                // Assign available digital keys to this order item.
                $keySelect->bindValue(
                    ':offer_id',
                    $item['offer_id'],
                    PDO::PARAM_INT
                );

                $keySelect->bindValue(
                    ':quantity',
                    $item['quantity'],
                    PDO::PARAM_INT
                );

                $keySelect->execute();
                $keys = $keySelect->fetchAll(PDO::FETCH_ASSOC);

                foreach ($keys as $key) {
                    $keyUpdate->execute([
                        'order_item_id' => $orderItemId,
                        'id' => $key['id'],
                    ]);

                    $deliveredKeys[] = [
                        'offer_id' => $item['offer_id'],
                        'key' => $key['key_value'],
                    ];
                }

                if (count($keys) < $item['quantity']) {
                    $allKeysAssigned = false;
                }
            }

            $deliveryStatus = $allKeysAssigned
                ? 'delivered'
                : 'pending';

            $updateOrder = $pdo->prepare(
                'UPDATE orders
                 SET delivery_status = :delivery_status
                 WHERE id = :id'
            );

            $updateOrder->execute([
                'delivery_status' => $deliveryStatus,
                'id' => $orderId,
            ]);

            $pdo->commit();

            return [
                '_http_status' => 201,
                'success' => true,
                'message' => 'Order created and simulated payment confirmed.',
                'order' => [
                    'id' => $orderId,
                    'email' => $email,
                    'total' => round($total, 2),
                    'status' => 'paid',
                    'payment_status' => 'succeeded',
                    'payment_reference' => $paymentReference,
                    'delivery_status' => $deliveryStatus,
                    'created_at' => $order['created_at'],
                    'items' => $validatedItems,
                    'digital_keys' => $deliveredKeys,
                ],
            ];
        } catch (Throwable $exception) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Order creation failed: ' . $exception->getMessage()
            );

            return [
                '_http_status' => 500,
                'error' => 'The order could not be completed. No payment was confirmed.',
            ];
        }
    }
}