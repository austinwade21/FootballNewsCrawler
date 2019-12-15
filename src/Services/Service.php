<?php

namespace Globalia\StatsCrawler\Services;

abstract class Service {

    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }
}
