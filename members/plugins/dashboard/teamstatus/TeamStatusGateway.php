<?php

require_once 'plugins/dashboard/teamstatus/TeamStatusEntry.php';
require_once 'plugins/dashboard/teamstatus/TeamStatusIterator.php';

/**
 * TeamStatus Gateway
 *
 * @category   Gateway
 * @package    Home
 * @author     Alberto Vasquez <alberto@clientexec.com>
 * @license    http://www.clientexec.com  ClientExec License Agreement
 * @link       http://www.clientexec.com
 */
class TeamStatusGateway extends NE_Model
{

    //default get Team Status
    function getTeamStatus($limit,$start,$sort,$dir)
    {
        if($limit){
            if (!$start) $start = 0;
            $limitarray[] = $start;
            $limitarray[] = $limit;
        }else{
            $limitarray=false;
        }

        $orderbyarray = array();
        $orderbyarray[]=$sort;
        $orderbyarray[]=$dir;

        $query = "SELECT ts.id, "
                ."ts.userid, "
                ."u.firstname, "
                ."u.lastname, "
                ."u.email, "
                ."u.groupid, "
                ."ts.userstatus, "
                ."ts.status_datetime, "
                ."UNIX_TIMESTAMP(ts.status_datetime) AS status_datetime_stamp, "
                ."(UNIX_TIMESTAMP() - UNIX_TIMESTAMP(ts.status_datetime)) AS seconds_elapsed, "
                ."ts.replyid "
                ."FROM team_status ts "
                ."LEFT JOIN users u "
                ."ON ts.userid = u.id ";

        return new TeamStatusIterator(new TeamStatusEntry(), false, $orderbyarray, $query, $limitarray);
    }

    function getGroupNames(){
        $query = "SELECT id, name FROM groups WHERE isadmin=1";
        $result = $this->db->query($query);

        $groupNames = array();
        while (list($id, $name) = $result->fetch()){
            $groupNames[$id] = $name;
        }

        return $groupNames;
    }

    function getReplyToInfo($uid, $replyid){
        $replyToInfo = array();
        if(isset($replyid)){
            $query =  "SELECT ts.userid,u.firstname,u.lastname,u.email,u.groupid,ts.userstatus, ts.replyid ";
            $query .= "FROM team_status ts left join users u on ts.userid=u.id ";
            $query .= "WHERE ts.id=? ";
            $result = $this->db->query($query, $replyid);
            list($useridreplied, $firstnamereplied, $lastnamereplied, $emailreplied, $groupreplied, $userstatusreplied, $replyidreplied) = $result->fetch();
            if(isset($replyidreplied)){
                $query2 =  "SELECT ts.userid,u.firstname,u.lastname ";
                $query2 .= "FROM team_status ts left join users u on ts.userid=u.id ";
                $query2 .= "WHERE ts.id=? ";
                $result2 = $this->db->query($query2, $replyidreplied);
                list($useridreplied2, $firstnamereplied2, $lastnamereplied2) = $result2->fetch();
            }

            $replyToInfo["groupreplied"] = (isset($groupreplied))? $groupreplied: '';
            $replyToInfo["emailreplied"] = (isset($emailreplied))? $emailreplied: '';
            $replyToInfo["useridreplied"] = (isset($useridreplied))? $useridreplied: '';
            $replyToInfo["firstnamereplied"] = (isset($firstnamereplied))? $firstnamereplied: '';;
            $replyToInfo["lastnamereplied"] = (isset($lastnamereplied))? $lastnamereplied: '';;

            $fullnamereplied = (isset($firstnamereplied))? $firstnamereplied: '';
            $fullnamereplied .= (isset($lastnamereplied) && $lastnamereplied != '')? ' '.$lastnamereplied: '';

            if(isset($useridreplied2) && isset($useridreplied) && $useridreplied2 != $useridreplied && $uid != $useridreplied){
                $fullnamereplied2 = (isset($firstnamereplied2))? ' in reply to '.$firstnamereplied2: '';
                $fullnamereplied2 .= (isset($lastnamereplied2) && $lastnamereplied2 != '')? ' '.$lastnamereplied2: '';
            }else{
                $fullnamereplied2 = '';
            }

            if($fullnamereplied != "" && isset($userstatusreplied)){
                $replyToInfo["reply_details"] = $fullnamereplied.$fullnamereplied2.":<br>".$userstatusreplied;
            }else{
                $replyToInfo["reply_details"] = '';
            }
        }else{
            $replyToInfo["reply_details"] = '';
            $replyToInfo["groupreplied"] = '';
            $replyToInfo["emailreplied"] = '';
            $replyToInfo["useridreplied"] = '';
            $replyToInfo["firstnamereplied"] = '';
            $replyToInfo["lastnamereplied"] = '';
        }
        return $replyToInfo;
    }

    /**
     * Function to delete a team status based on the id
     *
     * @param int $id Team Status ID
     *
     * @return void
     */
    public function deleteTeamStatus($id)
    {
        $query = 'DELETE FROM team_status WHERE id = ?';
        $this->db->query($query, $id);
    }

    /**
     * Saves Team status to the database and uses the api settings
     * to connect to hipchat and add the new status
     *
     * @param user $user User object of the user saving the team status
     * @param string $status Team status message
     * @param int $replyid ID of the status message if it's a reply
     * @param string $name Name override for team status
     * @param bool $savestatus Boolean value to save the status or not
     *
     * @return [type]                [description]
     */
    public function saveTeamStatus(&$user, $status, $replyid = "", $name = null, $savestatus = null)
    {
        $savestatus = ($savestatus === null) ? true: $savestatus;
        $fromName = ($name===null) ? $user->getFullName() : $name;
        $replyIdField = '';
        $replyIdValue = '';
        if($replyid != ''){
            $replyIdField = ', replyid';
            $replyIdValue = ',?';
        }

        $status = trim($status);
        if ($savestatus) {
            $query = "INSERT INTO team_status (userid, userstatus, status_datetime".$replyIdField.") VALUES (?, ?, NOW()".$replyIdValue.")";
            if($replyid != ''){
                $this->db->query($query, $user->getId(), $status, $replyid);
            }else{
                $this->db->query($query, $user->getId(), $status);
            }
        }

        //if there is a replyid then we need to send an email to notify user
        if ($replyid != '' && $savestatus) {
            include_once 'library/CE/NE_MailGateway.php';
            include_once 'modules/support/models/AutoresponderTemplateGateway.php';

            $query =  "SELECT userid,userstatus,status_datetime FROM team_status WHERE id = ? ";
            $result = $this->db->query($query, $replyid);
            list($useridreplied, $userstatusreplied, $status_datetimereplied) = $result->fetch();

            $userreplied = new user($useridreplied);
            $from = $user->getEmail();

            $query =  "SELECT userid FROM team_status WHERE id = ? ";
            $result = $this->db->query($query, $replyid);
            list($userid) = $result->fetch();

            $templategateway = new AutoresponderTemplateGateway();
            $template = $templategateway->getEmailTemplateByName("Team Status Reply Template");

            $body = $template->getContents();
            $subject = $template->getSubject();
            $templateID = $template->getId();
            $tUser = new User($userid);
            if($templateID !== false){
                include_once 'modules/admin/models/Translations.php';
                $languages = CE_Lib::getEnabledLanguages();
                $translations = new Translations();
                $languageKey = ucfirst(strtolower($tUser->getRealLanguage()));
                CE_Lib::setI18n($languageKey);

                if(count($languages) > 1){
                    $subject = $translations->getValue(EMAIL_SUBJECT, $templateID, $languageKey, $subject);
                    $body = $translations->getValue(EMAIL_CONTENT, $templateID, $languageKey, $body);
                }
            }

            $body = str_replace("[TEAMSTATUS]", nl2br($status), $body);
            $body = str_replace("[REPLIEDTEAMSTATUSUSERNAME]", $userreplied->getFullName(), $body);
            $body = str_replace("[REPLIEDTEAMSTATUSDATE]", $status_datetimereplied, $body);
            $body = str_replace("[REPLIEDTEAMSTATUS]", $userstatusreplied, $body);

            $mailGateway = new NE_MailGateway();
            $mailGateway->sendMailMessage(
                $body,
                $from,
                $fromName,
                $userid,
                '',
                $subject,
                3,
                0,
                'notifications',
                '',
                '',
                MAILGATEWAY_CONTENTTYPE_HTML
            );
        }
    }
}
