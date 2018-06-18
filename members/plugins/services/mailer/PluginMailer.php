<?php

require_once 'library/CE/NE_MailGateway.php';
require_once 'modules/admin/models/ServicePlugin.php';
/**
* @package Plugins
*/
class PluginMailer extends ServicePlugin
{
    protected $featureSet = 'restricted';
    public $hasPendingItems = true;

    function getVariables()
    {
        $variables = array(
            lang('Plugin Name')   => array(
                'type'          => 'hidden',
                'description'   => '',
                'value'         => lang('Bulk Mailer'),
            ),
            lang('Enabled')       => array(
                'type'          => 'yesno',
                'description'   => lang('When enabled, emails won\'t be sent immediately, but be queued instead and sent when this service is triggered. Use it to improve performance if you have to send lots of emails at once.'),
                'value'         => '0',
            ),
            lang('Limit')       => array(
                'type'          => 'text',
                'description'   => lang('Sets the number of emails that will be sent each time this service is triggered.  Leave this blank to not have a limit.'),
                'value'         => '',
            ),
            lang('Run schedule - Minute')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '30',
                'helpid'        => '8',
            ),
            lang('Run schedule - Hour')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
            ),
            lang('Run schedule - Day')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
            ),
            lang('Run schedule - Month')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number, range, list or steps'),
                'value'         => '*',
            ),
            lang('Run schedule - Day of the week')  => array(
                'type'          => 'text',
                'description'   => lang('Enter number in range 0-6 (0 is Sunday) or a 3 letter shortcut (e.g. sun)'),
                'value'         => '*',
            ),
        );

        return $variables;
    }

    function execute()
    {
        include_once 'modules/billing/models/Invoice.php';
        $messages = array();
        $numMailsSent = 0;

        //Clean orphaned data
        $queryClean = "DELETE FROM `email_queue_addressees` WHERE `email_queue_id` NOT IN (SELECT `id` FROM `email_queue`) ";
        $resultClean = $this->db->query($queryClean);
        //Clean orphaned data

        $query = "SELECT `id`, `subject`, `from`, `from_name`, `bcc`, `priority`, `confirmreceipt`, `emailtype`, `body`, `contenttype`, `dfilename`, `attachment`, `cc` FROM `email_queue` ORDER BY `id` ASC ";
        $limit = $this->settings->get('plugin_mailer_Limit');
        if ( $limit != '0' && $limit != '' ) {
            $limit = $this->db->escape($limit);
            $query .= " LIMIT $limit";
        }

        $result = $this->db->query($query);
        while ($row = $result->fetch()) {
            // Extract multiple attachments
            if (strpos($row['dfilename'], "||") !== false) {
                $row['dfilename'] = explode("||", $row['dfilename']);

                // this is needed for multiple attachments per email.
                $row['attachment'] = $this->_string_base64_decode($row['attachment']);
                $row['attachment'] = unserialize($row['attachment']);
            }

            if (!is_array($row['dfilename']) && $row['emailtype'] == 'invoice') {
                //---------------------------------------------------------------------------------
                //Added the code snippet to fix the issue reported in 59863
                //Issue - if the service "bulk mailer" is enabled, the pdf attached to the invoice is getting corrupted
                //because UTF-8 charset do not taking the pdf content.
                $invoiceId = strrchr($row['dfilename'], "_");
                $invoiceId = mb_substr($invoiceId, 1, strlen($invoiceId)-5);
                $tempInvoice = new Invoice($invoiceId);
                $tUser = new User($tempInvoice->getUserID());

                // change language to the customer's
                $this->user->getLanguage();    // just to get the customFields filled

                if ($this->settings->get("Attach PDF When Mailing Invoice") == 1) {
                    include_once 'modules/billing/models/PDFInvoice.php';
                    $pdf = new PDFInvoice($tUser, $invoiceId);
                    $row['attachment'] = $pdf->get();

                    //$pdfname = $tUser->lang("invoice")."_{$invoiceId}.pdf";
                } else {
                    $row['attachment'] = "";
                    //$pdfname = "";
                    //let's add instructions
                }
                //------------------------------------------------------------------------------------
            }

            $row['body'] = $this->_string_base64_decode($row['body']);
            $body = @unserialize($row['body']);
            if (!$body) {
                $body = $row['body'];
            }

            $mailGateway = new NE_MailGateway();

            // better to call MailGateway::sendMailMessage() for each userid instead of calling it once with
            // the array of ids, so that we can erase each entry in email_queue_addressees right after is sent.
            // Before, we used to send all in an array, and if the process failed at some point, the addressees that
            // did get processed weren't being erased from the queue.
            $query2 = "SELECT `userid` FROM `email_queue_addressees` WHERE `email_queue_id` = ? ";
            $result2 = $this->db->query($query2, $row['id']);
            $failedAddressees = array();
            while ($row2 = $result2->fetch()) {
                if ($this->_isSerialization($row['bcc'])) {
                    $row['bcc'] = unserialize($row['bcc']);
                }
                if ($this->_isSerialization($row['cc'])) {
                    $row['cc'] = unserialize($row['cc']);
                }
                try {
                    $mailSend = $mailGateway->sendMailMessage(
                        $body,
                        $row['from'],
                        $row['from_name'],
                        $row2['userid'],
                        $row['bcc'],
                        $row['subject'],
                        $row['priority'],
                        $row['confirmreceipt'],
                        $row['emailtype'],
                        $row['attachment'],
                        $row['dfilename'],
                        $row['contenttype'],
                        $row['cc']
                    );
                    $numMailsSent++;
                } catch(Exception $e) {
                    $failedAddressees[$row['id']]['subject'] = $row['subject'];
                    $failedAddressees[$row['id']]['users'][] = $row2['userid'];
                }
                $query3 = "DELETE FROM `email_queue_addressees` WHERE `email_queue_id` = ? AND `userid` = ? ";
                $this->db->query($query3, $row['id'], $row2['userid']);
            }

            if (count($failedAddressees)) {
                foreach($failedAddressees as $emailid => $data) {
                    $users = implode(', ', $data['users']);
                    $subject = $data['subject'];
                    CE_Lib::log(1, "Error trying to E-mail queued message $emailid with subject '$subject' to user(s) $users");
                    $messages[] = new CE_Error($this->user->lang('Error trying to E-mail queued message %s with subject %s to user(s) %s', $emailid, $subject, $users));
                }

            } else {
                $query4 = "DELETE FROM `email_queue` WHERE `id` = ? ";
                $this->db->query($query4, $row['id']);
            }
        }

        array_unshift($messages, $this->user->lang('%s message(s) sent', $numMailsSent));

        return $messages;
    }

    function pendingItems()
    {
        $returnArray = array();
        $returnArray['data'] = array();
        $query = "SELECT id, subject, bcc, body, emailtype, attachment, cc FROM email_queue";
        $result = $this->db->query($query);
        $queueEmpty = true;
        $mailGateway = new NE_MailGateway();
        $limit = 100;
        while ($row = $result->fetch()) {
            $emails = array();
            $query2 = "SELECT userid FROM email_queue_addressees WHERE email_queue_id=?";
            $result2 = $this->db->query($query2, $row['id']);
            $i = 0;
            while ($row2 = $result2->fetch()) {
                if ($i++ > $limit) {
                    $emails[] = "<br><b>".$this->user->lang('...with a total of %s E-mails', $result2->getNumRows())."</b>";
                    break;
                }
                $emails = array_merge($emails, $mailGateway->getEmailsForUserID($row2['userid'], $row['emailtype']));
            }
            $to = implode(', ', $emails);

            $row['body'] = $this->_string_base64_decode($row['body']);
            $bodyArr = @unserialize($row['body']);
            if ($bodyArr) {
                if ($bodyArr['HTML']) {
                    $body = strip_tags(NE_MailGateway::br2nl($bodyArr['HTML']));
                } else {
                    $body = $bodyArr['plainText'];
                }
            } else {
                $body = $row['body'];
            }

            $body = trim(mb_substr($body, 0, 200));
            if (strlen($row['body']) > 200) {
                $body .= ' ...';
            }

            $bcc = $this->_isSerialization($row['bcc'])? implode('<br />', unserialize($row['bcc'])): $row['bcc'];
            $cc = $this->_isSerialization($row['cc'])? implode('<br />', unserialize($row['cc'])): $row['cc'];
            $tmpInfo = array();
            $tmpInfo['subject'] = $row['subject'];
            $tmpInfo['to'] = $to;
            $tmpInfo['bcc'] = $bcc;
            $tmpInfo['cc'] = $cc;
            $tmpInfo['body'] = $body;
            $tmpInfo['attachment'] = $row['attachment']? $this->user->lang('Yes') : $this->user->lang('No');
            $returnArray['data'][] = $tmpInfo;
        }
        $returnArray['totalcount'] = count($returnArray['data']);
        $returnArray['headers'] = array (
            $this->user->lang('Subject'),
            $this->user->lang('To'),
            $this->user->lang('BCC'),
            $this->user->lang('CC'),
            $this->user->lang('Body'),
            $this->user->lang('Attachment')
        );
        return $returnArray;
    }

    function output()
    {
    }

    function dashboard()
    {
        $query = "SELECT COUNT(id) AS emails FROM email_queue";
        $result = $this->db->query($query);
        $row = $result->fetch();
        if (!$row) {
            $row['emails'] = 0;
        }

        return $this->user->lang('Current E-mails in queue: %d', $row['emails']);
    }

    function _isSerialization($string)
    {
        return mb_substr($string, 0, 2) == 'a:';
    }

    function _string_base64_decode($string)
    {
        if (mb_substr($string, 0, 3) == 'b||') {
            // Base64 decode the string
            $temp_string = mb_substr($string, 3);
            $temp_string = base64_decode($temp_string, true);

            if ($temp_string !== false) {
                $string = $temp_string;
            }
        }
        return $string;
    }
}