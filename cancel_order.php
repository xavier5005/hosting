<?php
require 'db.php';

if (!isset($_POST['order_id'])) {
    die("ID de pedido no especificado para cancelar.");
}

$id = (int)$_POST['order_id'];

try {

    $pdo->beginTransaction();

    $stmt_items = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt_items->execute([$id]);

    $stmt_order = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt_order->execute([$id]);

    $pdo->commit();
    
    header('Location: index.php?status=cancelled');
    exit;

} catch (PDOException $e) {
    
    $pdo->rollBack();
    die("Error al cancelar el pedido: " . $e->getMessage());
}
?>
