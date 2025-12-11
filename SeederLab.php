<?php
declare(strict_types=1);

/**
 * SeederLab
 * Generador semántico, extensible y reproducible de datos para MySQL.
 * - Determinismo (setSeed)
 * - Unicidad garantizada para índices UNIQUE
 * - Batching + transacciones
 * - Carga de catálogos (JSON)
 * - Hooks y reglas por campo / table.field
 * - Modo dry-run con handler
 */
class SeederLab
{
  private \PDO $pdo;
  private string $dbName;
  private array $customRulesField = [];
  private array $customRulesTableField = [];
  private array $config = [];
  private array $hooks = [];
  private int $seed = 0;
  private int $batchSize = 500;

  private bool $dryRun = false;
  private $dryRunHandler = null;

  private array $firstNames = ["Carlos","María","José","Ana","Luis","Valentina","Pedro","Sofía","Andrés","Camila","Miguel","Isabella","Juan","Gabriela","Fernando","Lucía","Diego","Paola","Ricardo","Daniela","Sebastián","Alejandra","Manuel","Patricia","Jorge","Rosa","Héctor","Mónica","Ángel","Verónica","Esteban","Carolina","Martín","Julieta","Raúl","Adriana","Felipe","Claudia","Gustavo","Lorena","Alberto","Marisol","Cristian","Beatriz","Hernán","Natalia","Oscar","Marina","Pablo","Elena"];
  private array $lastNames = ["García","Rodríguez","Martínez","Hernández","López","González","Pérez","Fernández","Torres","Ramírez","Castro","Morales","Vargas","Jiménez","Silva","Rojas","Mendoza","Ortega","Delgado","Guerrero","Cordero","Suárez","Reyes","Campos","Navarro","Peña","Cabrera","Salazar","Aguilar","Soto","Vega","Fuentes","Bravo","Pacheco","Acosta","Mejía","Palacios","Villanueva","Montoya","Escobar","Valencia","Carrillo","Arrieta","Domínguez","Quiroz","Rivera","Santana","Medina","Solís","Bermúdez"];

  private array $companyPrefixes = ["Tech","Global","Innova","Next","Data","Smart","Prime","Future","Cloud","Digital","Green","Blue","Red","Quantum","Vision","Creative","Dynamic","Elite","Nova","Hyper","Apex","Vertex","Core","Bright","Summit","Pioneer","Fusion","Matrix","Orbit","Pulse"];
  private array $companySuffixes = ["Corp","Solutions","Systems","Group","Labs","Works","Industries","Partners","Networks","Studio","Consulting","Enterprises","Holdings","Technologies","Services","International","Logistics","Media","Software","Hardware","Dynamics","Ventures","Collective","Design","Factory","Hub","Alliance","Resources","Concepts","Innovations"];

  private array $streetTypes = ["Av.","Calle","Carrera","Boulevard","Pasaje","Camino","Vereda","Plaza","Vía","Paseo"];
  private array $streetNames = ["Bolívar","Sucre","Libertador","Miranda","Los Próceres","Independencia","San Martín","Altamira","Chacao","La Castellana","Rojas","Urdaneta","Crespo","Rómulo"];
  private array $zones = ["Centro","Norte","Sur","Este","Oeste","Industrial","Residencial","Comercial","Antiguo","Moderno"];

  private array $productCategories = ["Laptop","Smartphone","Tablet","Camera","Headphones","Monitor","Keyboard","Mouse","Printer","Speaker","Router","SSD","Smartwatch"];
  private array $productModels = ["Pro","Max","Lite","Plus","Ultra","X","S","Mini","Edge","Prime","Neo","One","Z"];

  private array $marketingAdjectives = ["Exclusive","Limited","Premium","Top-rated","Unbeatable","Special","Incredible","Amazing","Unique","Fantastic","Essential","Ultimate"];
  private array $marketingActions = ["offer","deal","discount","promotion","sale","opportunity","bonus","package","bundle","campaign","clearance"];
  private array $marketingBenefits = ["just for you","save big","guaranteed satisfaction","don’t miss it","best in market","limited edition","while supplies last","trusted worldwide","customer favorite","award-winning","with free shipping","with extended warranty"];

  private array $descriptionIntros = ["This product is designed to","Our solution helps you","Experience the power to","Discover how you can","We provide tools to","Engineered to","Built to"];
  private array $descriptionDetails = ["improve efficiency","save time","boost productivity","enhance performance","simplify tasks","increase security","reduce costs","maximize results","streamline workflows","optimize operations"];
  private array $descriptionClosings = ["with ease.","like never before.","at an affordable price.","for everyday use.","trusted by professionals.","with guaranteed quality.","for modern needs.","with cutting-edge technology.","for small businesses and enterprises.","for seamless integration."];

  private array $cities = ["Caracas","Valencia","Maracay","Barquisimeto","Maracaibo","Puerto Ordaz","Mérida","Barcelona","Cumaná","San Cristóbal","Trujillo","Ciudad Bolívar","Porlamar","Cabimas","Acarigua"];
  private array $regions = ["Distrito Capital","Carabobo","Aragua","Lara","Zulia","Bolívar","Mérida","Anzoátegui","Táchira","Trujillo"];

  private array $countries = ["Venezuela","Colombia","Argentina","Chile","México","España","Perú","Brasil","Uruguay","Paraguay","Ecuador","Bolivia"];
  private array $countryCodes = ["VE","CO","AR","CL","MX","ES","PE","BR","UY","PY","EC","BO"];

  private array $jobs = ["Developer","Designer","Manager","Analyst","Engineer","Consultant","Administrator","Architect","Product Manager","QA Engineer","Support Specialist","Data Scientist"];
  private array $colors = ["Red","Blue","Green","Yellow","Black","White","Orange","Purple","Gray","Silver","Gold","Teal"];
  private array $tags = ["tech","business","health","sports","education","travel","finance","food","fashion","gaming","music","science"];
  private array $languages = ["English","Spanish","French","German","Portuguese","Italian","Dutch","Russian","Chinese","Japanese"];

  public function __construct(string $dsn, string $user, string $password, string $dbName, int $batchSize = 500)
  {
    $this->pdo = new \PDO($dsn, $user, $password, [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);
    $this->dbName = $dbName;
    $this->batchSize = max(1, (int)$batchSize);
  }

    // === Determinismo ===
  public function setSeed(int $seed): void
  {
    $this->seed = $seed;
    mt_srand($seed);
  }

    // === Dry-run control ===
  public function setDryRun(bool $enable = true): void
  {
    $this->dryRun = $enable;
  }

  public function setDryRunHandler(callable $handler): void
  {
    $this->dryRunHandler = $handler;
  }

    // === Rules API (flexible) ===
  public function addRule(string $key, callable $generator): void
  {
    $key = strtolower($key);
    if (str_contains($key, '.')) {
      $this->customRulesTableField[$key] = $generator;
    } else {
      $this->customRulesField[$key] = $generator;
    }
  }

  public function removeRule(string $key): void
  {
    $key = strtolower($key);
    if (str_contains($key, '.')) {
      unset($this->customRulesTableField[$key]);
    } else {
      unset($this->customRulesField[$key]);
    }
  }

    // === Hooks ===
  public function on(string $hook, callable $fn): void
  {
    $this->hooks[$hook][] = $fn;
  }

  private function trigger(string $hook, array $ctx = []): void
  {
    foreach ($this->hooks[$hook] ?? [] as $h) {
      $h($ctx);
    }
  }

    // === Load external config (JSON) ===
  public function loadConfig(string $file): void
  {
    if (!is_file($file)) throw new \InvalidArgumentException("Config file not found: $file");
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $content = file_get_contents($file);
    if ($ext === 'json') {
      $decoded = json_decode($content, true);
      if (!is_array($decoded)) throw new \RuntimeException("Invalid JSON config");
      if (isset($decoded['catalogs']) && is_array($decoded['catalogs'])) {
        $this->mergeCatalogs($decoded['catalogs']);
      }
      if (isset($decoded['rules']) && is_array($decoded['rules'])) {
        $this->config = $decoded['rules'];
      } else {
        $this->config = $decoded;
      }
    } else {
      throw new \InvalidArgumentException("Unsupported config format: $ext");
    }
  }

  private function mergeCatalogs(array $catalogs): void
  {
    foreach ($catalogs as $k => $arr) {
      if (!is_array($arr)) continue;
      $prop = lcfirst($k);
      if (property_exists($this, $prop) && is_array($this->$prop)) {
        $this->$prop = array_merge($this->$prop, $arr);
      }
    }
  }

    // === Seed table (batch + transactions + uniqueness + dry-run) ===
  public function seed(string $table, int $count = 10): array
  {
    $columns = $this->pdo->query("DESCRIBE `$table`")->fetchAll();
    $cols = array_filter($columns, fn($c) => ($c['Extra'] ?? '') !== 'auto_increment');
    $foreignKeys = $this->getForeignKeys($table);
    $uniqueColumns = $this->getUniqueColumns($table);

    $colNames = array_map(fn($c) => $c['Field'], $cols);
    $placeholders = array_map(fn($c) => ":{$c['Field']}", $cols);
    $sql = sprintf("INSERT INTO `%s` (%s) VALUES (%s)", $table, implode(", ", $colNames), implode(", ", $placeholders));
    $stmt = $this->pdo->prepare($sql);

    $this->trigger('beforeTable', ['table' => $table, 'count' => $count]);

    $rowsDryRun = [];

    if (!$this->dryRun) {
      $this->pdo->beginTransaction();
    }

    try {
      $inserted = 0;
      for ($i = 0; $i < $count; $i++) {
        $data = [];
        foreach ($cols as $col) {
          $field = $col['Field'];

          $tableFieldKey = strtolower($table . '.' . $field);
          if (isset($this->customRulesTableField[$tableFieldKey])) {
            $data[":$field"] = ($this->customRulesTableField[$tableFieldKey])();
            continue;
          }

          $fname = strtolower($field);
          if (isset($this->customRulesField[$fname])) {
            $data[":$field"] = ($this->customRulesField[$fname])();
            continue;
          }

          if (isset($this->config[$table]) && is_array($this->config[$table])) {
            $tableRules = array_change_key_case($this->config[$table], CASE_LOWER);
            if (isset($tableRules[$fname])) {
              $rule = $tableRules[$fname];
              $method = (str_starts_with($rule, 'fake') ? $rule : 'fake' . ucfirst($rule));
              if (method_exists($this, $method)) {
                $data[":$field"] = $this->$method();
                continue;
              }
              $data[":$field"] = $this->generateByType((string)$rule, $field);
              continue;
            }
          }

          if (isset($foreignKeys[$field])) {
            $refTable = $foreignKeys[$field]['REFERENCED_TABLE_NAME'];
            $refIds = $this->pdo->query("SELECT id FROM `$refTable`")->fetchAll(\PDO::FETCH_COLUMN);
            if (empty($refIds)) throw new \RuntimeException("No records found in referenced table: $refTable");
            $data[":$field"] = $refIds[array_rand($refIds)];
            continue;
          }

          $data[":$field"] = $this->generateSmartValue($col['Type'], $field, $table);
        }

        foreach ($uniqueColumns as $uniqueField) {
          if (!isset($data[":$uniqueField"])) continue;
          $value = $data[":$uniqueField"];
          if ($this->valueExists($table, $uniqueField, $value)) {
            $gen = function() use ($table, $uniqueField) {
              $tfk = strtolower($table . '.' . $uniqueField);
              if (isset($this->customRulesTableField[$tfk])) return ($this->customRulesTableField[$tfk])();
              if (isset($this->customRulesField[$uniqueField])) return ($this->customRulesField[$uniqueField])();
              $method = 'fake' . ucfirst($uniqueField);
              if (method_exists($this, $method)) return $this->$method();
              return $this->generateByType('varchar', $uniqueField);
            };
            $data[":$uniqueField"] = $this->uniqueOrGenerate($table, $uniqueField, $gen, 20);
          }
        }

        $this->trigger('beforeInsert', ['table' => $table, 'row' => $data, 'index' => $i]);

        if ($this->dryRun) {
          $rowPlain = [];
          foreach ($data as $k => $v) $rowPlain[substr($k, 1)] = $v;
          if (is_callable($this->dryRunHandler)) {
            call_user_func($this->dryRunHandler, $rowPlain, $table, $i);
          } else {
            $rowsDryRun[] = $rowPlain;
          }
          $this->trigger('afterInsert', ['table' => $table, 'row' => $data, 'index' => $i]);
          $inserted++;
          continue;
        }

        $stmt->execute($data);
        $inserted++;
        $this->trigger('afterInsert', ['table' => $table, 'row' => $data, 'index' => $i]);

        if (($inserted % $this->batchSize) === 0) {
          $this->pdo->commit();
          $this->pdo->beginTransaction();
        }
      }

      if (!$this->dryRun) $this->pdo->commit();
    } catch (\Throwable $e) {
      if (!$this->dryRun) $this->pdo->rollBack();
      throw $e;
    }

    $this->trigger('afterTable', ['table' => $table, 'inserted' => $inserted]);

    return $rowsDryRun;
  }

    // === Helpers ===
  private function getForeignKeys(string $table): array
  {
    $sql = "
    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND REFERENCED_TABLE_NAME IS NOT NULL
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':db' => $this->dbName, ':table' => $table]);
    $rows = $stmt->fetchAll();
    $fks = [];
    foreach ($rows as $row) $fks[$row['COLUMN_NAME']] = $row;
    return $fks;
  }

  private function getUniqueColumns(string $table): array
  {
    $sql = "
    SELECT COLUMN_NAME
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND NON_UNIQUE = 0 AND INDEX_NAME != 'PRIMARY'
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':db' => $this->dbName, ':table' => $table]);
    $cols = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    return $cols ?: [];
  }

  private function valueExists(string $table, string $field, $value): bool
  {
    $stmt = $this->pdo->prepare("SELECT 1 FROM `$table` WHERE `$field` = :v LIMIT 1");
    $stmt->execute([':v' => $value]);
    return (bool)$stmt->fetchColumn();
  }

  private function uniqueOrGenerate(string $table, string $field, callable $gen, int $tries = 10)
  {
    for ($i = 0; $i < $tries; $i++) {
      $val = $gen();
      if (!$this->valueExists($table, $field, $val)) return $val;
    }
    throw new \RuntimeException("Unable to generate unique value for $table.$field after $tries tries");
  }

  private function generateSmartValue(string $type, string $field, string $table = ''): mixed
  {
    $fname = strtolower($field);
    $method = 'fake' . ucfirst($fname);
    if (method_exists($this, $method)) return $this->$method();
    return $this->generateByType($type, $field);
  }

  private function generateByType(string $type, string $field): mixed
  {
    $type = strtolower($type);
    switch (true) {
      case str_contains($type, 'tinyint'): return rand(0, 127);
      case str_contains($type, 'smallint'): return rand(0, 32767);
      case str_contains($type, 'mediumint'): return rand(0, 8388607);
      case str_contains($type, 'int'): return rand(0, 2147483647);
      case str_contains($type, 'bigint'): return (string)rand(0, PHP_INT_MAX);
      case str_contains($type, 'decimal'):
      case str_contains($type, 'numeric'): return round(mt_rand(100, 10000) / 100, 2);
      case str_contains($type, 'float'):
      case str_contains($type, 'double'): return (float)rand() / (float)getrandmax();
      case str_contains($type, 'char'):
      case str_contains($type, 'varchar'): return ucfirst($field) . "_" . uniqid();
      case str_contains($type, 'text'): return $this->fakeDescription();
      case str_contains($type, 'date'): return date("Y-m-d", rand(strtotime("2000-01-01"), time()));
      case str_contains($type, 'datetime'):
      case str_contains($type, 'timestamp'): return date("Y-m-d H:i:s", rand(strtotime("2000-01-01"), time()));
      case str_contains($type, 'time'): return date("H:i:s", rand(0, 86400));
      case str_contains($type, 'year'): return (string)rand(1970, (int)date("Y"));
      case str_contains($type, 'blob'):
      case str_contains($type, 'binary'): return random_bytes(8);
      default: return "Val_" . uniqid();
    }
  }

    // === Generadores dinámicos (ejemplos) ===
  private function fakeName(): string { return $this->firstNames[array_rand($this->firstNames)]; }
  private function fakeLastname(): string { return $this->lastNames[array_rand($this->lastNames)]; }
  private function fakeFullname(): string { return $this->fakeName() . " " . $this->fakeLastname(); }
  private function fakeEmail(): string { $domains = ["example.com","test.com","mail.com","demo.org","company.io"]; $local = strtolower(preg_replace('/[^a-z]/','',$this->fakeName())) . rand(10,9999); return $local . "@" . $domains[array_rand($domains)]; }
  private function fakeUsername(): string { $parts = ["user","member","guest","dev","pro","team"]; return $parts[array_rand($parts)] . "_" . strtolower(substr(uniqid(), -6)); }
  private function fakePhone(): string { return "+58" . (string)rand(400000000, 499999999); }
  private function fakeCompany(): string { return $this->companyPrefixes[array_rand($this->companyPrefixes)] . " " . $this->companySuffixes[array_rand($this->companySuffixes)]; }
  private function fakeCompanyEmail(): string { $domain = strtolower(preg_replace('/[^a-z]/','',str_replace(' ','',$this->fakeCompany()))) . ".com"; return strtolower($this->fakeName()) . "@" . $domain; }
  private function fakeCompanyPhone(): string { return "+1" . rand(2000000000, 9999999999); }
  private function fakeAddress(): string { return $this->streetTypes[array_rand($this->streetTypes)] . " " . $this->streetNames[array_rand($this->streetNames)] . " #" . rand(1,999) . ", " . $this->zones[array_rand($this->zones)]; }
  private function fakeCity(): string { return $this->cities[array_rand($this->cities)] . ", " . $this->regions[array_rand($this->regions)]; }
  private function fakeCountry(): string { $i = array_rand($this->countries); return $this->countries[$i] . " (" . $this->countryCodes[$i] . ")"; }
  private function fakeZip(): string { return str_pad((string)rand(1000,99999), 5, "0", STR_PAD_LEFT); }
  private function fakeProduct(): string { return $this->productCategories[array_rand($this->productCategories)] . " " . $this->productModels[array_rand($this->productModels)] . " " . rand(100,999); }
  private function fakeMarketing(): string { return $this->marketingAdjectives[array_rand($this->marketingAdjectives)] . " " . $this->marketingActions[array_rand($this->marketingActions)] . " - " . $this->marketingBenefits[array_rand($this->marketingBenefits)]; }
  private function fakeDescription(): string { return $this->descriptionIntros[array_rand($this->descriptionIntros)] . " " . $this->descriptionDetails[array_rand($this->descriptionDetails)] . " " . $this->descriptionClosings[array_rand($this->descriptionClosings)]; }
  private function fakeJob(): string { return $this->jobs[array_rand($this->jobs)]; }
  private function fakeColor(): string { return $this->colors[array_rand($this->colors)]; }
  private function fakeTag(): string { return $this->tags[array_rand($this->tags)]; }
  private function fakeLanguage(): string { return $this->languages[array_rand($this->languages)]; }
  private function fakeIp(): string { return rand(1,255) . "." . rand(0,255) . "." . rand(0,255) . "." . rand(1,255); }
  private function fakeUuid(): string { return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)); }
  private function fakeCurrency(): string { $currencies = ["USD","EUR","GBP","JPY","VES","BRL","CAD","AUD"]; return $currencies[array_rand($currencies)]; }
  private function fakePrice(): float { return round(mt_rand(100, 100000) / 100, 2); }
  private function fakeBoolean(): bool { return (bool)rand(0,1); }
  private function fakeStatus(): string { $statuses = ["active","inactive","pending","archived","deleted"]; return $statuses[array_rand($statuses)]; }
  private function fakeUrl(): string { $domains = ["example.com","mysite.org","demo.net","shop.io"]; return "https://" . $domains[array_rand($domains)] . "/" . strtolower(preg_replace('/[^a-z0-9]/','',$this->fakeProduct())); }
  private function fakeAge(): int { $mean = 35; $std = 12; $u = mt_rand()/mt_getrandmax(); $v = mt_rand()/mt_getrandmax(); $z = sqrt(-2*log(max(1e-9,$u))) * cos(2*M_PI*$v); $age = (int)round($mean + $std*$z); return max(18, min(90, $age)); }
}
