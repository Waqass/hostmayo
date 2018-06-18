<?php

require_once 'modules/billing/models/BillingType.php';

/**
 * Coupon Summary Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.1
 * @link     http://www.clientexec.com
 *
 *************************************************
 *   1.1 Updated the report to use Pear Commenting & the new title handing to make app reports consistent.
 ************************************************
 */



/**
 * Coupon_Summary Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.1
 * @link     http://www.clientexec.com
 */
class Coupon_Summary extends Report
{
    private $lang;

    protected $featureSet = 'billing';

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Coupon Summary');
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
        $this->SetDescription($this->user->lang('Displays stats on usage of individual coupon codes.'));

        ?>

<script type="text/javascript">
    function viewCouponSelected(changedType)
    {
        var tagname =document.getElementsByTagName('input');
        for(var i = 0; i < tagname.length; i++) {
            if(tagname[i].type == 'radio' && tagname[i].checked == true){
                var couponType = tagname[i].value;
            }
        }
        if(changedType == 1){
            document.getElementById("coupons").value = 0;
        }

        location.href='index.php?fuse=reports&report=Coupon_Summary&controller=index&type=Income&view=viewreport&couponId='+document.getElementById("coupons").value+'&isRecurring='+couponType;
    }
</script>
        <?php

        $aGroup = array();
        $i = 0;
        $selected_normal = "";
        $selected_recurring = "";
        $isRecurring = (isset($_GET['isRecurring']))? $_GET['isRecurring']:0;

        if(isset($_GET['isRecurring'])) {
            if($_GET['isRecurring'] == 0) {
                $selected_normal = "checked";
            }else {
                $selected_recurring = "checked";
            }
        } else {
            $selected_normal = "checked";
        }

        echo "<div style='margin-left:20px;'>";
        echo '<label class="radio">';
        echo "<input type='radio' name='coupon_type' value='0' onChange='viewCouponSelected(1)' $selected_normal>";
        echo " Normal (non recurring)";
        echo '</label>';
        echo '<label class="radio">';
        echo "<input type='radio' name='coupon_type' value='1' onChange='viewCouponSelected(1)' $selected_recurring> Recurring";
        echo '</label>';


        $couponsInUse = "SELECT DISTINCT(coupons_id), coupons_name "
                ."FROM coupons "
                ."WHERE coupons_recurring = ?";
        $result = $this->db->query($couponsInUse, $isRecurring);

        echo "<br>".$this->user->lang('Select which coupon you want usage information on:')."<br/>";
        echo "<select id=coupons name=coupons onChange='viewCouponSelected(0)'>";
        echo"<option value=0>".$this->user->lang('--- SELECT ONE ---')."</option>";
        while (list($coupons_id, $coupons_name) = $result->fetch()) {
            if(isset($_GET['couponId']) && $_GET['couponId'] == $coupons_id) {
                echo"<option selected value=".$coupons_id.">".$coupons_name."</option>";
            }else {
                echo"<option value=".$coupons_id.">".$coupons_name."</option>";
            }
        }
        echo "</select>";
        echo "</div>";


        if(!isset($_GET['couponId'])) {
            $_GET['couponId'] = 0;
        }

        $notUsedCoupons = "SELECT coupons_quantity "
                ."FROM coupons "
                ."WHERE coupons_id = ? ";

        $result = $this->db->query($notUsedCoupons, $_GET['couponId']);

        $statuses = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(array(PACKAGE_STATUS_PENDING, PACKAGE_STATUS_ACTIVE));

        list($totalNotUsedCoupons) = $result->fetch();
        if($isRecurring == 0) {
            $couponsReport = "SELECT u.id, u.firstname, u.lastname, c.coupons_name, c.coupons_description, c.coupons_discount, c.coupons_type, c.coupons_archive, ie.invoiceid, ie.detail, inv.amount, ie.date "
                    ."FROM invoiceentry ie, coupons c, users u, coupons_usage cu, invoice inv "
                    ."WHERE ie.id = cu.invoiceentryid AND cu.couponid = ? AND ie.customerid = u.id AND c.coupons_id = cu.couponid AND ie.invoiceid = inv.id";
        }else {
            $couponsReport = "SELECT u.id, u.firstname, u.lastname, c.coupons_name, c.coupons_description, c.coupons_discount, c.coupons_type, c.coupons_archive, ie.invoiceid, ie.detail, inv.amount, ie.date "
                ."FROM invoiceentry ie, coupons c, users u, coupons_usage cu, invoice inv, recurringfee r "
                ."LEFT JOIN domains d ON r.appliestoid = d.id "
                ."WHERE ie.id = cu.invoiceentryid "
                ."AND r.recurring = 1 "
                ."AND (r.appliestoid = 0 OR d.status IN (".implode(', ', $statuses).")) "
                ."AND r.paymentterm != 0 "
                ."AND cu.couponid = ? AND ie.customerid = u.id AND c.coupons_id = cu.couponid AND ie.invoiceid = inv.id AND r.id = ie.recurringappliesto GROUP BY u.firstname, u.lastname";
        }

        $result = $this->db->query($couponsReport, $_GET['couponId']);

        include_once 'modules/billing/models/Currency.php';
        $currency = new Currency($this->user);
        $totalSaved = 0;
        while (list($userId, $firstname, $lastname, $coupons_name, $coupons_description, $coupons_discount, $coupons_type, $coupons_archive, $invoiceid, $invoice_detail, $amount, $coupon_date) = $result->fetch()) {

            ($coupons_archive == 0) ? $archived = $this->user->lang('No') : $archived = $this->user->lang('Yes');
            $date = $this->convertDate($coupon_date);
            $tUser = new User($userId);
            if($coupons_type == 1) {
                $invoiceSaved = 0;
                if ($coupons_discount == 1) {
                    $invoiceSaved = $amount;
                    $totalSaved += $invoiceSaved;
                } else {
                    $invoiceSaved = ($amount/(1-$coupons_discount) - $amount);
                    $totalSaved += $invoiceSaved;
                }
                $aGroup[] = array("<a href=\"index.php?frmClientID=".$userId."&fuse=clients&controller=userprofile&view=profilecontact\">".$lastname.", ".$firstname."</a>", $coupons_name, "<a href=index.php?fuse=billing&controller=invoice&frmClientID=".$userId."&view=invoice&profile=1&invoiceid=".$invoiceid.">".$invoiceid."</a>", $date, ($coupons_discount*100)."% (".$currency->format($tUser->getCurrency(), $invoiceSaved).")", $currency->format($tUser->getCurrency(),$amount), $invoice_detail, $archived);
            }else {
                $totalSaved += $coupons_discount;
                $aGroup[] = array("<a href=\"index.php?frmClientID=".$userId."&fuse=clients&controller=userprofile&view=profilecontact\">".$lastname.", ".$firstname."</a>", $coupons_name, "<a href=index.php?fuse=billing&controller=invoice&frmClientID=".$userId."&view=invoice&profile=1&invoiceid=".$invoiceid.">".$invoiceid."</a>", $date, $currency->format($tUser->getCurrency(), $coupons_discount), $currency->format($tUser->getCurrency(), $amount), $invoice_detail, $archived);

                if($isRecurring == 1) {
                    $sql = "SELECT ie.date, ie.invoiceid  FROM invoiceentry ie, coupons_usage cu, recurringfee r "
                        ."LEFT JOIN domains d ON r.appliestoid = d.id "
                        ."WHERE  ie.customerid = ? AND r.id = ie.recurringappliesto "
                        ."AND r.recurring = 1 "
                        ."AND (r.appliestoid = 0 OR d.status IN (".implode(', ', $statuses).")) "
                        ."AND r.paymentterm != 0 "
                        ."AND cu.couponid = ? AND cu.invoiceentryid = r.id";
                    $result2 = $this->db->query($sql, $userId, $_GET['couponId']);
                    $timesUsed = 0;
                    $valuesOfPayment = "<b>Payments: </b>";
                    while(list($date, $idInvoiceEntry)=$result2->fetch()) {
                        $valuesOfPayment .= "(".$date.") ";
                        $timesUsed++;
                    }
                    //$valuesOfPayment2 = ;
                    $aGroup[] = array($valuesOfPayment, "<br><b>Coupon used ".$timesUsed." time(s)</b>");
                }


            }
            $i++;
        }

        if($totalSaved > 0) {
            $aGroup[] = array('', '', '', '', '', '', $this->user->lang('<b>Total saved</b>'), '<b>'.$currency->format($tUser->getCurrency(), $totalSaved).'</b>');
        }

        $this->SetDescription("<b>".(($totalNotUsedCoupons == -1) ? $this->user->lang('unlimited') : $totalNotUsedCoupons)."</b> ".$this->user->lang('Available coupons')."<br><b>".$i."</b> ".$this->user->lang('Coupons have already been used'));

        if(count($aGroup) > 0) {

            $this->reportData[] = array(
                "group" => $aGroup,
                "groupname" => $this->user->lang('Customers'),
                "label" => array($this->user->lang('Customer'),$this->user->lang('Coupon Name'), $this->user->lang('Invoice #'), $this->user->lang('Date'), $this->user->lang('Discount'), $this->user->lang('Amount'), $this->user->lang('Details'), $this->user->lang('Archived')),
                "groupId" => "",
                "isHidden" => false);

        }else if($_GET['couponId'] > 0) {
            echo "<br> <b>THERE ARE NO CUSTOMERS USING THIS COUPON YET</b> <br><br>";
        }
    }

    function convertDate($date)
    {
        $date = date("F j, Y", strtotime($date));
        return $date;
    }
}
?>
