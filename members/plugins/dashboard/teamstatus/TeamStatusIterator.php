<?php

require_once 'library/CE/NE_RowIterator.php';
require_once 'plugins/dashboard/teamstatus/TeamStatusEntry.php';

class TeamStatusIterator extends NE_RowIterator
{
    function fetch()
    {
        if (!$row = $this->resultSet->fetch()) {
            return false;
        }

        $teamStatus = new TeamStatusEntry();
        $teamStatus->setId($row['id']);
        $teamStatus->setUserId($row['userid']);
        $teamStatus->setFirstName($row['firstname']);
        $teamStatus->setLastName($row['lastname']);
        $teamStatus->setEmail($row['email']);
        $teamStatus->setGroup($row['groupid']);
        $teamStatus->setUserStatus($row['userstatus']);
        $teamStatus->setStatusDateTime($row['status_datetime']);
        $teamStatus->setStatusDateTimeStamp($row['status_datetime_stamp']);
        $teamStatus->setSecondsElapsed($row['seconds_elapsed']);
        $teamStatus->setReplyId($row['replyid']);

        return $teamStatus;
    }
}

?>
