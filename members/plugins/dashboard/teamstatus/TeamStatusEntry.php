<?php

require_once 'library/CE/NE_ActiveRecord.php';

class TeamStatusEntry extends NE_ActiveRecord
{
    var $tableName = 'teamstatus';

    var $fields = array(
        'id'                    => null,
        'userid'                => null,
        'firstname'             => '',
        'lastname'              => '',
        'email'                 => '',
        'groupid'               => null,
        'userstatus'            => '',
        'status_datetime'       => '0000-00-00 00:00:00',
        'status_datetime_stamp' => 0,
        'seconds_elapsed'       => 0,
        'replyid'               => null,
    );


    function setUserId($userid)
    {
        $this->fields['userid'] = $userid;
        $this->dirty = true;
    }

    function getUserId()
    {
        return $this->fields['userid'];
    }

    function setFirstName($firstname)
    {
        $this->fields['firstname'] = $firstname;
        $this->dirty = true;
    }

    function getFirstName()
    {
        return $this->fields['firstname'];
    }

    function setLastName($lastname)
    {
        $this->fields['lastname'] = $lastname;
        $this->dirty = true;
    }

    function getLastName()
    {
        return $this->fields['lastname'];
    }

    function setEmail($email)
    {
        $this->fields['email'] = $email;
        $this->dirty = true;
    }

    function getEmail()
    {
        return $this->fields['email'];
    }

    function setGroup($groupid)
    {
        $this->fields['groupid'] = $groupid;
        $this->dirty = true;
    }

    function getGroup()
    {
        return $this->fields['groupid'];
    }

    function setUserStatus($userstatus)
    {
        $this->fields['userstatus'] = $userstatus;
        $this->dirty = true;
    }

    function getUserStatus()
    {
        return $this->fields['userstatus'];
    }

    function setStatusDateTime($status_datetime)
    {
        $this->fields['status_datetime'] = $status_datetime;
        $this->dirty = true;
    }

    function getStatusDateTime()
    {
        return $this->fields['status_datetime'];
    }

    function setStatusDateTimeStamp($status_datetime_stamp)
    {
        $this->fields['status_datetime_stamp'] = $status_datetime_stamp;
        $this->dirty = true;
    }

    function getStatusDateTimeStamp()
    {
        return $this->fields['status_datetime_stamp'];
    }

    function setSecondsElapsed($seconds_elapsed)
    {
        $this->fields['seconds_elapsed'] = $seconds_elapsed;
        $this->dirty = true;
    }

    function getSecondsElapsed()
    {
        return $this->fields['seconds_elapsed'];
    }

    function setReplyId($replyid)
    {
        $this->fields['replyid'] = $replyid;
        $this->dirty = true;
    }

    function getReplyId()
    {
        return $this->fields['replyid'];
    }

}