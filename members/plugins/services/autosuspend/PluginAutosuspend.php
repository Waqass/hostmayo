<?php

require_once 'library/CE/NE_MailGateway.php';

require_once 'modules/clients/models/UserPackageGateway.php';
require_once 'modules/billing/models/Invoice.php';
require_once 'modules/billing/models/InvoiceEntry.php';
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/admin/models/StatusAliasGateway.php';
require_once 'modules/support/models/TicketGateway.php';
require_once 'modules/support/models/TicketLog.php';
require_once 'modules/support/models/TicketTypeGateway.php';
require_once 'modules/support/models/Ticket_EventLog.php';

/**
 * All plugin variables/settings to be used for this particular service.
 *
 * @return array The plugin variables.
 */

/**
* @package Plugins
*/
class PluginAutosuspend extends ServicePlugin
{
    public $hasPendingItems = true;
    protected $featureSet = 'products';
    private $ticketNotifications;

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Auto Suspend / Unsuspend'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, overdue packages will be suspended when this service is run.'),
                'value'         => '0',
            ),
            lang('E-mail Notifications')       => array(
                'type'          => 'textarea',
                'description'   => lang('When a package requires manual suspension you will be notified at this E-mail address. If packages are suspended when this service is run, a summary E-mail will be sent to this address.'),
                'value'         => '',
            ),
            lang('Suspend Customer')    => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, all customers packages will be suspended if a recurring fee not associated with a package is overdue.'),
                'value'         => '1',
            ),
            lang('Enable Unsuspension') => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, suspended and paid packages will be unsuspended when this service is run.'),
                'value'         => '0',
            ),
            lang('Days Overdue Before Suspending')    => array(
                'type'          => 'text',
                'description'   => lang('Only suspend packages that are this many days overdue. Enter 0 here to disable package suspension'),
                'value'         => '7',
            ),
            lang('Create Ticket') => array(
                'type'          => 'yesno',
                'description'   => lang("When a package is suspended, automatically create a ticket under the user's account and notify him.<br>The ticket contents is defined in the <b>Notify Package Suspension</b> email template at <b><a href='index.php?fuse=admin&controller=settings&view=emailtemplates&settings=mail'>Settings&nbsp;>&nbsp;Email Templates</a></b>"),
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
            lang('Notified Package List') => array(
                'type'          => 'hidden',
                'description'   => lang('Used to store package IDs of manually suspended packages whose E-mail has already been sent.'),
                'value'         => ''
            )
        );

        return $variables;
    }

    /**
     * Execute the order processor.  We'll activate any pending users an then their packages
     * if they are paid and used the signup form.  Manually added packages will be left
     * untouched.
     *
     */
    function execute()
    {
        $gateway = new UserPackageGateway($this->user);

        $messages = array();
        $newPreEmailed = array();
        $autoUnsuspend = array();
        $autoSuspend = array();
        $preEmailed = unserialize($this->settings->get('plugin_autosuspend_Notified Package List'));
        $dueDays = $this->settings->get('plugin_autosuspend_Days Overdue Before Suspending');
        if ( $dueDays !=0 ) {
            $manualSuspend = array();
            $overdueArray = $this->_getOverduePackages();
            $createTicket = $this->settings->get('plugin_autosuspend_Create Ticket');

            if ($createTicket) {
                $templategateway = new AutoresponderTemplateGateway();
                $template = $templategateway->getEmailTemplateByName("Notify Package Suspension");

                $strSubjectEmailStringOriginal = $template->getSubject();
                $strEmailArrOriginal = $template->getContents();

                $templateID = $template->getId();
            }

            $ticketTypeGateway = new TicketTypeGateway();
            $this->ticketNotifications = new TicketNotifications($this->user);
            $billingTicketType = $ticketTypeGateway->getBillingTicketType();

            foreach ($overdueArray as $packageId => $dueDate) {
                $domain = new UserPackage($packageId, array(), $this->user);

                $user = new User($domain->getCustomerId());
                if ($createTicket) {
                    $strSubjectEmailString = $strSubjectEmailStringOriginal;
                    $strEmailArr = $strEmailArrOriginal;

                    if($templateID !== false){
                        include_once 'modules/admin/models/Translations.php';
                        $languages = CE_Lib::getEnabledLanguages();
                        $translations = new Translations();
                        $languageKey = ucfirst(strtolower($user->getRealLanguage()));
                        CE_Lib::setI18n($languageKey);

                        if(count($languages) > 1){
                            $strSubjectEmailString = $translations->getValue(EMAIL_SUBJECT, $templateID, $languageKey, $strSubjectEmailString);
                            $strEmailArr = $translations->getValue(EMAIL_CONTENT, $templateID, $languageKey, $strEmailArr);
                        }
                    }

                    $ticketSubj = $this->replaceMsgGenericTags($strSubjectEmailString);
                    $ticketMsg = $this->replaceMsgGenericTags($strEmailArr);
                }

                if ($gateway->hasServerPlugin($domain->getCustomField("Server Id"), $pluginName)) {
                    $errors = false;
                    try{
                        $domain->suspend(true, true);
                    }catch(Exception $ex){
                        $errors = true;
                    }

                    if($errors){
                        $manualSuspend[] = $domain->getID();
                    }else{
                        $autoSuspend[] = $domain->getID();
                    }

                    if ($createTicket) {
                        if (!is_array($preEmailed) || !in_array($domain->getID(), $preEmailed)) {
                            $this->createTicket($ticketSubj, $ticketMsg, $domain, $dueDate, $billingTicketType);
                        }
                        if($errors){
                            $newPreEmailed[] = $domain->getID();
                        }
                    }
                } elseif (!is_array($preEmailed) || !in_array($domain->getID(), $preEmailed)) {
                    $manualSuspend[] = $domain->getID();
                    $newPreEmailed[] = $domain->getID();
                    if ($createTicket) {
                        $this->createTicket($ticketSubj, $ticketMsg, $domain, $dueDate, $billingTicketType);
                    }
                } else {
                    $newPreEmailed[] = $domain->getID();
                }
            }
            $sendSummary = false;
            $body = $this->user->lang("Autosuspend Service Summary")."\n\n";
            if (sizeof($autoSuspend) > 0) {
                $sendSummary = true;
                $body .= $this->user->lang("Suspended").":\n\n";
                foreach ($autoSuspend as $id) {
                    $domain = new UserPackage($id, array(), $this->user);
                    $user = new User($domain->CustomerId);
                    $body .= $user->getFullName()." => ".$domain->getReference(true)."\n";
                }
                $body .= "\n";
            }
            if (sizeof($manualSuspend) > 0) {
                $sendSummary = true;
                $body .= $this->user->lang("Requires Manual Suspension").":\n\n";
                foreach ($manualSuspend as $id) {
                    $domain = new UserPackage($id, array(), $this->user);
                    $user = new User($domain->CustomerId);
                    $body .= $user->getFullName()." => ".$domain->getReference(true)."\n";
                }
            }

            if ($sendSummary && $this->settings->get('plugin_autosuspend_E-mail Notifications') != "") {
                $mailGateway = new NE_MailGateway();
                $destinataries = explode("\r\n", $this->settings->get('plugin_autosuspend_E-mail Notifications'));
                foreach ($destinataries as $destinatary) {
                    if ( $destinatary != '' ) {
                        $mailGateway->mailMessageEmail( $body,
                            $this->settings->get('Support E-mail'),
                            $this->settings->get('Support E-mail'),
                            $destinatary,
                            false,
                            $this->user->lang("AutoSuspend Service Summary"));
                    }
                }
            }

            // Store the new notified list
            array_unshift($messages, $this->user->lang('%s package(s) suspended', sizeof($autoSuspend)));
        }

        if ( $this->settings->get('plugin_autosuspend_Enable Unsuspension') !=0 ) {
            $manualUnsuspend = array();
            $suspendedArray = $this->_getSuspendedPackages();
            foreach ($suspendedArray as $packageId) {
                $domain = new UserPackage($packageId, array(), $this->user);
                if ($gateway->hasServerPlugin($domain->getCustomField("Server Id"), $pluginName)) {
                    $errors = false;
                    try{
                        $domain->unsuspend(true, true);
                    }catch(Exception $ex){
                        $errors = true;
                    }

                    if($errors){
                        $manualUnsuspend[] = $domain->getID();
                    }else{
                        $autoUnsuspend[] = $domain->getID();
                    }
                } elseif (!is_array($preEmailed) || !in_array($domain->getID(), $preEmailed)) {
                    $manualUnsuspend[] = $domain->getID();
                    $newPreEmailed[] = $domain->getID();
                } else {
                    $newPreEmailed[] = $domain->getID();
                }
            }
            $sendSummary = false;
            $body = $this->user->lang("Autounsuspend Service Summary")."\n\n";
            if (sizeof($autoUnsuspend) > 0) {
                $sendSummary = true;
                $body .= $this->user->lang("Unsuspended").":\n\n";
                foreach ($autoUnsuspend as $id) {
                    $domain = new UserPackage($id, array(), $this->user);
                    $user = new User($domain->CustomerId);
                    $body .= $user->getFullName()." => ".$domain->getReference(true)."\n";
                }
                $body .= "\n";
            }
            if (sizeof($manualUnsuspend) > 0) {
                $sendSummary = true;
                $body .= $this->user->lang("Requires Manual Unsuspension").":\n\n";
                foreach ($manualUnsuspend as $id) {
                    $domain = new UserPackage($id, array(), $this->user);
                    $user = new User($domain->CustomerId);
                    $body .= $user->getFullName()." => ".$domain->getReference(true)."\n";
                }
            }

            if ($sendSummary && $this->settings->get('plugin_autounsuspend_E-mail Notifications') != "") {
                $mailGateway = new NE_MailGateway();
                $destinataries = explode("\r\n", $this->settings->get('plugin_autounsuspend_E-mail Notifications'));
                foreach ($destinataries as $destinatary) {
                    if ( $destinatary != '' ) {
                        $mailGateway->mailMessageEmail( $body,
                            $this->settings->get('Support E-mail'),
                            $this->settings->get('Support E-mail'),
                            $destinatary,
                            false,
                            $this->user->lang("AutoUnsuspend Service Summary"));
                    }
                }
            }
            array_unshift($messages, $this->user->lang('%s package(s) unsuspended', sizeof($autoUnsuspend)));
        }
        if($this->settings->get('plugin_autosuspend_Enable Unsuspension')==0 and $dueDays==0) {
            array_unshift($messages, $this->user->lang('As you disabled both the services. The system has nothing to do.'));
        }
        $this->settings->updateValue("plugin_autosuspend_Notified Package List", serialize($newPreEmailed));
        return $messages;
    }

    function pendingItems()
    {
        $gateway = new UserPackageGateway($this->user);
        $overdueArray = $this->_getOverduePackages();
        $suspendedArray = $this->_getSuspendedPackages();
        $returnArray = array();
        $returnArray['data'] = array();
        foreach ($overdueArray as $packageId => $dueDate) {
            $domain = new UserPackage($packageId, array(), $this->user);
            $user = new User($domain->CustomerId);
            if ($gateway->hasServerPlugin($domain->getCustomField("Server Id"), $pluginName)) {
                $auto = "No";
            } else {
                $auto = "<span style=\"color:red\"><b>Yes</b></span>";
            }

            $tmpInfo = array();
            $tmpInfo['customer'] = '<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=' . $user->getId() . '">' . $user->getFullName() . '</a>';
            $tmpInfo['package_type'] = $domain->getProductGroupName();
            if ( $domain->getProductType() == 3 ) {
                $tmpInfo['package'] = $domain->getProductGroupName();
            } else {
                $tmpInfo['package'] = $domain->getProductName();
            }
            $tmpInfo['domain'] = '<a href="index.php?fuse=clients&controller=userprofile&view=profileproduct&selectedtab=groupinfo&frmClientID=' . $user->getId() . '&id=' . $domain->getId() . '">' . $domain->getReference(true) . '</a>';
            $tmpInfo['date'] = date($this->settings->get('Date Format'), $dueDate);
            $tmpInfo['manual'] = $auto;
            $tmpInfo['status'] = $this->user->lang('Suspending');
            $returnArray['data'][] = $tmpInfo;
        }
        foreach ($suspendedArray as $packageId) {
            $domain = new UserPackage($packageId, array(), $this->user);
            $user = new User($domain->CustomerId);
            if ($gateway->hasServerPlugin($domain->getCustomField("Server Id"),$pluginName)) {
                $auto = "No";
            } else {
                $auto = "<span style=\"color:red\"><b>Yes</b></span>";
            }

            $tmpInfo = array();
            $tmpInfo['customer'] = '<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=' . $user->getId() . '">' . $user->getFullName() . '</a>';
            $tmpInfo['package_type'] = $domain->getProductGroupName();
            if ( $domain->getProductType() == 3 ) {
                $tmpInfo['package'] = $domain->getProductGroupName();
            } else {
                $tmpInfo['package'] = $domain->getProductName();
            }
            $tmpInfo['domain'] = '<a href="index.php?fuse=clients&controller=userprofile&view=profileproduct&selectedtab=groupinfo&frmClientID=' . $user->getId() . '&id=' . $domain->getId() . '">' . $domain->getReference(true) . '</a>';
            $tmpInfo['date'] = '';
            $tmpInfo['manual'] = $auto;
            $tmpInfo['status'] = $this->user->lang('Unsuspending');
            $returnArray['data'][] = $tmpInfo;
        }
        $returnArray["totalcount"] = count($returnArray['data']);
        $returnArray['headers'] = array (
            $this->user->lang('Customer'),
            $this->user->lang('Package Type'),
            $this->user->lang('Package Name'),
            $this->user->lang('Domain'),
            $this->user->lang('Due Date'),
            $this->user->lang('Requires Manual Suspension?'),
            $this->user->lang('Status')
        );
        return $returnArray;
    }

    function output() { }

    function dashboard()
    {
        $overdueArray = $this->_getOverduePackages();

        $autoSuspend = 0;
        $manualSuspend = 0;

        $gateway = new UserPackageGateway($this->user);

        foreach ($overdueArray as $packageId => $dueDate) {
            $domain = new UserPackage($packageId, array(), $this->user);
            if ($gateway->hasServerPlugin($domain->getCustomField("Server Id"), $pluginName)) {
                $autoSuspend++;
            } else {
                $manualSuspend++;
            }
        }

        $message = $this->user->lang('Number of packages pending auto suspension: %d', $autoSuspend);
        $message .= "<br>";

        $message .= $this->user->lang('Number of packages requiring manual suspension: %d', $manualSuspend);

        return $message;
    }

    function _getOverduePackages()
    {
        $query = "SELECT id FROM invoice WHERE (status=0 OR status=5) AND billdate < DATE_SUB( NOW() , INTERVAL ? DAY ) ORDER BY billdate ASC";
        $result = $this->db->query($query, @$this->settings->get('plugin_autosuspend_Days Overdue Before Suspending'));
        $overduePackages = array();
        $overdueCustomers = array();
        $statusGateway = StatusAliasGateway::getInstance($this->user);
        while ($row = $result->fetch()) {
            $invoice = new Invoice($row['id']);
            $user = new User($invoice->getUserID());
            if (!$statusGateway->isActiveUserStatus($user->getStatus())) continue;
            foreach ($invoice->getInvoiceEntries() as $invoiceEntry) {
                if ($invoiceEntry->AppliesTo() != 0) {
                    // Found an overdue package, add it to the list
                    if (!in_array($invoiceEntry->AppliesTo(), array_keys($overduePackages))) {
                        $package = new UserPackage($invoiceEntry->AppliesTo(), array(), $this->user);
                        if (!$statusGateway->isActivePackageStatus($package->status)) continue;
                        // ignore this user package, as we are set to override the autosuspend.
                        if ( $package->getCustomField('Override AutoSuspend') == 1 ) {
                            continue;
                        }
                        $overduePackages[$invoiceEntry->AppliesTo()] = $invoice->getDate('timestamp');
                    }
                } else {
                    // Found an overdue recurring fee that doesn't belong to a package.  Assume the entire client is overdue.
                    if ( !in_array($invoiceEntry->GetCustomerID(), array_keys($overdueCustomers)) && $this->settings->get('plugin_autosuspend_Suspend Customer') == 1) {
                        $overdueCustomers[$invoiceEntry->GetCustomerID()] = $invoice->getDate('timestamp');
                    }
                }
            }
        }

        if ( $this->settings->get('plugin_autosuspend_Suspend Customer') == 1 ) {
            // Now we have all the overdue packages and clients.
            // We'll loop through the clients and all their packages to the list.
            $statusActive = StatusAliasGateway::packageActiveAliases($this->user);
            foreach ($overdueCustomers as $customerId => $dueDate) {
                $query = "SELECT id "
                        ."FROM domains "
                        ."WHERE CustomerID = ? "
                        ."AND status IN (".implode(', ', $statusActive).") ";
                $result = $this->db->query($query, $customerId);
                while ($row = $result->fetch()) {
                    if (!in_array($row['id'], array_keys($overduePackages))) {
                        $overduePackages[$row['id']] = $dueDate;
                    }
                }
            }
        }
        asort($overduePackages);

        return $overduePackages;
    }

    function _getSuspendedPackages()
    {
        $statusSuspended = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(PACKAGE_STATUS_SUSPENDED);
        $userStatusActive = StatusAliasGateway::getInstance($this->user)->getUserStatusIdsFor(USER_STATUS_ACTIVE);

        // Select domains that should not be unsuspended due to an invoice being overdue with entries that do no apply to any domains. (apply to entire account)
        $query = "SELECT d.id AS domain_id "
                ."FROM `domains` d "
                ."WHERE d.`status` IN (".implode(', ', $statusSuspended).") "
                ."AND (EXISTS(SELECT * "
                ."            FROM `invoice` i "
                ."            JOIN `invoiceentry` ie "
                ."            ON (i.id = ie.invoiceid) "
                ."            WHERE (i.`status` = 0 "
                ."            OR i.`status` = 5) "
                ."            AND d.`CustomerID` = i.`customerid` "
                ."            AND ie.appliestoid = 0)) ";
        $result = $this->db->query($query);
        $doNotUnsuspend = array();
        while ($row = $result->fetch())
        {
            $doNotUnsuspend[] = $row['domain_id'];
        }

        // Find all packages eligible for unsuspend
        $query = "SELECT d.id AS domain_id "
                ."FROM `domains` d, `users` u "
                ."WHERE d.CustomerID = u.id "
                ."AND d.`status` IN (".implode(', ', $statusSuspended).") "
                ."AND u.status IN (".implode(', ', $userStatusActive).") "
                ."AND (NOT EXISTS(SELECT * "
                ."                FROM `invoice` i "
                ."                JOIN `invoiceentry` ie "
                ."                ON (i.id = ie.invoiceid) "
                ."                WHERE (i.`status` = 0 "
                ."                OR i.`status` = 5) "
                ."                AND d.`CustomerID` = i.`customerid` "
                ."                AND ie.`appliestoid` = d.id "
                ."                AND billdate < NOW())) ";
        $result = $this->db->query($query);
        $suspendedPackages = array();
        while ($row = $result->fetch())
        {
            // Verify that the packages can be unsuspended
            if (!in_array($row['domain_id'], $doNotUnsuspend))
                $suspendedPackages[] = $row['domain_id'];
        }

        asort($suspendedPackages);
        return $suspendedPackages;
    }

    private function createTicket($ticketSubj, $ticketMsg, $domain, $dueDate, $billingTicketType)
    {
        $date = date('Y-m-d H-i-s');
        $user = new User($domain->getCustomerId());
        $ticket = new Ticket();
        $tickets = new TicketGateway();
        if ($tickets->GetTicketCount() == 0) {
             $id = $this->settings->get('Support Ticket Start Number');
             $ticket->setForcedId($id);
        }
        $ticket->setUser($user);
        $ticket->save();
        $ticketSubj = $this->replaceMsgTags($ticketSubj, $user, $domain, $ticket, $dueDate);
        $ticketMsg = $this->replaceMsgTags($ticketMsg, $user, $domain, $ticket, $dueDate);
        $ticket->setSubject($ticketSubj);
        $ticket->SetDateSubmitted($date);
        $ticket->SetLastLogDateTime($date);
        $ticket->setMethod(1);
        $ticket->SetStatus(TICKET_STATUS_OPEN);
        $ticket->SetMessageType($billingTicketType);
        $ticket->save();

        $supportLog = Ticket_EventLog::newInstance(
           false,
           $user->getId(),
           $ticket->getId(),
           TICKET_EVENTLOG_CREATED,
           $user->getId()
        );
        $supportLog->save();

        // tickets can't be html-formatted
        $ticket->addInitialLog(strip_tags(NE_MailGateway::br2nl($ticketMsg)), $date, $user);

        $this->ticketNotifications->notifyCustomerForNewTicket($user, $ticket, $ticketMsg, '', '', true);
    }

    private function replaceMsgGenericTags($msg)
    {
        $msg = str_replace("[BILLINGEMAIL]", $this->settings->get("Billing E-mail"), $msg);
        $msg = str_replace("[SUPPORTEMAIL]", $this->settings->get("Support E-mail"), $msg);
        $msg = str_replace(array("[CLIENTAPPLICATIONURL]","%5BCLIENTAPPLICATIONURL%5D"), CE_Lib::getSoftwareURL(), $msg);
        $msg = str_replace(array("[COMPANYNAME]","%5BCOMPANYNAME%5D"), $this->settings->get("Company Name"), $msg);
        $msg = str_replace(array("[COMPANYADDRESS]","%5BCOMPANYADDRESS%5D"), $this->settings->get("Company Address"), $msg);
        $msg = str_replace(array("[FORGOTPASSWORDURL]","%5BFORGOTPASSWORDURL%5D"), CE_Lib::getForgotUrl(), $msg);
        return $msg;
    }

    private function replaceMsgTags($msg, $user, $domain, $ticket, $dueDate)
    {
        include_once 'modules/admin/models/Package.php';
        $package = new Package($domain->Plan);
        $msg = str_replace("[CLIENTNAME]", $user->getFullName(true), $msg);
        $msg = str_replace("[FIRSTNAME]", $user->getFirstName(), $msg);
        $msg = str_replace("[LASTNAME]", $user->getLastName(), $msg);
        $msg = str_replace("[CLIENTEMAIL]", $user->getEmail(), $msg);
        $msg = str_replace("[ORGANIZATION]", $user->getOrganization(), $msg);
        $msg = str_replace("[CCLASTFOUR]", $user->getCCLastFour(), $msg);
        $msg = str_replace("[CCEXPDATE]", $user->getCCMonth()."/".$user->getCCYear(), $msg);
        $msg = CE_Lib::ReplaceCustomFields($this->db, $msg,$user->getId(), $this->settings->get('Date Format'), $domain->getId());
        $msg = str_replace("[PACKAGEID]", $domain->getId(), $msg);
        include_once 'modules/admin/models/Translations.php';
        $languages = CE_Lib::getEnabledLanguages();
        $translations = new Translations();

        $languageKey = ucfirst(strtolower($user->getRealLanguage()));
        CE_Lib::setI18n($languageKey);

        $msg = str_replace("[PACKAGENAME]", $domain->getReference(true, true, '', $languageKey), $msg);
        if(count($languages) > 1){
            $planname = $translations->getValue(PRODUCT_NAME, $package->getId(), $languageKey, $package->planname);
            $msg = str_replace("[PLANNAME]", $planname, $msg);
        }else{
            $msg = str_replace("[PLANNAME]", $package->planname, $msg);
        }
        $msg = str_replace("[TICKETNUMBER]", $ticket->getId(), $msg);
        $msg = str_replace("[DATE]", date($this->settings->get('Date Format'), $dueDate), $msg);
        return $msg;
    }
}
