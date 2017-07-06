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
        foreach($test->get_user_latest_convos() as $convos) {
            $cs[] = $convos->get_convo_content();
        }

        $this->payload = $cs;
    }
}