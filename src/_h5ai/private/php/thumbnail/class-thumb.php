<?php

class Thumb {

  const array FFMPEG_CMDV = ['ffmpeg', '-v', 'warning', '-nostdin', '-y', '-hide_banner', '-ss', '[H5AI_DUR]', '-i', '[H5AI_SRC]', '-an', '-vframes', '1', '-f', 'image2', '-'];
  const array FFPROBE_CMDV = ['ffprobe', '-v', 'warning', '-show_entries', 'format=duration', '-of', 'default=noprint_wrappers=1:nokey=1', '[H5AI_SRC]'];

  const array GM_CONVERT_CMDV = ['gm', 'convert', '-density', '200', '-quality', '100', '-strip', '[H5AI_SRC][0]', 'JPG:-'];

  const array IMG_EXT = ['jpg', 'jpe', 'jpeg', 'jp2', 'jpx', 'tiff', 'webp', 'ico', 'png', 'bmp', 'gif'];

  const string THUMB_CACHE = 'thumbs';

  // 'file' has to be the last key tested during fallback logic.

  public const HANDLED_TYPES = array(
    'img' => ['img', 'img-bmp', 'img-jpg', 'img-gif', 'img-png', 'img-raw', 'img-tiff', 'img-svg'],
    'mov' => ['vid-mp4', 'vid-webm', 'vid-rm', 'vid-mpg', 'vid-avi', 'vid-mkv', 'vid-mov'],
    'doc' => ['x-ps', 'x-pdf'],
    'swf' => ['vid-swf', 'vid-flv'],
    'ar-zip' => ['ar', 'ar-zip', 'ar-cbr'],
    'ar-rar' => ['ar-rar'],
    'file' => ['file']
  );

  private CacheDB $db;
  public FileType $type;

  private string $thumbs_path;
  private string $thumbs_href;

  private ?Image $image = null;
  private int $attempt = 0;

  public string $source_path;
  public int $mtime;

  public string $source_hash;

  public ?string $thumb_path;
  public ?string $thumb_href;

  public function __construct($source_path, string $type, CacheDB $db) {

    $this->db = $db;
    $this->thumbs_path = G::CACHE_PUB_PATH . self::THUMB_CACHE;
    $this->thumbs_href = G::CACHE_PUB_HREF . self::THUMB_CACHE;
    $this->source_path = $source_path;
    $this->mtime = filemtime($this->source_path);
    $this->type = new FileType($type);
    $this->source_hash = sha1($source_path);
    $this->thumb_path = null;
    $this->thumb_href = null;

    if (!is_dir($this->thumbs_path)) {
      @mkdir($this->thumbs_path, 0755, true);
    }

  }

  public function __destruct() {
    if ($this->image !== null) {
      unset($this->image);
    }
  }

  public function thumb($width, $height): ?string {

    if (!file_exists($this->source_path) || str_starts_with($this->source_path, G::CACHE_PUB_PATH)) {
      return null;
    }

    $name = 'thumb-' . $this->source_hash . '-' . $width . 'x' . $height . '.jpg';
    $this->thumb_path = $this->thumbs_path . '/' . $name;
    $this->thumb_href = $this->thumbs_href . '/' . $name;

    if (file_exists($this->thumb_path)
      && $this->mtime <= filemtime($this->thumb_path)) {
      $row = $this->db->select($this->source_hash);
      if ($row) {
        // Notify the client that their type detection was wrong.
        $this->type->name = $row['type'];
      }
      return $this->thumb_href;
    }

    $row = $this->db->select($this->source_hash);
    if ($row && !$this->db->obsolete_entry($row, $this->mtime)) {
      // We have a cached handled failure, skip it.
      return null;
    }

    if ($this->image !== null) {
      // Reuse capture data still in memory.
      return $this->thumb_href($width, $height);
    }

    $handlers = self::get_handlers_array(
      self::type_to_handler($this->type->name));
    $thumb_href = null;

    /* Hopefully, the first type is the right one, but in the off chance
       that it is not, we'll shift to test the subsequent ones. */
    foreach ($handlers as $handler) {
      if (!$this->capture($handler)) {
        if ($this->type->name === 'file') {
          break;  // We have tried as a file but failed, give up.
        }
        continue;
      }
      $thumb_href = $this->thumb_href($width, $height);
      if (!is_null($thumb_href)) {
        if ($this->type->was_wrong()) {
          // No error, but type was wrong so cache it.
          $this->db->insert($this->source_hash, $this->type->name);
        }
        return $thumb_href;
      } else if (!$this->type->was_wrong()) {
        // Correct file type, but no thumb nor error.
        break;
      }
    }
    return $thumb_href;
  }

  private function thumb_href($width, $height) {
    if (!isset($this->image)) {
      return null;
    }
    $this->image->thumb($width, $height);
    $this->image->save_dest_jpeg($this->thumb_path, 80);

    if (file_exists($this->thumb_path)) {
      // Cache it for further requests
      return $this->thumb_href;
    }
    unset($this->image);
    $this->image = null;
    return null;
  }

  private function capture($handler) {
    if ($this->attempt >= count(array_keys(self::HANDLED_TYPES))) {
      return false;
    }
    ++$this->attempt;

    if ($handler === 'file') {

      // if ($this->setup->get('HAS_PHP_FILEINFO')) {

      // Map to types available from types.json.

      $finfo_open = finfo_open(FILEINFO_MIME_TYPE);
      $mime_type = finfo_file($finfo_open, $this->source_path);
      finfo_close($finfo_open);

      $type = $this->type->mime_to_type($mime_type);
      $handler = self::type_to_handler($type);

      // Util::log("Fileinfo: $this->source_path $this->source_hash detected as $type, handler: $handler");
      $this->type->name = $type;
      // $this->db->insert($this->source_hash, $type, null);

      if ($handler === 'file') {
        return false;  // Giving up
      }

      return $this->capture($handler);

//      } else {
//
//        $this->type->name = 'file';
//        return false;
//      }

    } else if ($handler === 'img') {

      //if ($this->setup->get('HAS_PHP_EXIF')) {

      $exiftype = exif_imagetype($this->source_path);

      if (!$exiftype) {
        return $this->capture('file');
      } //       IMAGETYPE_SWF      IMAGETYPE_SWC

      else if ($exiftype === 4 || $exiftype === 13) {
        $this->type->name = 'vid-swf';
        return $this->capture('swf');
      }

      //  }

      $success = $this->do_capture_img($this->source_path);
      return $success ? $success : $this->capture('file');

    } else if ($handler === 'mov') {

//      if ($this->setup->get('HAS_CMD_FFMPEG')) {
      $probe_cmd = self::FFPROBE_CMDV;
      $conv_cmd = self::FFMPEG_CMDV;
//      } else if ($this->setup->get('HAS_CMD_AVCONV')) {
//        $probe_cmd = self::AVPROBE_CMDV;
//        $conv_cmd = self::AVCONV_CMDV;
//      } else {
//        return false;
//      }

      try {

        $timestamp = $this->compute_duration($probe_cmd, $this->source_path);
        return $this->do_capture($conv_cmd, $timestamp);

      } catch (Exception $e) {
        return $this->capture('file');
      }

    } else if ($handler === 'swf') {

      //if ($this->setup->get('HAS_CMD_FFMPEG')) {

      $conv_cmd = self::FFMPEG_CMDV;
      $probe_cmd = self::FFPROBE_CMDV;

//      } else if ($this->setup->get('HAS_CMD_AVCONV')) {
//        $probe_cmd = self::AVPROBE_CMDV;
//        $conv_cmd = self::AVCONV_CMDV;
//      } else {
//        return false;
//      }

      try {

        $timestamp = $this->compute_duration($probe_cmd, $this->source_path);

        // Swap SRC and DUR
        $conv_cmd[6] = '-i';
        $conv_cmd[7] = '[H5AI_SRC]';
        $conv_cmd[8] = '-ss';
        $conv_cmd[9] = '[H5AI_DUR]';

        return $this->do_capture($conv_cmd, $timestamp);

      } catch (Exception $e) {
        return $this->capture('file');
      }

    } else if ($handler === 'doc') {

      try {
//        if ($this->setup->get('HAS_CMD_GM')) {
        return $this->do_capture(self::GM_CONVERT_CMDV);
//        } else if ($this->setup->get('HAS_CMD_CONVERT')) {
//          return $this->do_capture(self::CONVERT_CMDV);
//        } else {
//          return false;
//        }

      } catch (Exception $e) {
        return $this->capture('file');
      }

    } else if (str_contains($handler, 'ar')) {

      try {
        return $this->do_capture_archive($this->source_path, $handler);
      } catch (UnhandledArchive $e) {
        Logger::error("Unhandled $this->source_path: " . $e->getMessage());
        // Cache this failure result to avoid scanning the file again in the future.
        $this->db->insert($this->source_hash, $this->type->name, $e->getCode());
        // Stop trying to guess the type
        return true;
      } catch (WrongType $e) {
        // Util::log("WrongType for $this->source_path: ". $e->getMessage());
        return $this->capture('file');
      } catch (Exception $e) {
        // Probably shouldn't cache these errors, they might be temporary only.
        Logger::error("Unhandled exception while reading archive $this->source_path of type $handler: " . $e->getMessage());
      }
    }
    return false;
  }

  public function do_capture_archive($path, $type) {
    $extracted = $this->extract_from_archive($type);
    if (!$extracted) {
      throw new UnhandledArchive("No file found in archive.", 1);
    }
    $success = $this->do_capture_img($extracted);
    if (!$success) {
      throw new UnhandledArchive(
        "Failed processing selected thumbnail candidate from archive.", 2);
    }
    return $success;
  }

  public function extract_from_archive($type) {

    /* Write one file from the archive into memory. */

    if (($type === "ar-zip")
      //  && ($this->setup->get('HAS_PHP_ZIP'))
    ) {
      $za = new ZipArchive();
      $err = $za->open($this->source_path, ZipArchive::RDONLY);
      $extracted = false;
      if ($err === true) { // No Error
        for ($i = 0; $i < $za->numFiles; $i++) {
          $entry = $za->getNameIndex($i);
          if (str_ends_with($entry, '/')) {
            // is directory
            continue;
          }
          // Deduce type from file extension
          $stat = $za->statIndex($i);
          $label = $stat['name'];
          $tmp = explode(".", $label);
          $ext = end($tmp);
          if (!empty($ext)
            && array_search($ext, self::IMG_EXT) !== false) {
            $extracted = fopen("php://temp/maxmemory:" . 2 * 1024 * 1024, 'r+');
            fwrite($extracted, $za->getFromIndex($i));
            break;
          }
        }
        $za->close();
        return $extracted;
      } else if ($err === ZipArchive::ER_NOZIP) {
        throw new WrongType("Not a zip file", $err);
      } else {
        // Despite these errors, we can probably try again later.
        throw new Exception("Unhandled Zip error code: $err", 5);
      }
    }
    if (($type === "ar-rar")
      // && ($this->setup->get('HAS_PHP_RAR'))
    ) {
      $rar = RarArchive::open($this->source_path);
      if (!$rar) {
        /* Give up entirely, we won't detect file type.
        This module does not detect if the type is incorrect. */
        throw new UnhandledArchive("Error opening rar archive", 4);
      }
      $extracted = false;
      $entries = $rar->getEntries();
      // TODO instead of sorting full entry paths, perhaps sort labels only?
      sort($entries, SORT_NATURAL);
      foreach ($entries as $entry) {
        if ($entry->isDirectory()) continue;
        $label = $entry->getName();
        $tmp = explode(".", $label);
        $ext = end($tmp);
        if (!empty($ext) && array_search($ext, self::IMG_EXT) !== false) {
          $stream = $entry->getStream();
          if ($stream !== false) {
            $extracted = fopen(
              "php://temp/maxmemory:" . 2 * 1024 * 1024, 'r+');
            fwrite($extracted, stream_get_contents($stream));
            fclose($stream);
            break;
          }
        }
      }
      $rar->close();
      return $extracted;
    }
    throw new UnhandledArchive("No handler for archive of type $type.", 2);
  }

  public function do_capture_img($source): bool {

    $image = new Image($source);

    $capture_data = fopen("php://temp/maxmemory:" . 2 * 1024 * 1024, 'r+');

    $et = @exif_thumbnail($source);

    if ($et !== false) {
      rewind($capture_data);
      fwrite($capture_data, $et);

      $is_valid = $image->set_source_data($capture_data);
      $image->normalize_exif_orientation($source);
    } else if (is_resource($source)) {
      // we assume this is a valid image resource
      $is_valid = $image->set_source_data($source);
      fclose($source);
    } else {
      // source is a path string
      $input_file = fopen($source, 'r');
      stream_copy_to_stream($input_file, $capture_data);
      fclose($input_file);
      $is_valid = $image->set_source_data($capture_data);
    }
    fclose($capture_data);

    if (!$is_valid) {
      unset($image);
      return false;
    }
    if ($this->image === null) {
      $this->image = $image;
    }
    return true;
  }

  public function do_capture($cmdv, $timestamp = null): bool {

    if (is_null($timestamp)) {

      foreach ($cmdv as &$arg) {
        $arg = str_replace('[H5AI_SRC]', $this->source_path, $arg);
      }

    } else {

      foreach ($cmdv as &$arg) {
        $arg = str_replace(
          ['[H5AI_SRC]', '[H5AI_DUR]'],
          [$this->source_path, $timestamp],
          $arg
        );
      }

    }

    $image = new Image($this->source_path);

    // Allocate 2MiB, write it to /tmp if bigger
    $capture_data = fopen("php://temp/maxmemory:" . 2 * 1024 * 1024, 'r+');

    $error = null;
    // $exit = Context::proc_open_cmdv($cmdv, $capture_data, $error);
    $exit = Common::proc_open($cmdv, $capture_data, $error);

    rewind($capture_data);
    $magic = fread($capture_data, 3);

    // Instead of parsing the child process' stderror stream for actual errors,
    // making sure the stdout stream starts with the JPEG magic number is enough
    $is_image = !empty($magic) && bin2hex($magic) === 'ffd8ff';

    if (!$is_image) {
      fclose($capture_data);
      throw new Exception($error);
    }

    $success = $image->set_source_data($capture_data);

    fclose($capture_data);

    if (!$success) {
      return false;
    }

    if ($this->image === null) {
      $this->image = $image;
    }

    return true;

  }

  public static function get_handlers_array($handler): array {
    /* Return an array of types of handlers, with $handler as its first element. */
    $available = array_keys(self::HANDLED_TYPES);

    // $key = array_search($handler, $available);
    // if ($key !== false) {
    //     unset($available[$key]);
    //     array_unshift($available, $handler);
    // }

    $handlers[] = $handler;
    foreach ($available as $item) {
      if ($item === $handler)
        continue;
      $handlers[] = $item;
    }
    return $handlers;
  }

  public static function type_to_handler($type): int|string {
    foreach (array_keys(self::HANDLED_TYPES) as $key) {
      if (in_array($type, self::HANDLED_TYPES[$key])) {
        return $key;
      }
    }
    return 'file';
  }

  // ===========================================================================

  private function compute_duration($cmd_v, $source_path): false|int {
    foreach ($cmd_v as &$arg) {
      $arg = str_replace('[H5AI_SRC]', $source_path, $arg);
    }
    Common::proc_open($cmd_v, $stdout, $stderr);
    if (!is_numeric($stdout)) {
      Logger::error('Get video duration failed -> ' . $source_path . ' ERR:\\n' . $stderr);
      return false;
    }
    $seek = 50;
    return round(floatval($stdout) * $seek / 100, 1);
  }

}

