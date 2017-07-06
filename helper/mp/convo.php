<?php namespace scfr\phpbbJsonTemplate\helper\mp;
class convo {
    private $root_level;
    private $user_id;
    private $msg_id;
    public $messages;
    public $author;
    public $start;
    public $last;
    
    public function __construct($user_id, $msg_id, $root_level = 0) {
        $this->root_level = (integer) $root_level;
        $this->user_id = (integer) $user_id;
        $this->msg_id = (integer) $msg_id;
    }
    
    public function get_convo_content($start = 0, $limit = 20) {
        global $db;
        $messages = [];
        
        $sql = "SELECT * FROM " . PRIVMSGS_TABLE . " as msg, " . PRIVMSGS_TO_TABLE . " as too
        WHERE too.msg_id = msg.msg_id AND (too.user_id='{$this->user_id}' OR too.author_id='{$this->user_id}')
        AND ((msg.root_level = 0 AND (msg.msg_id='{$this->msg_id}' OR msg.msg_id='{$this->root_level}')) OR (msg.root_level != 0 AND (msg.root_level = '{$this->root_level}' OR msg.root_level = '{$this->msg_id}')))
        GROUP BY msg.msg_id ORDER BY msg.msg_id DESC LIMIT {$start}, {$limit}";
        $result = $db->sql_query($sql);
        
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
        
        $sub_one = " FROM " . PRIVMSGS_TABLE . " as msg, " . PRIVMSGS_TO_TABLE . " as too 
                WHERE too.msg_id = msg.msg_id AND too.user_id='{$user_id}' 
                AND msg.root_level != '0' AND pm_deleted = '0' AND folder_id <> -1  AND folder_id <> -2 GROUP BY msg.root_level";

        $sql = "SELECT too.msg_id,msg.root_level  FROM " . PRIVMSGS_TABLE . " as msg , " . PRIVMSGS_TO_TABLE . " as too, 
        (
            (
                ( SELECT DISTINCT msg.root_level, msg.msg_id {$sub_one} ) 
                    UNION 
                ( SELECT msg.root_level, msg.msg_id FROM " . PRIVMSGS_TABLE . " as msg, " . PRIVMSGS_TO_TABLE . " as too 
                WHERE too.msg_id = msg.msg_id AND too.user_id='{$user_id}' 
                AND msg.root_level = '0'  AND pm_deleted = '0' AND folder_id <> -1  AND folder_id <> -2 
                AND msg.msg_id NOT IN ( SELECT DISTINCT msg.root_level {$sub_one} )      
                )
            ) 
        ) as target
        WHERE too.msg_id = msg.msg_id AND too.msg_id = target.msg_id GROUP BY msg.msg_id, msg.root_level ORDER BY target.msg_id DESC LIMIT {$start}, {$limit} ";
        
        
        $result = $db->sql_query($sql);
        while($c = $db->sql_fetchrow($result)) {
            $results[] = new convo($user_id, $c['msg_id'], $c['root_level']);
        }
        return $results;
    }
}
?>