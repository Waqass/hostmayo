<?php
require_once 'library/CE/NE_MenuItem.php';

/**
* Parent class for menu hooks (to generate the menu dropdowns at the top of the screen)
*
* @package ClientExec_API
* @abstract
*/
class NE_MenuHook
{

    /**
    * Description that will appear under the menu item, when viewed from the customer's section
    *
    * @access public
    */
    var $menuDescription;
    var $direction = "right";
    var $width = "";
    var $offset = "";
    var $snapin_key ="snapin_key";

    /**
    * List of menuitems for this menu
    *
    * @access protected
    * @var NE_MenuItem
    */
    var $menuItems = array();

    function addItem($menuItem){
        //$this->col_key = $col_key;
        $this->menuItems[] = $menuItem;
    }

    function getItems($user, $checkUserPermission=false){
        if($checkUserPermission){
            $returnArray = array();
            //go through all the items and check permission before we return this
            foreach($this->menuItems as $key=>$menuItem){
                $haspermission = true;
                foreach ($menuItem->getPermissions() as $permission) {
                     if (!$user->hasPermission($permission)){
                        $haspermission = false;
                     }
                }

                //if user has permission the add menu to the returned collection
                if($haspermission)
                {
                    $returnArray[] = $menuItem;
                }
            }
            return $returnArray;
        }
        return $this->menuItems;
    }

    function getWidth()
    {
        return $this->width;
    }

    /**
     * returns the key we want snapins to be grouped in
     * @return [type] [description]
     */
    function getSnapinKey()
    {
        return $this->snapin_key;
    }

    function getDirection()
    {
        return $this->direction;
    }

    function getOffset() {
        return $this->offset;
    }

    function getModulesViews()
    {
        return $this->modulesViews;
    }

    function getMenuDescription()
    {
        return $this->menuDescription;
    }

}

?>
