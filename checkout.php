<?php

session_start();
require 'db.php';

if (empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit;
}

$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, price_unit FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$rows = $stmt->fetchAll();

$total = 0;
$prices = [];
foreach ($rows as $r) {
    $prices[$r['id']] = $r['price_unit'];
}
foreach ($_SESSION['cart'] as $pid => $qty) {
    $total += ($prices[$pid] ?? 0) * $qty;
}

$customer = isset($_POST['customer_name']) && $_POST['customer_name'] !== '' ? trim($_POST['customer_name']) : 'Cliente';


$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO orders (customer_name, total) VALUES (?, ?)");
    $stmt->execute([$customer, $total]);
    $orderId = $pdo->lastInsertId();

    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $pid => $qty) {
        $price = $prices[$pid] ?? 0;
        $stmtItem->execute([$orderId, $pid, $qty, $price]);
    }

    $pdo->commit();

    $_SESSION['cart'] = [];

    header("Location: ticket.php?id=$orderId");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error al guardar el pedido: " . htmlspecialchars($e->getMessage());
    exit;
}