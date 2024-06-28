<?php

abstract class Handler {

  abstract public function __construct(Map $settings);

  abstract function apply(Map $requests): array;

}
