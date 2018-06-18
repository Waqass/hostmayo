<?php

require_once 'modules/admin/models/SnapinPlugin.php';

class PluginClicksend extends SnapinPlugin
{
    public $customFieldName = 'Do Not Send Invoices To ClickSend';
    public $listeners = array(
        array("Invoice-Create", "invoiceCreateCallback")
    );

    public function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array(
                'type'        => 'hidden',
                'description' => '',
                'value'       => lang('ClickSend Invoice Sending Integration'),
            ),
            lang('Username')  => array(
                'type'        => 'text',
                'description' => lang('ClickSend Username.  Sign up for an account <a href="http://clicksend.com/?u=12366">here</a>.'),
                'value'       => '',
            ),
            lang('API Key')  => array(
                'type'        => 'text',
                'description' => lang('ClickSend API Key'),
                'value'       => '',
            ),
            lang('Use Color?')  => array(
                'type'        => 'yesno',
                'description' => lang('Use color when sending the invoice'),
                'value'       => '',
            ),
        );

        return $variables;
    }

    public function init()
    {
        $this->setDescription("This feature adds support for sending pdf invoices via snailmail.");
        $this->addMappingHook("admin_top_invoice", "invoiceTop", "Invoice Integration", "Adds a button to the top of an admin invoice to manually send the invoice.");

        // check if plugin is enabled, and create the custom field we need, if it's not already created
        $session = Zend_Registry::get('session');
        $enabled = $this->settings->get('plugin_clicksend_Enabled');
        if ($enabled) {
            if (!isset($session->clicksend_checked_exists)) {
                $session->clicksend_checked_exists = true;
                $exists  = $this->customFieldsExists();
                if (!$exists) {
                    $this->createCustomFields();
                }
            }
        }
    }

    public function invoiceTop()
    {
    }

    function callAction($callback=true)
    {
        switch($_REQUEST['pluginaction']) {
            case 'sendInvoice':
                $invoiceId = $_REQUEST['invoiceId'];
                $returnValue = $this->invoiceCreateCallback(['invoiceId' => $invoiceId, 'invoice' => new Invoice($invoiceId), 'overrideCustomField' => true]);
                if ($returnValue == true) {
                    $this->send(array(), false, $this->user->lang("Invoice Successfully Sent via ClickSend"));
                } else {
                    $this->send(array(), false, $this->user->lang("Invoice Sending Failed via ClickSend"));
                }
                break;
        }
    }

    public function invoiceCreateCallback($e)
    {
        include_once 'modules/billing/models/PDFInvoice.php';

        if (is_array($e)) {
            $event = $e;
        } else {
            $event = $e->getParams();
        }

        $invoice = $event['invoice'];
        $invoiceId = $event['invoiceId'];
        $color = ($this->settings->get('plugin_clicksend_Use Color?') == '1' ) ? '1' : '0';
        $user = new User($invoice->getCustomerId());

        // do not send a draft invoice
        if ($invoice->isDraft()) {
            return;
        }

        // do not automatically send because the custom field says so, and we didn't manually send.
        if ($user->getBoolCustomValue($this->customFieldName) && !$event['overrideCustomField']) {
            return;
        }

        $pdfInvoice = new PDFInvoice($this->user, $invoiceId);
        $pdfInvoice->save();

        try {
            $client = new \ClickSendLib\ClickSendClient($this->settings->get('plugin_clicksend_Username'), $this->settings->get('plugin_clicksend_API Key'));
            $upload = $client->getUpload();

            $response = $upload->uploadFile(__DIR__ . '/../../../uploads/cache/invoice-' . $invoiceId . '.pdf', 'post');
            if ($response->http_code == 200 ) {
                $url = $response->data->_url;
                $postLetter = $client->getPostLetter();
                $params = [
                    'file_url' => $url,
                    'template_used' => 0,
                    'colour' => $color,
                    'duplex' => 0,
                    'recipients' => [
                        [
                            'address_name' => $user->getFullName(true),
                            'address_line_1' => $user->getAddress(),
                            'address_city' => $user->getCity(),
                            'address_state' => $user->getState(),
                            'address_postal_code' => $user->getZipCode(),
                            'address_country' => $user->getCountry(),
                            'return_address_id' => 0,
                            'schedule' => time()
                        ]
                    ]
                ];
                $response = $postLetter->sendPostLetter($params);
                $pdfInvoice->delete();
                if ($response->http_code != 200) {

                    CE_Lib::log(1, "Sending invoice $invoiceId via ClickSend failed: " . $response->response_msg);
                    return false;
                }
                return true;
            }
        } catch ( Exception $e) {
            $pdfInvoice->delete();
            CE_Lib::log(1, "Sending invoice $invoiceId via ClickSend failed: " . $e->getMessage());
            return false;
        }
    }

    private function customFieldsExists()
    {
        include_once 'modules/clients/models/ObjectCustomFields.php';
        $exists = false;
        $objs = ObjectCustomFields::getCustomFieldsByType("profile");
        foreach ($objs as $key => $value) {
            if ($value['usedbyplugin'] == 'clicksend') {
                $exists = true;
            }
        }
        return $exists;
    }

    private function createCustomFields()
    {
        include 'modules/admin/models/CustomFieldGateway.php';
        $gateway = new CustomFieldGateway($this->user);

        //let's check if custom field exists to be certain we don't add it twice
        $query = "SELECT * FROM customField WHERE name = ? and groupId = 3 and usedbyplugin = 'clicksend'";
        $result = $this->db->query($query, $this->customFieldName);
        if ($result->getNumRows() > 0) {
            return;
        }

        $vars = array(
            'type' => 'profile',
            'customfieldname' => $this->customFieldName,
        );
        $newFieldId = $gateway->insertnewcustomfield($vars);
        $gateway->save_custom_field(array(
            'type' => 'profile',
            'isAdmin' => true,
            'customfield_name' => $this->customFieldName,
            'customfield_typevalue' => 1,
            'customfield_id' => $newFieldId,
            'customfieldoptions' => '',
            'customfieldrequired' => 0,
            'customfieldsignup' => 0,
            'customfieldshowingridadmin' => 0,
            'customfieldcustomerprofile' => 1,
            'customfieldadminprofile' => 0,
            'customfielddesc' => '',
            'usedbyplugin' => 'clicksend'
        ));

        $query = 'UPDATE customField SET isChangeable = 0 WHERE id = ?';
        $this->db->query($query, $newFieldId);
    }
}