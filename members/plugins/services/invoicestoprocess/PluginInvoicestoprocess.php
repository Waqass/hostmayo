<?php

require_once 'modules/billing/models/BillingGateway.php';
require_once 'library/CE/NE_MailGateway.php';

/**
* @package Plugins
*/
class PluginInvoicestoprocess extends ServicePlugin
{
    public $hasPendingItems = false;

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Invoices To Process Today'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, an email will be sent to the provided email addresses, notifying about how many invoices need to be processed today. If there are no invoices needing to be processed today, no email will be sent. <br><b>NOTE:</b> Only run once per day to avoid duplicate E-mails.'),
                'value'         => '0',
            ),
            lang('Include invoices previously declined')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, will also take in count your customer\'s invoices that are due or past-due and have declined transactions.'),
                'value'         => '0',
            ),
            lang('E-mails')     => array(
                'type'          => 'textarea',
                'description'   => lang('E-mail addresses to be notified.'),
                'value'         => '',
            ),
            lang('E-mail Subject')     => array(
                'type'          => 'text',
                'description'   => lang('E-mail subject for the notification.'),
                'value'         => 'Invoices To Process Today',
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
        $billingGateway = new BillingGateway($this->user);
        $includeDeclined = $this->settings->get('plugin_invoicestoprocess_Include invoices previously declined');
        $result = $billingGateway->get_invoices_who_needs_to_charge_cc($includeDeclined);
        $count = $result->getNumRows();

        if($this->settings->get('plugin_invoicestoprocess_E-mails') != "" && $count > 0){
            $destinataries = explode("\r\n", $this->settings->get('plugin_invoicestoprocess_E-mails'));
            $mailGateway = new NE_MailGateway();
            $EmailMessage = $this->user->lang('There are currently %s invoice(s) waiting to be processed.', $count);
            if($includeDeclined){
                $EmailMessage .= ' '.$this->user->lang('Invoices with previously declined transactions are also taken in count.');
            }else{
                $EmailMessage .= ' '.$this->user->lang('Invoices with previously declined transactions are not taken in count.');
            }

            foreach($destinataries as $destinatary){
                $mailGateway->mailMessageEmail(
                    $EmailMessage,
                    $this->settings->get('Support E-mail'),
                    $this->settings->get('Company Name'),
                    $destinatary,
                    "",
                    $this->settings->get('plugin_invoicestoprocess_E-mail Subject')
                );
            }
        }

        array_unshift($messages, $EmailMessage);
        return $messages;
    }
}
?>
