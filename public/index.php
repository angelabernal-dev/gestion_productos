<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Producto.php';

function escapar(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

try {
    $database = new Database();
    $productoModel = new Producto($database->connect());
} catch (PDOException $e) {
    http_response_code(500);
    exit('No se pudo conectar con MySQL. Revisa config/Database.php y que el servicio esté iniciado.');
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? 'listar';
$error = '';
$productoEditar = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga la página e inténtalo nuevamente.');
    }

    if (in_array($accion, ['crear', 'actualizar'], true)) {
        $datos = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'categoria' => trim($_POST['categoria'] ?? ''),
            'precio' => $_POST['precio'] ?? '',
            'stock' => $_POST['stock'] ?? ''
        ];

        if ($datos['nombre'] === '' || $datos['categoria'] === '' ||
            !is_numeric($datos['precio']) || (float)$datos['precio'] < 0 ||
            filter_var($datos['stock'], FILTER_VALIDATE_INT) === false ||
            (int)$datos['stock'] < 0) {
            $error = 'Completa los campos obligatorios. Precio y stock deben ser valores válidos y no negativos.';
            $productoEditar = $datos;
            $accion = $accion === 'actualizar' ? 'editar' : 'nuevo';
        } else {
            try {
                if ($accion === 'crear') {
                    $productoModel->crear($datos);
                    header('Location: index.php?estado=creado');
                } else {
                    $productoModel->actualizar((int)($_POST['id'] ?? 0), $datos);
                    header('Location: index.php?estado=actualizado');
                }
                exit;
            } catch (PDOException $e) {
                $error = 'No se pudo guardar. Verifica los datos ingresados.';
                $productoEditar = $datos;
                $accion = 'nuevo';
            }
        }
    } elseif ($accion === 'eliminar') {
        $productoModel->eliminar((int)($_POST['id'] ?? 0));
        header('Location: index.php?estado=eliminado');
        exit;
    }
}

if ($accion === 'editar' && $productoEditar === null) {
    $productoEditar = $productoModel->obtener((int)($_GET['id'] ?? 0));
    if (!$productoEditar) {
        header('Location: index.php?estado=no-encontrado');
        exit;
    }
}

$busqueda = trim($_GET['buscar'] ?? '');
$productos = ($accion === 'listar') ? $productoModel->listar($busqueda) : [];
$mostrarFormulario = in_array($accion, ['nuevo', 'editar'], true);
$tituloFormulario = $accion === 'editar' ? 'Editar producto' : 'Nuevo producto';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TechMarket | Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container"><a class="navbar-brand" href="index.php">TechMarket</a>
        <span class="navbar-text text-white">Sistema de gestión de productos</span></div>
</nav>
<main class="container py-4">
    <?php if (isset($_GET['estado'])): ?>
        <div class="alert alert-success">
            <?php
            $mensajes = ['creado'=>'Producto registrado.', 'actualizado'=>'Producto actualizado.',
                         'eliminado'=>'Producto eliminado.', 'no-encontrado'=>'No se encontró el producto.'];
            echo escapar($mensajes[$_GET['estado']] ?? 'Operación realizada.');
            ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= escapar($error) ?></div><?php endif; ?>

    <?php if ($mostrarFormulario): ?>
        <div class="card shadow-sm mx-auto" style="max-width: 760px;">
            <div class="card-header bg-primary text-white"><h1 class="h4 mb-0"><?= escapar($tituloFormulario) ?></h1></div>
            <div class="card-body">
                <form method="post" action="index.php">
                    <input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
                    <input type="hidden" name="accion" value="<?= $accion === 'editar' ? 'actualizar' : 'crear' ?>">
                    <?php if ($accion === 'editar'): ?><input type="hidden" name="id" value="<?= (int)($productoEditar['id'] ?? 0) ?>"><?php endif; ?>
                    <div class="mb-3"><label class="form-label">Nombre *</label>
                        <input class="form-control" name="nombre" maxlength="120" required value="<?= escapar($productoEditar['nombre'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" maxlength="500" rows="3"><?= escapar($productoEditar['descripcion'] ?? '') ?></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Categoría *</label>
                            <input class="form-control" name="categoria" maxlength="80" required value="<?= escapar($productoEditar['categoria'] ?? '') ?>"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Precio (S/) *</label>
                            <input class="form-control" type="number" name="precio" min="0" step="0.01" required value="<?= escapar((string)($productoEditar['precio'] ?? '')) ?>"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Stock *</label>
                            <input class="form-control" type="number" name="stock" min="0" step="1" required value="<?= escapar((string)($productoEditar['stock'] ?? '0')) ?>"></div>
                    </div>
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn btn-outline-secondary" href="index.php">Cancelar</a>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div><h1 class="h3 mb-1">Productos</h1><p class="text-muted mb-0">Registra y administra el inventario.</p></div>
            <a class="btn btn-primary" href="index.php?accion=nuevo">+ Nuevo producto</a>
        </div>
        <form class="row g-2 mb-3" method="get">
            <div class="col-sm-8 col-md-5"><input class="form-control" name="buscar" placeholder="Buscar por nombre o categoría" value="<?= escapar($busqueda) ?>"></div>
            <div class="col-auto"><button class="btn btn-outline-primary">Buscar</button>
                <a class="btn btn-outline-secondary" href="index.php">Limpiar</a></div>
        </form>
        <div class="card shadow-sm"><div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark"><tr><th>ID</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?= (int)$producto['id'] ?></td>
                        <td><strong><?= escapar($producto['nombre']) ?></strong><br><small class="text-muted"><?= escapar($producto['descripcion']) ?></small></td>
                        <td><?= escapar($producto['categoria']) ?></td>
                        <td>S/ <?= number_format((float)$producto['precio'], 2) ?></td>
                        <td><?= (int)$producto['stock'] ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="index.php?accion=editar&id=<?= (int)$producto['id'] ?>">Editar</a>
                            <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este producto?')">
                                <input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= (int)$producto['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$productos): ?><tr><td colspan="6" class="text-center text-muted py-4">No se encontraron productos.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div></div>
    <?php endif; ?>
</main>
</body>
</html>
