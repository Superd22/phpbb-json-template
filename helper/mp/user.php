<?php namespace scfr\phpbbJsonTemplate\helper\mp;

class user {
    protected $db;
    protected $user_id;

    public function __construct($user_id) {
        global $db;
        $this->user_id = (integer) $user_id;
        $this->db = $db;
    }

    public function get_user_latest_convos($start=0, $limit = 20) {
        return convo::get_latest_convos($this->user_id, $start, $limit);
    }
}

?>