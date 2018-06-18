<?php
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/admin/models/StatusAliasGateway.php';

/**
* @package Plugins
*/
class PluginDeletependingusers extends ServicePlugin
{
    protected $featureSet = 'accounts';
    public $hasPendingItems = true;

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Delete Pending Users'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('Erases pending users after the amount of days selected without being approved.'),
                'value'         => '0',
            ),
            lang('Amount of days')    => array(
                'type'          => 'text',
                'description'   => lang('Set the amount of days before deleting a pending user from the system'),
                'value'         => '30',
            ),
            lang('Run schedule - Minute')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '30',
                'helpid'        => '8',
            ),
            lang('Run schedule - Hour')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '01',
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

    function getUsersWithStatus($status = '')
    {
        $query = "SELECT id, UNIX_TIMESTAMP(dateactivated), status FROM users WHERE status =?";
        $result = $this->db->query($query,$status);
        return $result;
    }

    function getUsersToDelete()
    {
        $arrayUsersToDelete = array();
        $daysToDeleteUser = $this->settings->get('plugin_deletependingusers_Amount of days');
        $statusPending = StatusAliasGateway::getInstance($this->user)->getUserStatusIdsFor(USER_STATUS_PENDING);
        $result = $this->getUsersWithStatus($statusPending);
        $num_rows = $result->getNumRows();
        if($num_rows > 0){
            $tempActualDate = strtotime(date('Y-m-d'));
            while(list($id, $dateactivated, $status) = $result->fetch()){
                if (in_array($status, $statusPending)) {
                    $diffdate = $tempActualDate - $dateactivated;
                    $diffdate = $diffdate/(60*60*24);
                    if($diffdate >= $daysToDeleteUser){
                        $arrayUsersToDelete[] = $id;
                    }
                }
            }
        }
        return $arrayUsersToDelete;
    }

    function execute()
    {
        include_once 'modules/clients/models/Client_EventLog.php';

        $arrayUsersToDelete = $this->getUsersToDelete();
        $deletedUsers = 0;
        foreach($arrayUsersToDelete as $userid) {
            $objUser = new User($userid);
            // Get number of tickets that are not closed
            $ticketCount = $objUser->getCountOfNotClosedTickets();
            // only delete the user if they have 0 not closed tickets
            if ( $ticketCount == 0 ) {
                // do not delete with server plugin
                $objUser->delete(false);
                $clientLog = Client_EventLog::newInstance(false, NE_EVENTLOG_USER_ERASED, $userid, CLIENT_EVENTLOG_DELETED, NE_EVENTLOG_USER_SYSTEM);
                $clientLog->save();
                $deletedUsers++;
            }
        }
        return array($deletedUsers." user(s) deleted");
    }

    function pendingItems()
    {
        $usersToDelete = $this->getUsersToDelete();
        $returnArray = array();
        $returnArray['data'] = array();
        if ( count($usersToDelete) > 0 ) {
            foreach ( $usersToDelete as $userID ) {
                $user = new User($userID);
                $ticketCount = $user->getCountOfNotClosedTickets();
                if ( $ticketCount == 0 ) {
                    $tmpInfo = array();
                    $tmpInfo['customer'] = '<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=' . $user->getId() . '">' . $user->getFullName() . '</a>';
                    $tmpInfo['email'] = $user->getEmail();
                    $returnArray['data'][] = $tmpInfo;
                }
            }
        }
        $returnArray['totalcount'] = count($returnArray['data']);
        $returnArray['headers'] = array (
            $this->user->lang('Customer'),
            $this->user->lang('E-mail'),

        );
        return $returnArray;
    }

    function output() { }

    function dashboard()
    {
        $statusPending = StatusAliasGateway::getInstance($this->user)->getUserStatusIdsFor(USER_STATUS_PENDING);
        $daysToDeleteUser = !is_null($this->settings->get('plugin_deletependingusers_Amount of days')) ? $this->settings->get('plugin_deletependingusers_Amount of days')*24*60*60 : 0;
    	$query = "SELECT COUNT(id) FROM users WHERE status IN (".implode(', ', $statusPending).") AND NOW() - UNIX_TIMESTAMP(dateactivated) >= $daysToDeleteUser";
        $result = $this->db->query($query);
    	list($numberOfUsersToDelete) = $result->fetch();
        return $this->user->lang('Pending users to be deleted on next run: %d', $numberOfUsersToDelete);
    }
}
?>
