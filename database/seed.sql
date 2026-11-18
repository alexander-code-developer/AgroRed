SET NAMES utf8mb4;
-- =====================================================================
-- AgroRed Hidalgo - Datos de prueba (contraseña de todos: Agrored2026!)
-- =====================================================================
USE agrored;

INSERT INTO roles (id,nombre) VALUES (1,'admin'),(2,'productor'),(3,'recolector'),(4,'coordinador'),(5,'usuario');
INSERT INTO estados_pedido (id,nombre) VALUES (1,'solicitado'),(2,'contactado'),(3,'completado'),(4,'cancelado');
INSERT INTO tipos_movimiento (id,nombre) VALUES (1,'ingreso'),(2,'gasto');

INSERT INTO municipios (id,nombre) VALUES
 (1,'Mixquiahuala de Juárez'),(2,'Chilcuautla'),(3,'Tezontepec de Aldama'),(4,'Ajacuba'),
 (5,'Tula de Allende'),(6,'Pachuca de Soto'),(7,'Tlaxcoapan'),(8,'Actopan');

INSERT INTO colonias (id,municipio_id,nombre) VALUES
 (1,4,'Libramiento Santo Niño'),(2,1,'Centro'),(3,1,'La Cruz'),(4,2,'El Mezquite'),
 (5,3,'San Lucas'),(6,5,'Bomintzha'),(7,6,'El Arbolito'),(8,6,'La Raza'),(9,6,'Palmitas'),
 (10,7,'Doxey'),(11,8,'Centro');

INSERT INTO cultivos (id,nombre,kcal,proteina_g,vitamina_c_mg) VALUES
 (1,'Maíz',365,9,0),(2,'Frijol',333,24,2),(3,'Calabaza',26,1,9),(4,'Jitomate',18,0.9,14),
 (5,'Nopal',16,1.3,8),(6,'Chile',40,2,144),(7,'Lechuga',15,1.4,9),(8,'Alfalfa',23,4,8);

INSERT INTO usuarios (id,rol_id,nombre,correo,telefono,pass_hash) VALUES
 (1,1,'Administrador AgroRed','admin@agrored.test','7710000001','$2y$10$TZlETkLJoy.CLxmsTqCXRekYAMbdTmBJcBK7y7VlS10wM4OrBFoN6'),
 (2,2,'Mario Cordero Portillo','mario.cordero@ejemplo.com','7710000000','$2y$10$TZlETkLJoy.CLxmsTqCXRekYAMbdTmBJcBK7y7VlS10wM4OrBFoN6'),
 (3,2,'Rosa Hernández Cruz','rosa.hernandez@ejemplo.com','7710000002','$2y$10$TZlETkLJoy.CLxmsTqCXRekYAMbdTmBJcBK7y7VlS10wM4OrBFoN6'),
 (4,2,'Luis Martínez Soto','luis.martinez@ejemplo.com','7710000003','$2y$10$TZlETkLJoy.CLxmsTqCXRekYAMbdTmBJcBK7y7VlS10wM4OrBFoN6'),
 (5,3,'Pedro Ramírez Vega','pedro.recolector@ejemplo.com','7710000004','$2y$10$TZlETkLJoy.CLxmsTqCXRekYAMbdTmBJcBK7y7VlS10wM4OrBFoN6'),
 (6,4,'Ana Lilia Torres','ana.coordinadora@ejemplo.com','7710000005','$2y$10$TZlETkLJoy.CLxmsTqCXRekYAMbdTmBJcBK7y7VlS10wM4OrBFoN6'),
 (7,5,'Laura Mendoza','laura@ejemplo.com','7710000006','$2y$10$TZlETkLJoy.CLxmsTqCXRekYAMbdTmBJcBK7y7VlS10wM4OrBFoN6'),
 (8,5,'Carlos Rojas','carlos@ejemplo.com','7710000007','$2y$10$TZlETkLJoy.CLxmsTqCXRekYAMbdTmBJcBK7y7VlS10wM4OrBFoN6');

INSERT INTO huertos (id,dueno_id,colonia_id,nombre,telefono,facebook,latitud,longitud) VALUES
 (1,2,1,'Huerto Vicente Guerrero','7711234567','https://facebook.com/huerto-vicente',20.098300,-99.116700),
 (2,3,2,'Huerto La Esperanza','7717654321',NULL,20.233300,-99.216700),
 (3,4,4,'Huerto El Mezquite','7715550101','https://facebook.com/elmezquite',20.333300,-99.183300),
 (4,2,5,'Huerto Los Pinos','7712228899',NULL,20.192500,-99.271100),
 (5,3,6,'Huerto Bomintzha','7713334455',NULL,20.053900,-99.341100),
 (6,4,7,'Huerto El Arbolito','7716667788',NULL,20.101100,-98.759100),
 (7,3,8,'Huerto La Raza','7719990011',NULL,20.115000,-98.742000),
 (8,2,9,'Huerto Palmitas','7718881122',NULL,20.088000,-98.770000);

INSERT INTO inventario (huerto_id,cultivo_id,volumen_kg,cosecha_estimada) VALUES
 (1,1,120,'2026-11-20'),(1,2,80,'2026-11-25'),(1,3,60,'2026-11-10'),(1,4,95,'2026-11-05'),
 (2,3,45,'2026-11-12'),(2,7,30,'2026-11-08'),(3,5,150,'2026-11-15'),(3,6,40,'2026-11-18'),
 (4,7,35,'2026-11-09'),(5,1,200,'2026-12-01'),(6,1,25,'2026-11-22'),(7,2,18,'2026-11-24'),(8,4,12,'2026-11-11');

INSERT INTO pedidos (huerto_id,usuario_id,estado_id,telefono,creado) VALUES
 (1,7,1,'7711234567','2026-10-02 10:15:00'),(1,8,2,'7717654321','2026-09-30 12:00:00'),
 (1,NULL,3,'7715550101','2026-09-28 09:30:00'),(1,NULL,4,'7712228899','2026-09-25 17:45:00');

INSERT INTO resenas (huerto_id,usuario_id,estrellas,comentario,recomienda,creado) VALUES
 (1,7,5,'Verduras muy frescas y el productor fue muy amable al atenderme.',1,'2026-10-02 11:00:00'),
 (1,8,4,'Buen precio por la calabaza; pasé a recogerla y estaba lista.',1,'2026-09-29 16:00:00');

INSERT INTO mensajes (de_usuario_id,para_huerto_id,texto,creado) VALUES
 (3,1,'¿Te interesa intercambiar calabaza por jitomate?','2026-10-02 09:00:00'),
 (4,1,'Propongo vender nopal en conjunto para las fondas de la zona.','2026-09-30 13:00:00'),
 (4,4,'Tengo excedente de lechuga, ¿lo recolectamos juntos?','2026-09-28 18:00:00');

INSERT INTO finanzas_movimientos (usuario_id,tipo_id,concepto,monto,fecha) VALUES
 (2,1,'Venta local de tomate (20 kg)',300,'2026-10-02'),
 (2,1,'Venta de maíz (25 kg)',850,'2026-09-28'),
 (2,2,'Costal de abono',250,'2026-09-28'),
 (2,1,'Venta de calabaza',400,'2026-09-15'),
 (2,1,'Venta de frijol',900,'2026-08-20'),
 (2,2,'Semillas',300,'2026-08-05'),
 (2,1,'Venta de nopal',650,'2026-10-10');
