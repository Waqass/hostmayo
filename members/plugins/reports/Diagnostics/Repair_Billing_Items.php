<?php

/**
 * Repair_Billing_Items Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */
class Repair_Billing_Items extends Report
{
    protected $featureSet = 'billing';

    private $lang;

    var $showOptionsForOverdueTransactions = true;
    var $lastPaidInvoiceInfo = array();

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Repair Billing Items');
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

        //Remove Invoice Subscription
        echo "<div style='margin:20px;'><h3>" . $this->user->lang('Remove Invoice Subscription') . "</h3>";
        echo "<div>".$this->user->lang("Any subscription id reference will be removed from the entered invoice id.")
            ."<br/><em>".$this->user->lang('If you also need to cancel the subscription, you will need to visit your respective gateway account.')."</em>"
            ."</div><br/>";
        echo "<input type='text' name='invoiceidsubrem' id='invoiceidsubrem' style='width:100px;' >&nbsp&nbsp&nbsp&nbsp";
        echo "<button class='btn' type='button' data-loading-text='Loading...' onclick='RemoveSubscription(document.getElementById(\"invoiceidsubrem\").value);'>"
            .$this->user->lang("Remove Invoice Subscription") . "</button>&nbsp;&nbsp;&nbsp;";
        echo "<br/><br/>";
        echo "</div>";
        echo "\n\n<script type='text/javascript'>
                function RemoveSubscription(invoiceidsubrem){
                    if(".(($this->user->hasPermission( 'billing_create' ))? "true" : "false")."){
                        location.href='index.php?fuse=reports&view=viewreport&controller=index&report=Repair+Billing+Items&type=Diagnostics&remove=1&invoiceidsubrem='+invoiceidsubrem;
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


        //Repair Corrupt Recurring Fee's Customers
        echo "<div style='margin:20px;'><h3>" . $this->user->lang("Repair Corrupt Recurring Fee's Customers") . "</h3>";
        echo "<div>".$this->user->lang("The recurring fee's customer reference will be updated to match the respective package's customer")
            ."</div><br/>";
        echo "<button class='btn' type='button' data-loading-text='Loading...' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Repair+Billing+Items&type=Diagnostics&getrfwcc=1\"'>"
            .$this->user->lang("Retrieve recurring fees with corrupt customers") . "</button>&nbsp;&nbsp;&nbsp;";
        if (isset($_GET['getrfwcc']) || isset($_GET['repairrfwcc'])) {
            $labels = array(
                'Recurring_Fee_Id'       => 'Recurring Fee Id',
                'Package_Customer'       => 'Package Customer Id',
                'Recurring_Fee_Customer' => 'Recurring Fee Customer Id'
            );
            $rows = $this->get_rows_of_recurring_fees_that_need_customers_updated();
            $emptyMessage = $this->user->lang('No recurring fees require updating at this time');
            $button = 'btn-rfwcc';

            echo "<button data-loading-text='Updating...' class='btn btn-danger ".$button."' type='button' style='display:none;' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Repair+Billing+Items&type=Diagnostics&repairrfwcc=1\"'>"
                .$this->user->lang("Repair the recurring fee's customers below")."</button>";
        }

        echo "<br/><br/>";
        if ( isset($_GET['getrfwcc'])) {
            $this->show_items_to_repair($labels, $rows, $emptyMessage, $button);
        } else if(isset($_GET['repairrfwcc'])){
            $this->update_recurring_fee_customer($rows);
            $rows = $this->get_rows_of_recurring_fees_that_need_customers_updated();
            $this->show_items_to_repair($labels, $rows, $emptyMessage, $button);
        }
        echo "</div>";


        //Repair Corrupt Invoice's Customers
        echo "<div style='margin:20px;'><h3>" . $this->user->lang("Repair Corrupt Invoice's Customers") . "</h3>";
        echo "<div>".$this->user->lang("The invoice's customer reference will be updated to match the respective package's customer")
            ."</div><br/>";
        echo "<button class='btn' type='button' data-loading-text='Loading...' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Repair+Billing+Items&type=Diagnostics&getiwcc=1\"'>"
            .$this->user->lang("Retrieve invoices with corrupt customers") . "</button>&nbsp;&nbsp;&nbsp;";
        if (isset($_GET['getiwcc']) || isset($_GET['repairiwcc'])) {
            $labels = array(
                'Invoice_Id'             => 'Invoice Id',
                'Package_Customer'       => 'Package Customer Id',
                'Invoice_Entry_Customer' => 'Invoice Entry Customer Id'
            );
            $rows = $this->get_rows_of_invoices_that_need_customers_updated();
            $emptyMessage = $this->user->lang('No invoices require updating at this time');
            $button = 'btn-iwcc';

            echo "<button data-loading-text='Updating...' class='btn btn-danger ".$button."' type='button' style='display:none;' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Repair+Billing+Items&type=Diagnostics&repairiwcc=1\"'>"
                .$this->user->lang("Repair the invoice's customers below")."</button>";
        }

        echo "<br/><br/>";
        if ( isset($_GET['getiwcc'])) {
            $this->show_items_to_repair($labels, $rows, $emptyMessage, $button);
        } else if(isset($_GET['repairiwcc'])){
            $this->update_invoice_customer($rows);
            $rows = $this->get_rows_of_invoices_that_need_customers_updated();
            $this->show_items_to_repair($labels, $rows, $emptyMessage, $button);
        }
        echo "</div>";


        //Repair Corrupt Invoice Entry's Customers
        echo "<div style='margin:20px;'><h3>" . $this->user->lang("Repair Corrupt Invoice Entry's Customers") . "</h3>";
        echo "<div>".$this->user->lang("The invoice entry's customer reference will be updated to match the respective invoice's customer")
            ."</div><br/>";
        echo "<button class='btn' type='button' data-loading-text='Loading...' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Repair+Billing+Items&type=Diagnostics&getiewcc=1\"'>"
            .$this->user->lang("Retrieve invoice entries with corrupt customers") . "</button>&nbsp;&nbsp;&nbsp;";
        if (isset($_GET['getiewcc']) || isset($_GET['repairiewcc'])) {
            $labels = array(
                'Invoice_Id'             => 'Invoice Id',
                'Invoice_Customer'       => 'Invoice Customer Id',
                'Invoice_Entry_Customer' => 'Invoice Entry Customer Id'
            );
            $rows = $this->get_rows_of_invoice_entries_that_need_customers_updated();
            $emptyMessage = $this->user->lang('No invoice entries require updating at this time');
            $button = 'btn-iewcc';

            echo "<button data-loading-text='Updating...' class='btn btn-danger ".$button."' type='button' style='display:none;' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Repair+Billing+Items&type=Diagnostics&repairiewcc=1\"'>"
                .$this->user->lang("Repair the invoice entry's customers below")."</button>";
        }

        echo "<br/><br/>";
        if ( isset($_GET['getiewcc'])) {
            $this->show_items_to_repair($labels, $rows, $emptyMessage, $button);
        } else if(isset($_GET['repairiewcc'])){
            $this->update_invoice_entry_customer($rows);
            $rows = $this->get_rows_of_invoice_entries_that_need_customers_updated();
            $this->show_items_to_repair($labels, $rows, $emptyMessage, $button);
        }
        echo "</div>";
    }


    function show_items_to_repair($labels, $rows, $emptyMessage, $button)
    {
        echo "<table class='table table-striped'>";
        echo "<thead>";
        foreach($labels AS $label){
            echo "<th>".$label."</th>";
        }
        echo "</thead><tbody>";

        $labelKeys = array_keys($labels);
        foreach($rows as $row)
        {
            echo "<tr>";
            foreach($labelKeys as $labelKey){
                echo "<td>".$row[$labelKey]."</td>";
            }
            echo "</tr>";
        }

        if (count($rows) == 0) {
            echo "<tr>";
            echo "<td colspan=".count($labels)."><center>"
                .$emptyMessage
                ."</center></td>";
            echo "</tr>";   
        } 

        echo "</tbody></table>";

        if (count($rows) > 0) {
            echo "<script type'text/javascript'>$('.".$button."').show();</script>";
        }
    }


    function get_rows_of_recurring_fees_that_need_customers_updated()
    {
        $return_array = array();
        //Search for recurring fees related to packages, that were generated for a customer different than the one owner of the package:
        $sql = "SELECT DISTINCT rf.`id` as Recurring_Fee_Id, d.`CustomerID` as Package_Customer, rf.`customerid` as Recurring_Fee_Customer "
            ."FROM `recurringfee` rf "
            ."JOIN `domains` d "
            ."ON rf.`appliestoid` = d.`id` "
            ."WHERE rf.`customerid` != d.`CustomerID` "
            ."ORDER BY rf.`id` DESC ";
        $result = $this->db->query($sql);

        while($row = $result->fetch()) 
        {
            $return_array[] = $row;
        }
        return $return_array;
    }

    function update_recurring_fee_customer($rows)
    {
        foreach($rows as $row)
        {
            $sql = "UPDATE `recurringfee` "
                ."SET `customerid` = ? "
                ."WHERE `customerid` = ? "
                ."AND `id` = ? ";
            $this->db->query($sql, $row['Package_Customer'], $row['Recurring_Fee_Customer'], $row['Recurring_Fee_Id']);
        }
    }


    function get_rows_of_invoices_that_need_customers_updated()
    {
        $return_array = array();
        //Search for invoice entries related to packages, that were generated for a customer different than the one owner of the package:
        $sql = "SELECT DISTINCT ie.`invoiceid` as Invoice_Id, d.`CustomerID` as Package_Customer, ie.`customerid` as Invoice_Entry_Customer "
            ."FROM `invoiceentry` ie "
            ."JOIN `domains` d "
            ."ON ie.`appliestoid` = d.`id` "
            ."WHERE ie.`customerid` != d.`CustomerID` "
            ."ORDER BY ie.`invoiceid` DESC ";
        $result = $this->db->query($sql);

        while($row = $result->fetch()) 
        {
            $return_array[] = $row;
        }
        return $return_array;
    }

    function update_invoice_customer($rows)
    {
        foreach($rows as $row)
        {
            $sql1 = "UPDATE `invoiceentry` "
                ."SET `customerid` = ? "
                ."WHERE `customerid` = ? "
                ."AND `invoiceid` = ? ";
            $this->db->query($sql1, $row['Package_Customer'], $row['Invoice_Entry_Customer'], $row['Invoice_Id']);

            $sql2 = "UPDATE `invoice` "
                ."SET `customerid` = ? "
                ."WHERE `customerid` = ? "
                ."AND `id` = ? ";
            $this->db->query($sql2, $row['Package_Customer'], $row['Invoice_Entry_Customer'], $row['Invoice_Id']);
        }
    }


    function get_rows_of_invoice_entries_that_need_customers_updated()
    {
        $return_array = array();
        //Search for invoice entries related to invoices, that were generated for a customer different than the one owner of the invoice:
        $sql = "SELECT i.`id` as Invoice_Id, i.`customerid` as Invoice_Customer, ie.`customerid` as Invoice_Entry_Customer "
            ."FROM `invoice` i "
            ."JOIN `invoiceentry` ie "
            ."ON i.`id` = ie.`invoiceid` "
            ."WHERE i.`customerid` != ie.`customerid` ";
        $result = $this->db->query($sql);

        while($row = $result->fetch()) 
        {
            $return_array[] = $row;
        }
        return $return_array;
    }

    function update_invoice_entry_customer($rows)
    {
        foreach($rows as $row)
        {
            $sql = "UPDATE `invoiceentry` "
                ."SET `customerid` = ? "
                ."WHERE `customerid` = ? "
                ."AND `invoiceid` = ? ";
            $this->db->query($sql, $row['Invoice_Customer'], $row['Invoice_Entry_Customer'], $row['Invoice_Id']);
        }
    }
}