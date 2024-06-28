<?php

class Map {

  public static function fromJsonFile(string $path, string $message = 'Unable load json file'): Map {
    $json = Common::json_load_or_fail($path, $message);
    return new Map($json);
  }

  public static function of(array $value): Map {
    return new Map($value);
  }

  private array $value;

  public function __construct(array $value) {
    $this->value = $value;
  }

  public function contains(string $key): bool {
    return Common::array_contains_key($this->value, $key);
  }

  public function sub(string $key, mixed $default): Map {
    return Map::of(Common::array_get_value($this->value, $key, $default));
  }

  public function get(string $key, mixed $default) {
    return Common::array_get_value($this->value, $key, $default);
  }

  public function getOrFail(string $key, string $message = 'Extract value failed') {
    return Common::array_get_value_or_fail($this->value, $key, $message . ' ' . $key);
  }

  public function getBool(string $key, mixed $default) {
    return filter_var(Common::array_get_value($this->value, $key, $default), FILTER_VALIDATE_BOOLEAN);
  }

  public function getValue(): array {
    return $this->value;
  }

}
