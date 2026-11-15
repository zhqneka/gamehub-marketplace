<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Database\Connection;
use PDO;
final class ProductController
{
    public function index(): array
    {
        $pdo = Connection::getConnection();
        $sql = "
            SELECT
                p.id,
                p.name,
                p.description,
                p.platform,
                p.region,
                p.image_url,
                o.id AS offer_id,
                o.price,
                o.stock,
                s.name AS seller_name,
                s.rating AS seller_rating
            FROM products p
            LEFT JOIN offers o ON p.id = o.product_id
            LEFT JOIN sellers s ON o.seller_id = s.id
            ORDER BY p.id ASC, o.price ASC
        ";

        $statement = $pdo->query($sql);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $products = [];
        foreach ($rows as $row) {
            $productId = (int) $row['id'];
            if (!isset($products[$productId])) {
                $products[$productId] = [
                    'id' => $productId,
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'platform' => $row['platform'],
                    'region' => $row['region'],
                    'image_url' => $row['image_url'],
                    'offers' => [],
                ];
            }
            if ($row['offer_id'] !== null) {
                $products[$productId]['offers'][] = [
                    'offer_id' => (int) $row['offer_id'],
                    'price' => (float) $row['price'],
                    'stock' => (int) $row['stock'],
                    'seller_name' => $row['seller_name'],
                    'seller_rating' => (float) $row['seller_rating'],
                ];
            }
        }
        return [
            'products' => array_values($products),
        ];
    }

    public function show(string $id): array
{
        $pdo = Connection::getConnection();

        $sql = "
                SELECT
                p.id,
                p.name,
                p.description,
                p.platform,
                p.region,
                p.image_url,
                o.id AS offer_id,
                o.price,
                o.stock,
                s.name AS seller_name,
                s.rating AS seller_rating
            FROM products p
            LEFT JOIN offers o ON p.id = o.product_id
            LEFT JOIN sellers s ON o.seller_id = s.id
            WHERE p.id = :id
            ORDER BY o.price ASC
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute(['id' => $id]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return ['error' => 'Product not found'];
        }

        $product = [
            'id' => (int) $rows[0]['id'],
            'name' => $rows[0]['name'],
            'description' => $rows[0]['description'],
            'platform' => $rows[0]['platform'],
            'region' => $rows[0]['region'],
            'image_url' => $rows[0]['image_url'],
            'offers' => [],
        ];

        foreach ($rows as $row) {
            if ($row['offer_id'] !== null) {
                $product['offers'][] = [
                    'offer_id' => (int) $row['offer_id'],
                    'price' => (float) $row['price'],
                    'stock' => (int) $row['stock'],
                    'seller_name' => $row['seller_name'],
                    'seller_rating' => (float) $row['seller_rating'],
                ];
            }
        }

        return ['product' => $product];
    }

}