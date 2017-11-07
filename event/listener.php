<?php namespace scfr\phpbbJsonTemplate\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    /** @var \phpbb\template\template */
    protected $template;
    /** @var \phpbb\user */
    protected $user;
    /** @param \phpbb\db\driver\driver_interface */
    protected $db;
    
    protected $api;
    
    private $parentMap = [];
    private $forumIdMap = [];
    
    private $pre = false;
    
    
    /**
    * Constructor
    *
    * @param \phpbb\template\template             $template          Template object
    * @param \phpbb\user   $user             User object
    * @param \phpbb\db\driver\driver_interface   $db             Database object
    * @access public
    */
    public function __construct(\phpbb\template\template $template, \phpbb\user $user, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \scfr\phpbbJsonTemplate\helper\api $api) {
        $this->template = $template;
        $this->user = $user;
        $this->db = $db;
        $this->request = $request;
        $this->api = $api;
    }
    
    static public function getSubscribedEvents()
    {
        return array(
        'core.common' => 'json_header',
        'core.page_footer_after' => 'json_template',
        'core.make_jumpbox_modify_tpl_ary' => 'jumpbox',
        'core.submit_pm_after' => "submit_pm_after",
        );
    }
    
    public function jumpbox($event) {
        $this->pre = true;
        $raw = $event['row'];
        $tpl = $event['tpl_ary'];

        $idMap = [];

        foreach($tpl as &$tp) {
            if($tp['FORUM_ID'] > 0) {
                $tp['PARENT_ID'] = (integer) $raw['parent_id'];
                $this->forumIdMap[$tp['FORUM_ID']] = $tp;
                $this->check_unread_forum($tp['FORUM_ID']);
            }

        }

        $event['tpl_ary'] = $tpl;

        $this->parentMap[$raw['parent_id']][] = (integer) $raw['forum_id'];
    }
    
    /**
     * @todo CACHE THIS LIKE OMG
     * @param [type] $forum_id
     * @return void
     */
    private function check_unread_forum($forum_id)
    {
        global $db, $user;
        
        // The next block of code skips the check if the user is a guest (since prosilver and subsilver hide the unread link from guests)
        // or if the user is a a bot.  If you use a template that shows the link to unread posts for guests, you may want to get rid of the first part of the if
        // clause so that the text of the link to unread posts will toggle rather than always reading 'View unread posts'.
        if ($user->data['is_bot'])
        {
            return true;
        }
        
        $sql = 'SELECT f.forum_last_post_time, ft.mark_time
        FROM ' . FORUMS_TABLE . ' f
        LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft
        ON (f.forum_id = ft.forum_id AND ft.user_id = ' . $user->data['user_id'] . ')
        WHERE f.forum_id = ' . $forum_id;
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);


        // relevant mark time is the forums watch table mark time for this user or, if there is none, the user last mark time for this user
        $mark_time = ($row['mark_time']) ? $row['mark_time'] : $user->data['user_lastmark'];
        
        // if forum last post time is greater than relevant mark time then the forum has at least one unread post so return true
        if ($row['forum_last_post_time'] > $mark_time)
        {
            $this->bubble_forum_unread($forum_id);
            return true;
        }
        
        // forum has no unreads, so return false
        return false;
    }
    

    /**
     * Sets a forum as unread and bubble that state to its parent
     *
     * @param [type] $forum_id
     */
    private function bubble_forum_unread($forum_id) {
        if(!$forum_id || !isset($this->forumIdMap[$forum_id])) return;
        $this->forumIdMap[$forum_id]['UNREAD'] = true;

        if($this->forumIdMap[$forum_id]['PARENT_ID']) {
            $this->bubble_forum_unread($this->forumIdMap[$forum_id]['PARENT_ID']);}
    }

    private function should_display_json() {
        return $this->request->variable('scfr_json_callback', false);
    }
    
    public function json_header($event) {

        header('Access-Control-Allow-Origin: http://www.newforum.fr:4200');
        header('Access-Control-Allow-Credentials: true');
    }
    
    public function json_template($event) {
        
        if(!$this->pre) \make_jumpbox("");
        
        $this->template->assign_var("S_USER_ID", (integer) $this->user->data['user_id']);
        $this->template->assign_var("jumpbox_map", $this->parentMap);
        $this->template->assign_var("jumpbox_full", array_values($this->forumIdMap));
        
        $this->request->disable_super_globals();
        if($this->should_display_json()) {
            $this->api->render_json();
        }
        
        //$da = \scfr\phpbbJsonTemplate\helper\mp\convo::populate_db(0,100000);
    }
    
    /**
     * Performs our convo related pm thingy after a pm is submitted
     *
     * @param [type] $event
     * @return void
     */
    public function submit_pm_after($event) {
        global $db;
        $visibility = array_merge([$event['pm_data']['from_user_id']], array_keys($event['pm_data']['recipients']));

        $reply_from = (integer)($event['pm_data']["reply_from_root_level"]) > 0 ? (integer)($event['pm_data']["reply_from_root_level"]) : (integer) $event['pm_data']["reply_from_msg_id"];


        if($reply_from == 0)  \scfr\phpbbJsonTemplate\helper\mp\convo::new_convo($visibility, $event['pm_data']["msg_id"] );
        else \scfr\phpbbJsonTemplate\helper\mp\convo::new_pm_in_convo($visibility, $reply_from );
        
    }
    
}