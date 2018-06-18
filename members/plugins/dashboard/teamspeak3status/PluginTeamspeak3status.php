<?php
require_once 'modules/admin/models/DashboardPlugin.php';
require_once 'library/CE/NE_PluginCollection.php';
require_once 'modules/admin/models/server.php';

class PluginTeamspeak3status extends DashboardPlugin
{

    var $name;
    var $smallName;

    var $description;
    var $default = false;
    var $sidebarPlugin = true;

    protected $featureSet = 'products';

    function __construct($user, $typeOfFetch = 1) {
        $this->name = lang("Teamspeak 3 Status");
        $this->smallName = lang("Teamspeak");
        $this->description = lang("This plugin adds a dashboard panel which lists the current status of all teamspeak 3 servers.  Data shown includes server uptime, number of servers, number of clients online and total number of clients allowed.");
        parent::__construct($user,$typeOfFetch);
    }

    function getPanel()
    {
        $query = "SELECT id, name, hostname FROM server WHERE plugin='teamspeak3'";
        $result = $this->db->query($query);

        $this->view->servers = array();
        while ($row = $result->fetch()) {
      $server = new Server($row['id']);
      $params = $server->getAllServerPluginVariables($this->user, 'teamspeak3');
      $status = $this->getStatus($row['hostname'], 10011, $params['plugin_teamspeak3_Username'], $params['plugin_teamspeak3_Password']);
      $status['server_name'] = $row['name'];
      $this->view->servers[] = $status;
        }

        return parent::getPanel();
    }

    function getStatus($host, $port, $sq_username, $sq_password)
    {
        $statusInfo = array(
            'error'             => '',
            'uptime'            => 0,
            'servers_online'    => 0,
            'clients_max'       => 0,
            'clients_online'    => 0,
        );

        $conn = $this->connect($host, $port);
        $this->callCommand('login client_login_name='.$sq_username.' client_login_password='.$sq_password, $conn);
        $result = $this->callCommand("hostinfo", $conn);
        if (is_a($result, 'CE_Error')) {
            $statusInfo['error'] = $result->getMessage();
        } else {
            if (isset($result['msg']) && $result['msg'] != 'ok') {
                $statusInfo['error'] = $result['msg'];
            } else {
                if (isset($result['instance_uptime']))
                    $statusInfo['uptime'] = $this->timeformat($result['instance_uptime']);

                if (isset($result['virtualservers_running_total']))
                    $statusInfo['servers_online'] = $result['virtualservers_running_total'];

                if (isset($result['virtualservers_total_maxclients']))
                    $statusInfo['clients_max'] = $result['virtualservers_total_maxclients'];

                if (isset($result['virtualservers_total_clients_online']))
                    $statusInfo['clients_online'] = $result['virtualservers_total_clients_online'];
            }
        }
        return $statusInfo;
    }

    function timeformat($msecs)
    {
        $mins = 0;
        $hours = 0;
        $days = 0;
        $years = 0;

        if ($msecs == 0) return 'Offline';

        $secs = ceil($msecs / 1000);

        if ($secs >= 60) {
            $mins = floor($secs / 60);
            $secs = $secs % 60;
        }
        if ($mins >= 60) {
            $hours = floor($mins / 60);
            $mins = $mins % 60;
        }
        if ($hours >= 24) {
            $days = floor($hours / 24);
            $hours = $hours % 24;
        }
        if ($days >= 365) {
            $years = floor($days / 365);
            $days = $days % 365;
        }
        $uptime = "";
        if ($years != 1 && $years != 0) $uptime .= $years." years ";
        elseif ($uptime != "") $uptime .= $years." year ";
        if ($days != 1 && $days != 0) $uptime .= $days." days ";
        elseif ($days != 0 || $uptime != "") $uptime .= $days." day ";
        if ($hours != 1) $uptime .= $hours." hours ";
        elseif ($hours != 0 || $uptime != "") $uptime .= $hours." hour ";
        if ($mins != 1) $uptime .= $mins." mins ";
        else $uptime .= $mins." min ";
        if ($secs != 1) $uptime .= $secs." secs ";
        else $uptime .= $secs." sec";
        return $uptime;
  }

    function connect($host, $port)
    {
        // Connect to the server and check that it was successful
        $conn = @fsockopen($host, $port);
        if (!$conn) {
            throw new Exception('Connection failed. ERROR: Cannot connect to server('.$return.')');
        }

        $return = fgets($conn, 10);
        if (strpos($return, "TS3") === false)
        {
            fclose($conn);
            throw new Exception('Welcome message missing. ERROR: Cannot connect to server('.$return.')');
        }

        return $conn;
    }

    function callCommand($command, $conn)
    {
        fputs($conn, $command . "\n");
        $result = array();
        $char = '';
        $token = '';
        while (($char = fgetc($conn)) !== false) {
            // End of token
            if (trim($char) == '' && $token != 'error') {
                $pieces = explode('=', $token);
                if (sizeof($pieces) == 2) {
                    $result[$pieces[0]] = $pieces[1];
                }
                $token = '';
                if (isset($result['msg'])) break;
            } else {
                $token .= $char;
            }
        }
        return $result;
    }
}

