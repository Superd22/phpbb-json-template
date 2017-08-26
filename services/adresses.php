<?php namespace scfr\phpbbJsonTemplate\services;

class adresses {
    private $cache;
    
    private static $instance;
    
    public function get() {
        if(!self::$instance) self::$instance = new adresses();
        return self::$instance;
    }
    
    private function __construct() {
        
    }
    
    public function getAdressFor($string) {
        if(isset($cache[$string]) && !empty($cache[$string])) return $cache[$string];
        else {
            $cache[$string] = new \scfr\phpbbJsonTemplate\helper\mp\adress($string);
        }
        
        return $cache[$string];
    }
    
    public function getAdressesFor($string) {
        
        // truish is okay coz can't be at pos0
        if(strpos($string,":") > 0)
        $adresses = explode(":", $string);
        else $adresses = [$string];

            
        foreach($adresses as $adr) {
            $ret[] = $this->getAdressFor($adr);
        }
        return $ret;


    }
    
    
}