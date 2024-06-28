<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Index - h5ai</title>
  <script src="/_h5ai/public/js/scripts.js" data-module="index"></script>
  <link rel="stylesheet" href="/_h5ai/public/css/styles.css">
  <link rel="shortcut icon" href="/_h5ai/public/images/favicon/favicon-16-32.ico">
  <link rel="apple-touch-icon-precomposed" type="image/png" href="/_h5ai/public/images/favicon/favicon-152.png">
  <?php
  $json = Common::json_load_or_fail(G::CONF_CUSTOMIZE);
  $customize = new Map($json);
  foreach ($customize->get('heads.style.local', []) as $href) {
    echo '<link href="/_h5ai/public/ext/' . $href . '" class="x-head" rel="stylesheet">';
  }
  foreach ($customize->get('heads.style.global', []) as $href) {
    echo '<link href="' . $href . '" class="x-head" rel="stylesheet">';
  }
  foreach ($customize->get('heads.script.local', []) as $href) {
    echo '<script src="/_h5ai/public/ext/' . $href . '" class="x-head"></script>';
  }
  foreach ($customize->get('heads.script.global', []) as $href) {
    echo '<script src="' . $href . '" class="x-head"></script>';
  }
  ?>
  <style class="x-head">
    <?php
    $fonts = $customize->get('fonts.regular', []);
    if (sizeof($fonts) > 0) {
      echo '#root,input,select{font-family:"' . implode('","', $fonts) . '"!important}';
    }
    $fonts_mono = $customize->get('fonts.monospace', []);
    if (sizeof($fonts_mono) > 0) {
      echo 'pre,code{font-family:"' . implode('","', $fonts_mono) . '"!important}';
    }
    ?>
  </style>
</head>
<body class="index" id="root"></body>
</html>
