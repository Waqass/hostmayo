<?php
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/admin/models/StatusAliasGateway.php' ;
include_once 'modules/billing/models/BillingTypeGateway.php';
require_once 'modules/billing/models/BillingGateway.php';
require_once 'modules/billing/models/Invoice.php';
require_once 'modules/billing/models/InvoiceEntry.php';
/**
* @package Plugins
*/
class PluginLatefee extends ServicePlugin
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
                'value'         => lang('Late Fee'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, late invoices will be charged with additional late fees. This service should only run once per day, and preferable if run before <b>Invoice Reminder</b> service, to avoid sending reminders without the late fee.'),
                'value'         => '0',
            ),
            lang('Default late fee charge')  => array(
                'type'          => 'text',
                'description'   => lang('Enter a default amount to be charged as a late fee on invoices, if the product has not defined its own late fee. <br><strong><i>Note: Leave this field empty if you want to avoid default late fee.</i></strong>'),
                'value'         => '',
            ),
            lang('Day to charge late fee')  => array(
                'type'          => 'text',
                'description'   => lang('Enter the number of days after the due date to charge a late fee on invoices. <br><strong><i>Note: Please enter one number only.</i></strong><br><br><b>Example</b>: 10 would charge late fee when ten or more days late.'),
                'value'         => '10',
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
        $invoicesList = array();
        $arrDays = array($this->settings->get('plugin_latefee_Day to charge late fee').'+');

        $billingGateway = new BillingGateway($this->user);
        $invoicesList = $billingGateway->getUnpaidInvoicesDueDays($arrDays);

        foreach ($invoicesList as $invoiceData) {
            if ($invoiceData['autopayment'] == 1) {
                $countTransactions = $billingGateway->countInvoiceTransactions($invoiceData['invoiceId']);
                $usersWithInvalidCreditCards = $billingGateway->usersWithInvalidCreditCards();
                if ($countTransactions == 0 && !in_array($invoiceData['userId'], $usersWithInvalidCreditCards)) {
                    continue;
                }
            }

            $invoice = new Invoice($invoiceData['invoiceId']);
            if (!$invoice->hasLateFees()) {
                $packageIDs = $invoice->getAllPackageIDs();

                $chargedLateFee = false;
                foreach ($packageIDs AS $packageID) {
                    $userPackage = new UserPackage($packageID);
                    $lateFee = $userPackage->getLateFee();
                    if ($lateFee === false || $lateFee == '') {
                        $lateFee = $this->settings->get('plugin_latefee_Default late fee charge');
                    }

                    if ($lateFee != '' && $lateFee > 0.00) {
                        $this->addLateFeeInvoiceEntry($invoiceData, $lateFee, $packageID);
                        $chargedLateFee = true;
                    }
                }
                if (!$chargedLateFee) {
                    $lateFee = $this->settings->get('plugin_latefee_Default late fee charge');
                    if ($lateFee != '' && $lateFee > 0.00) {
                        $this->addLateFeeInvoiceEntry($invoiceData, $lateFee, 0);
                    }
                }
                $invoice->recalculateInvoice();
                $invoice->update();
            }
        }

        return array($this->user->lang('%s invoice reminders were sent', count($invoicesList)));
    }

    function addLateFeeInvoiceEntry($invoiceData, $lateFee, $packageID)
    {
        $params = array(
            'm_CustomerID'        => $invoiceData['userId'],
            'm_Description'       => $this->user->lang('Late Fee'),
            'm_Detail'            => $this->user->lang('Late Fee'),
            'm_InvoiceID'         => $invoiceData['invoiceId'],
            'm_Date'              => date("Y-m-d"),
            'm_BillingTypeID'     => BILLINGTYPE_LATE_FEE,
            'm_IsProrating'       => 0,
            'm_Price'             => $lateFee,
            'm_Recurring'         => 0,
            'm_AppliesToID'       => $packageID,
            'm_Setup'             => 0,
            'm_Taxable'           => 0,
            'm_TaxAmount'         => 0,
        );
        $invoiceEntry = new InvoiceEntry($params);
        $invoiceEntry->updateRecord();
    }

    function pendingItems()
    {
        $currency = new Currency($this->user);
        // Select all customers that have an invoice that needs generation
        $userActiveStatuses = StatusAliasGateway::userActiveAliases($this->user);
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

    function output() { }

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
?>
