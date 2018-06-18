<?php
/**
 * Customer Languages Report
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan D. Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 *
 *************************************************
 *   1.0 Initial Report Released.  - Juan D. Bolivar
 ************************************************
 */

/**
 * Customer_Languages Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan D. Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */
class Customer_Languages extends Report
{
    private $lang;

    protected $featureSet = 'accounts';

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Customer Languages');
        parent::__construct($user,$customer);
    }

    /**
     * Report Process Method
     *
     * @return null - direct output
     */
    function process()
    {
        $this->SetDescription($this->user->lang('Displays total users using each language.'));

        $graphdata = @$_GET['graphdata'];

        //SQL to generate the the result set of the report
        $sql = "SELECT ucuf.`value`, "
              ."COUNT(ucuf.`value`) AS total "
              ."FROM `user_customuserfields` ucuf "
              ."WHERE ucuf.`customid` = (SELECT cuf.`id` "
              ."FROM `customuserfields` cuf "
              ."WHERE cuf.`name` = 'Language') "
              ."GROUP BY ucuf.`value` "
              ."ORDER BY COUNT(ucuf.`value`) DESC ";

        $result = $this->db->query($sql);

        $totalElements = 0;

        while(list($element, $total) = $result->fetch()) {
            $aGroup[] = array($this->user->lang($element), $total);
            $totalElements += $total;
        }

        if (isset($aGroup)) {
            $this->reportData[] = array("group"=>$aGroup,
                "groupname"=> '',
                "label"=>array($this->user->lang('Language'),$this->user->lang('Customers')),
                "groupId"=>"",
                "isHidden"=>false);
            unset($aGroup);
        }
    }

}
?>
