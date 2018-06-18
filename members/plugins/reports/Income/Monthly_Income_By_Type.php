<?php
/**
 * Monthly Income By Type Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.2
 * @link     http://www.clientexec.com
 *
 *************************************************
 *   1.0 Initial Report Released.  - Alberto Vasquez
 *   1.2 Updated report to include a title & PEAR commenting
 ************************************************
 */

require_once 'modules/billing/models/Currency.php';
require_once 'modules/billing/models/BillingType.php';

/**
 * Monthly_Income_By_Type Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.2
 * @link     http://www.clientexec.com
 */
class Monthly_Income_By_Type extends Report
{
    private $lang;

    protected $featureSet = 'billing';
    public $hasgraph = true;

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Monthly Income By Type');
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
        $this->SetDescription($this->user->lang('Displays total recurring transactions broken down by package types with the sum and expected monthly income from each.'));

        // Load the currency information
        $currency = new Currency($this->user);

        $graphdata = @$_GET['graphdata'];

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
        while($row = $result->fetch()) {
            $inSQL="SELECT t.name, "
                ."p.planname, "
                ."p.pricing AS price, "
                ."t.type "
                ."FROM package p, "
                ."promotion t "
                ."WHERE p.id = ? "
                ."AND t.id = p.planid ";
            $result2=$this->db->query($inSQL, $row['Plan']);
            while($row2 = $result2->fetch()) {
                //Now we do the math, and add the class array and variables
                $pricing = unserialize($row2['price']);

                if($row2['type'] == 3){  // Domain Type
                    $aDomainName = $dng->splitDomain($row['domain_name']);
                    $tld = strtolower($aDomainName[1]);
                    $packagePrice = 0;

                    foreach ($pricing As $key => $value) {
                        $pricingInformation[$key] = $value;
                    }
                    $pricingArray = array_pop($pricingInformation['pricedata']);
                    if($row['paymentterm'] != 0 && ($row['paymentterm'] % 12) == 0 && isset($pricingArray[($row['paymentterm']/12)]['price'])){
                        $packagePrice = $pricingArray[($row['paymentterm']/12)]['price'] / $row['paymentterm'];
                    }
                }else{
                    $packagePrice = 0;

                    if($row['paymentterm'] != 0 && isset($pricing['price'.$row['paymentterm']])){
                        $packagePrice = $pricing['price'.$row['paymentterm']]/$row['paymentterm'];
                    }
                }

                //this is for overrided prices
                if($row['paymentterm'] != 0 && $row["use_custom_price"]) {
                    $packagePrice = $row["custom_price"]/$row['paymentterm'];
                }

                $tMonthlyIncome = $packagePrice * $row['counted'];
                $tPackageType = $row2['name'];
                $tPackageCount = $row['counted'];

                $expectedincomeTotal += $tMonthlyIncome;
                $sumpertotalpackagesTotal += $tPackageCount;

                if ($tMonthlyIncome > 0) {
                    if (isset($aGroup[$tPackageType])) {
                        $tArray = $aGroup[$tPackageType];
                        $aGroup[$tPackageType] = array($tPackageType,$tArray[1]+$tPackageCount,$tArray[2]+$tMonthlyIncome);
                        //$aGraphGroup[$tPackageType] = array($tPackageType,$tArray[1]+$tPackageCount,$tArray[2]+$tMonthlyIncome);
                    }else {
                        $aGroup[$tPackageType] = array($tPackageType,$tPackageCount,$tMonthlyIncome);
                        //$aGraphGroup[$tPackageType] = array($tPackageType,$tPackageCount,$tMonthlyIncome);
                    }
                }

                // NOTE: remember the addon cycle can be different than the package's
                $sql = "SELECT COUNT(*) AS counted, "
                        ."SUM(rf.amount) AS total, "
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
                $groupName = "$tPackageType add-ons";
                while ($row3 = $result3->fetch()) {
                    $tAddonCount = $row3['counted'];

                    //get expected monthly based on term
                    if(in_array($row3['paymentterm'], array(1, 3, 6, 12, 24, 36, 48, 60, 72, 84, 96, 108, 120))){
                        $row3['total'] = $row3['total'] / $row3['paymentterm'];
                    } else {
                        continue;
                    }
                    $tExpectedIncome = $currency->format($this->settings->get('Default Currency'), $row3['total'], true, false);
                    $expectedincomeTotal += $row3['total'];

                    if($row3['total'] > 0) {
                        if (isset($aGroup[$groupName])) {
                            $tArray = $aGroup[$groupName];
                            $aGroup[$groupName] = array($groupName, $tArray[1] + $tAddonCount, $tArray[2] + $row3['total']);
                            //$aGraphGroup[$groupName] = array($groupName, $tArray[1] + $tAddonCount, $tArray[2] + $row3['total']);
                        } else {
                            $aGroup[$groupName] = array($groupName, $tAddonCount, $row3['total']);
                            //$aGraphGroup[$groupName] = array($groupName, $tAddonCount, $row3['total']);
                        }
                    }

                }
            }
        }

        if($graphdata && isset($aGroup)) {
            //this supports lazy loading and dynamic loading of graphs
            $this->reportData = $this->GraphData($aGroup);
            return;
        }

        if (isset($aGroup)) {

            //Loop through group array and change price format now that all the sums have been made

            foreach ($aGroup as $tGroup) {
                //$aGroup[$tGroup[0]][2] = $currency->format($this->settings->get('Default Currency'), $aGroup[$tGroup[0]][2], true);
                $aGroupWithCurrency[] = array($tGroup[0],$tGroup[1],
                    $currency->format($this->settings->get('Default Currency'), $tGroup[2], true));
            }
            $aGroup = $aGroupWithCurrency;

            $this->reportData[] = array(
                "group" => $aGroup,
                "groupname" => $this->user->lang("Package Types"),
                "label" => array($this->user->lang('Package Type'),$this->user->lang('Total Packages'),$this->user->lang('Expected Monthly Income')),
                "groupId" => "",
                "isHidden" => false);

            unset($aGroup);
        }

        $expectedincomeTotal = $currency->format($this->settings->get('Default Currency'), $expectedincomeTotal, true);
        $aGroup[] = array("--------","<b>" . $sumpertotalpackagesTotal . "</b>","<b>" . $expectedincomeTotal . "</b>");

        $this->reportData[] = array(
                "istotal" => true,
                "group" => $aGroup,
                "label" => array("","",""),
                "groupId" => "",
                "isHidden" => false);
    }


    /**
     * Function to output the xml for the graph data
     *
     * @return XML - graph data
     */
    function GraphData($aGroups) {



        //get default currency symbol
        $currency = new Currency($this->user);
        if (strtoupper($this->settings->get('Default Currency')) == 'EUR') {
            $currencySymbol = 'EUR';
        } elseif (strtoupper($this->settings->get('Default Currency')) == 'GBP') {
            $currencySymbol = 'GBP';
        } elseif (strtoupper($this->settings->get('Default Currency')) == 'JPY') {
            $currencySymbol = 'JPY';
        } else {
            $currencySymbol = $currency->ShowCurrencySymbol($this->settings->get('Default Currency'));
        }

        $graph_data = array(
              "xScale" => "ordinal",
              "yScale" => "exponential",
              "xType" => "number",
              "yType" => "currency",
              "yPre" => $currencySymbol,
              "yFormat" => "addcomma",
              "type" => "bar",
              "main" => array());

        $group_data = array();
        $group_data['className'] = ".report";
        $group_data['data'] = array();

        $index = 0;
        foreach ($aGroups as $group) {
            $pretty_total = $currency->format($this->settings->get('Default Currency'),$group[2],true);

            $data = array();
            $data["x"] = array($index,substr($group[0], 0, 15));
            $data["y"] = $group[2];
            $data["tip"] = "<strong>".$group[0]."</strong><br/>".$group[1]." Packages for ".$pretty_total;
            $group_data["data"][] = $data;
            $index++;
        }
        $graph_data["main"][] = $group_data;
        return json_encode($graph_data);

    }

    /**
     * Function to return the average, used for making the pie chart
     *
     * @return num - average value
     */
    function ReturnAverage($count,$totalCount) {
        $avg = ($count/$totalCount);
        $avg =  $avg * 100;
        return ceil($avg);
    }
}
?>
