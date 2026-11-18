-- agrega el correo opcional del comprador.
USE agrored;
ALTER TABLE pedidos ADD COLUMN correo VARCHAR(150) NULL AFTER telefono;
