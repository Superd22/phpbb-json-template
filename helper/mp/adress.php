<?php namespace scfr\phpbbJsonTemplate\helper\mp;

class adress {
    public $name;
    public $id;
    public $type;
    public $color;
    public $image;
    
    private $string;
    
    public function __construct($adr_string) {
        $this->string = $adr_string;
        try{
            $this->parseAdress();
            $this->fetchTarget();
        }
        catch(\Exception $e) {

        }
    }
    
    /**
    * Parses a target string to get type and id data
    * @return void
    */
    private function parseAdress() {
        if(!$this->string) throw new \Exception("Null string given for Adress");
            
        // Keep instance string intact
        $string = $this->string;
        
        // Compute type of adress based on prefix
        if(strpos("u_", $string) >= 0) $this->type = "user";
        elseif(strpos("g_", $string) >= 0) $this->type = "group";
            
        // remove prefix
        $string = substr($string, 2);
        
        $this->id = (integer) $string;
        
        if(!($this->id > 0)) throw new \Exception("Computed ID ({$this->id}) for adress is invalid");
    }
    
    /**
    * Based on the id & target, fetches all the relevant data for this adress
    * @return void
    */
    private function fetchTarget() {
        if(!$this->id) throw new \Exception("ID ({$this->id}) for adress is invalid");
        
        if($this->type === "user") {
            $info = new \scfr\phpbbJsonTemplate\helper\userinfo($this->id);
            $this->name = $info->username;
            $this->color = $info->color;
            $this->image = $info->avatar;
        }
        
        elseif($this->type === "group") {
            $info = new \scfr\phpbbJsonTemplate\helper\groupinfo($this->id);
            $this->name = $info->name;
            $this->color = $info->color;
        }

        else throw new \Exception("unsuported type for FetchTarget in phpbbJsonTemplate\helper\mp\adress");
    }
}