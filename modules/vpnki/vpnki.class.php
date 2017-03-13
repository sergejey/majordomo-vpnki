<?php
/**
* VPN ki 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 12:03:12 [Mar 13, 2017])
*/
//
//
class vpnki extends module {
/**
* vpnki
*
* Module class constructor
*
* @access private
*/
function vpnki() {
  $this->name="vpnki";
  $this->title="VPNki";
  $this->module_category="<#LANG_SECTION_SYSTEM#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
  $this->getConfig();
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 $out['API_URL']=$this->config['API_URL'];
 $out['API_USERNAME']=$this->config['API_USERNAME'];
 $out['API_PASSWORD']=$this->config['API_PASSWORD'];

 if ($this->mode=='connect' && $out['API_URL'] && $out['API_USERNAME'] && $out['API_PASSWORD']) {
     $this->connect();
     $this->redirect("?");
 }

 if ($this->mode=='disconnect') {
     $this->disconnect();
     $this->redirect("?");
 }

 if ($this->view_mode=='update_settings') {
     $ok=1;
   global $api_url;
   $this->config['API_URL']=$api_url;
   global $api_username;
   $this->config['API_USERNAME']=$api_username;
     if (!$this->config['API_USERNAME']) {
         $out['ERR_API_USERNAME']=1;
         $ok=0;
     }
   global $api_password;
   $this->config['API_PASSWORD']=$api_password;
     if (!$this->config['API_PASSWORD']) {
         $out['ERR_API_PASSWORD']=1;
         $ok=0;
     }
     if ($ok) {
         $this->saveConfig();
         $this->redirect("?");
     }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
    if ($this->ajax) {
        global $op;
        if ($op=='status') {
            echo date('Y-m-d H:i:s')."<br/>";
            $res=exec('ifconfig',$ret);
            $data=implode("\n",$ret);
            $data=preg_replace("/(\d+\.\d+\.\d+\.\d+)/uis",'<b>$1</b>',$data);
            $data=str_replace("ppp0",'<b style="color:red">ppp0</b>',$data);
            echo "<pre>".$data."</pre>";
        }
        if ($op=='connect') {
            $this->connect();
        }
        if ($op=='disconnect') {
            $this->disconnect();
        }
        exit;
    }
 $this->admin($out);
}

 function connect() {
     safe_exec('pptpsetup --delete vpnki');
     safe_exec('pptpsetup --create vpnki --server '.$this->config['API_URL'].' --username '. $this->config['API_USERNAME'].' --password '.$this->config['API_PASSWORD']);
     sleep(2);
     safe_exec('pon vpnki updetach');
 }
 function disconnect() {
     safe_exec('poff vpnki');
 }

/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgTWFyIDEzLCAyMDE3IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
