<?php
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/billing/models/BillingType.php';
require_once 'modules/billing/models/Invoice_EventLog.php';
require_once 'modules/billing/models/BillingGateway.php';

/**
* @package Plugins
*/
class PluginInvoicegenerator extends ServicePlugin
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
                'value'         => lang('Invoice Generator'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, invoices will automatically be created.'),
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
        $allAtOnce = true;
        $billingGateway->generate_invoice($initial, $allAtOnce);
        if (isset($this->session->all_invoices)){
              $numCustomers = count($this->session->all_invoices);
        }
        $billingGateway->send_process_invoice_summary("generate");
        $billingGateway->reportInvalidRecurringFees();

        $this->settings->updateValue("LastDateGenerateInvoices", time());

        array_unshift($messages, "$numCustomers customer(s) were invoiced");
        return $messages;
    }

    function pendingItems()
    {
        $returnArray = array();
        $returnArray['data'] = array();
        $returnArray['totalcount'] = count($returnArray['data']);
        $returnArray['headers'] = array (
            $this->user->lang('Customer'),
            $this->user->lang('Due Date')
        );

        return $returnArray;
    }

    function output() { }

    function dashboard()
    {
        $row['customers'] = 0;
        return $this->user->lang('Number of customers to be billed: %d', $row['customers']);
    }
}
?>
