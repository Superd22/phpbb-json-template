<?php namespace scfr\phpbbJsonTemplate\helper\mp;
echo "cacaaaa";
class convo {
    private $root_level;
    private $user_id;
    public $messages;
    public $author;
    public $start;
    public $last;
    
    public function __construct($user_id, $root_msg_id) {
        $this->root_level = (integer) $root_msg_id;
        $this->user_id = (integer) $user_id;
    }
    
    public function get_convo_content($start = 0, $limit = 20) {
        global $db;
        $messages = [];
        die();
        $from = " FROM " . PRIVMSGS_TABLE . " as msg, " . PRIVMSGS_TO_TABLE . " as too";
        
        // Get all the msgs in this convo
        $sql_first_pass = "SELECT msg_id FROM " . PRIVMSGS_TABLE . " WHERE msg_id = {$this->root_level} OR root_level = {$this->root_level}";
        
        // Filter by only what the user can see
        $sql_second_pass = "SELECT msg_id FROM " . PRIVMSGS_TO_TABLE . " WHERE msg_id IN ({$sql_first_pass})
        AND (author_id = {$this->user_id} OR  user_id = {$this->user_id}) GROUP BY msg_id";
        
        $sql_third_pass = "SELECT * {$from} WHERE msg.msg_id = too.msg_id AND msg.msg_id IN ({$sql_second_pass}) GROUP BY msg.msg_id";
        
        $db->sql_query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        $result = $db->sql_query($sql_third_pass);
        
        while($m = $db->sql_fetchrow($result)) {
            $m["author_ip"] = "";
            $m["author"] = new \scfr\phpbbJsonTemplate\helper\userinfo($m["author_id"]);
            if($m['pm_deleted']) $m["message_text"]= "";
            
            $messages[] = $m;
        }
        
        $main = $messages[(sizeof($messages) - 1)];
        $this->title  = $main["message_subject"];
        $this->id  = $main["msg_id"];
        $this->author = $main["author"];
        $this->start  = $main["message_time"];
        $this->last   = $messages[0]["message_time"];
        
        $this->messages = $messages;
        return $this;
    }
    
    
    
    public static function get_latest_convos($user_id, $start = 0, $limit = 20) {
        global $db;
        
        $froms = "FROM (" . PRIVMSGS_TABLE . " as msg LEFT JOIN " . PRIVMSGS_TO_TABLE . " as too ON msg.msg_id = too.msg_id)";
        print_r($limit);
        
        /** this query will get all our roots (= first message) of every convos we started or someone started to us*/
        $sql_convos = "SELECT msg.msg_id as rooty, root_level {$froms}
        WHERE root_level = 0
        AND (msg.author_id = {$user_id} OR user_id = {$user_id})
        
        GROUP BY rooty";
        
        /**
        * This query will get roots for convos that starts for us at another point than 0
        * i.e when we get CCI'd
        */
        $sql_part_convo = "SELECT MIN(msg.msg_id) as rooty, msg.root_level as root_level {$froms}
        WHERE root_level != 0 AND root_level NOT IN (SELECT rooty FROM ({$sql_convos}) as asd)
        AND too.user_id = {$user_id}
        
        GROUP BY root_level";
        
        /**
        * This query will amalgamate both to get all the starting point for all our convos
        */
        $sql_full_convos = "SELECT rooty, root_level FROM (({$sql_convos})  UNION ({$sql_part_convo})) as full ORDER BY rooty ";
        
        /**
        * Finally, we query for every single convos, and order them by their latest message
        * (latest message being biggest id)
        */
        $sql_order = "SELECT rooty, full.root_level FROM ({$sql_full_convos}) as full, testfo_privmsgs_convo as c WHERE
        
        (full.root_level = 0 AND full.rooty = c.root_level) OR (full.root_level <> 0 AND full.root_level = c.root_level)
        
        GROUP BY full.rooty
        ORDER BY c.last_time DESC
        LIMIT {$start}, {$limit}
        ";
        
        
        
        
        $db->sql_query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        
        
        $result = $db->sql_query($sql_order);
        
        while($c = $db->sql_fetchrow($result)) {
            print_r($c);
            $root = $c['root_level'] > 0 ? $c['root_level'] : $c['rooty'];
            
            print_r($root);
            echo "<hr />";
            $results[] = new convo($user_id, $root);
        }
        return $results;
    }
    
    private static function insert_convo($user, $msg_id, $time = 0) {
        global $db;
        
        $time = $time == 0 ? time() : $time;
        
        $sql = "INSERT INTO testfo_privmsgs_convo (user_id, last_time, creation_time, root_level)
        VALUES ({$user}, {$time}, {$time}, $msg_id)";
        
        $db->sql_query($sql);
    }
    
    public static function new_convo($users, $msg_id, $time) {
        
        if(gettype($users) == gettype(123)) $users = array($users);
        if(gettype($users) == gettype([])) foreach($users as $user) self::insert_convo($user, $msg_id, $time);
        else throw new \Exception("Invalid type for users");
            
        
        
    }
    
    public function populate_db($start=0, $limit=10) {
        global $db;
        
        $sql = "SELECT too.*, msg.*, c.id as existed FROM testfo_privmsgs_to as too, testfo_privmsgs as msg
        LEFT JOIN testfo_privmsgs_convo as c ON c.root_level = IF(msg.root_level = 0, msg.msg_id, msg.root_level)
        WHERE
        msg.msg_id = too.msg_id

        ORDER BY msg.msg_id ASC        
        LIMIT {$start}, {$limit}";
        
        echo $sql;
        
        $result = $db->sql_query($sql);
        while($c = $db->sql_fetchrow($result)) {
            $root = $c['root_level'] > 0 ? $c['root_level'] : $c['msg_id'];
            $users = [$c['user_id'], $c['author_id']];
            echo $c['message_time'];

            print_r($c['msg_id']);
            if($c['existed']) self::new_pm_in_convo($users, $root, $c['message_time']);
            else self::new_convo($users, $root, $c['message_time']);
                echo "<hr/>";
        }
    }
    
    
    public static function new_pm_in_convo($users, $root_level, $time = 0) {
        if(gettype($users) == gettype(123)) $users = array($users);
        if(gettype($users) == gettype([])) foreach($users as $user) self::update_convo($user, $root_level, $time);
        else throw new \Exception("Invalid type for users");
    }
    
    
    
    private static function update_convo($user, $root_level, $time=0) {
        global $db;
        $time = $time == 0 ? time() : $time;
        
        $sql = "UPDATE testfo_privmsgs_convo  SET last_time = {$time}
        WHERE root_level = {$root_level} AND user_id= {$user}";
        
        $db->sql_query($sql);
    }
}
?>