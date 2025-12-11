<?php

session_start();
require 'db.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'add' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] += 1;
        } else {
            $_SESSION['cart'][$id] = 1;
        }
        header('Location: index.php');
        exit;
    }
    if ($action === 'remove' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        unset($_SESSION['cart'][$id]);
        header('Location: index.php');
        exit;
    }
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        header('Location: index.php');
        exit;
    }
}

$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll();

$cart_items = [];
$total = 0.0;
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $pid = $row['id'];
        $qty = $_SESSION['cart'][$pid];
        $sub = $row['price_unit'] * $qty;
        $cart_items[] = [
            'product' => $row,
            'qty' => $qty,
            'sub' => $sub
        ];
        $total += $sub;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Papeleria 5 letras - Catálogo</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>

:root {
    --negro:#0a0a0f; --neon-azul:#00eaff; --neon-rosa:#ff00ea; --neon-verde:#39ff14; --blanco:#fff;
}
body{margin:0;font-family:Arial;background:var(--negro);color:var(--blanco);}
header{padding:20px;text-align:center;color:var(--neon-rosa);text-shadow:0 0 10px var(--neon-rosa);}
.container{display:flex;gap:20px;padding:20px;}
.productos{flex:1;display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:18px;}
.item{background:#0d0d18;padding:12px;border-radius:10px;border:1px solid var(--neon-azul);box-shadow:0 0 10px var(--neon-azul);text-align:center;}
.item img{width:100%;height:100px;object-fit:contain;margin-bottom:8px;}
.item h3{color:var(--neon-rosa);text-shadow:0 0 8px var(--neon-rosa);margin:8px 0;}
.item p{color:var(--neon-verde);font-weight:bold;margin:6px 0;}
.btn{display:inline-block;padding:8px 10px;border-radius:8px;border:2px solid var(--neon-verde);background:#0d0d18;color:var(--neon-verde);cursor:pointer;text-decoration:none;}
.btn:hover{background:var(--neon-verde);color:#000;}
.carrito{width:320px;background:#0d0d18;padding:16px;border-radius:10px;border:1px solid var(--neon-rosa);box-shadow:0 0 14px var(--neon-rosa);}
.carrito h2{color:var(--neon-rosa);}
.cart-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.04);}
.cart-row img{width:45px;height:45px;object-fit:contain;margin-right:8px;}
.small{font-size:14px;color:#ccc;}
.link-clear{display:block;margin-top:10px;color:#fff;text-decoration:none;padding:8px;border-radius:8px;background:#222;text-align:center;}
.checkout-form input[type="text"]{width:100%;padding:8px;margin-top:6px;border-radius:6px;border:1px solid #333;}
.checkout-form button{margin-top:8px;width:100%;}
</style>
</head>
<body>
<header><h1>Papeleria 5 letras — Catálogo Neón</h1></header>

<div class="container">
    <div class="productos">
        <?php foreach($products as $p): ?>
            <div class="item">
                <img src="<?php echo htmlspecialchars($p['image'] ?: 'img/placeholder.jpg'); ?>" alt="">
                <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                <p>$ <?php echo number_format($p['price_unit'],2); ?></p>
                <a class="btn" href="index.php?action=add&id=<?php echo $p['id']; ?>">Agregar</a>
            </div>
        <?php endforeach; ?>
    </div>

    <aside class="carrito">
        <h2>🛒 Carrito</h2>

        <?php if (empty($cart_items)): ?>
            <p class="small">Tu carrito está vacío.</p>
        <?php else: ?>
            <?php foreach($cart_items as $it): ?>
                <div class="cart-row">
                    <div style="display:flex;align-items:center;">
                        <img src="<?php echo htmlspecialchars($it['product']['image'] ?: 'img/placeholder.jpg'); ?>" alt="">
                        <div>
                            <div><?php echo htmlspecialchars($it['product']['name']); ?></div>
                            <div class="small">x<?php echo $it['qty']; ?> — $<?php echo number_format($it['product']['price_unit'],2); ?></div>
                        </div>
                    </div>
                    <div>
                        <div>$ <?php echo number_format($it['sub'],2); ?></div>
                        <div style="margin-top:6px;text-align:right;">
                            <a class="btn" style="border-color:var(--neon-rosa);color:var(--neon-rosa);" href="index.php?action=remove&id=<?php echo $it['product']['id']; ?>">X</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <h3 style="margin-top:12px;color:var(--neon-verde)">Total: $ <?php echo number_format($total,2); ?></h3>

            <form class="checkout-form" method="post" action="checkout.php">

            <label class="small">Nombre (opcional)</label>
                <input type="text" name="customer_name" placeholder="Nombre del cliente">

                  <h3 style="margin-top:15px;color:var(--neon-azul);">🔍 Buscar producto</h3>

                <input 
                    type="text" 
                    id="buscar" 
                    placeholder="Escribe un producto..." 
                    style="width:100%;padding:8px;border-radius:8px;border:1px solid var(--neon-rosa);margin-top:6px;background:#111;color:white;">

                <button type="button" class="btn" style="width:100%;margin-top:10px;" onclick="buscarProducto()">
                    Buscar
                </button>

                <p id="resultado-busqueda" style="margin-top:10px;color:var(--neon-verde);font-size:14px;"></p>
                <input type="hidden" name="total" value="<?php echo htmlspecialchars($total); ?>">
                <button class="btn" type="submit">Finalizar compra</button>
            </form>

            <a class="link-clear" href="index.php?action=clear">Vaciar carrito</a>
        <?php endif; ?>
    </aside>
</div>

<script>
function buscarProducto() {
    let texto = document.getElementById("buscar").value.toLowerCase();
    let productos = document.querySelectorAll(".item");
    let resultados = 0;

    productos.forEach(p => {
        let nombre = p.querySelector("h3").textContent.toLowerCase();
        
        if (nombre.includes(texto)) {
            p.style.display = "block";
            resultados++;
        } else {
            p.style.display = "none";
        }
    });

    let msg = document.getElementById("resultado-busqueda");
    if (texto.trim() === "") {
        msg.textContent = "";
        productos.forEach(p => p.style.display = "block"); 
    } else if (resultados > 0) {
        msg.textContent = resultados + " producto(s) encontrado(s).";
        msg.style.color = "var(--neon-verde)";
    } else {
        msg.textContent = "No se encontraron productos.";
        msg.style.color = "var(--neon-rosa)";
    }
}
</script>
</body>
</html
