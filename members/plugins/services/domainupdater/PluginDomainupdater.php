<?php
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/admin/models/Package.php';
require_once 'modules/admin/models/TopLevelDomainGateway.php';
require_once 'modules/clients/models/DomainNameGateway.php';

/**
* @package Plugins
*/
class PluginDomainupdater extends ServicePlugin
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
                'value'         => lang('Domain Updater'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, this service will check all active domains and update the internal expiration date of the domains.  This date is used to send expiration notices among other things.'),
                'value'         => '0',
            ),
            lang('Sync Due Date?')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, a domain will have its next due date updated to the expiration date at the registrar.'),
                'value'         => '0',
            ),
            lang('Cancel Domains?')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, a domain in Clientexec that does not exist at the registrar will be marked as cancelled.'),
                'value'         => '0',
            ),
            lang('Force Recurring?')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, a domain will always have the recurring fee turned on and enabled.'),
                'value'         => '1',
            ),
            lang('Update Transfer Status?')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, Clientexec will check if a domain has been transfered and update internal values as needed.'),
                'value'         => '1',
            ),
            lang('Enable Renewal Notifications?') => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, Clientexec will send out renewal notifications for domains that are expiring.'),
                'value'         => '0'
            ),
            lang('Days To Send Renewal Notice')  => array(
                'type'          => 'text',
                'description'   => lang('Enter the number of days before a domain expires that Clientexec should send a renewal notification.  Separate numbers with a comma.'),
                'value'         => '30,7'
            ),
            lang('Days To Send Expiration Notice')  => array(
                'type'          => 'text',
                'description'   => lang('Enter the number of days after a domain expires that Clientexec should send an expiration notification.  Separate numbers with a comma.'),
                'value'         => '5',
            ),
            lang('E-mail Notifications')       => array(
                'type'          => 'textarea',
                'description'   => lang('If domains are updated when this service is run, a summary E-mail will be sent to this address.'),
                'value'         => '',
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


    /**
     * Execute the domain update.
     * This service will update all domains internal status to show when a domain is going to expire.
     *
     * @return void
     */
    function execute()
    {
        include_once 'modules/admin/models/StatusAliasGateway.php';

        $domainNameGateway = new DomainNameGateway($this->user);
        $messages = array();

        // get all active domains
        $statuses = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(PACKAGE_STATUS_ACTIVE);
        $query = "SELECT d.id FROM domains d, package p, promotion pr WHERE d.status IN(".implode(', ', $statuses).") AND d.Plan=p.id AND p.planid=pr.id AND pr.type=3";

        $result = $this->db->query($query);
        while ( $row = $result->fetch() ) {
            $userPackage = new UserPackage($row['id']);
            $domainName = $userPackage->getCustomField('Domain Name');
            $registrar = $userPackage->getCustomField('Registrar');
            $registrationOption = $userPackage->getCustomField('Registration Option');
            $transferUpdateDate = $userPackage->getCustomField('Transfer Update Date');
            $customerId = $userPackage->getCustomerId();

            // no registrar, so skip this entry
            if ( $registrar == '' || $registrar == null ) {
                continue;
            }

            // domain transfer, and not completed...
            if ( $registrationOption == 1 && $userPackage->getCustomField('Transfer Status') != 'Completed' ) {
                if ( $this->settings->get('plugin_domainupdater_Update Transfer Status?') == 1 ) {
                    $transferId = $userPackage->getCustomField("Transfer Status");
                    if ( $transferId == '' || $transferId == null ) {
                        $messages[] = $domainName . ' is a transfer and not complete, but has no transfer id; skipping.';
                        continue;
                    }
                    try {
                        $domainNameGateway->getTransferStatus($userPackage);
                    } catch ( MethodNotImplemented $e ) {
                    } catch ( Exception $e ) {
                        // connection issue so stop running the service.
                        if ( $e->getCode() == EXCEPTION_CODE_CONNECTION_ISSUE ) {
                            $messages[] = 'A connection issue has occurred, stopping domain updater.';
                            $this->sendSummaryEmail($messages);
                            return;
                        }
                    }
                } else {
                    CE_Lib::log(4, "$domainName is a transfer and not completed, skipping this entry as Update Transfer Status is not turned on.");
                    continue;
                }
            }

            // only run if registered by host, or transfered and completed
            if ( ($registrationOption == 0) ||
                ($registrationOption == 1 && $userPackage->getCustomField('Transfer Status') == 'Completed') ) {

                try {
                    $domainInfo = $domainNameGateway->getGeneralInfoViaPlugin($userPackage);
                } catch ( MethodNotImplemented $e ) {
                } catch ( Exception $e ) {
                    // connection issue so stop running the service.
                    if ( $e->getCode() == EXCEPTION_CODE_CONNECTION_ISSUE ) {
                        $messages[] = 'A connection issue has occurred, stopping domain updater.';
                        $this->sendSummaryEmail($messages);
                        return;
                    }

                    if ( $this->settings->get('plugin_domainupdater_Cancel Domains?') == 1 ) {
                        $messages[] = $domainName . ' does not seem to be in your registrar account, marking as cancelled in Clientexec.';
                        $userPackage->cancel(false);

                        $packageLog = Package_EventLog::newInstance(false, $customerId, $row['id'], PACKAGE_EVENTLOG_DOMAINUPDATER_CANCEL, 0, $domainName);
                        $packageLog->save();
                        continue;
                    }
                }

                // check if we're in redemption status
                // ToDo: We should not check RGP here, but set a variable in domainInfo instead
                if ( $this->settings->get('plugin_domainupdater_Cancel Domains?') == 1 ) {
                    if ( $domainInfo['registration'] == 'RGP' ) {
                        $messages[] = $domainName . ' is in redemption, marking as cancelled in Clientexec.';
                        $userPackage->cancel(false);

                        $packageLog = Package_EventLog::newInstance(false, $customerId, $row['id'], PACKAGE_EVENTLOG_DOMAINUPDATER_CANCEL, 0, $domainName);
                        $packageLog->save();
                        continue;
                    }
                }

                if ( $this->settings->get('plugin_domainupdater_Sync Due Date?') == 1 ) {
                    $recurringFee = $userPackage->getRecurringFeeEntry();

                    $timeStamp = strtotime($domainInfo['expires']);
                    if ( $timeStamp === false ) {
                        $messages[] = 'Can not determine expiration date for ' . $domainName . "; skipping (strtotime failed: {$domainInfo['expires']})";
                        continue;
                    }

                    $date = date('Y-m-d', $timeStamp);
                    // ensure date is valid
                    if ( $date == '' || $date == 0 || $date == null || $date == false || $date == '1969-12-31' || $date == '1970-01-01' ) {
                        $messages[] = 'Can not determine expiration date for ' . $domainName . "; skipping (invaid timestamp: $timeStamp)";
                        continue;
                    }

                    if ( $this->settings->get('plugin_domainupdater_Force Recurring?') == 1 ) {
                        // RecurringFee didn't exist. Set the values.
                        if (!$recurringFee->GetID()) {
                            $recurringFee->SetRecurring(1);
                            $messages[] = 'Enabled recurring billing for ' . $domainName . '.';

                            $recurringFee->setNextBillDate($date);
                            $messages[] = 'Updated next bill date for ' . $domainName . ' to ' . $date . '.';

                            $recurringFee->SetCustomerID($userPackage->CustomerId);
                            $recurringFee->SetBillingTypeID(-1);
                            $recurringFee->setAppliesToId($userPackage->getId());
                            $recurringFee->setPaymentTerm($this->getPaymentTerm($userPackage));
                            $recurringFee->setTaxable($userPackage->isTaxable());
                            $recurringFee->SetAmount(0);
                            $recurringFee->Update();
                            $recurringFee->SetAmount($userPackage->getPrice(false));

                            $packageLog = Package_EventLog::newInstance(false, $customerId, $row['id'], PACKAGE_EVENTLOG_DOMAINUPDATER_RECURRING, 0, $domainName);
                            $packageLog->save();

                            $packageLog = Package_EventLog::newInstance(false, $customerId, $row['id'], PACKAGE_EVENTLOG_DOMAINUPDATER_DATE, 0, $date);
                            $packageLog->save();
                        }

                        // all domains should be recurring.
                        if ( $recurringFee->GetRecurring() == 0 ) {
                            $recurringFee->SetRecurring(1);
                            // if the domain isn't recurring, we should default to a payment term of one year.
                            $recurringFee->setPaymentTerm($this->getPaymentTerm($userPackage));
                            $messages[] = 'Enabled recurring billing for ' . $domainName . '.';
                            $packageLog = Package_EventLog::newInstance(false, $customerId, $row['id'], PACKAGE_EVENTLOG_DOMAINUPDATER_RECURRING, 0, $domainName);
                            $packageLog->save();
                        }
                    }

                    if ( $recurringFee->getNextBillDate() != $date ) {
                        $updateNextDueDate = false;
                        // Only update the next due date if:
                        // - there are no invoices for the package
                        // OR
                        // (
                        //  - the domain is a transfer
                        //  AND
                        //  - The Last Invoice Date is NOT the same as the due date
                        // )
                        // OR
                        // ( - the difference between the next due date and the domain expiration date is lower than 6 months (180 days)
                        //   AND
                        //   - the difference between the last invoice for the package and the domain expiration date is greater than 6 months (180 days)
                        // )
                        $lastInvoiceDate = $userPackage->getLastInvoiceDate();

                        if ( $lastInvoiceDate === false || ($registrationOption == 1 && $transferUpdateDate == 1 && $lastInvoiceDate != $date ) ) {
                            $updateNextDueDate = true;
                        } else {
                            $nextBillDateDiff = CE_Lib::date_diff($recurringFee->getNextBillDate(), $date);
                            $lastInvoiceDateDiff = CE_Lib::date_diff($userPackage->getLastInvoiceDate(), $date);
                            if ((abs($nextBillDateDiff["d"]) < 180)
                              &&(abs($lastInvoiceDateDiff["d"]) > 180)) {
                                $updateNextDueDate = true;
                            }
                        }

                        if ($updateNextDueDate) {
                            $recurringFee->setNextBillDate($date);
                            $messages[] = 'Updated next bill date for ' . $domainName . ' to ' . $date . '.';

                            $packageLog = Package_EventLog::newInstance(false, $customerId, $row['id'], PACKAGE_EVENTLOG_DOMAINUPDATER_DATE, 0, $date);
                            $packageLog->save();
                        }
                    }
                    $recurringFee->Update();
                    if ($transferUpdateDate == 1) {
                        $userPackage->setCustomField("Transfer Update Date", 0);
                    }
                }

                $numberOfDaysTillExpires = (int)$domainNameGateway->getExpiresInDays($domainInfo['expires']);
                if ( $this->settings->get('plugin_domainupdater_Enable Renewal Notifications?') == 1 ) {
                    if ( $this->canHandleRenewalNotification() ) {
                        $this->handleRenewalNotification($userPackage, $numberOfDaysTillExpires, $domainInfo);
                    } else {
                        throw new Exception($this->user->lang('Renewal Notifications are enabled, however the domain updater service is not scheduled to run only once per day.  Please ensure that this service is set to only run once per day.'));
                    }
                }

                $userPackage->setCustomField('Plugin Status', $domainNameGateway->getPluginStatusByDays($numberOfDaysTillExpires));
                $userPackage->setCustomField('Expiration Date', strtotime($domainInfo['expires']));
                $userPackage->setCustomField('Auto Renew', $domainInfo['auto_renew']);
            }
        }
        if ( count($messages) > 0 ) {
            $this->sendSummaryEmail($messages);
        }
    }

    function sendSummaryEmail($messages)
    {
        if ( $this->settings->get('plugin_domainupdater_E-mail Notifications') != '' ) {
            $body = "Domain Updater Summary:\n\n";
            $body .= implode("\n", $messages);
            $mailGateway = new NE_MailGateway();
            $destinataries = explode("\r\n", $this->settings->get('plugin_domainupdater_E-mail Notifications'));
            foreach ($destinataries as $destinatary) {
                if ( $destinatary != '' ) {
                    $mailGateway->mailMessageEmail( $body,
                        $this->settings->get('Support E-mail'),
                        $this->settings->get('Support E-mail'),
                        $destinatary,
                        false,
                        $this->user->lang("Domain Updater Service Summary"));
                }
            }
        }
    }


     /**
     * Function to get the first valid payment term of a domain.
     *
     * @return int payment term
     */
    private function getPaymentTerm($userPackage)
    {
        $tldGateway = new TopLevelDomainGateway($this->user);

        for ( $i=1; $i<=10; $i++ ) {
            $price = $tldGateway->getPeriodPricesForTld($userPackage->Plan, $i);
            if ( isset($price['price']) && $price['price'] > 0 ) {
                return $i * 12;
            }
        }
    }

    /**
     * When Enable Renewal Notifications is enabled, this function will check if we should send a renewal notification and send one out
     *
     * @param  UserPackage $userPackage UserPackage of the domain we are checking
     * @param  Int $daysTillExpires The number of days till the domain expires
     * @param  Array $domainInfo Domain information array (getGeneralInfoViaPlugin function from DomainNameGateway)
     * @return void
     */
    private function handleRenewalNotification($userPackage, $daysTillExpires, $domainInfo)
    {
        $domainNameGateway = new DomainNameGateway($this->user);
        if ( $domainInfo['is_registered'] === true ) {
            $daysToSendNotification = $this->settings->get('plugin_domainupdater_Days To Send Renewal Notice');
            $daysToSendNotification = explode(",", $daysToSendNotification);

            if ( in_array($daysTillExpires, $daysToSendNotification) ) {
                $contactInfo = $domainNameGateway->callPlugin($userPackage, 'getContactInformation');
                $registrantEmail = $contactInfo['Registrant']['EmailAddress'][1];
                $domainNameGateway->sendReminder($userPackage->id, $registrantEmail, $domainInfo['expires']);
            }
        } else if ( $domainInfo['is_expired'] === true ) {
            $daysToSendNotification = $this->settings->get('plugin_domainupdater_Days To Send Expiration Notice');
            $daysToSendNotification = explode(",", $daysToSendNotification);
            $daysTillExpires = abs($daysTillExpires);
            if ( in_array($daysTillExpires, $daysToSendNotification) ) {
                $contactInfo = $domainNameGateway->callPlugin($userPackage, 'getContactInformation');
                $registrantEmail = $contactInfo['Registrant']['EmailAddress'][1];
                $domainNameGateway->sendExpirationReminder($userPackage->id, $registrantEmail, $domainInfo['expires']);
            }
        }
    }

    /**
     * Function to determine if we should be sending out notifications based on the run schedule of the plugin
     *
     * @return boolean
     */
    private function canHandleRenewalNotification()
    {
        // Ensure that this service is only set to run once a day.
        $runMinute = $this->settings->get('plugin_domainupdater_Run schedule - Minute');
        $runHour = $this->settings->get('plugin_domainupdater_Run schedule - Hour');
        $runDay = $this->settings->get('plugin_domainupdater_Run schedule - Day');
        $runMonth = $this->settings->get('plugin_domainupdater_Run schedule - Month');
        $runWeekDay = $this->settings->get('plugin_domainupdater_Run schedule - Day of the week');

        if ( is_numeric($runMinute) && is_numeric($runHour) && $runDay == '*' && $runMonth == '*' && $runWeekDay == '*' ) {
            return true;
        }
        return false;
    }

    function output()
    {
    }
    function dashboard()
    {
    }
}
