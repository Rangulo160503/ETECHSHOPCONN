CREATE TABLE productos (
    codigo INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(255),
    Detalle TEXT,
    precio DECIMAL(10, 2),
    imagen VARCHAR(255)
);

INSERT INTO productos (Nombre, Detalle, precio, imagen) VALUES
('Iphone 15', 'Loremp imsup', 19.99, 'https://th.bing.com/th/id/OIP.ITpMpuDyv_KG6YLJ2IUT3AHaFj?w=221&h=180&c=7&r=0&o=5&dpr=1.3&pid=1.7'),
('Mackbook pro', 'Loremp imsup', 29.99, 'https://th.bing.com/th/id/OIP.ITpMpuDyv_KG6YLJ2IUT3AHaFj?w=221&h=180&c=7&r=0&o=5&dpr=1.3&pid=1.7'),
('Apple wacth', 'Loremp imsup', 39.99, 'https://th.bing.com/th/id/OIP.ITpMpuDyv_KG6YLJ2IUT3AHaFj?w=221&h=180&c=7&r=0&o=5&dpr=1.3&pid=1.7');


CREATE TABLE compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    productos TEXT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);