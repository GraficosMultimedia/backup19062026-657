USE colibrip_abcsistema;

-- Publicación automática de promociones en una Página de Facebook.
CREATE TABLE IF NOT EXISTS cp_promotion_facebook (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    promotion_id INT UNSIGNED NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    custom_message TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    facebook_page_id VARCHAR(80) NULL,
    facebook_post_id VARCHAR(190) NULL,
    image_url VARCHAR(1000) NULL,
    last_error TEXT NULL,
    response_json LONGTEXT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_attempt_at DATETIME NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cp_promotion_facebook_promotion (promotion_id),
    KEY idx_cp_promotion_facebook_status (status),
    CONSTRAINT fk_cp_promotion_facebook_promotion FOREIGN KEY (promotion_id) REFERENCES cp_promotions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores globales en cp_settings. El token nunca se imprime en pantalla completo.
INSERT INTO cp_settings(setting_key,setting_value,created_at,updated_at)
VALUES
('facebook.enabled','0',NOW(),NOW()),
('facebook.auto_publish','1',NOW(),NOW()),
('facebook.page_id','',NOW(),NOW()),
('facebook.page_access_token','',NOW(),NOW()),
('facebook.graph_version','v26.0',NOW(),NOW()),
('facebook.public_base_url','https://colibriprint.com.mx',NOW(),NOW())
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);

-- Las promociones existentes quedan habilitadas para Facebook por defecto.
INSERT INTO cp_promotion_facebook (promotion_id,enabled,status,created_at,updated_at)
SELECT p.id,1,'pending',NOW(),NOW()
FROM cp_promotions p
LEFT JOIN cp_promotion_facebook f ON f.promotion_id=p.id
WHERE f.id IS NULL;
