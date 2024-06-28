<?php /** @noinspection PhpUnused */

class Logger {

  private const int TRACE = 10;
  private const int DEBUG = 20;
  private const int INFO = 50;
  private const int WARN = 80;
  private const int ERROR = 90;
  private const int FATAL = 100;

  private static int $LEVEL = 0;

  private static function println(string $prefix, string $message, mixed $object = null): void {
    if ($object == null) {
      @error_log('[' . $prefix . '] ' . $message);
    } else {
      @error_log('[' . $prefix . '] ' . $message . ' ' . var_export($object, true));
    }
  }

  public static function setLevel(int $level): void {
    self::$LEVEL = $level;
  }

  public static function trace(string $message, mixed $object = null): void {
    if (self::$LEVEL <= self::TRACE) self::println('TRACE', $message, $object);
  }

  public static function debug(string $message, mixed $object = null): void {
    if (self::$LEVEL <= self::DEBUG) self::println('DEBUG', $message, $object);
  }

  public static function info(string $message, mixed $object = null): void {
    if (self::$LEVEL <= self::INFO) self::println('INFO', $message, $object);
  }

  public static function warn(string $message, mixed $object = null): void {
    if (self::$LEVEL <= self::WARN) self::println('WARN', $message, $object);
  }

  public static function error(string $message, mixed $object = null): void {
    if (self::$LEVEL <= self::ERROR) self::println('ERROR', $message, $object);
  }

  public static function fatal(string $message, mixed $object = null): void {
    if (self::$LEVEL <= self::FATAL) self::println('FATAL', $message, $object);
  }

}
