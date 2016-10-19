<?php namespace scfr\phpbbJsonTemplate\helper;

use phpbb\json_response;

class api extends json_response {

  public function __construct(\phpbb\template\template $template, \phpbb\user $user) {
    $this->template = $template;
    $this->user = $user;
  }

  private function access_protected($obj, $prop) {
    $reflection = new \ReflectionClass($obj);
    $property = $reflection->getProperty($prop);
    $property->setAccessible(true);
    return $property->getValue($obj);
  }

  private function get_template_vars() {
    $tpldata = $this->access_protected($this->access_protected($this->template, "context"), "tpldata");

    foreach($tpldata as $pp => $val) {
      if($pp == "." && isset($val[0])) foreach($val[0] as $p => $v) $return[$p] = $v;
      else $return[$pp] = $val;
    }

    return $return;
  }

  public function get_template_filename() {
    return $this->template->get_user_style()[0] . "/template/" . $this->access_protected($this->template, "filenames")["body"];
  }

  public function render_json($status_code = 200) {
    $json = array('@template' => $this->get_template_vars());
    $this->send($json);
  }

}
?>
