<?php
/**
 * Latest KB Articles Added Report
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
 * Latest_Articles_Added Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.1
 * @link     http://www.clientexec.com
 */
class Latest_Articles_Added extends Report
{
    protected $featureSet = 'support';

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process()
    {

        // Set the report information
        $this->SetDescription($this->user->lang('Displays a list of the latest knowledgebase articles that have been added to ClientExec.'));

        $aGroup = array();
        $reportSQL = "SELECT id, title, UNIX_TIMESTAMP(created), rating, totalvisitors FROM  kb_articles ORDER BY created DESC LIMIT 0,20";

        $result = $this->db->query($reportSQL);
        while (list($id, $title, $created, $rating, $visitors) = $result->fetch()) {
            //$id of article for future links
            $aGroup[] = array("<span style='color:#888;'>#".$id."</span> ".$title,
                    $this->getDateDifference(CE_Lib::date_diff_hrs(date('Y-m-d H:i:s',$created),date('Y-m-d H:i:s')),$this->user),
                    '<center>'.$rating.'</center>',
                    '<center>'.$visitors.'</center>',
            );
        }
        
        $this->reportData[] = array(
            "group" => $aGroup,
            "groupname" => $this->user->lang('Latest Articles Added'),
            "label" => array($this->user->lang('Article Title'),
                $this->user->lang('Date Created'),
                '<center>' . $this->user->lang('Article Rating') . '</center>',
                '<center>' . $this->user->lang('Total Visitors') . '</center>'
            ),
            "groupId" => "",
            "isHidden" => false);
        
    }

    function getDateDifference($datearray, $user, $returnTimestamp=false)
    {
        $returnString = "";
        $tWeeks = 0;
        $tDays = 0;
        $tHours = 0;
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
                $returnString .= $tWeeks. ' '.$user->lang('weeks') . ' ';
            } else {
                $returnString .= $tWeeks.' '.$user->lang('week') . ' ';
            }
        }

        if($tDays > 0) {
            if($tDays > 1) {
                $returnString .= $tDays.' '.$user->lang('days') . ' ';
            } else {
                $returnString .= $tDays.' '.$user->lang('day') . ' ';
            }
        }

        if ($tHours > 0) {
            if ($tHours > 1) {
                $returnString .= $tHours.' '.$user->lang('hrs').' ';
            } else {
                $returnString .= ' 1 '.$user->lang('hr').' ';
            }
            if ($datearray['m']==1) {
                $returnString .= ' '.abs($datearray['m']).' min ';
            } else {
                $returnString .= ' '.abs($datearray['m']).' mins ';
            }
        } else {
            if ($datearray['m']==1) {
                $returnString .= abs($datearray['m']).' min ';
            } else {
                $returnString .= abs($datearray['m']).' mins ';
            }
        }

        return $returnString;
    }
}
?>
