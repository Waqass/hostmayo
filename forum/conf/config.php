<?php if (!defined('APPLICATION')) exit();

// Conversations
$Configuration['Conversations']['Version'] = '2.4.201';

// Database
$Configuration['Database']['Name'] = 'vanilla';
$Configuration['Database']['Host'] = 'localhost';
$Configuration['Database']['User'] = 'root';
$Configuration['Database']['Password'] = '';

// EnabledApplications
$Configuration['EnabledApplications']['Conversations'] = '/applications/conversations';
$Configuration['EnabledApplications']['Vanilla'] = '/applications/vanilla';

// EnabledPlugins
$Configuration['EnabledPlugins']['recaptcha'] = true;
$Configuration['EnabledPlugins']['GettingStarted'] = 'GettingStarted';
$Configuration['EnabledPlugins']['stubcontent'] = true;
$Configuration['EnabledPlugins']['AllViewed'] = true;
$Configuration['EnabledPlugins']['Vanilla'] = true;
$Configuration['EnabledPlugins']['IndexPhotos'] = true;
$Configuration['EnabledPlugins']['VanillaInThisDiscussion'] = true;
$Configuration['EnabledPlugins']['StopForumSpam'] = true;
$Configuration['EnabledPlugins']['vanillicon'] = true;

// Garden
$Configuration['Garden']['Title'] = 'Vanilla';
$Configuration['Garden']['Cookie']['Salt'] = 'ywWMM82vCY7vdals';
$Configuration['Garden']['Cookie']['Domain'] = '';
$Configuration['Garden']['Registration']['ConfirmEmail'] = true;
$Configuration['Garden']['Email']['SupportName'] = 'Vanilla';
$Configuration['Garden']['Email']['Format'] = 'text';
$Configuration['Garden']['SystemUserID'] = '1';
$Configuration['Garden']['InputFormatter'] = 'Markdown';
$Configuration['Garden']['Version'] = 'Undefined';
$Configuration['Garden']['CanProcessImages'] = true;
$Configuration['Garden']['Installed'] = true;
$Configuration['Garden']['Theme'] = 'Gopi';
$Configuration['Garden']['HomepageTitle'] = 'Host Mayo Forums';
$Configuration['Garden']['Description'] = 'Host Mayo all your way.';
$Configuration['Garden']['Logo'] = '';
$Configuration['Garden']['MobileLogo'] = '';
$Configuration['Garden']['FavIcon'] = '';
$Configuration['Garden']['TouchIcon'] = '';
$Configuration['Garden']['ShareImage'] = '';
$Configuration['Garden']['MobileAddressBarColor'] = '';
$Configuration['Garden']['ThemeOptions']['Styles']['Key'] = 'Light';
$Configuration['Garden']['ThemeOptions']['Styles']['Value'] = '%s_light';

// Plugins
$Configuration['Plugins']['GettingStarted']['Dashboard'] = '1';
$Configuration['Plugins']['GettingStarted']['Plugins'] = '1';
$Configuration['Plugins']['VanillaInThisDiscussion']['Limit'] = '20';
$Configuration['Plugins']['StopForumSpam']['UserID'] = '7';
$Configuration['Plugins']['Vanillicon']['Type'] = 'v2';

// Routes
$Configuration['Routes']['YXBwbGUtdG91Y2gtaWNvbi5wbmc='] = array('utility/showtouchicon', 'Internal');
$Configuration['Routes']['DefaultController'] = array('categories', 'Internal');

// Tagging
$Configuration['Tagging']['Discussions']['Enabled'] = true;

// Vanilla
$Configuration['Vanilla']['Version'] = '2.4.201';
$Configuration['Vanilla']['Discussions']['Layout'] = 'table';
$Configuration['Vanilla']['Categories']['Layout'] = 'modern';

// Last edited by waqass (127.0.0.1)2018-04-20 19:21:01