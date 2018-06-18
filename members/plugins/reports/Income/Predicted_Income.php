<?php
/**
 * Predicted Income Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */

require_once 'modules/admin/models/Package.php';
require_once 'modules/clients/models/UserPackage.php';
require_once 'modules/billing/models/Currency.php';
require_once 'modules/clients/models/User.php';
require_once 'modules/billing/models/Invoice.php';

/**
 * Predicted_Income Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */
class Predicted_Income extends Report
{
    private $lang;

    protected $featureSet = 'billing';

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Predicted Income');
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
        $this->SetDescription($this->user->lang('Displays the total predicted income per month.'));

        @set_time_limit(0);

        // Load the currency information
        $currency = new Currency($this->user);

        $userStatuses = StatusAliasGateway::userActiveAliases($this->user);
        $packageStatuses = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(array(PACKAGE_STATUS_PENDING, PACKAGE_STATUS_ACTIVE));

        $amountOfMonths = ((isset($_REQUEST['amountOfMonths']))? $_REQUEST['amountOfMonths'] : 12);

        //Get recurring fees
        $reportSQL = "SELECT r.`customerid`, "
            ."r.`amount`, "
            ."r.`nextbilldate`, "
            ."r.`appliestoid`, "
            ."r.`billingtypeid`, "
            ."r.`taxable`, "
            ."r.`paymentterm` "
            ."FROM `recurringfee` r "
            ."INNER JOIN `users` u "
            ."ON r.`customerid` = u.`id` "
            ."LEFT JOIN `domains` d "
            ."ON r.`appliestoid` = d.`id` "
            ."WHERE r.`nextbilldate` <> 'NULL' "
            ."AND DATE_FORMAT(r.`nextbilldate`, '%Y%m') <= DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL ".($amountOfMonths - 1)." MONTH), '%Y%m') "
            ."AND u.`status` IN(".implode(', ', $userStatuses).") "
            ."AND (r.`appliestoid` = 0 "
            ."OR (r.`appliestoid` <> 0 "
            ."AND d.`status` IN(".implode(', ', $packageStatuses).") "
            ."AND (r.`billingtypeid` <> ".BILLINGTYPE_PACKAGE." "
            ."OR (r.`billingtypeid` = ".BILLINGTYPE_PACKAGE." "
            ."AND IFNULL(r.`recurring`, 0) = 1)))) "
            ."AND r.`paymentterm` != 0 "
            ."ORDER BY r.`nextbilldate` ";
        $result = $this->db->query($reportSQL);

        $masterGroup = array();

        while(list($customerid, $amount, $nextbilldate, $appliestoid, $billingtype, $taxable, $paymentterm) = $result->fetch()) {
            // Check for taxes
            $tax = 0;
            $customertax          = 0;
            $customertax2         = 0;
            $customertax2compound = 0;
            $user = new User($customerid);

            if ($user->IsTaxable()==1){
                //determine country and state and see if there is a tax in the rules to match
                $customertax  = $user->GetTaxRate();
                $customertax2 = $user->GetTaxRate(2);
                if($user->isTax2Compound()){
                    $customertax2compound = 1;
                }
            }

            if($appliestoid > 0 && $billingtype == -1) {
                $domain = new UserPackage($appliestoid);
                $paymentterm = $domain->getPaymentTerm();
                $taxable = $domain->isTaxable()? true : false;
                $amount = $domain->getPrice(false);
            }

            if($taxable){
                $tax1 = round($amount * $customertax / 100, 2);

                if($customertax2compound){
                    $tax2 = round(($amount + $tax1) * $customertax2 / 100, 2);
                }else{
                    $tax2 = round($amount * $customertax2 / 100, 2);
                }

                $tax = $currency->format($this->settings->get('Default Currency'), $tax1 + $tax2);
            }

            $masterGroup[] = array($nextbilldate, $amount, $tax, $paymentterm);
        }

        $newMonthTotals  = array();

        //Get amounts already invoiced
        $reportSQL2 = "SELECT `billdate`, "
            ."IFNULL(`amount`, 0), "
            ."IFNULL(`subtotal`, 0) "
            ."FROM `invoice` "
            ."WHERE DATE_FORMAT(`billdate`, '%Y%m') >= DATE_FORMAT(CURDATE(), '%Y%m') "
            ."AND DATE_FORMAT(`billdate`, '%Y%m') <= DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL ".($amountOfMonths - 1)." MONTH), '%Y%m') "
            ."AND `status` IN (".INVOICE_STATUS_UNPAID.", ".INVOICE_STATUS_PAID.", ".INVOICE_STATUS_PENDING.", ".INVOICE_STATUS_PARTIALLY_PAID.") ";
        $result2 = $this->db->query($reportSQL2);

        //Initialize months income
        for($monthsAhead = 0; $monthsAhead < $amountOfMonths; $monthsAhead++){
            $newMonthTotals[date("Y M", mktime(0, 0, 0, date("m") + $monthsAhead, 1, date("Y")))] = array(
                'amount' => 0,
                'taxes'  => 0,
                'total'  => 0
            );
        }

        //Add invoices already generated
        while(list($billdate, $amount, $subtotal) = $result2->fetch()) {
            $monthAndYear = date("Y M", mktime(0, 0, 0, date("m", strtotime($billdate)), 1, date("Y", strtotime($billdate))));
            if(isset($newMonthTotals[$monthAndYear])){
                $newMonthTotals[$monthAndYear]['amount'] += $subtotal;
                $newMonthTotals[$monthAndYear]['taxes']  += $amount - $subtotal;
                $newMonthTotals[$monthAndYear]['total']  += $amount;
            }
        }

        $maxMonthAndYear = date("Ym", mktime(0, 0, 0, date("m") + $amountOfMonths - 1, 1, date("Y")));

        //Add recurring charges
        foreach($masterGroup as $masterGroupinfo){
            $nextbilldate    = $masterGroupinfo[0];
            $amount          = $masterGroupinfo[1];
            $taxes           = $masterGroupinfo[2];
            $paymentterm     = $masterGroupinfo[3];
            if($paymentterm == 0){
                break;
            }

            $monthsAhead = 0;
            $currentMonthAndYear = 0;
            do{
                $monthAndYear = date("Y M", mktime(0, 0, 0, date("m", strtotime($nextbilldate)) + $monthsAhead, 1, date("Y", strtotime($nextbilldate))));
                if(isset($newMonthTotals[$monthAndYear])){
                    $newMonthTotals[$monthAndYear]['amount'] += $amount;
                    $newMonthTotals[$monthAndYear]['taxes']  += $taxes;
                    $newMonthTotals[$monthAndYear]['total']  += $amount + $taxes;
                }
                $monthsAhead += $paymentterm;
                $currentMonthAndYear = date("Ym", mktime(0, 0, 0, date("m", strtotime($nextbilldate)) + $monthsAhead, 1, date("Y", strtotime($nextbilldate))));
            }while($currentMonthAndYear <= $maxMonthAndYear);
        }

        $this->SetDescription($this->user->lang('Displays predicted income'));

        $MonthsToDisplay =
             '<form id="report" method="GET">'
            .'    <div style="text-align:center">'
            .'        Months to display: '
            .'        <input type="text" name="amountOfMonths" id="amountOfMonths" size="2" value="'.CE_Lib::viewEscape($amountOfMonths).'"> '
            .'        <input type=button name=search class="btn" value=\''.$this->user->lang('search').'\' onclick="ChangeTable(document.getElementById(\'amountOfMonths\').value);">'
            .'    </div>'
            .'</form>'
            .'</br>'
            .'<script type="text/javascript">'
            .'    function ChangeTable(amountOfMonths){'
            .'        location.href="index.php?fuse=reports&view=viewreport&controller=index&report=Predicted+Income&type=Income&amountOfMonths="+amountOfMonths;'
            .'    }'
            .'</script>';
        echo $MonthsToDisplay;

        $subGroup = array();
        foreach($newMonthTotals AS $monthAndYear => $monthData){
            $subGroup[] = array(
                $monthAndYear,
                $currency->format($this->settings->get('Default Currency'), $monthData['amount'], true, false),
                $currency->format($this->settings->get('Default Currency'), $monthData['taxes'], true, false),
                $currency->format($this->settings->get('Default Currency'), $monthData['total'], true, false)
            );
        }

        $this->reportData[] = array(
            "group" => $subGroup,
            "groupname" => "",
            "label" => array(
                $this->user->lang('Month and Year'),
                $this->user->lang('Predicted Subtotal'),
                $this->user->lang('Predicted Tax'),
                $this->user->lang('Predicted Total Income')
            )
        );
    }

}
?>
