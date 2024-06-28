<?php

/**
 * because PHP is shit, So
 * 1. Can't limit search frequency ( No hi-level controller for xlimit)
 * 2. Can't limit regex search timeout ( something like timeout(x){ } in kotlin )
 */
class Search extends Handler {

  private bool $enableRegex;

  private int $searchLimit;
  private int $resultLimit;

  private bool $showTime;
  private bool $useNewest;

  private bool $showFileSize;
  private bool $showFolderSize;

  private bool $enableManaged;
  private array $managedIndexes;

  private bool $hideFolder;
  private bool $hideUnreadableFile;
  private bool $hideUnreadableFolder;

  private bool $enableExclusive;
  private array $exclusiveRegex;

  public function __construct(Map $settings) {

    $this->enableRegex = $settings->get('appearance.feature.searching.enableRegex', false);

    $config = $settings->sub('preference.searching', []);

    $this->searchLimit = $config->get('search-limit', 100);
    $this->resultLimit = $config->get('result-limit', 100);

    $this->showTime = $config->get('time.enable', false);
    $this->useNewest = $config->get('time.use-newest', false);

    $this->showFileSize = $config->get('size.enable-file', false);
    $this->showFolderSize = $config->get('size.enable-folder', false);

    $this->enableManaged = $config->get('managed.enable', false);
    $this->managedIndexes = $config->get('managed.indexes', []);

    $this->hideFolder = $config->get('hidden.folder', true);

    $this->hideUnreadableFile = $config->get('hidden.unreadable-file', true);
    $this->hideUnreadableFolder = $config->get('hidden.unreadable-folder', true);

    $this->enableExclusive = $config->get('exclusive.enable', true);

    if (!$this->enableExclusive) {
      $this->exclusiveRegex = [];
      return;
    }

    $this->exclusiveRegex = $config->get('exclusive.extra-regex', []);

    if ($config->get('exclusive.global', true)) {
      $this->exclusiveRegex = array_merge(
        $settings->get('preference.general.global-exclusive', []),
        $this->exclusiveRegex
      );
    }

  }

  function apply(Map $requests): array {

    // Logger::debug('REQUEST-SEARCH -> ', $requests->getValue());

    $href = $requests->getOrFail('search.href');
    $pattern = $requests->getOrFail('search.pattern');

    $realPath = Common::traversal_href_check($href);

    Logger::debug('SEARCH ' . $pattern . '-> ' . $realPath);

    $result = [];

    try {
      $this->searchRecursive($result, '/' . $pattern . '/i', $realPath);
    } catch (Exception $exception) {
      Logger::error('Search failed ' . $href . ' -> ' . $pattern, $exception);
      return [];
    }

    Logger::debug('SEARCH RESULT -> ', $result);

    return $result;

  }

  private function searchRecursive(array &$result, string $pattern, string $path): int {

    $prefix = substr($path, 9);

    $loaded = 0;

    foreach (scandir($path) as $name) {

      // if (is_link($name)) continue;
      if ($name === '.') continue;
      if ($name === '..') continue;
      if (str_starts_with('_h5ai', $name)) continue;

      if ($this->enableExclusive) {
        foreach ($this->exclusiveRegex as $regex) {
          if (preg_match($name, $regex)) continue 2;
        }
      }

      if ($loaded > $this->searchLimit) {
        return $loaded;
      }

      $loaded++;

      $file = $path . '/' . $name;

      if ($this->match($pattern, $name)) {

        // $href = substr($file, 9);
        $href = basename($file);

        if (is_dir($file)) {

          if ($this->hideFolder) continue;

          if (!is_executable($file) && $this->hideUnreadableFolder) continue;

          Logger::debug('searchRecursive - ADD D ' . $href . ' -> ' . $file);

          $result[] = [
            'href' => $prefix . '/' . $href,
            'time' => $this->showTime ? $this->getFileTime($file) : null,
            'size' => $this->showFolderSize ? Search::folderSize($file) : null,
            'managed' => !$this->enableManaged || Search::isManaged($file),
            'fetched' => false,
          ];

        } else {

          if (!is_readable($file) && $this->hideUnreadableFile) continue;

          Logger::debug('searchRecursive - ADD S ' . $href . ' -> ' . $file);

          $result[] = [
            'href' => $prefix . '/' . $href,
            'time' => $this->showTime ? $this->getFileTime($file) : null,
            'size' => $this->showFileSize ? @filesize($file) : null,
          ];

        }

      } else {

        if (is_dir($file)) {
          $subLoaded = $this->searchRecursive($result, $pattern, $file);
          $loaded = $loaded + $subLoaded;
        }

        continue;
      }

      if (sizeof($result) > $this->resultLimit) {
        return $loaded;
      }

    }

    return $loaded;
  }

  private function match($pattern, $name): bool {
    if ($this->enableRegex) {
      $result = preg_match($pattern, $name);
      Logger::debug("REG-MATCH " . boolval($result) . ' - ' . $pattern . ' -> ' . $name);
    } else {
      $result = str_contains($name, $pattern);
      Logger::debug("STR-MATCH " . $result . ' - ' . $pattern . ' -> ' . $name);
    }
    return $result;
  }

  private function getFileTime($realPath): int {
    if ($this->useNewest) {
      return max(@filectime($realPath) or 0, @filemtime($realPath) or 0);
    } else {
      return @filemtime($realPath);
    }
  }

  private function isManaged(string $path): bool {
    foreach ($this->managedIndexes as $name) {
      $str = $path . '/' . $name;
      Logger::debug('check managed -> ' . $str);
      $realpath = realpath($str);
      if ($realpath != null) return false;
    }
    return true;
  }

  private static function folderSize($path): int {
    $lines = Common::execute('du -sbL ' . escapeshellarg($path));
    $strings = preg_split('/\s+/', $lines[0], 2);
    return intval($strings[0]);
  }

}
