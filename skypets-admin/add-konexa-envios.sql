-- Respuestas de los formularios de Konexa que llegan por webhook (recibir_konexa.php). El admin las suma a las filas de la hoja de Google.
-- La fila que ve el dashboard es 100002 + id (las de la hoja usan 2, 3, 4...): nunca chocan y no cambian si la hoja crece.
CREATE TABLE IF NOT EXISTS konexa_envios (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  envio_id    CHAR(36)     NOT NULL,
  payload     LONGTEXT     NOT NULL,
  recibido_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_envio (envio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
