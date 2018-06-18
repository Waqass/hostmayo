<?php
require_once 'modules/admin/models/SnapinPlugin.php';
require_once 'modules/clients/models/UserPackageGateway.php';

class PluginPhpsysinfo extends SnapinPlugin
{
    public $title = 'Server Stats';
    public $phpsysinfo_ver = "old";

    public function init()
    {
        $this->settingsNotes = lang('When enabled this snapin allows your customers to see server information.');
        $this->addMappingForPublicMain("view", "View Server Details", 'Integrate PHPSysInfo in Public Home', 'icon-sitemap', 'margin: 2px;');
        $this->addMappingForTopMenu('public', '', 'view', 'Server Info', 'Integrate PHPSysInfo in Public Top Menu');
    }


    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')       => array(
                'type'        => 'hidden',
                'description' => '',
                'value'       => 'Server Info',
            ),
            'Public Description'       => array(
                'type'        => 'hidden',
                'description' => 'Description to be seen by public',
                'value'       => 'View Server Details',
            ),
            'Public Icon'       => array(
                'type'        => 'hidden',
                'description' => 'Custom Icon',
                'value'       => 'icon-sitemap',
            ),
            'Public Icon Style'       => array(
                'type'        => 'hidden',
                'description' => 'Custom margin based on Icon we are using',
                'value'       => 'margin: 2px;',
            ),
        );

        return $variables;
    }



    function view()
    {
        if (isset($_GET['pluginaction'])) {
            $this->view->serverId = filter_var($_GET['serverid'], FILTER_SANITIZE_NUMBER_INT);

            //$this->view->serverId = $this->getParam('serverid', FILTER_SANITIZE_NUMBER_INT);
            switch ($_GET['pluginaction']) {
                case 'showserver':
                    $this->showServer();
                    break;
            }
        }

        $this->showServerDropDown();
        return $this->view->render('view.phtml');
    }

    function showServerDropDown()
    {
        $sql = "SELECT s.id, s.name FROM server s WHERE statsurl != '' ORDER BY s.name";
        $result = $this->db->query($sql);

        $this->view->servers = array();
        while (list($serverid, $servername) = $result->fetch())
        {
            if (isset($this->view->serverId) && $this->view->serverId == $serverid) {
                $selected = "SELECTED";
            } else {
                $selected = "";
            }
            $server = array();
            $server['selected'] = $selected;
            $server['id'] = $serverid;
            $server['name'] = $servername;
            if ( NE_ADMIN ) {
                $server['view'] = 'viewsnapin';
            } else {
                $server['view'] = 'snapin';
            }
            $this->view->servers[] = $server;
        }
    }

    function showServer()
    {
        $sql = "SELECT s.statsurl, s.name, s.id FROM server s WHERE s.id=?";
        $result = $this->db->query($sql, $this->view->serverId);
        list($statsurl, $servername, $serverid) = $result->fetch();

        $xml = $this->_getXML($statsurl);
        if (is_a($xml, 'NE_Error')) {
            $this->view->error = $xml->getMessage();
        } else {
            $this->_SetVitals($xml);
            $this->_SetHardware($xml);
            $this->_NetworkUsage($xml);
            $this->_MemoryUsage($xml);
            $this->_FileSystem($xml);
        }
    }

    function getDashboardData($statsurl)
    {
        $xml = $this->_getXML($statsurl);
        if (is_a($xml, 'NE_Error')) return $xml;
        return array(
            'loadAverages'      => $this->_getLoadAverages($xml),
            'uptime'            => $this->_getUptime($xml),
            'memoryUsedPercent' => $this->_getMemUsedPercent($xml),
            'memoryCached'      => $this->_getCachedPercent($xml),
            'mounts'            => $this->_getMounts($xml),
        );
    }

    function getPublicOutput()
    {
        require_once 'modules/admin/models/ServerGateway.php';
        $serverGateway = new ServerGateway();
        $result = $serverGateway->getStatsUrlDBIT($this->user, true);
        $output = '';
        while ($row = $result->fetch()) {
            $xml = $this->_getXML($row['statsurl']);
            if (is_a($xml, 'NE_Error')) continue;
            $output .= '<li><h4 style="font-weight:bold">Server '.$row['name'].'</h4><p><b>Load Average: </b>'.$this->_getLoadAverages($xml).'<br /><b>Uptime: </b>'.$this->_getUptime($xml).'</p></li>';
        }

        if (!$output) {
            return false;
        }

        return "<ul>$output</ul>";
    }

    function _getXML($statsurl)
    {
        require_once 'library/CE/XmlFunctions.php';
        require_once 'library/CE/NE_Network.php';

        $xmldata = NE_Network::curlRequest($this->settings, $statsurl, false, false, true, false);

        if (is_a($xmldata, 'NE_Error')) return $xmldata;

        //need to validate XML before we do anything so that we do not
        //get unexpected errors when using this xmlize function
        $xml = XmlFunctions::xmlize($xmldata,1);

        if(array_key_exists("tns:phpsysinfo", $xml)){
            $this->phpsysinfo_ver = "3.x";
        }else{
            $this->phpsysinfo_ver = "old";
        }

        return $xml;
    }

    function _MemoryUsage($xml)
    {
        if($this->phpsysinfo_ver == "3.x"){

            //Getting Physical Memory
            //we get these in kilobytes so we convert to byte before passing to this function
            $memfree = $this->_ConvertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["@"]["Free"]),"");
            $memtotal = $this->_ConvertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["@"]["Total"]),"");

            //Get Kernal + Application
            //we get these in kilobytes so we convert to byte before passing to this function
            $kernused = $this->_ConvertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Details"][0]["@"]["App"]),"");
            $kernpercent = $xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Details"][0]["@"]["AppPercent"];

            //Get Buffers
            //we get these in kilobytes so we convert to byte before passing to this function
            $buffused = $this->_ConvertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Details"][0]["@"]["Buffers"]),"");
            $buffpercent = $xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Details"][0]["@"]["BuffersPercent"];

            //Get Cached
            //we get these in kilobytes so we convert to byte before passing to this function
            $cachedused = $this->_ConvertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Details"][0]["@"]["Cached"]),"");

            // Get Disk Swap
            //we get these in kilobytes so we convert to byte before passing to this function
            // some systems don't show swap info so gotta make sure
            if ((isset($xml['tns:phpsysinfo']["#"]["Memory"][0]["#"]["Swap"][0]['@']['Used']))) {
                $swapused = $this->_ConvertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Swap"][0]["@"]["Used"]),"");
            } else {
                $swapused = $this->user->lang('NA');
            }
            if ((isset($xml['tns:phpsysinfo']["#"]["Memory"][0]["#"]["Swap"][0]['#']['Free']))) {
                $swapfree = $this->_ConvertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Swap"][0]["#"]["Free"]),"");
            } else {
                $swapfree = $this->user->lang('NA');
            }
            if ((isset($xml['tns:phpsysinfo']["#"]["Memory"][0]["#"]["Swap"][0]['#']['Total']))) {
                $swaptotal = $this->_ConvertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Swap"][0]["#"]["Total"]),"");
            } else {
                $swaptotal = $this->user->lang('NA');
            }
            if ((isset($xml['tns:phpsysinfo']["#"]["Memory"][0]["#"]["Swap"][0]['#']['Percent']))) {
                $swappercent = $xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Swap"][0]["#"]["Percent"];
            } else {
                $swappercent = $this->user->lang('NA');
            }

        }else{
            //Getting Physical Memory
            //we get these in kilobytes so we convert to byte before passing to this function
            $memfree = $this->_ConvertByte(floatval($xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Free"][0]["#"])*1024,"");
            $memtotal = $this->_ConvertByte(floatval($xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Total"][0]["#"])*1024,"");

            //Get Kernal + Application
            //we get these in kilobytes so we convert to byte before passing to this function
            $kernused = $this->_ConvertByte(floatval($xml["phpsysinfo"]["#"]["Memory"][0]["#"]["App"][0]["#"])*1024,"");
            $kernpercent = $xml["phpsysinfo"]["#"]["Memory"][0]["#"]["AppPercent"][0]["#"];

            //Get Buffers
            //we get these in kilobytes so we convert to byte before passing to this function
            $buffused = $this->_ConvertByte(floatval($xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Buffers"][0]["#"])*1024,"");
            $buffpercent = $xml["phpsysinfo"]["#"]["Memory"][0]["#"]["BuffersPercent"][0]["#"];

            //Get Cached
            //we get these in kilobytes so we convert to byte before passing to this function
            $cachedused = $this->_ConvertByte(floatval($xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Cached"][0]["#"])*1024,"");

            // Get Disk Swap
            //we get these in kilobytes so we convert to byte before passing to this function
            // some systems don't show swap info so gotta make sure
            if ((isset($xml['phpsysinfo']["#"]["Swap"][0]['#']['Used']))) {
                $swapused = $this->_ConvertByte(floatval($xml["phpsysinfo"]["#"]["Swap"][0]["#"]["Used"][0]["#"])*1024,"");
            } else {
                $swapused = $this->user->lang('NA');
            }
            if ((isset($xml['phpsysinfo']["#"]["Swap"][0]['#']['Free']))) {
                $swapfree = $this->_ConvertByte(floatval($xml["phpsysinfo"]["#"]["Swap"][0]["#"]["Free"][0]["#"])*1024,"");
            } else {
                $swapfree = $this->user->lang('NA');
            }
            if ((isset($xml['phpsysinfo']["#"]["Swap"][0]['#']['Total']))) {
                $swaptotal = $this->_ConvertByte(floatval($xml["phpsysinfo"]["#"]["Swap"][0]["#"]["Total"][0]["#"])*1024,"");
            } else {
                $swaptotal = $this->user->lang('NA');
            }
            if ((isset($xml['phpsysinfo']["#"]["Swap"][0]['#']['Percent']))) {
                $swappercent = $xml["phpsysinfo"]["#"]["Swap"][0]["#"]["Percent"][0]["#"];
            } else {
                $swappercent = $this->user->lang('NA');
            }
        }

        //Set Vitals
        $this->view->assign(array(
            'SYSINFO_MEMFREE'       => $memfree,
            'SYSINFO_MEMUSED'       => $this->_getMemUsed($xml),
            'SYSINFO_MEMTOTAL'      => $memtotal,
            'SYSINFO_MEMPERCENT'    => $this->bar($this->_getMemUsedPercent($xml),100),
            'SYSINFO_KERNUSED'      => $kernused,
            'SYSINFO_KERNPERCENT'   => $this->bar($kernpercent,100),
            'SYSINFO_BUFFUSED'      => $buffused,
            'SYSINFO_BUFFPERCENT'   => $this->bar($buffpercent,100),
            'SYSINFO_CACHUSED'      => $cachedused,
            'SYSINFO_CACHPERCENT'   => $this->bar($this->_getCachedPercent($xml),100),
            'SYSINFO_SWAPFREE'      => $swapfree,
            'SYSINFO_SWAPUSED'      => $swapused,
            'SYSINFO_SWAPTOTAL'     => $swaptotal,
            'SYSINFO_SWAPPERCENT'   => $this->bar($swappercent,100),
        ));
    }

    function _getMemUsed(&$xml)
    {
        if($this->phpsysinfo_ver == "3.x"){
            return $this->_ConvertByte(floatval($xml["tns:phpsysinfo"]["#"]["Memory"][0]["@"]["Used"]),"");
        }else{
            return $this->_ConvertByte(floatval($xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Used"][0]["#"])*1024,"");
        }

    }

    function _getMemUsedPercent(&$xml)
    {
        if($this->phpsysinfo_ver == "3.x"){
            return $xml["tns:phpsysinfo"]["#"]["Memory"][0]["@"]["Percent"];
        }else{
            return $xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Percent"][0]["#"];
        }

    }

    function _getCachedPercent(&$xml)
    {
        if($this->phpsysinfo_ver == "3.x"){
            return $xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]["Details"][0]["@"]["CachedPercent"];
        }else{
            return $xml["phpsysinfo"]["#"]["Memory"][0]["#"]["CachedPercent"][0]["#"];
        }

    }

    function _FileSystem($xml)
    {
        $this->view->mounts = array();
        $mounts = $this->_getMounts($xml);

        for($i = 0; $i < sizeof($mounts); $i++) {
            $mount = $mounts[$i];
            if($this->phpsysinfo_ver == "3.x"){
                $mounttype  = $mount["@"]["FSType"];
                $mountpart = $mount["@"]["Name"];

                //we get these in kilobytes so we convert to byte before passing to this function
                $mountfree = $this->_ConvertByte(floatval($mount["@"]["Free"]),"0.00 KB");
                $mountused = $this->_ConvertByte(floatval($mount["@"]["Used"]),"0.00 KB");
                $mountsize = $this->_ConvertByte(floatval($mount["@"]["Total"]),"0.00 KB");

            }else{
                $mounttype  = $mount["#"]["Type"][0]["#"];
				if ( is_array($mount["#"]["Device"][0]["#"]) ) {
					$mountpart = $mount["#"]["Device"][0]["#"]['Name'][0]['#'];
				} else {
					$mountpart = $mount["#"]["Device"][0]["#"];
				}

                //we get these in kilobytes so we convert to byte before passing to this function
                $mountfree = $this->_ConvertByte(floatval($mount["#"]["Free"][0]["#"])*1024,"0.00 KB");
                $mountused = $this->_ConvertByte(floatval($mount["#"]["Used"][0]["#"])*1024,"0.00 KB");
                $mountsize = $this->_ConvertByte(floatval($mount["#"]["Size"][0]["#"])*1024,"0.00 KB");

            }



            $mountArray = array(
                'SYSINFO_MOUNTPOINT'        => $this->_getMountPoint($mount),
                'SYSINFO_MOUNTTYPE'         => $mounttype,
                'SYSINFO_MOUNTPARTITION'    => $mountpart,
                'SYSINFO_MOUNTPERCENT'      => $this->bar($this->_getMountPercent($mount),100),
                'SYSINFO_MOUNTFREE'         => $mountfree,
                'SYSINFO_MOUNTUSED'         => $mountused,
                'SYSINFO_MOUNTSIZE'         => $mountsize
            );
            $this->view->mounts[] = $mountArray;

        }
    }

    function _getMounts(&$xml)
    {
        if($this->phpsysinfo_ver == "3.x"){
            if (!isset($xml["tns:phpsysinfo"]["#"]["FileSystem"][0]["#"]["Mount"])) {
                return array();
            }
            return $xml["tns:phpsysinfo"]["#"]["FileSystem"][0]["#"]["Mount"];
        }else{
            if (!isset($xml["phpsysinfo"]["#"]["FileSystem"][0]["#"]["Mount"])) {
                return array();
            }

            return $xml["phpsysinfo"]["#"]["FileSystem"][0]["#"]["Mount"];
        }

    }

    function _getMountPoint($mount)
    {
        if($this->phpsysinfo_ver == "3.x"){
            return $mount["@"]["MountPoint"];
        }else{
            return $mount["#"]["MountPoint"][0]["#"];
        }

    }

    function _getMountPercent($mount)
    {
        if($this->phpsysinfo_ver == "3.x"){
            return $mount["@"]["Percent"];
        }else{
            return $mount["#"]["Percent"][0]["#"];
        }

    }

    function _NetworkUsage($xml)
    {
        if($this->phpsysinfo_ver == "3.x"){
			if ( @array_key_exists('NetDevice', $xml["tns:phpsysinfo"]["#"]["Network"][0]["#"]) )
			{
				$netdevices = $xml["tns:phpsysinfo"]["#"]["Network"][0]["#"]["NetDevice"];

				for($i = 0; $i < sizeof($netdevices); $i++) {

					$netdevice = $netdevices[$i];

					$name   = $netdevice["@"]["Name"];
					$rxbytes    = $this->_ConvertByte($netdevice["@"]["RxBytes"],"0.00 KB");
					$txbytes    = $this->_ConvertByte($netdevice["@"]["TxBytes"],"0.00 KB");
					$errors     = $netdevice["@"]["Err"];
					$drops  = $netdevice["@"]["Drops"];

					$this->view->assign(array(
						'SYSINFO_DEVICENAME' => $name,
						'SYSINFO_DEVICERECEIVED'   => $rxbytes,
						'SYSINFO_DEVICESENT' => $txbytes,
						'SYSINFO_DEVICEERR'   => $errors,
						'SYSINFO_DEVICEDROP'   => $drops,
					));
				}
				if ( sizeof($netdevices) > 0 ) {
                    // Only show network block if we aren't in public dir.
                    if ( !NE_PUBLIC ) {
        				//$this->tpl->parse('networkblock_out', 'networkblock');
                    }
                }
            }
        }else{
			if ( @array_key_exists('NetDevice', $xml["phpsysinfo"]["#"]["Network"][0]["#"]) ) {
				$netdevices = $xml["phpsysinfo"]["#"]["Network"][0]["#"]["NetDevice"];
				for($i = 0; $i < sizeof($netdevices); $i++) {

					$netdevice = $netdevices[$i];
					$name   = $netdevice["#"]["Name"][0]["#"];
					$rxbytes    = $this->_ConvertByte($netdevice["#"]["RxBytes"][0]["#"],"0.00 KB");
					$txbytes    = $this->_ConvertByte($netdevice["#"]["TxBytes"][0]["#"],"0.00 KB");
					if ( array_key_exists('Err', $netdevice["#"]) ) {
						$errors = $netdevice["#"]["Err"][0]["#"];
					} else {
						$errors = $netdevice["#"]["Errors"][0]["#"];
					}
					$drops  = $netdevice["#"]["Drops"][0]["#"];

					$this->view->assign(array(
						'SYSINFO_DEVICENAME' => $name,
						'SYSINFO_DEVICERECEIVED'   => $rxbytes,
						'SYSINFO_DEVICESENT' => $txbytes,
						'SYSINFO_DEVICEERR'   => $errors,
						'SYSINFO_DEVICEDROP'   => $drops,
					));

				    if ( sizeof($netdevices) > 0 ) {
					   // Only show network block if we aren't in public dir.
					   if ( !NE_PUBLIC ) {
						  //$this->tpl->parse('networkblock_out', 'networkblock');
					   }
                    }
				}
			}
		}
	}

    function _SetHardware($xml)
    {
        if($this->phpsysinfo_ver == "3.x"){
           //Getting Server Information
            $processors = sizeof($xml["tns:phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["CpuCore"]);
            $cpumodel = $xml["tns:phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["CpuCore"][0]["@"]["Model"];
            $cpuspeed = round(floatval($xml["tns:phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["CpuCore"][0]["@"]["CpuSpeed"])/1000,2);
            if (isset($xml["tns:phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["CpuCore"][0]["@"]["Cache"])) {
                $cpucache = $xml["tns:phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["CpuCore"][0]["@"]["Cache"];
                $cpucache = $this->_ConvertByte(floatval($cpucache),"0.00 KB");
            } else {
                $cpucache = 'unavailable';
            }
        }else{
            $processors = $xml["phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["Number"][0]["#"];
            $cpumodel = $xml["phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["Model"][0]["#"];
            $cpuspeed = round(floatval($xml["phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["Cpuspeed"][0]["#"])/1000,2);
            if (isset($xml["phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["Cache"][0]["#"])) {
                $cpucache = $xml["phpsysinfo"]["#"]["Hardware"][0]["#"]["CPU"][0]["#"]["Cache"][0]["#"];
                $cpucache = $this->_ConvertByte(floatval($cpucache)*1024,"0.00 KB");
            } else {
                $cpucache = 'unavailable';
            }
        }



        if($cpuspeed > 0.00) $cpuspeed = $cpuspeed." GHz";


        //Set Vitals
        $this->view->assign(array(
            'SYSINFO_PROCESSORS' => $processors,
            'SYSINFO_CPUMODEL' => $cpumodel,
            'SYSINFO_CPUSPEED' => $cpuspeed,
            'SYSINFO_CPUCACHESIZE' => $cpucache,
        ));

    }

    function _SetVitals($xml)
    {
        if($this->phpsysinfo_ver == "3.x"){
            //Getting Vitals
            $hostname = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["Hostname"];
            $ipaddress = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["IPAddr"];
            $per_cpuload = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["LoadAvg"];
            $distroname = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["Distro"];
            $kernalver = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["Kernel"];
            $currentusers = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["Users"];
        }else{
            //Getting Vitals
            $hostname = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["Hostname"][0]["#"];
            $ipaddress = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["IPAddr"][0]["#"];
            $per_cpuload = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["LoadAvg"][0]["#"];
            $distroname = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["Distro"][0]["#"];
            $kernalver = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["Kernel"][0]["#"];
            $currentusers = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["Users"][0]["#"];
        }


        if ($per_cpuload > 0.00){
            $per_cpuload = $this->bar($per_cpuload,50);
        }else{
            $per_cpuload = "";
        }

        //Set Vitals
        $this->view->assign(array(
            'SYSINFO_HOSTNAME'      => $hostname,
            'SYSINFO_LISTENINGIP'   => $ipaddress,
            'SYSINFO_UPTIME'        => $this->_getUptime($xml),
            'SYSINFO_LOADAVERAGE'   => $this->_getLoadAverages($xml),
            'SYSINFO_CPULOAD'       => $per_cpuload,
            'SYSINFO_DISTRONAME'    => $distroname,
            'SYSINFO_KERNALVER'     => $kernalver,
            'SYSINFO_CURRENTUSERS'  => $currentusers,
        ));

    }

    function _getLoadAverages(&$xml)
    {
        if($this->phpsysinfo_ver == "3.x"){
            return $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["LoadAvg"];
        }else{
            return $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["LoadAvg"][0]["#"];
        }

    }

    function _getUptime(&$xml)
    {
        if($this->phpsysinfo_ver == "3.x"){

            return $this->_parseUptime($xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["Uptime"]);
        }else{
            return $this->_parseUptime($xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["Uptime"][0]["#"]);
        }

    }

    //Make Human Readable
    function _ConvertByte($bytes,$emptyStr)
    {

        /*
        1 KiloByte == 1024 bytes
        1 MegaByte == 1024 KiloByte
        1 GigaByte == 1024 MegaByte
        */

         $amount = $bytes / 1024;
         $symbol = "KB";

         if ($amount > 1024){
            $symbol = "MB";
            $amount = $amount / 1024;
         }

         if ($amount > 1024){
            $symbol = "GB";
            $amount = $amount / 1024;
         }

        $returnStr = ($amount == 0) ? $emptyStr : sprintf("%01.2f", round($amount,2))." ".$symbol;
        return $returnStr;
    }

    function _parseUptime($tuptime)
    {
         $uptimeString = "";
         $secs = floor($tuptime) % 60;
         $mins = floor($tuptime / 60) % 60;
         $hours = floor($tuptime / 3600) % 24;
         $days = floor($tuptime / 86400);

         if ($days > 0) {
           $uptimeString .= $days;
           $uptimeString .= ($days == 1) ? " day " : " days ";
         }

         if ($hours > 0) {
           $uptimeString .= $hours;
           $uptimeString .= ($hours == 1) ? " hour " : " hours ";
         }

         if ($mins > 0) {
           $uptimeString .= $mins;
           $uptimeString .= ($mins == 1) ? " minute " : " minutes ";
         }

         $uptimeString .= $secs;
         $uptimeString .= ($secs == 1) ? " second " : " seconds ";

         return $uptimeString;
    }

    function bar($value, $width) {

        $relative = '';
        if (!NE_PUBLIC) {
            $relative = '../';
        }
        $prefix = "blue";
        if ($value > 100) $value = 100;
        if ($value < 0) $value = 0;
        $left = 100 - $value;
        if ($left <= 10) $prefix = "red";
        $strHTML = "<!-- progress bar -->";
        $strHTML .= "<table width=\"$width%\">";
        $strHTML .= "<tr><td width=\"100%\">";
        $strHTML .= "<table width=\"100%\" height=\"14\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" background={$relative}plugins/snapin/phpsysinfo/images/$prefix-back.gif>";
        $strHTML .= "<tr>";
        $strHTML .= "<td width=\"3\" align=\"left\"><img src={$relative}plugins/snapin/phpsysinfo/images/$prefix-start.gif></td>";
        if ($value != "0") $strHTML .= "<td width=\"$value%\" background={$relative}plugins/snapin/phpsysinfo/images/$prefix-active.gif><img src={$relative}plugins/snapin/phpsysinfo/images/zero.gif width=\"1\" height=\"1\"></td>";
        if ($value != "100" && $value != "0") $strHTML .= "<td width=\"4\"><img src={$relative}plugins/snapin/phpsysinfo/images/$prefix-divider.gif></td>";
        if ($value != "100") $strHTML .= "<td width=\"$left%\"><img src={$relative}plugins/snapin/phpsysinfo/images/zero.gif width=\"1\" height=\"1\"></td>";
        if ($value != "100" && $value != "0") $strHTML .= "<td width=\"3\" align=\"right\"><img src={$relative}plugins/snapin/phpsysinfo/images/$prefix-end.gif></td>";
        if ($value == "0" || $value == "100") $strHTML .= "<td width=\"2\" align=\"right\"><img src={$relative}plugins/snapin/phpsysinfo/images/$prefix-zeroend.gif></td>";
        $strHTML .= "</tr>";
        $strHTML .= "</table>";
        $strHTML .= "</td><td width=\"1\" class=\"text\">$value%</td></tr>";
        $strHTML .= "</table>";
        $strHTML .= "<!-- //progress bar -->";

        return $strHTML;
    }
}
