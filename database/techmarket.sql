CREATE DATABASE IF NOT EXISTS techmarket
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE techmarket;

CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(500),
    categoria VARCHAR(80) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO productos (nombre, descripcion, categoria, precio, stock) VALUES
('Teclado mecánico', 'Teclado USB para uso diario.', 'Accesorios', 89.90, 15),
('Mouse inalámbrico', 'Mouse óptico inalámbrico.', 'Accesorios', 45.50, 22),
('Memoria USB 64 GB', 'Unidad de almacenamiento USB.', 'Almacenamiento', 29.90, 30);
