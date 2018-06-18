<?php

use Illuminate\Database\Capsule\Manager as Db;

require_once 'modules/admin/models/DashboardPlugin.php';
require_once 'modules/clients/models/UserGateway.php';

class PluginLivevisitor extends DashboardPlugin
{
    /* plugin member vars used by ClientExec */
    var $name;
    var $smallName;

    var $description;
    var $default = false; //to be included with fresh installs
    var $cache = true;
    var $sidebarPlugin = true;
    var $order = 2;
    var $iconName  = "icon-group"; // must be bootstrap defined icon

    var $sharedSettings = true;

    //we want to restrict editing settings to this by role Id for super admin
    //ids can be obtained from roles view in setup/staff
    var $roleIds = array(2);

    var $jsLibs  = array('plugins/dashboard/livevisitor/lib/eventsource.js', 'plugins/dashboard/livevisitor/plugin.js','plugins/dashboard/livevisitor/lib/chat.shared.js');
    var $cssPages = array('plugins/dashboard/livevisitor/plugin.css','plugins/dashboard/livevisitor/assets/flags.css');

    var $listeners = array(
            array("Account-Logoff","handlemylogoff")
        );

    var $publicActions = array("savemsg","typing","loggedin","showchatpopup","closeroom","leaveroom","track");

    function __construct($user, $typeOfFetch = 1) {
        $this->name = lang("Live Chat Beta");
        $this->smallName = lang("Chat");
        $this->description = lang("This shows the visitors currently viewing your website.");
        parent::__construct($user,$typeOfFetch);
    }

    //override the getPanel of DashboardPlugin as we do not want or have an index.phtml to output
    //we can just return html directly
    public function getPanel()
    {
        return '<div id="plugin-livevisitor"><img class="content-loading" src="../images/loader.gif" /></div>';
    }

    public function logAction()
    {

        require_once('plugins/dashboard/livevisitor/lib/sse_data.php');
        $data = new SSEData($this->db);

        if (!isset($_REQUEST['id'])) {
            throw new CE_Exception("Room id was not passed");
        }

        if (isset($_REQUEST['message'])){
            $_REQUEST['message'] = htmlentities($_REQUEST['message']);
        }

        $a = array();
        $a['id'] = $_REQUEST['id']; //roomid
        $a['chatterid'] = (isset($_REQUEST['chatterid'])) ? $_REQUEST['chatterid'] : 0;
        $a['message'] = $_REQUEST['message'];
        $data->storeLog($a);

    }

    /**
     * Main action that return data for plugin sidebar
     * @return [type] [description]
     */
    public function visitorpollAction() {

        $data = array();
        $data['type'] = 'event';
        $data['name'] = 'VisitorPoll';
        $data['superadmin'] = ($this->user->getGroupId() == ROLE_SUPERADMIN)? 1 : 0;
        $data['self'] = $this->user->getId();
        $data['visitors'] = $this->_getsitevisitors();

        $chats = $this->_getchats();
        $data['chats'] = $chats['items'];
        $data['totalunreadchats'] = $chats['totalunreadchats'];
        $data['waitingchats'] = $chats['waitingchats'];

        return $data;

    }

    /**
     * gets active chats
     * @return [type] [description]
     */
    private function _getchats(){

        $chats = array();

        $totalunreadchats = 0;
        $waitingchats = 0;

        //lets get any open chats
        $query = "SELECT distinct(c.id) as roomid,c.time,c.title,c.chatterid, cu.fullname, cu.email, c.ip FROM `chatroom` c, chatuser cu where c.status = 1 and c.ispublic = 0 and c.chatterid = cu.chatterid and cu.usertype=0";
        $result = $this->db->query($query);
        while($row = $result->fetch())
        {
            $chat = array();

            $newchats = 0;

            //let's get count of users
            $users = array();
            $query2 = "SELECT id, cr.chatterid,title,time, fullname, email, usertype FROM `chatroom` cr, chatuser cu WHERE cr.chatterid = cu.chatterid and id=? group by email";
            $result2 = $this->db->query($query2, $row['roomid'] );

            $newchats = 0;
            while ($row2 = $result2->fetch()) {
                if ($row2['chatterid'] == $_REQUEST['chatterid']) {
                    //let's get our latest time
                    $query3 = "SELECT roomid,chatterid,msg FROM `chatlog` where roomid=? and chatterid <> ? and time > ? ";
                    $result3 = $this->db->query($query3,$row['roomid'],$_REQUEST['chatterid'],$row2['time']);
                    $newchats = $result3->getNumRows();
                    if ($newchats > 0) {
                        $chat['haschats'] = true;
                        //let's get chats to raise alert
                    }
                }

                //we only want to know about admin users
                if ($row2['usertype'] != "0") $users[] = $row2;
            }

            $chat['roomid'] = $row['roomid'];
            $chat['title'] = $row['title'];
            $chat['timeago'] = CE_Lib::_get_timeago($row['time']);
            $chat['fullname'] = $row['fullname'];
            $chat['chatterid'] = $row['chatterid'];
            $chat['email'] = $row['email'];
            $chat['ip'] = $row['ip'];

            if ($newchats > 9) {
                $chat['paddingclass'] = 'count-withpadding';
                $chat['newchats'] = "+9";
            } else {
                $chat['newchats'] = $newchats;
            }

            $totalunreadchats += $newchats;
            if (count($users) === 0) $waitingchats++;
            $chat['users'] = $users;
            $chats[] = $chat;
        }

        //I think we should get all logged in users as well here
        return array("waitingchats"=>$waitingchats,"totalunreadchats"=>$totalunreadchats,"items"=>$chats);
    }

    public function handlemylogoff($e)
    {
        /*
        require_once('plugins/dashboard/livevisitor/lib/sse_data.php');
        $data = new SSEData($this->db);

        $arr = $e->getParams();
        $data->leave_all_rooms( array("chatterid"=>$arr['userid']) );
        */

        //let's logout of all rooms with this user
    }

    /**
     * Action to perform on a room
     * @return [type] [description]
     */
    public function actiononroomAction()
    {
        require_once('plugins/dashboard/livevisitor/lib/sse_data.php');
        $data = new SSEData($this->db);

        switch($_GET['actiontoperform']) {
            case "closeroom":
                $data->close_room($_GET['roomid']);
                break;
            case "clearlogs":
                $data->clear_logs($_GET['roomid']);
                break;
        }

    }

    /**
     * Method to call for viewing admin panel
     * @return html
     */
    public function showadminpanelAction()
    {
        $this->cssPages = array("plugins/dashboard/livevisitor/views/adminpanel.css");
        $this->jsLibs = array("plugins/dashboard/livevisitor/views/adminpanel.js",'templates/default/js/vendor/jquery.ui.widget.js','templates/default/js/jquery.fileupload.js','templates/default/js/bootstrap-file-input.js');

        $roomid = filter_var($_GET['roomid'],FILTER_SANITIZE_NUMBER_INT);
        if ($roomid == "") $this->view->roomid = 0;
        else $this->view->roomid = $roomid;

        //let's update the user as having done something
        if (!$this->user->isAnonymous()) {
            $this->user->setLastViewLastSeen();
        }

        echo $this->view->render("views/adminpanel.phtml");

    }

    /**
     * Saving message by room attendee
     * @return [type] [description]
     */
    public function savemsgAction()
    {
        require_once('plugins/dashboard/livevisitor/lib/sse_data.php');
        $data = new SSEData($this->db);

        //let's update the user as having done something
        if (!$this->user->isAnonymous()) {
            $this->user->setLastViewLastSeen();
        }

        return $data->storeLog($_POST);
    }

    /**
     * Send typing action
     * @return [type] [description]
     */
    public function typingAction()
    {
        require_once('plugins/dashboard/livevisitor/lib/sse_data.php');
        $data = new SSEData($this->db);
        $data->storeTyping($_POST);
    }

    /**
     * [loggedinAction description]
     * @return [type] [description]
     */
    public function loggedinAction()
    {
        require_once('plugins/dashboard/livevisitor/lib/sse_data.php');
        $data = new SSEData($this->db);

        $ip = $_SERVER['REMOTE_ADDR'];
        if ($ip === "::1") $ip = "NA";

        //let's update the user as having done something
        if (!$this->user->isAnonymous()) {
            $this->user->setLastViewLastSeen();
        }

        //if admin we need to assign him to a room
        $_POST['groupid'] = $this->user->isAdmin()? 1 : 0;
        $data->storeUserInRoom($ip,$_POST); //not storing

    }

    /**
     * Closes a room
     * @return [type] [description]
     */
    public function closeroomAction()
    {
        require_once('plugins/dashboard/livevisitor/lib/sse_data.php');
        $data = new SSEData($this->db);

        //let's update the user as having done something
        if (!$this->user->isAnonymous()) {
            $this->user->setLastViewLastSeen();
        }

        $roomid = filter_var($_POST['roomid'],FILTER_SANITIZE_NUMBER_INT);
        $data->close_room($roomid);

    }

    /**
     * Initial call when entering a room to return all data needed to set room up
     * @return [type] [description]
     */
    public function getuserinroominfoAction()
    {

        require_once('plugins/dashboard/livevisitor/lib/sse_data.php');
        $data = new SSEData($this->db);

        $json = $data->get_info_on_user_who_opened_room($_POST);
        $chats = $data->get_other_chats_this_user_had($json['chatterid']);
        $json['session'] = @unserialize($json['session']);
        $json['otherchats'] = $chats;

        //who's chatterid should this be.. user's or admins
        $json['isclosed'] = $data->is_room_closed( $_POST['roomid'], $_POST['chatterid'] );
        $json['description'] = $data->get_room_description( $_POST['roomid']);
        $json['description'] = (trim($json['description']) == "") ? "No room description" : $json['description'];
        $json['title'] = $data->get_room_title( $_POST['roomid']);
        $json['admins'] = $data->get_all_active_admin_users();
        $json['users'] = $data->get_all_active_admin_users_inroom($_POST['roomid'], $this->user->isAdmin());

        return $json;
    }

    /**
     * upload a file .. currently only admin is allowed to upload a file to a room
     * @return [type] [description]
     */
    public function uploadfileAction()
    {
        require_once('plugins/dashboard/livevisitor/lib/sse_data.php');
        $data = new SSEData($this->db);

        //error_reporting(E_ALL | E_STRICT);
        require('plugins/dashboard/livevisitor/chat/UploadHandler.php');
        //upload dir
        $path = UploadHandler::get_full_url();

        $saveasname = md5(microtime());
        $room_id = $_REQUEST['roomid'];
        if (CE_Lib::hasDots($room_id)) {
            CE_Lib::log(1, '*** Possible path injection attack detected in roomid query var.');
            exit;
        }

        $options = array("saveasname"=>$saveasname,"upload_dir" => dirname($_SERVER['SCRIPT_FILENAME']).'/uploads/files/'.$room_id."/",
            "upload_url"=> $path.'/uploads/files/'.$room_id."/"
            );
        $upload_handler = new UploadHandler($options);
        $files = $upload_handler->post($data, $room_id);

        //let's update the user as having done something
        if (!$this->user->isAnonymous()) {
            $this->user->setLastViewLastSeen();
        }

        //suppress output
        $this->outputData = false;

    }

    /**
     * Track a visitor on the site
     * @return [type] [description]
     */
    public function trackAction()
    {
        require_once('plugins/dashboard/livevisitor/lib/sse_data.php');
        $data = new SSEData($this->db);

        $ip = $_SERVER['REMOTE_ADDR'];
        if ($ip === "::1") $ip = "NA";


        unset($_GET['callback']);
        $data->storeVisitor($ip,$_GET);
    }

    /**
     * Sign out of a room
     * @return [type] [description]
     */
    public function leaveroomAction()
    {
        require_once('plugins/dashboard/livevisitor/lib/sse_data.php');
        $data = new SSEData($this->db);
        $data->leave_room($_POST);
    }

    public function showchatpopupAction()
    {
        $this->cssPages = array("plugins/dashboard/livevisitor/chat/chat.css","javascript/hellobar/hellobar.css");
        $this->jsLibs = array('plugins/dashboard/livevisitor/lib/eventsource.js', "plugins/dashboard/livevisitor/lib/chat.shared.js","plugins/dashboard/livevisitor/chat/chat.js","javascript/hellobar/hellobar.js");

        // $controller = Zend_Controller_Front::getInstance();
        // $this->view->_helper->layout()->disableLayout()

        $this->view->gHideStyle = true;

        // to avoid fetching track.php again, which would launch multiple popups
        $this->view->gHasAccessToLiveChat = false;

        echo $this->view->render("chat/popup.php");
    }

    private function _getsitevisitors() {

        $visitors = array();
        $userGateway = new UserGateway($this->user);

        //need to pass db info as we are using our own db stuff here
        require_once('plugins/dashboard/livevisitor/lib/sse_data.php');
        $data = new SSEData($this->db);
        $rawvisitor = $data->getVisitors();

        $filecounter = 1;
        foreach($rawvisitor as $visitor)
        {
            $data = @unserialize($visitor['data']);
            if (isset($data['location'])&&isset($data['location']['countryCode'])) {
                $data['location']['countryCode'] = strtolower($data['location']['countryCode']);
            } else {
                if (!isset($data['location'])) $data['location'] = array();
                $data['location']['countryCode'] = $visitor['country'];
            }
            //we can get real customer information
            $ip = $visitor['ip'];

            $customer = $userGateway->getCustomInfoByIp($ip);
            $visitors[] = array("count"=>$filecounter++,"ip"=>$ip,"timeago"=>CE_Lib::_get_timeago($visitor['lastvisit']),"customer"=>$customer,"data"=>$data,);
        }
        return $visitors;

    }

}
