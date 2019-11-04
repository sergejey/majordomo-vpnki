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
            $out['OK_MSG'] = gr('ok_msg');
        }
        if (gr('err_msg')) {
            $out['ERR_MSG'] = gr('err_msg');
        }

        $out['API_URL'] = $this->config['API_URL'];
        $out['API_USERNAME'] = $this->config['API_USERNAME'];
        $out['API_PASSWORD'] = $this->config['API_PASSWORD'];
        $out['LATEST_HTTP_ADDRESS'] = $this->config['LATEST_HTTP_ADDRESS'];
        $out['LATEST_HTTP_PORT'] = $this->config['LATEST_HTTP_PORT'];
        $out['LATEST_SSH_ADDRESS'] = $this->config['LATEST_SSH_ADDRESS'];
        $out['LATEST_SSH_PORT'] = $this->config['LATEST_SSH_PORT'];
        $out['API_KEY'] = $this->config['API_KEY'];
        $out['API_TOKEN'] = $this->config['API_TOKEN'];
        $out['API_PORT'] = $this->config['API_PORT'];
        $out['API_TYPE'] = $this->config['API_TYPE'];


        $out['TAB'] = $this->tab;

        if ($this->tab == 'service') {
            $tmp = (exec('dpkg -l pptp-linux|grep pptp-linux'));
            if (preg_match('/^i/', $tmp)) {
                $out['PPTP_INSTALLED'] = $tmp;
            }
            $tmp = (exec('dpkg -l openvpn|grep openvpn'));
            if (preg_match('/^i/', $tmp)) {
                $out['OPENVPN_INSTALLED'] = $tmp;
            }
        }
        if ($this->mode == 'install_pptp') {
            $this->install_pptp($out);
        }
        if ($this->mode == 'install_openvpn') {
            $this->install_openvpn($out);
        }

        if ($this->mode == 'connect' && $out['API_URL'] && $out['API_USERNAME'] && $out['API_PASSWORD']) {
            $this->connect();
            $this->redirect("?");
        }

        if ($this->mode == 'disconnect') {
            $this->disconnect();
            $this->redirect("?");
        }

        if ($this->mode == 'qh_connect') {
            $this->config['API_TOKEN'] = gr('api_token');
            $this->config['API_SERVICE'] = gr('api_service');
            $this->config['API_TYPE'] = gr('api_type');
            $this->config['API_PORT'] = gr('api_port');
            $this->saveConfig();
            $this->qh_connect($out);
        }

        if ($this->mode == 'qh_disconnect') {
            $this->qh_disconnect($out,1);
            $this->config['API_TOKEN']='';
            $this->saveConfig();
            $this->redirect("?");
        }

        if ($this->mode == 'connect_saved') {
            $this->connect(1);
            $out['OK_MSG']='Connecting...';
        }

        if ($this->view_mode == 'update_settings') {
            $ok = 1;
            global $api_url;
            $this->config['API_URL'] = $api_url;

            global $api_type;
            $this->config['API_TYPE'] = $api_type;

            global $api_username;
            $this->config['API_USERNAME'] = $api_username;
            global $api_password;
            $this->config['API_PASSWORD'] = $api_password;
            if ($ok) {
                $this->saveConfig();
                $this->redirect("?tab={$this->tab}&ok_msg=" . urlencode(LANG_DATA_SAVED));
            }
        }
    }

    function install_pptp(&$out)
    {
        safe_exec('apt-get update && apt-get -y install pptp-linux');
        $out['OK_MSG'] = 'PPTP installation command added to queue, please wait about 5 minutes.';
    }

    function install_openvpn(&$out)
    {
        safe_exec('apt-get update && apt-get -y install openvpn');
        $out['OK_MSG'] = 'OpenVPN installation command added to queue, please wait about 5 minutes.';
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
                echo "<pre>System time: " . date('Y-m-d H:i:s') . "\n\n" . $data . "</pre>";
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


    function qh_disconnect(&$out, $delete = 0)
    {

        $this->disconnect();

        $api_key = $this->config['API_KEY'];
        $api_token = $this->config['API_TOKEN'];

        if (!$api_token) {
            return;
        }

        if ($this->config['LATEST_HTTP_PORT'] || $this->config['LATEST_SSH_PORT']) {
            if ($delete) {
                $url = 'https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=token&key=partner&token=' . $api_token . '&action=delete';
                $reply = getURL($url);
                $data = json_decode($reply, true);
                DebMes($url . "\n" . $reply, 'vpnki');

            }
            $this->config['LATEST_HTTP_ADDRESS'] = '';
            $this->config['LATEST_HTTP_PORT'] = '';
            $this->config['LATEST_SSH_ADDRESS'] = '';
            $this->config['LATEST_SSH_PORT'] = '';
        }
        $this->config['LATEST_CONNECT_USERNAME'] = '';
        $this->config['LATEST_CONNECT_PASSWORD'] = '';

        $this->saveConfig();
    }

    function qh_connect(&$out)
    {
        $api_token = $this->config['API_TOKEN'];
        if (!$api_token) {
            return;
        }
        $this->qh_disconnect($out);

        $int_port = (int)$this->config['API_PORT'];
        if (!$int_port) {
            $int_port=22;
        }


        // STEP 0. Token status
        $url = 'https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=token&key=partner&token=' . $api_token . '&action=status';
        $reply = getURL($url);
        $data = json_decode($reply, true);
        DebMes($url . "\n" . $reply, 'vpnki');
        if ($data['status'] != 'not active' && $data['status']!='active') {
            $out['ERR_MSG'] = 'Incorrect token status: ' . $data['token'].' - '.$data['status'];
            return;
        }

        // STEP 1. Get tunnel data
        $url = 'https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=token&key=partner&token=' . $api_token . '&port='.$int_port.'&action=activate';
        $reply = getURL($url);
        $data = json_decode($reply, true);
        DebMes($url . "\n" . $reply, 'vpnki');
        $this->config['LATEST_TUNNEL_IP'] = $data['internal_ip']; //
        $this->config['LATEST_TUNNEL_NAME'] = $data['end_datetime'];
        $this->config['LATEST_CONNECT_USERNAME'] = $data['username'];
        $this->config['LATEST_CONNECT_PASSWORD'] = $data['password'];

        if ($int_port=='22') {
            $this->config['LATEST_SSH_ADDRESS'] = $data['external_ip'];
            $this->config['LATEST_SSH_PORT'] = $data['external_port'];
        } else {
            $this->config['LATEST_HTTP_ADDRESS'] = $data['external_ip'];
            $this->config['LATEST_HTTP_PORT'] = $data['external_port'];
        }
        $this->saveConfig();

        //dprint($data);

        /*
        //
        // STEP 1. New tunnel
        $url = 'https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=add_tunnel&key=' . urlencode($api_key);
        $new_tunnel = getURL($url);
        DebMes($url . "\n" . $new_tunnel, 'vpnki');
        $new_tunnel_data = json_decode($new_tunnel, true);

        $new_tunnel_ip = $new_tunnel_data['vpnki_address'];
        $this->config['LATEST_TUNNEL_IP'] = $new_tunnel_data['vpnki_address'];
        $this->config['LATEST_TUNNEL_NAME'] = $new_tunnel_data['comment'];
        $this->config['LATEST_CONNECT_USERNAME'] = $new_tunnel_data['login'];
        $this->config['LATEST_CONNECT_PASSWORD'] = $new_tunnel_data['password'];
        $this->saveConfig();

        if (!$this->config['LATEST_TUNNEL_IP']) {
            if ($new_tunnel_data['add error']) {
                $out['ERR_MSG'] = 'Error: ' . $new_tunnel_data['add error'];
            } else {
                $out['ERR_MSG'] = 'Error: ' . $new_tunnel_data['message'];
            }
            return;
        }

        // STEP 2.1 Port publishing (80)
        $port = 80;
        $url = 'https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=port&action=add&inip=' . urlencode($new_tunnel_ip) . '&inport=' . $port . '&key=' . urlencode($api_key);
        $forward_request = getURL($url);
        DebMes($url . "\n" . $forward_request, 'vpnki');
        $forward_request_data = json_decode($forward_request, true);
        if (!$forward_request_data['address']) {
            $out['ERR_MSG'] .= '<br/>Error: ' . $forward_request_data['error'];
        }
        $http_address = $forward_request_data['address'];
        $http_port = $forward_request_data['port'];
        if ($http_address && $http_port) {
            $this->config['LATEST_HTTP_ADDRESS'] = $http_address;
            $this->config['LATEST_HTTP_PORT'] = $http_port;
        } else {
            $this->config['LATEST_HTTP_ADDRESS'] = '';
            $this->config['LATEST_HTTP_PORT'] = '';
        }
        $this->saveConfig();

        // STEP 2.2 Port publishing (22)
        $port = 22;
        $url = 'https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=port&action=add&inip=' . urlencode($new_tunnel_ip) . '&inport=' . $port . '&key=' . urlencode($api_key);
        $forward_request = getURL($url);
        DebMes($url . "\n" . $forward_request, 'vpnki');
        $forward_request_data = json_decode($forward_request, true);
        if (!$forward_request_data['address']) {
            $out['ERR_MSG'] .= '<br/>Error: ' . $forward_request_data['error'];
        }
        $ssh_address = $forward_request_data['address'];
        $ssh_port = $forward_request_data['port'];
        if ($ssh_address && $ssh_port) {
            $this->config['LATEST_SSH_ADDRESS'] = $ssh_address;
            $this->config['LATEST_SSH_PORT'] = $ssh_port;
        } else {
            $this->config['LATEST_SSH_ADDRESS'] = '';
            $this->config['LATEST_SSH_PORT'] = '';
        }
        $this->saveConfig();
        */

        if ($this->config['LATEST_HTTP_ADDRESS'] || $this->config['LATEST_SSH_ADDRESS']) {
            $this->redirect("?mode=connect_saved");
        }
    }

    function connect($saved = 0)
    {

        if ($saved) {
            $username = $this->config['LATEST_CONNECT_USERNAME'];
            $password = $this->config['LATEST_CONNECT_PASSWORD'];
            $server = $this->config['API_URL'];
            $type = $this->config['API_TYPE'];
            $token = $this->config['API_TOKEN'];
        } else {
            $username = $this->config['API_USERNAME'];
            $password = $this->config['API_PASSWORD'];
            $server = $this->config['API_URL'];
            $type = $this->config['API_TYPE'];
        }

        if (!$server) {
            $server = 'vpnki.ru';
        }

        if ($type=='' || $type=='pptp') {
            $cmd = 'sudo pptpsetup --delete vpnki';
            debmes($cmd, 'vpnki');
            safe_exec($cmd);
            $cmd = 'sudo pptpsetup --create vpnki --server ' . $server . ' --username ' . $username . ' --password ' . $password;
            debmes($cmd, 'vpnki');
            safe_exec($cmd);
            sleep(1);
            $cmd = 'sudo pon vpnki updetach';
            debmes($cmd, 'vpnki');
            safe_exec($cmd);
            $cmd = 'sudo ip route add 172.16.0.0/16 via 172.16.0.1';
            debmes($cmd, 'vpnki');
            setTimeout('ip_route_add', "safe_exec('$cmd');", 3);
        } elseif ($type=='openvpn') {
            //$url="https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=ovpn&action=add&key=$key";
            if ($token=='') {
                dprint("Token is not set");
                return false;
            }
            $url="https://vpnki.ru/index.php?option=com_api&format=raw&app=webservices&resource=token&key=partner&token=$token&action=ovpn";
            $ovpn=getURL($url);
            $object=json_decode($ovpn);

            $conf_path=__DIR__.'/../../cached';

            if (is_object($object)) {
                $file_content=$object->file_content;
                SaveFile($conf_path.'/vpnki.conf',$file_content.PHP_EOL);
            } else {
                dprint("cannot get openvpn data: ".$ovpn);
                return false;
            }

            $text = <<<TEXT
$username
$password
TEXT;
            SaveFile($conf_path.'/vpnki_login',$text.PHP_EOL);

            $text = <<<TEXT
auth-user-pass $conf_path/vpnki_login
<ca>
TEXT;
            $str = LoadFile($conf_path.'/vpnki.conf');
            $oldMessage = "<ca>";

            function lreplace($search, $replace, $subject){
                $pos = strrpos($subject, $search);
                if($pos !== false){
                    $subject = substr_replace($subject, $replace, $pos, strlen($search));
                }
                return $subject;
            }
            $str = lreplace($oldMessage, $text, $str);
            SaveFile($conf_path.'/vpnki.conf',$str);

            $cmd = "sudo killall openvpn";
            debmes($cmd, 'vpnki');
            safe_exec($cmd);
            $cmd="sudo openvpn --config ".$conf_path."/vpnki.conf --daemon";
            debmes($cmd, 'vpnki');
            safe_exec($cmd);
            //dprint('OpenVPN connection is under construction...',false);
            //dprint($str);
        }


    }

    function disconnect()
    {
        safe_exec('sudo killall openvpn');
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
