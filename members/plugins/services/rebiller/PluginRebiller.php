<?php
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/admin/models/StatusAliasGateway.php' ;
require_once 'modules/billing/models/BillingGateway.php';
require_once 'modules/billing/models/Currency.php';
require_once 'library/CE/NE_MailGateway.php';
/**
* @package Plugins
*/
class PluginRebiller extends ServicePlugin
{
    protected $featureSet = 'billing';
    public $hasPendingItems = true;
    public $permission = 'billing_view';

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Invoice Reminder'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, late invoice reminders will be sent out to customers. This service should only run once per day to avoid sending reminders twice in the same day.'),
                'value'         => '0',
            ),
            lang('Summary E-mail')     => array(
                'type'          => 'textarea',
                'description'   => lang('E-mail addresses to which a summary of the notified invoices will be sent.  (Leave blank if you do not wish to receive a summary)'),
                'value'         => '',
            ),
            lang('Summary E-mail Subject')     => array(
                'type'          => 'text',
                'description'   => lang('E-mail subject for the summary notification.'),
                'value'         => 'Invoice Reminder Summary',
            ),
            lang('Days to trigger reminder')       => array(
                'type'          => 'text',
                'description'   => lang('<b>For late invoice remainder</b>: Enter the number of days after the due date to send a late invoice reminder.  You may enter more than one day by seperating the numbers with a comma.  <strong><i>Note: A number followed by a + sign indicates to send for all days greater than the previous number or use * to send reminders each day.</i></strong><br><br><b>For upcoming invoice reminder</b>: Enter the number of days before the due date to send an upcoming invoice reminder. You may enter more than one day by seperating the numbers by commas: these numbers must start with a - sign (negative numbers). <strong><i>Note: this only works if the invoice is already generated</i></strong>.<br><br><b>Example</b>: -10,-5,-1,1,5,10+ would send on the tenth days before the due date, five days before, one day before, one day late, five days late and ten or more days late'),
                'value'         => '-10,-5,-1,1,5,10+',
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
        $arrDays = explode(',', $this->settings->get('plugin_rebiller_Days to trigger reminder'));

        $billingGateway = new BillingGateway($this->user);
        $invoicesList = $billingGateway->getUnpaidInvoicesDueDays($arrDays);

        $invoicesNotified = array(
            "Late"     => array(),
            "Upcoming" => array()
        );

        foreach ($invoicesList as $invoiceData) {
            $user = new User($invoiceData['userId']);
            if ($invoiceData['days'] > 0) {
                if ($invoiceData['autopayment'] == 1) {
                    $countTransactions = $billingGateway->countInvoiceTransactions($invoiceData['invoiceId']);
                    $usersWithInvalidCreditCards = $billingGateway->usersWithInvalidCreditCards();
                    if ($countTransactions == 0 && !in_array($invoiceData['userId'], $usersWithInvalidCreditCards)) {
                        continue;
                    }
                }
                $invoicesNotified["Late"][] = '#' . $invoiceData['invoiceId'] . ' - ' . $user->getFullName(true);
                $billingGateway->sendInvoiceEmail($invoiceData['invoiceId'], "Overdue Invoice Template");
            } else {
                $invoicesNotified["Upcoming"][] = '#' . $invoiceData['invoiceId'] . ' - ' . $user->getFullName(true);
                $billingGateway->sendInvoiceEmail($invoiceData['invoiceId'], "Invoice Template");
            }
        }

        if ($this->settings->get('plugin_rebiller_Summary E-mail') != "") {
            $summaryEmail = '';
            if (count($invoicesNotified["Late"]) > 0) {
                $summaryEmail .= $this->user->lang("Late invoices").":\n";
                $summaryEmail .= implode("\n", $invoicesNotified["Late"])."\n";
                $summaryEmail .= "\n";
            }

            if (count($invoicesNotified["Upcoming"]) > 0) {
                $summaryEmail .= $this->user->lang("Upcoming invoices").":\n";
                $summaryEmail .= implode("\n", $invoicesNotified["Upcoming"])."\n";
                $summaryEmail .= "\n";
            }

            if ($summaryEmail != '') {
                $summaryEmail = $this->user->lang("Invoice Reminder has emailed the following invoices").":\n"
                    ."\n"
                    .$summaryEmail;
                $destinataries = explode("\r\n", $this->settings->get('plugin_rebiller_Summary E-mail'));

                foreach ($destinataries as $destinatary) {
                    $mailGateway = new NE_MailGateway();
                    $mailGateway->mailMessageEmail(
                        $summaryEmail,
                        $this->settings->get('Support E-mail'),
                        $this->settings->get('Company Name'),
                        $destinatary,
                        "",
                        $this->settings->get('plugin_rebiller_Summary E-mail Subject')
                    );
                }
            }
        }
        return array($this->user->lang('%s invoice reminders were sent', (count($invoicesNotified["Late"]) + count($invoicesNotified["Upcoming"]))));
    }

    function pendingItems()
    {
        $currency = new Currency($this->user);
        $userActiveStatuses = StatusAliasGateway::userActiveAliases($this->user);
        // Select all customers that have an invoice that needs generation
        $query = "SELECT i.`id`,i.`customerid`, i.`amount`, i.`balance_due`, (TO_DAYS(NOW()) - TO_DAYS(i.`billdate`)) AS days "
            ."FROM `invoice` i, `users` u "
            ."WHERE (i.`status`='0' OR i.`status`='5') AND u.`id`=i.`customerid` AND u.`status` IN (".implode(', ', $userActiveStatuses).") AND TO_DAYS(NOW()) - TO_DAYS(i.`billdate`) > 0 AND i.`subscription_id` = '' "
            ."ORDER BY i.`billdate`";
        $result = $this->db->query($query);
        $returnArray = array();
        $returnArray['data'] = array();
        while ($row = $result->fetch()) {
            $user = new User($row['customerid']);
            $tmpInfo = array();
            $tmpInfo['customer'] = '<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=' . $user->getId() . '">' . $user->getFullName() . '</a>';
            $tmpInfo['invoice_number'] = '<a href="index.php?controller=invoice&fuse=billing&frmClientID=' . $user->getId() . '&view=invoice&invoiceid=' . $row['id'] . ' ">' . $row['id'] . '</a>';
            $tmpInfo['amount'] = $currency->format($this->settings->get('Default Currency'), $row['amount'], true);
            $tmpInfo['balance_due'] = $currency->format($this->settings->get('Default Currency'), $row['balance_due'], true);
            $tmpInfo['days'] = $row['days'];
            $returnArray['data'][] = $tmpInfo;
        }
        $returnArray["totalcount"] = count($returnArray['data']);
        $returnArray['headers'] = array (
            $this->user->lang('Customer'),
            $this->user->lang('Invoice Number'),
            $this->user->lang('Amount'),
            $this->user->lang('Balance Due'),
            $this->user->lang('Days Overdue'),
        );

        return $returnArray;
    }

    function output()
    {
    }

    function dashboard()
    {
        $userActiveStatuses = StatusAliasGateway::userActiveAliases($this->user);
        $query = "SELECT COUNT(*) AS overdue "
            ."FROM `invoice` i, `users` u "
            ."WHERE (i.`status`='0' OR i.`status`='5') AND u.`id`=i.`customerid` AND u.`status` IN (".implode(', ', $userActiveStatuses).") AND TO_DAYS(NOW()) - TO_DAYS(i.`billdate`) > 0 AND i.`subscription_id` = '' ";
        $result = $this->db->query($query);
        $row = $result->fetch();
        if (!$row) {
            $row['overdue'] = 0;
        }
        return $this->user->lang('Number of invoices overdue: %d', $row['overdue']);
    }
}