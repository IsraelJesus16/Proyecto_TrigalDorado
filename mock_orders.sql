SET @id1 = UUID();
INSERT INTO pedido (id_pedido, cedula_cliente, condicion_pago, metodo_pago, estado, subtotal, descuento, total) VALUES
(@id1, 'J-318439377', 'CONTADO', 'TRANSFERENCIA', 'CONFIRMADO', 1045.51, 0, 1045.51);

INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES
(@id1, 'c826d604-5dd3-45a4-bd10-388f613cb3d0', 1, 1000.00, 1000.00),
(@id1, 'PRD-G001', 1, 45.51, 45.51);

SET @id2 = UUID();
INSERT INTO pedido (id_pedido, cedula_cliente, condicion_pago, metodo_pago, estado, subtotal, descuento, total) VALUES
(@id2, 'J-318439377', 'CREDITO', 'EFECTIVO', 'ENTREGADO', 91.02, 10.00, 81.02);

INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES
(@id2, 'PRD-G001', 2, 45.51, 91.02);
