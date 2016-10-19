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
      'core.page_header_after' => 'json_header',
      'core.page_footer_after' => 'json_template',
    );
  }

  private function should_display_json() {
    return $this->request->variable('scfr_json_callback', false);
  }

  public function json_header($event) {
    if($this->should_display_json())  {
      $headers = $event["http_headers"];
      $headers["Content-type"] = "application/json";
      $event["http_headers"] = $headers;
    }
  }

  public function json_template($event) {
    if($this->should_display_json()) {
      $this->api->render_json();
    }
  }


}
?>
