var PluginMgr = function(args) {
    this.options = args;
    this.active_panel = "";
    this.caches = [];
};

/**
 * TODO
 * Ability for plugins to add events for CE related hooks
 * @param string plugin
 * @param string event
 * @param fnc method to run
 */
PluginMgr.prototype.addSystemListener = function(plugin,event,method)
{
    //we need to raise error if adding listener that CE does not support
};

PluginMgr.prototype.leftbarpluginsCache = "";
PluginMgr.prototype.pluginsContentCache = {};

PluginMgr.prototype.setCount = function(plugin,count, style)
{
    if (!style) style = "badge-important";

    //do not show count if plugin is in the more plugin section
    $('.leftmenu-icon[name="plugin-'+plugin+'"]:not(".leftmenu-more-icon") .leftmenu-icon-count').attr('class','').addClass('badge').addClass('leftmenu-icon-count').addClass(style).html(count).addClass('visible');
    //let's cache left bar until next page load
    var leftbarplugins = $.trim($('.lefmenu-plugin-icons').html());
    if (leftbarplugins != this.leftbarpluginsCache) {
        this.leftbarpluginsCache = leftbarplugins;
        $.ajax({
            url: 'index.php?fuse=clients&action=updatesessionvar',
            type: 'POST',
            data: { fields : {leftbarplugins:  leftbarplugins} }
        });
    }
};

/**
 * Sets cache and plugin panel content if the plugin you are passing content for
 * is active and the panel open
 *
 * @param string plugin  name of plugin that you are setting content for
 * @param string content html to add to the plugin frame
 */
PluginMgr.prototype.setContent = function(plugin,content,skipcaching)
{
    //let's add the plugin name to the container
    if (typeof(skipcaching) === "undefined") skipcaching = false;
    content = this.setCache(plugin,content);
    if (this.active_panel == "plugin-"+plugin) {
        $('.plugincontainer').attr('data-plugin',"plugin-"+plugin);
        if (!skipcaching) {
            $('div#plugin-notsystemplugin').html(content);
            //below is used when we should ASK before leaving present page
            clientexec.bindLinksIfLeavingInvoiceViewIsPrevented();
        }

        var pluginContent = $('div.pluginframe').html();
        if (!this.pluginsContentCache[plugin] || this.pluginsContentCache[plugin] != pluginContent) {
            this.pluginsContentCache[plugin] = pluginContent;
            $.ajax({
                url: 'index.php?fuse=clients&action=updatesessionvar',
                type: 'POST',
                data: { fields : { plugincontent: pluginContent, plugincontent_plugin: plugin } }
            });
        }
    }

    return content;
};

PluginMgr.prototype.setCache = function(plugin,content)
{
    if (typeof(content) === "undefined") {
        content = $('div#plugin-notsystemplugin').html();
    }
    this.caches[plugin] = content;
    return content;
};

PluginMgr.prototype.getCache = function(plugin)
{
    var self = this;
    var content = null;

    //sometimes set to null when we want to unset
    if ((typeof(this.caches[plugin]) !== "undefined") && (this.caches[plugin]!== null) ) {
        content = this.caches[plugin];

        if (this.active_panel == "plugin-"+plugin) {
            //let's cache until next page load
            $('div#plugin-notsystemplugin.plugincontainer').html(content);

            $.ajax({
                url: 'index.php?fuse=clients&action=updatesessionvar',
                type: 'POST',
                data: { fields : { plugincontent: $('div.pluginframe').html(), plugincontent_plugin: plugin } }
            });

        }

    }

    return content;

};

PluginMgr.prototype.process = function(args) {
    var self = this;
    var active_panel = this.active_panel;
    var loading_img = "<img class='content-loading' src='../images/loader.gif' />";
    var url = "";
    var showtoolbar = false;
    var hadCachedData = false;
    var canCache = true;
    var plugin_el = $('div#plugin-notsystemplugin.plugincontainer');

    var params = {
        data: null,
        options : null
    };

    params = $.extend(params,args);
    var plugin = $('.leftmenu-icon[name="plugin-'+params.plugin+'"]');

    //let's see if we have a cached copy before we get latest
    //if we have a cached copy and we can't find the container
    //that means it is a plugin's cached copy
    //getCacheAlsoUpdates content if available and plugin is active
    if ( canCache && (self.getCache(params.plugin) !== null)) {
        hadCachedData = true;
    } else if (params.data === null) {
        //if we don't have cached data don't add the loading image graphic
        plugin_el.html(loading_img);
    } else {
        hadCachedData = true;
    }

    this.options.label = "";

    //let's set this as the saved plugin if it is different
    if (clientexec.pluginData.plugin != params.plugin) {
        $.post('index.php?fuse=admin&controller=sidebar&action=setactivesidebarplugin&plugin='+params.plugin);
        clientexec.pluginData.plugin = params.plugin;
    }

    //we need to do this for all plugins
    //let's load data ONLY if we don't have cached data
    //for this to work all plugins need to use the setcontnet method so that we cache
    //when there is something to cache

    if ( (!canCache) || ((params.data === null) && (!hadCachedData))  )  {
        self.reloadpluginpanel(params.plugin);
    } else if (plugin.data('activeMethod')) {
        eval(plugin.data('activeMethod') + '()');
    }

};

/**
 * Loads the plugin panel via ajax
 * @param  string pluginname internal plugin name
 * @return void
 */
PluginMgr.prototype.reloadpluginpanel = function(pluginname) {
    var plugin_el = $('div#plugin-notsystemplugin.plugincontainer');
    var plugin = $('.leftmenu-icon[name="plugin-'+pluginname+'"]');
    plugin_el.load('index.php?nolog=1&fuse=home&view=viewdashboardplugin&plugin='+pluginname,
        function() {
            if (plugin.data('activeMethod')) {
                eval(plugin.data('activeMethod') + '()');
            }
            //below is used when we should ASK before leaving present page
            clientexec.bindLinksIfLeavingInvoiceViewIsPrevented();
        });
};

/**
 * Populate left bar with icons for the plugins we have enabled
 * @return void
 */
PluginMgr.prototype.startAutoPlugins = function()
{
    var newplugin, obj, el, leftname, moreplugins="", maxplugins = 5;

    //let's add the plugins which we have enabled
    for (k = 0; k < clientexec.sidebarplugins.plugins.length; k++) {
        obj = clientexec.sidebarplugins.plugins[k];

        pluginname = 'plugin-'+obj.plugin;

        //let's check if it has already been added via cache
        el = $('.leftmenu-icon[name="'+pluginname+'"]');
        if (el.length > 0) {

            el.data('activeMethod',obj.activeMethod);
            el.data('cache',obj.cache);
            continue;
        }

        if (!obj.sidebar) {
            continue;
        }

        newplugin = $('.leftmenu-icon[name="cloneme"]').clone();
        newplugin.attr('name',pluginname);
        newplugin.attr('title',obj.title);
        newplugin.attr('data-toggle',"tooltip");
        newplugin.attr('data-placement',"right");
        newplugin.attr('data-header-name',obj.title);

        //let's add name
        //
        if (obj.smalltitle) {
            leftname = obj.smalltitle;
        } else {
            leftname = obj.title.substring(0,10);
        }

        newplugin.find('.plugin-icon-name').text(leftname);

        newplugin.data('activeMethod',obj.activeMethod);
        newplugin.data('cache',obj.cache);

        newplugin.addClass(obj.icon);
        newplugin.css('display','block');
        //newplugin.attr('data-order',obj.order );

        if ($('.leftmenuplugin').length > maxplugins) {
            //we still add the plugin info to dom as hidden so that all sidebar plugin code works as before
            //we pull information from the div when we select active plugin since the popover content disappears when not visible
            //so we use the hidden divs to pull details after clicking on plugin
            $('.lefmenu-plugin-icons').append($('<div class="leftmenuplugin" style="display:none;"></div>').append(newplugin));

            //adding additional plugin in popover container
            //since we are getting the html we need to reset icon
            newplugin.attr('data-placement',"top");
            moreplugins += "<div class='leftmenu-moreplugins'>"+$(newplugin)[0].outerHTML+"</div>";
        } else if (obj.order === 0) {
            $('.lefmenu-plugin-icons').prepend($('<div class="leftmenuplugin"></div>').append(newplugin));
        } else {
            $('.plugin-moreplugins').before($('<div class="leftmenuplugin"></div>').append(newplugin));
        }

    }

    //show more button if we are showing more than 6 plugins
    if (moreplugins!=="" && $('.leftmenuplugin').length > maxplugins) {
        $('.plugin-moreplugins').show();
        $('.plugin-moreplugins .popover').remove();
        $('.plugin-moreplugins i').attr('data-content',moreplugins);
        $('.plugin-moreplugins i').popover();
        $('.plugin-moreplugins .plugin-moreplugins-count').text(clientexec.sidebarplugins.plugins.length - maxplugins);
        $('.plugin-moreplugins .plugin-moreplugins-count').show();
    } else if ($('.leftmenuplugin').length > maxplugins) {
        $('.plugin-moreplugins .popover').remove();
        $('.plugin-moreplugins i').popover();
    }

    //let's cache left bar until next page load
    setTimeout( function() {

        $.ajax({
            url: 'index.php?fuse=clients&action=updatesessionvar',
            type: 'POST',
            data: { fields : { leftbarplugins: $.trim($('.lefmenu-plugin-icons').html()) } }
        });

    }, 2500 );
};

/**
 * After seleting a plugin we set it as the active panel by unsetting active classes
 * from the other lefticons as well both showing the plugin panel and storing into memory the plugin name
 * i.e. this.active_panel
 *
 * @param bool shouldprocess determines if we need to really do any work
 */
PluginMgr.prototype.setActivePlugin = function(jobj) {

    pluginname = jobj.attr('name').toString().split("-");
    this.active_panel = 'plugin-'+pluginname[1];

    $('div.pluginframe-header span.pluginframe-plugin-name').text(jobj.attr('data-header-name'));
    $('.plugincontainer').hide();

    if ($('div#plugin-'+pluginname[1]+'.plugincontainer').length > 0) {
        $('div#plugin-'+pluginname[1]+'.plugincontainer').show();
    } else {
        $('div#plugin-notsystemplugin.plugincontainer').show();
    }

    $('.leftmenu-icon-active').removeClass('leftmenu-icon-active');
    $('#selected-leftmenu-icon-tick').remove();
    jobj.prepend($('<div id="selected-leftmenu-icon-tick" aria-hidden="true" data-icon="&#xe001;"></div>'));
    $('div.pluginframe').show();
    $('div.pluginframe-header').show();
    //$('.maincontainer').addClass('withpluginpanel');
    $('body').addClass('withpluginpanel');
    jobj.addClass('leftmenu-icon-active');

    $(window).trigger('resize');
    if (clientexec.myChart) clientexec.myChart._resize();

};

/**
 * Determine if the config options exist for a plugin
 * @param  string plugin name of the plugin you require to get info for
 * @return void
 */
PluginMgr.prototype.getPluginConfigOptions = function(plugin) {
    var self = this;

    $.ajax({
        url: 'index.php?fuse=home&action=pluginhassettings&type=dashboard&plugin='+plugin,
        dataType: 'json',
        success: function(xhr) {
            var json = ce.parseResponse(xhr);

            if (json.error) return;

            if (json.configurable) {
                //let's load the plugin config settings to where they need to be
                var el = $('#enabledplugin_'+plugin+' div.accordion-inner');
                el.append("<br/><br/><img class='content-loading' src='../images/loader.gif' />");

                $.ajax({
                    url: 'index.php?fuse=home&action=pluginoptions&type=dashboard&plugin='+plugin,
                    dataType: 'json',
                    success: function(xhr) {
                        var desc = el.find('span.description').html();
                        el.html('<span class="description">'+desc+'</span><br/><br/>');
                        $.each(xhr.data.vars,function(a,b){
                            el.append('<span style="margin-bottom:5px;" class="label label-info">'+a+'</span> <input name="'+a+'" class="pluginvalue" style="display:block;width:95%;" type="text" value="'+b+'" /><br/>');
                        });
                        el.append('<button class="submitpluginsettings btn btn-primary" data-complete-text="Saved!" data-loading-text="Updating..." style="float:right;margin-right:10px;" type="button">Update</button><br/><br/>');

                        $('button.submitpluginsettings').unbind('click');
                        $('button.submitpluginsettings').bind('click',function(e){
                            $('button.submitpluginsettings').button('loading');
                            self.savePluginConfigOptions(plugin);
                        });
                    }
                });
            }
        }
    });
};

/**
 * save plugin configuration options
 * @param  string plugin Name of the plugin
 * @return void
 */
PluginMgr.prototype.savePluginConfigOptions = function(plugin) {
    var el = $('#enabledplugin_'+plugin+' div.accordion-inner');
    var data = {};
    data['pluginfields'] = {};

    //let's create our data elements
    $.each(el.find(':input.pluginvalue'),function(e,b) {
        data['pluginfields'][$(b).attr('name')] = $(b).val();
    });

    $.ajax({
        url: 'index.php?fuse=home&action=savepluginoptions&type=dashboard&plugin='+plugin,
        dataType: 'json',
        data : data,
        success: function(xhr) {
            $('button.submitpluginsettings').button('complete');
        }
    });
};

/**
 * disable a system plugin
 * @param string plugin  name of a plugin
 * @param int    shared   1 = shared 0 = not shared
 * @return void
 */
clientexec.disablePlugin = function(plugin,shared)
{
    clientexec.customizePlugins.mask();
    $.ajax({
        url: 'index.php?fuse=home&action=savedashboardstate&task=close&plugin='+plugin+'&shared='+shared,
        success: function(xhr) {
            clientexec.customizePlugins.addParams({shownotice:1});
            clientexec.customizePlugins.reload();
            $('.dashboardchanges').show();
        }
    });
};

/**
 * Method called from plugin configuration file to enable a system plugin
 * @param string plugin  name of a plugin
 * @param int    shared   1 = shared 0 = not shared
 * @return void
 */
clientexec.enablePlugin = function(plugin,shared)
{
    clientexec.customizePlugins.mask();
    $.ajax({
        url: 'index.php?fuse=home&action=savedashboardstate&task=add&plugin='+plugin+'&shared='+shared,
        success: function(xhr) {
            clientexec.customizePlugins.addParams({shownotice:1});
            clientexec.customizePlugins.reload();
            $('.dashboardchanges').show();
        }
    });
};

clientexec.bindextensionicons = function(e)
{
    $('.plugin-moreplugins i').popover('hide');
    pluginname = $(this).attr('name').toString().split("-");

    //let's only process if we have selected a new plugin icon
    if ( 'plugin-'+pluginname[1] !== clientexec.pluginMgr.active_panel ) {

        clientexec.pluginMgr.setActivePlugin($(this));
        clientexec.pluginMgr.process({plugin:pluginname[1]});

        $('.tooltip').remove();

        //lets make sure that the panel is set to open next page refresh
        var session_vars = {
            pluginpanelPosition: "",
            leftbarplugins: $.trim($('.lefmenu-plugin-icons').html())
        };

        $.ajax({
            url: 'index.php?fuse=clients&action=updatesessionvar',
            type: 'POST',
            data: { fields : session_vars }
        });

        $(window).trigger('resize');

    } else {

        $('.closepluginpanel').trigger('click');
        clientexec.pluginMgr.active_panel = "";
    }
};

clientexec.initialize_sidepanel_plugins = function ()
{
    //$(document).on("tap", '.leftmenu-icon:not(".leftmenu-more-icon")', clientexec.bindextensionicons);
    $(document).on("click", '.leftmenu-icon:not(".leftmenu-more-icon")', clientexec.bindextensionicons);


    if ( (clientexec.pluginData !== "") && ($('body').hasClass('withpluginpanel')) ) {
        //if plugin has been removed let's close the panel
        //we have to hard code the system plugins
        if (($.inArray(clientexec.pluginData.plugin, clientexec.sidebarplugins.names) > -1) ) {
            clientexec.pluginMgr.active_panel = 'plugin-'+clientexec.pluginData.plugin;
            clientexec.pluginMgr.process({plugin:clientexec.pluginData.plugin, data:clientexec.pluginData});
            clientexec.pluginMgr.setActivePlugin($('.leftmenu-icon[name="plugin-'+clientexec.pluginData.plugin+'"]'));
        } else {
            //closing as the last saved active panel doesn't exist any longer
            //most likely a plugin that was removed
            $('.closepluginpanel').trigger('click');
            //let's remove this plugin as it doens't exist any longer
            $.ajax({url: 'index.php?fuse=home&action=savedashboardstate&task=close&plugin='+clientexec.pluginData.plugin});
        }

    }

    $('.btncustomizesidebar .icon-cog').click(function(){
        clientexec.customizePlugins = new RichHTML.window({
            hideTitle:true,
            height : 400,
            width : 450,
            url: 'index.php?fuse=home&view=customizedashboard',
            buttons: {button1:{type:'cancel',text:'Close',onclick:function(){
                if ($('.dashboardchanges:reallyvisible').length > 0) {
                    clientexec.customizePlugins.hide();
                    RichHTML.mask();
                    window.location.reload();
                } else {
                    clientexec.customizePlugins.hide();
                }
            }}}
        });
        clientexec.customizePlugins.show();
    });
}
$(document).ready(function(){



});

//Sidebar plugins to be used by the sidebar plugins to interact with CE via js
var ceSidebarPlugin = function(args) {
    if (typeof(args) === "undefined" || typeof(args.pluginname) === "undefined") {
        alert('Error (sidebarPlugin): A plugin name is required during initiation');
        return;
    }
    this.options = args;
};

ceSidebarPlugin.prototype.addSystemListener = function(event,method)
{
    clientexec.pluginMgr.addSystemListener(this.options.pluginname,event,method);
};

ceSidebarPlugin.prototype.setContent = function(content,skipcaching)
{
    //sometimes we cache html with js for setcontent that gets executed before admin is ready
    //if (!clientexec.pluginMgr) return;
    if (typeof(skipcaching) === "undefined") skipcaching = false;
    return clientexec.pluginMgr.setContent(this.options.pluginname,content,skipcaching);
};

ceSidebarPlugin.prototype.setCount = function(count,style)
{
    clientexec.pluginMgr.setCount(this.options.pluginname,count,style);
};

ceSidebarPlugin.prototype.callAction = function(options) {
    var self = this;

    if (options.name === null) {
        alert('Action name required in callAction');
        return;
    }

    if (typeof(options.args) == "undefined") { options.args = {}; }
    if (typeof(options.callback) == "undefined") { options.callback = null; }

    options.args.pluginaction = options.name;
    options.args.plugin=self.options.pluginname;

    $.ajax({
        url: 'index.php?fuse=admin&controller=plugin&action=doplugin',
        data: options.args,
        dataType: 'json',
        success : function(response) {
            if (options.callback !== null ) options.callback(response);
        }
    });
};

ceSidebarPlugin.prototype.cancelHeartBeat = function(name)
{
    heartbeat.remove('index.php?fuse=admin&controller=plugin&action=doplugin&p='+this.options.pluginname+'&i='+name);
};

ceSidebarPlugin.prototype.addHeartBeat = function(options)
{
    var self = this;
    var defaults = { args: {}, callback: null, pulse: 15, delay: null };
    options = $.extend(true, {}, defaults, options);

    options.args.pluginaction=options.name;
    options.args.plugin=self.options.pluginname;

    heartbeat.add({
        name:'index.php?fuse=admin&controller=plugin&action=doplugin&p='+self.options.pluginname+'&i='+options.name,
        args : options.args,
        delay:options.delay,
        pulse:options.pulse,
        callback:options.callback
    });
};





