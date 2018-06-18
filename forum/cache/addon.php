<?php return array (
  'conversations' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'description' => 'An improvement upon existing private messaging tools, Conversations allows multiple users to take part in private conversations.',
      'version' => '2.4.201',
      'setupController' => 'setup',
      'url' => 'https://open.vanillaforums.com',
      'license' => 'GNU GPL v2',
      'icon' => 'conversations.png',
      'key' => 'conversations',
      'type' => 'addon',
      'priority' => 10,
      'name' => 'Conversations',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Vanilla Staff',
          'email' => 'support@vanillaforums.com',
          'homepage' => 'https://open.vanillaforums.com',
        ),
      ),
      'oldType' => 'application',
      'keyRaw' => 'Conversations',
      'Issues' => 
      array (
      ),
    ),
     'classes' => 
    array (
      'conversationscontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ConversationsController',
          'path' => '/controllers/class.conversationscontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ConversationsController',
          'path' => '/Controllers/class.conversationscontroller.php',
        ),
      ),
      'messagescontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'MessagesController',
          'path' => '/controllers/class.messagescontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'MessagesController',
          'path' => '/Controllers/class.messagescontroller.php',
        ),
      ),
      'conversationsapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ConversationsApiController',
          'path' => '/controllers/api/ConversationsApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ConversationsApiController',
          'path' => '/Controllers/api/ConversationsApiController.php',
        ),
      ),
      'messagesapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'MessagesApiController',
          'path' => '/controllers/api/MessagesApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'MessagesApiController',
          'path' => '/Controllers/api/MessagesApiController.php',
        ),
      ),
      'conversationmessagemodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ConversationMessageModel',
          'path' => '/models/class.conversationmessagemodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ConversationMessageModel',
          'path' => '/Models/class.conversationmessagemodel.php',
        ),
      ),
      'conversationmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ConversationModel',
          'path' => '/models/class.conversationmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ConversationModel',
          'path' => '/Models/class.conversationmodel.php',
        ),
      ),
      'conversationsmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ConversationsModel',
          'path' => '/models/class.conversationsmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ConversationsModel',
          'path' => '/Models/class.conversationsmodel.php',
        ),
      ),
      'addpeoplemodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'AddPeopleModule',
          'path' => '/modules/class.addpeoplemodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'AddPeopleModule',
          'path' => '/Modules/class.addpeoplemodule.php',
        ),
      ),
      'clearhistorymodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ClearHistoryModule',
          'path' => '/modules/class.clearhistorymodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ClearHistoryModule',
          'path' => '/Modules/class.clearhistorymodule.php',
        ),
      ),
      'inboxmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'InboxModule',
          'path' => '/modules/class.inboxmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'InboxModule',
          'path' => '/Modules/class.inboxmodule.php',
        ),
      ),
      'inthisconversationmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'InThisConversationModule',
          'path' => '/modules/class.inthisconversationmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'InThisConversationModule',
          'path' => '/Modules/class.inthisconversationmodule.php',
        ),
      ),
      'newconversationmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'NewConversationModule',
          'path' => '/modules/class.newconversationmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'NewConversationModule',
          'path' => '/Modules/class.newconversationmodule.php',
        ),
      ),
      'conversationshooks' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ConversationsHooks',
          'path' => '/settings/class.hooks.php',
        ),
      ),
    ),
     'subdir' => '/applications/conversations',
     'translations' => 
    array (
      'en' => 
      array (
        0 => '/locale/en.php',
      ),
    ),
     'special' => 
    array (
      'plugin' => 'ConversationsHooks',
      'structure' => '/settings/structure.php',
      'config' => '/settings/configuration.php',
    ),
  )),
  'dashboard' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'description' => 'Handles user, role, permission, plugin, theme, and application management.',
      'version' => '2.4.201',
      'allowDisable' => false,
      'url' => 'https://open.vanillaforums.com',
      'license' => 'GNU GPL v2',
      'priority' => 5,
      'hidden' => true,
      'icon' => 'dashboard.png',
      'key' => 'dashboard',
      'type' => 'addon',
      'name' => 'Dashboard',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Vanilla Staff',
          'email' => 'support@vanillaforums.com',
          'homepage' => 'https://open.vanillaforums.com',
        ),
      ),
      'oldType' => 'application',
      'keyRaw' => 'Dashboard',
      'Issues' => 
      array (
      ),
    ),
     'classes' => 
    array (
      'activitycontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ActivityController',
          'path' => '/controllers/class.activitycontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ActivityController',
          'path' => '/Controllers/class.activitycontroller.php',
        ),
      ),
      'addoncachecontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'AddonCacheController',
          'path' => '/controllers/class.addoncachecontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'AddonCacheController',
          'path' => '/Controllers/class.addoncachecontroller.php',
        ),
      ),
      'assetcontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'AssetController',
          'path' => '/controllers/class.assetcontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'AssetController',
          'path' => '/Controllers/class.assetcontroller.php',
        ),
      ),
      'authenticatecontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'AuthenticateController',
          'path' => '/controllers/class.authenticatecontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'AuthenticateController',
          'path' => '/Controllers/class.authenticatecontroller.php',
        ),
      ),
      'dashboardcontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DashboardController',
          'path' => '/controllers/class.dashboardcontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DashboardController',
          'path' => '/Controllers/class.dashboardcontroller.php',
        ),
      ),
      'dbacontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DbaController',
          'path' => '/controllers/class.dbacontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DbaController',
          'path' => '/Controllers/class.dbacontroller.php',
        ),
      ),
      'embedcontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'EmbedController',
          'path' => '/controllers/class.embedcontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'EmbedController',
          'path' => '/Controllers/class.embedcontroller.php',
        ),
      ),
      'entrycontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'EntryController',
          'path' => '/controllers/class.entrycontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'EntryController',
          'path' => '/Controllers/class.entrycontroller.php',
        ),
      ),
      'homecontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'HomeController',
          'path' => '/controllers/class.homecontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'HomeController',
          'path' => '/Controllers/class.homecontroller.php',
        ),
      ),
      'importcontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ImportController',
          'path' => '/controllers/class.importcontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ImportController',
          'path' => '/Controllers/class.importcontroller.php',
        ),
      ),
      'logcontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'LogController',
          'path' => '/controllers/class.logcontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'LogController',
          'path' => '/Controllers/class.logcontroller.php',
        ),
      ),
      'messagecontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'MessageController',
          'path' => '/controllers/class.messagecontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'MessageController',
          'path' => '/Controllers/class.messagecontroller.php',
        ),
      ),
      'modulecontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ModuleController',
          'path' => '/controllers/class.modulecontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ModuleController',
          'path' => '/Controllers/class.modulecontroller.php',
        ),
      ),
      'notificationscontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'NotificationsController',
          'path' => '/controllers/class.notificationscontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'NotificationsController',
          'path' => '/Controllers/class.notificationscontroller.php',
        ),
      ),
      'plugincontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'PluginController',
          'path' => '/controllers/class.plugincontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'PluginController',
          'path' => '/Controllers/class.plugincontroller.php',
        ),
      ),
      'profilecontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ProfileController',
          'path' => '/controllers/class.profilecontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ProfileController',
          'path' => '/Controllers/class.profilecontroller.php',
        ),
      ),
      'rolecontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'RoleController',
          'path' => '/controllers/class.rolecontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'RoleController',
          'path' => '/Controllers/class.rolecontroller.php',
        ),
      ),
      'rootcontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'RootController',
          'path' => '/controllers/class.rootcontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'RootController',
          'path' => '/Controllers/class.rootcontroller.php',
        ),
      ),
      'routescontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'RoutesController',
          'path' => '/controllers/class.routescontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'RoutesController',
          'path' => '/Controllers/class.routescontroller.php',
        ),
      ),
      'searchcontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SearchController',
          'path' => '/controllers/class.searchcontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SearchController',
          'path' => '/Controllers/class.searchcontroller.php',
        ),
      ),
      'sessioncontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SessionController',
          'path' => '/controllers/class.sessioncontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SessionController',
          'path' => '/Controllers/class.sessioncontroller.php',
        ),
      ),
      'settingscontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SettingsController',
          'path' => '/controllers/class.settingscontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SettingsController',
          'path' => '/Controllers/class.settingscontroller.php',
        ),
      ),
      'setupcontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SetupController',
          'path' => '/controllers/class.setupcontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SetupController',
          'path' => '/Controllers/class.setupcontroller.php',
        ),
      ),
      'socialcontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SocialController',
          'path' => '/controllers/class.socialcontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SocialController',
          'path' => '/Controllers/class.socialcontroller.php',
        ),
      ),
      'statisticscontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'StatisticsController',
          'path' => '/controllers/class.statisticscontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'StatisticsController',
          'path' => '/Controllers/class.statisticscontroller.php',
        ),
      ),
      'usercontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UserController',
          'path' => '/controllers/class.usercontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UserController',
          'path' => '/Controllers/class.usercontroller.php',
        ),
      ),
      'utilitycontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UtilityController',
          'path' => '/controllers/class.utilitycontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UtilityController',
          'path' => '/Controllers/class.utilitycontroller.php',
        ),
      ),
      'abstractapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'AbstractApiController',
          'path' => '/controllers/api/AbstractApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'AbstractApiController',
          'path' => '/Controllers/api/AbstractApiController.php',
        ),
      ),
      'addonsapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'AddonsApiController',
          'path' => '/controllers/api/AddonsApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'AddonsApiController',
          'path' => '/Controllers/api/AddonsApiController.php',
        ),
      ),
      'applicantsapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ApplicantsApiController',
          'path' => '/controllers/api/ApplicantsApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ApplicantsApiController',
          'path' => '/Controllers/api/ApplicantsApiController.php',
        ),
      ),
      'authenticateapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'AuthenticateApiController',
          'path' => '/controllers/api/AuthenticateApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'AuthenticateApiController',
          'path' => '/Controllers/api/AuthenticateApiController.php',
        ),
      ),
      'invitesapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'InvitesApiController',
          'path' => '/controllers/api/InvitesApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'InvitesApiController',
          'path' => '/Controllers/api/InvitesApiController.php',
        ),
      ),
      'rolesapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'RolesApiController',
          'path' => '/controllers/api/RolesApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'RolesApiController',
          'path' => '/Controllers/api/RolesApiController.php',
        ),
      ),
      'tokensapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'TokensApiController',
          'path' => '/controllers/api/TokensApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'TokensApiController',
          'path' => '/Controllers/api/TokensApiController.php',
        ),
      ),
      'usersapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UsersApiController',
          'path' => '/controllers/api/UsersApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UsersApiController',
          'path' => '/Controllers/api/UsersApiController.php',
        ),
      ),
      'emailtemplate' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'EmailTemplate',
          'path' => '/library/class.emailtemplate.php',
        ),
      ),
      'nestedcollection' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'NestedCollection',
          'path' => '/library/class.nestedcollection.php',
        ),
      ),
      'nestedcollectionadapter' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'NestedCollectionAdapter',
          'path' => '/library/class.nestedcollectionadapter.php',
        ),
      ),
      'rawemailtemplate' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'RawEmailTemplate',
          'path' => '/library/class.rawemailtemplate.php',
        ),
      ),
      'staticinitializer' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'StaticInitializer',
          'path' => '/library/class.staticinitializer.php',
        ),
      ),
      'gdn_iemailtemplate' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'Gdn_IEmailTemplate',
          'path' => '/library/interface.iemailtemplate.php',
        ),
      ),
      'activitymodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ActivityModel',
          'path' => '/models/class.activitymodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ActivityModel',
          'path' => '/Models/class.activitymodel.php',
        ),
      ),
      'assetmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'AssetModel',
          'path' => '/models/class.assetmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'AssetModel',
          'path' => '/Models/class.assetmodel.php',
        ),
      ),
      'attachmentmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'AttachmentModel',
          'path' => '/models/class.attachmentmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'AttachmentModel',
          'path' => '/Models/class.attachmentmodel.php',
        ),
      ),
      'banmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'BanModel',
          'path' => '/models/class.banmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'BanModel',
          'path' => '/Models/class.banmodel.php',
        ),
      ),
      'dbamodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DBAModel',
          'path' => '/models/class.dbamodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DBAModel',
          'path' => '/Models/class.dbamodel.php',
        ),
      ),
      'exportmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ExportModel',
          'path' => '/models/class.exportmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ExportModel',
          'path' => '/Models/class.exportmodel.php',
        ),
      ),
      'importmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ImportModel',
          'path' => '/models/class.importmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ImportModel',
          'path' => '/Models/class.importmodel.php',
        ),
      ),
      'invitationmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'InvitationModel',
          'path' => '/models/class.invitationmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'InvitationModel',
          'path' => '/Models/class.invitationmodel.php',
        ),
      ),
      'localemodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'LocaleModel',
          'path' => '/models/class.localemodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'LocaleModel',
          'path' => '/Models/class.localemodel.php',
        ),
      ),
      'logmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'LogModel',
          'path' => '/models/class.logmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'LogModel',
          'path' => '/Models/class.logmodel.php',
        ),
      ),
      'mediamodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'MediaModel',
          'path' => '/models/class.mediamodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'MediaModel',
          'path' => '/Models/class.mediamodel.php',
        ),
      ),
      'messagemodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'MessageModel',
          'path' => '/models/class.messagemodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'MessageModel',
          'path' => '/Models/class.messagemodel.php',
        ),
      ),
      'permissionmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'PermissionModel',
          'path' => '/models/class.permissionmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'PermissionModel',
          'path' => '/Models/class.permissionmodel.php',
        ),
      ),
      'regardingmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'RegardingModel',
          'path' => '/models/class.regardingmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'RegardingModel',
          'path' => '/Models/class.regardingmodel.php',
        ),
      ),
      'rolemodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'RoleModel',
          'path' => '/models/class.rolemodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'RoleModel',
          'path' => '/Models/class.rolemodel.php',
        ),
      ),
      'searchmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SearchModel',
          'path' => '/models/class.searchmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SearchModel',
          'path' => '/Models/class.searchmodel.php',
        ),
      ),
      'sessionmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SessionModel',
          'path' => '/models/class.sessionmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SessionModel',
          'path' => '/Models/class.sessionmodel.php',
        ),
      ),
      'smf2importmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'Smf2ImportModel',
          'path' => '/models/class.smf2importmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'Smf2ImportModel',
          'path' => '/Models/class.smf2importmodel.php',
        ),
      ),
      'spammodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SpamModel',
          'path' => '/models/class.spammodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SpamModel',
          'path' => '/Models/class.spammodel.php',
        ),
      ),
      'tagmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'TagModel',
          'path' => '/models/class.tagmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'TagModel',
          'path' => '/Models/class.tagmodel.php',
        ),
      ),
      'updatemodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UpdateModel',
          'path' => '/models/class.updatemodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UpdateModel',
          'path' => '/Models/class.updatemodel.php',
        ),
      ),
      'userauthenticationnoncemodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UserAuthenticationNonceModel',
          'path' => '/models/class.userauthenticationnoncemodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UserAuthenticationNonceModel',
          'path' => '/Models/class.userauthenticationnoncemodel.php',
        ),
      ),
      'userauthenticationtokenmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UserAuthenticationTokenModel',
          'path' => '/models/class.userauthenticationtokenmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UserAuthenticationTokenModel',
          'path' => '/Models/class.userauthenticationtokenmodel.php',
        ),
      ),
      'usermetamodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UserMetaModel',
          'path' => '/models/class.usermetamodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UserMetaModel',
          'path' => '/Models/class.usermetamodel.php',
        ),
      ),
      'usermodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UserModel',
          'path' => '/models/class.usermodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UserModel',
          'path' => '/Models/class.usermodel.php',
        ),
      ),
      'vanilla1importmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'Vanilla1ImportModel',
          'path' => '/models/class.vanilla1importmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'Vanilla1ImportModel',
          'path' => '/Models/class.vanilla1importmodel.php',
        ),
      ),
      'vbulletinimportmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'vBulletinImportModel',
          'path' => '/models/class.vbulletinimportmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'vBulletinImportModel',
          'path' => '/Models/class.vbulletinimportmodel.php',
        ),
      ),
      'tiny_diff' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'Tiny_diff',
          'path' => '/models/tiny_diff.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'Tiny_diff',
          'path' => '/Models/tiny_diff.php',
        ),
      ),
      'activityfiltermodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ActivityFilterModule',
          'path' => '/modules/class.activityfiltermodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ActivityFilterModule',
          'path' => '/Modules/class.activityfiltermodule.php',
        ),
      ),
      'conditionmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ConditionModule',
          'path' => '/modules/class.conditionmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ConditionModule',
          'path' => '/Modules/class.conditionmodule.php',
        ),
      ),
      'configurationmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ConfigurationModule',
          'path' => '/modules/class.configurationmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ConfigurationModule',
          'path' => '/Modules/class.configurationmodule.php',
        ),
      ),
      'cropimagemodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'CropImageModule',
          'path' => '/modules/class.cropimagemodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'CropImageModule',
          'path' => '/Modules/class.cropimagemodule.php',
        ),
      ),
      'dashboardnavmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DashboardNavModule',
          'path' => '/modules/class.dashboardnavmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DashboardNavModule',
          'path' => '/Modules/class.dashboardnavmodule.php',
        ),
      ),
      'dropdownmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DropdownModule',
          'path' => '/modules/class.dropdownmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DropdownModule',
          'path' => '/Modules/class.dropdownmodule.php',
        ),
      ),
      'guestmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'GuestModule',
          'path' => '/modules/class.guestmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'GuestModule',
          'path' => '/Modules/class.guestmodule.php',
        ),
      ),
      'headmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'HeadModule',
          'path' => '/modules/class.headmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'HeadModule',
          'path' => '/Modules/class.headmodule.php',
        ),
      ),
      'mediaitemmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'MediaItemModule',
          'path' => '/modules/class.mediaitemmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'MediaItemModule',
          'path' => '/Modules/class.mediaitemmodule.php',
        ),
      ),
      'memodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'MeModule',
          'path' => '/modules/class.memodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'MeModule',
          'path' => '/Modules/class.memodule.php',
        ),
      ),
      'menumodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'MenuModule',
          'path' => '/modules/class.menumodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'MenuModule',
          'path' => '/Modules/class.menumodule.php',
        ),
      ),
      'messagemodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'MessageModule',
          'path' => '/modules/class.messagemodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'MessageModule',
          'path' => '/Modules/class.messagemodule.php',
        ),
      ),
      'morepagermodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'MorePagerModule',
          'path' => '/modules/class.morepagermodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'MorePagerModule',
          'path' => '/Modules/class.morepagermodule.php',
        ),
      ),
      'navmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'NavModule',
          'path' => '/modules/class.navmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'NavModule',
          'path' => '/Modules/class.navmodule.php',
        ),
      ),
      'pagermodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'PagerModule',
          'path' => '/modules/class.pagermodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'PagerModule',
          'path' => '/Modules/class.pagermodule.php',
        ),
      ),
      'profilefiltermodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ProfileFilterModule',
          'path' => '/modules/class.profilefiltermodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ProfileFilterModule',
          'path' => '/Modules/class.profilefiltermodule.php',
        ),
      ),
      'profileoptionsmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ProfileOptionsModule',
          'path' => '/modules/class.profileoptionsmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ProfileOptionsModule',
          'path' => '/Modules/class.profileoptionsmodule.php',
        ),
      ),
      'recentactivitymodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'RecentActivityModule',
          'path' => '/modules/class.recentactivitymodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'RecentActivityModule',
          'path' => '/Modules/class.recentactivitymodule.php',
        ),
      ),
      'recentusermodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'RecentUserModule',
          'path' => '/modules/class.recentusermodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'RecentUserModule',
          'path' => '/Modules/class.recentusermodule.php',
        ),
      ),
      'settingsmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SettingsModule',
          'path' => '/modules/class.settingsmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SettingsModule',
          'path' => '/Modules/class.settingsmodule.php',
        ),
      ),
      'sidemenumodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SideMenuModule',
          'path' => '/modules/class.sidemenumodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SideMenuModule',
          'path' => '/Modules/class.sidemenumodule.php',
        ),
      ),
      'signedinmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SignedInModule',
          'path' => '/modules/class.signedinmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SignedInModule',
          'path' => '/Modules/class.signedinmodule.php',
        ),
      ),
      'sitenavmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SiteNavModule',
          'path' => '/modules/class.sitenavmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SiteNavModule',
          'path' => '/Modules/class.sitenavmodule.php',
        ),
      ),
      'sitetotalsmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SiteTotalsModule',
          'path' => '/modules/class.sitetotalsmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'SiteTotalsModule',
          'path' => '/Modules/class.sitetotalsmodule.php',
        ),
      ),
      'tablesummarymodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'TableSummaryModule',
          'path' => '/modules/class.tablesummarymodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'TableSummaryModule',
          'path' => '/Modules/class.tablesummarymodule.php',
        ),
      ),
      'togglemenumodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ToggleMenuModule',
          'path' => '/modules/class.togglemenumodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ToggleMenuModule',
          'path' => '/Modules/class.togglemenumodule.php',
        ),
      ),
      'tracemodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'TraceModule',
          'path' => '/modules/class.tracemodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'TraceModule',
          'path' => '/Modules/class.tracemodule.php',
        ),
      ),
      'userbanmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UserBanModule',
          'path' => '/modules/class.userbanmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UserBanModule',
          'path' => '/Modules/class.userbanmodule.php',
        ),
      ),
      'userboxmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UserBoxModule',
          'path' => '/modules/class.userboxmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UserBoxModule',
          'path' => '/Modules/class.userboxmodule.php',
        ),
      ),
      'userinfomodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UserInfoModule',
          'path' => '/modules/class.userinfomodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UserInfoModule',
          'path' => '/Modules/class.userinfomodule.php',
        ),
      ),
      'userphotomodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UserPhotoModule',
          'path' => '/modules/class.userphotomodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UserPhotoModule',
          'path' => '/Modules/class.userphotomodule.php',
        ),
      ),
      'dashboardhooks' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DashboardHooks',
          'path' => '/settings/class.hooks.php',
        ),
      ),
    ),
     'subdir' => '/applications/dashboard',
     'translations' => 
    array (
      'en' => 
      array (
        0 => '/locale/en.php',
      ),
    ),
     'special' => 
    array (
      'plugin' => 'DashboardHooks',
      'structure' => '/settings/structure.php',
      'config' => '/settings/configuration.php',
    ),
  )),
  'vanilla' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'allowDisable' => false,
      'description' => 'Vanilla is the sweetest discussion forum on the web.',
      'version' => '2.4.201',
      'setupController' => 'setup',
      'url' => 'https://open.vanillaforums.com',
      'license' => 'GPL v2',
      'hidden' => true,
      'icon' => 'vanilla.png',
      'key' => 'vanilla',
      'type' => 'addon',
      'priority' => 10,
      'name' => 'Vanilla',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Vanilla Staff',
          'email' => 'support@vanillaforums.com',
          'homepage' => 'https://open.vanillaforums.com',
        ),
      ),
      'oldType' => 'application',
      'keyRaw' => 'Vanilla',
      'Issues' => 
      array (
      ),
    ),
     'classes' => 
    array (
      'categoriescontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'CategoriesController',
          'path' => '/controllers/class.categoriescontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'CategoriesController',
          'path' => '/Controllers/class.categoriescontroller.php',
        ),
      ),
      'categorycontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'CategoryController',
          'path' => '/controllers/class.categorycontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'CategoryController',
          'path' => '/Controllers/class.categorycontroller.php',
        ),
      ),
      'discussioncontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionController',
          'path' => '/controllers/class.discussioncontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionController',
          'path' => '/Controllers/class.discussioncontroller.php',
        ),
      ),
      'discussionscontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionsController',
          'path' => '/controllers/class.discussionscontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionsController',
          'path' => '/Controllers/class.discussionscontroller.php',
        ),
      ),
      'draftscontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DraftsController',
          'path' => '/controllers/class.draftscontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DraftsController',
          'path' => '/Controllers/class.draftscontroller.php',
        ),
      ),
      'moderationcontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ModerationController',
          'path' => '/controllers/class.moderationcontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'ModerationController',
          'path' => '/Controllers/class.moderationcontroller.php',
        ),
      ),
      'postcontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'PostController',
          'path' => '/controllers/class.postcontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'PostController',
          'path' => '/Controllers/class.postcontroller.php',
        ),
      ),
      'tagscontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'TagsController',
          'path' => '/controllers/class.tagscontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'TagsController',
          'path' => '/Controllers/class.tagscontroller.php',
        ),
      ),
      'vanillacontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'VanillaController',
          'path' => '/controllers/class.vanillacontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'VanillaController',
          'path' => '/Controllers/class.vanillacontroller.php',
        ),
      ),
      'vanillasettingscontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'VanillaSettingsController',
          'path' => '/controllers/class.vanillasettingscontroller.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'VanillaSettingsController',
          'path' => '/Controllers/class.vanillasettingscontroller.php',
        ),
      ),
      'categoriesapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'CategoriesApiController',
          'path' => '/controllers/api/CategoriesApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'CategoriesApiController',
          'path' => '/Controllers/api/CategoriesApiController.php',
        ),
      ),
      'commentsapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'CommentsApiController',
          'path' => '/controllers/api/CommentsApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'CommentsApiController',
          'path' => '/Controllers/api/CommentsApiController.php',
        ),
      ),
      'discussionsapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionsApiController',
          'path' => '/controllers/api/DiscussionsApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionsApiController',
          'path' => '/Controllers/api/DiscussionsApiController.php',
        ),
      ),
      'draftsapicontroller' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DraftsApiController',
          'path' => '/controllers/api/DraftsApiController.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DraftsApiController',
          'path' => '/Controllers/api/DraftsApiController.php',
        ),
      ),
      'categorycollection' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'CategoryCollection',
          'path' => '/library/class.categorycollection.php',
        ),
      ),
      'categorymodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'CategoryModel',
          'path' => '/models/class.categorymodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'CategoryModel',
          'path' => '/Models/class.categorymodel.php',
        ),
      ),
      'commentmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'CommentModel',
          'path' => '/models/class.commentmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'CommentModel',
          'path' => '/Models/class.commentmodel.php',
        ),
      ),
      'discussionmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionModel',
          'path' => '/models/class.discussionmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionModel',
          'path' => '/Models/class.discussionmodel.php',
        ),
      ),
      'draftmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DraftModel',
          'path' => '/models/class.draftmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DraftModel',
          'path' => '/Models/class.draftmodel.php',
        ),
      ),
      'vanillamodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'VanillaModel',
          'path' => '/models/class.vanillamodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'VanillaModel',
          'path' => '/Models/class.vanillamodel.php',
        ),
      ),
      'vanillasearchmodel' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'VanillaSearchModel',
          'path' => '/models/class.vanillasearchmodel.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'VanillaSearchModel',
          'path' => '/Models/class.vanillasearchmodel.php',
        ),
      ),
      'bookmarkedmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'BookmarkedModule',
          'path' => '/modules/class.bookmarkedmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'BookmarkedModule',
          'path' => '/Modules/class.bookmarkedmodule.php',
        ),
      ),
      'categoriesmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'CategoriesModule',
          'path' => '/modules/class.categoriesmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'CategoriesModule',
          'path' => '/Modules/class.categoriesmodule.php',
        ),
      ),
      'categoryfollowtogglemodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'CategoryFollowToggleModule',
          'path' => '/modules/class.categoryfollowtogglemodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'CategoryFollowToggleModule',
          'path' => '/Modules/class.categoryfollowtogglemodule.php',
        ),
      ),
      'categorymoderatorsmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'CategoryModeratorsModule',
          'path' => '/modules/class.categorymoderatorsmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'CategoryModeratorsModule',
          'path' => '/Modules/class.categorymoderatorsmodule.php',
        ),
      ),
      'discussionfiltermodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionFilterModule',
          'path' => '/modules/class.discussionfiltermodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionFilterModule',
          'path' => '/Modules/class.discussionfiltermodule.php',
        ),
      ),
      'discussionsmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionsModule',
          'path' => '/modules/class.discussionsmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionsModule',
          'path' => '/Modules/class.discussionsmodule.php',
        ),
      ),
      'discussionsortermodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionSorterModule',
          'path' => '/modules/class.discussionsortermodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionSorterModule',
          'path' => '/Modules/class.discussionsortermodule.php',
        ),
      ),
      'discussionssortfiltermodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionsSortFilterModule',
          'path' => '/modules/class.discussionssortfiltermodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DiscussionsSortFilterModule',
          'path' => '/Modules/class.discussionssortfiltermodule.php',
        ),
      ),
      'draftsmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'DraftsModule',
          'path' => '/modules/class.draftsmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'DraftsModule',
          'path' => '/Modules/class.draftsmodule.php',
        ),
      ),
      'flatcategorymodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'FlatCategoryModule',
          'path' => '/modules/class.flatcategorymodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'FlatCategoryModule',
          'path' => '/Modules/class.flatcategorymodule.php',
        ),
      ),
      'newdiscussionmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'NewDiscussionModule',
          'path' => '/modules/class.newdiscussionmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'NewDiscussionModule',
          'path' => '/Modules/class.newdiscussionmodule.php',
        ),
      ),
      'promotedcontentmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'PromotedContentModule',
          'path' => '/modules/class.promotedcontentmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'PromotedContentModule',
          'path' => '/Modules/class.promotedcontentmodule.php',
        ),
      ),
      'tagmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'TagModule',
          'path' => '/modules/class.tagmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'TagModule',
          'path' => '/Modules/class.tagmodule.php',
        ),
      ),
      'usercommentsmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UserCommentsModule',
          'path' => '/modules/class.usercommentsmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UserCommentsModule',
          'path' => '/Modules/class.usercommentsmodule.php',
        ),
      ),
      'userdiscussionsmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'UserDiscussionsModule',
          'path' => '/modules/class.userdiscussionsmodule.php',
        ),
        1 => 
        array (
          'namespace' => '',
          'className' => 'UserDiscussionsModule',
          'path' => '/Modules/class.userdiscussionsmodule.php',
        ),
      ),
      'vanillahooks' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'VanillaHooks',
          'path' => '/settings/class.hooks.php',
        ),
      ),
    ),
     'subdir' => '/applications/vanilla',
     'translations' => 
    array (
      'en' => 
      array (
        0 => '/locale/en.php',
      ),
    ),
     'special' => 
    array (
      'plugin' => 'VanillaHooks',
      'structure' => '/settings/structure.php',
      'config' => '/settings/configuration.php',
    ),
  )),
  'allviewed' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'All Viewed',
      'description' => 'Allows users to mark all discussions as viewed and mark category viewed.',
      'version' => '2.2',
      'license' => 'GNU GPLv2',
      'mobileFriendly' => true,
      'icon' => 'all-viewed.png',
      'key' => 'allviewed',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Lincoln Russell',
          'email' => 'lincoln@vanillaforums.com',
          'homepage' => 'http://lincolnwebs.com',
        ),
        1 => 
        array (
          'name' => 'Oliver Chung',
          'email' => 'shoat@cs.washington.edu',
        ),
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'AllViewed',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'allviewedplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'AllViewedPlugin',
          'path' => '/class.allviewed.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/AllViewed',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'AllViewedPlugin',
    ),
  )),
  'buttonbar' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Button Bar',
      'description' => 'Adds several simple buttons above comment boxes, allowing additional formatting.',
      'version' => '1.8.0',
      'mobileFriendly' => true,
      'requiredTheme' => false,
      'icon' => 'button_bar.png',
      'key' => 'buttonbar',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Tim Gunter',
          'email' => 'tim@vanillaforums.com',
          'homepage' => 'http://www.vanillaforums.com',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.1',
      ),
      'conflict' => 
      array (
        'editor' => '*',
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'ButtonBar',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'buttonbarplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ButtonBarPlugin',
          'path' => '/class.buttonbar.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/ButtonBar',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'ButtonBarPlugin',
    ),
  )),
  'editor' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Advanced Editor',
      'description' => 'Enables advanced editing of posts in several formats, including WYSIWYG, simple HTML, Markdown, and BBCode.',
      'version' => '1.8.1',
      'mobileFriendly' => true,
      'registerPermissions' => 
      array (
        'Plugins.Attachments.Upload.Allow' => 'Garden.Profiles.Edit',
      ),
      'settingsUrl' => '/settings/editor',
      'settingsPermission' => 'Garden.Settings.Manage',
      'icon' => 'advanced-editor.png',
      'key' => 'editor',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Dane MacMillan',
          'homepage' => 'https://open.vanillaforums.com/profile/dane',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.2',
      ),
      'conflict' => 
      array (
        'buttonbar' => '*',
      ),
      'oldType' => 'plugin',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'editorplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'EditorPlugin',
          'path' => '/class.editor.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/editor',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'EditorPlugin',
    ),
  )),
  'emojiextender' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Emoji Sets',
      'description' => 'Change your emoji set!',
      'version' => '1.1',
      'license' => 'GNU GPL2',
      'icon' => 'emoji_set.png',
      'settingsUrl' => '/settings/EmojiExtender',
      'mobileFriendly' => true,
      'key' => 'emojiextender',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Becky Van Bussel',
          'email' => 'rvanbussel@vanillaforums.com',
          'homepage' => 'http://vanillaforums.com',
        ),
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'EmojiExtender',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'emojiextenderplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'EmojiExtenderPlugin',
          'path' => '/class.emojiextender.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/EmojiExtender',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'EmojiExtenderPlugin',
    ),
  )),
  'facebook' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Facebook Social Connect',
      'description' => 'Users may sign into your site using their Facebook account and optionally share forum content there.',
      'version' => '1.2.0',
      'requiredTheme' => false,
      'mobileFriendly' => true,
      'settingsUrl' => '/dashboard/social/facebook',
      'settingsPermission' => 'Garden.Settings.Manage',
      'hasLocale' => true,
      'registerPermissions' => false,
      'socialConnect' => true,
      'requiresRegistration' => true,
      'icon' => 'facebook_social_connect.png',
      'key' => 'facebook',
      'type' => 'addon',
      'documentationUrl' => 'http://docs.vanillaforums.com/help/addons/social/facebook/',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Todd Burry',
          'email' => 'todd@vanillaforums.com',
          'homepage' => 'https://open.vanillaforums.com/profile/todd',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.2',
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'Facebook',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'facebookplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'FacebookPlugin',
          'path' => '/class.facebook.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/Facebook',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'FacebookPlugin',
    ),
  )),
  'flagging' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Flagging',
      'description' => 'Allows users to report content that violates forum rules.',
      'version' => '1.1.1',
      'requiredTheme' => false,
      'settingsUrl' => '/dashboard/plugin/flagging',
      'usePopupSettings' => false,
      'settingsPermission' => 'Garden.Moderation.Manage',
      'hasLocale' => true,
      'mobileFriendly' => true,
      'registerPermissions' => 
      array (
        0 => 'Plugins.Flagging.Notify',
      ),
      'icon' => 'flagging.png',
      'key' => 'flagging',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Tim Gunter',
          'email' => 'tim@vanillaforums.com',
          'homepage' => 'http://www.vanillaforums.com',
        ),
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'Flagging',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'flaggingplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'FlaggingPlugin',
          'path' => '/class.flagging.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/Flagging',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'FlaggingPlugin',
    ),
  )),
  'gettingstarted' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Getting Started',
      'description' => 'Adds a welcome message to the dashboard showing new administrators things they can do to get started using their forum. Checks off each item as it is completed.',
      'version' => '1',
      'hidden' => true,
      'key' => 'gettingstarted',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Mark O\'Sullivan',
          'email' => 'mark@vanillaforums.com',
          'homepage' => 'http://vanillaforums.com',
        ),
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'GettingStarted',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'gettingstartedplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'GettingStartedPlugin',
          'path' => '/default.php',
        ),
      ),
    ),
     'subdir' => '/plugins/GettingStarted',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'GettingStartedPlugin',
    ),
  )),
  'googleplus' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Google+ Social Connect',
      'description' => 'Users may sign into your site using their Google Plus account.',
      'version' => '1.1.0',
      'mobileFriendly' => true,
      'settingsUrl' => '/dashboard/social/googleplus',
      'settingsPermission' => 'Garden.Settings.Manage',
      'hidden' => false,
      'socialConnect' => true,
      'requiresRegistration' => false,
      'icon' => 'google_social_connect.png',
      'key' => 'googleplus',
      'type' => 'addon',
      'documentationUrl' => 'http://docs.vanillaforums.com/help/addons/social/googleplus/',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Todd Burry',
          'email' => 'todd@vanillaforums.com',
          'homepage' => 'https://open.vanillaforums.com/profile/todd',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.2',
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'GooglePlus',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'googleplusplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'GooglePlusPlugin',
          'path' => '/class.googleplus.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/GooglePlus',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'GooglePlusPlugin',
    ),
  )),
  'googleprettify' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Syntax Prettifier',
      'description' => 'Adds pretty syntax highlighting to code in discussions and tab support to the comment box. This is a great addon for communities that support programmers and designers.',
      'version' => '1.2.3',
      'mobileFriendly' => true,
      'settingsUrl' => '/dashboard/settings/googleprettify',
      'settingsPermission' => 'Garden.Settings.Manage',
      'icon' => 'google-prettify.png',
      'key' => 'googleprettify',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Todd Burry',
          'email' => 'todd@vanillaforums.com',
          'homepage' => 'https://open.vanillaforums.com/profile/todd',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.0.18',
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'GooglePrettify',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'googleprettifyplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'GooglePrettifyPlugin',
          'path' => '/class.googleprettify.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/GooglePrettify',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'GooglePrettifyPlugin',
    ),
  )),
  'gravatar' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Gravatar',
      'description' => 'Implements Gravatar avatars for all users who have not uploaded their own custom profile picture & icon.',
      'version' => '1.5',
      'settingsUrl' => '/settings/gravatar',
      'icon' => 'gravatar.png',
      'mobileFriendly' => true,
      'key' => 'gravatar',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Mark O\'Sullivan',
          'email' => 'mark@vanillaforums.com',
          'homepage' => 'http://vanillaforums.com',
        ),
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'Gravatar',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'gravatarplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'GravatarPlugin',
          'path' => '/default.php',
        ),
      ),
    ),
     'subdir' => '/plugins/Gravatar',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'GravatarPlugin',
    ),
  )),
  'htmlawed' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'description' => 'This addon is deprecated and can be removed. Its functionality is now in core.',
      'version' => '1.5',
      'hidden' => true,
      'key' => 'htmlawed',
      'type' => 'addon',
      'priority' => 100,
      'oldType' => 'plugin',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Todd Burry',
          'email' => 'todd@vanillaforums.com',
          'homepage' => 'http://vanillaforums.com/profile/todd',
        ),
      ),
      'Issues' => 
      array (
      ),
      'keyRaw' => 'HtmLawed',
    ),
     'classes' => 
    array (
      'htmlawedplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'HtmLawedPlugin',
          'path' => '/class.htmlawed.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/HtmLawed',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'HtmLawedPlugin',
    ),
  )),
  'indexphotos' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Discussion Photos',
      'description' => 'Displays photo and name of the user who started each discussion in discussion listings on modern layouts. Note that this plugin will not have any affect when table layouts are enabled.',
      'version' => '1.2.2',
      'registerPermissions' => false,
      'mobileFriendly' => true,
      'icon' => 'discussion_photos.png',
      'key' => 'indexphotos',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Lincoln Russell',
          'email' => 'lincolnwebs@gmail.com',
          'homepage' => 'http://lincolnwebs.com',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.1',
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'IndexPhotos',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'indexphotosplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'IndexPhotosPlugin',
          'path' => '/class.indexphotos.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/IndexPhotos',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'IndexPhotosPlugin',
    ),
  )),
  'oauth2' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'OAuth2 SSO',
      'className' => 'OAuth2Plugin',
      'description' => 'Connect to an authentication provider to allow users to log on using SSO.',
      'version' => '1.0.0',
      'settingsUrl' => '/settings/oauth2',
      'settingsPermission' => 'Garden.Settings.Manage',
      'mobileFriendly' => true,
      'icon' => 'oauth2.png',
      'key' => 'oauth2',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Patrick Kelly',
          'email' => 'patrick.k@vanillaforums.com',
          'homepage' => 'http://www.vanillaforums.com',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.2',
      ),
      'oldType' => 'plugin',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'oauth2plugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'OAuth2Plugin',
          'path' => '/class.oauth2.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/oauth2',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'OAuth2Plugin',
    ),
  )),
  'openid' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'OpenID',
      'description' => 'Allows users to sign in with OpenID. Must be enabled before using &lsquo;Google Sign In&rsquo; and &lsquo;Steam&rsquo; plugins.',
      'version' => '1.2.0',
      'mobileFriendly' => true,
      'settingsUrl' => '/settings/openid',
      'settingsPermission' => 'Garden.Settings.Manage',
      'socialConnect' => true,
      'icon' => 'open-id.png',
      'key' => 'openid',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Todd Burry',
          'email' => 'todd@vanillaforums.com',
          'homepage' => 'https://open.vanillaforums.com/profile/todd',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.2',
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'OpenID',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'lightopenid' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'LightOpenID',
          'path' => '/class.lightopenid.php',
        ),
      ),
      'openidplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'OpenIDPlugin',
          'path' => '/class.openid.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/OpenID',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'OpenIDPlugin',
    ),
  )),
  'profileextender' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Profile Extender',
      'description' => 'Add fields (like status, location, or gamer tags) to profiles and registration.',
      'version' => '3.0.2',
      'mobileFriendly' => true,
      'settingsUrl' => '/dashboard/settings/profileextender',
      'usePopupSettings' => false,
      'settingsPermission' => 'Garden.Settings.Manage',
      'icon' => 'profile-extender.png',
      'key' => 'profileextender',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Lincoln Russell',
          'email' => 'lincoln@vanillaforums.com',
          'homepage' => 'http://lincolnwebs.com',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.1',
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'ProfileExtender',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'profileextenderplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'ProfileExtenderPlugin',
          'path' => '/class.profileextender.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/ProfileExtender',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'ProfileExtenderPlugin',
    ),
  )),
  'quotes' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Quotes',
      'description' => 'Adds an option to each comment for users to easily quote each other.',
      'version' => '1.9',
      'mobileFriendly' => true,
      'hasLocale' => true,
      'icon' => 'quotes.png',
      'key' => 'quotes',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Tim Gunter',
          'email' => 'tim@vanillaforums.com',
          'homepage' => 'http://www.vanillaforums.com',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.1',
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'Quotes',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'quotesplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'QuotesPlugin',
          'path' => '/class.quotes.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/Quotes',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'QuotesPlugin',
    ),
  )),
  'recaptcha' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'reCAPTCHA Support',
      'description' => 'Add recaptcha validation to signups.',
      'version' => '0.1',
      'mobileFriendly' => true,
      'icon' => 'recaptcha_support.png',
      'key' => 'recaptcha',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Tim Gunter',
          'email' => 'tim@vanillaforums.com',
          'homepage' => 'http://www.vanillaforums.com',
        ),
      ),
      'oldType' => 'plugin',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'recaptchaplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'RecaptchaPlugin',
          'path' => '/class.recaptcha.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/recaptcha',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'RecaptchaPlugin',
    ),
  )),
  'splitmerge' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Split / Merge',
      'description' => 'Allows moderators with discussion edit permission to split & merge discussions.',
      'version' => '1.2',
      'hasLocale' => true,
      'icon' => 'split-merge.png',
      'key' => 'splitmerge',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Mark O\'Sullivan',
          'email' => 'mark@vanillaforums.com',
          'homepage' => 'http://www.vanillaforums.com',
        ),
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'SplitMerge',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'splitmergeplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'SplitMergePlugin',
          'path' => '/class.splitmerge.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/SplitMerge',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'SplitMergePlugin',
    ),
  )),
  'stopforumspam' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Stop Forum Spam',
      'description' => 'Got spammer problems? This integrates the spammer blacklist from stopforumspam.com to mitigate the issue.',
      'version' => '1.0.1',
      'settingsUrl' => '/settings/stopforumspam',
      'icon' => 'stop_forum_spam.png',
      'key' => 'stopforumspam',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Todd Burry',
          'email' => 'todd@vanillaforums.com',
          'homepage' => 'https://open.vanillaforums.com/profile/todd',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.0.18',
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'StopForumSpam',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'stopforumspamplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'StopForumSpamPlugin',
          'path' => '/class.stopforumspam.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/StopForumSpam',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'StopForumSpamPlugin',
    ),
  )),
  'stubcontent' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Stub Content',
      'description' => 'This plugin adds stub content to new forums.',
      'version' => '1.0',
      'mobileFriendly' => true,
      'requiredTheme' => false,
      'hasLocale' => false,
      'registerPermissions' => false,
      'icon' => 'stubcontent-plugin.png',
      'key' => 'stubcontent',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Tim Gunter',
          'email' => 'tim@vanillaforums.com',
          'homepage' => 'http://www.vanillaforums.com',
        ),
      ),
      'oldType' => 'plugin',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'stubcontentplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'StubContentPlugin',
          'path' => '/class.stubcontent.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/stubcontent',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'StubContentPlugin',
    ),
  )),
  'twitter' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Twitter Social Connect',
      'description' => 'Users may sign into your site using their Twitter account.',
      'version' => '1.1.10',
      'mobileFriendly' => true,
      'settingsUrl' => '/dashboard/social/twitter',
      'settingsPermission' => 'Garden.Settings.Manage',
      'hasLocale' => true,
      'socialConnect' => true,
      'requiresRegistration' => true,
      'icon' => 'twitter_social_connect.png',
      'key' => 'twitter',
      'type' => 'addon',
      'documentationUrl' => 'http://docs.vanillaforums.com/help/addons/social/twitter/',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Todd Burry',
          'email' => 'todd@vanillaforums.com',
          'homepage' => 'https://open.vanillaforums.com/profile/todd',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.2',
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'Twitter',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'twitterplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'TwitterPlugin',
          'path' => '/class.twitter.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/Twitter',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'TwitterPlugin',
    ),
  )),
  'vanillaconnect' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'VanillaConnect',
      'description' => 'VanillaConnect SSO.',
      'key' => 'vanillaconnect',
      'type' => 'addon',
      'version' => '1.0',
      'settingsUrl' => '/settings/vanillaconnect',
      'usePopupSettings' => false,
      'settingsPermission' => 'Garden.Settings.Manage',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Alexandre (DaazKu) Chouinard',
          'email' => 'alexandre.c@vanillaforums.com',
          'homepage' => 'https://github.com/DaazKu',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.4',
      ),
      'oldType' => 'plugin',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'vanillaconnectauthenticator' => 
      array (
        0 => 
        array (
          'namespace' => 'Vanilla\\VanillaConnect\\',
          'className' => 'VanillaConnectAuthenticator',
          'path' => '/VanillaConnectAuthenticator.php',
        ),
      ),
      'vanillaconnectplugin' => 
      array (
        0 => 
        array (
          'namespace' => 'Vanilla\\VanillaConnect\\',
          'className' => 'VanillaConnectPlugin',
          'path' => '/VanillaConnectPlugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/vanillaconnect',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'Vanilla\\VanillaConnect\\VanillaConnectPlugin',
    ),
  )),
  'vanillainthisdiscussion' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'In This Discussion',
      'description' => 'Adds a list of users taking part in the discussion to the side panel of the discussion page in Vanilla.',
      'version' => '1',
      'settingsPermission' => 'Garden.Settings.Manage',
      'settingsUrl' => '/settings/inthisdiscussion',
      'icon' => 'in-this-discussion.png',
      'key' => 'vanillainthisdiscussion',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Mark O\'Sullivan',
          'email' => 'mark@vanillaforums.com',
          'homepage' => 'http://markosullivan.ca',
        ),
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'VanillaInThisDiscussion',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'inthisdiscussionmodule' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'InThisDiscussionModule',
          'path' => '/class.inthisdiscussionmodule.php',
        ),
      ),
      'vanillainthisdiscussionplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'VanillaInThisDiscussionPlugin',
          'path' => '/default.php',
        ),
      ),
    ),
     'subdir' => '/plugins/VanillaInThisDiscussion',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'VanillaInThisDiscussionPlugin',
    ),
  )),
  'vanillastats' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Vanilla Statistics',
      'description' => 'Adds helpful graphs and information about activity on your forum over time (new users, discussions, comments, and pageviews).',
      'version' => '2.0.7',
      'mobileFriendly' => true,
      'icon' => 'vanilla_stats.png',
      'key' => 'vanillastats',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Vanilla Staff',
          'email' => 'support@vanillaforums.com',
          'homepage' => 'http://www.vanillaforums.com',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.0.18',
      ),
      'oldType' => 'plugin',
      'keyRaw' => 'VanillaStats',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'vanillastatsplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'VanillaStatsPlugin',
          'path' => '/class.vanillastats.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/VanillaStats',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'VanillaStatsPlugin',
    ),
  )),
  'vanillicon' => 
  Vanilla\Addon::__set_state(array(
     'info' => 
    array (
      'name' => 'Vanillicon',
      'description' => 'Provides fun default user icons from vanillicon.com.',
      'version' => '2.1.0',
      'mobileFriendly' => true,
      'settingsUrl' => '/settings/vanillicon',
      'settingsPermission' => 'Garden.Settings.Manage',
      'icon' => 'vanillicon.png',
      'key' => 'vanillicon',
      'type' => 'addon',
      'authors' => 
      array (
        0 => 
        array (
          'name' => 'Todd Burry',
          'email' => 'todd@vanillaforums.com',
          'homepage' => 'https://open.vanillaforums.com/profile/todd',
        ),
      ),
      'require' => 
      array (
        'vanilla' => '>=2.0.18',
      ),
      'oldType' => 'plugin',
      'Issues' => 
      array (
      ),
      'priority' => 100,
    ),
     'classes' => 
    array (
      'vanilliconplugin' => 
      array (
        0 => 
        array (
          'namespace' => '',
          'className' => 'VanilliconPlugin',
          'path' => '/class.vanillicon.plugin.php',
        ),
      ),
    ),
     'subdir' => '/plugins/vanillicon',
     'translations' => 
    array (
    ),
     'special' => 
    array (
      'plugin' => 'VanilliconPlugin',
    ),
  )),
);
