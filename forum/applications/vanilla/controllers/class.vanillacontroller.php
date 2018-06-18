<?php
/**
 * Vanilla controller
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Master application controller for Vanilla, extended by all others except Settings.
 */
class VanillaController extends Gdn_Controller {

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        // Set up head
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.popin.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('jquery.atwho.js');
        $this->addJsFile('global.js');
        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');

        // Add modules
//      $this->addModule('MeModule');
        $this->addModule('GuestModule');
        $this->addModule('SignedInModule');

        parent::initialize();
    }

    /**
     * Check a category level permission.
     *
     * @param int|array|object $category The category to check the permission for.
     * @param string|array $permission The permission(s) to check.
     * @param bool $fullMatch Whether or not several permissions should be a full match.
     */
    protected function categoryPermission($category, $permission, $fullMatch = true) {
        if (!CategoryModel::checkPermission($category, $permission, $fullMatch)) {
            $categoryID = is_numeric($category) ? $category : val('CategoryID', $category);

            $this->permission($permission, $fullMatch, 'Category', $categoryID);
        }
    }
}
