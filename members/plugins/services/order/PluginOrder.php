<?php

require_once 'library/CE/NE_MailGateway.php';

require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/admin/models/Package.php';
require_once 'modules/admin/models/PluginGateway.php';

/**
* @package Plugins
*/
class PluginOrder extends ServicePlugin
{
    protected $featureSet = 'products';
    public $hasPendingItems = false;

    /**
     * All plugin variables/settings to be used for this particular service.
     *
     * @return array The plugin variables.
     */
    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Order Processor'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, new orders will be activated and processed when this service is run.  Please note that manually added packages will not be processed.'),
                'value'         => '0',
            ),
            lang('E-mail Notifications')       => array(
                'type'          => 'textarea',
                'description'   => lang('When a domain requires manual registration or transfer, or an account requires manual setup you will be notified at this E-mail address.'),
                'value'         => '',
            ),
            lang('Activate Cancelled Users')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, new orders from cancelled customers will also be activated.'),
                'value'         => '0',
            ),
            lang('Activate Manually Added Packages')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, new packages added by a staff member will also be activated when they have been paid.'),
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


    /**
     * Execute the order processor.  We'll activate any pending users and then their packages
     * if they are paid and used the signup form.  Manually added packages will be left
     * untouched.
     *
     */
    function execute()
    {
        include_once 'modules/admin/models/StatusAliasGateway.php' ;

        $statusGateway = StatusAliasGateway::getInstance($this->user);
        $statusPending = $statusGateway->getUserStatusIdsFor(USER_STATUS_PENDING);
        $statusCancelled = $statusGateway->getUserStatusIdsFor(USER_STATUS_CANCELLED);
        $statusActive = $statusGateway->getUserStatusIdsFor(USER_STATUS_ACTIVE);
        $messages = array();
        $numOrdersProcessed = 0;
        // We'll select all packages that allow automatic activation
        $query = "SELECT id FROM package WHERE automaticactivation = 1";
        $result = $this->db->query($query);
        while ($row = $result->fetch()) {
            $automaticactivation[$row['id']] = $row['id'];
        }

        // We'll select all packages that are pending and weren't manually added
        $statusPackagePending = $statusGateway->getPackageStatusIdsFor(PACKAGE_STATUS_PENDING);
        $query = "SELECT id, Plan, signup FROM domains WHERE status IN (".implode(', ', $statusPackagePending).")";

        // If we are not to activate manually added packages, then it must be from sign up.
        if ( $this->settings->get('plugin_order_Activate Manually Added Packages') == '0' ) {
            $query .= ' AND signup = 1';
        } else {
            // manually added package, have to check this, as we set it to 2 later on if it fails.
            $query .= ' AND signup <> 2';
        }

        $result = $this->db->query($query);
        while ($row = $result->fetch()) {
            $domain = new UserPackage($row['id'], array(), $this->user);
            if ($domain->CustomerId == 0) continue; // prevents orphaned domains from creating many users
            $user = new User($domain->CustomerId);
            // Activate the user if pending or if they are cancelled and we are activating cancelled
            if ( (in_array($user->GetStatus(), $statusPending) && $domain->isPaid()) || ( $this->settings->get('plugin_order_Activate Cancelled Users') && in_array($user->GetStatus(), $statusCancelled) && $domain->isPaid() ) ) {
                $user->setStatus(USER_STATUS_ACTIVE);
                $user->save();
            }

            // Make sure that the domain is paid and user is active and the plan has domain options
            // Also, make sure the domain do not have all its invoices void
            // Finally, make sure that the plugin "Order Processor" is Enabled and that the domain allows automatic activation,
            // or make sure that the plugin "Order Processor" is disabled, meaning this is a Manual Execution.

            //0 = HAS UNPAID, PARTIALLY PAID, OR PENDING INVOICES
            //1 = HAS NO INVOICES
            //3 = HAS ALL INVOICES VOID
            //2 = HAS INVOICES, AND NONE OF THEM IS UNPAID, PARTIALLY PAID, OR PENDING
            $domainIsPaid = $domain->isPaid();

            //There must be at least 1 paid invoice, and none of them unpaid, partially paid or pending
            $canActivatePackage = ( $domainIsPaid == 2);

            if ($canActivatePackage && in_array($user->GetStatus(), $statusActive)) {
                $mailGateway = new NE_MailGateway();
                $userPackageGateway = new UserPackageGateway($user);
                if ( (($this->settings->get('plugin_order_Enabled') && isset($automaticactivation[$row['Plan']])) || (!$this->settings->get('plugin_order_Enabled')))) {
                    // If we are here, then we should be automatically registering the domain as well, based on the automatic activation of product setting.
                    $useRegistrarPlugin = true;
                    $useServerPlugin = false;
                    // Only use server plugin if we have a plugin to use
                    if ( $userPackageGateway->hasPlugin($domain , $pluginName) ) {
                        $useServerPlugin = true;
                    }
                    $numOrdersProcessed++;
                    $package = new Package($domain->Plan);

                    if ( $domain->getProductType() == 3 ) {
                        // We are doing a transfer request so handle it differently
                        // Register action is handled in UserPackage::activate()
                        if ( $domain->getCustomField('Registration Option') == 1 ) {
                            $advancedSettings = @unserialize($package->advanced);
                            if ( @$advancedSettings['autoInitiateTranfer'] == 1 ) {
                                try {
                                    $registrar = $domain->getCustomField('Registrar');
                                    if ( $registrar == null ) {
                                         // Auto Transfer is turned on, but no valid registrar
                                        $subject = "Manual transfer required for domain: " . $domain->getCustomField('Domain Name');
                                        $message = "** System created ticket **\n\n";
                                        $message .= "Package " . $domain->getReference(true) . " is for a manual domain transfer, however there is no registrar set for the TLD.\n\nPlease manually process this order as soon as possible.";
                                        $this->createTicketForDomainTransfer($subject, $message, $user);
                                    } else {
                                        $pluginGateway = new PluginGateway();
                                        $registrarPlugin = $pluginGateway->getPluginByName('registrars', $registrar);
                                        if ( $registrarPlugin->supportsAction('DomainTransferWithPopup') ) {
                                            // do nothing
                                        } else if ( $registrarPlugin->supportsAction('DomainTransfer') ) {
                                            // do nothing
                                        } else {
                                            // Auto Transfer is turned on, but no valid registrar plugin
                                            $subject = "Manual transfer required for domain: " . $domain->getCustomField('Domain Name');
                                            $message = "** System created ticket **\n\n";
                                            $message .= "Package " . $domain->getReference(true) . " is for a manual domain transfer, however your registrar plugin does not support automatic transfers.\n\nPlease manually process this order as soon as possible.";
                                            $this->createTicketForDomainTransfer($subject, $message, $user);
                                        }
                                    }
                                } catch ( Exception $e ) {
                                    // Domain Transfer Failed
                                    // Update signup value so we don't constantly try to activate a failed activation
                                    $sql = "UPDATE `domains` SET `signup` = '2' WHERE `id` = ?";
                                    $this->db->query($sql, $row['id']);

                                     // Auto Transfer is turned on, but no valid registrar plugin
                                    $subject = "Manual transfer required for domain: " . $domain->getCustomField('Domain Name');
                                    $message = "** System created ticket **\n\n";
                                    $message .= "The auto transfer for Package " . $domain->getReference(true) . " has failed.\n\n";
                                    $message .= "Reason: " . $e->getMessage();

                                    $this->createTicketForDomainTransfer($subject, $message, $user);
                                }
                            } else {
                                // Auto Transfer is turned off, so create a ticket.
                                $subject = "Manual transfer required for domain: " . $domain->getCustomField('Domain Name');
                                $message = "** System created ticket **\n\n";
                                $message .= "Package " . $domain->getReference(true) . " is for a manual domain transfer.\n\nPlease process this as soon as possible.";
                                $this->createTicketForDomainTransfer($subject, $message, $user);
                            }
                        }
                    }
                    // Try to activate the package.
                    try {
                        $domain->activate($this, $package->sendwelcome, $useServerPlugin, $useRegistrarPlugin, true);
                    } catch ( Exception $e ) {
                        // Update signup value so we don't constantly try to activate a failed activation
                        $sql = "UPDATE `domains` "
                          ."SET `signup` = '2' "
                          ."WHERE `id` = ? ";
                        $this->db->query($sql, $row['id']);

                        // Send an e-mail to notifiy admin that the attempt to automatically activate the package failed.
                        if ( $this->settings->get('plugin_order_E-mail Notifications') != '' ) {
                            $strEmailMessage = "Dear Support Member,\r\n\r\nCustomer ".$user->getFullName()
                                ." has ordered a package that requires manual processing, as the automatic attempt failed. Error message: " . $e->getMessage() . "\r\n\r\nPlease process the order as soon as possible.\r\n\r\nThank You";

                            $destinataries = explode("\r\n", $this->settings->get('plugin_order_E-mail Notifications'));
                            foreach ($destinataries as $destinatary) {
                                $mailGateway->mailMessageEmail( $strEmailMessage,
                                                            $this->settings->get('Support E-mail'),
                                                            $this->settings->get('Support E-mail'),
                                                            $destinatary,
                                                            '',
                                                            $this->user->lang("Manual Intervention Required:")." ".$user->getFullName());
                            }
                        }
                    }
                } else {
                    if ( $this->settings->get('plugin_order_E-mail Notifications') != '' ) {
                        $strEmailMessage = "Dear Support Member,\r\n\r\nCustomer ".$user->getFullName()
                            ." has ordered a package that requires manual processing. Please process the order as soon as possible.\r\n\r\nThank You";

                        $destinataries = explode("\r\n", $this->settings->get('plugin_order_E-mail Notifications'));
                        foreach ($destinataries as $destinatary) {
                            $mailGateway->mailMessageEmail( $strEmailMessage,
                                                            $this->settings->get('Support E-mail'),
                                                            $this->settings->get('Support E-mail'),
                                                            $destinatary,
                                                            '',
                                                            $this->user->lang("Manual Intervention Required:")." ".$user->getFullName());
                        }
                    }
                    // Don't want to send notification more than once.
                    $sql = "UPDATE `domains` SET `signup` = '2' WHERE `id` = ?";
                    $this->db->query($sql, $row['id']);
                }
            }
        }
        array_unshift($messages, $this->user->lang('%s order(s) processed', $numOrdersProcessed));
        return $messages;
    }

    function output() { }

    function dashboard()
    {
        $statusPackagePending = $statusGateway->getPackageStatusIdsFor(PACKAGE_STATUS_PENDING);
        $query = "SELECT COUNT(id) AS orders FROM domains WHERE status IN (".implode(', ', $statusPackagePending).")";

        // If we are not to activate manually added packages, then it must be from sign up.
        if ( $this->settings->get('plugin_order_Activate Manually Added Packages') == '0' ) {
            $query .= ' AND signup = 1';
        }

        $result = $this->db->query($query);
        $row = $result->fetch();
        if (!$row) {
            $row['orders'] = 0;
        }

        $message = $this->user->lang('Number of orders pending auto activation: %d', $row['orders']);
        $message .= "<br>";

        $query = "SELECT COUNT(id) AS orders FROM domains WHERE status IN (".implode(', ', $statusPackagePending).") AND signup = 2 ";
        $result = $this->db->query($query);
        $row = $result->fetch();
        if (!$row) {
            $row['orders'] = 0;
        }

        $message .= $this->user->lang('Number of orders requiring manual setup: %d', $row['orders']);

        return $message;
    }

    function createTicketForDomainTransfer($subject, $message, &$tUser)
    {
        require_once 'modules/support/models/TicketGateway.php';
        require_once 'modules/support/models/DepartmentGateway.php';

        $ticketGateway = new TicketGateway();
        $tTimeStamp = date('Y-m-d H-i-s');

        $cTicket = new Ticket();
        if ( $ticketGateway->GetTicketCount() == 0 )  {
            $cTicket->setForcedId($this->settings->get('Support Ticket Start Number'));
        }
        $cTicket->setUser($tUser);
        $cTicket->setSubject($subject);
        $cTicket->setPriority(1);
        $cTicket->setMethod(1);
        $cTicket->setStatus(1);
        $cTicket->setAssignedToDeptId(1);

        require_once 'modules/support/models/TicketTypeGateway.php';
        $ticketTypeGateway = new TicketTypeGateway();
        $billingTicketType = $ticketTypeGateway->getBillingTicketType();
        $cTicket->setMessageType($billingTicketType);
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
        $cTicket->addInitialLog($message, $tTimeStamp, $tUser);

        try {
            $cTicket->notifyAssignation($tUser);
        } catch (Exception $ex) {
            CE_Lib::log(1,"Failed sending ticket creation notification");
            CE_Lib::log(1,$ex->getMessage());
        }
    }
}