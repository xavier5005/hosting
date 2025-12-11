<?php

require 'db.php';
if (!isset($_GET['id'])) {
    echo "Pedido no especificado.";
    exit;
}
$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) {
    echo "Pedido no encontrado.";
    exit;
}

$stmt = $pdo->prepare("
  SELECT oi.qty, oi.price, p.name
  FROM order_items oi
  JOIN products p ON p.id = oi.product_id
  WHERE oi.order_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<!-- Seguridad: sanitizado el ID del pedido -->
<title>Ticket - Pedido #<?php echo htmlspecialchars($order['id']); ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Arial;background:#000;color:#fff;padding:20px;}
.ticket{max-width:420px;margin:0 auto;background:#050511;padding:18px;border-radius:8px;border:1px solid #222;}
h2{color:#ff00ea;text-shadow:0 0 8px #ff00ea;}
.line{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed rgba(255,255,255,0.06);}
.total{font-weight:bold;color:#39ff14;}
.btn{display:block;margin-top:12px;padding:10px;text-align:center;border-radius:6px;text-decoration:none;cursor:pointer;width:100%;box-sizing:border-box;}
.print-btn{background:#00eaff;color:#000;}
.cancel-btn{background:#ff4d4d;color:#fff;border:none;} /* Estilo para el nuevo botón */
.back-link{display:block;margin-top:8px;color:#aaa;text-decoration:none;text-align:center;}
</style>
</head>
<body>
<div class="ticket">
    <h2>Papelería 5 letras</h2>
    <p>Pedido #: <?php echo htmlspecialchars($order['id']); ?></p>
    <p>Cliente: <?php echo htmlspecialchars($order['customer_name']); ?></p>
    <p>Fecha: <?php echo htmlspecialchars($order['created_at']); ?></p>

    <?php foreach($items as $it): ?>
        <div class="line">
            <div><?php echo htmlspecialchars($it['name']); ?> x<?php echo htmlspecialchars($it['qty']); ?></div>
            <div>$ <?php echo number_format($it['price'] * $it['qty'],2); ?></div>
        </div>
    <?php endforeach; ?>

    <div class="line total">
        <div>Total</div>
        <div>$ <?php echo number_format($order['total'],2); ?></div>
    </div>

    <!-- Botón de Imprimir -->
    <a class="btn print-btn" href="#" onclick="window.print();return false;">Imprimir Ticket</a>

    <!-- Formulario con el nuevo botón de Cancelar -->
    <form action="cancel_order.php" method="POST">
        <!-- Campo oculto para pasar el ID de forma segura mediante POST -->
        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
        
        <!-- Botón de Submit con confirmación JavaScript -->
        <button type="submit" class="btn cancel-btn" onclick="return confirm('¿Está seguro de que desea cancelar este pedido? Esta acción es irreversible.');">
            Cancelar Compra
        </button>
    </form>

    <!-- Enlace para volver al catálogo -->
    <a class="back-link" href="index.php">Volver al catálogo</a>
</div>
</body>
</html>
