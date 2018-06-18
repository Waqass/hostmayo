<?php

require_once 'modules/admin/models/StatusAliasGateway.php';

/*
 * @package Reports
 */

class Staff_Report extends Report {

    protected $featureSet = 'accounts';
    public $hasgraph = false;


    public function process() {
        $this->SetDescription($this->user->lang('Staff Report.'));

        echo "<div style='margin:5px;'>";
        echo '<div class="well">';

        if (isset($_GET['date1'])) {
            // $pulldate = $_GET['date'];
            $d1 = new DateTime($_GET['date1']);
            $pulldate1 = $d1->format( 'm-d-Y' );
            $d1 = new DateTime($_GET['date2']);
            $pulldate2 = $d1->format( 'm-d-Y' );

        } else {
            $pulldate1 = date("m-d-Y");
            $pulldate2 = date("m-d-Y");
        }

        echo "<table>";
        echo "<tr><td><b>From:</b></td><td><div style='margin-left: 20px;'><b>To:</b></div></td><td></td></tr>";
        echo "<tr><td>";
        echo '<div class="input-append date report-date" id="dp3" data-date="'.$pulldate1.'" data-date-format="mm-dd-yyyy" style="margin-bottom: 0;">';
        echo '<input class="span2" size="16" type="text" value="'.$pulldate1.'" readonly="">';
        echo '<span class="add-on"><i class="icon-calendar"></i></span>';
        echo '</div>';
        echo "</td><td><div style='margin-left: 20px;'>";

        echo '<div class="input-append date report-date2" id="dp3" data-date="'.$pulldate2.'" data-date-format="mm-dd-yyyy" style="margin-bottom: 0;">';
        echo '<input class="span2" size="16" type="text" value="'.$pulldate2.'" readonly="">';
        echo '<span class="add-on"><i class="icon-calendar"></i></span>';

        echo "</div></td><td><button type='button' class='btn btn-warning btn-loading-data' data-loading-text='Loading...' onclick='pullreport();'>Pull Report</button></td></tr>";
        echo "</table>";

        echo '</div>'; //well

        if (isset($_GET['date1'])) {

            $StaffValues = $this->_getStaff();
            if (count($StaffValues) > 0) {
                echo "<br/><b>Searching with Range</b><br/> ";
                $date1 = new DateTime($StaffValues[0]);
                $date2 = new DateTime($StaffValues[1]);
                echo "<i>".$date1->format('l jS \of F Y h:i:s A')." PST</i> <b>-</b> ";
                echo "<i>".$date2->format('l jS \of F Y h:i:s A')." PST</i>";
            }

            echo "<table class='table table-striped' style='margin-top:20px;'>";
            echo "<thead>";
            echo "<tr>";

            echo "<th>Technician</th>";
            echo "<th style='text-align:center'># Chat</th>";
            echo "<th style='text-align:center'># Calls</th>";
            echo "<th style='text-align:center'># Tickets Created</th>";
            echo "<th style='text-align:center'># Tickets Completed</th>";
            // echo "<th style='text-align:center'>Chats Per Shift All Users</th>";
            // echo "<th style='text-align:center'>Calls Per Shift All Users</th>";

            //  Ticket Created Per Shift All Users,
            //  Ticket Completed Per Shift All Users,

            echo "<th style='text-align:center'>Average Ticket Close Time</th>";

            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";


            if (count($StaffValues) > 0) {
                foreach ($StaffValues[2] as $row) {
                    echo "<tr>";
                    echo "<td>".$row['user']."</td>";
                    echo "<td style='text-align:center'>".$row['chat_num']."</td>";
                    echo "<td style='text-align:center'>".$row['call_num']."</td>";
                    echo "<td style='text-align:center'>".$row['tix_created']."</td>";
                    echo "<td style='text-align:center'>".$row['tix_num']."</td>";
                    // echo "<td style='text-align:center'>".$row['chat_all']."</td>";
                    // echo "<td style='text-align:center'>".$row['calls_all']."</td>";
                    echo "<td style='text-align:center'>".$row['tix_avg_time']."</td>";
                    echo "</tr>";
                }
            }

            echo "</tbody>";
            echo "</table>";
            echo "</div>";

        }

        echo "\n\n<script type='text/javascript'>\n";

        echo "\n\n$(document).ready(function(){";

        echo "\n\n";
        echo "pullreport = function(){\n";
        echo "  RichHTML.mask();\n";
        echo "  $('.btn-loading-data').button('loading');\n";

        echo "  var date = $('.report-date').attr('data-date');";
        echo '  date = new Date(date.replace(/-/g,"/"));';
        echo "  var y = date.getFullYear();\n";
        echo "  _m = date.getMonth() + 1;\n";
        echo "  m = (_m > 9 ? _m : '0'+_m);\n";
        echo "  _d = date.getDate();\n";
        echo "  d = (_d > 9 ? _d : '0'+_d);\n";
        echo "  date1 = y + '-' + m + '-' + d;";

        echo "  var date = $('.report-date2').attr('data-date');";
        echo '  date = new Date(date.replace(/-/g,"/"));';
        echo "  var y = date.getFullYear();\n";
        echo "  _m = date.getMonth() + 1;\n";
        echo "  m = (_m > 9 ? _m : '0'+_m);\n";
        echo "  _d = date.getDate();\n";
        echo "  d = (_d > 9 ? _d : '0'+_d);\n";
        echo "  date2 = y + '-' + m + '-' + d;";

        echo "  window.location.href = window.location.origin+'/members/admin/index.php?fuse=reports&view=viewreport&controller=index&report=Staff+Report&type=Support&date1='+date1+'&date2='+date2;\n";
        echo " ";
        echo "};";
        echo "\n";

        echo "\n$('.report-date')";
        echo ".datepicker({format: 'mm-dd-yyyy'})\n";
        echo ".on('changeDate', function(ev) {\n";
        echo "  var y = ev.date.getFullYear(),\n";
        echo "  _m = ev.date.getMonth() + 1,\n";
        echo "  m = (_m > 9 ? _m : '0'+_m),\n";
        echo "  _d = ev.date.getDate(),\n";
        echo "  d = (_d > 9 ? _d : '0'+_d);\n";
        echo "  var formattedDate = m + '-' + d + '-' + y;\n";
        echo "  $('.report-date').attr('data-date', formattedDate).datepicker('hide');";
        echo "});\n";

        echo "\n$('.report-date2')";
        echo ".datepicker({format: 'mm-dd-yyyy'})\n";
        echo ".on('changeDate', function(ev) {\n";
        echo "  var y = ev.date.getFullYear(),\n";
        echo "  _m = ev.date.getMonth() + 1,\n";
        echo "  m = (_m > 9 ? _m : '0'+_m),\n";
        echo "  _d = ev.date.getDate(),\n";
        echo "  d = (_d > 9 ? _d : '0'+_d);\n";
        echo "  var formattedDate = m + '-' + d + '-' + y;\n";
        echo "  $('.report-date2').attr('data-date', formattedDate).datepicker('hide');";
        echo "});\n";

        echo "});\n";

        echo "</script>";


    }

    private function _getStaff()
    {
        $queryStaffCustomFields = "SELECT `id`, `name` FROM `customuserfields` WHERE `name` IN ('ChatUsername', 'AsteriskExtension') ";
        $resultStaffCustomFields = $this->db->query($queryStaffCustomFields);
        $StaffCustomFields = array();
        $StaffCustomFieldsIds = array();
        while ($rowStaffCustomFields = $resultStaffCustomFields->fetch()) {
            $StaffCustomFields[$rowStaffCustomFields['id']] = $rowStaffCustomFields['name'];
            $StaffCustomFieldsIds[] = $rowStaffCustomFields['id'];
        }

        if(count($StaffCustomFieldsIds) > 0){
            $queryStaffCustomFieldsValues = "SELECT `userid`, `customid`, `value` FROM `user_customuserfields` uc, users u WHERE `customid` IN (".implode(',', $StaffCustomFieldsIds).") AND u.id = uc.userid order by CONCAT(u.firstname, ' ', u.lastname)";
            $resultStaffCustomFieldsValues = $this->db->query($queryStaffCustomFieldsValues);
            $StaffCustomFieldsValues = array();
            while ($rowStaffCustomFieldsValues = $resultStaffCustomFieldsValues->fetch()) {
                $StaffCustomFieldsValues[$rowStaffCustomFieldsValues['userid']][$StaffCustomFields[$rowStaffCustomFieldsValues['customid']]] = $rowStaffCustomFieldsValues['value'];
            }
        }

        //get start date and end date based on shift

        $day1 = $_GET['date1']; //day of the year from calendar widget
        $day2 = $_GET['date2']; //day of the year from calendar widget

        $startDate = $this->getStartOfShift($day1);
        $endDate   = $this->getEndOfShift($day2);

        $aData = array($startDate, $endDate, $this->_getStaffData($StaffCustomFieldsValues, $startDate, $endDate));
        return $aData;

    }

    //Date helper functions
    private function getStartOfShift($day)
    {
        return $day.' 00:00:00';
    }

    private function getEndOfShift($day)
    {

        // $date = new DateTime($day);
        // $date->modify( '+1 day' );
        // $day = $date->format( 'Y-m-d' );
        // $time = "04:00:00";

        return $day.' 23:59:00';
    }

    private function _getStaffData($staff, $shiftStartTime, $shiftEndTime)
    {

        // Calls
        $nightShiftCalls   = 0;
        $dayShiftCalls     = 0;
        $eveningShiftCalls = 0;
        $earlyShiftCalls   = 0;
        $lateShiftCalls    = 0;
        $swingShiftCalls   = 0;
        $answeredCalls     = 0;
        $totalCalls        = 0;

        // Chats
        $nightShiftChats   = 0;
        $dayShiftChats     = 0;
        $eveningShiftChats = 0;
        $earlyShiftChats   = 0;
        $lateShiftChats    = 0;
        $swingShiftChats   = 0;
        $answeredChats     = 0;
        $totalChats        = 0;

        // Tickets
        $nightShiftOpenTickets     = 0;
        $nightShiftClosedTickets   = 0;
        $dayShiftOpenTickets       = 0;
        $dayShiftClosedTickets     = 0;
        $eveningShiftOpenTickets   = 0;
        $eveningShiftClosedTickets = 0;
        $earlyShiftOpenTickets     = 0;
        $earlyShiftClosedTickets   = 0;
        $lateShiftOpenTickets      = 0;
        $lateShiftClosedTickets    = 0;
        $swingShiftOpenTickets     = 0;
        $swingShiftClosedTickets   = 0;
        $openedTickets             = 0;
        $closedTickets             = 0;
        $totalTickets              = 0;

        $ar = array();
        foreach ($staff as $userid => $value) {
            $user = new User($userid);
            $item = array();
            $item['user'] = $user->getFullName();
            $item['chat_num'] = "0";
            $item['call_num'] = "0";
            $item['tix_num'] = "0";
            $item['tix_avg_time'] = "NA";

            $employeeAnsweredCalls     = 0;
            $employeeAnsweredChats     = 0;
            $employeeTotalTickets               = 0;
            $employeeAverageCompletedTicketTime = 0;

            // Connect to voip5 and return values
            $calls = $this->GetCalls($shiftStartTime, $shiftEndTime, $value['AsteriskExtension']);

            if($calls === false){
                $customerServiceCalls = 0;
                $billingCalls         = 0;
                $technicalCalls       = 0;
                $answeredCallsNow     = 0;
            }else{
                $customerServiceCalls = $calls['customerServiceCalls'];
                $billingCalls         = $calls['billingCalls'];
                $technicalCalls       = $calls['technicalCalls'];
                $answeredCallsNow     = $calls['answeredCalls'];
            }


            $item['total_call_num'] = $customerServiceCalls + $billingCalls + $technicalCalls;
            $item['call_num'] = $answeredCallsNow;

            $chats = $this->GetChats($shiftStartTime, $shiftEndTime, $value['ChatUsername']);
            if($chats === false){
                $todayChats = 0;
                $answeredChatsNow = 0;
            }else{
                $todayChats       = $chats['totalChats'];
                $answeredChatsNow = $chats['totalAnswered'];
            }

            $item['chat_num'] = $answeredChatsNow;
            $tickets = $this->GetTickets($shiftStartTime, $shiftEndTime, $user->getEmail());

            if($tickets === false){
                $todayOpenedTickets             = 0;
                $todayClosedTickets             = 0;
                $todayTickets                   = 0;
                $todayAverageCompleteTicketTime = 0;
            }else{
                $todayOpenedTickets             = $tickets['ticketsOpened'];
                $todayClosedTickets             = $tickets['ticketsClosed'];
                $todayTickets                   = $tickets['totalTickets'];
                $todayAverageCompleteTicketTime = $tickets['averageCompletedTicketTime'];
            }

            // Employee totals
            $employeeAnsweredCalls              += $answeredCallsNow;
            $employeeAnsweredChats              += $answeredChatsNow;
            $totalCalls                         += $todayCalls;
            $totalChats                         += $todayChats;
            $employeeAverageCompletedTicketTime += $todayAverageCompleteTicketTime;

            $item['tix_num'] = $todayTickets;
            $item['tix_created'] = $todayOpenedTickets;

            if ($todayAverageCompleteTicketTime != "") {
                $avgtime = explode(":", gmdate("H:i:s", $todayAverageCompleteTicketTime));
                $item['tix_avg_time'] = $avgtime[0]." hrs ".$avgtime[1]." mins";
            }

            //let's not add item if they don't have data
            if ( ($item['tix_num'] == 0) &&
                 ($item['tix_created'] == 0) &&
                 ($item['chat_num'] == 0) &&
                 ($item['call_num'] == 0) &&
                 ($item['tix_avg_time'] == "NA") ){

                // $ar[] = $item;

            } else {
                $ar[] = $item;
            }
        }

        return $ar;
    }

    //collection of data helper methods
    //$startTime and $endTime are in Unix timestamp
    function GetCalls($startTime, $endTime, $extension)
    {

        if ($extension == "") return false;

        $startTime = urlencode($startTime);
        $endTime = urlencode($endTime);
        $extension = urlencode($extension);
        $url = "https://voip5.theconsortium.org/call_report.php?startTime=".$startTime."&endTime=".$endTime."&dst=".$extension;

        // //Make request using cURL
        try{
            $csv = $this->makeRequest($url);
        }catch(Exception $ex){
            CE_Lib::log(1, "Issue getting calls for startTime: ".date("Y-m-d H:i:s", $startTime).", endTime: ".date("Y-m-d H:i:s", $endTime).", and extension: ".$extension.". ".$ex->getMessage());
            return false;
        }

        $values = explode(',', $csv);

        $customerServiceCalls = $values[0];
        $billingCalls         = $values[1];
        $technicalCalls       = $values[2];
        $answeredCalls        = $values[3];

        return array(
            'customerServiceCalls' => $customerServiceCalls,
            'billingCalls'         => $billingCalls,
            'technicalCalls'       => $technicalCalls,
            'answeredCalls'        => $answeredCalls
        );
    }

    //$startTime and $endTime are in Unix timestamp
    function GetChats($startTime, $endTime, $username)
    {

        if ($username == "") return false;

        $startTime = urlencode($startTime);
        $endTime = urlencode($endTime);
        $username = urlencode($username);
        $url = "http://chat.millenicom.com/chat_report.php?startTime=".$startTime."&endTime=".$endTime."&username=".$username;

        //Make request using cURL
        try{
            $csv = $this->makeRequest($url);
        }catch(Exception $ex){
            CE_Lib::log(1, "Issue getting chats for startTime: ".date("Y-m-d H:i:s", $startTime).", endTime: ".date("Y-m-d H:i:s", $endTime).", and username: ".$username.". ".$ex->getMessage());
            return false;
        }

        $values = explode(',', $csv);

        $totalChats    = $values[0];
        $totalAnswered = $values[1];

        return array(
            'totalChats'    => $totalChats,
            'totalAnswered' => $totalAnswered
        );
    }

    //$startTime and $endTime are in Unix timestamp
    function GetTickets($startTime, $endTime, $username)
    {
        $averageCompletedTicketTime = 0;

        // Get total tickets opened during this period
        $queryTicketsOpened = "SELECT COUNT(*) AS total FROM troubleticket "
            ."INNER JOIN users ON troubleticket.assignedtoid = users.id "
            ."WHERE (users.email = ?) "
            ."AND (troubleticket.datesubmitted BETWEEN ? AND ?) "
            ."AND (troubleticket.datesubmitted = troubleticket.lastlog_datetime) "
            ."AND (troubleticket.datesubmitted IS NOT NULL) ";
        $resultTicketsOpened = $this->db->query($queryTicketsOpened, $username, $startTime, $endTime);
        list($ticketsOpened) = $resultTicketsOpened->fetch();

        // Get total tickets closed by employee during this period
        $queryTicketsClosed = "SELECT COUNT(*) AS total FROM troubleticket "
            ."INNER JOIN users ON troubleticket.assignedtoid = users.id "
            ."WHERE (users.email = ?) "
            ."AND (troubleticket.status = -1) "
            ."AND (troubleticket.lastlog_datetime BETWEEN ? AND ?) "
            ."AND (troubleticket.lastlog_datetime IS NOT NULL) ";
        $resultTicketsClosed = $this->db->query($queryTicketsClosed, $username, $startTime, $endTime);
        list($ticketsClosed) = $resultTicketsClosed->fetch();


        // Get date opened and date closed for all tickets during this period
        $queryTickets = "SELECT UNIX_TIMESTAMP(troubleticket.datesubmitted) AS dateSubmitted, UNIX_TIMESTAMP(troubleticket.lastlog_datetime) AS dateClosed FROM troubleticket "
            ."INNER JOIN users ON troubleticket.assignedtoid = users.id "
            ."WHERE (users.email = ?) "
            ."AND (troubleticket.status = -1) "
            ."AND (troubleticket.lastlog_datetime BETWEEN ? AND ?) "
            ."AND (troubleticket.lastlog_datetime IS NOT NULL) ";
        $resultTickets = $this->db->query($queryTickets, $username, $startTime, $endTime);
        while ($rowTickets = $resultTickets->fetch()) {
            $TotalSeconds = $rowTickets['dateClosed'] - $rowTickets['dateSubmitted'];
            $averageCompletedTicketTime += round($TotalSeconds / $ticketsClosed);
        }

        $totalTickets = $ticketsOpened + $ticketsClosed;

        return array(
            'ticketsOpened'              => $ticketsOpened,
            'ticketsClosed'              => $ticketsClosed,
            'totalTickets'               => $totalTickets,
            'averageCompletedTicketTime' => $averageCompletedTicketTime
        );
    }


    private function makeRequest($url) {

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);
        $code     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        //Check we got a response
        if(strlen($response) == 0) {
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            throw new Exception($code, "CURL error: $errno - $error", $url);
        }

        //Check we got the correct http code
        if($code !== 200) {
            throw new Exception($code, "HTTP status code: $code, response=$response", $url);
        }

        curl_close($ch);

        //Return JSON
        return $response;
    }

}