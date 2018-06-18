<?php

/**
 * Repair_Billing_Issues Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */
class Invoice_Subscription extends Report
{
    protected $featureSet = 'billing';

    private $lang;

    var $showOptionsForOverdueTransactions = true;
    var $lastPaidInvoiceInfo = array();

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Invoice Subscription');
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
        $this->SetDescription($this->user->lang('A report to remove the subscription association on a given invoice.'));

        @set_time_limit(0);

        $errors = array();

        //Invoice Subscription
        echo "<div style='margin:20px;'><h3>" . $this->user->lang('Remove Invoice Subscription') . "</h3>";
        echo "<div>".$this->user->lang("Any subscription id reference will be removed from the entered invoice id.")."<br/><em>"
            .$this->user->lang('If you also need to cancel the subscription, you will need to visit your respective gateway account.')."</em></div><br/>";   
        echo "<input type='text' name='invoiceidsubrem' id='invoiceidsubrem' style='width:100px;' >&nbsp&nbsp&nbsp&nbsp";
        echo "<button class='btn' type='button' data-loading-text='Loading...' onclick='RemoveSubscription(document.getElementById(\"invoiceidsubrem\").value);'>"
            .$this->user->lang("Remove Invoice Subscription") . "</button>&nbsp;&nbsp;&nbsp;";
        echo "<br/><br/>";
        echo "</div>";
        echo "\n\n<script type='text/javascript'>
                function RemoveSubscription(invoiceidsubrem){
                    if(".(($this->user->hasPermission( 'billing_create' ))? "true" : "false")."){
                        location.href='index.php?fuse=reports&view=viewreport&controller=index&report=Invoice+Subscription&type=Diagnostics&remove=1&invoiceidsubrem='+invoiceidsubrem;
                    }else{
                        RichHTML.error(lang('You do not have permission to perform this action'));
                    }
                }
            </script>";
        echo "\n\n";

        if (isset($_GET['remove']) && $_GET['invoiceidsubrem'] && $this->user->hasPermission( 'billing_create' )) {
            $billingGateway = new BillingGateway( $this->user );
            $billingGateway->removeSubscriptionIdInvoices( array($_GET['invoiceidsubrem']) );
        }

    }
}
