<?php

require_once 'modules/admin/models/StatusAliasGateway.php';

/**
 * Support_Tickets_Closed Report Class
 *
 * @category Report
 * @package  ClientExec
 * @license  ClientExec License
 * @link     http://www.clientexec.com
 */
class Tickets_Review extends Report
{
    private $lang;

    protected $featureSet = 'support';
    public $hasgraph = true;

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Tickets Review');
        parent::__construct($user,$customer);
    }

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process()
    {

        $this->options = array(
            "label"=> $this->user->lang("Date Range"),
            "values" => array(
                $this->user->lang("View this week")  => 0,
                //$this->user->lang("View this month")  => 1,
                $this->user->lang("View this year") => 2
            ),
            "defaultid" => 0
        );

        $settings = $this->settings = new CE_Settings();
        // Set the report information
        $this->goal_minutes = $settings->get("Response Time Goal");
        if (is_numeric($this->goal_minutes) && $this->goal_minutes > 0) {
            $this->SetDescription($this->user->lang('Support totals for the last six months. Closed, Opened and Response Time Goal of %s minutes', $this->goal_minutes));
        } else {
            $this->SetDescription($this->user->lang('Support totals for the last six months. Closed, Opened'));
        }

        if (!isset($_GET['option_id'])) {
            if (isset($_GET['indashboard'])) $option_id = 0;
            else $option_id = 2;
        } else {
            $option_id = filter_input(INPUT_GET,'option_id',FILTER_SANITIZE_NUMBER_INT);
        }

        $thisYear = Date("Y");

        $graphdata = @$_GET['graphdata'];

        if($graphdata) {

            if ($option_id == 2) {
                $graph_row_data = $this->returnRowsForYear();    
            } else {
                $graph_row_data = $this->returnRowsForWeek();    
            }
            

            //this supports lazy loading and dynamic loading of graphs
            $this->reportData = $this->GraphData($graph_row_data[0],$graph_row_data[1],$option_id);
            return;                        
        }

        //Class Initialize
        $this->SetDescription($this->user->lang('Displays all support tickets closed.(click on a date to view all tickets for that year)'));

        //Select the total tickets for use later
        $statusClosed = StatusAliasGateway::ticketClosedAliases($this->user);
        $sql = "SELECT COUNT(*) AS total FROM troubleticket WHERE status IN (".implode(', ', $statusClosed).")";
        $result = $this->db->query($sql);

        while($row = $result->fetch()) {
            $totaltickets = $row['total'];
        }
        unset($result);

        //Now we query for all support tickets all time
        $sql = "SELECT "
                ."DATE_FORMAT( lastlog_datetime, '%Y' ) AS date_opened, "
                ."lastlog_datetime AS date_unformatted, "
                ."COUNT(*) "
                ."FROM troubleticket "
                ."WHERE status IN (".implode(', ', $statusClosed).") "
                ."GROUP BY DATE_FORMAT( lastlog_datetime, '%Y' ) "
                ."ORDER BY DATE_FORMAT( lastlog_datetime, '%Y' ) DESC";

        $result = $this->db->query($sql);
        if($result->getNumRows() >0) {

            $prev_year = false;
            $prevMonthName = "";
            while (list($year,$unformatted,$tickets) = $result->fetch()) {


                $basePage = $_SERVER["REQUEST_URI"];
                $nPos = strpos($basePage, "&year=");
                if ( $nPos === false ) {
                    $basePage .= "&year=";
                } else {
                    $basePage = mb_substr($basePage, 0, $nPos) . "&year=";
                }
                $yearLi = mb_substr($unformatted, 0, 4);
                $year = "<a href=\"" . $basePage . $yearLi . "\">" . $year . "</a>";
                $aGroup[] = array($year,$tickets);
                $prev_year = $tickets;
            }

            //add final group
            if (isset($aGroup)) {
                $this->reportData[] = array("group"=>$aGroup,
                    "groupname"=>"",
                    "label"=>array($this->user->lang('Year'),$this->user->lang('Tickets Closed')),
                    "groupId"=>"",
                    "isHidden"=>false);
                unset($aGroup);
            }

            if(@$_GET['year']) {
                //Now we query for all support tickets in this year
                $sql = "SELECT users.firstname, users.lastname, t.id, t.subject, t.datesubmitted, t.lastlog_datetime, t.response_time "
                        ."FROM troubleticket t "
                        ."LEFT JOIN users ON ( users.id = t.assignedtoid ) "
                        ."WHERE SUBSTRING( t.lastlog_datetime, 1, 4 ) =? "
                        ."AND t.status IN (".implode(', ', $statusClosed).") "
                        ."ORDER BY t.lastlog_datetime";

                $result = $this->db->query($sql, $_GET['year']);


                //initialize
                $prevMonth = "-1";
                $monthTotal = 0;
                $yearTotal = 0;
                while (list($assignedToFirst,$assignedToLast,$ttID,$ttSubject,$ttDateSubmitted,$ttLastLog_DateTime,$response_time) = $result->fetch()) {
                    $thisMonth = date("n",strtotime($ttLastLog_DateTime));
                    $assignedTo = $assignedToFirst . " " . $assignedToLast;

                    if($prevMonth!=$thisMonth) {
                        if (isset($aGroup)) {

                            $aGroup[] = array("--------","--------","--------","--------");
                            $aGroup[] = array("",$this->user->lang('Total This Month'),$monthTotal,"");
                            //add previous group before getting next group
                        $this->reportData[] = array("group"=>$aGroup,
                            "groupname"=>$prevMonthName,
                            "label"=>array($this->user->lang('Ticket#'),$this->user->lang('Subject'),$this->user->lang('Submitted'),$this->user->lang('Initial Response'),$this->user->lang('Total Elapsed')),
                            "groupId"=>"",
                            "isHidden"=>false);
                            unset($aGroup);
                        }
                        $aGroup = array();
                        $monthTotal = 0;
                        $prevMonth = $thisMonth;
                        $prevMonthName = date("F Y",strtotime($ttLastLog_DateTime));
                    }


                    $monthTotal++;
                    $yearTotal ++;
                    $linkedTicket = "<a href=index.php?fuse=support&view=viewtickets&controller=ticket&id=".$ttID.">".$ttID."</a>";

                    $timeElapsed = $this->getDateDifference($ttDateSubmitted, $ttLastLog_DateTime);

                    $aGroup[] = array($linkedTicket,$ttSubject,date('j M Y',strtotime($ttDateSubmitted)),$response_time." mins",$timeElapsed);
                }

                //add final group
                if (isset($aGroup)) {
                    $aGroup[] = array("--------","--------","--------","--------");
                    $aGroup[] = array("",$this->user->lang('Total This Month'),$monthTotal,"");
                    $this->reportData[] = array("group"=>$aGroup,
                            "groupname"=>$prevMonthName,
                            "label"=>array($this->user->lang('Ticket ID'),$this->user->lang('Subject'),$this->user->lang('Date Submitted'),$this->user->lang('Total Elapsed')),
                            "groupId"=>"",
                            "isHidden"=>false);
                    unset($aGroup);
                }

                $aGroup[] = array("","","<b>".$this->user->lang('Total for')." ".$_GET['year']."</b> ".$yearTotal,"");
                $this->reportData[] = array("group"=>$aGroup,
                        "groupname"=>"",
                        "label"=>array("","","",""),
                        "groupId"=>"",
                        "isHidden"=>false);


            }
        } else {
            $this->reportData[] = array("group"=>array(),
                "groupname"=>"",
                "label"=>array(""),
                "groupId"=>"",
                "isHidden"=>false);
        }
    }

    function getDateDifference($ttDateSubmitted, $ttLastLog_DateTime)
    {
        $returnString = "";

        $tWeeks = 0;
        $tDays = 0;
        $tHours = 0;
        $datearray = CE_Lib::date_diff_hrs(date('Y-m-d H:i:s',strtotime($ttDateSubmitted)),date('Y-m-d H:i:s',strtotime($ttLastLog_DateTime)));

        if ($datearray['h'] > 0) {
            $tHours += abs($datearray['h']);
            if($tHours >= 24) {
                $tDays = intval($tHours / 24);
                $tHours -= $tDays*24;
                if($tDays >= 7) {
                    $tWeeks = intval($tDays / 7);
                    $tDays -= $tWeeks*7;
                }
            }
        }

        if($tWeeks > 0) {
            if($tWeeks > 1) {
                $returnString .= $tWeeks. ' '.$this->user->lang('weeks').' ';
            } else {
                $returnString .= $tWeeks.' '.$this->user->lang('week' ).' ';
            }
        }
        if($tDays > 0) {
            if($tDays > 1) {
                $returnString .= $tDays. ' '.$this->user->lang('days').' ';
            } else {
                $returnString .= $tDays.' '.$this->user->lang('day').' ';
            }
        }
        if ($tHours > 0) {
            if ($tHours > 1) {
                $returnString .= $tHours.' '.$this->user->lang('hrs').' ';
            } else {
                $returnString .= ' 1 '.$this->user->lang('hr').' ';
            }
            if ($datearray['m']==1) {
                $returnString .= ' '.abs($datearray['m']).' min';
            } else {
                $returnString .= ' '.abs($datearray['m']).' mins';
            }
        } else {
            if ($datearray['m']==1) {
                $returnString .= abs($datearray['m']).' min';
            } else {
                $returnString .= abs($datearray['m']).' mins';
            }
        }

        return $returnString;
    }

    function GraphData($aMonths, $elapsedtimes, $option_id)
    {

        $settings = $this->settings = new CE_Settings();

        //building graph data to pass back
        if ($option_id == 0) {
            $graph_data = array(
              "xScale" => "ordinal",
              "yScale" => "linear",
              "type" => "bar",
              "xType" => "daysofweek",
              "main" => array()); 
        } else {
            $graph_data = array(
              "xScale" => "ordinal",
              "yScale" => "linear",
              "type" => "bar",
              "main" => array());            
        }

        foreach ($aMonths as $key => $months) {
            $year_data = array();
            $year_data['className'] = ".report_".$key;
            $year_data['data'] = array();

            foreach ($months as $month_label => $monthtotal) {
                
                $month_data = array();
                if ($monthtotal == "") $monthtotal = "0";                
                $month_data["x"] = $month_label;
                $month_data["y"] = $monthtotal;
                if ($option_id == 0) {
                    $pretty_day = $this->user->lang((date("l",strtotime($month_label))));
                    $month_data["tip"] = "<strong>".$pretty_day."</strong><br/>".$key." ".$monthtotal;
                } else {
                    $pretty_month = $this->user->lang((date("F",strtotime($month_label))));
                    $pretty_year = (date("Y",strtotime($month_label)));                     
                    $month_data["tip"] = "<strong>".$pretty_month.", ".$pretty_year."</strong><br/>".$key." ".$monthtotal;
                }
                $year_data['data'][] = $month_data;

            }

            $graph_data["main"][] = $year_data;            
        }

        if (is_numeric($this->goal_minutes) && $this->goal_minutes > 0) {
            $ratings = array();
            $ratings['className'] = ".report_Elaped Time";
            $ratings['type'] = "line-dotted";
            $ratings['data'] = array();
            foreach ($elapsedtimes as $month_label => $monthtotal) {

                $rating = array();
                $rating["x"] = $month_label;
                $rating["y"] = $monthtotal;
                if ($option_id == 0) {
                    $pretty_day = $this->user->lang(date("l",strtotime($month_label)));                    
                    $rating["tip"] = "<strong>".$pretty_day."</strong><br/>".$this->user->lang("Tickets met goal")." ".$monthtotal;
                } else {
                    $pretty_month = $this->user->lang(date("F",strtotime($month_label)));
                    $pretty_year = (date("Y",strtotime($month_label))); 
                    $rating["tip"] = "<strong>".$pretty_month.", ".$pretty_year."</strong><br/>".$this->user->lang("Tickets met goal")." ".$monthtotal;
                }
                $ratings['data'][] = $rating;
            
            }
            
            $graph_data["comp"][] = $ratings;
        }


        return json_encode($graph_data);



    }
    
    //return data for last x months
    private function returnRowsForYear()
    {
        $lArrayMonth = array();
        $statusClosed = StatusAliasGateway::ticketClosedAliases($this->user);

            //*****************************
            //Get count of users based on minus x from tomonth that have signed up
            //*****************************
            for($m=6; $m>=0; $m--) {    
                $month_minus_TS = mktime (0,0,0,date("m")-$m,1,  date("Y"));
                $minus_date = strftime("%Y-%m-%d",$month_minus_TS); //used for the sql                    
                $query = "SELECT COUNT(id) FROM troubleticket WHERE (MONTH(datesubmitted) = MONTH('$minus_date')) AND (YEAR(datesubmitted) = YEAR('$minus_date'))";
                $result = $this->db->query($query);
                $tArray = $result->fetch();

                if ($tArray[0] < 0) $tArray[0] = 0;
                $date = date("Y-m-d",$month_minus_TS);
                $lArrayMonth['Opened'][$date] = $tArray[0];
            }

            for($m=6; $m>=0; $m--) {
                $month_minus_TS = mktime (0,0,0,date("m")-$m,1,  date("Y"));
                $minus_date = strftime("%Y-%m-%d",$month_minus_TS); //used for the sql
                $query = "SELECT COUNT(id) FROM troubleticket WHERE (MONTH(lastlog_datetime) = MONTH('$minus_date')) AND (YEAR(lastlog_datetime) = YEAR('$minus_date')) AND status IN (".implode(', ', $statusClosed).")";
                $result = $this->db->query($query);
                $tArray = $result->fetch();

                if ($tArray[0] < 0) $tArray[0] = 0;
                $date = date("Y-m-d",$month_minus_TS);
                $lArrayMonth['Closed'][$date] = $tArray[0];
            }

            $elapsedtimes = array();
            for($m=6; $m>=0; $m--) {
                $month_minus_TS = mktime (0,0,0,date("m")-$m,1,  date("Y"));
                $minus_date = strftime("%Y-%m-%d",$month_minus_TS); //used for the sql
                $query = "SELECT count(*) FROM `troubleticket` WHERE response_time > 0  AND (YEAR(datesubmitted) = YEAR('$minus_date')) AND (MONTH(datesubmitted) = MONTH('$minus_date'))";
                $result = $this->db->query($query,$m);
                $tArray = $result->fetch();                
                $date = date("Y-m-d",$month_minus_TS);

                if ($tArray[0] < 0) $tArray[0] = 0;
                $elapsedtimes[$date] = $tArray[0];
            }

            return array($lArrayMonth,$elapsedtimes);
    }

    //return data only for last week
    private function returnRowsForWeek()
    {
        $lArrayMonth = array();
        $statusClosed = StatusAliasGateway::ticketClosedAliases($this->user);

            //*****************************
            //Get count of users based on minus x from tomonth that have signed up
            //*****************************
            for($m=6; $m>=0; $m--) {    
                $month_minus_TS = strtotime("-".$m." day");                
                $minus_date = strftime("%Y-%m-%d",$month_minus_TS); //used for the sql     
                $query = "SELECT COUNT(id) FROM troubleticket WHERE (DAY(datesubmitted) = DAY('$minus_date')) AND (MONTH(datesubmitted) = MONTH('$minus_date')) AND (YEAR(datesubmitted) = YEAR('$minus_date'))";
                $result = $this->db->query($query);
                $tArray = $result->fetch();

                //$month_minus_TS = mktime (0,0,0,date("m")-$m,1,  date("Y"));
                $date = date("Y-m-d",$month_minus_TS);
                $lArrayMonth['Opened'][$date] = $tArray[0];
            }

            for($m=6; $m>=0; $m--) {
                $month_minus_TS = strtotime("-".$m." day");                
                $minus_date = strftime("%Y-%m-%d",$month_minus_TS); //used for the sql                
                $query = "SELECT COUNT(id) FROM troubleticket WHERE (DAY(datesubmitted) = DAY('$minus_date')) AND (MONTH(lastlog_datetime) = MONTH('$minus_date')) AND (YEAR(lastlog_datetime) = YEAR('$minus_date')) AND status IN (".implode(', ', $statusClosed).")";
                $result = $this->db->query($query);
                $tArray = $result->fetch();
                $date = date("Y-m-d",$month_minus_TS);
                $lArrayMonth['Closed'][$date] = $tArray[0];
            }

            $elapsedtimes = array();
            for($m=6; $m>=0; $m--) {
                $month_minus_TS = strtotime("-".$m." day");                
                $minus_date = strftime("%Y-%m-%d",$month_minus_TS); //used for the sql
                $query = "SELECT count(*) FROM `troubleticket` WHERE response_time > 0  AND (DAY(datesubmitted) = DAY('$minus_date')) AND  (YEAR(datesubmitted) = YEAR('$minus_date')) AND (MONTH(datesubmitted) = MONTH('$minus_date'))";
                $result = $this->db->query($query,$m);
                $tArray = $result->fetch();                
                $date = date("Y-m-d",$month_minus_TS);
                $elapsedtimes[$date] = $tArray[0];
            }

            return array($lArrayMonth,$elapsedtimes);
    }

}
?>
