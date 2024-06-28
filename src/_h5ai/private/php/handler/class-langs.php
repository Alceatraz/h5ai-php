<?php

class Langs extends Handler {

  public function __construct(?Map $settings) {  }

  function apply(?Map $requests): array {
    return Langs::load();
  }

  public static function load(): array {
    $result = [];
    if ($open = opendir(G::DATA_L10N)) {
      while (($file = readdir($open)) !== false) {
        if ($file === '.') continue;
        if ($file === '..') continue;
        if (!str_ends_with($file, '.json')) continue;
        $l10n = Common::json_load_or_fail(G::DATA_L10N . '/' . $file, 'Load l10n failed -> ' . $file);
        $name = $l10n['lang'];
        if ($name == null) continue;
        $result[basename($file, '.json')] = $name;
      }
      closedir($open);
    }
    ksort($result);
    return $result;
  }

}
