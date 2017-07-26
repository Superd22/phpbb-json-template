<?php namespace scfr\phpbbJsonTemplate\controller\api;

use \Symfony\Component\HttpFoundation\Response;

class mp {
    
    protected $helper;
    protected $user;
    protected $api;
    protected $payload;
    
    public function __construct(\phpbb\controller\helper $helper, \phpbb\user $user,  \scfr\phpbbJsonTemplate\helper\api $api) {
        $this->api      = $api;
        $this->helper   = $helper;
        $this->user     = $user;
    }
    

    public function handle($mode) {
        $this->switch_mode($mode);
        return $this->api->render_message($this->payload);
    }

    private function switch_mode($mode) {
        switch($mode) {
            case "latest_convo":
                $this->latest_convo();
            break;
        }
    }

    private function latest_convo() {
        $test = new \scfr\phpbbJsonTemplate\helper\mp\user($this->user->data['user_id']);

        $postPerPage = request_var("convoPerPage", 20);
        $page = request_var("page", 1) - 1;
        if($page < 0) $page = 0;
        $start = $page * $postPerPage; 

        $payload = $test->get_user_latest_convos($start, $postPerPage);

        foreach($payload['convos'] as &$convo) {
            $convo->get_convo_content();
        }

        $this->payload = $payload;
    }
}