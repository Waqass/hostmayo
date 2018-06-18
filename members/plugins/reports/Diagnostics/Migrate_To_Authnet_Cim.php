<?php

/**
 * Migrate_To_Authnet_Cim Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */
class Migrate_To_Authnet_Cim extends Report
{
    protected $featureSet = 'billing';

    private $lang;

    var $showOptionsForOverdueTransactions = true;
    var $lastPaidInvoiceInfo = array();

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Migrate CC Accounts to Authorize.net CIM');
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
        $this->SetDescription($this->user->lang('A report to migrate credit card accounts to Authorize.net CIM.'));

        @set_time_limit(0);

        echo "<div style='margin:20px;'><h3>" . $this->user->lang('Migrate CC Accounts to Authorize.net CIM') . "</h3>";
        echo "<div>".$this->user->lang("The credit card number and expiration month and year of the customers using the selected payment methods will be migrated to an account in Authorize.net CIM")
            ."</div><br/>";
        echo $this->user->lang('Passphrase').":&nbsp;&nbsp;<input type='password' name='passphrase' id='passphrase' style='width:100px;' >&nbsp&nbsp&nbsp&nbsp";

        $currentPaymentMethod =  "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$this->user->lang('Current Payment Methods').":&nbsp;&nbsp;<select multiple='multple' name='paymentMethod[]' id='paymentMethod[]'style='display:none'>
                    <option selected='selected' value='all'>All</option>";
        $ccPlugins = $this->get_cc_plugins();
        foreach($ccPlugins as $ccPlugin){
            $currentPaymentMethod .= "<option value='".$ccPlugin['paymentTypeOptionValue']."'>".$ccPlugin['paymentTypeOptionLabel']."</option>";
        }
        $currentPaymentMethod .= "</select>&nbsp&nbsp&nbsp&nbsp";
        echo $currentPaymentMethod;

        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button class='btn' type='button' data-loading-text='Loading...' onclick='MigrateToAuthnetCIM(document.getElementById(\"passphrase\").value, document.getElementById(\"paymentMethod[]\").selectedOptions);'>"
            .$this->user->lang("Migrate to Authnet CIM") . "</button>&nbsp;&nbsp;&nbsp;";
        echo "<br/><br/>";
        echo "</div>";
        echo "\n\n<script type='text/javascript'>
                function MigrateToAuthnetCIM(passphrase, paymentMethod){
                    if(".(($this->user->hasPermission( 'billing_create' ))? "true" : "false")."){
                        if(passphrase == ''){
                            RichHTML.error(lang('Passphrase is required.'));
                        }else{
                            var countOptionsSelected = paymentMethod.length;
                            if(countOptionsSelected == 0){
                                RichHTML.error(lang('You must select at least one current payment method.'));
                            }else{
                                var optionsSelected = '';
                                var firstOption = true;
                                for(x = 0; x < countOptionsSelected; x ++){
                                    if(firstOption){
                                        firstOption = false;
                                    }else{
                                        optionsSelected += ',';
                                    }
                                    optionsSelected += '\''+paymentMethod.item(x).value+'\'';
                                }
                                location.href='index.php?fuse=reports&view=viewreport&controller=index&report=Migrate+To+Authnet+Cim&type=Diagnostics&migrate=1&data='+btoa(btoa(passphrase)+'_'+btoa(optionsSelected));
                            }
                        }
                    }else{
                        RichHTML.error(lang('You do not have permission to perform this action.'));
                    }
                }
            </script>";
        echo "\n\n";

        if(isset($_GET['migrate']) && $_GET['data'] && $this->user->hasPermission( 'billing_create' )){
            $data = base64_decode($_GET['data']);
            $dataArray = explode('_', $data);
            $passphrase = base64_decode($dataArray[0]);

            if(Clientexec::getPassPhraseHash($this->settings)!= md5($passphrase)){
                echo "<b style='margin:20px; color:red'>".$this->user->lang("Passphrase do not match.")."</b>";
                return;
            }

            $paymentMethod = base64_decode($dataArray[1]);
            $this->migrateToAuthnetCIM($passphrase, $paymentMethod);
        }
    }

    function migrateToAuthnetCIM($passphrase, $paymentMethod)
    {
        $successCount = 0;
        $errorsArray = array();
        if($paymentMethod != ''){
            $paymentMethodArray = explode(',', $paymentMethod);
            if(in_array("'all'", $paymentMethodArray)){
                $paymentMethodArray = array();
                $ccPlugins = $this->get_cc_plugins();
                foreach($ccPlugins as $ccPlugin){
                    $paymentMethodArray[] = $ccPlugin['paymentTypeOptionValue'];
                }
                $where = " WHERE paymenttype IN ('".implode("','", $paymentMethodArray)."') ";
            }else{
                $where = " WHERE paymenttype IN (".$paymentMethod.") ";
            }
        }

        $sql = "SELECT `id` FROM `users` ".$where;
        $result = $this->db->query($sql);
        while($row = $result->fetch()) 
        {
            $tempUser = new User($row['id']);
            if($tempUser->isPassphrased()){
                $cc_num = $tempUser->getCreditCardInfo($passphrase);
            }else{
                $cc_num = $tempUser->getCreditCardInfo();
            }
            if(trim($cc_num) == ""){
                //The credit card has been cleared
                echo 'Customer #'.$row['id'].' does not have a credit card number.</br>';
                continue;
            }
            $cc_exp_month = sprintf("%02d", $tempUser->getCCMonth());
            $cc_exp_year = $tempUser->getCCYEAR();

            $params = array(
                'CustomerID'       => $tempUser->getId(),
                'userID'           => "CE" . $tempUser->getId(),
                'userEmail'        => $tempUser->getEmail(),
                'userFirstName'    => $tempUser->getFirstName(),
                'userLastName'     => $tempUser->getLastName(),
                'userOrganization' => $tempUser->getOrganization(),
                'userAddress'      => $tempUser->getAddress(),
                'userCity'         => $tempUser->getCity(),
                'userState'        => $tempUser->getState(),
                'userZipcode'      => $tempUser->getZipCode(),
                'userCountry'      => $tempUser->getCountry(),
                'userPhone'        => $tempUser->getPhone(),
                'userCCNumber'     => $cc_num,
                'cc_exp_month'     => $cc_exp_month,
                'cc_exp_year'      => $cc_exp_year
            );

            require_once 'library/CE/NE_PluginCollection.php';
            $pluginCollection = new NE_PluginCollection('gateways', $this->user);
            $customerProfile = $pluginCollection->callFunction('authnetcim', 'createFullCustomerProfile', $params);;
            if($customerProfile['error']){
                $errorsArray[] = 'Customer #'.$tempUser->getId().' had an issue creating the Authorize.net CIM acount. Details: '.$customerProfile['detail'].'</br>';
                continue;
            }else{
                $tempUser->setPaymentType('authnetcim');
                $tempUser->setAutoPayment(1);
                $tempUser->clearCreditCardInfo();
                $tempUser->save();

                $eventLog = Client_EventLog::newInstance(false, $tempUser->getId(), $tempUser->getId());
                $eventLog->setSubject($tempUser->getId());
                $eventLog->setAction(CLIENT_EVENTLOG_CHANGEDPAYMENTTYPE);
                $eventLog->setParams('authnetcim');
                $eventLog->save();

                $successCount ++;
            }
        }

        echo "<b style='margin:20px; color:green'>".$this->user->lang("Successfully migrated %s account(s).", $successCount)."</b></br></br></br></br>";
        if(count($errorsArray) > 0){
            echo "<b style='margin:20px; color:red'>".$this->user->lang("ERRORS").":"."</b><ul>";
            foreach($errorsArray as $error){
                echo "<li style='margin:20px;'>".$error."</li>";
            }
            echo "</ul>";
        }
    }

    function get_cc_plugins()
    {
        include_once ("library/CE/NE_PluginCollection.php");
        $plugins = new NE_PluginCollection("gateways", $this->user);

        $pluginsArray = array();
        while ($tplugin = $plugins->getNext()) {
            $tvars = $tplugin->getVariables();
            $tvalue = $this->user->lang($tvars['Plugin Name']['value']);

            // Only show plugins that accept credit card
            if (!is_null($this->settings->get("plugin_" . $tplugin->getInternalName() . "_Accept CC Number")) && $this->settings->get("plugin_" . $tplugin->getInternalName() . "_Accept CC Number") == 1) {
                $pluginsArray[$tvalue] = $tplugin;
            }
        }
        uksort($pluginsArray, "strnatcasecmp");
        
        foreach ($pluginsArray as $value => $plugin) {
            $return_plugins[] = array(
                'paymentTypeOptionValue' => $plugin->getInternalName(),
                'paymentTypeOptionLabel' => $value . '&nbsp;&nbsp;&nbsp;'
            );
        }
        return $return_plugins;
    }
}