<?php
require_once 'modules/admin/models/ImportPlugin.php';
require_once 'modules/domains/models/ICanImportDomains.php';
require_once 'modules/admin/models/PluginGateway.php';

class PluginDomains_registrar extends ImportPlugin
{
    var $_title;
    var $_description;

    public function __construct()
    {
        $this->_title = lang('Registrar Domains');
        $this->_description = lang("This import plugin imports domains and customers (if they don't already exist in CE) from your registrar account.");
        parent::__construct($this->user);
    }

    /**
     * Returns form for domain importing
     *
     * @return html
     */
    function getForm()
    {

        $configuration = Zend_Registry::get('configuration');
        if (!isset($configuration['modules']['domains']['installedVersion'])) {
            $this->view->noDomainsModule = true;
        } else {
            $this->view->registrars = array();

            $count = 0;
            $showWarning = true;
            $pluginGateway = new PluginGateway();
            $query = "SELECT package.pricing FROM package, promotion WHERE package.planid = promotion.id AND promotion.type=3";
            $result = $this->db->query($query);
            $addedTLD = array();
            while ($row = $result->fetch()) {

                // Unserialize the TLD's
                $tldPricing = @unserialize($row['pricing']);

                // Loop the pricing
                if (@is_array($tldPricing['pricedata'])) {
                    foreach ($tldPricing['pricedata'] AS $key) {
                        //determine if I have settings defined before hand
                        if (isset($key['registrar']) && strlen($key['registrar']) != 0) {
                            $plugin = $pluginGateway->getPluginByName("registrars", $key['registrar']);
                            //check to see if this plugin supports import functionality
                            if (($plugin instanceof ICanImportDomains)  && !@$addedTLD[$key['registrar']]) {
                                $this->view->registrars[] = $key['registrar'];
                                $count++;
                                $addedTLD[@$key['registrar']] = 1;
                            }
                        }
                    }
                }
            }

            if ($count > 0) {
                $this->view->registrarDropdown = true;
                //$this->tpl->parse('view', 'registrarDropdown');
            } else {
                $this->view->noRegistrarsText = $this->user->lang('You must first %sconfigure a registrar plugin%s and then assign TLDs to that plugin via the Domain Pricing tab for a %sdomains registration product%s.', '<a href="index.php?fuse=admin&controller=settings&view=plugins&settings=plugins_registrars&type=Registrars">', '</a>', '<a href="index.php?fuse=admin&view=products&controller=products">', '</a>');
                $this->view->noRegistrars = true;
                $showWarning = false;
            }

            if ($showWarning) {
                $this->view->hasRegistrars = true;
            }

            return $this->view->render('PluginDomainsregistrar.phtml');
        }
    }
}
