<?php
require_once 'modules/admin/models/ServerPlugin.php';
/**
* @package Plugins
*/
class PluginSkeletonserver extends ServerPlugin
{
    public $features = array(
        'packageName' => true,
        'testConnection' => true,
        'showNameservers' => true
    );
    /*****************************************************************/
    // function getVariables - required function
    /*****************************************************************/

    function getVariables(){
        /* Specification
              itemkey     - used to identify variable in your other functions
              type        - text,textarea,yesno,password
              description - description of the variable, displayed in ClientExec
              encryptable - used to indicate the variable's value must be encrypted in the database
        */


        $variables = array (
                   lang("Name") => array (
                                        "type"=>"hidden",
                                        "description"=>"Used By CE to show plugin",
                                        "value"=>"Skeleton Plugin"
                                       ),
                   lang("Description") => array (
                                        "type"=>"hidden",
                                        "description"=>lang("Description viewable by admin in server settings"),
                                        "value"=>lang("Skeleton Plugin, to be used as a base for your custom server plugins.")
                                       ),
                   lang("Username") => array (
                                        "type"=>"text",
                                        "description"=>lang("Username used to connect to server"),
                                        "value"=>""
                                       ),
                   lang("Password") => array (
                                        "type"=>"password",
                                        "description"=>lang("Password used to connect to server"),
                                        "value"=>"",
                                        "encryptable"=>true
                                       ),
                   lang("Actions") => array (
                                        "type"=>"hidden",
                                        "description"=>lang("Current actions that are active for this plugin per server"),
                                        "value"=>"Create,Delete,Suspend,UnSuspend"
                                       ),
                   lang('package_vars_values') => array(
                                        'type'        => 'hidden',
                                        'description' => lang('VM account parameters'),
                                        'value'       => array(
                                            // VIRTUAL MACHINE PROPERTIES
                                            // Template
                                            'template' => array(
                                                'type'        => 'dropdown',
                                                'multiple'    => false,
                                                'getValues'   => 'getTestValues',
                                                'label'       => lang('Template'),
                                                'description' => lang('A Template is a pre-configured OS image that you can build a Virtual Machine on.'),
                                                'value'       => '',
                                            )
                                        ))
        );
        return $variables;
    }

    public function getTestValues()
    {
        $returnValues = array();
        $returnValues[] = array("0","somename-0");
        $returnValues[] = array("1","somename-1");
        $returnValues[] = array("2","somename-2");
        return $returnValues;
    }

    function create($args){
    }

    function update($args){
    }

    function delete($args){
    }

    function suspend($args){
    }

    function unsuspend($args){
    }

    function getAvailableActions($userPackage)
    {
        $actions[] = 'Create';
        $actions[] = 'Delete';
        $actions[] = 'UnSuspend';
        $actions[] = 'Suspend';
        return $actions;
    }

    function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->create($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") .  ' has been created.';
    }

    function doSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->suspend($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") .  ' has been suspended.';
    }

    function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->unsuspend($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") .  ' has been unsuspended.';
    }

    function doDelete($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $this->delete($this->buildParams($userPackage));
        return $userPackage->getCustomField("Domain Name") . ' has been deleted.';
    }
}
?>
