# SeederLab

SeederLab es una utilidad PHP para poblar bases de datos MySQL con datos **realistas**, **variados** y **reproducibles**.  
Ideal para pruebas, desarrollo y generación de datos de ejemplo con control fino sobre reglas, relaciones y formatos.

---

## 🚀 Características principales

- **Generadores semánticos** por nombre de campo (`fakeEmail`, `fakeFullname`, `fakeProduct`, etc.).
- **Reglas configurables** por campo global o por `tabla.campo`.
- **Soporte de claves foráneas** leyendo `INFORMATION_SCHEMA`.
- **Garantía de unicidad** para columnas con índice UNIQUE.
- **Batching y transacciones** para cargas seguras y eficientes.
- **Carga de catálogos externos** en JSON para ampliar variaciones sin tocar código.
- **Hooks** para personalizar flujo: `beforeTable`, `afterTable`, `beforeInsert`, `afterInsert`.
- **Modo dry-run** para inspección sin modificar la base.
- **Determinismo opcional** con `setSeed()` para reproducibilidad en tests.

---

## 📦 Instalación

### Requisitos
- PHP 8.0 o superior.
- PDO MySQL habilitado.
- Permisos para leer `INFORMATION_SCHEMA` y escribir en las tablas destino.

### Instalación manual
1. Copia `src/SeederLab.php` en tu proyecto.
2. Incluye la clase en tu script:

```php
require_once __DIR__ . '/src/SeederLab.php';

$dsn = 'mysql:host=127.0.0.1;dbname=my_db;charset=utf8mb4';
$seeder = new SeederLab($dsn, 'db_user', 'db_pass', 'my_db');
```

---

## 🛠️ Uso rápido

### Instancia básica
```php
$dsn = 'mysql:host=127.0.0.1;dbname=my_db;charset=utf8mb4';
$seeder = new SeederLab($dsn, 'user', 'pass', 'my_db', 200);
```
### Seed simple
```php
$seeder->seed('users', 50);
```

### Dry run y recolección de filas
```php
$seeder->setDryRun(true);
$rows = $seeder->seed('users', 5);
print_r($rows);
```

### Dry run con handler
```php
$seeder->setDryRun(true);
$seeder->setDryRunHandler(function(array $row, string $table, int $index) {
    echo "[$table][$index] " . json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
});
$seeder->seed('products', 10);
```

### Reglas personalizadas
```php
// Regla global por campo
$seeder->addRule('email', fn() => 'generic@example.com');

// Regla específica por tabla
$seeder->addRule('users.email', fn() => 'no-reply@users.example');

$seeder->seed('users', 10);
```

### Reproducibilidad
```php
$seeder->setSeed(12345); // hace las generaciones deterministas
$seeder->seed('orders', 20);
```

---

## ⚙️ Configuración externa y catálogos
SeederLab puede cargar un archivo JSON con catalogs y rules. Los catálogos se fusionan con los arrays internos para aumentar variaciones; las reglas definen generadores por tabla.

Ejemplo seeder_config.json

```json
{
  "catalogs": {
    "firstNames": ["Alejo","María","Luis"],
    "lastNames": ["García","Pérez","López"],
    "companyPrefixes": ["Seed","Forge"],
    "companySuffixes": ["Corp","Solutions"]
  },
  "rules": {
    "users": {
      "name": "fakeName",
      "email": "fakeEmail",
      "created_at": "datetime"
    },
    "products": {
      "product": "fakeProduct",
      "price": "decimal",
      "sku": "fakeUuid"
    }
  }
}
```
### Cargar configuración
```php
$seeder->loadConfig(__DIR__ . '/seeder_config.json');
```

---

## 📚 API referencia

### Control de ejecución
- ```setDryRun(bool $enable = true)``` activa/desactiva dry run.

- ```setDryRunHandler(callable $handler)``` define un handler para cada fila en dry run.

- ```setSeed(int $seed)``` fija la semilla para reproducibilidad.

### Reglas y hooks
- ```addRule(string $key, callable $generator)``` registra reglas (field o table.field).
- ```removeRule(string $key)``` elimina una regla.
- ```on(string $hook, callable $fn)``` registra hooks (beforeTable, afterTable, beforeInsert, afterInsert).

### Configuración
- ```loadConfig(string $file)``` carga JSON con catalogs y rules.

### Operación
- ```seed(string $table, int $count = 10): array``` genera e inserta filas. Si ```dryRun``` está activo devuelve las filas generadas o llama al handler.

---

## 🔧 Ejemplos avanzados
### Hook para logging
```php
$seeder->on('beforeTable', function($ctx) {
    echo "Sembrando tabla: " . $ctx['table'] . " cantidad: " . $ctx['count'] . PHP_EOL;
});
```

### Generador personalizado
```php
$seeder->addRule('users.username', function() {
    return 'u_' . bin2hex(random_bytes(4));
});
```

---

## ✅ Buenas prácticas
- Siempre probar con dry run antes de insertar en entornos sensibles.
- Sembrar tablas referenciadas primero para evitar errores por claves foráneas.
- Usar setSeed en tests para reproducibilidad.
- Mantener catálogos en JSON para que el equipo pueda ampliar datos sin tocar código.
- Ajustar batchSize según la capacidad de la base de datos.
- Registrar hooks para auditar o transformar filas problemáticas.

---

## 🐛 Resolución de problemas
- **Error por clave foránea:** *siembra primero la tabla referenciada*.
- **Excepción por unicidad:** *revisa el generador del campo o aumenta intentos*.
- **Rendimiento lento:** aumenta *batchSize o desactiva índices temporalmente en entornos controlados*.
- **Problemas con loadConfig:** *valida JSON y confirma estructura catalogs y rules*.

---

## 🤝 Contribuciones
- Haz fork del repositorio.
- Crea un branch descriptivo: git checkout -b feat/nueva-funcion.
- Implementa tu cambio y añade pruebas si aplica.
- Abre un Pull Request describiendo el cambio y por qué es útil.

---

## 📄 Licencia
SeederLab está bajo **licencia MIT**. Consulta el archivo ```LICENSE``` para más detalles.
