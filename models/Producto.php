<?php
class Producto
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function listar(string $busqueda = ''): array
    {
        $sql = "SELECT * FROM productos
                WHERE nombre LIKE :busqueda OR categoria LIKE :busqueda
                ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['busqueda' => '%' . $busqueda . '%']);
        return $stmt->fetchAll();
    }

    public function obtener(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $producto = $stmt->fetch();
        return $producto ?: null;
    }

    public function crear(array $datos): bool
    {
        $sql = "INSERT INTO productos (nombre, descripcion, categoria, precio, stock)
                VALUES (:nombre, :descripcion, :categoria, :precio, :stock)";
        return $this->db->prepare($sql)->execute($datos);
    }

    public function actualizar(int $id, array $datos): bool
    {
        $datos['id'] = $id;
        $sql = "UPDATE productos SET nombre=:nombre, descripcion=:descripcion,
                categoria=:categoria, precio=:precio, stock=:stock WHERE id=:id";
        return $this->db->prepare($sql)->execute($datos);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM productos WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
