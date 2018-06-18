<?php
/**
 * Client Monthly Retention Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan D. Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 *
 *************************************************
 *   1.0 Initial Report Released.  - Juan D. Bolivar
 ************************************************
 */

/**
 * Client_Monthly_Retention Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan D. Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */
class Client_Monthly_Retention extends Report
{
    private $lang;

    protected $featureSet = 'accounts';

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Client Monthly Retention From Last Year');
        parent::__construct($user,$customer);
    }

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process()
    {
        $this->SetDescription($this->user->lang('Displays the percentage of customers retained from each period in the last 12 months.'));

        // *** SQL to generate the the result set of the report ***

        //HOW MANY CUSTOMERS RETAINED FROM EACH PERIOD (MONTH AND YEAR). CONTINUES WITH BILLING ACTIVITY IN THE LATEST MONTH (HAS AN INVOICE CREATED, DUE OR PAID IN THE LAST 30 DAYS).
        $sql1 = "SELECT DATE_FORMAT(u.`dateActivated`,'%M %Y') AS period, "
              ."COUNT(*) "
              ."FROM `users` u "
              ."WHERE u.`groupid` = 1 "
              ."AND u.`id` IN (SELECT DISTINCT r.`customerid` "
              ."    FROM `recurringfee` r "
              ."    WHERE r.`paymentterm` = 1) "
              ."AND u.`id` IN (SELECT DISTINCT i.`customerid` "
              ."    FROM `invoice` i "
              ."    WHERE (TO_DAYS(NOW()) - TO_DAYS(i.`datecreated`)) <= 30 "
              ."    OR (TO_DAYS(NOW()) - TO_DAYS(i.`billdate`)) <= 30 "
              ."    OR (TO_DAYS(NOW()) - TO_DAYS(i.`datepaid`)) <= 30) "
              ."GROUP BY period "
              ."ORDER BY u.`dateActivated` ASC ";
        $result1 = $this->db->query($sql1);

        $aCustomersRetained = array();
        while (list($element, $total) = $result1->fetch()) {
            $aCustomersRetained[$element] = $total;
        }

        //HOW MANY CUSTOMERS (GROUP 1), THAT CREATED THE ACCOUNT THAT PERIOD (MONTH AND YEAR), THAT HAD A MONTHLY RECURRING FEE (PAYMENT TERM 1) AND AT LEAST 1 INVOICE
        $sql2 = "SELECT DATE_FORMAT(u.`dateActivated`,'%M %Y') AS period, "
              ."COUNT(*), "
              ."YEAR(u.`dateActivated`) AS year, "
              ."DATE_FORMAT(u.`dateActivated`,'%M') AS month "
              ."FROM `users` u "
              ."WHERE u.`groupid` = 1 "
              ."AND u.`id` IN (SELECT DISTINCT r.`customerid` "
              ."    FROM `recurringfee` r "
              ."    WHERE r.`paymentterm` = 1) "
              ."AND u.`id` IN (SELECT DISTINCT i.`customerid` "
              ."    FROM `invoice` i) "
              ."GROUP BY period "
              ."ORDER BY u.`dateActivated` ASC ";
        $result2 = $this->db->query($sql2);

        $aYears = array();

        while (list($element, $total, $year, $month) = $result2->fetch()) {
            $mostRecentYear = $year;
            $aYears[$year][$month] = array(
                'element' => $element,
                'total'   => $total
            );
        }

        foreach ($aYears as $year => $aMonths) {
            $average = 0;
            $grouphidden = ($year != $mostRecentYear);
            $aGroup = array();

            foreach ($aMonths as $month => $values) {
                if (!isset($aCustomersRetained[$values['element']])) {
                    $aCustomersRetained[$values['element']] = 0;
                }
                $average += round(($aCustomersRetained[$values['element']] * 100 / $values['total']), 2);
                $aGroup[] = array($month, round(($aCustomersRetained[$values['element']] * 100 / $values['total']), 2)."%");
            }

            $aGroup[] = array('', '');
            $aGroup[] = array($this->user->lang('Average').' ('.$year.')', round(($average / (count($aGroup) - 1)), 2)."%");

            $groupid = "id-".$year;
            if (isset($aGroup)) {
                $this->reportData[] = array(
                    "group"     => $aGroup,
                    "groupname" => $this->user->lang('New Customer Retention').' ('.$year.')',
                    "label"     => array($this->user->lang('Month'),$this->user->lang('Retention')),
                    "groupId"   => $groupid,
                    "isHidden"  => $grouphidden
                );
                unset($aGroup);
            }
        }

        //Display Report
        echo "<script type='text/javascript'>";
        echo "function ShowYears(obj){";
        echo "    var strYear = obj.value;";

        //Loop the number of years
        foreach ($aYears as $year => $aMonths) {
            echo "if (document.getElementById('id-$year') != null) {";
            echo "    if (strYear == '$year') {";
            echo "        document.getElementById('id-$year').style.display = '';";
            echo "    } else {";
            echo "        document.getElementById('id-$year').style.display = 'none';";
            echo "    }";
            echo "}";
        }

        echo "}";
        echo "</script>";
        echo $this->user->lang("Percentage of customers in each period (month and year) that created the account that period, had at least one monthly recurring fee and one invoice, and continues with billing activity in the latest month (has an invoice created, due or paid in the last 30 days).");
        echo "<br>";
        echo "<br>";
        echo "<br>";
        echo "<div style='margin-left:20px;'>";
        echo "    <form id='reportdropdown' method='GET'>";
        echo $this->user->lang('Select Year Range')."<br/>";
        echo "        <select id='passedyear' name='passedyear' onChange='ShowYears(this);'>";
        
        //Loop the number of years
        foreach ($aYears as $year => $aMonths) {
            if ($year == $mostRecentYear) {
                echo "<option value='".$year."' SELECTED>".$year."</option>";
            } else {
                echo "<option value='".$year."'>".$year."</option>";
            }
        }

        echo "        </select>";
        echo "    </form>";
        echo "</div>";
    }
}