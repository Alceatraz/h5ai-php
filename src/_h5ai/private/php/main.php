<?php

spl_autoload_register(callback: function ($clazz) {
  $filename = 'class-' . strtolower($clazz) . '.php';
  foreach (['boot', 'handler', 'preview', 'thumbnail'] as $it) {
    $file = __DIR__ . '/' . $it . '/' . $filename;
    if (!file_exists($file)) continue;
    require_once $file;
  }
});

putenv('LANG=en_US.UTF-8');
setlocale(LC_CTYPE, 'en_US.UTF-8');
date_default_timezone_set(@date_default_timezone_get());

$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

switch ($REQUEST_METHOD) {

  case 'GET':
    header('Content-type: text/html; charset=utf-8');
    require __DIR__ . '/template/index.php';
    break;

  case 'POST':
    $settings = Map::fromJsonFile(G::CONF_SETTINGS);
    Api::of($settings)->apply();
    break;

  default:
    Logger::info("Default request ", $_REQUEST);
    exit;
}



