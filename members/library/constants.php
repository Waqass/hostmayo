<?php

// Required defines for app to work when config.php missing entries or file is missing altogether
if (!defined('INSTALLED')) {
    define('INSTALLED', false);
}
if (!defined('DEBUG')) {
    define('DEBUG', false);
}
if (!defined('REMOTELOG')) {
    define('REMOTELOG', false);
}
if (!defined('APIKEY')) {
    define('APIKEY', false);
}
if (!defined('SESSION_PATH')) {
    define('SESSION_PATH', false);
}
if (!defined('SALT')) {
    define('SALT', 'canary');
}
if (!defined('DISABLE_CACHING')) {
    define('DISABLE_CACHING', false);
}

if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', 'CLIENTEXEC');
}

if ( !defined('RUNNING_SERVICE_SCRIPT') ) {
    define('RUNNING_SERVICE_SCRIPT', false);
}

// Fix for PHP < 5.3.6
if ( !defined('DEBUG_BACKTRACE_IGNORE_ARGS') ) {
  define('DEBUG_BACKTRACE_IGNORE_ARGS', false);
}

if ( !defined('HOSTED') ) {
  define('HOSTED', false);
}

// Connection Issue (time out, etc).
define('EXCEPTION_CODE_CONNECTION_ISSUE', 10001);

// Any exception that shouldn't be e-mailed out.
define('EXCEPTION_CODE_NO_EMAIL', 20001);

// Package Status
define('PACKAGE_STATUS_PENDING', 0);
define('PACKAGE_STATUS_ACTIVE', 1);
define('PACKAGE_STATUS_SUSPENDED', 2);
define('PACKAGE_STATUS_CANCELLED', 3);
define('PACKAGE_STATUS_PENDINGCANCELLATION', 4);
define('PACKAGE_STATUS_EXPIRED', 5);


/**
 * User roles
 */
define('ROLE_GUEST', -1);
define('ROLE_ANONYMOUS', 0);
define('ROLE_CUSTOMER', 1);
define('ROLE_SUPERADMIN', 2);
/* * #@- */

define('APIUSERID',-1);

// Package Types
define('PACKAGE_TYPE_GENERAL', 0);
define('PACKAGE_TYPE_HOSTING', 1);
define('PACKAGE_TYPE_SSL', 2);
define('PACKAGE_TYPE_DOMAIN', 3);

/**
 * Custom field type, corresponding to the "type" field in the customuserfields table
 */
define('typeTEXTFIELD', 0);
define('typeADDRESS', 2);
define('typeCITY', 3);
define('typeSTATE', 4);
define('typeZIPCODE', 5);
define('typePHONENUMBER', 7);
define('typeYESNO', 1);
define('typeCOUNTRY', 6);
define('typeLANGUAGE', 8);
define('typeDROPDOWN', 9);
define('typeTEXTAREA', 10);
define('typeFIRSTNAME', 11);
define('typeLASTNAME', 12);
define('typeEMAIL', 13);
define('typeORGANIZATION', 14);
define('typeDATE', 15);
define('TYPE_ALLOW_EMAIL', 16);
define('typeRECORDSPERPAGE', 20);
define('typePAYPALSUBSCRIPTIONS', 24);
define('typePRODUCTSTATUS', 30);
define('typeDASHBOARDLASTUSEDGRAPH', 41);
define('typeVIEWMENU', 42);
define('typeLASTSENTFEEDBACKEMAIL', 43);
define('typeVATNUMBER', 47);
define('typeDASHBOARDSTATE', 48);
define('typePASSWORD', 70);

//100 > are preference customfields
define('typePREFERENCE_TICKETREPLYTOP', 100);
define('typePREFERENCE_SITEDEFAULTACTIVEUSERPANEL', 101);

//200 > are notification customfields
define('typeNOTIFICATION', 200);


/* constants for ui */
define('TYPE_UI_BUTTON', 49);
define('TYPE_UI_DNSENTRY', 50);
define('typeVATVALIDATION', 51);
define('typeHIDDEN', 52);
define('typeCHECK', 53);
define('typeNUMBER', 54);
define('typeNAMESERVER', 55);
define('typeNICKNAME',61);
define('typeSTATUS',62);
define('TYPEFULLNAME',63);
define('TYPEFULLADDRESS',64);
define('TYPEPASSWORD',65);

/* * */
/**
 * Define Credit Card Types
 *
 * @access private
 */
define('cCREDITVISA', 0);
define('cCREDITMC', 1);
define('cCREDITAMEX', 2);
define('cCREDITDISC', 3);

define('cCREDITLASER', 4);
define('cCREDITDINERS', 5);
define('cCREDITSWITCH', 6);
/* * */

/**
 *
 * @access private
 */
define('errDuplicateEmail', 0);
define('errDuplicateUserName', 1);
define('errDuplicateDomainName', 2);
define('ERRINCORRECTPASSPHRASE', 3);
define('errCCExpiresInPast', 4);
/* * */

/**
 * User Status
 */
define('USER_STATUS_PENDING', 0);
define('USER_STATUS_ACTIVE', 1);
define('USER_STATUS_INACTIVE', -1);
define('USER_STATUS_CANCELLED', -2);
define('USER_STATUS_FRAUD', -3);
/* * */

/**
 * User Chat Online Status
 */
define('CHAT_STATUS_AVAILABLE', 0);
define('CHAT_STATUS_BUSY', 1);
define('CHAT_STATUS_AWAY', 2);


/**
  * Cancellation Types
  */
define('PACKAGE_CANCELLATION_TYPE_IMMEDIATE', 1);
define('PACKAGE_CANCELLATION_TYPE_END_BILLING', 2);

/**
 * Event Types
 */
define('EVENT_TYPE_ALL',1);
define('EVENT_TYPE_INVOICES',2);
define('EVENT_TYPE_TICKETS',3);
define('EVENT_TYPE_PROFILE',4);
define('EVENT_TYPE_PACKAGE',5);
define('EVENT_TYPE_ORDER',6);

/**
 * Ticket Status
 */
define('TICKET_STATUS_UNASSIGNED', 0);
define('TICKET_STATUS_OPEN', 1);
define('TICKET_STATUS_WAITINGONTECH', 2);
define('TICKET_STATUS_WAITINGONCUSTOMER', 3);
define('TICKET_STATUS_CLOSED', -1);

/**
 * Ticket Rating
 */
define('TICKET_RATE_NO', 0);
define('TICKET_RATE_OUTSTANDING', 1);
define('TICKET_RATE_GOOD', 2);
define('TICKET_RATE_MEDIOCRE', 3);
define('TICKET_RATE_POOR', 4);

/**
 * Custom Status Types (Aliases)
 */
define('ALIAS_STATUS_PACKAGE', 1);
define('ALIAS_STATUS_TICKET', 2);
define('ALIAS_STATUS_USER', 3);

define('REGEXDOMAIN_PARSLEY', '^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,10}$');
define('REGEXSUBDOMAIN_PARSLEY', '^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)+$');

/**
 * Translations types
 */
define('ANNOUNCEMENT_TITLE', 1);
define('ANNOUNCEMENT_EXCERPT', 2);
define('ANNOUNCEMENT_CONTENT', 3);
define('PRODUCT_GROUP_NAME', 4);
define('PRODUCT_GROUP_DESCRIPTION', 5);
define('PRODUCT_NAME', 6);
define('PRODUCT_DESCRIPTION', 7);
define('PRODUCT_ASSET', 8);
define('ADDON_NAME', 9);
define('ADDON_DESCRIPTION', 10);
define('ADDON_OPTION_LABEL', 11);
define('EMAIL_NAME', 12);
define('EMAIL_SUBJECT', 13);
define('EMAIL_CONTENT', 14);
define('SETTING_VALUE', 15);
define('KNOWLEDGE_BASE_CATEGORY_NAME', 16);
define('KNOWLEDGE_BASE_CATEGORY_DESCRIPTION', 17);
define('KNOWLEDGE_BASE_ARTICLE_TITLE', 18);
define('KNOWLEDGE_BASE_ARTICLE_CONTENT', 19);

/**
 * isvat Exception class. Make sure you try catch this
 */
class isvatException extends Exception
{
    /**
     * Exception constructor. Use is to set the error message format.
     */
    public function __construct($code, $info, $url = NULL) {

        $message = "isvat error: code=$code, info=$info";

        //Include URL in message if supplied
        if(!empty($url)) $message .= " url=$url";

        parent::__construct($message, (int)$code);
    }
}

class CE_Exception extends Exception { }
class CE_ExceptionPermissionDenied extends Exception {}
class CE_PackageException extends Exception {}
