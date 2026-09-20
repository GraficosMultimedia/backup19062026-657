CREATE TABLE IF NOT EXISTS cp_order_photos (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id int(10) UNSIGNED NOT NULL,
  file_path varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  original_name varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  mime_type varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  file_size int(10) UNSIGNED NOT NULL DEFAULT '0',
  photo_type varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reference',
  caption varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  created_by int(10) UNSIGNED DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY idx_cp_order_photos_order (order_id),
  KEY idx_cp_order_photos_type (photo_type),
  CONSTRAINT fk_cp_order_photos_order FOREIGN KEY (order_id) REFERENCES cp_orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_cp_order_photos_created_by FOREIGN KEY (created_by) REFERENCES cp_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
