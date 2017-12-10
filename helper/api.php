<?php namespace scfr\phpbbJsonTemplate\helper;

use phpbb\json_response;

class api extends json_response {
    
    public function __construct(\phpbb\template\template $template, \phpbb\user $user) {
        $this->template = $template;
        $this->user = $user;
    }
    
    private function access_protected($obj, $prop) {
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }
    
    private function get_template_vars() {
        $tpldata = $this->access_protected($this->access_protected($this->template, "context"), "tpldata");
        
        foreach($tpldata as $pp => $val) {
            if($pp == "." && isset($val[0])) foreach($val[0] as $p => $v) $return[$p] = $v;
            else $return[$pp] = $val;
            }
        
        return $return;
    }
    
    public static function doHaeaders() {
        error_reporting(0);
        global $request;
        $request->enable_super_globals();
        
        $http_origin = $_SERVER['HTTP_REFERER'];
        header('X-Origami: '.$http_origin);

        if(preg_match('/^(https:\/\/([^.]*\.)?starcitizen\.fr)/', $http_origin, $matchs) !== false) {
            header('Access-Control-Allow-Origin: '.$matchs[1]);
        }
        else header('Access-Control-Allow-Origin:  https://starcitizen.fr');
             
        header('Access-Control-Allow-Credentials: true');
    }

    public function get_template_filename() {
        return \str_replace(".html", "",$this->access_protected($this->template, "filenames")["body"]);
    }
    
    public function render_json($status_code = 200) {
        $this->doHaeaders();
        $json = array('@template' => array_merge(["TWITCH_BANNER" => ""], $this->get_template_vars()), '@tplName' => $this->get_template_filename());
        $this->send($json);
    }
    
    public function render_message($message) {
        $this->doHaeaders();
        $this->send($message);
    }
    
}
?>
