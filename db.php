<?php

function get_db_connection(array $config): PDO
{
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";

    return new PDO($dsn, $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}


const ORDERS_JOIN_QUERY = <<<SQL
    SELECT
        o.order_id          AS order_id,
        c.customer_name      AS customer_name,
        p.product_name       AS product_name,
        p.weight_g           AS product_weight_g,
        p.purity             AS gold_purity,
        o.quantity           AS quantity,
        o.order_date         AS order_date
    FROM Orders o
    JOIN Customers c    ON o.customer_id = c.customer_id
    JOIN GoldProducts p ON o.product_id  = p.product_id
    ORDER BY o.order_id
SQL;

function get_orders_with_details(PDO $pdo): array
{
    $stmt = $pdo->query(ORDERS_JOIN_QUERY);
    return $stmt->fetchAll();
}
