<?php

class Theme extends Handler {

  private string $theme;
  private string $theme_path;

  public function __construct(Map $settings) {
    $this->theme = $settings->get('preference.browsing.theme', 'comity');
    $this->theme_path = '/app/h5ai/_h5ai/public/images/themes/' . $this->theme;
  }

  function apply(Map $requests): array {
    $icons = [];
    if ($open = opendir($this->theme_path)) {
      while (($file = readdir($open)) !== false) {
        $info = pathinfo($file);
        if (!in_array(@$info['extension'], ['svg', 'png', 'jpg'])) continue;
        $icons[$info['filename']] = $this->theme . '/' . $file;
      }
      closedir($open);
    }
    return $icons;
  }
}
