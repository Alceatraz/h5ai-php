<?php

class Items extends Handler {

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

    $config = $settings->sub('preference.browsing', []);

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

    //    Logger::debug('$show_modify_time ' . $show_modify_time);
    //    Logger::debug('$calculate_file_size ' . $calculate_file_size);
    //    Logger::debug('$calculate_folder_size ' . $calculate_folder_size);
    //    Logger::debug('$show_folder ' . $show_folder);
    //    Logger::debug('$hide_unreadable_file ' . $hide_unreadable_file);
    //    Logger::debug('$hide_executable_folder ' . $hide_executable_folder);

  }

  function apply(Map $requests): array {

    $request_uri = $requests->getOrFail('items.href');
    $realPath = Common::traversal_href_check($request_uri);
    if (!$realPath) return [];
    if (!is_dir($realPath)) return [];
    $realHref = substr($realPath, 9);
    //    Logger::debug('Request HREF -> ' . $request_uri);
    //    Logger::debug('Request PATH -> ' . $realPath);
    //    Logger::debug('Request HREF -> ' . $realHref);

    $result = [];
    $file_cache = [];

    $result[] = [
      'href' => $request_uri,
      'time' => $this->showTime ? $this->getFileTime($realPath) : null,
      'size' => $this->showFolderSize ? Items::folderSize($realPath) : null,
      'managed' => true,
      'fetched' => true,
    ];

    foreach (scandir($realPath) as $name) {

      if ($name === '.') continue;
      if ($name === '..') continue;

      if (str_starts_with($name, '_h5ai')) continue;

      if ($this->enableExclusive) {
        foreach ($this->exclusiveRegex as $regex) {
          if (preg_match($name, $regex)) continue 2;
        }
      }

      $href = $realHref . '/' . $name;
      $file = G::ROOT_PATH . $href;

      // if (is_link($file)) continue;

      //      Logger::debug('HREF = ' . $href);
      //      Logger::debug('FILE = ' . $file);

      if (is_dir($file)) {

        if ($this->hideFolder) continue;

        if (!is_executable($file) && $this->hideUnreadableFolder) continue;
        $temp_href = $href . '/';
        //        Logger::debug('D -> ' . $file . '=' . $temp_href);
        $result[] = [
          'href' => $temp_href,
          'time' => $this->showTime ? $this->getFileTime($realPath) : null,
          'size' => $this->showFolderSize ? Items::folderSize($file) : null,
          'managed' => !$this->enableManaged || Items::isManaged($file),
          'fetched' => false,
        ];
      } else {
        if (!is_readable($file) && $this->hideUnreadableFile) continue;
        $temp_href = $href;
        //        Logger::debug('F -> ' . $file . '=' . $temp_href);
        $file_cache[] = [
          'href' => $temp_href,
          'time' => $this->showTime ? @filemtime($file) : null,
          'size' => $this->showFileSize ? @filesize($file) : null,
        ];
      }
    }
    foreach ($file_cache as $item) $result[] = $item;
    //    Logger::debug('Respond', $result);
    return $result;
  }

  private function getFileTime($realPath): ?int {
    if ($this->useNewest) {
      $ctime = @filectime($realPath);
      $mtime = @filemtime($realPath);
      $time = max($ctime, $mtime);
    } else {
      $time = @filemtime($realPath);
    }
    return $time == 0 ? null : $time;
  }

  private function isManaged(string $path): bool {
    foreach ($this->managedIndexes as $name) {
      $str = $path . '/' . $name;
      // Logger::debug('check managed -> ' . $str);
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
