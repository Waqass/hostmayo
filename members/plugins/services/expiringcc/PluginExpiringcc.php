<?php

require_once 'modules/billing/models/Invoice.php';
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/admin/models/StatusAliasGateway.php' ;
require_once 'modules/clients/models/Client_EventLog.php';
require_once 'modules/support/models/AutoresponderTemplateGateway.php';

/**
* @package Plugins
*/
class PluginExpiringcc extends ServicePlugin
{
    protected $featureSet = 'billing';
    public $hasPendingItems = false;
    public $permission = 'billing_view';

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Expiring CC Notifier'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, notify customers with expiring cards at the end of the month.  When run it will E-mail customers with a credit card expiring that month and generate a ticket for credit cards expired in the previous month.<br><b>NOTE:</b> This service is intended to run only once per month.'),
                'value'         => '0',
            ),
            lang('Delete Expired CC') => array(
                'type'          => 'yesno',
                'description'   => lang('Delete expired credit card if there are no recurring charges and no unpaid invoices.'),
                'value'         => '0',
            ),
            lang('Generate Ticket Upon Expiration') => array(
                'type'          => 'yesno',
                'description'   => lang('Generates a ticket in the customers account when their credit card expires.  Useful for allowing administrators to follow up on the expired credit card.'),
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
                'value'         => '1',
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
        require_once 'modules/support/models/TicketGateway.php';
        require_once 'modules/support/models/Ticket.php';
        require_once 'library/CE/NE_MailGateway.php';
        $messages = array();

        if ($this->settings->get('plugin_expiringcc_Delete Expired CC')) {
            $this->_deleteExpiredCC();
        }

        $arrExpiringCCwithActiveBilling = $this->_getExpiringCCUsers(false);
        $templategateway = new AutoresponderTemplateGateway();
        $template = $templategateway->getEmailTemplateByName("Expiring CC Template");

        /* Handle notifying customers whose credit cards will soon be expiring */
        $mailGateway = new NE_MailGateway();
        foreach ($arrExpiringCCwithActiveBilling as $userid) {
            $objUser = new User($userid);
            $strMessageArr = $template->getContents();
            $strSubjectMessage = $template->getSubject();

            $templateID = $template->getId();
            if($templateID !== false){
                include_once 'modules/admin/models/Translations.php';
                $languages = CE_Lib::getEnabledLanguages();
                $translations = new Translations();
                $languageKey = ucfirst(strtolower($objUser->getRealLanguage()));
                CE_Lib::setI18n($languageKey);

                if(count($languages) > 1){
                    $strSubjectMessage = $translations->getValue(EMAIL_SUBJECT, $templateID, $languageKey, $strSubjectMessage);
                    $strMessageArr = $translations->getValue(EMAIL_CONTENT, $templateID, $languageKey, $strMessageArr);
                }
            }

            $strMessageArr = str_replace("[BILLINGEMAIL]", $this->settings->get("Billing E-mail"), $strMessageArr);
            $strMessageArr = str_replace(array("[CLIENTAPPLICATIONURL]","%5BCLIENTAPPLICATIONURL%5D"), CE_Lib::getSoftwareURL(), $strMessageArr);
            $strMessageArr = str_replace(array("[COMPANYNAME]","%5BCOMPANYNAME%5D"), $this->settings->get("Company Name"), $strMessageArr);
            $strMessageArr = str_replace(array("[COMPANYADDRESS]","%5BCOMPANYADDRESS%5D"), $this->settings->get("Company Address"), $strMessageArr);
            $strMessageArr = str_replace("[CLIENTNAME]", $objUser->getFullName(true), $strMessageArr);
            $strMessageArr = str_replace("[FIRSTNAME]", $objUser->getFirstName(), $strMessageArr);
            $strMessageArr = str_replace("[CLIENTEMAIL]", $objUser->getEmail(), $strMessageArr);
            $strMessageArr = str_replace(array("[FORGOTPASSWORDURL]","%5BFORGOTPASSWORDURL%5D"), CE_Lib::getForgotUrl(), $strMessageArr);
            $strMessageArr = str_replace("[CCLASTFOUR]", $objUser->getCCLastFour(), $strMessageArr);
            $strMessageArr = str_replace("[CCEXPDATE]", $objUser->getCCMonth()."/".$objUser->getCCYear(), $strMessageArr);
            $strMessageArr = CE_Lib::ReplaceCustomFields($this->db, $strMessageArr,$userid, $this->settings->get('Date Format'));

            $strSubjectMessage = str_replace("[BILLINGEMAIL]", $this->settings->get("Billing E-mail"), $strSubjectMessage);
            $strSubjectMessage = str_replace(array("[CLIENTAPPLICATIONURL]","%5BCLIENTAPPLICATIONURL%5D"), CE_Lib::getSoftwareURL(), $strSubjectMessage);
            $strSubjectMessage = str_replace(array("[COMPANYNAME]","%5BCOMPANYNAME%5D"), $this->settings->get("Company Name"), $strSubjectMessage);
            $strSubjectMessage = str_replace(array("[COMPANYADDRESS]","%5BCOMPANYADDRESS%5D"), $this->settings->get("Company Address"), $strSubjectMessage);
            $strSubjectMessage = str_replace("[CLIENTNAME]", $objUser->getFullName(true), $strSubjectMessage);
            $strSubjectMessage = str_replace("[FIRSTNAME]", $objUser->getFirstName(), $strSubjectMessage);
            $strSubjectMessage = str_replace("[CLIENTEMAIL]", $objUser->getEmail(), $strSubjectMessage);
            $strSubjectMessage = str_replace(array("[FORGOTPASSWORDURL]","%5BFORGOTPASSWORDURL%5D"), CE_Lib::getForgotUrl(), $strSubjectMessage);
            $strSubjectMessage = str_replace("[CCLASTFOUR]", $objUser->getCCLastFour(), $strSubjectMessage);
            $strSubjectMessage = str_replace("[CCEXPDATE]", $objUser->getCCMonth()."/".$objUser->getCCYear(), $strSubjectMessage);

            $mailerResult = $mailGateway->mailMessage(  $strMessageArr,
                                                        $this->settings->get("Billing E-mail"),
                                                        $this->settings->get("Billing Name"),
                                                        $objUser->getId(),
                                                        '',
                                                        $strSubjectMessage,
                                                        3,
                                                        0,
                                                        'notifications',
                                                        '',
                                                        '',
                                                        MAILGATEWAY_CONTENTTYPE_HTML);
            if (!is_a($mailerResult, 'CE_Error')) {
                $clientLog = Client_EventLog::newInstance(false, $userid, $userid, CLIENT_EVENTLOG_SENTCCEXPIRATIONEMAIL, $this->user->getId());
                $clientLog->save();
            }
        }
        $messages[] = count($arrExpiringCCwithActiveBilling)." ".$this->user->lang("customers notified of expiring credit cards");

        if ($this->settings->get('plugin_expiringcc_Generate Ticket Upon Expiration')) {
            $tTimeStamp = date('Y-m-d H-i-s');
            $arrExpiredCCwithActiveBilling = $this->_getExpiringCCUsers(true);
            foreach ($arrExpiredCCwithActiveBilling as $userid) {
                $cTickets = new TicketGateway();
                if ($cTickets->GetTicketCount() == 0) {
                    $ticketId = $this->settings->get('Support Ticket Start Number');
                } else {
                    $ticketId = '';
                }
                $objUser = new User($userid);
                $subject = $this->user->lang("Credit Card Expired")." - ".$objUser->getFullName();
                $message = $this->user->lang("** This ticket has been generated automatically **\n\n%s's credit card on file ending with %s has expired on %s.", $objUser->getFullName(), $objUser->getCCLastFour(), $objUser->getCCMonth()."/".$objUser->getCCYear());
                $cTicket = new Ticket();
                $cTicket->setId($ticketId);
                $cTicket->setUser($objUser);
                $cTicket->setMethod(1);
                $cTicket->setSubject($subject);
                $cTicket->setPriority(1);
                $cTicket->setStatus(1);

                require_once 'modules/support/models/TicketTypeGateway.php';
                $ticketTypeGateway = new TicketTypeGateway();
                $billingTicketType = $ticketTypeGateway->getBillingTicketType();
                $cTicket->setMessageType($billingTicketType);
                $cTicket->setAssignedToId(0);
                $cTicket->SetDateSubmitted($tTimeStamp);
                $cTicket->SetLastLogDateTime($tTimeStamp);
                if ($targetDeptId = $billingTicketType->getTargetDept()) {
                    require_once 'modules/support/models/Department.php';
                    $dep = new Department($targetDeptId);
                    $staff = null;
                    if ($targetStaffId = $billingTicketType->getTargetStaff()) {
                        $staff = new User($targetStaffId);
                    } elseif ($dep->getAssignToMember()) {
                        $staff = $dep->getMember();
                    }
                    $cTicket->assign($dep, $staff);
                }
                $cTicket->save();
                $cTicket->addInitialLog($message, $tTimeStamp, $objUser);
            }
            $messages[] = count($arrExpiredCCwithActiveBilling)." ".$this->user->lang("expired credit card ticket(s) generated");
        }

        return $messages;
    }

    function output()
    {
        /*
        $this->view->define(array('expiringcc_table' => 'views/admin/expiringcc_table.tpl'));
        $this->view->define_dynamic('expiringcc', 'expiringcc_table');
        $rowclass="odd";

        $arrExpiring = $this->_getExpiringCCUsers(false);
        $arrExpired = $this->_getExpiringCCUsers(true);

        foreach ($arrExpiring as $userid) {
            $user = new User($userid);

            $this->view->assign(array(
                'trClass'                 => $rowclass,
                'expiringcc_customer'     => $user->getFullName(),
                'expiringcc_customerid'   => $user->getId(),
                'expiringcc_last4'        => $user->getCCLastFour(),
            ));

            //alternate row color
            if ($rowclass=="odd") $rowclass="even";
            else $rowclass="odd";

            $this->view->subst('trash', '.expiringcc');
        }

        if (count($arrExpiring) == 0) {
            $this->view->setkey('NOEXPIRING', "<tr><td colspan=\"5\" align=\"center\" style=\"font-style:italic\">".$this->user->lang('There are no expiring credit cards this month')."</td></tr>");
        } else {
            $this->view->setkey('NOEXPIRING', '');
        }

        foreach ($arrExpired as $userid) {
            $user = new User($userid);

            $this->view->setkey(array(
                'trClass'                => $rowclass,
                'expiredcc_customer'     => $user->getFullName(),
                'expiredcc_customerid'   => $user->getId(),
                'expiredcc_last4'        => $user->getCCLastFour(),
            ));

            //alternate row color
            if ($rowclass=="odd") $rowclass="even";
            else $rowclass="odd";

            $this->view->subst('trash', '.expiredcc');
        }

        if (count($arrExpired) == 0) {
            $this->view->setkey('NOEXPIRED', "<tr><td colspan=\"5\" align=\"center\" style=\"font-style:italic\">".$this->user->lang('There are no credit cards that expired last month.')."</td></tr>");
        } else {
            $this->view->setkey('NOEXPIRED', '');
        }

        $this->view->parse('output', 'expiringcc_table');

        return $this->view->fetch('output');
         */
        return "";
    }

    function dashboard()
    {
        return $this->user->lang("Number of credit cards expiring this month: %d<br>Number of credit cards expired last month: %d", $this->_getExpiringCCUsers(false),$this->_getExpiringCCUsers(true));
    }

    /**
     * Gets users with credit cards that are expiring this month or last month.
     * @param boolean $expired          If true get users that expired last month, otherwise get expiring this month
     * @param boolean $allExpired       If true and $expired get all users that expired
     * @param boolean $activeBilling    If true and $expired and $allExpired get all users that expired and has any active recurring fee or unpaid invoice
     * @return array an array of user ids
     */
    function _getExpiringCCUsers($expired = false, $allExpired = false, $activeBilling = true) {
        $activeBillingCondition = "AND (r.`recurring` = 1 OR i.`status` IN (".INVOICE_STATUS_UNPAID.", ".INVOICE_STATUS_PARTIALLY_PAID.")) ";

        if ($expired) {
            if ($allExpired) {
                $where = "AND (u.`ccyear` < '".date("Y")."' OR (u.`ccyear` = '".date("Y")."' AND u.`ccmonth` < '".date("n")."')) ";
                if($activeBilling){
                    $where .= $activeBillingCondition;
                }
            } else {
                // Get the month and year for last month
                if (date("n") == 1) {
                    $where = "AND u.`ccmonth` = '12' "
                            ."AND u.`ccyear` = '".(date("Y") - 1)."' "
                            .$activeBillingCondition;
                } else {
                    $where = "AND u.`ccmonth` = '".(date("n") - 1)."' "
                            ."AND u.`ccyear` = '".date("Y")."' "
                            .$activeBillingCondition;
                }
            }
        } else {
            $where = "AND u.`ccmonth` = '".date("n")."' "
                    ."AND u.`ccyear` = '".date("Y")."' "
                    .$activeBillingCondition;
        }

        $arrIds = array();

        $userActiveStatuses = StatusAliasGateway::userActiveAliases($this->user);
        $query = "SELECT DISTINCT u.`id` "
                ."FROM `users` u "
                ."LEFT JOIN `recurringfee` r "
                ."ON r.`customerid` = u.`id` "
                ."LEFT JOIN `invoice` i "
                ."ON i.`customerid` = u.`id` "
                ."WHERE u.`status` IN (".implode(', ', $userActiveStatuses).") "
                .$where
                ."AND u.`passphrased`='1' "
                ."AND (u.`data1` != '' OR u.`data3` != '') "
                ."AND u.`data2` != '' "
                ."AND u.`groupid` = '1' ";
        $result = $this->db->query($query);
        while ($row = $result->fetch()) {
            $arrIds[] = $row['id'];
        }
        return $arrIds;
    }

    /**
     * Delete Expired CC information.
     */
    function _deleteExpiredCC() {
        $arrDeleteCC = array();
        $arrExpiredCC = $this->_getExpiringCCUsers(true, true, false);
        $arrExpiredCCActiveBilling = $this->_getExpiringCCUsers(true, true, true);

        foreach ($arrExpiredCC as $userid) {
            if (!in_array($userid, $arrExpiredCCActiveBilling)) {
                $tUser = new User($userid);
                $tUser->clearCreditCardInfo();
                $tUser->save();
            }
        }
    }
}
?>
