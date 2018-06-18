<?php
/**
 * New_Customers Report Class
 *
 * @category Report
 * @package  ClientExec
 * @license  ClientExec License
 * @link     http://www.clientexec.com
 */
class New_Customers extends Report
{
    private $lang;

    protected $featureSet = 'accounts';
    public $hasgraph = true;
    private $currentYear = null;
    private $yearCount = 1;

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('New Customers');
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

        // Set the report information
        $this->SetDescription($this->user->lang('Displays the total number of new signups for a given year broken down by month.'));

        if (!isset($_GET['option_id'])) {
            if (isset($_GET['indashboard'])) $option_id = 0;
            else $option_id = 2;
        } else {
            $option_id = filter_input(INPUT_GET,'option_id',FILTER_SANITIZE_NUMBER_INT);
        }

        if (!isset($_GET['passedyear'])) {
            $currentYear = date("Y");
        }else {
            $currentYear = filter_input(INPUT_GET,'passedyear',FILTER_SANITIZE_NUMBER_INT);
        }


        $thisYear = Date("Y");
        $aYears = $this->returnRows($option_id, $currentYear);

        $graphdata = @$_GET['graphdata'];
        if($graphdata) {
            //this supports lazy loading and dynamic loading of graphs
            $this->reportData = $this->GraphData($aYears,$option_id);
            return;
        }
        
        //Display Report
        echo "<script type='text/javascript'>";
        echo "function ShowYears(obj){";
        echo "	var strYear = obj.value;";
        //Loop the number of years
        for($x=0;$x<$this->yearCount;$x++) {
            $xYear = $thisYear - $x;
            echo  "if(document.getElementById('id-$xYear') != null) {";
            echo 	    "document.getElementById('id-$xYear').style.display='none';";
            echo  "}";
            echo  "if(document.getElementById('id-$xYear-totals') != null) {";
            echo 	    "document.getElementById('id-$xYear-totals').style.display='none';";
            echo  "}";
        }
        echo  "   if(strYear.substring(0,4)==\"Last\"){";
        echo  "     	yearsback = strYear.substring(4);";
        echo  "       for(x=0;x<yearsback;x++){";
        echo  "          if(document.getElementById('id-'+(".$thisYear."-x)) != null){";
        echo 	"            document.getElementById('id-'+(".$thisYear."-x)).style.display='';";
        echo  "          };";
        echo  "          if(document.getElementById('id-'+(".$thisYear."-x)+'-totals') != null){";
        echo 	"            document.getElementById('id-'+(".$thisYear."-x)+'-totals').style.display='';";
        echo  "          };";
        echo 	"       }";
        echo  "   }else{";
        echo  "       if(document.getElementById('id-'+obj.value) != null){";
        echo 	"           document.getElementById('id-'+obj.value).style.display='';";
        echo  "       };";
        echo  "       if(document.getElementById('id-'+obj.value+'-totals') != null){";
        echo 	"           document.getElementById('id-'+obj.value+'-totals').style.display='';";
        echo  "       };";
        echo  "   }";
        echo "  clientexec.populate_report('New_Customers-Accounts','#myChart',{passedyear:obj.value});\n";        
        echo "}";
        echo "</script>";

        echo "<div style='margin-left:20px;'>";
        echo "<form id='reportdropdown' method='GET'>";
        echo "<input type='hidden' name='fuse' value='reports' />";
        echo "<input type='hidden' name='report' value='New Customers' />";
        echo "<input type='hidden' name='view' value='viewreport' />";
        echo "<input type='hidden' name='type' value='Accounts' />";

        echo $this->user->lang('Select Year Range')."<br/>";
        echo "<select id='passedyear' name='passedyear' onChange='ShowYears(this);'>";

        //Loop the number of years
        for($x=0;$x<$this->yearCount;$x++) {
            $xYear = $thisYear - $x;
            if($currentYear == $xYear) {
                echo "<option value='".$xYear."' SELECTED>".$xYear."</option>";
            }else {
                echo "<option value='".$xYear."'>".$xYear."</option>";
            }
        }

        //Create based on number of years of data available
        for($x=1;$x<$this->yearCount;$x++) {
            $value=$x+1;
            if($currentYear == "Last$value") {
                echo "<option value='Last$value' SELECTED>Last $value Years&nbsp;&nbsp;&nbsp;</option>";
            }else {
                echo "<option value='Last$value'>Last $value Years&nbsp;&nbsp;&nbsp;</option>";
            }
        }

        echo "</select>";
        echo "</form>";
        echo "</div>";        
        
    }

    /**
     * Function to generate the graph data
     *
     * @return null - direct output
     */
    function GraphData($aYears, $option_id)
    {

        if ($option_id == 0) {
            //we only care about this week
            $currentYear = 0;
            $number_of_years = 7; // using number of years for days
            $xScale = "ordinal";
            $yScale = "linear";
            $xType = "daysofweek";
            $type = "bar";
        } else {
            $xScale = "time";
            $xType = "";
            $yScale = "linear";
            $type = "line-dotted";
            if (!isset($_REQUEST['passedyear'])) {
                if (isset($_GET['indashboard'])) {
                    $currentYear = "Last2";       
                } else {
                    $currentYear = date("Y");                
                }
            }else {
                $currentYear = $_REQUEST['passedyear'];
            }    
            $number_of_years = 0;
            if (mb_substr($currentYear, 0, 4) == "Last") {
                $number_of_years = mb_substr($currentYear, 4);            
            }

        }

        $yearCount=0;
        $ydata = array();

        //building graph data to pass back
        $graph_data = array(
              "xScale" => $xScale,
              "yScale" => $yScale,
              "xType" => $xType,
              "type" => $type,
              "main" => array());

        foreach($aYears as $key => $aYear) {

            if($number_of_years>0 || $currentYear==$key) {

                $year_data = array();
                $year_data['className'] = ".report_newcustomer".$key;
                $year_data['data'] = array();

                foreach ($aYear as $months) {

                    if (!is_array($months)) continue;

                    foreach($months as $year => $monthtotal) {

                        $month_data = array();
                        if ($monthtotal[1] == "") $monthtotal[1] = "0";  

                        if ($option_id == 0) {
                            $month_data["x"] = $monthtotal[0];
                            $month_data["y"] = $monthtotal[1];
                            $month_data["tip"] = "<strong>".$monthtotal[0]."</strong><br/>".$this->user->lang("New customers").": ".$monthtotal[1];
                        } else {
                            $month_data["x"] = $year;
                            $month_data["y"] = $monthtotal[1];
                            $month_data["tip"] = "<strong>".$monthtotal[0].", ".$key."</strong><br/>".$this->user->lang("New customers").": ".$monthtotal[1];
                        }
                  
                        $year_data['data'][] = $month_data;

                    }

                    // if there's no data, fill with emptiness for the graph to at least show the axis (this is for the dashboard)
                    if (!$year_data['data']) {
                      $year_data['data'] = array(array(
                        'x' => date('Y-01-01'),
                        'y' => 0,
                        'tip' => $this->user->lang('No Data')
                      ));
                    }

                    $graph_data["main"][] = $year_data;
                }
                $number_of_years--;
            }

        }

        // if there's no data, fill with emptiness for the graph to at least show the axis (this is for the reports module)
        if (!$graph_data['main']) {
          $graph_data['main'] = array(array(
            'className' => '.report_newcustomer0',
            'data'=> array(array(
                'x' => date('Y-01-01'),
                'y' => 0,
                'tip' => $this->user->lang('No Data')
            ))
          ));
        }

        return json_encode($graph_data);

    }

    private function ord($a) {
      // return English ordinal number
      return $a.substr(date('jS', mktime(0,0,0,1,($a%10==0?9:($a%100>20?$a%10:$a%100)),2000)),-2);
    }

    private function retunRowsForWeek()
    {

        $aYears = array();

        $sql  = "SELECT count(id) as count, DATE_FORMAT(dateActivated,'%W') as day, DATE_FORMAT(dateActivated,'%w') as day_num, DATE_FORMAT(dateActivated,'%Y-%m-%d') as dateActivated, DATE_FORMAT(dateActivated,'%d') as dayActivated FROM users ";
        $sql .= "WHERE DATE_ADD( dateActivated, INTERVAL 7 DAY ) > NOW() ";
        $sql .= "GROUP BY dateActivated  ";
        $sql .= "ORDER BY  `users`.`dateActivated` ASC ";
        $result = $this->db->query($sql);

        $weekCount = 0;
        $dayGroup = array();
        while($row = $result->fetch()) {
            
            $weekCount += $row['count'];
            $group[] = array("<b>".$row['day']."</b> (".$row['dateActivated'].")",$row['count']);

            $aYears[0][0][$row['day_num']] = array( $row['dateActivated'],$row['count'], $row['day_num'] );
        }        

        //let's fill in the gaps
        
        $dowMap = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');        
        for($x=0;$x<=6;$x++) {

            if(!isset($aYears[0][0][$x])) {
                if (date('%w',time()) == $x) {
                    $dateActivated = date("Y-m-d",time());
                } else {
                    $dateActivated = date("Y-m-d",strtotime('last '.$dowMap[$x]));    
                }                
                $aYears[0][0][$x] = array( $dateActivated, 0, $x );
            }
            
        }

        //CE_Lib::debug($aYears[0][0]);

        $group[] = array("",$this->user->lang('Total Count'));
        $group[] = array("--------",$weekCount);            
        $this->reportData[] = array(
            "group"=>$group,
            "groupname"=>$this->user->lang("New Customer's This Week"),
            "label"=>array("Day",$this->user->lang('Count')),
            "groupId"=>'week',
            "isHidden"=>false); 

        return $aYears;
    }

    private function returnRows($option_id,$currentYear)
    {

        if ($option_id == 0) {
            $aYears = $this->retunRowsForWeek();
            return $aYears;            
        }

        $aYears = array();
        $sql = "SELECT COUNT(id) as usercount, MONTH(dateActivated) as monthactivated, YEAR(dateActivated) as yearactivated, DATE_FORMAT(dateActivated,'%Y-%m-1') as dateActivated FROM users ";
        $sql .= " WHERE groupid=".ROLE_CUSTOMER." GROUP BY yearactivated, monthactivated ORDER BY yearactivated DESC";

        $result = $this->db->query($sql);

        $firstTime = true;
        $sumCount =0;
        $sumAffCount = 0;
        $aYear = array();
        $newYear = 0;
        $lastMonthCount = 0;

        while($row = $result->fetch()) {

            $tMonth = date("F", mktime(0, 0, 0, $row['monthactivated']+1, 0, 0));
            $month_date = $row['dateActivated'];

            $tYear = $row['yearactivated'];

            if($firstTime) {
                $newYear = $tYear;
                $firstTime=false;
            }
            $tCount = $row['usercount'];
            $sumCount += $tCount;

            if($newYear!=$tYear) {
                $aYears[$newYear]["array"] = $aYear;
                $aYears[$newYear]["sumCount"] = $sumCount;

                $newYear = $tYear;
                unset($aYear);
                $sumCount = 0;
            }

            $aYear[$month_date] = array($tMonth,$tCount);

            $lastMonthCount = $row['usercount'];
        }

        $aYears[$newYear]["array"] = $aYear;
        $aYears[$newYear]["sumCount"] = $sumCount;

        $this->yearCount=count($aYears);

        foreach($aYears as $key => $aYear) {
            $grouphidden=true;
            $groupid="id-".$key;

            if($currentYear==$key) {
                $grouphidden=false;
            }else {
                if( mb_substr($currentYear,0,4)=="Last") {
                    $tYears = mb_substr($currentYear,4);
                    for($y=0;$y<$tYears;$y++) {
                        if ($key==date("Y")-$y) $grouphidden = false;
                    }
                }
            }

            $aGroup = array();            
            foreach($aYear["array"] as $month) {
                $aGroup[] = array($month[0],$month[1]);
            }   
            $aGroup[] = array("",$this->user->lang('Total Count'));
            $aGroup[] = array("--------",$aYear["sumCount"]);            
            $this->reportData[] = array(
                "group"=>$aGroup,
                "groupname"=>$key." ".$this->user->lang("Customer"),
                "label"=>array("Month",$this->user->lang('Count')),
                "groupId"=>$groupid,
                "isHidden"=>$grouphidden);  

        }

        

        return $aYears;
    }

    private function ReturnMonthsArray($aMonth,$currentYear,$valIndex = 1)
    {
        //Get all months values
        //Loop through 12 months using long date description and determine
        //if aMonth contains that month.  If not return 0.0

        $aReturn = array();
        for($x=1;$x<=12;$x++) {
            $tstrMonth = date("F", mktime(0, 0, 0, $x+1, 0, 0));
            $valIndex = 1;

            if(!isset($aMonth[$tstrMonth])) {
                $aReturn[] = "";
            }else if( $aMonth[$tstrMonth][$valIndex] == "na" ) {
                $aReturn[] = "";
            }else {
                $tempY = (int) $aMonth[$tstrMonth][$valIndex];
                $aReturn[] = $tempY;
            }

            if( ($currentYear==date("Y")) && (date("F")==$tstrMonth) ) {
                return $aReturn;
            }

        }
        return $aReturn;
    }

}
?>
