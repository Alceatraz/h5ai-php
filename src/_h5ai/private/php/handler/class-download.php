<?php

class Download extends Handler {

  private bool $enableBatch;
  private bool $enableFolder;

  private bool $enableExclusive;

  private bool $enableRecursive;

  private array $hideRegex;

  public function __construct(Map $settings) {

    $this->enableBatch = $settings->get('appearance.feature.download.enable', true);
    $this->enableFolder = $settings->get('appearance.feature.download.enableFolder', true);

    $this->enableRecursive = $settings->get('preference.download.recursive', true);
    $this->enableExclusive = $settings->get('preference.download.exclusive.enable', true);

    if ($this->enableExclusive) {
      return;
    }

    $this->hideRegex = $settings->get('preference.download.exclusive.extra-regex', []);

    $global = $settings->get('preference.download.exclusive.global', true);

    if ($global) {
      $this->hideRegex = array_merge($settings->get('preference.general.global-exclusive', true), $this->hideRegex);
    }

  }

  // action: download
  // as: xxxxxxxxxxx.zip
  // type: shell-zip
  // baseHref: /
  // hrefs:

  // action: download
  // as: xxxxxxxxxxx.zip
  // type: shell-zip
  // baseHref: /
  // hrefs:
  // hrefs[0]: /package.json
  // hrefs[1]: /README.md
  // hrefs[2]: /test.go

  //    Logger::debug('REQUEST -> ', $_REQUEST);

  function apply(Map $requests): array {

    if (!$this->enableBatch) Call::respondHero('Batch download not enabled');

    $baseHref = $_REQUEST['baseHref'];
    $realBase = Common::traversal_href_check($baseHref);

    $prefixLength = strlen($realBase);

    if (!$realBase) Call::respondHero('Sorry, But we can\'t handle this request.');

    $hrefs = $_REQUEST['hrefs'];

    $list = [];

    if (is_array($hrefs)) {
      // $hrefLength = strlen($baseHref);
      foreach ($hrefs as $href) {
        $realFile = Common::traversal_href_check($href);
        if (!$realFile) Call::respondHero('Sorry, But we can\'t handle this request.');
        // $name = substr($href, $hrefLength);
        $name = basename($href);
        Logger::debug('ADD S file ' . $name . '-> ' . $realFile);
        $list[] = $name;
      }
    } else {

      if (!$this->enableFolder) Call::respondText('Folder download not enabled');

      foreach (scandir($realBase) as $name) {
        // if (is_link($name)) continue;
        if ($name === '.') continue;
        if ($name === '..') continue;
        if (str_starts_with('_h5ai', $name)) continue;
        // Logger::debug('ADD D file ' . $name);
        $list[] = $name;
      }
    }

    // Logger::debug('Download list -> ', $list);

    mkdir(G::CACHE_PRV_PATH . '/download');

    $fileList = G::CACHE_PRV_PATH . '/download/task-' . uniqid();

    foreach ($list as $name) {
      if ($this->enableExclusive) foreach ($this->hideRegex as $regex) {
        if (preg_match($name, $regex)) continue 2;
      }
      $realpath = realpath($realBase . '/' . $name);
      $realpath = substr($realpath, $prefixLength + 1);
      file_put_contents($fileList, $realpath . "\n", FILE_APPEND);
      // Logger::debug('APPEND file ' . $realpath);
    }

    if ($this->enableRecursive) {
      $cmd_v = 'cd ' . $realBase . ' && tar -T ' . $fileList . ' -c --';
    } else {
      $cmd_v = 'cd ' . $realBase . ' && tar -T ' . $fileList . ' --no-recursion -c --';
    }

    Logger::debug('download command -> ', $cmd_v);

    header('Connection: close');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $_REQUEST['as'] . '"');

    set_time_limit(0);

    try {
      passthru($cmd_v);
    } catch (Exception $err) {
      Logger::error('Download failed -> ', $err);
      Call::respondHero("Sorry. But we can't handle your download request.");
    }

    unlink($fileList);

    exit;

  }

}
