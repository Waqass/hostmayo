<?php
/**
 * Upcoming Charges Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.3
 * @link     http://www.clientexec.com
 *
 *************************************************
 *   1.2 Only include for active customers.  - Alberto Vasquezs
 *   1.3 Updated the report to use Pear Commenting & the new title handing to make app reports consistent.
 ************************************************
 */

require_once 'modules/admin/models/Package.php';
require_once 'modules/admin/models/StatusAliasGateway.php' ;
require_once 'modules/clients/models/UserPackage.php';
require_once 'modules/billing/models/Currency.php';


/**
 * Upcoming_Charges Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.3
 * @link     http://www.clientexec.com
 */
class Upcoming_Charges extends Report
{
    private $lang;

    protected $featureSet = 'billing';

    var $showOptionsForOverdueCharges = false;
    var $lastPaidInvoiceInfo = array();

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Upcoming Charges');
        parent::__construct($user,$customer);
    }

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process()
    {
        // Set the report information
        $this->SetDescription($this->user->lang('Displays Stats on the upcoming charges for all Active users'));

        @set_time_limit(0);

        $sortbydate = @$_GET['sortbydate'];

        $userStatuses = StatusAliasGateway::userActiveAliases($this->user);
        $statuses = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(array(PACKAGE_STATUS_PENDING, PACKAGE_STATUS_ACTIVE));

        // THIS BLOCK IS ONLY FOR RUNNING ACTIONS OVER THE CHARGES SELECTED
        if($this->showOptionsForOverdueCharges && $this->user->getGroupId() == ROLE_SUPERADMIN) {
            if(isset($_POST['actionButton'])) {
                switch($_POST['actionButton']) {
                    case "Cancel Subscription":
                        if(isset($_POST['updateentries'])) {
                            foreach($_POST['updateentries'] AS $entries) {
                                $entriesParams = explode('|', $entries);

                                $queryGetAppliesToIds = "SELECT DISTINCT r.appliestoid "
                                    ."FROM recurringfee r "
                                    ."LEFT JOIN domains d ON r.appliestoid = d.id "
                                    ."WHERE r.appliestoid != 0 "
                                    ."AND r.recurring = 1 "
                                    ."AND d.status IN (".implode(', ', $statuses).") "
                                    ."AND r.paymentterm != 0 "
                                    ."AND r.id IN (".$this->db->escape($entriesParams[1]).") ";
                                $resultGetAppliesToIds = $this->db->query($queryGetAppliesToIds);

                                $packagesIDs = array();

                                while(list($r_appliestoid) = $resultGetAppliesToIds->fetch()) {
                                    $packagesIDs[] = $r_appliestoid;
                                }

                                $this->cancelSubscription($entriesParams[0], $packagesIDs, explode(',', $entriesParams[1]));
                            }
                        }
                        break;
                    case "Suspend Packages":
                    case "Cancel Packages":
                        if(isset($_POST['updateentries'])) {
                            $entryIds = array();

                            foreach($_POST['updateentries'] AS $entries) {
                                $entriesParams = explode('|', $entries);
                                $entryIds[] = $entriesParams[1];
                            }

                            $entryIds = implode(',', $entryIds);

                            $queryGetAppliesToIds = "SELECT DISTINCT r.appliestoid "
                                ."FROM recurringfee r "
                                ."LEFT JOIN domains d ON r.appliestoid = d.id "
                                ."WHERE r.appliestoid != 0 "
                                ."AND r.recurring = 1 "
                                ."AND d.status IN (".implode(', ', $statuses).") "
                                ."AND r.paymentterm != 0 "
                                ."AND r.id IN (".$this->db->escape($entryIds).") ";
                            $resultGetAppliesToIds = $this->db->query($queryGetAppliesToIds);

                            $packagesIDs = array();

                            while(list($r_appliestoid) = $resultGetAppliesToIds->fetch()) {
                                $packagesIDs[] = $r_appliestoid;
                            }

                            $TriggerServerPlugin = false;

                            if(isset($_POST['TriggerServerPlugin'])) {
                                $TriggerServerPlugin = $_POST['TriggerServerPlugin'];
                            }

                            switch($_POST['actionButton']) {
                                case "Suspend Packages":
                                    $this->suspendCancelPackages('suspend', $packagesIDs, $TriggerServerPlugin);
                                    break;
                                case "Cancel Packages":
                                    $this->suspendCancelPackages('cancel', $packagesIDs, $TriggerServerPlugin);
                                    break;
                            }
                        }
                        break;
                    case "Update Next Date":
                        if(isset($_POST['updateentries'])) {
                            if(isset($_POST['nextDueDate'])) {
                                $nextDueDateArray = explode('/', $_POST['nextDueDate']);
                                $nextDueDate = date("Y-m-d", mktime(0, 0, 0, $nextDueDateArray[0], $nextDueDateArray[1], $nextDueDateArray[2]));

                                $entryIds = array();

                                foreach($_POST['updateentries'] AS $entries) {
                                    $entriesParams = explode('|', $entries);
                                    $entryIds[] = $entriesParams[1];
                                }

                                $entryIds = implode(',', $entryIds);

                                $queryGetAppliesToIds = "SELECT DISTINCT r.appliestoid "
                                    ."FROM recurringfee r "
                                    ."LEFT JOIN domains d ON r.appliestoid = d.id "
                                    ."WHERE r.appliestoid != 0 "
                                    ."AND r.recurring = 1 "
                                    ."AND d.status IN (".implode(', ', $statuses).") "
                                    ."AND r.paymentterm != 0 "
                                    ."AND r.id IN (".$this->db->escape($entryIds).") ";
                                $resultGetAppliesToIds = $this->db->query($queryGetAppliesToIds);

                                $packagesIDs = array();

                                while(list($r_appliestoid) = $resultGetAppliesToIds->fetch()) {
                                    $packagesIDs[] = $r_appliestoid;
                                }

                                $this->resetDueDate($nextDueDate, $packagesIDs, explode(',', $entryIds));
                            }
                        }
                        break;
                }

//CE_Lib::redirectPage($_SERVER['PHP_SELF'].'?fuse=reports&amp;report=Upcoming+Charges&amp;controller=index&amp;type=Income&amp;view=viewreport&sortbydate='.(($sortbydate)? 1 : 0));
            }
        }
        // THIS BLOCK IS ONLY FOR RUNNING ACTIONS OVER THE CHARGES SELECTED

        $currency = new Currency($this->user);

        $this->SetDescription($this->user->lang('Displays upcoming charges'));

        $reportSQL = "SELECT r.id, "
                ."r.customerid, "
                ."r.amount, "
                ."r.amount_percent, "
                ."r.nextbilldate, "
                ."r.appliestoid, "
                ."r.disablegenerate, "
                ."r.subscription_id, "
                ."r.billingtypeid, "
                ."r.taxable, "
                ."IF(TIMESTAMP(r.nextbilldate) < TIMESTAMP(CURDATE()), 1, 0) AS late, "
                ."u.paymenttype, "
                ."ocf.value AS domain_name "
                ."FROM recurringfee r "
                ."INNER JOIN users u "
                ."ON r.customerid = u.id "
                ."LEFT JOIN domains d "
                ."ON r.appliestoid = d.id "
                ."LEFT JOIN object_customField ocf "
                ."ON ocf.objectid = d.id "
                ."AND ocf.customFieldId = (SELECT cf.id "
                ."FROM customField cf "
                ."WHERE groupId = 2 "
                ."AND subGroupId  = 3 "
                ."AND name  = 'Domain Name') "
                ."WHERE r.nextbilldate <> 'NULL' "
                ."AND u.status IN (".implode(', ', $userStatuses).") "
                ."AND r.recurring = 1 "
                ."AND (r.appliestoid = 0 OR d.status IN (".implode(', ', $statuses).")) "
                ."AND r.paymentterm != 0 "
                ."ORDER BY r.nextbilldate ASC, r.customerid, r.appliestoid, r.billingtypeid DESC ";
        $result = $this->db->query($reportSQL);

        $masterGroup = array();

        $lateEntries = array();

        $productPricesToCalculateDiscounts = array();

        while(list($id, $customerid, $amount, $amount_percent, $nextbilldate, $appliestoid, $disablegenerate, $subscription_id, $billingtype, $taxable, $late, $paymenttype, $DomainName) = $result->fetch()) {
            $date = $this->convertDate($nextbilldate);

            if($disablegenerate == '1' && $subscription_id != '') {
                $disablegenerate = '<a href="#" title="Paypal subscription set, invoice will not be created when running Generate invoices">*</a>';
            }else {
                $disablegenerate = '';
            }

            // Check for taxes
            $tax = 0;
            $user = new User($customerid);
            $taxrate = $user->GetTaxRate();
            //-1 is returned when we don't have a taxrate
            $taxrate     = ($taxrate == -1) ? 0 : $taxrate;

            if($appliestoid > 0 && $billingtype == -1) {
                $domain = new UserPackage($appliestoid);
                $planid = $domain->Plan;
                $paymentterm = $domain->getPaymentTerm();
                $taxable = $domain->isTaxable()? true : false;
                $amount = $domain->getPrice(false);
                $productPricesToCalculateDiscounts[$appliestoid] = $amount;
            }elseif($appliestoid > 0 && $billingtype == -3 && $amount_percent > 0){
                $amount = -($productPricesToCalculateDiscounts[$appliestoid] * $amount_percent);
            }

            if($taxrate > 0) {
                // Check to see if the package is taxable
                if($taxable) {
                    $tax = $currency->format($user->getCurrency(), $amount * $taxrate / 100);
                }
            }

            $masterGroup[] = array($customerid, $date, $amount, $disablegenerate, $paymenttype, $tax, ($amount + $tax), $id, $late, $appliestoid, $DomainName);
            if($late) {
                $lateEntries[] = $id;
            }
        }

        if($this->showOptionsForOverdueCharges
                && $this->user->getGroupId() == ROLE_SUPERADMIN) {
            $this->setLastPaidInvoiceForRecurringFees(implode(',', $lateEntries));
        }

        $tmpUID = array();
        $total = count($masterGroup);

        for($i = 0; $i < $total; $i++) {
            if(in_array($masterGroup[$i][0], $tmpUID)) {
                $position = array_search($masterGroup[$i][0], $tmpUID);

                if($masterGroup[$position][1] == $masterGroup[$i][1]
                        && $masterGroup[$position][9] == $masterGroup[$i][9]) {
                    $newAmount = $masterGroup[$position][2] + $masterGroup[$i][2];
                    $masterGroup[$position][2] = $newAmount;

                    $newTax = $masterGroup[$position][5] + $masterGroup[$i][5];
                    $masterGroup[$position][5] = $newTax;

                    $newTotal = $masterGroup[$position][6] + $masterGroup[$i][6];
                    $masterGroup[$position][6] = $newTotal;

                    $newId = $masterGroup[$position][7].','.$masterGroup[$i][7];
                    $masterGroup[$position][7] = $newId;

                    unset($masterGroup[$i]);
                }
            }

            $tmpUID[] = @$masterGroup[$i][0];
        }

        $paymenttypes = array();
        $subGroup = array();
        $i = 0;

        $indexUpdateEntries = 1;

        while(list($info) = each($masterGroup)) {
            $customerid      = $masterGroup[$info][0];
            $date            = $masterGroup[$info][1];
            $amount          = $masterGroup[$info][2];
            $disablegenerate = $masterGroup[$info][3];
            $paymenttype     = $masterGroup[$info][4];
            $taxes           = $masterGroup[$info][5];
            $total           = $masterGroup[$info][6];
            $id              = $masterGroup[$info][7]; // Charges (recurring fees) ids
            $late            = $masterGroup[$info][8]; // Charge is overdue
            $appliestoid     = $masterGroup[$info][9];
            $DomainName      = $masterGroup[$info][10];

            if(!in_array($paymenttype, $paymenttypes)) {
                $paymenttypes[] = $paymenttype;
            }

            $userid = $customerid;
            $tUser = new User($userid);

            $dusername = $tUser->getFullName();


            if($this->showOptionsForOverdueCharges
                    && $this->user->getGroupId() == ROLE_SUPERADMIN
                    && count($lateEntries) > 0) {
                $maxSize = 9;
            }else {
                $maxSize = 20;
            }

            if(strlen($dusername) > $maxSize) {
                $title = "title=\"$dusername\"";
                $dusername = mb_substr($dusername, 0,$maxSize) . "...";
            }else {
                $title = "";
            }

            $username[] = $dusername.' / #'.$appliestoid;

            $checkbox = '';

            if($this->showOptionsForOverdueCharges && $late) {
                $checkbox = "<input type=\"checkbox\" name=\"updateentries[]\" value=\"".$customerid."|".$id."\" id=\"updateentries".$indexUpdateEntries."\" align=left  style=\"float:left;margin:0\" />&nbsp;&nbsp;";
                $indexUpdateEntries++;
            }

            if($sortbydate) {
                if($this->showOptionsForOverdueCharges
                        && $this->user->getGroupId() == ROLE_SUPERADMIN
                        && count($lateEntries) > 0) {
                    $subGroup[] = array(
                            $checkbox."<a href=\"index.php?frmClientID=".$customerid."&fuse=clients&controller=userprofile&view=profilecontact\">".$username[0]." ".@$username[1]."</a>",
                            $this->showDate($date, $late, explode(',', $id)),
                            ($late)? $this->showLatestPaidInvoicesInfo(explode(',', $id), 'billdate', true) : '---',
                            "<a href=\"index.php?frmClientID=".$customerid."&fuse=clients&controller=userprofile&view=profilerecurringcharges\" title=\"".strtoupper($paymenttype)." - ".$DomainName."\">".$currency->format($tUser->getCurrency(), $amount, true)."</a>".$disablegenerate,
                            $currency->format($tUser->getCurrency(), $taxes, true),
                            $currency->format($tUser->getCurrency(), $total, true)
                    );
                }else {
                    $subGroup[] = array(
                            $checkbox."<a href=\"index.php?frmClientID=".$customerid."&fuse=clients&controller=userprofile&view=profilecontact\">".$username[0]." ".@$username[1]."</a>",
                            $this->showDate($date, $late, explode(',', $id)),
                            "<a href=\"index.php?frmClientID=".$customerid."&fuse=clients&controller=userprofile&view=profilerecurringcharges\" title=\"".strtoupper($paymenttype)." - ".$DomainName."\">".$currency->format($tUser->getCurrency(), $amount, true)."</a>".$disablegenerate,
                            $currency->format($tUser->getCurrency(), $taxes, true),
                            $currency->format($tUser->getCurrency(), $total, true)
                    );
                }
            }else {
                if($this->showOptionsForOverdueCharges
                        && $this->user->getGroupId() == ROLE_SUPERADMIN
                        && count($lateEntries) > 0) {
                    $subGroup[$paymenttype][] = array(
                            $checkbox."<a href=\"index.php?frmClientID=".$customerid."&fuse=clients&controller=userprofile&".$currency."&view=profilecontact\" $title>".$username[0]." ".@$username[1]."</a>",
                            $this->showDate($date, $late, explode(',', $id)),
                            ($late)? $this->showLatestPaidInvoicesInfo(explode(',', $id), 'billdate', true) : '---',
                            "<a href=\"index.php?frmClientID=".$customerid."&fuse=clients&controller=userprofile&view=profilerecurringcharges\" title=\"".$DomainName."\">".$currency->format($tUser->getCurrency(), $amount, true)."</a>".$disablegenerate,
                            $currency->format($tUser->getCurrency(), $taxes, true),
                            $currency->format($tUser->getCurrency(), $total, true)
                    );
                }else {
                    $subGroup[$paymenttype][] = array(
                            $checkbox."<a href=\"index.php?frmClientID=".$customerid."&fuse=clients&controller=userprofile&view=profilecontact\" $title>".$username[0]." ".@$username[1]."</a>",
                            $this->showDate($date, $late, explode(',', $id)),
                            "<a href=\"index.php?frmClientID=".$customerid."&fuse=clients&controller=userprofile&view=profilerecurringcharges\" title=\"".$DomainName."\">".$currency->format($tUser->getCurrency(), $amount, true)."</a>".$disablegenerate,
                            $currency->format($tUser->getCurrency(), $taxes, true),
                            $currency->format($tUser->getCurrency(), $total, true)
                    );
                }
            }

            unset($username);
            $i++;
        }

        $actionsDescription = '';
        $scriptCode = '';
        $TriggerServerPluginField = '';
        $FormButtons = '';
        $NextDueDateField = '';

        if($this->showOptionsForOverdueCharges
                && $this->user->getGroupId() == ROLE_SUPERADMIN
                && count($lateEntries) > 0) {
            $actionsDescription = '<table width=80%>'
                    .'    <tr>'
                    .'        <td colspan="2" valign="top" nowrap>'
                    .'            <font color="#FF7E7E">'
                    .'                <b>'
                    .'                    Note:'
                    .'                </b>'
                    .'                Before executing any of these actions, it is highly recommended to create a backup of the information.<br><br>'
                    .'            </font>'
                    .'        </td>'
                    .'    </tr>'
                    .'    <tr>'
                    .'        <td valign="top" nowrap>'
                    .'            <b>Cancel Subscriptions:</b>'
                    .'        </td>'
                    .'        <td>'
                    .'            Converts all the selected charges and all the entries related to the packages of these charges, to not use subscription.'
                    .'            <br>Useful for those missed cancellations of paypal subscriptions.<br><br>'
                    .'        </td>'
                    .'    </tr>'
                    .'    <tr>'
                    .'        <td valign="top" nowrap>'
                    .'            <b>Suspend / Cancel Packages:</b>'
                    .'        </td>'
                    .'        <td>'
                    .'            Suspends or cancels the packages of the selected charges.'
                    .'            <br>Useful for avoid customers to keep using the services when they are not paying.<br><br>'
                    .'        </td>'
                    .'    </tr>'
                    .'    <tr>'
                    .'        <td valign="top" nowrap>'
                    .'            <b>Update Next Due Date:</b>'
                    .'        </td>'
                    .'        <td>'
                    .'            Changes the value of Next Due Date to a given date in all the selected charges and all the entries related to the packages of these charges, and also changes the value of Next Due Date on those packages.'
                    .'            <br>Useful to correct some wrong Next Due Dates.'
                    .'            <br>Can only be used over entries that are set as no subscriptions. In case you need to do it over a subscription, then execute first the <b>Cancel Subscriptions</b> action.<br><br>'
                    .'        </td>'
                    .'    </tr>'
                    .'</table>'
                    .'<br><br>';

            $scriptCode  = "<script type='text/javascript'>"
                    ."    function entriesSelected()"
                    ."    {"
                    ."        var selectedEntries = false;"
                    ."        for(var i = 1; document.getElementById('updateentries'+i); i++){"
                    ."            if(document.getElementById('updateentries'+i).checked){"
                    ."                selectedEntries = true;"
                    ."                break;"
                    ."            }"
                    ."        }"
                    ."        if(!selectedEntries){"
                    ."            alert('You must select some charges to perform this action');"
                    ."        }"
                    ."        return selectedEntries;"
                    ."    }"
                    ."    "
                    ."    function suspendCancelPackages(packageAction)"
                    ."    {"
                    ."        if(confirm('Click OK if you are sure you want to ' + packageAction + ' the packages of this charges')){"
                    ."            if(confirm('Click OK if you want to trigger the server plugin to ' + packageAction + ' the packages')){"
                    ."                document.getElementById('TriggerServerPlugin').value=1;"
                    ."            }else{"
                    ."                document.getElementById('TriggerServerPlugin').value=0;"
                    ."            }"
                    ."            return true;"
                    ."        }else{"
                    ."            return false;"
                    ."        }"
                    ."    }"
                    ."    "
                    ."    function requestNextDueDate(showField)"
                    ."    {"
                    ."        if(showField){"
                    ."            document.getElementById('FormButtonsSpan').style.display = 'none';"
                    ."            document.getElementById('NextDueDateSpan').style.display = '';"
                    ."        }else{"
                    ."            document.getElementById('FormButtonsSpan').style.display = '';"
                    ."            document.getElementById('NextDueDateSpan').style.display = 'none';"
                    ."        }"
                    ."    }"
                    ."</script>";

            $TriggerServerPluginField = "<input type='hidden' id='TriggerServerPlugin' name='TriggerServerPlugin' value=0>";

            $VERSION = urlencode(CE_Lib::getAppVersion());
            $tempNextDueDate = date("Y-m-d");

            if ($this->settings->get('Date Format') == 'm/d/Y') {
                $dateFormat = '%m/%d/%Y';
            } else {
                $dateFormat = '%d/%m/%Y';
            }

            $FormButtons  = '<td align=right>'
                    .'    <span name="FormButtonsSpan" id="FormButtonsSpan">'
                    .'        <input name="actionButton"    type="submit" value="Cancel Subscription" class="button_xlarge" onclick="if(!entriesSelected()){return false;}"                                        />&nbsp;&nbsp;'
                    .'        <input name="actionButton"    type="submit" value="Suspend Packages"    class="button_xlarge" onclick="if(!entriesSelected() || !suspendCancelPackages(\'suspend\')){return false;}" />&nbsp;&nbsp;'
                    .'        <input name="actionButton"    type="submit" value="Cancel Packages"     class="button_xlarge" onclick="if(!entriesSelected() || !suspendCancelPackages(\'cancel\')){return false;}"  />&nbsp;&nbsp;'
                    .'        <input name="subactionButton" type="button" value="Update Next Date"    class="button_xlarge" onclick="if(entriesSelected()){requestNextDueDate(1);}"                                />'
                    .'    </span>'
                    .'    <span name="NextDueDateSpan" id="NextDueDateSpan" style="display:none">'
                    .'        <input class=body type=text size=12 MAXLENGTH=10 name="nextDueDate" id="nextDueDate" value="'.CE_Lib::db_to_form($tempNextDueDate, $this->settings->get('Date Format'), "/").'">&nbsp;&nbsp;'
                    .'        <input name="actionButton"    type="submit" value="Update Next Date"    class="button_xlarge" onclick="if(!entriesSelected()){return false;}"                                        />&nbsp;&nbsp;'
                    .'        <input name="subactionButton" type="button" value="Cancel"              class="button_medium" onclick="requestNextDueDate(0);"                                                       />'
                    .'    </span>'
                    .'</td>';
        }

        $total = count($paymenttypes);

        if($sortbydate) {
            if($this->showOptionsForOverdueCharges
                    && $this->user->getGroupId() == ROLE_SUPERADMIN
                    && count($lateEntries) > 0) {
                $this->Add($subGroup, "Charges", array(
                        $this->user->lang('Client').' / '.$this->user->lang('Package ID'),
                        $this->user->lang('Next Invoice Date'),
                        $this->user->lang('Last Paid Invoice'),
                        $this->user->lang('Subtotal'),
                        $this->user->lang('Tax'),
                        $this->user->lang('Total')
                        )
                );

                $this->reportData[] = array(
                    "group" => $subGroup,
                    "groupname" => "Charges",
                    "label" => array(
                            $this->user->lang('Client').' / '.$this->user->lang('Package ID'),
                            $this->user->lang('Next Invoice Date'),
                            $this->user->lang('Last Paid Invoice'),
                            $this->user->lang('Subtotal'),
                            $this->user->lang('Tax'),
                            $this->user->lang('Total')
                            ),
                    "groupId" => "",
                    "isHidden" => false);

            }else {

                $this->reportData[] = array(
                    "group" => $subGroup,
                    "groupname" => "Charges",
                    "label" => array(
                            $this->user->lang('Client').' / '.$this->user->lang('Package ID'),
                            $this->user->lang('Next Invoice Date'),
                            $this->user->lang('Subtotal'),
                            $this->user->lang('Tax'),
                            $this->user->lang('Total')
                            ),
                    "groupId" => "",
                    "isHidden" => false);

            }
        }else {
            if($this->showOptionsForOverdueCharges
                    && $this->user->getGroupId() == ROLE_SUPERADMIN
                    && count($lateEntries) > 0) {
                for($i = 0; $i < $total; $i++) {
                    $this->reportData[] = array(
                    "group" => $subGroup[$paymenttypes[$i]],
                    "groupname" => $paymenttypes[$i],
                    "label" => array(
                            $this->user->lang('Client').' / '.$this->user->lang('Package ID'),
                            $this->user->lang('Next Invoice Date'),
                            $this->user->lang('Last Paid Invoice'),
                            $this->user->lang('Subtotal'),
                            $this->user->lang('Tax'),
                            $this->user->lang('Total')
                            ),
                    "groupId" => "",
                    "isHidden" => false);

                }
            }else {
                for($i = 0; $i < $total; $i++) {

                    $this->reportData[] = array(
                        "group" => $subGroup[$paymenttypes[$i]],
                        "groupname" => $paymenttypes[$i],
                        "label" => array(
                                $this->user->lang('Client').' / '.$this->user->lang('Package ID'),
                                $this->user->lang('Next Invoice Date'),
                                $this->user->lang('Subtotal'),
                                $this->user->lang('Tax'),
                                $this->user->lang('Total')
                                ),
                        "groupId" => "",
                        "isHidden" => false);

                }
            }
        }

        echo '<div style="margin-left:20px;position:relative;top:25px;"><form name="chargesForm" method="post" action="'.CE_Lib::viewEscape($_SERVER['PHP_SELF']).'?fuse=reports&amp;report=Upcoming+Charges&amp;controller=index&amp;type=Income&amp;view=viewreport&Transactions'.(($sortbydate)? 1 : 0).'" />'
                .$scriptCode
                .$TriggerServerPluginField
                .$actionsDescription
                .'<table>'
                .'    <tr>'
                .'        <td>'
                .'            <a href="'.CE_Lib::viewEscape($_SERVER['PHP_SELF']).'?fuse=reports&amp;report=Upcoming+Charges&amp;controller=index&amp;type=Income&amp;view=viewreport&sortbydate='.(($sortbydate)? '0">'.$this->user->lang('Sort By Payment Type') : '1">'.$this->user->lang('Sort By Date')).'</a>'
                .'        </td>'
                .$FormButtons
                .'    </tr>'
                .'</table>'
                .$NextDueDateField;
        echo "</form></div>";
    }

    function convertDate($date) {
        return date("F j, Y", strtotime($date));
    }

    // Change the look of a date depending if is a late date or not
    function showDate($date, $late, $entryIds) {
        if($this->showOptionsForOverdueCharges
                && $this->user->getGroupId() == ROLE_SUPERADMIN
                && $late) {
            $dateTitle = '<font color=red><b>This charge is overdue</b></font>'
                    .'<br>'
                    .'<br>The latest paid invoices for this entries are as follow:'
                    .'<br>'
                    .'<br><b>Invoice #: (bill date / date paid)</b>'
                    .'<br>';

            $dateTitle .= $this->showLatestPaidInvoicesInfo($entryIds, 'id: (billdate / datepaid)');

            $date = '<a href="#" onmouseover="return overlib(\''.$dateTitle.'\',FIXY,_mousePosY+5,FIXX,_mousePosX+13,DELAY,310,BGCOLOR, \'#FFCC00\', FGCOLOR, \'#FFFFCC\',BORDER,1,TEXTCOLOR,\'#000000\');" onmouseout="return nd();" ><font color=red>'.$date.'</font></a>';
        }

        return $date;
    }

    // Shows the info of the latest paid invoices for the given entries, in the given template
    function showLatestPaidInvoicesInfo($entryIds, $template, $unique = false) {
        if($this->showOptionsForOverdueCharges
                && $this->user->getGroupId() == ROLE_SUPERADMIN) {
            $InvoicesInfo = array();

            foreach($entryIds AS $id) {
                $paidInvoiceInfo = $this->getLastPaidInvoiceForRecurringFee($id);
                if($paidInvoiceInfo['id'] != '') {
                    $InvoicesInfo[$paidInvoiceInfo['id']] = str_replace(array('billdate', 'datepaid', 'id'), array($paidInvoiceInfo['billdate'], $paidInvoiceInfo['datepaid'], $paidInvoiceInfo['id']), $template);
                }else {
                    $InvoicesInfo[$paidInvoiceInfo['id']] = $this->user->lang('None');
                }
            }

            if($unique && count($InvoicesInfo) > 1) {
                return $this->user->lang('More than 1 invoice');
            }else {
                return implode('<br>', $InvoicesInfo);
            }
        }else {
            return '';
        }
    }

    // Search for information of the last billed invoice that has been paid and has an entry
    // that is based on a recurring fee with a given id
    // Works for multiple recurring fees
    function setLastPaidInvoiceForRecurringFees($entryIds) {
        if($this->showOptionsForOverdueCharges
                && $this->user->getGroupId() == ROLE_SUPERADMIN) {
            if($entryIds != '') {
                $querySetLastPaidInvoiceInfo = "SELECT ie.recurringappliesto, i.id, i.billdate, i.datepaid "
                        ."FROM invoiceentry ie "
                        ."LEFT JOIN invoice i ON ie.invoiceid = i.id "
                        ."WHERE i.status = 1 "
                        ."AND ie.recurringappliesto IN (".$entryIds.") "
                        ."ORDER BY ie.recurringappliesto, i.billdate DESC ";
                $resultSetLastPaidInvoiceInfo = $this->db->query($querySetLastPaidInvoiceInfo);
                while(list($ie_recurringappliesto, $i_id, $i_billdate, $i_datepaid) = $resultSetLastPaidInvoiceInfo->fetch()) {
                    if(!isset($this->lastPaidInvoiceInfo[$ie_recurringappliesto])) {
                        $this->lastPaidInvoiceInfo[$ie_recurringappliesto] = array(
                                'id'       => $i_id,
                                'billdate' => $i_billdate,
                                'datepaid' => $i_datepaid,
                        );
                    }
                }
            }
        }
    }

    // Search for information of the last billed invoice that has been paid and has an entry
    // that is based on a recurring fee with a given id
    function getLastPaidInvoiceForRecurringFee($id) {
        if($this->showOptionsForOverdueCharges
                && $this->user->getGroupId() == ROLE_SUPERADMIN) {
            if(!isset($this->lastPaidInvoiceInfo[$id])) {
                $queryGetLastPaidInvoiceInfo = "SELECT i.id, i.billdate, i.datepaid "
                        ."FROM invoice i "
                        ."WHERE i.status = 1 "
                        ."  AND i.id IN (SELECT ie.invoiceid "
                        ."               FROM invoiceentry ie "
                        ."               WHERE ie.recurringappliesto = ".$id." "
                        ."              ) "
                        ."ORDER BY i.billdate DESC "
                        ."LIMIT 1 ";
                $resultGetLastPaidInvoiceInfo = $this->db->query($queryGetLastPaidInvoiceInfo);
                list($i_id, $i_billdate, $i_datepaid) = $resultGetLastPaidInvoiceInfo->fetch();
                $this->lastPaidInvoiceInfo[$id] = array(
                        'id'       => $i_id,
                        'billdate' => $i_billdate,
                        'datepaid' => $i_datepaid,
                );
            }
            return $this->lastPaidInvoiceInfo[$id];
        }else {
            return array('id'       => '',
                    'billdate' => '',
                    'datepaid' => '',
            );
        }
    }

    // Converts all the selected charges and all the entries related to the packages of
    // these charges, to not use subscription.
    // Useful for those missed cancellations of paypal subscriptions.
    function cancelSubscription($customerid, $packagesIDs, $entryIds) {
        if($this->showOptionsForOverdueCharges
                && $this->user->getGroupId() == ROLE_SUPERADMIN) {
            foreach($packagesIDs AS $domainID) {
                $query = "UPDATE recurringfee "
                        ."SET disablegenerate='0', "
                        ."subscription_id = '' "
                        ."WHERE appliestoid = ? "
                        ."AND disablegenerate = '1' ";
                $this->db->query($query, $domainID);
            }

            foreach($entryIds AS $id) {
                $query = "UPDATE recurringfee "
                        ."SET disablegenerate='0', "
                        ."subscription_id = '' "
                        ."WHERE id = ? "
                        ."AND disablegenerate = '1' ";
                $this->db->query($query, $id);
            }

            $statuses = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(array(PACKAGE_STATUS_PENDING, PACKAGE_STATUS_ACTIVE));

            // set client payment preference to non-subscription if he has no more recurring subscriptions
            $query = "SELECT r.* "
                ."FROM recurringfee r "
                ."LEFT JOIN domains d ON r.appliestoid = d.id "
                ."WHERE r.customerid = ? "
                ."AND r.disablegenerate != 0 "
                ."AND r.recurring = 1 "
                ."AND (r.appliestoid = 0 OR d.status IN (".implode(', ', $statuses).")) "
                ."AND r.paymentterm != 0 ";
            $result = $this->db->query($query, $customerid);

            if(!$result->getNumRows()) {
                $tUser = new User($customerid);
                $tUser->updateCustomTag('Use Paypal Subscriptions', 0);
                $tUser->save();
            }
        }
    }

    // Suspends or cancels the packages of the selected charges.
    // Useful for avoid customers to keep using the services when they are not paying.
    function suspendCancelPackages($action, $packagesIDs, $TriggerServerPlugin = false) {
        if($this->showOptionsForOverdueCharges
                && $this->user->getGroupId() == ROLE_SUPERADMIN
                && in_array($action, array('suspend', 'cancel'))) {
            require_once 'modules/clients/models/Package_EventLog.php';

            foreach($packagesIDs AS $domainID) {
                $domain = new UserPackage($domainID);
                $customerID = $domain->CustomerId;
                $actionLog = '';

                switch($action) {
                    case "suspend":
                        //$domain->suspend($TriggerServerPlugin);
                        $domain->status = PACKAGE_STATUS_SUSPENDED;
                        $actionLog = 'Suspended';
                        break;
                    case "cancel":
                        //$domain->cancel(isset($_REQUEST['serverplugin']));
                        $domain->status = PACKAGE_STATUS_CANCELLED;
                        $actionLog = 'Cancelled';
                        break;
                }

                $packageLog = Package_EventLog::newInstance(false, $customerID, $domainID, PACKAGE_EVENTLOG_CHANGEDSTATUS, $this->user->getId(), $actionLog);
                $packageLog->save();
            }
        }
    }

    // Changes the value of Next Due Date to a given date in all the selected charges
    // and all the entries related to the packages of these charges, and also changes
    // the value of Next Due Date on those packages.
    // Useful to correct some wrong Next Due Dates.
    // Can only be used over entries that are set as no subscriptions. In case you need to
    // do it over a subscription, then execute first the Cancel Subscriptions action.
    function resetDueDate($nextBillDate, $packagesIDs, $entryIds) {
        if($this->showOptionsForOverdueCharges
                && $this->user->getGroupId() == ROLE_SUPERADMIN) {
            $statuses = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(array(PACKAGE_STATUS_PENDING, PACKAGE_STATUS_ACTIVE));
            foreach($packagesIDs AS $domainID) {
                $sql = "SELECT r.packageaddon_prices_id "
                    ."FROM recurringfee r "
                    ."LEFT JOIN domains d ON r.appliestoid = d.id "
                    ."WHERE r.appliestoid = ? "
                    ."AND r.disablegenerate = '0' "
                    ."AND d.status IN (".implode(', ', $statuses).") "
                    ."AND r.billingtypeid='".BILLINGTYPE_PACKAGE_ADDON."' ";
                $sqlresult = $this->db->query($sql, $domainID);

                while (list($packageaddon_prices_id) = $sqlresult->fetch()) {
                    $updateQuery2 = "UPDATE domain_packageaddon_prices "
                            ."SET nextbilldate = ? "
                            ."WHERE domain_id=? "
                            ."AND packageaddon_prices_id=? ";
                    $this->db->query($updateQuery2, $nextBillDate, $domainID, $packageaddon_prices_id);
                }

                $query = "UPDATE recurringfee "
                        ."SET nextbilldate = ? "
                        ."WHERE appliestoid = ? "
                        ."AND disablegenerate = '0' ";
                $this->db->query($query, $nextBillDate, $domainID);

                /*
                $query = "UPDATE domains "
                        ."SET  nextbilldate = ? "
                        ."WHERE id = ? ";
                $this->db->query($query, $nextBillDate, $domainID);
                */
            }

            foreach($entryIds AS $id) {
                $query = "UPDATE recurringfee "
                        ."SET nextbilldate = ? "
                        ."WHERE id = ? "
                        ."AND disablegenerate = '0' ";
                $this->db->query($query, $nextBillDate, $id);
            }
        }
    }
}
?>
