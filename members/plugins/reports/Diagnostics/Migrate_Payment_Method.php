<?php

/**
 * Migrate_Payment_Method Report Class
 *
 * @category Report
 * @package  ClientExec
 * @author   Juan Bolivar <juan@clientexec.com>
 * @license  ClientExec License
 * @version  1.0
 * @link     http://www.clientexec.com
 */
class Migrate_Payment_Method extends Report
{
    protected $featureSet = 'billing';

    private $lang;

    var $showOptionsForOverdueTransactions = true;
    var $lastPaidInvoiceInfo = array();

    function __construct($user=null,$customer=null)
    {
        $this->lang = lang('Migrate Payment Method');
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
        $this->SetDescription($this->user->lang('A report to migrate a payment method to a different one.'));

        @set_time_limit(0);

        echo "<div style='margin:20px;'><h3>" . $this->user->lang('Migrate CC Accounts to Authorize.net CIM') . "</h3>";
        echo "<div>".$this->user->lang("The credit card number and expiration month and year of the customers using the selected payment methods will be migrated to an account in Authorize.net CIM")
            ."</div><br/>";
        echo $this->user->lang('Passphrase').":&nbsp;&nbsp;<input type='password' name='passphraseAuthnetCIM' id='passphraseAuthnetCIM' style='width:100px;' >&nbsp&nbsp&nbsp&nbsp";

        $currentPaymentMethod =  "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$this->user->lang('Current Payment Methods').":&nbsp;&nbsp;<select multiple='multple' name='paymentMethodAuthnetCIM[]' id='paymentMethodAuthnetCIM[]'style='display:none'>
                    <option selected='selected' value='all'>All</option>";
        $ccPlugins = $this->get_cc_plugins();
        foreach($ccPlugins as $ccPlugin){
            $currentPaymentMethod .= "<option value='".$ccPlugin['paymentTypeOptionValue']."'>".$ccPlugin['paymentTypeOptionLabel']."</option>";
        }
        $currentPaymentMethod .= "</select>&nbsp&nbsp&nbsp&nbsp";
        echo $currentPaymentMethod;

        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button class='btn' type='button' data-loading-text='Loading...' onclick='MigrateToAuthnetCIM(document.getElementById(\"passphraseAuthnetCIM\").value, document.getElementById(\"paymentMethodAuthnetCIM[]\").selectedOptions);'>"
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
                                location.href='index.php?fuse=reports&view=viewreport&controller=index&report=Migrate+Payment+Method&type=Diagnostics&migrate=1&data='+btoa(btoa(passphrase)+'_'+btoa(optionsSelected)+'_'+btoa('authnetcim'));
                            }
                        }
                    }else{
                        RichHTML.error(lang('You do not have permission to perform this action.'));
                    }
                }
            </script>";
        echo "\n\n";


        echo "<div style='margin:20px;'><h3>" . $this->user->lang('Migrate CC Accounts to Stripe Checkout') . "</h3>";
        echo "<div>".$this->user->lang("The credit card number and expiration month and year of the customers using the selected payment methods will be migrated to an account in Stripe Checkout")
            ."</div><br/>";
        echo $this->user->lang('Passphrase').":&nbsp;&nbsp;<input type='password' name='passphraseStripeCheckout' id='passphraseStripeCheckout' style='width:100px;' >&nbsp&nbsp&nbsp&nbsp";

        $currentPaymentMethod =  "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$this->user->lang('Current Payment Methods').":&nbsp;&nbsp;<select multiple='multple' name='paymentMethodStripeCheckout[]' id='paymentMethodStripeCheckout[]'style='display:none'>
                    <option selected='selected' value='all'>All</option>";
        $ccPlugins = $this->get_cc_plugins();
        foreach($ccPlugins as $ccPlugin){
            $currentPaymentMethod .= "<option value='".$ccPlugin['paymentTypeOptionValue']."'>".$ccPlugin['paymentTypeOptionLabel']."</option>";
        }
        $currentPaymentMethod .= "</select>&nbsp&nbsp&nbsp&nbsp";
        echo $currentPaymentMethod;

        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button class='btn' type='button' data-loading-text='Loading...' onclick='MigrateToStripeCheckout(document.getElementById(\"passphraseStripeCheckout\").value, document.getElementById(\"paymentMethodStripeCheckout[]\").selectedOptions);'>"
            .$this->user->lang("Migrate to Stripe Checkout") . "</button>&nbsp;&nbsp;&nbsp;";
        echo "<br/><br/>";
        echo "</div>";
        echo "\n\n<script type='text/javascript'>
                function MigrateToStripeCheckout(passphrase, paymentMethod){
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
                                location.href='index.php?fuse=reports&view=viewreport&controller=index&report=Migrate+Payment+Method&type=Diagnostics&migrate=1&data='+btoa(btoa(passphrase)+'_'+btoa(optionsSelected)+'_'+btoa('stripecheckout'));
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

            $oldPaymentMethod = base64_decode($dataArray[1]);
            $newPaymentMethod = base64_decode($dataArray[2]);
            $this->migrateToPaymentMethod($passphrase, $oldPaymentMethod, $newPaymentMethod);
        }
    }

    function migrateToPaymentMethod($passphrase, $oldPaymentMethod, $newPaymentMethod)
    {
        $newPaymentMethodNames = array(
            'authnetcim'     => 'Authorize.net CIM',
            'stripecheckout' => 'Stripe Checkout'
        );
        $successCount = 0;
        $errorsArray = array();
        if($oldPaymentMethod != ''){
            $oldPaymentMethodArray = explode(',', $oldPaymentMethod);
            if(in_array("'all'", $oldPaymentMethodArray)){
                $oldPaymentMethodArray = array();
                $ccPlugins = $this->get_cc_plugins();
                foreach($ccPlugins as $ccPlugin){
                    $oldPaymentMethodArray[] = $ccPlugin['paymentTypeOptionValue'];
                }
                $where = " WHERE paymenttype IN ('".implode("','", $oldPaymentMethodArray)."') ";
            }else{
                $where = " WHERE paymenttype IN (".$oldPaymentMethod.") ";
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
                'cc_exp_year'      => $cc_exp_year,
                'validate'         => false,
            );

            require_once 'library/CE/NE_PluginCollection.php';
            $pluginCollection = new NE_PluginCollection('gateways', $this->user);

            $statusAliasGateway = StatusAliasGateway::getInstance($this->user);
            if(in_array($tempUser->getStatus(), $statusAliasGateway->getUserStatusIdsFor(array(USER_STATUS_INACTIVE, USER_STATUS_CANCELLED, USER_STATUS_FRAUD)))){
                $customerProfile = array('error' => false);
            }else{
                $customerProfile = $pluginCollection->callFunction($newPaymentMethod, 'createFullCustomerProfile', $params);
            }

            if($customerProfile['error']){
                $errorsArray[] = $this->user->lang('Customer #%s had an issue creating the %s account. Details: %s', $tempUser->getId(), $newPaymentMethodNames[$newPaymentMethod], $customerProfile['detail']).'</br>';
                continue;
            }else{
                $tempUser->setPaymentType($newPaymentMethod);
                $tempUser->setAutoPayment(1);
                $tempUser->clearCreditCardInfo();
                $tempUser->save();

                $eventLog = Client_EventLog::newInstance(false, $tempUser->getId(), $tempUser->getId());
                $eventLog->setSubject($tempUser->getId());
                $eventLog->setAction(CLIENT_EVENTLOG_CHANGEDPAYMENTTYPE);
                $eventLog->setParams($newPaymentMethod);
                $eventLog->save();

                $successCount ++;
            }
        }

        echo "<b style='margin:20px; color:green'>".$this->user->lang("Successfully migrated %s account(s) to %s.", $successCount, $newPaymentMethodNames[$newPaymentMethod])."</b></br></br></br></br>";
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
