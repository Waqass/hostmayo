<?php
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/billing/models/BillingType.php';
require_once 'modules/billing/models/Invoice_EventLog.php';
require_once 'modules/billing/models/BillingGateway.php';

/**
* @package Plugins
*/
class PluginCreditcardpaymentsprocessor extends ServicePlugin
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
                'value'         => lang('Credit Card Payments Processor'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, will process your customer\'s credit cards for invoices that are due or past-due. This will only process your customers whose credit card is stored outside of ClientExec.'),
                'value'         => '0',
            ),
            lang('Include invoices previously declined')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, will also process your customer\'s credit cards for invoices that are due or past-due and have declined transactions.'),
                'value'         => '0',
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
        $messages = array();
        $numCustomers = 0;

        $billingGateway = new BillingGateway($this->user);
        $initial = 0;
        $includeDeclined = $this->settings->get('plugin_creditcardpaymentsprocessor_Include invoices previously declined');
        $passphrase = '';
        $allAtOnce = true;
        $billingGateway->process_invoice($initial, $includeDeclined, $passphrase, $allAtOnce);
        if (isset($this->session->all_invoices)){
              $numCustomers = count($this->session->all_invoices);
        }
        $billingGateway->send_process_invoice_summary("process");

        //$this->settings->updateValue("LastDateGenerateInvoices", time());

        array_unshift($messages, "$numCustomers customer(s) were charged");
        return $messages;
    }

    function pendingItems()
    {


        $returnArray = array();
        $returnArray['data'] = array();
        $returnArray['totalcount'] = count($returnArray['data']);
        $returnArray['headers'] = array (
            $this->user->lang('Customer'),
            $this->user->lang('Charge Date')
        );

        return $returnArray;
    }

    function output() { }

    function dashboard()
    {
        $row['customers'] = 0;
        return $this->user->lang('Number of customers to be charged: %d', $row['customers']);
    }
}
?>
