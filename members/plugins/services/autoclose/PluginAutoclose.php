<?php
require_once 'modules/admin/models/ServicePlugin.php';
require_once'library/CE/NE_MailGateway.php';
require_once 'modules/support/models/Ticket.php';
require_once 'modules/admin/models/StatusAliasGateway.php';
require_once 'modules/support/models/TicketLog.php';

/**
* @package Plugins
*/
class PluginAutoclose extends ServicePlugin
{
    protected $featureSet = 'support';
    public $hasPendingItems = true;

    function getVariables()
    {

        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Ticket Auto Close'),
            ),

	    lang('Enabled')       => array(
                'type'          => "yesno",
                'description'   => lang('When enabled, tickets remaining unresponded to by customers for x amount of days will automatically be closed.'),
                'value'         => '0',
            ),

            lang('Days to trigger autoclose')       => array(
                'type'          => 'text',
                'description'   => lang('Enter number of days to wait before autoclosing a ticket that is in the waiting on customer status.'),
                'value'         => '3',
            ),
            lang('Ticket Message')       => array(
                'type'          => 'textarea',
                'description'   => lang('Enter the message you would like entered into the ticket when it is closed.<br>Template Tags: [CLIENTNAME], [TICKETNUMBER], [TICKETSUBJECT], [TICKETFIRSTLOG], [COMPANYNAME]'),
                'value'         => "ATTN: [CLIENTNAME],\r\n\r\nYour Support Ticket #[TICKETNUMBER] with subject \"[TICKETSUBJECT]\" has been closed due to inactivity.\r\nIf this issue has not been resolved please reopen this ticket.\r\n\r\nThank you,\r\n[COMPANYNAME]",
            ),
            lang('Pre-Notify Customer')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled the customer will be notified before the ticket is closed. Use the AutoClose Ticket Service Template'),
                'value'         => '1',
            ),
            lang('Days to trigger Pre-Notify autoclose')       => array(
                'type'          => 'text',
                'description'   => lang('Enter number of days to notify before autoclosing a ticket that is in the waiting on customer status.'),
                'value'         => '1',
            ),
            lang('Notify Customer')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled the customer will be notified when a ticket is closed.'),
                'value'         => '1',
            ),
            lang('Run schedule - Minute')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '0',
                'helpid'        => '8',
            ),
            lang('Run schedule - Hour')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '0',
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

        require_once 'library/CE/NE_MailGateway.php';
        require_once 'modules/support/models/TicketNotifications.php';
        $mailGateway = new NE_MailGateway();
        $messages = array();
        $numTicketsClosed = 0;

        if($this->settings->get('plugin_autoclose_Pre-Notify Customer')){
            $autocloseCondition = 'AND autoclose=1';
        } else {
            $autocloseCondition = '';
        }

        $statusWaitingCustomer = StatusAliasGateway::getInstance($this->user)->getTicketStatusIdsFor(TICKET_STATUS_WAITINGONCUSTOMER);
        $sql = "SELECT * FROM `troubleticket` WHERE `status` IN (".implode(', ', $statusWaitingCustomer).") $autocloseCondition AND `lastlog_datetime` <= DATE_SUB( NOW() , INTERVAL ? DAY )";
        $result = $this->db->query($sql, $this->settings->get('plugin_autoclose_Days to trigger autoclose'));
        while ($row = $result->fetch()) {
            $ticket = new Ticket($row['id']);
            $ticket->setStatus(-1);
            $ticket->SetLastLogDateTime(date('Y-m-d H-i-s'));
            $ticket->save();
            $userid = $ticket->getUserID();
            $user = new User($userid);

            $message = $this->settings->get('plugin_autoclose_Ticket Message');
            $message = str_replace("[CLIENTNAME]", $user->getFullName(true), $message);
            $message = str_replace("[TICKETNUMBER]", $ticket->getIdLabel(), $message);
            $message = str_replace("[TICKETSUBJECT]", $ticket->getSubject(), $message);
            $message = str_replace("[TICKETFIRSTLOG]", $ticket->getFirstLog(), $message);
            $message = str_replace(array("[COMPANYNAME]","%5BCOMPANYNAME%5D"), $this->settings->get("Company Name"), $message);

            $logSql = "INSERT INTO troubleticket_log (troubleticketid, message, userid, mydatetime, logaction, logtype, newstate) VALUES(?, ?, ?, NOW(), '2', ?, '')";
            $this->db->query($logSql, $row['id'], $message, $ticket->getAssignedToId(), TicketLog::TYPE_MSG);
            if ($this->settings->get('plugin_autoclose_Notify Customer')) {
                $mailGateway->mailMessage(  $message,
                                            $this->settings->get('Support E-mail'),
                                            $this->settings->get('Support E-mail'),
                                            $userid,
                                            '',
                                            "[".$ticket->getIdLabel()."] ".$this->user->lang("Support ticket has been closed"),
                                            3,
                                            0,
                                            'notifications',
                                            '',
                                            '',
                                            MAILGATEWAY_CONTENTTYPE_PLAINTEXT);
            }
            $numTicketsClosed++;
        }
        array_unshift($messages, $this->user->lang('%s ticket(s) closed', $numTicketsClosed));

        $numNotified=0;
        if($this->settings->get('plugin_autoclose_Pre-Notify Customer')){
            $sql = "SELECT * FROM `troubleticket` WHERE `status` IN (".implode(', ', $statusWaitingCustomer).") AND autoclose='0' AND `lastlog_datetime` <= DATE_SUB( NOW() , INTERVAL ? DAY )";
            $result = $this->db->query($sql, ($this->settings->get('plugin_autoclose_Days to trigger autoclose') - $this->settings->get('plugin_autoclose_Days to trigger Pre-Notify autoclose')));
            while ($row = $result->fetch()) {
                $ticket = new Ticket($row['id']);
                $ticketNotifications= new TicketNotifications();
                $ticketNotifications->PreNotifyAutoClose($ticket);
                $ticket->setAutoClose(1);
                $ticket->save();
                $numNotified++;
            }
        }
        array_unshift($messages, $this->user->lang('%s ticket(s) notified', $numNotified));
        return $messages;
    }

    function output() { }

	function dashboard()
	{
        $statusWaitingCustomer = StatusAliasGateway::getInstance($this->user)->getTicketStatusIdsFor(TICKET_STATUS_WAITINGONCUSTOMER);
		$query = "SELECT COUNT(*) AS tickets FROM `troubleticket` WHERE `status` IN (".implode(', ', $statusWaitingCustomer).") AND `lastlog_datetime` <= DATE_SUB( NOW() , INTERVAL ? DAY )";
        $result = $this->db->query($query, $this->settings->get('plugin_autoclose_Days to trigger autoclose'));
        $row = $result->fetch();
        if (!$row) {
            $row['tickets'] = 0;
        }

        return $this->user->lang('Number of tickets pending close: %d', $row['tickets']);
	}

    function pendingItems()
    {
        // Notify
        $statusWaitingCustomer = StatusAliasGateway::getInstance($this->user)->getTicketStatusIdsFor(TICKET_STATUS_WAITINGONCUSTOMER);
        $query = "SELECT * FROM `troubleticket` WHERE `status` IN (".implode(', ', $statusWaitingCustomer).") AND autoclose='0' AND `lastlog_datetime` <= DATE_SUB( NOW() , INTERVAL ? DAY )";
        $result = $this->db->query($query, ($this->settings->get('plugin_autoclose_Days to trigger autoclose') - $this->settings->get('plugin_autoclose_Days to trigger Pre-Notify autoclose')));

        $tickets = array();
        while ($row = $result->fetch()) {
            $user = new User($row['userid']);
            $tmp = array (
                'ticketid' => '<a href="index.php?fuse=support&view=viewtickets&controller=ticket&id=' . $row['id'] . '">' . $row['id'] . '</a>',
                'subject' => $row['subject'],
                'customer' => '<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=' . $user->getId() . '">' . $user->getFullName() . '</a>',
                'status' => 'notifying'
            );
            $tickets[] = $tmp;
        }

        // Closed
        $query = "SELECT * FROM `troubleticket` WHERE `status` IN (".implode(', ', $statusWaitingCustomer).") AND autoclose='1' AND `lastlog_datetime` <= DATE_SUB( NOW() , INTERVAL ? DAY )";
        $result = $this->db->query($query, $this->settings->get('plugin_autoclose_Days to trigger autoclose'));
        while ($row = $result->fetch()) {
            $user = new User($row['userid']);

            $tmp = array (
                'ticketid' => '<a href="index.php?fuse=support&view=viewtickets&controller=ticket&id=' . $row['id'] . '">' . $row['id'] . '</a>',
                'subject' => $row['subject'],
                'customer' => '<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=' . $user->getId() . '">' . $user->getFullName() . '</a>',
                'status' => 'closing'
            );
            $tickets[] = $tmp;
        }

        $returnArray = array();
        $returnArray["totalcount"] = count($tickets);
        $returnArray["data"] = $tickets;
        $returnArray['headers'] = array (
            $this->user->lang('Ticket ID'),
            $this->user->lang('Subject'),
            $this->user->lang('Customer'),
            $this->user->lang('Status'),
        );
        return $returnArray;
    }
}
