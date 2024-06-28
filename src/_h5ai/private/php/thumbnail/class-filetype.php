<?php

class FileType {

  public string $name;
  private bool $name_changed;

  public function __construct(string $name = null) {
    // TODO parse file extension like the client does here.
    $this->name = $name;
    $this->name_changed = false;
  }

  public function __get($property) {
    if (property_exists($this, $property)) {
      return $this->$property;
    }
  }

  public function __set($property, $value) {

    if (property_exists($this, $property)) {

      $pv = $this->name; // value copy

      if ($value !== $this->$property) {
        $this->$property = $value;
        if ($this->name !== $pv) {
          $this->name_changed = true;
        }
      }

    }

    return $this;
  }

  public function was_wrong(): false {
    // We assume that if is has changed, it must have been wrong at the beginning.
    return $this->name_changed;
  }

  public function mime_to_type($mime): int|string {

    $instance = new Types(null);
    $types = $instance->apply(null);

    foreach ($types as $key => $values) {
      if (count($values) < 2) {
        // No mime found, only glob.
        continue;
      }
      foreach ($values['mime'] as $test) {
        // TODO use a regex in types.json instead, for better precision.
        if (str_contains($mime, $test)) {
          return $key;
        }
      }
    }
    return 'file';
  }
}
