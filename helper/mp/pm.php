<?php namespace scfr\phpbbJsonTemplate\helper\mp;

class pm {
    
    public static function get_convo_of_pm_by_id($pm_id) {
        global $db, $user;
        $sql = "SELECT msg_id, root_level, message_subject FROM ".PRIVMSGS_TABLE." WHERE msg_id = {$pm_id}";
        
        $result = $db->sql_query($sql);
        $pm = $db->sql_fetchrow($result);
        
        if($pm['root_level'] == 0) $ret = ["convo_id" => (integer) $pm_id, "convo_title" => $pm['message_subject']];
        else {
            $sql = "SELECT message_subject FROM ".PRIVMSGS_TABLE." WHERE msg_id = {$pm['root_level']}";

            $result = $db->sql_query($sql);
            $root_pm = $db->sql_fetchrow($result);

            $ret =  ["convo_id" => (integer) $pm['root_level'] , "convo_title" => $root_pm['message_subject']];
        }
        

        return $ret;
    }
    
}