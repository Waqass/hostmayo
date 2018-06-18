<?php
/**
 * Support Ticket Totals Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.3
 * @link     http://www.clientexec.com
 *
 *************************************************
 *   1.0 Initial version by Daniel Jones - Nation Voice Commuincations LLC (NationVoice.com)
 *   1.1 Keith with assistance from Mike & Alejandro (07/2007)
 *   1.2 Juan David (10/2007)
 *   1.3 Updated the report to use Pear Commenting & the new title handing to make app reports consistent.
 ************************************************
 */

require_once 'modules/admin/models/StatusAliasGateway.php';
require_once 'modules/support/models/TicketLog.php';
require_once 'library/CE/NE_GroupsGateway.php';

/**
 * Ticket_Totals Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.3
 * @link     http://www.clientexec.com
 */
class Ticket_Totals extends Report
{
    private $lang;

    protected $featureSet = 'support';

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Ticket Totals');
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
        $this->SetDescription($this->user->lang('Displays how many tickets each staff member has closed (and how many times has replied to them) based on a date range.'));

        if(isset($_GET['startdate'])) {
            $startDateArray = explode('/', $_GET['startdate']);
            $tempStartDate = date("Y-m-d", mktime(0, 0, 0, $startDateArray[0], $startDateArray[1], $startDateArray[2]));
            $temp2StartDate = mktime(0, 0, 0, $startDateArray[0], $startDateArray[1], $startDateArray[2]);
        }else {
            $tempStartDate = date("Y-m-d", mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
            $temp2StartDate = mktime(0, 0, 0, date("m")-1, date("d"), date("Y"));
        }

        if(isset($_GET['enddate'])) {
            $endDateArray = explode('/', $_GET['enddate']);
            $tempEndDate = date("Y-m-d", mktime(0, 0, 0, $endDateArray[0], $endDateArray[1], $endDateArray[2]));
            $temp2EndDate = mktime(0, 0, 0, $endDateArray[0], $endDateArray[1]+1, $endDateArray[2]);
        }else {
            $tempEndDate = date("Y-m-d");
            $temp2EndDate = mktime(0, 0, 0, date("m"), date("d")+1, date("Y"));
        }
        $amountOfDays = ((($temp2EndDate-$temp2StartDate)/60)/60)/24;

        if ($this->settings->get('Date Format') == 'm/d/Y') {
            $dateFormat = '%m/%d/%Y';
        } else {
            $dateFormat = '%d/%m/%Y';
        }

        echo "<div style='margin-left:20px;'>";
        echo $this->user->lang('Displays how many tickets each staff member has closed (and how many times has replied to them) based on a date range.')."<br/><br/>";        
        echo "<form id='reportdropdown' method='GET'>";
        echo "<table border=0 cellpadding=2 cellspacing=0>";
        echo "<tr><td>";        
        echo $this->user->lang('Start Date').":&nbsp;&nbsp;";

        echo '</td><td><input style="width:70px;" type=text size=12 MAXLENGTH=10 name="startdate" id="startdate" value=\''.CE_Lib::db_to_form($tempStartDate, $this->settings->get('Date Format'), "/").'\'>';

        echo "<tr><td>".$this->user->lang('End Date').":&nbsp;&nbsp;";

        echo '</td><td><input style="width:70px;" type=text size=12 MAXLENGTH=10 name="enddate" id="enddate" value=\''.CE_Lib::db_to_form($tempEndDate, $this->settings->get('Date Format'), "/").'\'>';

        echo '&nbsp;&nbsp;&nbsp;
                <input type=button name=search class="btn" value=\''.$this->user->lang('search').'\' onclick="ChangeTable(document.getElementById(\'startdate\').value, document.getElementById(\'enddate\').value);">
              &nbsp;&nbsp;';
        echo "</td></tr></table>";
        echo "</form>";
        echo "</div>";


        echo "\n\n<script type='text/javascript'>

                function ChangeTable(strStartDate, strEndDate){
                    location.href='index.php?fuse=reports&report=Ticket_Totals&controller=index&type=Support&view=viewreport&startdate='+strStartDate+'&enddate='+strEndDate+'&change=1';
                }
            </script>";
        echo "\n\n";


        if(isset($_GET['change']) && $_GET['change']==1 && $amountOfDays > 0) {
            $queryTypes = "SELECT id, name FROM troubleticket_type WHERE enabled=1 ORDER BY myorder";
            $resultTypes = $this->db->query($queryTypes);
            $types = array();
            $tags = array($this->user->lang('Staff Member'));
            while(list($typeID, $type) = $resultTypes->fetch()) {
                $types[$typeID] = $type;
                $tags[] = $this->user->lang($type);
            }
            $tags[] = $this->user->lang('Total Closed');
            $tags[] = $this->user->lang('Tickets/Day');
            $tags[] = $this->user->lang('Replies/Ticket');

            $totalTotal = array();
            foreach($types AS $tType) {
                $totalTotal[$tType] = 0;
                $totalTotal[$tType.'Answers'] = 0;
            }

            $groupGateway = new NE_GroupsGateway();
            $groupIterator = $groupGateway->getAdminGroups();

            $closedStatuses = StatusAliasGateway::ticketClosedAliases($this->user);
            $queryTickets = "SELECT t.assignedtoid, t.messagetype, COUNT(t.id) "
                    ."FROM troubleticket t "
                    ."INNER JOIN troubleticket_log "
                    ."ON (t.id = troubleticket_log.troubleticketid AND t.assignedtoid= troubleticket_log.userid) "
                    ."WHERE t.status IN (".implode(', ', $closedStatuses).") "
                    ."AND t.lastlog_datetime >= '$tempStartDate' "
                    ."AND t.lastlog_datetime <= '$tempEndDate 23:59:59' "
                    ."AND troubleticket_log.logtype=?"
                    ."GROUP BY t.assignedtoid, t.messagetype, t.id";
            $resultTickets = $this->db->query($queryTickets, TicketLog::TYPE_MSG);
            $arrayAllInfo = array();
            while(list($assignedtoid, $messagetype, $repliesamount) = $resultTickets->fetch()) {
                if(isset($arrayAllInfo[$assignedtoid][$messagetype])) {
                    $arrayAllInfo[$assignedtoid][$messagetype]['amountReplies'] += $repliesamount;
                    $arrayAllInfo[$assignedtoid][$messagetype]['amountTickets'] += 1;
                }else {
                    $arrayAllInfo[$assignedtoid][$messagetype]['amountReplies'] = $repliesamount;
                    $arrayAllInfo[$assignedtoid][$messagetype]['amountTickets'] = 1;
                }
            }

            while ($group = $groupIterator->fetch()) {
                $query = "SELECT id, firstname, lastname FROM users WHERE groupid=? ORDER BY firstname, lastname";
                $result = $this->db->query($query, $group->getId());
                $aGroup = array();

                $totalLevel = array();
                foreach($types AS $tType) {
                    $totalLevel[$tType] = 0;
                    $totalLevel[$tType.'Answers'] = 0;
                }

                while(list($id, $firstname, $lastname) = $result->fetch()) {
                    $values = array($firstname.' '.$lastname);
                    $total = 0;
                    $totalAnswers = 0;
                    foreach($types AS $tTypeID=>$tType) {
                        $amount = isset($arrayAllInfo[$id][$tTypeID]['amountTickets'])? $arrayAllInfo[$id][$tTypeID]['amountTickets'] : 0;
                        $amountAnswers = isset($arrayAllInfo[$id][$tTypeID]['amountReplies'])? $arrayAllInfo[$id][$tTypeID]['amountReplies'] : 0;

                        $totalLevel[$tType] += $amount;
                        $totalTotal[$tType] += $amount;
                        $totalLevel[$tType.'Answers'] += $amountAnswers;
                        $totalTotal[$tType.'Answers'] += $amountAnswers;
                        $values[] = "$amount ($amountAnswers)";
                        $total += $amount;
                        $totalAnswers += $amountAnswers;
                    }
                    $values[] = "<b>$total ($totalAnswers)</b>";
                    $values[] = '<b>'.round($total/$amountOfDays, 2).'</b>';
                    $RepliesXticket = ($total != 0)? round($totalAnswers/$total, 2) : 'n/a';
                    $values[] = '<b>'.$RepliesXticket.'</b>';

                    $aGroup[] = $values;
                }
                if(count($aGroup) > 0) {
                    $totalLevelValues = array('<font color=green><b>'.$this->user->lang('TOTAL').'</b></font>');
                    $totalTotalLevel = 0;
                    $totalTotalLevelAnswers = 0;
                    foreach($types AS $tType) {
                        $totalLevelValues[] = '<font color=green><b>'.$totalLevel[$tType].' ('.$totalLevel[$tType.'Answers'].')</b></font>';
                        $totalTotalLevel += $totalLevel[$tType];
                        $totalTotalLevelAnswers += $totalLevel[$tType.'Answers'];
                    }

                    $totalLevelValues[] = "<font color=green><b>$totalTotalLevel ($totalTotalLevelAnswers)</b></font>";
                    $totalLevelValues[] = '<font color=green><b>'.round($totalTotalLevel/$amountOfDays, 2).'</b></font>';
                    $RepliesXticket = ($totalTotalLevel != 0)? round($totalTotalLevelAnswers/$totalTotalLevel, 2) : 'n/a';
                    $totalLevelValues[] = '<font color=green><b>'.$RepliesXticket.'</b></font>';
                    $aGroup[] = $totalLevelValues;

                    $this->reportData[] = array("group"=>$aGroup,
                        "groupname"=>$this->user->lang($group->getName()),
                        "label"=>$tags,
                        "groupId"=>"",
                        "isHidden"=>false);

                }else {
                    $aGroup = array(array('<font color=red>'.$this->user->lang('no values').'</font>'));
                    $this->reportData[] = array("group"=>$aGroup,
                        "groupname"=>$this->user->lang($group->getName()),
                        "label"=>array(''),
                        "groupId"=>"",
                        "isHidden"=>false);
                }
            }

            $totalValues = array('<font color=green><b>'.$this->user->lang('TOTAL').'</b></font>');
            $totalTotalValues = 0;
            $totalTotalValuesAnswers = 0;
            foreach($types AS $tType) {
                $totalValues[] = '<font color=green><b>'.$totalTotal[$tType].' ('.$totalTotal[$tType.'Answers'].')</b></font>';
                $totalTotalValues += $totalTotal[$tType];
                $totalTotalValuesAnswers += $totalTotal[$tType.'Answers'];
            }

            $totalValues[] = "<font color=green><b>$totalTotalValues ($totalTotalValuesAnswers)</b></font>";
            $totalValues[] = '<font color=green><b>'.round($totalTotalValues/$amountOfDays, 2).'</b></font>';
            $RepliesXticket = ($totalTotalValues != 0)? round($totalTotalValuesAnswers/$totalTotalValues, 2) : 'n/a';
            $totalValues[] = '<font color=green><b>'.$RepliesXticket.'</b></font>';
            $aGroup2 = array();
            $aGroup2[] = $totalValues;

            $this->reportData[] = array("group"=>$aGroup2,
                "groupname"=> $this->user->lang('Totals'),
                "label"=>$tags,
                "groupId"=>"",
                "isHidden"=>false);

        }else {
            echo "<div style='margin-left:20px;'>";
            if($amountOfDays <= 0) {
                echo '<br/><font color=red>'.$this->user->lang('Please select a valid date range and press SEARCH.').'</font>';
            }
            echo "</div>";
        }
    }
}
?>
