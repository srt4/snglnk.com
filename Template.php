<?php

class Template {
    private $templateDir;
    
    public function __construct($templateDir = 'templates') {
        $this->templateDir = $templateDir;
    }
    
    public function render($template, $data = []) {
        $templatePath = $this->templateDir . '/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template not found: $templatePath");
        }
        
        // Extract variables for use in template
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include template file
        include $templatePath;
        
        // Get the output and clean buffer
        $output = ob_get_clean();
        
        return $output;
    }
    
    public function display($template, $data = []) {
        echo $this->render($template, $data);
    }
}