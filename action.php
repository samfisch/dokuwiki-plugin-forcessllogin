<?php
/** 
 * @license    GPL3 (http://www.gnu.org/licenses/gpl.html)
 * @author     Samuel Fischer <sf@notomorrow.de>
 * 
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'action.php');

class action_plugin_forcessllogin extends DokuWiki_Action_Plugin {
  function getInfo( ) {
    return array(
        'author' => 'Samuel Fischer',
        'email'  => 'sf@notomorrow.de',
        'date'   => '2012-01-23',
        'name'   => 'forcessllogin',
        'desc'   => 'redirects login requests to ssl',
        'url'    => 'http://www.dokuwiki.org/plugin:forcessllogin',
    );
  }
  function register(Doku_Event_Handler $controller) {
    $controller->register_hook('TPL_ACT_RENDER', 'BEFORE',  $this, 'forcessllogin');
    $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE',  $this, 'forcessllogin');
  }
  function forcessllogin(&$event, $param) {
    global $ACT;
    if( is_ssl( )) return;

    $acts = explode(',',$this->getConf('actions'));
    $intercept = false;

    if( !is_array( $acts )) $acts = array( );
    if( $ACT == 'denied' && in_array( 'login', $acts )){ $intercept = true; }
    elseif( !in_array( $ACT, $acts )) return;

    if( $event->name == 'ACTION_ACT_PREPROCESS' && !$this->getConf('splashpage')) {
      send_redirect( $this->_path( ));
      exit;
    }
    if( $event->name == 'TPL_ACT_RENDER' ) {
      $event->preventDefault();
      if( $intercept ){
        if( $ACT == 'denied' ) echo p_locale_xhtml('denied');
        if( !in_array( $ACT, $acts )) return;
        $ACT = 'login';
      }
      echo $this->locale_xhtml('splashpage');
      $this->_render( $ACT );
    }
  }
  function _render( $act ) {
    global $ID;
    $form = new Doku_Form(array('id'=>'forcessllogin1',
        'action' => $this->_path( ),
        'method' => 'get'));
     $form->addHidden( 'id', $ID );
     $form->addHidden( 'do', $act );
    if( $this->getConf('cert')) {
      if( strpos( $this->getLang('certinfo'), '{{name}}' ) !== false ) {
        $form->addElement('<p>'
            .str_replace( '{{name}}', $this->getConf('cert'), $this->getLang('certinfo') )
            .'</p>'.NL ); } 
      else {
        $form->addElement('<p>'.$this->getLang('certinfo')." ".$this->getConf('cert').'</p>'.NL ); }}

    if( $this->getConf('ca')) {
      if( strpos( $this->getLang('ca'), '{{name}}' ) !== false ) {
        $form->addElement('<p>'
            .str_replace( '{{name}}', $this->getConf('ca'), $this->getLang('cainfo') )
            .'</p>'.NL ); }
      else {
        $form->addElement('<p>'.$this->getLang('cainfo')
            ." <a href='".$this->getConf('ca')."'>".$this->getConf('ca')."</a></p>".NL ); }}

    $form->addElement(form_makeButton('submit','',$this->getLang('submit'),
      array('accesskey'=>'h','title'=>$this->getLang('submittitle'), id=>'focus__this' )));
    $form->printForm();

    $form = new Doku_Form(array('id'=>'forcessllogin2', 'method' => 'get'));
    $form->addElement(form_makeButton('submit','',$this->getLang('cancel'),
      array('accesskey'=>'c','title'=>$this->getLang('canceltitle'))));
    $form->printForm();
  }
  function _path( ) {
    $base = str_replace( 'http://', 'https://', getBaseUrl( 'absolute' ));
    $base = rtrim($base, getBaseUrl());  # right trim the dokuwiki directory from the base URL
    $path = $_SERVER['REQUEST_URI'];
    return $base.$path;
  }
}
