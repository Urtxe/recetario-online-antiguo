# Migracion local

Este directorio contiene el SQL de seguridad para dejar la base de datos lista en local.

## Ejecucion

Desde la carpeta del proyecto:

```bash
mysql -u root -p recetario < database/security.sql
```

Si prefieres hacerlo desde el cliente de MySQL:

```sql
USE recetario;
SOURCE database/security.sql;
```

## Notas

- El script es idempotente en la medida de lo posible.
- Antes de crear los indices unicos, conviene borrar duplicados en `usuarios.email` y en `favoritos (usuario_id, receta_id)`.
- El script tambien crea indices para `recetas` e `ingredientes` que ayudan a los listados, filtros y favoritos cuando la base crece.
- La busqueda principal usa `FULLTEXT` sobre `recetas.titulo` y `recetas.preparacion` si el motor lo soporta; si no, cae a una busqueda `LIKE` tradicional.
- La recuperacion de contraseña en local sigue usando almacenamiento en disco para no depender de correo real.
