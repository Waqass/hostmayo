<?php
/*
* @package libSSE-php
* @author Licson Lee <licson0729@gmail.com>
* @description A PHP library for handling Server-Sent Events (SSE)
*/

/*
* @class SSEData
* @description A class for store data and access them between scripts using different mechnism.
*/

chdir(dirname(__FILE__).'/../../../..');

use AvatarBio\AvatarBio;

require_once 'modules/admin/models/StatusAliasGateway.php';

class SSEData {
    private $db;
    private $pAvatar;

    public function __construct($db)
    {
        $this->db = $db;
        $this->pAvatar = new AvatarBio();
    }

    public function do_we_have_new_messages($roomid, $id, $chatterid)
    {
        $result = $this->db->query('SELECT max(id) as id from chatlog where roomid=? and id > ?', $roomid, $id);
        $row = $result->fetch();
        if (!$row) {
            return false;
        }
        return $row['id'];
    }

    public function save_files($roomid, $file)
    {
        $this->db->query("INSERT INTO `files` (`id_parent`, `name`, `hash`, `size`, `roomid`, `added`) VALUES(-1,?,?,?,?, NOW())", $file->original_name, $file->name, $file->size, $roomid);
    }

    public function getMessages($roomid,$id,$chatterid)
    {
        $this->db->query('UPDATE chatroom set time = ? where chatterid= ? and id= ? ', time(), $chatterid, $roomid);

        $result = $this->db->query(
                'SELECT cl.id, cl.chatterid, cl.msg, cl.time, cu.fullname, cu.email '
               .'FROM chatlog cl, chatuser cu '
               .'WHERE (cl.chatterid = cu.chatterid) AND cl.roomid=? AND cl.id > ? '
               .'ORDER BY cl.id DESC LIMIT 70',
           $roomid, $id);
        $data = array();

        // specify MYSQLI_ASSOC to avoid revealing other information in indexed numbers
        while ($row = $result->fetch(MYSQLI_ASSOC)) {

            $this->pAvatar->setEmail($row['email']);
            $this->pAvatar->setText(CE_Lib::getAvatarInitials($row['fullname']));
            $this->pAvatar->setSize(80);
            $row['gravatar'] = html_entity_decode($this->pAvatar->getImageURL());

            // let's not revail emails here
            unset($row['email']);
            $data[] = $row;
        }
        $data = array_reverse($data);
        return $data;
    }

    public function getuserinfobychatterid($chatterid)
    {
        $result = $this->db->query('select chatterid,fullname,email,usertype from chatuser where chatterid = ? ', $chatterid);
        $row = $result->fetch();
        return $row;
    }

    /**
     * method used when user wants to leave room (different from closing room)
     * @param  [type] $a [description]
     * @return [type]    [description]
     */
    public function leave_room($a)
    {
        //file_put_contents("test.txt",serialize(print_r($a,true)),FILE_APPEND);
        $this->db->query("DELETE from chatroom where id=? AND chatterid<> 0 AND chatterid = ?", $a['roomid'], $a['chatterid']);
        //$sql = sprintf("SELECT cu.email, cu.chatterid, status FROM  `chatroom` cr, chatuser cu WHERE cu.chatterid = cr.chatterid AND cr.id =  '%s'",$a['roomid']);

        //when someone leaves the room let's update the time to any other user so that the send.php will pick up on the change
        $this->db->query("UPDATE `chatroom` set time = ? WHERE id = ? and chatterid = 0", time(), $a['roomid']);
    }

    /**
     * method used when user wants to leave room (different from closing room)
     * @param  [type] $a [description]
     * @return [type]    [description]
     */
    public function leave_all_rooms($a)
    {
        $this->db->query("DELETE from chatroom where chatterid<> 0 AND chatterid = ?", $a['chatterid']);
    }

    public function update_room_description($a)
    {
        //file_put_contents("test.txt",serialize(print_r($a,true)),FILE_APPEND);
        $this->db->query('UPDATE chatroom SET description=? WHERE chatterid=0 and id = ? ', $a['title'], $a['roomid']);
    }

    /**
     * retrieves the last time another user logged in other than me
     * @param  [type] $roomid    [description]
     * @param  [type] $time      [description]
     * @param  [type] $chatterid [description]
     * @return [type]            [description]
     */
    public function get_last_user_not_me($roomid,$time,$chatterid)
    {
        $result = $this->db->query('SELECT time,chatterid from chatroom where chatterid <> ? and id = ? and time > ? ORDER by time DESC', $chatterid, $roomid, $time);
        $data = array();
        while ($row = $result->fetch()) {
            $data[] = $row;
        }
        return $data;
    }

    public function get_all_active_admin_users_inroom($roomid, $isAdmin)
    {

        $this->killInactivePublicChatters();
        $data = array();

        $result = $this->db->query('SELECT cu.fullname as user, 1 as loggedin, cu.email, cu.usertype, cr.time,cr.chatterid, cr.chatterid as id,cr.title,cr.ip,cr.status, UNIX_TIMESTAMP( NOW( ) ) - TIME AS timediff from chatroom cr, chatuser cu where cu.chatterid = cr.chatterid AND cr.id =? ORDER by cr.time DESC', $roomid);
        while ($row = $result->fetch()) {
            //do not add room
            if ($row['chatterid'] == 0) {
                continue;
            }
            if (!$isAdmin) {
                // minimal info for non-admins
                $row = array(
                    'chatterid' => $row['chatterid'],
                    'user' => $row['user'],
                    'loggedin' => 1,
                );
            }
            $data[$row['chatterid']] = $row;
        }

        //logged in needs to be checked below if a real admin as users above don't really have that info
        $statusActive = StatusAliasGateway::userActiveAliases();
        $result = $this->db->query('select id from users where status IN ('.implode(', ', $statusActive).') and groupid > 1 and loggedin = 0');
        while ($row = $result->fetch()) {
            if (array_key_exists($row['id'], $data)) {
                $data[$row['id']]['loggedin'] = 0;
            }
        }

        return $data;
    }

    public function get_all_active_admin_users()
    {
        $statusActive = StatusAliasGateway::userActiveAliases();
        $result = $this->db->query('select id,loggedin,firstname,lastname,email,UNIX_TIMESTAMP(lastseen) as lastseen_time,lastseen, TIMESTAMPDIFF(MINUTE, lastseen, now()) as timediff from users where status IN ('.implode(', ', $statusActive).') and groupid > 1 order by loggedin desc,lastseen desc');
        $data = array();
        while ($row = $result->fetch()) {
            $row['user'] = $row['firstname']." ".$row['lastname'];
            $sqlfornickname = sprintf("select value as nickname from user_customuserfields where userid='%s' and customid IN(select id from customuserfields where type=61) limit 1", $row['id']);
            //file_put_contents("test.txt",serialize(print_r($sqlfornickname,true)),FILE_APPEND);
            $resultfornickname = $this->db->query($sqlfornickname);
            $row['nickname'] = $row['firstname']." ".$row['lastname'];
            while ($row2 = $resultfornickname->fetch()) {
                if (trim($row2['nickname']) == "") {
                    continue;
                }
                $row['nickname'] = $row2['nickname'];
            }
            $data[] = $row;
        }
        return $data;
    }

    public function is_somone_typing($roomid,$chatterid,$typing)
    {
        $result = $this->db->query('SELECT time, "1" as subtype,chatterid from chattyping where chatterid <> ? and roomid=? ', $chatterid, $roomid);
        $nowtyping = array();
        while ($row = $result->fetch()) {
            $nowtyping[$row['chatterid']] = $row;
        }

        //lets go through all those typing
        foreach ($typing as $wastyping) {
            if ( !array_key_exists($wastyping['chatterid'], $nowtyping ) ) {
                $wastyping['subtype'] = "0";
                $nowtyping[$wastyping['chatterid']] = $wastyping;
            } else {
                unset($nowtyping[$wastyping['chatterid']]);
            }
        }

        return $nowtyping;
    }

    /**
     * get room description
     * @param  [type] $roomid [description]
     * @return [type]         [description]
     */
    public function get_room_description($roomid)
    {
        $result = $this->db->query("SELECT description FROM  `chatroom` WHERE `chatterid` = 0 AND `id` = ? limit 1", $roomid);
        $description = "";
        while ($row = $result->fetch()) {
            $description = $row['description'];
        }
        return $description;
    }

    /**
     * Retrieves the title for a given roomid
     * @param  [type] $roomid [description]
     * @return [type]         [description]
     */
    public function get_room_title($roomid)
    {
        $result = $this->db->query("SELECT title FROM  `chatroom` WHERE `chatterid` = 0 AND `id` = ? limit 1", $roomid);
        $title = "";
        while ($row = $result->fetch()) {
            $title = $row['title'];
        }
        return $title;
    }

    public function get_info_on_user_who_opened_room($a)
    {
        $this->db->query("UPDATE users SET lastview = '',lastseen = ?,loggedin=1 WHERE id = ?", date('Y-m-d H:i:s'), $a['chatterid']);

        $result = $this->db->query("Select * from (SELECT cu.chatterid,fullname,ip,title,description,email FROM `chatroom` c, chatuser cu where c.chatterid = cu.chatterid and cu.usertype=0 and c.id = ?) v, chatvisitor cv where v.chatterid = cv.chatterid order by lastvisit desc limit 1", $a['roomid']);
        return $result->fetch();
    }

    public function get_other_chats_this_user_had($chatterid)
    {
        $result = $this->db->query("Select id,time from chatroom where chatterid = ? order by time asc", $chatterid);
        $otherchats = array();
        while ($row = $result->fetch()) {
            $otherchats[] = $row;
        }
        return $otherchats;
    }

    /**
     * Clear the logs and remove the links from files
     * @param  [type] $roomid [description]
     * @return [type]         [description]
     */
    public function clear_logs($roomid)
    {
        $this->db->query("DELETE FROM chatlog WHERE roomid =?", $roomid);
        $this->db->query("DELETE from files where roomid = ?", $roomid);

        //quick way to delete all files from directory
    }

    /**
     * [delete_file description]
     * @param  [type] $roomid [description]
     * @param  [type] $hash   [description]
     * @return [type]         [description]
     */
    public function delete_file($roomid, $hash, $msgid)
    {
        $this->db->query("DELETE from files where hash=? and roomid = ?", $hash, $roomid);
        $this->db->query("DELETE FROM chatlog WHERE id =?", $msgid);
    }

    public function close_room($roomid)
    {
        $this->db->query("UPDATE chatroom set status = 0 where id=?", $roomid);
    }

    /**
     * checks to see if the room is not populated by any other user than chatterid
     * @param  [type]  $a [description]
     * @return boolean    [description]
     */
    public function is_room_closed($roomid,$chatterid)
    {
        $result = $this->db->query("Select * from chatroom where id=? and chatterid=? and status <> 1", $roomid, $chatterid );

        $retFound = false;
        while ($row = $result->fetch()) {
            $retFound = true;
        }

        return $retFound;

    }

    public function getVisitors()
    {
        $data = array();
        // $result = mysqli_query(sprintf("Select t1.session as data,t1.ip,t1.country as country,t1.chatterid,t1.title,t1.lastvisit from chatvisitor as t1 inner join (SELECT ip,max(lastvisit) as elder FROM chatvisitor GROUP BY ip ORDER BY elder desc) as t2 on t1.ip = t2.ip and t1.lastvisit = t2.elder and UNIX_TIMESTAMP(now()) - t1.lastvisit < 500 " ));
        $result = $this->db->query("Select t1.session as data,t1.ip,t1.country as country,t1.chatterid,t1.title,t1.lastvisit from chatvisitor t1 WHERE UNIX_TIMESTAMP(now()) - t1.lastvisit < 500");
        while ($row = $result->fetch()) {
            $data[] = $row;
        }
        return $data;
    }

    public function storeVisitor($ip,$data)
    {
        //file_put_contents("test.txt",serialize(print_r($data,true)),FILE_APPEND);
        //determine device
        $device = "pc";
        if (isset($data['device'])) {
            if ( (isset($data['device']['is_tablet'])) && ($data['device']['is_tablet'] == "true") ) {
                $device = "tablet";
            } else if (isset($data['device']['is_phone']) && $data['device']['is_phone'] == "true") {
                $device = "phone";
            }
        }

        if (isset($data['current_session'])) {
            $url = $data['current_session']['url'];
            $path = $data['current_session']['path'];
            $title = $data['current_session']['title'];
            $ref_url = $data['current_session']['referrer'];
            $ref_path = $data['current_session']['referrer_info']['path'];
            $ref_host = $data['current_session']['referrer_info']['host'];
        } else {
            $url = "";
            $path = "";
            $title = "";
            $ref_url = "";
            $ref_path = "";
            $ref_host = "";
        }

        if (isset($data['chatterid'])) {
            $chatterid = $data['chatterid'];
        } else {
            $chatterid = "";
        }

        if (!isset($data['locale'])) {
            $country = "na";
            $lang = "na";
        } else {
            $country = $data['locale']['country'];
            $lang = $data['locale']['lang'];
        }

        if (!isset($data['browser'])) {
            $b_version = "na";
            $b_browser = "na";
            $os = "na";
        } else {

            if (!isset($data['browser']['version'])) {
                $b_version = "na";
            } else {
                $b_version = $data['browser']['version'];
            }

            if (!isset($data['browser']['browser'])) {
                $b_browser = "na";
            } else {
                $b_browser = $data['browser']['browser'];
            }

            if (!isset($data['browser']['os'])) {
                $os = "na";
            } else {
                $os = $data['browser']['os'];
            }
        }

        $this->db->query("REPLACE INTO `chatvisitor` (ip,lastvisit,chatterid,session,country,lang, browser_name, browser_ver, os, search_engine, search_terms, ref_url, ref_host, ref_path, device, url, path, title) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            $ip,
            time(),
            $chatterid,
            serialize($data),
            $country,
            $lang,
            $b_browser,
            $b_version,
            $os,
            ( (!isset($data['current_session'])) || (!isset($data['current_session']['search'])) || $data['current_session']['search']['engine'] === "null") ? "" : $data['current_session']['search']['engine'],
            ( (!isset($data['current_session'])) || (!isset($data['current_session']['search'])) || (!isset($data['current_session']['search']['query'])) || ($data['current_session']['search']['query'] === "null") ) ? "" : $data['current_session']['search']['query'],
            $ref_url,
            $ref_host,
            $ref_path,
            $device,
            $url,
            $path,
            $title
        );
    }


    public function storeUserInRoom($ip,$a)
    {
        $this->storeChatUser($a);
        //if (isset($a['userid']) && $a['userid'] != 0) {
        //  mysqli_query(sprintf("UPDATE users SET lastview = '',lastseen = '%s',loggedin=1 WHERE id = '%s'",date('Y-m-d H:i:s'),$a['userid']));
        //}
        $a['title'] = substr($a['title'], 0, 45);
        return $this->db->query("REPLACE INTO `chatroom`(id,chatterid,title,time,ip,ispublic) VALUES(?,?,?,?,?,?)", $a['id'], $a['chatterid'], $a['title'], time(), $ip, $a['ispublic']);
    }

    public function updateChatterTimestamp($roomId, $chatterId)
    {
        $this->db->query("UPDATE chatroom
                SET time=? WHERE id=? AND chatterid=?",
            time(),
            $roomId,
            $chatterId);
    }

    private function killInactivePublicChatters()
    {
        $this->db->query("DELETE cr FROM chatroom cr LEFT JOIN chatuser cu
                ON cr.chatterid=cu.chatterid WHERE cu.usertype=0 AND cr.`time` < ?",
            strtotime('15 minutes ago'));
    }

    private function storeChatUser($a)
    {
        $this->db->query("REPLACE INTO `chatuser`(chatterid,fullname,email,usertype) VALUES(?,?,?,?)", $a['chatterid'], $a['user'], $a['email'], $a['groupid']);
    }

    public function storeTyping($a)
    {
        if ($a['subtype'] == "start") {
            return $this->db->query("REPLACE INTO `chattyping`(roomid,chatterid,time,subtype) VALUES(?,?,?,?)", $a['id'], $a['chatterid'], time(), 0);
        } else {
            return $this->db->query("DELETE from `chattyping` where roomid = ? and chatterid = ? ", $a['id'], $a['chatterid']);
        }
    }

    public function storeLog($a, $hash = null)
    {
        //first let's make sure we have chatuser
        //if we pass zero it is a room chat log (meaning notification)
        if ($a['chatterid'] != 0) {
            $result = $this->db->query("Select count(*) as count from chatuser where chatterid= ?", $a['chatterid']);
            $row = $result->fetch();
            if ($row['count'] == 0) {
                $this->storeChatUser($a);
            }

            $a['subtype'] = "stop";
            $this->storeTyping($a);

        }

        $result = $this->db->query("INSERT INTO `chatlog`(roomid,chatterid,msg,time) VALUES(?,?,?,?)", $a['id'], $a['chatterid'], $a['message'], time());
        $inserted_id = $result->getInsertId();

        //if we are passing hash than it was an image we are saving
        if (isset($a['hash'])) {
            //we are using description to store messageid for this file
            $hash = filter_var($a['hash'], FILTER_SANITIZE_STRING);
            $this->db->query("UPDATE files set description = ? where roomid=? and hash=?", $inserted_id, $a['id'], $hash);
        }

        //file_put_contents("data.txt",serialize(array($inserted_id, $hash)),FILE_APPEND);
        return $inserted_id;

    }

};
