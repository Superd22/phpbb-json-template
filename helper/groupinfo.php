<?php namespace scfr\phpbbJsonTemplate\helper;

class groupinfo {
    
    public $id;
    public $color;
    public $name;

    function __construct($id) {
        $this->id = (integer) $id;
        $this->hydrate_user();
    }

    private function hydrate_user() {
        global $db;

        $sql = "SELECT group_name, group_colour FROM " . GROUPS_TABLE . " WHERE user_id='{$this->id}' ";
        $results = $db->sql_query($sql);

        $r = $db->sql_fetchrow($results);

        $this->color = $r["group_colour"];
        $this->name = $r["group_name"];
    }
}