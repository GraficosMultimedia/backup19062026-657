-- FASE 10 · Variantes y personalización
-- MySQL 5.7+
-- Idempotente y no destructiva. Solo crea estructuras nuevas.

CREATE TABLE IF NOT EXISTS `cp_product_option_groups` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'select',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `help_text` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placeholder` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_length` int(10) UNSIGNED DEFAULT NULL,
  `max_length` int(10) UNSIGNED DEFAULT NULL,
  `min_value` decimal(15,3) DEFAULT NULL,
  `max_value` decimal(15,3) DEFAULT NULL,
  `accept` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cp_product_option_groups_product` (`product_id`),
  CONSTRAINT `fk_cp_product_option_groups_product`
    FOREIGN KEY (`product_id`) REFERENCES `cp_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cp_product_option_values` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` int(10) UNSIGNED NOT NULL,
  `label` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_delta` decimal(15,2) NOT NULL DEFAULT '0.00',
  `sku_suffix` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cp_product_option_values_group` (`group_id`),
  CONSTRAINT `fk_cp_product_option_values_group`
    FOREIGN KEY (`group_id`) REFERENCES `cp_product_option_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cp_product_variants` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int(10) UNSIGNED NOT NULL,
  `sku` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_override` decimal(15,2) DEFAULT NULL,
  `combination_json` json DEFAULT NULL,
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cp_product_variants_product` (`product_id`),
  CONSTRAINT `fk_cp_product_variants_product`
    FOREIGN KEY (`product_id`) REFERENCES `cp_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
