<?php
require_once 'modules/admin/models/DashboardPlugin.php';
require_once 'modules/admin/models/ServerGateway.php';

class PluginPhpsysinfoDashboard extends DashboardPlugin
{
    var $name;
    var $smallName;

    var $hasTab = true;
    var $hasPanel = true;

    var $description;
    var $default = false;
    var $sidebarPlugin = true;

    protected $featureSet = 'products';

    function __construct($user, $typeOfFetch = 1) {
        $this->name = lang("Server Info");
        $this->smallName = lang("Server");
        $this->description = lang("This shows your current server status based off the phpsysinfo plugin.");
        parent::__construct($user,$typeOfFetch);
    }

    function getPanel()
    {
        // dependent on the snapin plugin
        include_once 'plugins/snapin/phpsysinfo/PluginPhpsysinfo.php';

        $phpsysinfo  = null;
        if (class_exists('PluginPhpsysinfo')) {
            $phpsysinfo = new PluginPhpsysinfo($this->user,1);
        }

        $serverGateway = new ServerGateway();
        $result = $serverGateway->getStatsUrlDBIT($this->user);
        $workingServerFound = false;
        $this->view->ServerData = array();
        while (($row = $result->fetch()) && $phpsysinfo !== null) {

            $data = $phpsysinfo->getDashboardData($row['statsurl']);
            if (is_a($data, 'CE_Error')) continue;
            $workingServerFound = true;

            $serverData =array(
                'SERVERSTATUS_FUSE'         => 'admin',
                'SERVERSTATUS_ACTION'       => 'viewsnapin',
                'SERVERSTATUS_SERVERID'     => $row['id'],
                'SERVERSTATUS_SERVERNAME'   => $row['name'],
                'SERVERSTATUS_LOADAVERAGES' => $data['loadAverages'],
                'SERVERSTATUS_UPTIME'       => $data['uptime'],
                'SERVERSTATUS_MEMORYUSED'   => $data['memoryUsedPercent'],
                'SERVERSTATUS_MEMORYCACHED' => $data['memoryCached'],
            );

            $serverData['SERVERSTATUS_MOUNTSUSED'] = "Not applicable";
            $numMounts = count($data['mounts']);
            for ($i = 0; $i < $numMounts; $i++) {
                if ($i == 0) {
                    $serverData['SERVERSTATUS_MOUNTSUSED'] =
                                 $phpsysinfo->_getMountPoint($data['mounts'][$i])
                                 .': '.$phpsysinfo->_getMountPercent($data['mounts'][$i]).'%';
                } else {
                    $serverData['SERVERSTATUS_MOUNTSUSED'] =
                                 $phpsysinfo->_getMountPoint($data['mounts'][$i])
                                 .': '.$phpsysinfo->_getMountPercent($data['mounts'][$i]).'%';
                }
                if ($i < ($numMounts - 1)) {
                    $serverData['SERVERSTATUS_MOUNTSUSED'] =  ',  ';
                }
            }

            $this->view->ServerData[] = $serverData;
        }

        $this->view->showNoServers = false;
        if (!$result->getNumRows() || !$workingServerFound) {
            $this->view->showNoServers = true;
        }

        return parent::getPanel();

    }

}

?>
