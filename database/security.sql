-- Migracion local de seguridad para Recetario Online
-- Ejecutar sobre la base de datos seleccionada previamente.
-- Si existen duplicados en usuarios.email o favoritos(usuario_id, receta_id), elimínalos antes
-- de crear los índices únicos.

SET @db_name := DATABASE();

-- Columnas nuevas en usuarios
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = @db_name
              AND table_name = 'usuarios'
              AND column_name = 'rol'
        ),
        'SELECT 1',
        'ALTER TABLE usuarios ADD COLUMN rol VARCHAR(20) NOT NULL DEFAULT ''usuario'''
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = @db_name
              AND table_name = 'usuarios'
              AND column_name = 'two_factor_enabled'
        ),
        'SELECT 1',
        'ALTER TABLE usuarios ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = @db_name
              AND table_name = 'usuarios'
              AND column_name = 'two_factor_secret'
        ),
        'SELECT 1',
        'ALTER TABLE usuarios ADD COLUMN two_factor_secret VARCHAR(255) NULL'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Indice unico por email de usuario
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @db_name
              AND table_name = 'usuarios'
              AND index_name = 'idx_usuarios_email_unique'
        ),
        'SELECT 1',
        'CREATE UNIQUE INDEX idx_usuarios_email_unique ON usuarios (email)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tabla para rate limiting local o compartido
CREATE TABLE IF NOT EXISTS security_rate_limits (
    rate_key CHAR(64) NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    window_started_at INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (rate_key)
);

-- Tokens de recuperacion de contraseña
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

-- Evitar favoritos duplicados
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @db_name
              AND table_name = 'favoritos'
              AND index_name = 'idx_favoritos_unique'
        ),
        'SELECT 1',
        'CREATE UNIQUE INDEX idx_favoritos_unique ON favoritos (usuario_id, receta_id)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Indices para listados y filtros frecuentes
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @db_name
              AND table_name = 'recetas'
              AND index_name = 'idx_recetas_busqueda'
        ),
        'SELECT 1',
        'CREATE FULLTEXT INDEX idx_recetas_busqueda ON recetas (titulo, preparacion)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @db_name
              AND table_name = 'recetas'
              AND index_name = 'idx_recetas_usuario_id'
        ),
        'SELECT 1',
        'CREATE INDEX idx_recetas_usuario_id ON recetas (usuario_id)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @db_name
              AND table_name = 'recetas'
              AND index_name = 'idx_recetas_origen'
        ),
        'SELECT 1',
        'CREATE INDEX idx_recetas_origen ON recetas (origen)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @db_name
              AND table_name = 'recetas'
              AND index_name = 'idx_recetas_dificultad'
        ),
        'SELECT 1',
        'CREATE INDEX idx_recetas_dificultad ON recetas (dificultad)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @db_name
              AND table_name = 'recetas'
              AND index_name = 'idx_recetas_tipo'
        ),
        'SELECT 1',
        'CREATE INDEX idx_recetas_tipo ON recetas (tipo)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @db_name
              AND table_name = 'recetas'
              AND index_name = 'idx_recetas_duracion'
        ),
        'SELECT 1',
        'CREATE INDEX idx_recetas_duracion ON recetas (duracion)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @db_name
              AND table_name = 'ingredientes'
              AND index_name = 'idx_ingredientes_receta_id'
        ),
        'SELECT 1',
        'CREATE INDEX idx_ingredientes_receta_id ON ingredientes (receta_id)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @db_name
              AND table_name = 'ingredientes'
              AND index_name = 'idx_ingredientes_nombre'
        ),
        'SELECT 1',
        'CREATE INDEX idx_ingredientes_nombre ON ingredientes (nombre)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
