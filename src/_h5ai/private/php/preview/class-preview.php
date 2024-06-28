<?php

// file-name name-hash -> link -> hash
// 计算文件名hash比SQLite快

// settings:
// mapping-mode: linkfile/database

class Preview extends Handler {

  public function __construct(Map $settings) { }

  /*

{
	"action": "get",
	"thumbs": [{
		"type": "file",
		"href": "/CHANGELOG.md"
	}, {
		"type": "file",
		"href": "/README.md"
	}, {
		"type": "file",
		"href": "/arc.deb"
	}, {
		"type": "vid-mov",
		"href": "/arc.mov"
	}, {
		"type": "file",
		"href": "/arl.apk"
	}, {
		"type": "file",
		"href": "/config.json"
	}, {
		"type": "file",
		"href": "/ghu.js"
	}, {
		"type": "file",
		"href": "/package.json"
	}, {
		"type": "file",
		"href": "/test.go"
	}]
}

{
	"action": "get",
	"thumbs": [{
		"type": "img-png",
		"href": "/Video/530a84fa0f12167b12d94f331a11554723f3479a95dcc3effd0051c3708f9c36.png"
	}, {
		"type": "img-png",
		"href": "/Video/70b31526a32b6e5c635747a0fe072837aae96ffcb39dfef381f562bfc044de94.png"
	}, {
		"type": "img-png",
		"href": "/Video/87c34d763550c33b286e88817e4fafc0fa4f566527bcc97e88367b0012345f4e.png"
	}]
}

  {
  "thumbs": {
      "thumbnails": [
          "\/_h5ai\/public\/cache\/thumbs\/thumb-a2a82324fb73cf8a33110657fd605b8a5905c096-320x240.jpg",
          "\/_h5ai\/public\/cache\/thumbs\/thumb-584f58aad3040f6feaf74f8e7e23a6d95f7c830c-320x240.jpg",
          "\/_h5ai\/public\/cache\/thumbs\/thumb-f8d262fd39d5e716d97bc3b2178ae0a521256ba2-320x240.jpg"
      ],
      "file-types": [
          null,
          null,
          null
      ]
  }
}

{
	"action": "get",
	"thumbs": [{
		"type": "img-png",
		"href": "/Video/530a84fa0f12167b12d94f331a11554723f3479a95dcc3effd0051c3708f9c36.png"
	}, {
		"type": "img-png",
		"href": "/Video/70b31526a32b6e5c635747a0fe072837aae96ffcb39dfef381f562bfc044de94.png"
	}, {
		"type": "img-png",
		"href": "/Video/87c34d763550c33b286e88817e4fafc0fa4f566527bcc97e88367b0012345f4e.png"
	}]
}

{
	"thumbs": {
		"thumbnails": [
			"\/_h5ai\/public\/cache\/thumbs\/thumb-a2a82324fb73cf8a33110657fd605b8a5905c096-320x240.jpg",
			"\/_h5ai\/public\/cache\/thumbs\/thumb-584f58aad3040f6feaf74f8e7e23a6d95f7c830c-320x240.jpg",
			"\/_h5ai\/public\/cache\/thumbs\/thumb-f8d262fd39d5e716d97bc3b2178ae0a521256ba2-320x240.jpg"
		],
		"file-types": [
			null,
			null,
			null
		]
	}
}

   */

  function apply(Map $requests): array {

    $thumbs = $requests->getOrFail('thumbs');

    $types = [];
    $hrefs = [];

    foreach ($thumbs as $thumb) {

      $exists = array_key_exists('href', $thumb);
      if (!$exists) Call::respondText('Invalid request');

      $exists = array_key_exists('type', $thumb);
      if (!$exists) Call::respondText('Invalid request');

      $href = $thumb['href'];
      $type = $thumb['type'];

      $filePath = Common::traversal_href_check($href);
      if (!$filePath) Call::respondText('Invalid request');

      $fileName = basename($filePath);
      $linkName = sha1($fileName);
      $linkFile = G::THUMB_LINK_PATH . $linkName;

      [$success, $fileType] = $this->getThumbnails($filePath, $fileName, $linkFile, $linkName);

      if ($success) {
        $types[] = $type == $fileType ? null : $fileType;
        $hrefs[] = G::THUMB_LINK_HREF . $linkName;
      } else {
        $types[] = 'file';
        $hrefs[] = null;
      }

    }

    return [
      'type' => $types,
      'href' => $hrefs,
    ];

  }

  private function getThumbnails(string $filePath, string $fileName, string $linkFile, string $linkName): array {

    if (file_exists($linkFile)) {

      $fileTime = max(@filectime($filePath), @filemtime($filePath));
      $linkTime = max(@filectime($linkFile), @filemtime($linkFile));

      if ($fileTime < $linkTime) {

        // 正常的缩略图

      } else {
        unlink($linkFile);
        // 删除link文件 - 已经过时了
        // return $this->generateThumbnails();
      }

    } else {

    }

  }

  private
  function generateThumbnails(): false|string {

  }

  private
  function generateImageThumbnails(): false|string {

  }

  private
  function generateVideoThumbnails(): false|string {

  }
}

