CREATE OR REPLACE PROCEDURE sp_registrar_usuario (
    p_correo   IN  VARCHAR2,
    p_contra   IN  VARCHAR2,   -- Contraseña YA HASHEADA desde PHP
    p_id_out   OUT NUMBER
) AS
BEGIN
    -- Validación del formato del correo
    IF NOT REGEXP_LIKE(p_correo, '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$') THEN
        RAISE_APPLICATION_ERROR(-20001, 'Correo inválido');
    END IF;

    -- Insertar usuario (correo se guarda en mayúsculas)
    INSERT INTO usuarios (correo, contrasena)
    VALUES (UPPER(p_correo), p_contra)
    RETURNING id INTO p_id_out;

EXCEPTION
    -- Error si ya existe el correo
    WHEN DUP_VAL_ON_INDEX THEN
        RAISE_APPLICATION_ERROR(-20002, 'El correo ya existe');

    -- Cualquier otro error
    WHEN OTHERS THEN
        RAISE_APPLICATION_ERROR(-20003, 'Error registrando usuario: ' || SQLERRM);
END;
/

CREATE OR REPLACE PROCEDURE sp_producto_upsert(
  p_codigo    IN OUT NUMBER,
  p_nombre    IN VARCHAR2,
  p_detalle   IN VARCHAR2,
  p_imagen    IN VARCHAR2,
  p_precio    IN NUMBER,
  p_stock     IN NUMBER
) AS
BEGIN
  -- Si no se envía código, se inserta
  IF p_codigo IS NULL THEN
    INSERT INTO productos(nombre, detalle, imagen, precio, stock)
    VALUES (p_nombre, p_detalle, p_imagen, p_precio, p_stock)
    RETURNING codigo INTO p_codigo;

  ELSE
    -- Si viene código, se actualiza
    UPDATE productos
       SET nombre = p_nombre,
           detalle = p_detalle,
           imagen = p_imagen,
           precio = p_precio,
           stock  = p_stock
     WHERE codigo = p_codigo;
  END IF;

END;
/


CREATE OR REPLACE FUNCTION fn_login_usuario (
    p_correo IN VARCHAR2,
    p_contra IN VARCHAR2
) RETURN NUMBER
AS
    v_id        NUMBER;
    v_blk       CHAR(1);
    v_intentos  NUMBER;
    v_contra_db VARCHAR2(200);
BEGIN
    -- Traemos todo en una sola consulta
    SELECT id, bloqueado, intentos, contrasena
    INTO   v_id, v_blk, v_intentos, v_contra_db
    FROM usuarios
    WHERE UPPER(correo) = UPPER(p_correo);

    -- Usuario bloqueado
    IF v_blk = 'S' THEN
        RAISE_APPLICATION_ERROR(-20010, 'Cuenta bloqueada');
    END IF;

    -- Verificar la contraseña
    IF p_contra != v_contra_db THEN

        UPDATE usuarios
        SET intentos = v_intentos + 1
        WHERE id = v_id;

        -- Si llega a 3 intentos, bloquearlo
        IF v_intentos + 1 >= 3 THEN
            UPDATE usuarios SET bloqueado = 'S' WHERE id = v_id;
            COMMIT;
            RAISE_APPLICATION_ERROR(-20011, 'Cuenta bloqueada por intentos fallidos');
        END IF;

        COMMIT;
        RETURN 0;  -- contraseña incorrecta
    END IF;

    -- Si la contraseña es correcta, reiniciar intentos
    UPDATE usuarios SET intentos = 0 WHERE id = v_id;
    COMMIT;

    RETURN v_id;

EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RETURN 0;
END;
/


CREATE OR REPLACE PROCEDURE sp_login_wrapper(
  p_correo IN VARCHAR2,
  p_contra IN VARCHAR2,
  p_id_out OUT NUMBER
) AS
BEGIN
--validación del correo y contraseña
  p_id_out := fn_login_usuario(p_correo, p_contra);
  IF p_id_out = 0 THEN
    UPDATE usuarios
       SET intentos = NVL(intentos,0)+1,
           bloqueado = CASE WHEN NVL(intentos,0)+1 >= 3 THEN 'S' 
           ELSE bloqueado 
           END
     WHERE UPPER(correo)=UPPER(p_correo);
  END IF;
END;
/





CREATE OR REPLACE PROCEDURE sp_carrito_agregar_db(
  p_id_usuario IN NUMBER,
  p_codigo     IN NUMBER,
  p_cantidad   IN NUMBER
)
AS
  v_stock NUMBER;
BEGIN
  -- Validar cantidad
  IF p_cantidad IS NULL OR p_cantidad <= 0 THEN
    RAISE_APPLICATION_ERROR(-20011, 'Cantidad inválida');
  END IF;

  -- Verificar que el producto exista y obtener stock
  SELECT stock 
  INTO v_stock
  FROM productos
  WHERE codigo = p_codigo;

  -- Verificar stock insuficiente
  IF v_stock < p_cantidad THEN
    RAISE_APPLICATION_ERROR(-20020, 'Stock insuficiente');
  END IF;

  -- Intentar actualizar la cantidad si ya existía en el carrito
  UPDATE carrito
     SET cantidad = cantidad + p_cantidad
   WHERE id_usuario = p_id_usuario
     AND codigo_prod = p_codigo;

  -- Si no actualizó ninguna fila, insertar nuevo registro
  IF SQL%ROWCOUNT = 0 THEN
    INSERT INTO carrito(id_usuario, codigo_prod, cantidad)
    VALUES (p_id_usuario, p_codigo, p_cantidad);
  END IF;

END;
/


CREATE OR REPLACE PROCEDURE sp_generar_compra(
  p_id_usuario IN NUMBER,
  p_id_compra  OUT NUMBER
) AS
  CURSOR cur_items IS
    SELECT c.codigo_prod, c.cantidad, p.precio
      FROM carrito c JOIN productos p ON p.codigo=c.codigo_prod
     WHERE c.id_usuario = p_id_usuario;
  v_total NUMBER := 0;
BEGIN
  INSERT INTO compras(id_usuario,total) VALUES (p_id_usuario, 0)
  RETURNING id INTO p_id_compra;
  FOR r IN cur_items LOOP
    INSERT INTO compras_detalle(id_compra,codigo_prod,cantidad,precio_unit)
    VALUES (p_id_compra, r.codigo_prod, r.cantidad, r.precio);
    v_total := v_total + (r.cantidad * r.precio);
  END LOOP;

  UPDATE compras SET total=v_total WHERE id=p_id_compra;
-- limpiar carrito
  DELETE FROM carrito WHERE id_usuario=p_id_usuario; 
EXCEPTION
  WHEN OTHERS THEN
    RAISE_APPLICATION_ERROR(-20030,'Error generando compra: '||SQLERRM);
END;
/

CREATE OR REPLACE TRIGGER trg_aud_productos_u
AFTER UPDATE OF precio, stock ON productos
FOR EACH ROW
DECLARE
  v_detalle VARCHAR2(400);
BEGIN
  -- información del cambio 
  IF NVL(:OLD.precio, -1) <> NVL(:NEW.precio, -1) THEN
    v_detalle := NVL(v_detalle,'') || 'precio: '||:OLD.precio||' -> '||:NEW.precio||' ';
  END IF;

  IF NVL(:OLD.stock, -1) <> NVL(:NEW.stock, -1) THEN
    v_detalle := NVL(v_detalle,'') || 'stock: '||:OLD.stock||' -> '||:NEW.stock||' ';
  END IF;

  -- solo inserta en AUDITORIA si hay cambios reales
  IF v_detalle IS NOT NULL THEN
    INSERT INTO auditoria(tabla, operacion, id_registro, usuario_bd, detalle)
    VALUES ('PRODUCTOS',
            'UPDATE',
            TO_CHAR(:NEW.codigo),
            SYS_CONTEXT('USERENV','SESSION_USER'),
            TRIM(v_detalle));
  END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_stock_disminuir
AFTER INSERT ON compras_detalle
FOR EACH ROW
BEGIN
  UPDATE productos
     SET stock = stock - :NEW.cantidad
   WHERE codigo = :NEW.codigo_prod;
END;
/

CREATE OR REPLACE PACKAGE pkg_reportes AS
  TYPE t_rc IS REF CURSOR;
  PROCEDURE get_compras_usuario(p_id_usuario IN NUMBER, p_rc OUT t_rc);
END pkg_reportes;
/


CREATE OR REPLACE PROCEDURE sp_insert_producto (
    p_nombre   IN VARCHAR2,
    p_detalle  IN VARCHAR2,
    p_precio   IN NUMBER,
    p_imagen   IN VARCHAR2
)
AS
BEGIN
    INSERT INTO productos (nombre, detalle, precio, imagen)
    VALUES (p_nombre, p_detalle, p_precio, p_imagen);
END;
/






CREATE OR REPLACE PACKAGE BODY pkg_reportes AS
  PROCEDURE get_compras_usuario(p_id_usuario IN NUMBER, p_rc OUT t_rc) IS
  BEGIN
    OPEN p_rc FOR
      SELECT c.id, c.total, c.fecha
        FROM compras c
       WHERE c.id_usuario = p_id_usuario
       ORDER BY c.fecha DESC;
  END;
END pkg_reportes;
/
