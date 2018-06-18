<?php
/**
 * Billing Cycle Income Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.1
 * @link     http://www.clientexec.com
 *
 *************************************************
 *   1.0 Initial Report Released
 *   1.1 Updated report to include a title & PEAR commenting
 ************************************************
 */

require_once 'modules/billing/models/Currency.php';
require_once 'modules/billing/models/BillingType.php';
require_once('modules/clients/models/DomainNameGateway.php');

/**
 * Billing_Cycle_Income Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.1
 * @link     http://www.clientexec.com
 */
class Billing_Cycle_Income extends Report
{
    private $lang;

    protected $featureSet = 'billing';

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Billing Cycle Income');
        parent::__construct($user,$customer);
    }

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process()
    {
        include_once 'modules/admin/models/StatusAliasGateway.php' ;

        // Set the report information
        $this->SetDescription($this->user->lang('Displays total recurring transactions broken down by billing cycles with the sum and expected yearly income from each.'));

        // Load the currency information
        $currency = new Currency($this->user);

        // Array to store all the totals
        $billingCycleIncome = array();

        $userStatuses = StatusAliasGateway::userActiveAliases($this->user);
        $packageStatuses = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(array(PACKAGE_STATUS_PENDING, PACKAGE_STATUS_ACTIVE));

        // Get the totals for recurring charges that are not package prices
        $reportSQL = "SELECT COUNT(r.id), "
            ."SUM(r.amount), "
            ."r.paymentterm "
            ."FROM users u, "
            ."recurringfee r "
            ."LEFT JOIN domains d "
            ."ON r.appliestoid = d.id "
            ."WHERE r.customerid = u.id "
            ."AND u.status IN (".implode(', ', $userStatuses).") "
            ."AND (r.appliestoid = 0 "
            ."OR (r.appliestoid <> 0 "
            ."AND d.status IN (".implode(', ', $packageStatuses).") "
            ."AND r.billingtypeid <> ".BILLINGTYPE_PACKAGE.")) "
            ."GROUP BY r.paymentterm "
            ."ORDER BY r.paymentterm ";
        $result = $this->db->query($reportSQL);

        // Fill array with the totals for recurring charges that are not package prices
        while (list($tNumberOfRecurringItems,$tSumPerBillingCycle,$tBillingCycle) = $result->fetch()) {
            if(isset($billingCycleIncome[$tBillingCycle]["NumberOfRecurringItems"])){
                $billingCycleIncome[$tBillingCycle]["NumberOfRecurringItems"] += $tNumberOfRecurringItems;
            }else{
                $billingCycleIncome[$tBillingCycle]["NumberOfRecurringItems"] = $tNumberOfRecurringItems;
            }

            if(isset($billingCycleIncome[$tBillingCycle]["SumPerBillingCycle"])){
                $billingCycleIncome[$tBillingCycle]["SumPerBillingCycle"] += $tSumPerBillingCycle;
            }else{
                $billingCycleIncome[$tBillingCycle]["SumPerBillingCycle"] = $tSumPerBillingCycle;
            }
        }

        // Get the totals for recurring charges that are package prices
        $reportSQL = "SELECT r.paymentterm, "
            ."d.use_custom_price, "
            ."d.custom_price, "
            ."ocf.value AS domain_name, "
            ."p.pricing, "
            ."g.type "
            ."FROM users u, "
            ."recurringfee r, "
            ."domains d "
            ."LEFT JOIN object_customField ocf "
            ."ON ocf.objectid = d.id "
            ."AND ocf.customFieldId = (SELECT cf.id "
            ."FROM customField cf "
            ."WHERE groupId = 2 "
            ."AND subGroupId  = 3 "
            ."AND name  = 'Domain Name'), "
            ."package p, "
            ."promotion g "
            ."WHERE r.appliestoid = d.id "
            ."AND r.customerid = u.id "
            ."AND u.status IN (".implode(', ', $userStatuses).") "
            ."AND r.appliestoid != 0 "
            ."AND d.status IN (".implode(', ', $packageStatuses).") "
            ."AND r.billingtypeid = ".BILLINGTYPE_PACKAGE." "
            ."AND r.recurring = 1 "
            ."AND r.paymentterm != 0 "
            ."AND p.id = d.Plan "
            ."AND p.planid = g.id "
            ."ORDER BY r.paymentterm ";
        $result = $this->db->query($reportSQL);

        // Just in case we have domains
        $dng = new DomainNameGateway($this->user);

        // Fill array with the totals for recurring charges that are package prices
        while (list($tBillingCycle,$tUseCustomPrice,$tCustomPrice,$tDomainName,$tPricing,$tType) = $result->fetch()) {
            if($tUseCustomPrice){
                 $packagePrice = $tCustomPrice;
            }else{
                $pricing = unserialize($tPricing);

                if($tType == 3){  // Domain Type
                    $aDomainName = $dng->splitDomain($tDomainName);
                    $tld = $aDomainName[1];
                    $packagePrice = 0;

                    if(($tBillingCycle % 12) == 0 && isset($pricing['pricedata'][$tld][($tBillingCycle/12)]['price'])){
                        $packagePrice = $pricing['pricedata'][$tld][($tBillingCycle/12)]['price'];
                    }
                }else{
                    $packagePrice = 0;

                    if(isset($pricing['price'.$tBillingCycle])){
                        $packagePrice = $pricing['price'.$tBillingCycle];
                    }
                }
            }

            if(isset($billingCycleIncome[$tBillingCycle]["NumberOfRecurringItems"])){
                $billingCycleIncome[$tBillingCycle]["NumberOfRecurringItems"] += 1;
            }else{
                $billingCycleIncome[$tBillingCycle]["NumberOfRecurringItems"] = 1;
            }

            if(isset($billingCycleIncome[$tBillingCycle]["SumPerBillingCycle"])){
                $billingCycleIncome[$tBillingCycle]["SumPerBillingCycle"] += $packagePrice;
            }else{
                $billingCycleIncome[$tBillingCycle]["SumPerBillingCycle"] = $packagePrice;
            }
        }

        //initialize
        $oldBillingCycle = -1;
        $expectedincomeTotal = 0;
        $sumperbillingcycleTotal = 0;
        $itemTotal = 0;

        foreach($billingCycleIncome AS $tBillingCycle => $billingCycleIncomeData){
            $tNumberOfRecurringItems = $billingCycleIncomeData["NumberOfRecurringItems"];
            $tSumPerBillingCycle = $billingCycleIncomeData["SumPerBillingCycle"];

            if($oldBillingCycle!=$tBillingCycle) {
                if (isset($aGroup)) {
                    //add previous group before getting next group
                    $this->reportData[] = array(
                        "group" => $aGroup,
                        "groupname" => $this->GetExtendedName($oldBillingCycle),
                        "label" => array($this->user->lang('Items'),$this->user->lang('Sum'),$this->user->lang('Expected Yearly Income')),
                        'colStyle' => 'width:200px',
                        "groupId" => "",
                        "isHidden" => false);
                    unset($aGroup);
                }
                $aGroup = array();
                $oldBillingCycle = $tBillingCycle;
            }

            //truncate
            $tExpectedIncome = $currency->format($this->settings->get('Default Currency'), $this->GetExpectedYearlyIncome($tBillingCycle,$tSumPerBillingCycle), true);
            $expectedincomeTotal += $this->GetExpectedYearlyIncome($tBillingCycle,$tSumPerBillingCycle);
            $sumperbillingcycleTotal += $tSumPerBillingCycle;
            $tSumPerBillingCycle = $currency->format($this->settings->get('Default Currency'), $tSumPerBillingCycle, true);
            $aGroup[] = array($tNumberOfRecurringItems,$tSumPerBillingCycle,$tExpectedIncome);
            $itemTotal += $tNumberOfRecurringItems;
        }

        //add final group
        if (isset($aGroup)) {

            $this->reportData[] = array(
                "group" => $aGroup,
                "groupname" => $this->GetExtendedName($oldBillingCycle),
                "label" => array($this->user->lang('Items'),$this->user->lang('Sum'),$this->user->lang('Expected Yearly Income')),
                'colStyle' => 'width:200px',
                "groupId" => "",
                "isHidden" => false);
            unset($aGroup);
        }

        $expectedincomeTotal = $currency->format($this->settings->get('Default Currency'), $expectedincomeTotal, true);
        $sumperbillingcycleTotal = $currency->format($this->settings->get('Default Currency'), $sumperbillingcycleTotal, true);
        $aGroup[] = array($itemTotal,$sumperbillingcycleTotal,$expectedincomeTotal);

        $this->reportData[] = array(
            "group" => $aGroup,
            "groupname" => $this->user->lang('Totals'),
            "label" => array("","",""),
            'colStyle' => 'width:200px',
            "groupId" => "",
            "isHidden" => false);
    }

    //*********************************************
    // Custom Function Definitions for this report
    //*********************************************

    /**
     * function get the expected yearly income per billing cycle
     *
     * @return var - estimated income
     */
    function GetExpectedYearlyIncome($billingCycle,$tempSum)
    {
        switch ($billingCycle) {
            case 1:
                return $tempSum*12;
                break;
            case 3:
                return $tempSum*4;
                break;
            case 6:
                return $tempSum*2;
                break;
            case 12:
                return $tempSum;
                break;
            case 24:
                return $tempSum/2;
                break;
            case 36:
                return $tempSum/3;
                break;
            case 48:
                return $tempSum/4;
                break;
            case 60:
                return $tempSum/5;
                break;
            case 72:
                return $tempSum/6;
                break;
            case 84:
                return $tempSum/7;
                break;
            case 96:
                return $tempSum/8;
                break;
            case 108:
                return $tempSum/9;
                break;
            case 120:
                return $tempSum/10;
                break;
        }
    }

    /**
     * function to translate the digit billing cycle to the preferred header ( e.g. 1 returns Monthly )
     *
     * @return var - header item
     */
    function GetExtendedName($billingCycle)
    {
        switch ($billingCycle) {
            case 1:
                return $this->user->lang('Monthly');
                break;
            case 3:
                return $this->user->lang('Quarterly');
                break;
            case 6:
                return $this->user->lang('Semiannually');
                break;
            case 12:
                return $this->user->lang('Annual');
                break;
            case 24:
                return $this->user->lang('Every 2 Years');
                break;
            case 36:
                return $this->user->lang('Every 3 Years');
                break;
            case 48:
                return $this->user->lang('Every 4 Years');
                break;
            case 60:
                return $this->user->lang('Every 5 Years');
                break;
            case 72:
                return $this->user->lang('Every 6 Years');
                break;
            case 84:
                return $this->user->lang('Every 7 Years');
                break;
            case 96:
                return $this->user->lang('Every 8 Years');
                break;
            case 108:
                return $this->user->lang('Every 9 Years');
                break;
            case 120:
                return $this->user->lang('Every 10 Years');
                break;
        }
    }
}

?>
