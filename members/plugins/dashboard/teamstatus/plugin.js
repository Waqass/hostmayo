teamstatus = {};
teamstatus.plugin = new ceSidebarPlugin({pluginname:"teamstatus"});

teamstatus.paging = {};
teamstatus.paging.start = 0;
teamstatus.paging.limit = 3;
teamstatus.statusCount = 0;

teamstatus.process = function(e)
{

    var self = teamstatus;
    //lets not update anything if the plugin is not visible
    if ($('#newest_link').length === 0) return;

    // Update the count if it has changed
    if(self.statusCount != e.totalcount){
        self.statusCount = e.totalcount;
    }

    if(teamstatus.paging.start > 0 &&  e.teamstatus.length === 0){
        teamstatus.paginate('newer');
    }

    var displayNewerLink = '';
    var displayPaginationSeparator = '';
    var displayOlderLink = '';

    if(teamstatus.paging.start === 0){
        displayNewerLink = 'none';
        displayPaginationSeparator = 'none';
    }

    if((teamstatus.paging.start + teamstatus.paging.limit) >= self.statusCount){
        displayOlderLink = 'none';
        displayPaginationSeparator = 'none';
    }

    document.getElementById('newest_link').style.display = displayNewerLink;
    document.getElementById('pagination_separator_n').style.display = displayNewerLink;
    document.getElementById('newer_link').style.display = displayNewerLink;
    document.getElementById('pagination_separator').style.display = displayPaginationSeparator;
    document.getElementById('older_link').style.display = displayOlderLink;
    document.getElementById('pagination_separator_o').style.display = displayOlderLink;
    document.getElementById('oldest_link').style.display = displayOlderLink;

    // If there are no items, then show a message
    var displayNoItems = 'none';

    if(self.statusCount === 0){
        displayNoItems = '';
    }

    document.getElementById('no_items').style.display = displayNoItems;

    var statusLowerUpdatedTimeStamp = -1;

    var content = "";
    $.get('../plugins/dashboard/teamstatus/plugin.mustache',
        function(template) {
            items = {teamstatus:e.teamstatus};
            //set cache for this plugin for plugin manager
            content = Mustache.render(template, items);
            $('#teamstatus').html(content);
            teamstatus.plugin.setContent();
        }
    );
};

// Moved the loading of the team status away from the ext.onload to a seperate function
teamstatus.PluginTeamStatus = function()
{
    if (teamstatus.paging.start<0) teamstatus.paging.start = 0;

    teamstatus.plugin.addHeartBeat({
        name: 'GetTeamStatus',
        delay:0,
        pulse:45,
        args: {
            start:  teamstatus.paging.start,
            limit:  teamstatus.paging.limit
        },
        callback: function(response) {
            teamstatus.process(response);
        }
    });

};

teamstatus.addTeamStatus = function(replyid)
{
    var title = lang("Your reply:");
    if(replyid === undefined) {
        title = lang("Your status:");
        teamstatus.replyid = '';
    } else {
        teamstatus.replyid = replyid;
    }

    new RichHTML.prompt(title,{textarea:true},function(ret){

        if ( ret.btn == lang('Cancel') ) {
            return;
        }

        if(trim(ret.elements.value) !== ""){
            teamstatus.plugin.callAction({
                name: 'saveteamstatus',
                args: {
                    message: ret.elements.value,
                    replyid : teamstatus.replyid
                },
                callback: function(response) {
                    teamstatus.paginate("newest");
                }
            });
        }

    });

};

teamstatus.deleteTeamStatus = function(id, userid)
{
    if(confirm(lang('Are you sure you want to delete this message?'))){
        teamstatus.plugin.callAction({
            name: 'deleteteamstatus',
            args: {
                id: id, userid: userid
            },
            callback: function(response) {
                teamstatus.paginate("newest");
            }
        });

    }
};

teamstatus.paginate = function(direction)
{

    var skipCallAction  = false;

    switch(direction){
        case 'newest':
            teamstatus.plugin.cancelHeartBeat('GetTeamStatus');
            //restarts the heartbeat
            teamstatus.paging.start = 0;
            teamstatus.PluginTeamStatus();
            skipCallAction = true;
            break;
        case 'newer':
            teamstatus.plugin.cancelHeartBeat('GetTeamStatus');
            teamstatus.paging.start = teamstatus.paging.start - teamstatus.paging.limit;
            break;
        case 'older':
            teamstatus.plugin.cancelHeartBeat('GetTeamStatus');
            teamstatus.paging.start = teamstatus.paging.start + teamstatus.paging.limit;
            break;
        case 'oldest':
            teamstatus.plugin.cancelHeartBeat('GetTeamStatus');
            var pages = teamstatus.statusCount / teamstatus.paging.limit;
            var correction = 0;

            if(pages <= Math.floor(pages)){
                correction = -1;
            }
            teamstatus.paging.start = (Math.floor(pages) + correction) * teamstatus.paging.limit;
            break;
        default:
            break;
    }

    if (teamstatus.paging.start<0) teamstatus.paging.start = 0;
    if (!skipCallAction) {
        teamstatus.plugin.callAction({
            name: 'GetTeamStatus',
            args: {
                start:  teamstatus.paging.start,
                limit:  teamstatus.paging.limit
            },
            callback: function(response) {
                teamstatus.process(response);
            }
        });
    }

};
