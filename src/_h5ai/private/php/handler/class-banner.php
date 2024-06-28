<?php

class Banner extends Handler {

  public function __construct(Map $settings) { }

  function apply(Map $requests): array {
    $href = $requests->getOrFail('banner');
    $path = Common::traversal_href_check($href);
    if (!$path) return [
      "header" => ["type" => null, "content" => null,],
      "footer" => ["type" => null, "content" => null,],
    ];
    $result = [];
    $header = $this->search_customizations($path, 'header');
    if (!$header) {
      $result['header'] = ["type" => null, "content" => null];
    } else {
      $result['header'] = ["type" => 'html', "content" => $header];
    }
    $footer = $this->search_customizations($path, 'footer');
    if (!$footer) {
      $result['footer'] = ["type" => null, "content" => null];
    } else {
      $result['footer'] = ["type" => 'html', "content" => $footer];
    }
    // Logger::debug('get_customizations -> ', $result);
    return $result;
  }

  private function search_customizations($path, $name): string|false {
    $file = $path . '/_h5ai.' . $name . '.html';
    if (file_exists($file)) {
      // Logger::debug('search_customizations -> ' . $file);
      return file_get_contents($file);
    }
    $temp = $path;
    while (str_starts_with($temp, G::ROOT_PATH)) {
      $file = $temp . '/_h5ai.' . $name . 's.html';
      // Logger::debug('search_customizations try -> ' . $file);
      if (file_exists($file)) {
        return file_get_contents($file);
      } else {
        $temp = dirname($temp);
      }
    }
    return false;
  }

}
