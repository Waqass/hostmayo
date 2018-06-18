<?php
require_once 'plugins/dashboard/teamstatus/TeamStatusGateway.php';
require_once 'modules/admin/models/DashboardPlugin.php';

use AvatarBio\AvatarBio;

class PluginTeamstatus extends DashboardPlugin
{
    var $name;
    var $smallName;

    var $description;
    var $sidebarPlugin = true;

    var $default = false;
    var $cache = true;
    var $iconName = "icon-bullhorn";
    var $sharedSettings = false;

    //we want to restrict editing settings to this by role Id for super admin
    //ids can be obtained from roles view in setup/staff
    var $roleIds = array(2);

    //add libs to view for this plugin
    var $jsLibs = array('plugins/dashboard/teamstatus/plugin.js' );
    var $cssPages = array('plugins/dashboard/teamstatus/plugin.css');

    var $listeners = array(
            array("Account-StatusUpdate","handlestatusupdate")
        );

    function __construct($user, $typeOfFetch = 1) {
        $this->name = lang("Team Status");
        $this->smallName = lang("Status");
        $this->description = lang("This allows you to view the status of your team and update your own status.");
        parent::__construct($user,$typeOfFetch);
    }

    /**
     * allows for api calls to add status
     * @return null
     */
    public function addstatusAction()
    {
        if (!isset($_REQUEST['id'])) return;
        if (!isset($_REQUEST['status'])) return;
        $user = new User($_REQUEST['id']);
        $gateway = new TeamStatusGateway();
        $gateway->saveTeamStatus($user, htmlentities($_REQUEST['status'],ENT_QUOTES), 0);
    }

    public function handlestatusupdate($e)
    {
        $arr = $e->getParams();
        $gateway = new TeamStatusGateway();
        $gateway->saveTeamStatus($this->user, htmlentities($arr[0],ENT_QUOTES), 0);
    }

    public function deleteteamstatusAction()
    {
        if (isset($_REQUEST['userid']) && $_REQUEST['userid'] == $this->user->getId() && isset($_REQUEST['id']) && $_REQUEST['id'] != ""){
            $gateway = new TeamStatusGateway();
            $gateway->deleteTeamStatus($_REQUEST['id']);
        }

        return array();
    }

    /**
     * Saves a reply to the team status for the active staff member
     *
     * @access public
     */
    public function saveteamstatusAction()
    {

        if (isset($_REQUEST['message']) && $_REQUEST['message'] != ""){
            $replyid = '';
            if(isset($_REQUEST['replyid'])){
                $replyid = $_REQUEST['replyid'];
            }
            $gateway = new TeamStatusGateway();
            $gateway->saveTeamStatus($this->user, htmlentities($_REQUEST['message'],ENT_QUOTES), $replyid);
        }

        return array();
    }

    function timeElapsed($secondsElapsed)
    {
        $TimeDifference = 0;
        $TimeScale = "";

        if($secondsElapsed < 60){
            $TimeDifference = $secondsElapsed;
            $TimeScale = "sec";
        }else{
            if($secondsElapsed < (60 * 60)){
                $TimeDifference = floor($secondsElapsed / 60);
                $TimeScale = "min";
            }else{
                if($secondsElapsed < (24 * 60 * 60)){
                    $TimeDifference = floor($secondsElapsed / (60 * 60));
                    $TimeScale = "hr";
                }else{
                    if($secondsElapsed < (31 * 24 * 60 * 60)){
                        $TimeDifference = floor($secondsElapsed / (24 * 60 * 60));
                        $TimeScale = "day";
                    }else{
                        if($secondsElapsed < (365 * 24 * 60 * 60)){
                            $TimeDifference = floor($secondsElapsed / (31 * 24 * 60 * 60));
                            $TimeScale = "mth";
                        }else{
                            $TimeDifference = floor($secondsElapsed / (365 * 24 * 60 * 60));
                            $TimeScale = "yr";
                        }
                    }
                }
            }
        }

        if($TimeDifference > 1 || $TimeDifference == 0){
            $TimeScale .= "s";
        }

        return ($TimeDifference . " " . $TimeScale . " ago");
    }

    function getteamstatusAction()
    {

        //initializing search criteria
        if(!isset($_GET['start'])){
            $_GET['start'] = 0;
        }
        $start = $_GET['start'];

        if(!isset($_GET['sort'])){
            $_GET['sort'] = "status_datetime";
        }
        $sort = $_GET['sort'];

        if(!isset($_GET['dir'])){
            $_GET['dir'] = "desc";
        }
        $dir = $_GET['dir'];

        if(isset($_GET['limit'])){
            $limit = $_GET['limit'];
        }else{
            $limit = 3;
        }

        $gateway = new TeamStatusGateway();
        $teamstatus = $gateway->getTeamStatus($limit,$start,$sort,$dir);

        //populate the clienttypes mappings
        $this->groupNames = $gateway->getGroupNames();

        $datalist = array();

        //create avatar url
        $avatar = new AvatarBio();
        while ($userstatus = $teamstatus->fetch()) {
            $data = null;

            $data["id"] = $userstatus->getId();
            $data["userid"] = $userstatus->getUserId();

            $fullname = $userstatus->getFirstName();
            $lastname = $userstatus->getLastName();
            $fullname .= (isset($lastname) && $lastname != '')? ' '.substr($lastname,0,1): '';
            $data["fullname"] = $fullname;
            $data["email"] = $userstatus->getEmail();

            // Add some formatting to the user status here as opposed to inside the iterator
            $data["userstatus"] = preg_replace("/(?<!href=\")(http?:\/\/[^\s]+)/", "<a href=\"$1\" target='_blank'>$1</a>", html_entity_decode($userstatus->getUserStatus(), ENT_QUOTES));
            $data["userstatus"] = preg_replace("/(?<!href=\")(https?:\/\/[^\s]+)/", "<a href=\"$1\" target='_blank'>$1</a>", $data["userstatus"]);
            $data["userstatus"] = nl2br($data["userstatus"]);

            $data["status_datetime"] = $userstatus->getStatusDateTime();
            $data["status_datetime_stamp"] = $userstatus->getStatusDateTimeStamp();
            $data["status_datetime_string"] = $this->timeElapsed($userstatus->getSecondsElapsed());
            $replyid = $userstatus->getReplyId();
            if ($replyid !== "0" && $replyid !== null) $data["replyid"] = $replyid;

            $replyArray = $gateway->getReplyToInfo($userstatus->getUserId(), $userstatus->getReplyId());
            $data["reply_details"] = str_replace(array("\r\n", "\n", "\r", '"'), array('<br>', '<br>', '<br>', "''"), $replyArray["reply_details"]);

            $data["emailreplied"] = $replyArray["emailreplied"];
            $data["useridreplied"] = $replyArray["useridreplied"];

            $fullnamereplied = $replyArray["firstnamereplied"];
            $fullnamereplied .= (isset($replyArray["lastnamereplied"]) && $replyArray["lastnamereplied"] != '')? ' '.$replyArray["lastnamereplied"]: '';
            $data["fullnamereplied"] = $fullnamereplied;

            $data["pictureurl"] = '';
            try {
                $avatar->setEmail($userstatus->getEmail());
                $avatar->setSize(80);
                $avatar->setText($this->customer->getAvatarInitials());
                $data["pictureurl"] = $avatar->getImageURL();
            } catch (Exception $e) {}

            if ( $this->user->getId() == $userstatus->getUserId() ) {
                $data['displayDelete'] = '';
                $data['displayReply'] = 'none';
                $data['displayDash'] = 'none';
            } else {
                $data['displayDelete'] = 'none';
                $data['displayReply'] = '';
                $data['displayDash'] = 'none';
            }

            $datalist[] = $data;
        }

        $returnArray["totalcount"] = $teamstatus->getTotalNumItems();
        $returnArray["teamstatus"] = $datalist;


        $returnArray['type'] = 'event';
        $returnArray['name'] = 'teamStatus';
        $returnArray['self'] = $this->user->getId();

        return $returnArray;
    }
}
