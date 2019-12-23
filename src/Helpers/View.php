<?php

namespace Globalia\StatsCrawler\Helpers;

class View {
    
    protected $content;
    protected $params;
    public $templates_dir;
  
    public function __construct($template, array $params = [])
    {
        $this->templates_dir = dirname(dirname(__DIR__)) . '/templates/';
        $this->params = $params;
        $this->content = $this->loadTemplate($template);   
    }
    
    public function loadTemplate($template)
    {
        $template_path = $this->templates_dir . $template . '.php';
        
        try {
            if (file_exists($template_path)) {
                ob_start();
                foreach ($this->params as $variable => $value) {
                    $$variable = $value;
                }    
                
                require_once($template_path);
                return ob_get_clean();
            } else {
                throw new \Exception("Template '$template.php' does not exist in $this->templates_dir");
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
        }        
    }
    
    public function getContent()
    {
        return $this->content;
    }
}
