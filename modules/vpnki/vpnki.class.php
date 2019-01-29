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
class vpnki extends module
{
    /**
     * vpnki
     *
     * Module class constructor
     *
     * @access private
     */
    function vpnki()
    {
        $this->name = "vpnki";
        $this->title = "VPNki";
        $this->module_category = "<#LANG_SECTION_SYSTEM#>";
        $this->checkInstalled();
    }

    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 0)
    {
        $p = array();
        if (IsSet($this->id)) {
            $p["id"] = $this->id;
        }
        if (IsSet($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (IsSet($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (IsSet($this->tab)) {
            $p["tab"] = $this->tab;
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
    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }

    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run()
    {
        $this->getConfig();
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (IsSet($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (IsSet($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {

        if (gr('ok_msg')) {
            $out['OK_MSG']=gr('ok_msg');
        }
        if (gr('err_msg')) {
            $out['ERR_MSG']=gr('err_msg');
        }

        $out['API_URL'] = $this->config['API_URL'];
        $out['API_USERNAME'] = $this->config['API_USERNAME'];
        $out['API_PASSWORD'] = $this->config['API_PASSWORD'];
        $out['LATEST_HTTP_ADDRESS'] = $this->config['LATEST_HTTP_ADDRESS'];
        $out['LATEST_HTTP_PORT'] = $this->config['LATEST_HTTP_PORT'];
        $out['LATEST_SSH_ADDRESS'] = $this->config['LATEST_SSH_ADDRESS'];
        $out['LATEST_SSH_PORT'] = $this->config['LATEST_SSH_PORT'];
        $out['API_KEY'] = $this->config['API_KEY'];


        $out['TAB'] = $this->tab;

        if ($this->tab=='service') {
            $tmp=(exec('dpkg -l pptp-linux|grep pptp-linux'));
            if (preg_match('/^i/',$tmp)) {
                $out['PPTP_INSTALLED']=$tmp;
            }
        }
        if ($this->mode=='install_pptp') {
            $this->install_pptp($out);
        }

        if ($this->mode == 'connect' && $out['API_URL'] && $out['API_USERNAME'] && $out['API_PASSWORD']) {
            $this->connect();
            $this->redirect("?");
        }

        if ($this->mode == 'disconnect') {
            $this->disconnect();
            $this->redirect("?");
        }

        if ($this->mode=='qh_connect') {
            $this->config['API_KEY']=gr('api_key');
            $this->saveConfig();
            $this->qh_connect($out);
        }

        if ($this->mode=='qh_disconnect') {
            $this->qh_disconnect($out);
            $this->redirect("?");
        }

        if ($this->mode=='connect_saved') {
            $this->connect(1);
        }

        if ($this->view_mode == 'update_settings') {
            $ok = 1;
            global $api_url;
            $this->config['API_URL'] = $api_url;
            global $api_username;
            $this->config['API_USERNAME'] = $api_username;
            global $api_password;
            $this->config['API_PASSWORD'] = $api_password;
            if ($ok) {
                $this->saveConfig();
                $this->redirect("?tab={$this->tab}&ok_msg=".urlencode(LANG_DATA_SAVED));
            }
        }
    }

    function install_pptp(&$out) {
        safe_exec('apt-get update && apt-get -y install pptp-linux');
        $out['OK_MSG']='Installation command added to queue, please wait about 5 minutes.';
    }

    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out)
    {
        if ($this->ajax) {
            global $op;
            if ($op == 'status') {
                $res = exec('ifconfig', $ret);
                $data = implode("\n", $ret);
                $data = preg_replace("/(\d+\.\d+\.\d+\.\d+)/uis", '<b>$1</b>', $data);
                $data = str_replace("ppp0", '<b style="color:red">ppp0</b>', $data);
                echo "<pre>System time: " .date('Y-m-d H:i:s')."\n\n". $data . "</pre>";
            }
            if ($op == 'connect') {
                $this->connect();
            }
            if ($op == 'disconnect') {
                $this->disconnect();
            }
            exit;
        }
        $this->admin($out);
    }


    function qh_disconnect(&$out) {

        $this->disconnect();

        $api_key=$this->config['API_KEY'];
        if (!$api_key) {
            return;
        }


        if ($this->config['LATEST_HTTP_PORT']) {
            $url = 'https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=port&action=remove&port='.$this->config['LATEST_HTTP_PORT'].'&key='.urlencode($api_key);
            $remove_port=getURL($url);
            DebMes($url."\n".$remove_port,'vpnki');
            $this->config['LATEST_HTTP_ADDRESS']='';
            $this->config['LATEST_HTTP_PORT']='';
        }
        if ($this->config['LATEST_SSH_PORT']) {
            $url = 'https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=port&action=remove&port='.$this->config['LATEST_SSH_PORT'].'&key='.urlencode($api_key);
            $remove_port=getURL($url);
            DebMes($url."\n".$remove_port,'vpnki');
            $this->config['LATEST_SSH_ADDRESS']='';
            $this->config['LATEST_SSH_PORT']='';
        }

        $tunnel_name=$this->config['LATEST_TUNNEL_NAME'];
        if ($tunnel_name!='') {
            $url = 'https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=del_tunnel&tunnel='.urlencode($tunnel_name).'&key='.urlencode($api_key);
            $remove_tunnel=getURL($url);
            DebMes($url."\n".$remove_tunnel,'vpnki');
            $this->config['LATEST_TUNNEL_IP']='';
            $this->config['LATEST_TUNNEL_NAME']='';
        }
        $this->config['LATEST_CONNECT_USERNAME']='';
        $this->config['LATEST_CONNECT_PASSWORD']='';
        
        $this->saveConfig();
    }

    function qh_connect(&$out) {
        $api_key=$this->config['API_KEY'];
        if (!$api_key) {
            return;
        }

        $this->qh_disconnect($out);
        //
        // STEP 1. New tunnel
        $url = 'https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=add_tunnel&key='.urlencode($api_key);
        $new_tunnel=getURL($url);
        DebMes($url."\n".$new_tunnel,'vpnki');
        $new_tunnel_data=json_decode($new_tunnel,true);

        $new_tunnel_ip = $new_tunnel_data['vpnki_address'];
        $this->config['LATEST_TUNNEL_IP']=$new_tunnel_data['vpnki_address'];
        $this->config['LATEST_TUNNEL_NAME']=$new_tunnel_data['comment'];
        $this->config['LATEST_CONNECT_USERNAME']=$new_tunnel_data['login'];
        $this->config['LATEST_CONNECT_PASSWORD']=$new_tunnel_data['password'];
        $this->saveConfig();

        if (!$this->config['LATEST_TUNNEL_IP']) {
            if ($new_tunnel_data['add error']) {
                $out['ERR_MSG']='Error: '.$new_tunnel_data['add error'];
            } else {
                $out['ERR_MSG']='Error: '.$new_tunnel_data['message'];
            }
            return;
        }

        // STEP 2.1 Port publishing (80)
        $port = 80;
        $url = 'https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=port&action=add&inip='.urlencode($new_tunnel_ip).'&inport='.$port.'&key='.urlencode($api_key);
        $forward_request = getURL($url);
        DebMes($url."\n".$forward_request,'vpnki');
        $forward_request_data = json_decode($forward_request,true);

        //dprint($url,false);
        //dprint($forward_request_data,false);
        if (!$forward_request_data['address']) {
            $out['ERR_MSG'].='<br/>Error: '.$forward_request_data['error'];
        }
        $http_address = $forward_request_data['address'];
        $http_port = $forward_request_data['port'];
        if ($http_address && $http_port) {
            $this->config['LATEST_HTTP_ADDRESS']=$http_address;
            $this->config['LATEST_HTTP_PORT']=$http_port;
        } else {
            $this->config['LATEST_HTTP_ADDRESS']='';
            $this->config['LATEST_HTTP_PORT']='';
        }
        $this->saveConfig();

        // STEP 2.2 Port publishing (22)
        $port = 22;
        $url ='https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=port&action=add&inip='.urlencode($new_tunnel_ip).'&inport='.$port.'&key='.urlencode($api_key);
        $forward_request = getURL($url);
        DebMes($url."\n".$forward_request,'vpnki');
        $forward_request_data = json_decode($forward_request,true);
        //dprint($url,false);
        //dprint($forward_request_data,false);
        if (!$forward_request_data['address']) {
            $out['ERR_MSG'].='<br/>Error: '.$forward_request_data['error'];
        }
        $ssh_address = $forward_request_data['address'];
        $ssh_port = $forward_request_data['port'];
        if ($ssh_address && $ssh_port) {
            $this->config['LATEST_SSH_ADDRESS']=$ssh_address;
            $this->config['LATEST_SSH_PORT']=$ssh_port;
        } else {
            $this->config['LATEST_SSH_ADDRESS']='';
            $this->config['LATEST_SSH_PORT']='';
        }
        $this->saveConfig();

        if ($http_address) {
            $this->redirect("?mode=connect_saved");
        }
        //{"action":"Added successfully","address":"193.232.49.4","port":"26033"}
    }

    function connect($saved=0)
    {

        if ($saved) {
            $username=$this->config['LATEST_CONNECT_USERNAME'];
            $password=$this->config['LATEST_CONNECT_PASSWORD'];
            $server=$this->config['API_URL'];
        } else {
            $username=$this->config['API_USERNAME'];
            $password=$this->config['API_PASSWORD'];
            $server=$this->config['API_URL'];
        }

        if (!$server) {
            $server='vpnki.ru';
        }

        $cmd='sudo pptpsetup --delete vpnki';
        debmes($cmd,'vpnki');
        safe_exec($cmd);
        $cmd='sudo pptpsetup --create vpnki --server ' . $server . ' --username ' . $username . ' --password ' . $password;
        debmes($cmd,'vpnki');
        safe_exec($cmd);
        sleep(1);
        $cmd='sudo pon vpnki updetach';
        debmes($cmd,'vpnki');
        safe_exec($cmd);
        $cmd='sudo ip route add 172.16.0.0/16 via 172.16.0.1';
        debmes($cmd,'vpnki');
        setTimeout('ip_route_add', "safe_exec('$cmd');", 3);

    }

    function disconnect()
    {
        safe_exec('sudo poff vpnki');
        safe_exec('sudo ip route del 172.16.0.0');
    }

    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($data = '')
    {
        parent::install();
    }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgTWFyIDEzLCAyMDE3IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
