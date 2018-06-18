<?php
/**
 * Monthly Income Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.5
 * @link     http://www.clientexec.com
 *
 *************************************************
 *   1.0 Initial Report Released.  - Bart Wegrzyn
 *   1.1 Fixed to account for yearly, semi-yearly,  quarterly, and monthly pricing.  - Shane Sammons
 *   1.2 Only show those packages that have recurring prices not all packages.  - Alberto Vasquez
 *   1.3 Only show those packages for active customers.  - Alberto Vasquez
 *   1.4 Refactored to show income from addons as well - Alejandro Pedraza
 *   1.5 Refactored report to adhere to Pear standards & Updated the package names that are shown - Jason Yates
 ************************************************
 */

require_once 'modules/billing/models/Currency.php';
require_once 'modules/billing/models/BillingType.php';
require_once('modules/clients/models/DomainNameGateway.php');

/**
 * Monthly_Income Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.5
 * @link     http://www.clientexec.com
 */
class Monthly_Income extends Report
{
    private $lang;

    protected $featureSet = 'billing';

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Monthly Income');
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
        $this->SetDescription($this->user->lang('Displays total recurring transactions broken down by packages with the sum and expected monthly income from each.'));

        // Load the currency information
        $currency = new Currency($this->user);

        $userStatuses = StatusAliasGateway::userActiveAliases($this->user);
        $packageStatuses = StatusAliasGateway::packageActiveAliases($this->user);

        //SQL to generate the the result set of the report
        $sql = "SELECT COUNT(*) AS counted, "
            ."d.Plan, "
            ."rf.paymentterm, "
            ."d.use_custom_price, "
            ."d.custom_price, "
            ."ocf.value AS domain_name "
            ."FROM domains d "
            ."LEFT JOIN `recurringfee` rf "
            ."ON rf.appliestoid = d.id "
            ."AND rf.billingtypeid = -1 "
            ."LEFT JOIN object_customField ocf "
            ."ON ocf.objectid = d.id "
            ."AND ocf.customFieldId = (SELECT cf.id "
            ."FROM customField cf "
            ."WHERE groupId = 2 "
            ."AND subGroupId  = 3 "
            ."AND name  = 'Domain Name'), "
            ."users u "
            ."WHERE rf.paymentterm <> 0 "
            ."AND IFNULL(rf.recurring, 0) <> 0 "
            ."AND d.customerid = u.id "
            ."AND u.status IN(".implode(', ', $userStatuses).") "
            ."AND d.status IN(".implode(', ', $packageStatuses).") "
            ."GROUP BY rf.paymentterm, d.Plan, d.use_custom_price, d.custom_price ";
        $result = $this->db->query($sql);
        $expectedincomeTotal = 0;
        $sumpertotalpackagesTotal = 0;

        // Just in case we have domains
        $dng = new DomainNameGateway($this->user);
        while($row = $result->fetch()){
            $inSQL = "SELECT p.planname as planname, "
                ."g.name as groupname, "
                ."p.pricing, "
                ."g.type "
                ."FROM package p, "
                ."promotion g "
                ."WHERE p.id = ? "
                ."AND p.planid = g.id ";
            $result2=$this->db->query($inSQL, $row['Plan']);
            while($row2 = $result2->fetch()){
                $pricing = unserialize($row2['pricing']);

                if($row2['type'] == 3){  // Domain Type
                    $aDomainName = $dng->splitDomain($row['domain_name']);
                    $tld = strtolower($aDomainName[1]);

                    $packagePrice = 0;
                    $append = '';

                    foreach ($pricing As $key => $value) {
                        $pricingInformation[$key] = $value;
                    }
                    $pricingArray = array_pop($pricingInformation['pricedata']);
                    if($row['paymentterm'] != 0 && ($row['paymentterm'] % 12) == 0 && isset($pricingArray[($row['paymentterm']/12)]['price'])){
                        $packagePrice = $pricingArray[($row['paymentterm']/12)]['price'] / $row['paymentterm'];

                        if($row['paymentterm'] == 12){
                            $append = " (annually)";
                        }else{
                            $append = " (every ".($row['paymentterm']/12)." years)";
                        }
                    }else{
                        $append = '(unsupported cycle)';
                    }
                }else{
                    $packagePrice = 0;
                    $append = '';

                    if($row['paymentterm'] != 0 && isset($pricing['price'.$row['paymentterm']])){
                        $packagePrice = $pricing['price'.$row['paymentterm']]/$row['paymentterm'];
                    }

                    switch($row['paymentterm']){
                        case 1:
                            break;
                        case 3:
                            $append = " (quarterly)";
                            break;
                        case 6:
                            $append = " (semi-annualy)";
                            break;
                        case 12:
                            $append = " (annually)";
                            break;
                        default:
                            if($row['paymentterm'] > 12 && ($row['paymentterm'] % 12) == 0){
                                $append = " (every ".($row['paymentterm']/12)." years)";
                            }else{
                                $append = '(unsupported cycle)';
                            }
                            break;
                    }
                }

                if($row['paymentterm'] != 0 && $row["use_custom_price"]){
                     $packagePrice = $row["custom_price"] / $row['paymentterm'];
                     $append .= " (overridden)";
                }

                $tMonthlyIncome = $packagePrice * $row['counted'];
                //echo "Plan Name: ".
                $tPackageName = $row2['planname']." / ".$row2['groupname'].$append;
                //echo "Quantity: ".
                $tPackageCount = $row['counted'];

                $tExpectedIncome = $currency->format($this->settings->get('Default Currency'), $tMonthlyIncome, true);
                $expectedincomeTotal += $tMonthlyIncome;
                $sumpertotalpackagesTotal += $tPackageCount;
                $aGroup[] = array($tPackageName,$tPackageCount,$tExpectedIncome);

                // NOTE: remember the addon cycle can be different than the package's
                $sql = "SELECT SUM(rf.amount) AS total, "
                      ."rf.paymentterm "
                      ."FROM recurringfee rf "
                      ."LEFT JOIN (domains d "
                      ."LEFT JOIN `recurringfee` rrff "
                      ."ON rrff.appliestoid = d.id "
                      ."AND rrff.billingtypeid = -1) "
                      ."ON rf.appliestoid = d.id "
                      ."WHERE rf.billingtypeid = ? "
                      ."AND d.Plan = ? "
                      ."AND d.status IN(".implode(', ', $packageStatuses).") "
                      ."AND rrff.paymentterm = ? "
                      ."GROUP BY rf.paymentterm ";
                $result3 = $this->db->query($sql, BILLINGTYPE_PACKAGE_ADDON, $row['Plan'], $row['paymentterm']);

                while ($row3 = $result3->fetch()) {
                    if(in_array($row3['paymentterm'], array(1, 3, 6, 12, 24, 36, 48, 60, 72, 84, 96, 108, 120))){
                        $row3['total'] = $row3['total'] / $row3['paymentterm'];
                    } else {
                        continue;
                    }
                    if($row3['paymentterm']== 1){
                            $append = 'Monthly';
                    }elseif($row3['paymentterm']== 3){
                            $append = "Quarterly";
                    }elseif($row3['paymentterm']== 6){
                            $append = "Semi-Annualy";
                    }elseif($row3['paymentterm']== 12){
                            $append = "Annually";
                    }elseif(in_array($row3['paymentterm'], array(24, 36, 48, 60, 72, 84, 96, 108, 120))){
                            $append = "Every ".($row3['paymentterm']/12)." Years";
                    }else{
                        continue;
                    }
                    $tExpectedIncome = $currency->format($this->settings->get('Default Currency'), $row3['total'], true);
                    $expectedincomeTotal += $row3['total'];
                    $aGroup[] = array("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $append Add-ons", '', $tExpectedIncome);
                }
            }
        }

          if (isset($aGroup)){
              $this->reportData[] = array(
                "group" => $aGroup,
                "groupname" => $this->GetExtendedName(@$oldtotalpackages),
                "label" => array($this->user->lang('Package Name'),$this->user->lang('Total Packages'),$this->user->lang('Expected Monthly Income')),
                "groupId" => "",
                "isHidden" => false);
              unset($aGroup);
          }

          $expectedincomeTotal = $currency->format($this->settings->get('Default Currency'), $expectedincomeTotal, true);
          $aGroup[] = array("--------","<b>" . $sumpertotalpackagesTotal . "</b>","<b>" . $expectedincomeTotal . "</b>");

          $this->reportData[] = array(
                "group" => $aGroup,
                "groupname" => $this->user->lang('Totals'),
                "label" => array("","",""),
                "groupId" => "",
                "isHidden" => false);

    }

    //*********************************************
    // Custom Function Definitions for this report
    //*********************************************

    //function to translate the digit billing cycle to the preferred header ( e.g. 1 returns Monthly )
    function GetExtendedName($totalpackages){
           switch ($totalpackages){
                   case 1:
                        return "Monthly";
                        break;
                   case 3:
                        return "Quarterly";
                        break;
                   case 6:
                        return "Semiannually";
                        break;
                   case 12:
                        return "Annual";
                        break;
                   case 24:
                        return "Every 2 Years";
                        break;
                   case 36:
                        return "Every 3 Years";
                        break;
                   case 48:
                        return "Every 4 Years";
                        break;
                   case 60:
                        return "Every 5 Years";
                        break;
                   case 72:
                        return "Every 6 Years";
                        break;
                   case 84:
                        return "Every 7 Years";
                        break;
                   case 96:
                        return "Every 8 Years";
                        break;
                   case 108:
                        return "Every 9 Years";
                        break;
                   case 120:
                        return "Every 10 Years";
                        break;
           }
    }
}
?>
