<?php namespace scfr\phpbbJsonTemplate\helper;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class api extends \phpbb\controller\helper {
  public function __construct(\phpbb\template\template $template, \phpbb\user $user, \phpbb\config\config $config, \phpbb\symfony_request $symfony_request, \phpbb\request\request_interface $request, \phpbb\routing\helper $routing_helper) {
    parent::__construct($template, $user, $config, $symfony_request, $request, $routing_helper);
  }

  private function access_protected($obj, $prop) {
    $reflection = new \ReflectionClass($obj);
    $property = $reflection->getProperty($prop);
    $property->setAccessible(true);
    return $property->getValue($obj);
  }

  public function get_template_vars() {
    $tpldata = $this->access_protected($this->access_protected($this->template, "context"), "tpldata");

    foreach($tpldata as $pp => $val) {
      if($pp == "." && isset($val[0])) foreach($val[0] as $p => $v) $return[$p] = $v;
      else $return[$pp] = $val;
    }

    return $return;
  }


    public function render_json($status_code = 200) {

      $json = json_encode(
        array('@template' => $this->get_template_vars())
      );

      $this->template->assign_vars(array( "SCFR_RETURN" => $json));

  		$this->template->set_filenames(array(
      	'body'	=> "scfr_phpbbjsontemplate_json.html",
      ));

      $headers = array(
        'Content-Type' => 'application/json'
      );

    	return new Response($this->template->assign_display('body'), $status_code, $headers);
    }

}
?>
