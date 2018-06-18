<?php

require_once 'library/CE/NE_MailGateway.php';
require_once 'modules/admin/models/ServicePlugin.php';

/**
* @package Plugins
*/
class PluginServerstatus extends ServicePlugin
{
    protected $featureSet = 'products';
    public $hasPendingItems = false;

    var $phpsysinfo_ver = "old";
    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Server Status'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled and server plugin has server stats script URL, ClientExec will notify you if the server is not responding or when certain thresholds are met.'),
                'value'         => '0',
            ),
            lang('Admin E-mail')     => array(
                'type'          => 'textarea',
                'description'   => lang('E-mails that will be E-mailed when thresholds are passed. Enter each E-mail in a separate line.'),
                'value'         => '',
            ),
            lang('1 Min. Load Average')     => array(
                'type'          => 'text',
                'description'   => lang('Add 1 Minute server load average threshold you want this service to E-mail if passed.<br/>Ex: 1.5, will E-mail when load goes over 1.5'),
                'value'         => '',
            ),
            lang('5 Min. Load Average')     => array(
                'type'          => 'text',
                'description'   => lang('Add 5 Minute server load average threshold you want this service to E-mail if passed.<br/>Ex: 1.5, will E-mail when load goes over 1.5'),
                'value'         => '',
            ),
            lang('Used Physical Memory')     => array(
                'type'          => 'text',
                'description'   => lang('Add used physical memory threshold in megabytes (MB) you want this service to E-mail if passed. Current cache use will be subtracted from the Used Physical Memory on the server.<br/>Ex: 512, will E-mail when memory goes over 512 MB'),
                'value'         => '',
            ),
            lang('Server Restarted')  => array(
                'type'          => 'yesno',
                'description'   => lang('Notify if the server was restarted since the last check.'),
                'value'         => '0',
            ),
            lang('Mount Space Available')     => array(
                'type'          => 'text',
                'description'   => lang('Add mount and percentage threshold that you want this service to E-mail you on. Use ; as separator if you want to monitor more than one mount.<br/>Ex: /home,75;/tmp,50'),
                'value'         => '',
            ),
            lang('Ignore Mount Point Errors')  => array(
                'type'          => 'yesno',
                'description'   => lang('Enabling this option will cause Clientexec not to error if it can not find a mount point'),
                'value'         => '0',
            ),
            lang('Run schedule - Minute')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
                'helpid'        => '8',
            ),
            lang('Run schedule - Hour')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
            ),
            lang('Run schedule - Day')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
            ),
            lang('Run schedule - Month')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
            ),
            lang('Run schedule - Day of the week')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number in range 0-6 (0 is Sunday) or a 3 letter shortcut (e.g. sun)'),
                'value'         => '*',
            ),
        );

        return $variables;
    }

    function execute()
    {
        // NOTE: We must return error objects only when there are fatal errors that prevent the plugin from working appropiately,
        // and handle inside this plugin the e-mailing corresponding to events.

        require_once 'library/CE/XmlFunctions.php';

        $arrEmails = explode("\r\n", $this->settings->get('plugin_serverstatus_Admin E-mail'));

        $messages = array();

        // Get list of servers
        $sql = "SELECT id, name, statsurl FROM server WHERE statsurl != ''";
        $result = $this->db->query($sql);

        $str1minLoad = $this->settings->get('plugin_serverstatus_1 Min. Load Average');
        $str5minLoad = $this->settings->get('plugin_serverstatus_5 Min. Load Average');
        $strMemory = $this->settings->get('plugin_serverstatus_Used Physical Memory');
        $arrSpace = explode(';', $this->settings->get('plugin_serverstatus_Mount Space Available'));
        if ( ($str1minLoad == "") && ($str5minLoad == "") && ($arrSpace[0] == "") ) {
            return;
        }

        while (list($serverid, $servername, $statsurl) = $result->fetch())
        {
            if (is_a($xmldata = $this->_getXMLData($statsurl, $serverid, $servername, $arrEmails), 'CE_Error')) {
                $messages[] = $xmldata;
                continue;
            }

            //need to validate XML before we do anything so that we do not
            //get unexpected errors when using this xmlize function
            $xml = XmlFunctions::xmlize($xmldata,1);
            if (is_a($xml, 'CE_Error')) {
                throw new Exception('Invalid XML.  Ensure your server stats URL is returning valid XML.');
            }
            if ( array_key_exists("tns:phpsysinfo", $xml) ){
               $this->phpsysinfo_ver = "3.x";
            }else{
                $this->phpsysinfo_ver = "old";
            }

            // Work from here
            if($this->phpsysinfo_ver == "3.x") {
                $uptimeTimeStamp = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["Uptime"];
            } else {
                $uptimeTimeStamp = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["Uptime"][0]["#"];
            }
            if ($this->settings->get('plugin_serverstatus_Server Restarted')
                && $uptimeStatus = $this->_updateUptimeStatus($uptimeTimeStamp, $serverid, $servername, $arrEmails))
            {
                $messages[] = $uptimeStatus;
            }

            //Check 1 Min. Load Threshold
            if ($str1minLoad != '' && ($load1minStatus = $this->_checkLoad($xml,$serverid, $servername, $arrEmails, '1'))) {
                $messages[] = $load1minStatus;
            }
            //Check 5 Min. Load Threshold
            if ($str5minLoad != '' && ($load5minStatus = $this->_checkLoad($xml,$serverid, $servername, $arrEmails, '5'))) {
                $messages[] = $load5minStatus;
            }
            //Check Used Physical Memory Threshold
            if ($strMemory != '' && ($memoryStatus = $this->_checkmemory($xml,$serverid, $servername, $arrEmails))) {
                $messages[] = $memoryStatus;
            }

            //Check Disk Mount Space
            if($arrSpace[0] != ""){
                $mountsStatus = $this->_checkdiskspace($xml, $serverid, $servername, $arrEmails);
                if (is_array($mountsStatus) && $mountsStatus) {
                    foreach ($mountsStatus as $status) {
                        if ($status) {
                            $messages[] = $status;
                        }
                    }
                }
            }

        }

        if (!$messages) {
            return array('Server Status Is Ok');
        }

        return $messages;
    }

    function isMountExists($xml,$matchmount)
    {
        $found=false;
        if ($this->phpsysinfo_ver == "3.x") {
            $mounts = $xml["tns:phpsysinfo"]["#"]["FileSystem"][0]["#"]["Mount"];
            for($i = 0; $i < sizeof($mounts); $i++) {
                $mount = $mounts[$i];
                $mountpoint = $mount["@"]["MountPoint"];
                $mountpercent = $mount["@"]["Percent"];
                if($matchmount==$mountpoint) $found = true;
            }
        } else {
            $mounts = $xml["phpsysinfo"]["#"]["FileSystem"][0]["#"]["Mount"];
            for($i = 0; $i < sizeof($mounts); $i++) {
                $mount = $mounts[$i];
                $mountpoint = $mount["#"]["MountPoint"][0]["#"];
                $mountpercent = $mount["#"]["Percent"][0]["#"];
                if($matchmount==$mountpoint) $found = true;
            }
        }
        return $found;
    }

    function checkIfMountOverThreshold($xml, $matchmount, $threshold, $serverId, $servername, $arrEmails)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            $mounts = $xml["tns:phpsysinfo"]["#"]["FileSystem"][0]["#"]["Mount"];
        } else {
            $mounts = $xml["phpsysinfo"]["#"]["FileSystem"][0]["#"]["Mount"];
        }
        $strErrorMessage = "";

        $info = @unserialize($this->settings->get('service_serverstatus_info'));
        $tmpInfo['time'] = date('Y-m-d H:i:s');
        $tmpInfo['manually_executed'] = 0;
        $tmpInfo['results'] = '';
        $tmpInfo['errors'] = '';
        $tmpInfo['status'] = SERVICE_STATUS_IN_PROCESS;
        $tmpInfo['extra_info'] = @$info['extra_info'];

        for($i = 0; $i < sizeof($mounts); $i++) {
            $mount = $mounts[$i];
            if ($this->phpsysinfo_ver == "3.x") {
                $mountpoint = $mount["@"]["MountPoint"];
                $mountpercent = $mount["@"]["Percent"];
            } else {
                $mountpoint = $mount["#"]["MountPoint"][0]["#"];
                $mountpercent = $mount["#"]["Percent"][0]["#"];
            }
            if($matchmount==$mountpoint){
                $status = "Mount over threshold-> server: $serverId mount: $matchmount";

                if (is_array($tmpInfo['extra_info']) && in_array($status, $tmpInfo['extra_info'])) {
                    $notified = true;
                } else {
                    $notified = false;
                }
                if( intval($threshold) < intval($mountpercent) ){
                    if (!$notified) {
                        $strErrorMessage =  "Server ".$servername." Mount Threshold Error: ".$matchmount." (".$mountpercent."% used) has exceeded threshold set for this mount of ".$threshold."%";
                        if ($arrEmails) {
                            $this->_email($arrEmails, false, $strErrorMessage);
                        }
                        $tmpInfo['extra_info'][] = $status;
                    }
                } elseif ($notified) {
                    if ($arrEmails) {
                        $this->_email($arrEmails,
                            false,
                            "Server $servername Mount $matchmount  has returned to a level ($mountpercent%) below the set threshold ($threshold%)."
                        );
                    }
                    $key = array_search($status, $tmpInfo['extra_info']);
                    if($key !== false){
                        unset($tmpInfo['extra_info'][$key]);
                    }
                }
            }
        }

        $this->settings->updateValue('service_serverstatus_info', serialize($tmpInfo));

        return $strErrorMessage;
    }

    function _checkdiskspace($xml, $serverId, $servername, $arrEmails)
    {
        $status = array();
        $arrSpaces = explode(';', $this->settings->get('plugin_serverstatus_Mount Space Available'));

        //need to look for mounts
        for($x=0;$x < sizeof($arrSpaces);$x++) {

            $bolServerMatched = false;

            $arrMount = explode(',',$arrSpaces[$x]);

            $tMount = $arrMount[0];

            if (!isset($arrMount[1]) || !is_numeric($arrMount[1])) {
                $status[] = new CE_Error("Server $servername error: invalid \"Mount Space Available\" format");
                break;
            }
            $tMountThreshold = $arrMount[1];

            if (isset($arrMount[2])) {
                $tMountServer = $arrMount[2];
            } else {
                $tMountServer = '';
            }

            if(strtoupper($tMountServer) == strtoupper($servername)){
                $bolServerMatched = true;
            }

            if(($tMount!="") && ($bolServerMatched || ($tMountServer=="") ) ){

                if($this->isMountExists($xml,$tMount)){
                    $strError = $this->checkIfMountOverThreshold($xml, $tMount, $tMountThreshold, $serverId, $servername, $arrEmails);
                    if($strError!=""){
                        $status[] = $strError;
                    }
                }else{
                    if ( $this->settings->get('plugin_serverstatus_Ignore Mount Point Errors') == 0 ) {
                        $status[] = new CE_Error("Server ".$servername." Mount Threshold Error: ".$tMount. " does not exist on server ".$servername);
                    }
                }
            }
        }
        return $status;
    }

    function _checkLoad($xml,$serverId, $servername, $arrEmails, $loadTime)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            $loadaverages = $xml["tns:phpsysinfo"]["#"]["Vitals"][0]["@"]["LoadAvg"];
        } else {
            $loadaverages = $xml["phpsysinfo"]["#"]["Vitals"][0]["#"]["LoadAvg"][0]["#"];
        }


        //Get the load threshold placed in admin->plugins->
        switch($loadTime) {
            case 1:
                $strLoad = $this->settings->get('plugin_serverstatus_1 Min. Load Average');
                $status = "1 Min. Load over threshold-> server: $serverId";
                $loadIndex = 0;
                $thresholdExceededMessage = 'Server %s 1 Min. Load Average Threshold Error (%s): Your threshold of %s was exceeded';
                $belowThresholdMessage = 'Server %s 1 Min. Load Average (%s) has returned to a level below the set threshold (%s).';
                break;
            case 5:
                $strLoad = $this->settings->get('plugin_serverstatus_5 Min. Load Average');
                $status = "5 Min. Load over threshold-> server: $serverId";
                $loadIndex = 1;
                $thresholdExceededMessage = 'Server %s 5 Min. Load Average Threshold Error (%s): Your threshold of %s was exceeded';
                $belowThresholdMessage = 'Server %s 5 Min. Load Average (%s) has returned to a level below the set threshold (%s).';
                break;
        }

        $info = @unserialize($this->settings->get('service_serverstatus_info'));
        $tmpInfo['time'] = date('Y-m-d H:i:s');
        $tmpInfo['manually_executed'] = 0;
        $tmpInfo['results'] = '';
        $tmpInfo['errors'] = '';
        $tmpInfo['status'] = SERVICE_STATUS_IN_PROCESS;
        $tmpInfo['extra_info'] = @$info['extra_info'];

        if (is_array($tmpInfo['extra_info']) && in_array($status, $tmpInfo['extra_info'])) {
            $notified = true;
        } else {
            $notified = false;
        }

        if($loadaverages!=""){
            $loads = explode(" ",$loadaverages);
            $load = $loads[$loadIndex];
            if(floatval($strLoad) < floatval($load) ) {
                if (!$notified) {
                    if ($arrEmails) {
                        $this->_email(
                            $arrEmails,
                            false,
                            sprintf($thresholdExceededMessage, $servername, $loadaverages, $strLoad)
                        );
                    }
                    $tmpInfo['extra_info'][] = $status;
                    $this->settings->updateValue('service_serverstatus_info', serialize($tmpInfo));
                    return sprintf($thresholdExceededMessage, $servername, $loadaverages, $strLoad);
                }
            } elseif ($notified) {
                // send the e-mail here because it's not an error message like the others
                if ($arrEmails) {
                    $this->_email(
                        $arrEmails,
                        false,
                        sprintf($belowThresholdMessage, $servername, $loadaverages, $strLoad)
                    );
                }
                $key = array_search($status, $tmpInfo['extra_info']);
                if($key !== false){
                    unset($tmpInfo['extra_info'][$key]);
                }
                $this->settings->updateValue('service_serverstatus_info', serialize($tmpInfo));
                return sprintf($belowThresholdMessage, $servername, $loadaverages, $strLoad);
            }
            return;
        }
        return new CE_Error($this->user->lang('Load average was not present in server status output'));
    }

    function _checkmemory($xml,$serverId, $servername, $arrEmails)
    {
        if ($this->phpsysinfo_ver == "3.x") {
            $memory = $xml["tns:phpsysinfo"]["#"]["Memory"][0]["@"]["Used"];
            $cachedmemory = $xml["tns:phpsysinfo"]["#"]["Memory"][0]["#"]['Details'][0]['@']["Cached"];
        } else {
            $memory = $xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Used"][0]["#"];
            $cachedmemory = $xml["phpsysinfo"]["#"]["Memory"][0]["#"]["Cached"][0]["#"];
        }

        if ($cachedmemory == '') {
            $cachedmemory = 0;
        }

        //Get the memory threshold placed in admin->plugins->
        $strMemory = $this->settings->get('plugin_serverstatus_Used Physical Memory');
        $status = "Used Memory over threshold-> server: $serverId";

        $info = @unserialize($this->settings->get('service_serverstatus_info'));
        $tmpInfo['time'] = date('Y-m-d H:i:s');
        $tmpInfo['manually_executed'] = 0;
        $tmpInfo['results'] = '';
        $tmpInfo['errors'] = '';
        $tmpInfo['status'] = SERVICE_STATUS_IN_PROCESS;
        $tmpInfo['extra_info'] = @$info['extra_info'];

        if (is_array($tmpInfo['extra_info']) && in_array($status, $tmpInfo['extra_info'])) {
            $notified = true;
        } else {
            $notified = false;
        }

        if($memory!=""){
            $mbmemory = round(($memory-$cachedmemory)/1024);
            if(floatval($strMemory) < floatval($mbmemory) ) {
                if (!$notified) {
                    if ($arrEmails) {
                        $this->_email(
                            $arrEmails,
                            false,
                            "Server ".$servername." Used Physical Memory Threshold Error (".$mbmemory." MB): Your threshold of ".$strMemory." MB was exceeded"
                        );
                    }
                    $tmpInfo['extra_info'][] = $status;
                    $this->settings->updateValue('service_serverstatus_info', serialize($tmpInfo));
                    return "Server ".$servername." Used Physical Memory Threshold Error (".$mbmemory." MB): Your threshold of ".$strMemory." MB was exceeded";
                }
            } elseif ($notified) {
                // send the e-mail here because it's not an error message like the others
                if ($arrEmails) {
                    $this->_email(
                        $arrEmails,
                        false,
                        "Server $servername Used Physical Memory ($mbmemory MB) has returned to a level below the set threshold ($strMemory MB)."
                    );
                }
                $key = array_search($status, $tmpInfo['extra_info']);
                if($key !== false){
                    unset($tmpInfo['extra_info'][$key]);
                }
                $this->settings->updateValue('service_serverstatus_info', serialize($tmpInfo));
                return "Server $servername Used Physical Memory($mbmemory MB) has returned to a level below the set threshold ($strMemory MB).";
            }
            return;
        }
        return new CE_Error($this->user->lang('Used Physical Memory was not present in server status output'));
    }


    function _email($arrEmails, $arrFailedMessage, $strBody = false)
    {
        $strSubject = "Server Status Report";
        $strErrors = "";

        if ($arrFailedMessage) {
            for($x=0;$x<=sizeof($arrFailedMessage);$x++){
                if($arrFailedMessage[$x]!="")   $strErrors .= $arrFailedMessage[$x]."\n";
            }
        }

        if (!$strBody) {
            $strBody = "Server Status Error:\n ".$strErrors." \n\n\n\nThis email is sent to you by ClientExec";
        }

        for($x=0;$x<=sizeof($arrEmails);$x++){
            if ($arrEmails[$x]!=""){
                $strFromEmail = $this->settings->get('Support E-mail');
                $strFromName = $this->settings->get('Support E-mail');
                $strTo = $arrEmails[$x];
                $mailGateway = new NE_MailGateway();
                $mailGateway->MailMessageEmail( $strBody,
                                                $strFromEmail,
                                                $strFromName,
                                                $strTo,
                                                '',
                                                $strSubject);
            }
        }

    }

    function _parseUptime($tuptime)
    {
         $uptimeString = "";
         $secs = round($tuptime % 60);
         $mins = round($tuptime / 60 % 60);
         $hours = round($tuptime / 3600 % 24);
         $days = round($tuptime / 86400);

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

    function _updateUptimeStatus($uptime, $serverId, $serverName, $arrEmails)
    {
        $newStatus = "Uptime-> server: $serverId uptime: $uptime";

        $info = @unserialize($this->settings->get('service_serverstatus_info'));
        $tmpInfo['time'] = date('Y-m-d H:i:s');
        $tmpInfo['manually_executed'] = 0;
        $tmpInfo['results'] = '';
        $tmpInfo['errors'] = '';
        $tmpInfo['status'] = SERVICE_STATUS_IN_PROCESS;
        $tmpInfo['extra_info'] = @$info['extra_info'];

        if (is_array($tmpInfo['extra_info']) && isset($tmpInfo['extra_info']['server'][$serverId]['uptime'])) {
            $time   = $tmpInfo['extra_info']['server'][$serverId]['uptime']['time'];
            $status = $tmpInfo['extra_info']['server'][$serverId]['uptime']['status'];

            $tmpInfo['extra_info']['server'][$serverId]['uptime']['time']   = date('Y-m-d H:i:s');
            $tmpInfo['extra_info']['server'][$serverId]['uptime']['status'] = $newStatus;
            $this->settings->updateValue('service_serverstatus_info', serialize($tmpInfo));

            $lastChecked = strtotime($time);
            preg_match('/uptime: (\d+)/', $status, $matches);
            $lastUptime = $matches[1];
            list($year, $month, $day, $hour, $minute, $second) = sscanf($time, '%d-%d-%d %d:%d:%d');
            $lastChecked = mktime($hour, $minute, $second, $month, $day, $year);
            // +10 to avoid false alarms caused by latencies in the uptime extraction from the server
            if ($uptime - $lastUptime + 10 < time() - $lastChecked) {
                if ($arrEmails) {
                    $this->_email(
                        $arrEmails,
                        false,
                        $this->user->lang('Server %s has been restarted since the last check.', $serverName)
                    );
                }
                return $this->user->lang('Server %s has been restarted since the last check.', $serverName);
            }
        } else {
            $tmpInfo['extra_info']['server'][$serverId]['uptime']['time']   = date('Y-m-d H:i:s');
            $tmpInfo['extra_info']['server'][$serverId]['uptime']['status'] = $newStatus;
            $this->settings->updateValue('service_serverstatus_info', serialize($tmpInfo));
        }
    }

    function _getXMLData($statsurl, $serverId, $serverName, $arrEmails)
    {
        require_once 'library/CE/NE_Network.php';

        $strErrorMessage = '' ;
        $xmldata = NE_Network::curlRequest($this->settings, $statsurl, false, false, true, false);

        $status = "Server not responding-> server: $serverId";

        $info = @unserialize($this->settings->get('service_serverstatus_info'));
        $tmpInfo['time'] = date('Y-m-d H:i:s');
        $tmpInfo['manually_executed'] = 0;
        $tmpInfo['results'] = '';
        $tmpInfo['errors'] = '';
        $tmpInfo['status'] = SERVICE_STATUS_IN_PROCESS;
        $tmpInfo['extra_info'] = @$info['extra_info'];

        if (is_array($tmpInfo['extra_info']) && isset($tmpInfo['extra_info']['server'][$serverId]['not_responding'])) {
            $notified = true;
        } else {
            $notified = false;
        }

        if (is_a($xmldata, 'CE_Error')) {
            if (!$notified) {
                if ($arrEmails) {
                    $this->_email(
                        $arrEmails,
                        false,
                        $this->user->lang('Server %s is not responding.', $serverName)
                    );
                }
                $tmpInfo['extra_info']['server'][$serverId]['not_responding'] = $status;
                $this->settings->updateValue('service_serverstatus_info', serialize($tmpInfo));
            }
            return $this->user->lang('Server %s is not responding.', $serverName);
        } elseif ($notified) {
            if ($arrEmails) {
                $this->_email(
                    $arrEmails,
                    false,
                    "Server $serverName is now responding."
                );
            }
            unset($tmpInfo['extra_info']['server'][$serverId]['not_responding']);
            $this->settings->updateValue('service_serverstatus_info', serialize($tmpInfo));
        }

        return $xmldata;
    }
}

?>
