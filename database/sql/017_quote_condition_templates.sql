CREATE TABLE IF NOT EXISTS cp_quote_condition_templates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  payment_terms VARCHAR(190) DEFAULT NULL,
  delivery_time VARCHAR(190) DEFAULT NULL,
  delivery_place VARCHAR(190) DEFAULT NULL,
  terms TEXT,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_by INT UNSIGNED DEFAULT NULL,
  updated_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_cpct_enabled (enabled, sort_order, id),
  KEY idx_cpct_default (is_default, enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO cp_quote_condition_templates (name,payment_terms,delivery_time,delivery_place,terms,is_default,enabled,sort_order,created_at,updated_at)
SELECT 'Condición general Colibrí Print','50% de anticipo y 50% contra entrega, salvo acuerdo distinto por escrito.','Tiempo estimado según proyecto y disponibilidad de materiales.','Hidalgo del Parral, Chihuahua / domicilio acordado con el cliente.','Cotización sujeta a disponibilidad de materiales, aprobación del cliente y cambios de alcance. Los tiempos pueden variar según materiales, producción y carga de trabajo. Cualquier modificación al proyecto puede generar ajustes de precio y entrega.',1,1,0,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM cp_quote_condition_templates);
