<?php

require_once 'modules/admin/models/StatusAliasGateway.php';

/**
 * Support Tickets Service Rating Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.2
 * @link     http://www.clientexec.com
 *
 * ************************************************
 *   1.2 Updated the report to use Pear Commenting & the new title handing to make app reports consistent.
 * ***********************************************
 */

/**
 * Support_Service_Rating Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.1
 * @link     http://www.clientexec.com
 */
class Service_Rating extends Report {

    private $lang;

    protected $featureSet = 'support';
    public $hasgraph = true;

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Service Rating');
        parent::__construct($user,$customer);
    }

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process() {

        // Set the report information
        $this->SetDescription($this->user->lang('Customer\'s ticket ratings.  How is your support doing?'));

        $graphdata = @$_GET['graphdata'];

        //TODO remove from language files
        $leyends = array($this->user->lang('No Rate'), $this->user->lang('Excellent'), $this->user->lang('Good enough'), $this->user->lang('Not great'), $this->user->lang('Poor'));

        $lArrayMonth = array();
        $lArrayLastWeek = array();
        $lArrayFillColor = array();

        //*****************************
        //Get count of users based on minus x from tomonth that have signed up
        //*****************************
        if (isset($_GET['year'])) {
            $year = $_GET['year'];
        } else {
            $year = date("Y");
        }
        if (isset($_GET['month'])) {
            $month = $_GET['month'];
        } else {
            $month = date("m");
        }

        $statusClosed = StatusAliasGateway::ticketClosedAliases($this->user);

        if ($graphdata) {
            //get all ratings for the year
            $sql = "select MONTH(lastlog_datetime) as month, DATE_FORMAT(lastlog_datetime,'%Y-%m-1') as rate_date, rate from troubleticket WHERE (YEAR(lastlog_datetime) = " . $this->db->escape($year) . ") AND status IN (".implode(', ', $statusClosed).") AND rate > 0 ORDER BY lastlog_datetime ASC";

            //echo $sql;
            $result = $this->db->query($sql);

            $firstTime = true;
            $sumCount = 0;
            $aMonth = array();
            $aMonths = array();
            $newMonth = 0;
            $lastMonthCount = 0;

            while ($row = $result->fetch()) {

                $tMonth = $row['month'];

                if ($firstTime) {
                    $newMonth = $tMonth;
                    $lastDate = $row['rate_date'];
                    $firstTime = false;
                }

                //	$sumCount ++;

                if ($newMonth != $tMonth) {

                    $aMonths[$lastDate]["1"] = $this->ReturnArrayOfRate($aMonth, 1, $sumCount);
                    $aMonths[$lastDate]["2"] = $this->ReturnArrayOfRate($aMonth, 2, $sumCount);
                    $aMonths[$lastDate]["3"] = $this->ReturnArrayOfRate($aMonth, 3, $sumCount);
                    $aMonths[$lastDate]["4"] = $this->ReturnArrayOfRate($aMonth, 4, $sumCount);

                    $lastDate = $row['rate_date'];
                    $newMonth = $tMonth;
                    unset($aMonth);
                    $sumCount = 1;
                } else {
                    $sumCount++;
                }

                $aMonth[] = $row['rate'];
            }

            //add last month


            if (count($aMonth) > 0) {
                if ($sumCount == 0) {
                    $sumCount++;
                }
                $aMonths[$lastDate]["1"] = $this->ReturnArrayOfRate($aMonth, 1, $sumCount);
                $aMonths[$lastDate]["2"] = $this->ReturnArrayOfRate($aMonth, 2, $sumCount);
                $aMonths[$lastDate]["3"] = $this->ReturnArrayOfRate($aMonth, 3, $sumCount);
                $aMonths[$lastDate]["4"] = $this->ReturnArrayOfRate($aMonth, 4, $sumCount);
            }

            $this->reportData = $this->GraphData($aMonths);
            return;
        }

        //Display Report
        echo "\n\n<script type='text/javascript'>";

        echo "\n\n";
        echo "function ChangeDate(strYear,strMonth){
                location.href='index.php?fuse=reports&view=ViewReport&report=Support_Service_Rating&type=Support&year='+strYear+'&month='+strMonth;
                }";
        echo "\n\n";
        echo "</script>";
        echo "\n\n";
        $thisYear = date("Y");
        $thisMonth = date("m");
        if (isset($_GET['year'])) {
            $currentYear = $_GET['year'];
        } else {
            $currentYear = $thisYear;
        }

        if (isset($_GET['month'])) {
            $currentMonth = $_GET['month'];
        } else {
            if ($currentYear != $thisYear) {
                $currentMonth = 12;
            } else {
                $currentMonth = $thisMonth;
            }
        }

        //Class Initialize
        $this->SetDescription($this->user->lang('Displays Service Rating.(click on a date to view Rating tickets for that year and month)'));

        //Now we query for all support tickets all time
        $sql = "SELECT "
                . "DATE_FORMAT( lastlog_datetime, '%Y' ) AS date_opened, "
                . "lastlog_datetime AS date_unformatted, "
                . "COUNT(*) "
                . "FROM troubleticket "
                . "WHERE status IN (".implode(', ', $statusClosed).") AND rate>0 "
                . "GROUP BY DATE_FORMAT( lastlog_datetime, '%Y' ) "
                . "ORDER BY DATE_FORMAT( lastlog_datetime, '%Y' ) ASC";
        $result = $this->db->query($sql);
        if ($result->getNumRows() > 0) {

            $prev_year = false;

            while (list($year, $unformatted, $tickets) = $result->fetch()) {
                $basePage = $_SERVER["REQUEST_URI"];
                $nPos = strpos($basePage, "&year=");
                if ($nPos === false) {
                    $basePage .= "&year=";
                } else {
                    $basePage = mb_substr($basePage, 0, $nPos) . "&year=";
                }
                $yearLi = mb_substr($unformatted, 0, 4);
                $year = "<a href=\"" . $basePage . $yearLi . "\">" . $year . "</a>";
                $aGroup[] = array($year, $tickets);
                $prev_year = $tickets;
            }

            //add final group
            if (isset($aGroup)) {
                $this->reportData[] = array("group" => $aGroup,
                    "groupname" => "",
                    "label" => array($this->user->lang('Year'), $this->user->lang('Tickets Rating')),
                    "groupId" => "",
                    "isHidden" => false);
                unset($aGroup);
            }

            if (@$_GET['year']) {
                //Now we query for all support tickets in this year
                $sql = "SELECT users.firstname, users.lastname, t.id, troubleticket_type.name, t.subject, t.datesubmitted, t.lastlog_datetime, t.rate  "
                        . "FROM troubleticket t "
                        . "LEFT JOIN users ON ( users.id = t.assignedtoid ) "
                        . "LEFT JOIN troubleticket_type ON ( troubleticket_type.id = t.messagetype ) "
                        . "WHERE SUBSTRING( t.lastlog_datetime, 1, 4 ) =? "
                        . "AND t.status IN (".implode(', ', $statusClosed).") and t.rate>0 "
                        . "ORDER BY t.lastlog_datetime, t.messagetype";

                $result = $this->db->query($sql, $_GET['year']);

                //initialize
                $prevMonth = "-1";
                $monthTotal = 0;
                $yearTotal = 0;
                $avgrate = 0;
                $prevMonthName = "";
                while (list($assignedToFirst, $assignedToLast, $ttID, $ttType, $ttSubject, $ttDateSubmitted, $ttLastLog_DateTime, $ttRate) = $result->fetch()) {

                    $thisMonth = date("n", strtotime($ttLastLog_DateTime));
                    $assignedTo = $assignedToFirst . " " . $assignedToLast;

                    if ($prevMonth != $thisMonth) {
                        if (isset($aGroup)) {

                            $aGroup[] = array($this->user->lang('Total This Month'), $monthTotal, $this->user->lang('Average Rate'), $this->user->lang($leyends[round($avgrate / $monthTotal)]));
                            //add previous group before getting next group
                            $this->reportData[] = array("group" => $aGroup,
                                "groupname" => $prevMonthName,
                                "label" => array($this->user->lang('Ticket ID'), $this->user->lang('Ticket Type'), $this->user->lang('Assigned To'), $this->user->lang('Rate')),
                                "groupId" => "",
                                "isHidden" => false);
                            unset($aGroup);
                        }
                        $aGroup = array();
                        $monthTotal = 0;
                        $avgrate = 0;
                        $prevMonth = $thisMonth;
                        $prevMonthName = date("F Y", strtotime($ttLastLog_DateTime));
                    }

                    $avgrate+=$ttRate;
                    $monthTotal++;
                    $yearTotal++;
                    $linkedTicket = "<a title='" . html_entity_decode($ttSubject) . "' href=index.php?fuse=support&view=viewtickets&controller=ticket&id=" . $ttID . ">" . $ttID . "</a>";

                    $assignedTo = ($assignedTo == "") ? "Not Recorded" : $assignedTo;
                    $aGroup[] = array($linkedTicket, $this->user->lang($ttType), $assignedTo, " " . $this->user->lang($leyends[$ttRate]));
                }



                //add final group
                if (isset($aGroup)) {
                    $aGroup[] = array($this->user->lang('Total This Month'), $monthTotal, $this->user->lang('Average Rate'), $this->user->lang($leyends[round($avgrate / $monthTotal)]));
                    $this->reportData[] = array("group" => $aGroup,
                        "groupname" => $prevMonthName,
                        "label" => array($this->user->lang('Ticket ID'), $this->user->lang('Ticket Type'), $this->user->lang('Assigned To'), $this->user->lang('Rate')),
                        "groupId" => "",
                        "isHidden" => false);

                } else {
                    $this->reportData[] = array("group" => $aGroup,
                    "groupname" => $this->user->lang('Total for') . " " . $_GET['year'],
                    "label" => array("", "", "", "", "", ""),
                    "groupId" => "",
                    "isHidden" => false);
                }
                unset($aGroup);

            }
        } else {
            $this->reportData[] = array("group" => array(),
                "groupname" => "",
                "label" => array(""),
                "groupId" => "",
                "isHidden" => false);
        }
    }

    function ReturnArrayOfRate($aMonths, $rate, $totalCount) {
        $count = 0;
        foreach ($aMonths as $mRate) {
            if ($mRate == $rate)
                $count++;
        }

        $avg = ($count / $totalCount);
        $avg = $avg * 100;
        return round($avg);
    }

    /**
     * Function to generate the graph data
     *
     * @return null - direct output
     */
    function GraphData($aMonths) {


        //building graph data to pass back
        $graph_data = array(
              "xScale" => "ordinal",
              "yScale" => "exponential",
              "yType" => "percent",
              "type" => "bar",
              "main" => array());

        for($x=1; $x<=4; $x++) {
            //we are getting values per rating 1-4
            switch ($x) {
                case "1":
                    $rateName = $this->user->lang("Excellent");
                    break;
                case "2":
                    $rateName = $this->user->lang("Good");
                    break;
                case "3":
                    $rateName = $this->user->lang("Okay");
                    break;
                case "4":
                    $rateName = $this->user->lang("Poor");
                    break;
            }


            $month_ratings = array();
            $month_ratings['className'] = ".report_rating_".$rateName;
            $month_ratings['data'] = array();

            if (!$aMonths) {
              // provide default empty data so that the graph is not just empty blank space
              $aMonths = array(
                date('Y-m-1', strtotime('last month')) => array(0, 0, 0, 0),
                date('Y-m-1') => array(0, 0, 0, 0),
              );
            }

            foreach ($aMonths as $key => $ratings) {


                $pretty_month = (date("F",strtotime($key)));
                $pretty_year = (date("Y",strtotime($key)));

                $month_data = array();
                if ($ratings[$x] == "") $ratings[$x] = "0";
                $month_data["x"] = $key;
                $month_data["y"] = $ratings[$x];
                $month_data["tip"] = "<strong>".$pretty_month.", ".$pretty_year."</strong><br/>".$rateName." ".$ratings[$x]."%";
                $month_ratings['data'][] = $month_data;

            }

            $graph_data["main"][] = $month_ratings;
        }

        return json_encode($graph_data);

    }

}

?>
