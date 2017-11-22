<?php namespace scfr\phpbbJsonTemplate\helper\mp;

class convo {
    private $root_level;
    private $user_id;
    public $messages;
    public $author;
    public $start;
    public $last;
    public $participants;
    
    public function __construct($user_id, $root_msg_id) {
        $this->root_level = (integer) $root_msg_id;
        $this->user_id = (integer) $user_id;
    }
    
    /**
    * Hydrates the instance with all the relevant info from the db
    *
    * @param integer $start start index for messages
    * @param integer $limit limit of messages to query starting from $start
    * @return void
    */
    public function get_convo_content($start = 0, $limit = 20) {
        $this->get_convo_participants();
        $this->get_convo_messages($start, $limit);
    }
    
    /**
    * Hydrates the instance with all the participants in this conversation
    *
    * @return void
    */
    private function get_convo_participants() {
        global $db;
        
        $participants = [];
        // Currently selects only authors from the db. should we query for every user that can see the convo ?
        $sql = "SELECT too.author_id FROM " . PRIVMSGS_TO_TABLE . " as too, " . PRIVMSGS_TABLE . " as msg WHERE
        msg.msg_id = too.msg_id
        AND user_id= {$this->user_id}
        AND ((msg.root_level = 0 AND msg.msg_id = {$this->root_level}) OR (msg.root_level = {$this->root_level})) GROUP BY author_id";
        
        $result = $db->sql_query($sql);
        
        while($m = $db->sql_fetchrow($result))
        // insert participant if they're not the current user
        if($m['author_id'] != $this->user_id) $participants[] = \scfr\phpbbJsonTemplate\services\adresses::get()->getAdressFor("u_{$m['author_id']}");
        
        // hydrate the instance
        $this->participants = $participants;
    }
    
    /**
    * Hydrates the instance with the messages it contains
    *
    * @param integer $start start index for messages
    * @param integer $limit limit of messages to query starting from $start
    * @return void
    */
    private function get_convo_messages($start = 0, $limit = 20) {
        global $db, $user;
        $messages = [];
        
        $from = " FROM " . PRIVMSGS_TABLE . " as msg, " . PRIVMSGS_TO_TABLE . " as too";
        
        // Get all the msgs in this convo
        $sql_first_pass = "SELECT msg_id FROM " . PRIVMSGS_TABLE . " WHERE msg_id = {$this->root_level} OR root_level = {$this->root_level}";
        
        // Filter by only what the user can see
        $sql_second_pass = "SELECT msg_id FROM " . PRIVMSGS_TO_TABLE . " WHERE msg_id IN ({$sql_first_pass})
        AND (author_id = {$this->user_id} OR  user_id = {$this->user_id}) GROUP BY msg_id";
        
        $sql_third_pass = "SELECT * {$from} WHERE msg.msg_id = too.msg_id AND msg.msg_id IN ({$sql_second_pass}) GROUP BY msg.msg_id ORDER BY msg.msg_id DESC";
        
        $db->sql_query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        $result = $db->sql_query($sql_third_pass);
        
        while($m = $db->sql_fetchrow($result)) {
            $m["author_ip"] = "";
            $m["author"] = new \scfr\phpbbJsonTemplate\helper\userinfo($m["author_id"]);
            if($m['pm_deleted']) $m["message_text"]= "";
            
            $messages[] = self::type_check_message($m);
        }
        
        $main = $messages[0];
        
        foreach($messages as &$message) {
            $message["recipients"] = \scfr\phpbbJsonTemplate\services\adresses::get()->getAdressesFor($message["to_address"]);
            /** @todo check if the user can see (=is in) this bcc */
            //$message["bcc"] = \scfr\phpbbJsonTemplate\services\adresses::get()->getAdressesFor($message["bcc_address"]);
            
            $message["sent_at"] = $user->format_date($message['message_time']);
        }
        
        $this->title  = $main["message_subject"];
        $this->id  = (integer) $main["msg_id"];
        $this->author = $main["author"];
        $this->start  = (integer) $main["message_time"];
        $this->last   = (integer) $messages[(sizeof($messages) - 1)]["message_time"];
        
        $this->messages = $messages;
    }
    
    /**
    * Ensures a message object/array is correctly typed for front-end
    *
    * @param array $message
    * @return array correctly typed message
    */
    public static function type_check_message($message) {
        $integers = [
        "author_id",
        "folder_id",
        "icon_id",
        "message_attachment",
        "message_edit_count",
        "message_edit_time",
        "message_edit_user",
        "message_time",
        "msg_id",
        "root_level",
        "user_id"
        ];
        
        $bools = [
        "enable_bbcode",
        "enable_magic_url",
        "enable_sig",
        "enable_smilies",
        "message_reported",
        "pm_deleted",
        "pm_forwarded",
        "pm_marked",
        "pm_new",
        "pm_replied",
        "pm_unread"
        ];
        
        foreach($integers as $int)
        $message[$int] = (integer) $message[$int];
        foreach($bools as $bool)
        $message[$bool] = (boolean) $message[$bool];
        
        return $message;
    }
    
    
    /**
    * Helper function to get the latest convos of a given user
    *
    * @param integer $user_id the forum id of the user to fetch for
    * @param integer $start the offset of convos
    * @param integer $limit the number of convo to fetch
    * @return convo[] an array of relevant conversations
    */
    public static function get_latest_convos($user_id, $start = 0, $limit = 20) {
        global $db;
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS root_level FROM testfo_privmsgs_convo WHERE user_id = {$user_id}
        
        ORDER BY last_time DESC
        LIMIT {$start}, {$limit}
        ";
        
        $result = $db->sql_query($sql);
        $count = $db->sql_fetchrow($db->sql_query("SELECT FOUND_ROWS() as count"))["count"];
        
        while($c = $db->sql_fetchrow($result)) {
            $root = $c['root_level'] > 0 ? $c['root_level'] : $c['rooty'];
            
            $results[] = new convo($user_id, $root);
        }
        
        
        $currentPage = floor($start / $limit) <= 1 ? 1 : floor($start / $limit);
        return ["convos" => $results, "count" => $count, "page" => $currentPage, "pages" => ceil($count / $limit)];
    }
    
    /**
    * Helper method to hydrate the db on new PM
    *
    * @param integer $user
    * @param integer $msg_id
    * @param integer $time
    * @return void
    */
    private static function insert_convo($user, $msg_id, $time = 0) {
        global $db;
        
        $time = $time == 0 ? time() : $time;
        
        $sql = "INSERT IGNORE INTO testfo_privmsgs_convo (user_id, last_time, creation_time, root_level)
        VALUES ({$user}, {$time}, {$time}, $msg_id)";
        
        $db->sql_query($sql);
    }
    
    /**
    * Called when a PM is dispatched to try and create a new convo if one doesn't exist yet.
    *
    * @param integer|integer[] $users
    * @param integer $msg_id
    * @param integer $time
    * @return void
    */
    public static function new_convo($users, $msg_id, $time = 0) {
        if(gettype($users) == gettype(123)) $users = array($users);
        if(gettype($users) == gettype([])) foreach($users as $user) self::insert_convo($user, $msg_id, $time);
        else throw new \Exception("Invalid type for users");
        }
    
    /**
    * Helper function to migrate an old db to the convo system
    *
    * @param integer $start
    * @param integer $limit
    * @return void
    */
    public static function populate_db($start=0, $limit=50000) {
        global $db;
        
        echo "start populating";
        
        $sql = "SELECT too.*, msg.*, c.id as existed FROM testfo_privmsgs_to as too, testfo_privmsgs as msg
        LEFT JOIN testfo_privmsgs_convo as c ON c.root_level = IF(msg.root_level = 0, msg.msg_id, msg.root_level)
        WHERE
        msg.msg_id = too.msg_id AND msg.msg_id > ".$start."
        
        ORDER BY msg.msg_id ASC LIMIT ".$limit."";
        
        $result = $db->sql_query($sql);
        while($c = $db->sql_fetchrow($result)) {
            $root = $c['root_level'] > 0 ? $c['root_level'] : $c['msg_id'];
            $users = [$c['user_id'], $c['author_id']];
            
            echo $c['msg_id'].":".$c['message_time'];
            
            if($c['existed']) self::new_pm_in_convo($users, $root, $c['message_time']);
            else self::new_convo($users, $root, $c['message_time']);
                echo "\n";
        }
    }
    
    /**
    * Called by event on new PM, will ensure a convo exists for this PM and update it with the latest
    * timestamp
    *
    * @param integer|integer[] $users
    * @param integer $root_level
    * @param integer $time
    * @return void
    */
    public static function new_pm_in_convo($users, $root_level, $time = 0) {
        if(gettype($users) == gettype(123)) $users = array($users);
        if(gettype($users) == gettype([])) foreach($users as $user) {
            // Ensure this convo exists if we wanna update it, this won't change anything if it exists.
            self::new_convo($users, $root_level, $time);
            self::update_convo($user, $root_level, $time);
        }
        else throw new \Exception("Invalid type for users");
        }
    
    
    /**
    * Helper method to hydrate the db on new pm in an existing convo
    *
    * @param integer $user
    * @param integer $root_level
    * @param integer $time
    * @return void
    */
    private static function update_convo($user, $root_level, $time=0) {
        global $db;
        $time = $time == 0 ? time() : $time;
        
        $sql = "UPDATE testfo_privmsgs_convo  SET last_time = {$time}
        WHERE root_level = {$root_level} AND user_id= {$user}";
        
        $db->sql_query($sql);
    }
}
?>