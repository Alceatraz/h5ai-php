<?php

class Api {

  public static function of(Map $settings): Api {
    return new Api($settings);
  }

  private Map $settings;

  public function __construct(Map $settings) {
    $this->settings = $settings;
  }

  function apply(): array {

    $CONTENT_TYPE = explode(";", $_SERVER['CONTENT_TYPE'])[0];

    switch ($CONTENT_TYPE) {

      case 'application/json':

        $response = [];

        $requestJson = Map::fromJsonFile('php://input');

        if ($requestJson->getBool('options', false)) {
          $response['options'] = $this->settings->get('appearance', []);
        }

        if ($requestJson->getBool('langs', false)) {
          $instance = new Langs($this->settings);
          $response['langs'] = $instance->apply($requestJson);
        }

        if ($requestJson->getBool('types', false)) {
          $instance = new Types($this->settings);
          $response['types'] = $instance->apply($requestJson);
        }

        if ($requestJson->getBool('theme', false)) {
          $instance = new Theme($this->settings);
          $response['theme'] = $instance->apply($requestJson);
        }

        if ($requestJson->contains('items')) {
          $instance = new Items($this->settings);
          $response['items'] = $instance->apply($requestJson);
        }

        if ($requestJson->contains('banner') &&
          $this->settings->getBool('appearance.browsing.banner.enable', false)) {

          $instance = new Banner($this->settings);
          $response['banner'] = $instance->apply($requestJson);
        }

        if ($requestJson->contains('search') &&
          $this->settings->getBool('appearance.feature.searching.enable', false)) {

          $instance = new Search($this->settings);
          $response['search'] = $instance->apply($requestJson);
        }

        if ($requestJson->contains('thumbs') &&
          $this->settings->getBool('appearance.feature.thumbnails.enable', false)) {

          // $instance = new Preview($this->settings);
          // $response['thumbs'] = $instance->apply($requests);

          $instance = new Thumbnails($this->settings);
          $response['thumbs'] = $instance->apply($requestJson);

        }

        Call::respondJson($response);

        break;

      case 'application/x-www-form-urlencoded':

        $requestForm = Map::of($_REQUEST);

        $instance = new Download($this->settings);
        $instance->apply($requestForm);

        break;

      default:
        exit('Not support request type -> ' . $CONTENT_TYPE);

    }

    exit;

  }

}
