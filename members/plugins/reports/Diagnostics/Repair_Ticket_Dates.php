<?php
require_once 'modules/support/models/TicketGateway.php';


/**
 * Repair_Billing_Issues Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Alberto Vasquez <alberto@clientexec.com>
 * @license  ClientExec License
 * @version  1.3
 * @link     http://www.clientexec.com
 */
class Repair_Ticket_Dates extends Report
{
    protected $featureSet = 'billing';

    private $lang;

    var $showOptionsForOverdueTransactions = true;
    var $lastPaidInvoiceInfo = array();

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Repair Ticket Dates');
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
        $this->SetDescription($this->user->lang('A report to process the next due date of all recurring products and ensure payments have been processed. The report also shows a summary of packages ordered and payments processed.'));

        @set_time_limit(0);

        $errors = array();

        //Original Date
        echo "<div style='margin:20px;'><h3>" . $this->user->lang('Repair Corrupt Ticket Dates') . "</h3>";
        echo "<div>".$this->user->lang("The ticket's date submitted date will be updated to match the first ticket entry")."<br/><em>"
            .$this->user->lang('This query can take a long time so repairs are broken down in segments of 500 tickets.')."</em></div><br/>";   
        
        echo "<button class='btn' type='button' data-loading-text='Loading...' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Repair+Ticket+Dates&type=Diagnostics&getids=1\"'>"
            .$this->user->lang("Retrieve ticket's with corrupt dates") . "</button>&nbsp;&nbsp;&nbsp;";
        if (isset($_GET['getids']) || isset($_GET['repairids'])) {
            echo "<button data-loading-text='Updating...' class='btn btn-danger btn-clean-date' type='button' style='display:none;' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Repair+Ticket+Dates&type=Diagnostics&repairids=1\"'>"
                .$this->user->lang('Repair the tickets dates below')."</button>";
        }

        echo "<br/><br/>";
        if ( isset($_GET['getids'])) {
            $this->show_tickets_that_need_dates_update();
        } else if(isset($_GET['repairids'])){
            $this->update_ticket_date();
            $this->show_tickets_that_need_dates_update();
        }
        echo "</div>";

        //Response Times
        echo "<div style='margin:20px;'><h3>".$this->user->lang('Repair Response Times')."</h3>";
        echo "<div><strong style='color:orangered'>"
            .$this->user->lang("It is important that you repair all corrupt tickets dates above before selecting this option.")
            ."</strong><br/>"
            .$this->user->lang("Synchronize initial response times for older tickets with response time zero (pre 5.0 tickets).")
            ."<br/><em>"
            .$this->user->lang("This query can take a long time so repairs are broken down in segments of 5000 tickets.")
            ."</em></div><br/>";   
        
        echo "<button class='btn' type='button' data-loading-text='Loading...' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Repair+Ticket+Dates&type=Diagnostics&showresponse=1\"'>"
            .$this->user->lang("Retrieve ticket's with corrupt response times")
            ."</button>&nbsp;&nbsp;&nbsp;";
        if (isset($_GET['showresponse']) || isset($_GET['repairresponse'])) {
            echo "<button data-loading-text='"
                .$this->user->lang('Updating')."...' class='btn btn-danger btn-clean-response' type='button' style='display:none;' onclick='window.location.href=\"index.php?fuse=reports&view=viewreport&controller=index&report=Repair+Ticket+Dates&type=Diagnostics&repairresponse=1\"'>"
                .$this->user->lang('Repair initial response times')
                ."</button>";
        }
        echo "<br/><br/>";

        if( isset($_GET['showresponse']) ) {
            $this->show_tickets_that_need_response_update();
        }else if(isset($_GET['repairresponse'])){
            $this->update_ticket_initial_response();
            $this->show_tickets_that_need_response_update();
        }

        echo "</div>";



    }

    function show_tickets_that_need_dates_update()
    {
        echo "<table class='table table-striped'>";
        echo "<thead>";
        echo "<th>".$this->user->lang('Ticket')
            . " #</th><th>"
            .$this->user->lang('Date Submitted')
            ."</th><th>"
            .$this->user->lang('First Log Date')
            ."</th>";
        echo "</thead><tbody>";

        $rows = $this->get_rows_of_tickets_that_need_dates_updated();
        foreach($rows as $row)
        {
                echo "<tr>";
                echo "<td>#".$row[0]."</td>";
                echo "<td>".$row[1]."</td>";
                echo "<td>".$row[2]."</td>";
                echo "</tr>";
        }

        if (count($rows) == 0) {
            echo "<tr>";
            echo "<td colspan=3><center>"
                .$this->user->lang('No tickets require updating at this time')
                ."</center></td>";
            echo "</tr>";   
        } 

        echo "</tbody></table>";

        if (count($rows) > 0) {
            echo "<script type'text/javascript'>$('.btn-clean-date').show();</script>";
        }
    }

    function update_ticket_date()
    {
        $rows = $this->get_rows_of_tickets_that_need_dates_updated();
        foreach($rows as $row)
        {
            $sql = "update troubleticket set datesubmitted = ? where id = ?";
            $this->db->query($sql,$row[2],$row[0]);
        }
    }

    function get_rows_of_tickets_that_need_dates_updated()
    {
        $return_array = array();
        $limit = 0; 
        $result = $this->db->query("SELECT t.id, t.datesubmitted AS datesubmitted, MIN( l.id ) AS log_id FROM troubleticket t JOIN troubleticket_log l ON t.id = l.troubleticketid GROUP BY t.id");
        while($row = $result->fetch()) 
        {
            $query = "Select id, mydatetime from troubleticket_log where (TIME_TO_SEC(TIMEDIFF(mydatetime,'".$row['datesubmitted']."'))/60) <> 0 AND id=".$row['log_id'];
            $result2 = $this->db->query($query);
            if ($result2->getNumRows() > 0){
                list($id,$mydatetime) = $result2->fetch();
                $return_array[] = array($row[id],$row['datesubmitted'],$mydatetime);
                $limit++;
            }
            if ($limit == 500) break;
        }
        return $return_array;
    }

    function show_tickets_that_need_response_update()
    {
        echo "<table class='table table-striped'>";
        echo "<thead>";
        echo "<th>".$this->user->lang('Ticket')." #</th><th>"
            .$this->user->lang('Date Submitted')
            ."</th><th>"
            .$this->user->lang('First Response (mins)')
            ."</th>";
        echo "</thead><tbody>";

        $rows = $this->get_rows_of_tickets_that_need_responses_updated();
        foreach($rows as $row)
        {
                echo "<tr>";
                echo "<td>#".$row[0]."</td>";
                echo "<td>".$row[1]."</td>";
                echo "<td>".$row[2]." mins</td>";
                echo "</tr>";
        }

        if (count($rows) == 0) {
            echo "<tr>";
            echo "<td colspan=3><center>"
                .$this->user->lang('No tickets require updating at this time')
                ."</center></td>";
            echo "</tr>";   
        }

        echo "</tbody></table>";

        if (count($rows) > 0) {
            echo "<script type'text/javascript'>$('.btn-clean-response').show();</script>";
        }
    }


    function get_rows_of_tickets_that_need_responses_updated()
    {
        $return_array = array();
        $limit = 0; 
        //$result = $this->db->query("SELECT id, datesubmitted, userid FROM  troubleticket WHERE status =-1 AND datesubmitted >= ( NOW( ) - INTERVAL 6 MONTH )");
        $result = $this->db->query("SELECT t.id, t.datesubmitted AS datesubmitted, MIN( l.id ) AS log_id FROM troubleticket t, troubleticket_log l WHERE t.id = l.troubleticketid AND t.userid <> l.userid AND t.response_time is null GROUP BY t.id");
        while($row=$result->fetch())
        {

            $query = "Select id, (TIME_TO_SEC(TIMEDIFF(mydatetime,'".$row['datesubmitted']."'))/60) as minute_diff from troubleticket_log where id=".$row['log_id'];
            $result2 = $this->db->query($query);
            if ($result2->getNumRows() > 0){
                list($id,$datediff) = $result2->fetch();
                $return_array[] = array($row[id],$row['datesubmitted'],ceil($datediff));
                $limit++;
            }
            if ($limit == 5000) break;

        }
        return $return_array;
    }

    function update_ticket_initial_response()
    {
        $rows = $this->get_rows_of_tickets_that_need_responses_updated();
        foreach($rows as $row)
        {
            $sql = "update troubleticket set response_time = ? where id = ?";
            $this->db->query($sql,$row[2],$row[0]);
        }
    }


}
