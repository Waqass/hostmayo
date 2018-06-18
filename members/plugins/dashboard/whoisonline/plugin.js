//create a namespace just to keep things clean
var whoisonline = {};

//to access CE's sidepanel plugin functions we need accessor to plugin infrastructure via
//the ceSidebarPlugin passing pluginname - same name given to dir name this plugin resides in
whoisonline.plugin = new ceSidebarPlugin({pluginname:"whoisonline"});

whoisonline.processUsers = function() {

    if (clientexec.whoisonline.userCount == -1) {
        setTimeout(function(){whoisonline.processUsers()},3000);
        return;
    }

    var self = whoisonline;
    var hideLastSeen = false;

    $.get('../plugins/dashboard/whoisonline/plugin.mustache',
        function(template) {
            items = {onlineusers:clientexec.whoisonline.onlineusers,offlineusers:clientexec.whoisonline.offlineusers};
            //set cache for this plugin for plugin manager
            var cachedcontent = self.plugin.setContent(Mustache.render(template, items));
        }
    );

    if (clientexec.whoisonline.onlineusers) {
        whoisonline.plugin.setCount(clientexec.whoisonline.onlineusers.length);
    }

    setTimeout(function(){whoisonline.processUsers()},30000);

};

$(document).ready(function(){ whoisonline.processUsers();});