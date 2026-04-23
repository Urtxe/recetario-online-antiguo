-- Seguridad y soporte operativo para Recetario Online
-- Ejecutar sobre la base de datos existente.

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS rol VARCHAR(20) NOT NULL DEFAULT 'usuario',
    ADD COLUMN IF NOT EXISTS two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(255) NULL;

CREATE UNIQUE INDEX IF NOT EXISTS idx_usuarios_email_unique ON usuarios (email);

CREATE TABLE IF NOT EXISTS security_rate_limits (
    rate_key CHAR(64) NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    window_started_at INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (rate_key)
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at INT NOT NULL,
    used_at INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_email (email),
    INDEX idx_password_resets_token (token_hash)
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_favoritos_unique ON favoritos (usuario_id, receta_id);
