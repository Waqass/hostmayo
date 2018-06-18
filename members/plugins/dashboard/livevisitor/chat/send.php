<?php

define('NE_ADMIN', false);

require_once '../../../../config.php';
require_once '../../../../library/vendor/autoload.php';
require_once '../../../../library/constants.php';
require_once '../../../../library/CE/Lib.php';
require_once('../lib/libsse.php');

define('APPLICATION_PATH', realpath(__DIR__.'/../../../../library'));
set_include_path(APPLICATION_PATH.'/..'.PATH_SEPARATOR.APPLICATION_PATH);

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
//let's register the CE namespace for autoloader of our own lib classes
$autoloader->registerNamespace('CE_');

// We can't create a session here because this is a long running process that will lock
// the session file, blocking any other requests trying to start the session.
// So I just create a dummy class, because some libs still need to find some object named 'session'
// in the registry.
$session = new stdClass();
Zend_Registry::set('session', $session);

$db = new CE_MySQL( $hostname, $dbuser, $dbpass, $database, 'utf8');
Zend_Registry::set('db', $db);
$db->setEngine('mysql');
if ( !@$db->connect() ) {
  throw new Exception('Couldn\'t connect to DB');
}

$GLOBALS['data'] = new SSEData($db);
$sse = new SSE();
$sse->sleep_time = 0.4;
$sse->client_reconnect = 1;
$sse->exec_limit = 5;

class LatestUser extends SSEEvent {
    private $cache = 0;
    private $data;
    private $roomid;
    private $userdata;
    private $serviceid;

    public function __construct($roomid,$userdata,$serviceid)
    {
        $this->roomid = $roomid;
        $this->userdata = $userdata;
        $this->serviceid = $serviceid;
    }

    public function update(){

        $users = $GLOBALS['data']->get_all_active_admin_users_inroom($this->roomid, $this->userdata['usertype'] == 1);
        if ($this->userdata['usertype'] == 0) {
            return json_encode(array("users"=>$users));
        }

        $admins = $GLOBALS['data']->get_all_active_admin_users();

        return json_encode(array("admins"=>$admins,"users"=>$users));
    }

    public function check(){

        $data = $GLOBALS['data']->get_last_user_not_me($this->roomid,$this->cache,$this->userdata['chatterid']);

        if( ( (count($data) > 0 ) && ($this->cache == 0)) ||
            ( (count($data) > 0 ) && $data[0]['time'] > $this->cache) ) {
            $this->cache = (int)$data[0]['time'];
            if ($this->cache === 0) $this->cache++;
            return true;
        }
        return false;
    }
};

class LatestRoomStatus extends SSEEvent {
    private $cache = 0;
    private $data;
    private $roomid;
    private $chatterid;
    private $serviceid;

    public function __construct($roomid,$chatterid,$serviceid)
    {
        $this->roomid = $roomid;
        $this->chatterid = $chatterid;
        $this->serviceid = $serviceid;
    }

    public function update(){
        return json_encode(true);
    }

    public function check(){
        return $GLOBALS['data']->is_room_closed($this->roomid,$this->chatterid);
    }
}

class LatestTyping extends SSEEvent {
    //private $cache = 0;
    private $data;
    private $roomid;
    private $userdata;
    //private $oldcache = 0;
    private $serviceid;
    private $typing = array();

    public function __construct($roomid,$userdata,$serviceid)
    {
        $this->roomid = $roomid;
        $this->userdata= $userdata;
        $this->serviceid = $serviceid;
    }

    public function update(){
        $this->typing = array();

        $users = array();
        foreach($this->data as $user)
        {
            if ( $user['subtype'] == "1")  {
                $this->typing[] = $user;
            }

            $userdata = $GLOBALS['data']->getuserinfobychatterid($user['chatterid']);
            $user['user'] = $userdata['fullname'];
            $user['usertype'] = $userdata['usertype'];
            if ($this->userdata['usertype'] == 1) {
                // don't reveal emails to visitors
                $user['email'] = $userdata['email'];
            }
            $users[] = $user;

        }

        $output = array();
        $output['users'] = $users;
        $output['serviceid'] = $this->serviceid;
        return json_encode($output);
    }

    public function check(){

        $this->data = $GLOBALS['data']->is_somone_typing($this->roomid,$this->userdata['chatterid'],$this->typing);

        if( count($this->data) > 0 )  {
            return true;
        } else {
            return false;
        }

    }
}

/*
class LatestFile extends SSEEvent {
    private $cache = 0;
    private $data;
    private $roomid;
    private $chatterid;
    private $oldcache = 0;
    private $serviceid;

    public function __construct($roomid,$chatterid,$serviceid)
    {
        $this->roomid = $roomid;
        $this->chatterid = $chatterid;
        $this->serviceid = $serviceid;
    }
    public function update(){
        $data = array();
        $data['data'] = $GLOBALS['data']->getFiles($this->roomid,$this->cache,$this->chatterid);
        $data['serviceid'] = $this->serviceid;
        try{
            return json_encode($data);
        } catch(Exception $e) {
            file_put_contents("test.txt",serialize(print_r($data,true)),FILE_APPEND);
        }

    }
    public function check(){

        $maxtime = $GLOBALS['data']->do_we_have_new_files($this->roomid,$this->cache,$this->chatterid);

        if( !is_null($maxtime) && $maxtime > $this->cache) {
            $this->oldcache = $this->cache;
            if ($this->cache === 0) {
                $this->cache = (int)$maxtime;
                return false;
            } else {
                $this->cache = (int)$maxtime;
                return true;
            }
        }
        return false;
    }
}*/

class LatestMessage extends SSEEvent {
    private $cache = 0;
    private $data;
    private $roomid;
    private $chatterid;
    private $oldcache = 0;
    private $serviceid;

    public function __construct($roomid,$chatterid,$fromid,$serviceid)
    {
        $this->roomid = $roomid;
        $this->chatterid = $chatterid;
        $this->cache = $fromid;
        $this->serviceid = $serviceid;
    }
    public function update(){
        $data = array();
        $data['data'] = $GLOBALS['data']->getMessages($this->roomid,$this->oldcache,$this->chatterid);
        $data['serviceid'] = $this->serviceid;
        return json_encode($data);

    }
    public function check(){

        $maxtime = $GLOBALS['data']->do_we_have_new_messages($this->roomid,$this->cache,$this->chatterid);
        //if( ($this->cache == 0) || ( !is_null($maxtime) && $maxtime > $this->cache) ) {
        if( !is_null($maxtime) && $maxtime > $this->cache) {
            $this->oldcache = $this->cache;
            if ($this->cache === 0) {
                $this->cache = (int)$maxtime;
                return false;
            } else {
                $this->cache = (int)$maxtime;
                return true;
            }
        }
        return false;
    }
};

$roomId = $_GET['roomid'];
$serviceId = $_GET['serviceid'];
$fromid = (int) (isset($_GET['fromid'])) ? $_GET['fromid'] : 0;
$chatterid = $_GET['chatterid'];

$userdata = $GLOBALS['data']->getuserinfobychatterid($chatterid);

//file_put_contents("test.txt",serialize(print_r($_GET,true)),FILE_APPEND);
$sse->addEventListener($roomId.'roomstatus',new LatestRoomStatus($roomId,$chatterid,$serviceId));
$sse->addEventListener($roomId.'typing',new LatestTyping($roomId,$userdata,$serviceId));
$sse->addEventListener($roomId.'user',new LatestUser($roomId,$userdata,$serviceId));
$sse->addEventListener($roomId.'message',new LatestMessage($roomId,$chatterid,$fromid,$serviceId));
//$sse->addEventListener($roomId.'files',new LatestFile($roomId,$chatterid,$serviceId));

// I'm taking advantage that public chat restarts send.php every 45 secs to update the user's
// timestamp
if ($userdata['usertype'] == 0) {
    $GLOBALS['data']->updateChatterTimestamp($roomId, $chatterid);
}

$sse->start();
