<?php 

class Openprovider extends Module {
    public function __construct() {

        // Loading module config
        $this->loadConfig(dirname(__FILE__) . DS . "config.json");

        // Loading language
        Language::loadLang("openprovider", null, dirname(__FILE__) . DS . "language" . DS);
    }
}
