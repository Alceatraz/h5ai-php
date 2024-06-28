<?php

class Image {
  private $source_file;
  private $source;
  private $width;
  private $height;
  private $dest;

  public function __construct($filename = null) {

    $this->source = null;
    $this->width = null;
    $this->height = null;
    $this->dest = null;
    $this->source_file = $filename;
  }

  public function __destruct() {
    $this->release_source();
    $this->release_dest();
  }

  public function set_source_data($fp) {
    $this->release_dest();

    rewind($fp);
    try {
      $this->source = @imagecreatefromstring(stream_get_contents($fp));
    } catch (Exception $e) {
      $this->source = null;
      return false;
    }
    if (!$this->source) {
      $this->source = null;
      return false;
    }
    $this->width = imagesx($this->source);
    $this->height = imagesy($this->source);

    if (!$this->width || !$this->height) {
      $this->release_source();
      $this->source_file = null;
      $this->width = null;
      $this->height = null;
      return false;
    }
    return true;
  }

  public function save_dest_jpeg($filename, $quality = 80) {
    if (!is_null($this->dest)) {
      @imagejpeg($this->dest, $filename, $quality);
      @chmod($filename, 0775);
    }
  }

  public function release_dest() {
    if (!is_null($this->dest)) {
      @imagedestroy($this->dest);
      $this->dest = null;
    }
  }

  public function release_source() {
    if (!is_null($this->source)) {
      @imagedestroy($this->source);
      $this->source_file = null;
      $this->source = null;
      $this->width = null;
      $this->height = null;
    }
  }

  public function thumb($width, $height) {
    if (is_null($this->source)) {
      return;
    }

    $src_r = 1.0 * $this->width / $this->height;

    if ($height == 0) {
      if ($src_r >= 1) {
        $height = 1.0 * $width / $src_r;
      } else {
        $height = $width;
        $width = 1.0 * $height * $src_r;
      }
      if ($width > $this->width) {
        $width = $this->width;
        $height = $this->height;
      }
    }

    $ratio = 1.0 * $width / $height;

    if ($src_r <= $ratio) {
      $src_w = $this->width;
      $src_h = $src_w / $ratio;
      $src_x = 0;
    } else {
      $src_h = $this->height;
      $src_w = $src_h * $ratio;
      $src_x = 0.5 * ($this->width - $src_w);
    }

    $width = intval($width);
    $height = intval($height);
    $src_x = intval($src_x);
    $src_w = intval($src_w);
    $src_h = intval($src_h);

    $this->dest = imagecreatetruecolor($width, $height);
    $icol = imagecolorallocate($this->dest, 255, 255, 255);
    imagefill($this->dest, 0, 0, $icol);
    imagecopyresampled($this->dest, $this->source, 0, 0, $src_x, 0, $width, $height, $src_w, $src_h);
  }

  public function rotate($angle) {
    if (is_null($this->source) || ($angle !== 90 && $angle !== 180 && $angle !== 270)) {
      return;
    }

    $this->source = imagerotate($this->source, $angle, 0);
    if ($angle === 90 || $angle === 270) {
      list($this->width, $this->height) = [$this->height, $this->width];
    }
  }

  public function normalize_exif_orientation($exif_source_file = null) {
    if (is_null($this->source) || !function_exists('exif_read_data')) {
      return;
    }

    if ($exif_source_file === null) {
      $exif_source_file = $this->source_file;
    }

    $exif = exif_read_data($exif_source_file);
    switch (@$exif['Orientation']) {
      case 3:
        $this->rotate(180);
        break;
      case 6:
        $this->rotate(270);
        break;
      case 8:
        $this->rotate(90);
        break;
    }
  }
}
