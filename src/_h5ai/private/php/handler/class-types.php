<?php

class Types extends Handler {

  public function __construct(?Map $settings) { }

  function apply(?Map $requests): array {
    return Types::load();
  }

  public static function load(): array {
    return Common::json_load_or_fail(G::CONF_TYPES, 'Load types schema failed');
  }

}


