<?php

/**
 * Most Viewed KB Articles Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.1
 * @link     http://www.clientexec.com
 *
 * ************************************************
 *   1.1 Updated the report to use Pear Commenting & the new title handing to make app reports consistent.
 * ***********************************************
 */

/**
 * Most_Viewed_Articles Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Jason Yates <jason@clientexec.com>
 * @license  ClientExec License
 * @version  1.1
 * @link     http://www.clientexec.com
 */
class Most_Viewed_Articles extends Report {

    protected $featureSet = 'support';

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process() {

        // Set the report information
        $this->SetDescription($this->user->lang('This report displays the Most Viewed Articles on the knowledgebase'));

        $aGroup = array();
        $reportSQL = "SELECT id, title, created, rating, totalvisitors FROM  kb_articles ORDER BY totalvisitors DESC LIMIT 0,20";

        $result = $this->db->query($reportSQL);
        while (list($id, $title, $created, $rating, $visitors) = $result->fetch()) {
            //$id of article for future links
            $aGroup[] = array("<span style='color:#888;'>#".$id."</span> ".$title,
                CE_Lib::db_to_form($created, $this->settings->get('Date Format'), '-', true),
                '<center>' . $rating . '</center>',
                '<center>' . $visitors . '</center>',
            );
        }

        $this->reportData[] = array(
            "group" => $aGroup,
            "groupname" => $this->user->lang('Most Viewed Articles'),
            "label" => array($this->user->lang('Article Title'),
                $this->user->lang('Date Created'),
                '<center>' . $this->user->lang('Article Rating') . '</center>',
                '<center>' . $this->user->lang('Total Visitors') . '</center>'
            ),
            "groupId" => "",
            "isHidden" => false);
    }

}
?>
