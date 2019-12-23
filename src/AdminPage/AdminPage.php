<?php

namespace Globalia\StatsCrawler\AdminPage;

use Globalia\StatsCrawler\Helpers\View;

abstract class AdminPage {

    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function render($template_name, array $params = null)
    {
        $view = new View($template_name, $params);
        echo $view->getContent();
    }
}
