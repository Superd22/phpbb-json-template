<?php namespace scfr\phpbbJsonTemplate\helper;

class userinfo {
    
    public $user_id;
    public $color;
    public $username;
    public $avatar;
    
    function __construct($user_id) {
        $this->user_id = (integer) $user_id;
        $this->hydrate_user();
    }
    
    protected function hydrate_user() {
        global $db;
        
        $sql = "SELECT username, user_colour, user_avatar, user_avatar_type FROM " . USERS_TABLE . " WHERE user_id='{$this->user_id}' ";
        $results = $db->sql_query($sql);
        
        $r = $db->sql_fetchrow($results);
        
        $this->color = $r["user_colour"];
        $this->username = $r["username"];
        
        $this->avatar = $r["user_avatar"];
        
        $this->doAvatar();
    }
    
    public function doAvatar($avatar = "") {
        if($this->avatar && strpos($this->avatar, "http") === 0) return;
        elseif ($this->avatar) {
            $this->avatar = "./download/file.php?avatar=".$this->avatar;
        }

        return $this->avatar;
    }

}