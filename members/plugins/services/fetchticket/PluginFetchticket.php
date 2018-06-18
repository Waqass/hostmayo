<?php

require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/support/models/EmailRoutingRule.php';

/**
* @package Plugins
*/
class PluginFetchticket extends ServicePlugin
{
    protected $featureSet = 'support';
    public $hasPendingItems = false;

    function getVariables()
    {
        $configuration = Zend_Registry::get('configuration');

	    $enabledType="";
        if (!isset($configuration['modules']['support']['installedVersion'])) {
            $enabledType="hidden";
        } else {
            $enabledType="yesno";
        }

        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Tickets Generator'),
            ),
            lang('Enabled')       => array(
                'type'          => $enabledType,
                'description'   => lang('This is the service that actually does the fetching of E-mails for all E-mail Routing Rules that use the "POP3 fetching" routing type. Only USER authentication mechanism. Be aware that messages on the account will get erased after being imported, or bounced if invalid.'),
                'value'         => '0',
            ),
            lang('Run schedule - Minute')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
                'helpid'        => '8',
            ),
            lang('Run schedule - Hour')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
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
        require_once 'plugins/services/fetchticket/pop3.php';
        require_once 'modules/support/models/EmailRoutingRuleGateway.php';
        include_once 'modules/support/models/EmailGateway.php';

        $messages = array();
        $rulesGateway = new EmailRoutingRuleGateway();
        $ruleIt = $rulesGateway->getRoutingRulesUsingPop();
        $pipeCount = 0;
        while ($rule = $ruleIt->fetch()) {
            $pop = new pop3_class();
            $pop->hostname  = $rule->getPop3Hostname();
            $pop->port      = $rule->getPop3Port();
            $pop->debug     = 0;
            $pop->join_continuation_header_lines = 1; /* Concatenate headers split in multiple lines */
            $username       = $rule->getPop3Username();
            $password       = $rule->getPop3Password();

            if ($error = $pop->open()) {
                $messages[] = new CE_Error($error);
                continue;
            }
            if ($error = $pop->login($username, $password)) {
                $messages[] = new CE_Error($error);
                if ($error = $pop->close()) {
                    $messages[] = new CE_Error($error);
                }
                continue;
            }
            if (!is_array($result = $pop->listMessages('', 1))) {
                $messages[] = new CE_Error($result);
                if ($error = $pop->close()) {
                    $messages[] = new CE_Error($error);
                }
                continue;
            }

            for ($i = 1; $i <= count($result); $i++) {
                $headers = array();
                $body = array();
                if ($error = $pop->retrieveMessage($i, $headers, $body, -1)) {
                    $messages[] = new CE_Error($error);
                    continue;
                }

                $email = implode("\r\n", $headers);
                $email .= "\r\n\r\n".implode("\r\n", $body);

                $ep = new CE_EmailParser($email, EMAILROUTINGRULE_POP3);
                $ep->parse();

                // If there's no from address, ignore the e-mail.
                if ( $ep->getFrom() == '' || $ep->getFrom() == false ) {
                    continue;
                }

                $to = trim($ep->getTo());
                if (!in_array('*', $rule->getEmails()) && !in_array($to, $rule->getEmails())) {
                    continue;
                }

                $emailGateway = new EmailGateway($this->user);
                $emailGateway->parseEmail($email, EMAILROUTINGRULE_POP3);

                // we always need to delete messages when using pop3.
                if ( /*$rule->isPop3DeleteEmails() &&*/ $error = $pop->deleteMessage($i)) {
                    $messages[] = new CE_Error($error);
                    continue;
                }

            }

            if ($error = $pop->close()) {
                $messages[] = new CE_Error($error);
                continue;
            }
            $pipeCount++;
        }
        $messages[] = $this->user->lang('%s POP3 support rules processed', $pipeCount);
        return $messages;
    }

    function output()
    {
    }

    function dashboard()
    {
    }
}
