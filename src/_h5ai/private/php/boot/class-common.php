<?php

class Common {

  public static function execute($cmd_v): array {
    $lines = [];
    exec($cmd_v, $lines);
    return $lines;
  }

  public static function proc_open($cmd_v, &$stdout, &$stderr): null|int {

    $command = implode(' ', array_map('escapeshellarg', $cmd_v));

    Logger::debug('proc_open -> ' . $command);

    $fd_0 = ["pipe", "r"];
    $fd_1 = ["pipe", "w"];
    $fd_2 = ["pipe", "w"];

    $spec = [$fd_0, $fd_1, $fd_2];

    $process = proc_open($command, $spec, $pipes);

    if (!is_resource($process)) return -1068425;

    fclose($pipes[0]);

    if (is_resource($stdout)) {
      stream_copy_to_stream($pipes[1], $stdout);
    } else {
      $stdout = stream_get_contents($pipes[1]);
    }

    fclose($pipes[1]);

    if (is_resource($stderr)) {
      stream_copy_to_stream($pipes[2], $stderr);
    } else {
      $stderr = stream_get_contents($pipes[2]);
    }

    fclose($pipes[2]);

    return proc_close($process);
  }

  public static function json_load_or_fail($path, $message = null): array {
    $text = file_get_contents($path);
    $json = json_decode($text, true);
    if (json_last_error() != JSON_ERROR_NONE) {
      Logger::fatal('json_load_or_fail ' . $path . ' = ' . $text . ' -> ' . json_last_error_msg());
      exit($message);
    }
    return $json;
  }

  public static function array_contains_key($array, $key): bool {
    $node = $array;
    foreach (explode('.', $key) as $it) {
      if (is_array($node) && array_key_exists($it, $node)) {
        $node = $node[$key];
      } else {
        return false;
      }
    }
    return true;
  }

  public static function array_get_value($array, $key, $value) {
    $node = $array;
    foreach (explode('.', $key) as $it) {
      if (is_array($node) && array_key_exists($it, $node)) {
        $node = $node[$it];
      } else {
        return $value;
      }
    }
    return $node;
  }

  public static function array_get_value_or_fail($array, $key, $message) {
    $node = $array;
    foreach (explode('.', $key) as $it) {
      if (is_array($node) && array_key_exists($it, $node)) {
        $node = $node[$it];
      } else {
        Logger::fatal($message . ', array_get_value_or_fail key=' . $key . ' find=' . $it . ' -> ', $node);
        exit($message);
      }
    }
    return $node;
  }

  public static function traversal_href_check($path): bool|string {
    return self::traversal_path_check(G::ROOT_PATH . '/' . $path);
  }

  public static function traversal_path_check($path): bool|string {
    if ($path === '/') return G::ROOT_PATH;
    $realpath = realpath($path);
    if (str_starts_with($realpath, G::ROOT_PATH)) {
      // Logger::trace("traversal_check ALLOWED " . $path . ' -> ' . $realpath);
      return $realpath;
    } else {
      // Logger::trace("traversal_check ILLEGAL " . $path . ' -> ' . $realpath);
      return false;
    }
  }
}
