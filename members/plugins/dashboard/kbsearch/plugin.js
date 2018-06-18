//to access CE's sidepanel plugin functions we need accessor to plugin infrastructure via
//the ceSidebarPlugin passing pluginname - same name given to dir name this plugin resides in
var kbsearch = kbsearch || {};
kbsearch.plugin = new ceSidebarPlugin({pluginname:"kbsearch"});

kbsearch.bindclick = function() {
    $('#search-kb-button').on('click', function(e){
        e.preventDefault();
        clientexec.searchKB_start = 0;
        clientexec.searchKB_query = $('#kbquery').val();
        clientexec.searchKB();
    });
}

$(document).ready(function(){


    $(document).on("keypress", "#kbsearchcontainer #kbquery", function(event){
        if ( event.which == 13 ) {
            event.preventDefault();
            clientexec.searchKB_start = 0;
            clientexec.searchKB_query = $('#kbquery').val();
            clientexec.searchKB();
        }
    });





    clientexec.searchKB = function() {

        $('#kbsearchcontainer #kbsearchresults').html("<img style='margin:20px;padding-left:50px;' class='kbsearchresult-icon-article' src='../images/loader.gif'>");
        var kbarticletemplate = "",prevDisabled="",nextDisabled="";
        $.ajax({
            url: "index.php?fuse=knowledgebase&action=getAutoSuggetArtices",
            dataType: 'json',
            data: {
                start:clientexec.searchKB_start,
                subject: clientexec.searchKB_query
                },
            success: function(json) {
                kbarticletemplate = "Results <span class='color:#888;'>("+json.total_entries+")</span> <div style='border-top:1px solid #ddd; width:175px;margin:5px 0px 0px 0px;'></div>";
                if (json.total_entries === 0) {
                    $('#kbsearchcontainer #kbsearchresults').html(kbarticletemplate+"<strong style='margin-top:10px;display:block;'>No articles found</strong>").show();
                } else {
                    kbarticletemplate +="<div style='margin-top:6px;'></div>{{#entries}}<div class='kbsearchresult-article'><a title='{{excerpt}}' target='_blank' class='articleresult_access_{{access}}' onclick='clientexec.popupKBArticle({{id}},\"{{title}}\")'><i class='icon-external-link' style='position:relative;top:1px;left:2px;color: #888;margin-right: 8px;'></i> {{title}}</a></div>{{/entries}}";
                    if (json.total_entries > 5) {
                        prevDisabled = (clientexec.searchKB_start===0) ? "disabled='disabled'": "";
                        nextDisabled = (clientexec.searchKB_start + 5 >= json.total_entries) ? "disabled='disabled'": "";
                        kbarticletemplate += "<div id='kbsearchresultsmore' class='richgrid-pagenavi'><span class='previouspostslink' "+prevDisabled+"></span><span class='nextpostslink' "+nextDisabled+"></span></div>";
                    } else {
                        kbarticletemplate += "<br/>";
                    }

                    $('#kbsearchcontainer #kbsearchresults').html(Mustache.render(kbarticletemplate, json)).show();

                }

                $('#kbquery').attr('value',clientexec.searchKB_query);
                kbsearch.plugin.setContent();

                // var session_vars = {
                //     KB_Searchquery: clientexec.searchKB_query,
                //     KB_Searchstart: clientexec.searchKB_start,
                //     KB_SearchResults: $('#kbsearchcontainer #kbsearchresults').html()
                // };

                // $.ajax({
                //     url: 'index.php?fuse=clients&action=updatesessionvar',
                //     type: 'POST',
                //     data: { fields : session_vars }
                // });

            }
        });
    };

    clientexec.bindSearchResultClicks = function() {

        $(document).on("click", "#kbsearchresultsmore .previouspostslink", function(event){
            if ($(this).attr('disabled')) {
                return false;
            }
            clientexec.searchKB_start = clientexec.searchKB_start-5;
            if (clientexec.searchKB_start < 0) clientexec.searchKB_start = 0;
            clientexec.searchKB();
        });

        $(document).on("click", "#kbsearchresultsmore .nextpostslink", function(event){
            if ($(this).attr('disabled')) {
                return false;
            }
            clientexec.searchKB_start = clientexec.searchKB_start+5;
            clientexec.searchKB();
        });
    };

    clientexec.bindSearchResultClicks();

});
