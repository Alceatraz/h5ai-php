<?php /** @noinspection PhpUnused */

class Call {

  private const string HEADER_HTML = 'Content-type: text/html; charset=utf-8';
  private const string HEADER_TEXT = 'Content-type: text/plain; charset=utf-8';
  private const string HEADER_JSON = 'Content-type: application/json; charset=utf-8';

  public static function respondHero($text): void {
    Call::respondHtml('<?DOCTYPE shtml><html lang="en"><head><meta charset="UTF-8"><title>H5ai</title></head><body><h1>' . $text . '</h1></body></html>');
  }

  public static function respondHtml($html): void {
    header(Call::HEADER_HTML);
    exit($html);
  }

  public static function respondText($text): void {
    header(Call::HEADER_TEXT);
    exit($text);
  }

  public static function respondJson($json, $options = 0): void {
    header(Call::HEADER_JSON);
    exit(json_encode($json, $options));
  }

  public static function failure($err, $msg = ''): void {
    Call::respondJson(['err' => $err, 'msg' => $msg]);
  }

  public static function required($err, $msg, $condition): void {
    if (!$condition) Call::respondJson(['err' => $err, 'msg' => $msg]);
  }

}

