<?php

class Thumbnails extends Handler {

  private int $width;
  private int $height;

  private array $extensions;

//  private bool $enableExif;
//  private bool $videoSeek;

  private CacheDB $db;

  public function __construct(Map $settings) {

    $this->width = $settings->get('preference.thumbnails.image-width', 320);

    if ($this->width % 4 == 0) {
      $this->height = $this->width / 4 * 3; // default 320x240
    } else {
      $this->width = 320;
      $this->height = 240;
    }

    $this->extensions = $settings->get('appearance.feature.thumbnails.extension', []);

//    $this->videoSeek = $settings->get('client.thumbnails.seek', 50);
//    $this->enableExif = $settings->get('client.thumbnails.exif', false);

    $this->db = new CacheDB();

  }

  function apply(Map $requests): array {

    $reqs = $requests->getOrFail('thumbs', 'Request invalid');

    Logger::debug('thumbs-apply ', $reqs);

    $hrefs = [];
    $thumbs = [];
    $filetypes = [];

    foreach ($reqs as $it) {

      // Security check, Reject all invalid type
      if (!array_key_exists($it['type'], $this->extensions)) {
        $hrefs[] = null;
        $filetypes[] = null;
        continue;
      }

      $href = $it['href'];

      // $path = $this->to_path($href);

      $path = G::ROOT_PATH . '/' . rawurldecode(substr($href, 1));

      Logger::debug('thumbs process file ' . $href . ' -> ' . $path);

      // computeIfAbsent
      // type==file 表示无效媒体

      $exists = array_key_exists($path, $thumbs);

      if (!$exists) { // computeIfAbsent

        $thumb = new Thumb($path, $it['type'], $this->db);
        $thumbs[$path] = $thumb;

        Logger::debug('not-exist ' . $path);

      } else if ($thumbs[$path]->type->name === 'file') {  // type==file 表示无效媒体

        Logger::debug('type = file ' . $path);

        $hrefs[] = null;
        $filetypes[] = 'file';

        continue;

      }

      // 从缓存拿出来
      $var = $thumbs[$path];

      $cache_href = $var->thumb($this->width, $this->height);

      $hrefs[] = $cache_href;

      if ($var->type->was_wrong()) {
        $filetypes[] = $thumbs[$path]->type->name;
      } else {
        $filetypes[] = null; // No client-side update needed.
      }

    }

    return [
      'href' => $hrefs,
      'type' => $filetypes,
    ];

  }
}
