licensedefender = {};
var isViewingAsAdmin = false;

licensedefender.countofrecords = 0;
licensedefender.loggedinas = "";
licensedefender.searchkey = "";

$(document).ready(function() {

    $('.clickable-link').click(function(e) {
        e.preventDefault();
        window.location = $(this).attr('data-url');
    });

    licensedefender.grid = new RichHTML.grid({
        el: 'resellerPanel-grid',
        url: 'index.php?fuse=admin&action=doplugin&controller=plugin&type=snapin&plugin=licensedefender&pluginaction=getDomains',
	    baseParams: { limit: clientexec.records_per_view, sort: 'domain', dir: 'asc'},
        root: 'data',
        columns: [{
                xtype: "expander",
                dataIndex: "response",
                renderOnExpand: true,
                renderer: function(text, row) {
                    licensedefender.grid.disable();
                    var html = "<div class='licensedetails'>";

                    $.ajax({
                        url: 'index.php?fuse=admin&controller=plugin&action=doplugin&type=snapin',
                        dataType: 'json',
                        async: false,
                        data: {
                            plugin: 'licensedefender',
                            pluginaction: 'getLicenseDetails',
                            domain: row.domain,
                            resellerid: row.resellerid,
                            loginas: licensedefender.loggedinas
                        },
                        success: function(json) {

                            if (json.data.domain){
                                html += " <label>Expires</label>: "+json.data.domain[1];
                                if (json.data.domain[3]!=0) {
                                    html += " <label>Installed Version</label>: "+json.data.domain[2];
                                    html += " <label>Last Checked</label>: "+json.data.domain[4];
                                    html += " <br/><label>Installed In</label>: "+json.data.domain[0];
                                }
                            }
                            if (json.data.lastx) {
                                if (json.data.lastx.length>0) html += " <br/></br><div class='detailheader'>Last "+json.data.lastx.length+" License Checks</div>";
                                for (var x=0;x<json.data.lastx.length;x++){
                                    html+= "<br/> <label>Date</label>: "+json.data.lastx[x][1];
                                    html+= "<label>ip</label>: "+json.data.lastx[x][0];

                                    passedtext = "<span style='color:red'>Failed Check</span>";
                                    if (json.data.lastx[x][2] == 1) {
                                        passedtext = "<span>Verified</span>";
                                    }
                                    html+= "<label>Status</label>: "+passedtext;
                                }
                            }
                            html += "</div>";
                        }
                    });
                    licensedefender.grid.enable();
                    return html;
                }
            },{
                id: "cb",
                dataIndex: "id",
                xtype: "checkbox"
            },{
                id: "domain",
                text: lang("Domain"),
                dataIndex: "domain",
                flex: 1,
                renderer: function(text, row) {
                    licensedefender.countofrecords++;
                    var attributes = '';
                    if (row.attributes) {
                      attributes = row.attributes.join(',');
                    }
                    return "<a id='license_"+licensedefender.countofrecords+"' onclick='licensedefender.editLicense(\""+row.domain+"\","+row.resellerid+","+row.is_owned+",\""+attributes+"\");'>"+row.domain+"</a>";
                }
            },{
                id: "is_owned",
                text: lang("Type"),
                dataIndex: "is_owned",
                align: 'center',
                width: 100,
                renderer: function(text, row) {
                    if ( row.is_owned == 1 ) {
                        return "Owned";
                    } else {
                        return "Leased";
                    }
                }
            },{
                id: "attributes",
                hidden: true,
                text: lang("Attributes"),
                dataIndex: "attributes",
                align: 'center',
                width: 150,
                renderer: function(text, row) {
                  if (!row.attributes || row.attributes.length == 0) {
                    return 'None';
                  }

                  var labelArr = [];
                  $.each(row.attributes, function(ix, val) {
                    switch (val) {
                      case 'plus':
                        labelArr.push('Plus');
                        break;
                      case 'nobranding':
                        labelArr.push('No-branding');
                        break;
                      // case 'logkeen':
                      //   labelArr.push('Log');
                      //   break;
                    }
                  });
                  return labelArr.join(', ');
                }
            },{
                id: "reseller",
                text: lang("Reseller"),
                dataIndex: "reseller",
                align: 'center',
                width: 200,
                renderer: function(text, row) {
                    if ( row.reseller == null ) {
                        return "<span style='color:gray;'>Not Applicable</span>";
                    } else {
                        return "<a style='cursor:pointer' title='Filter based on reseller: "+row.reseller+"' onclick='licensedefender.loadResellerLicenses(\""+row.reseller+"\");'>"+row.reseller+"</a>";
                    }
                }
            },{
                id: "status",
                text: lang("Status"),
                dataIndex: "Status",
                align: 'center',
                width: 100,
                renderer: function(text, row) {
                    if ( row.status == 1 ) {
                        return 'Active';
                    } else {
                        return 'Inactive';
                    }
                }
            }
        ]
    });
    licensedefender.grid.render();

    licensedefender.showResellerFilter = function() {
        // Show the reseller filter drop down and label.
        $('#resellerLabel').show();
        $('#resellerSelect').show();
    }

    licensedefender.removeAllFilters = function() {
        licensedefender.loggedinas = '';
        licensedefender.searchkey = '';
        $('#searchkey').text("");
        $('#searchlicense').val("");
        $('#loggedinas').text("");
        licensedefender.grid.reload({params:{start:0, loginas:licensedefender.loggedinas, searchkey:licensedefender.searchkey}});
        licensedefender.grid.reload();
        $('#addButtonText').text(lang('Add License(s)'));
        document.getElementById('usedlicenses').style.display = '';
    }

    licensedefender.loadResellerLicenses = function(reseller) {
        licensedefender.loggedinas = reseller;
        $('#loggedinas').text("("+licensedefender.loggedinas+")");
        licensedefender.grid.reload({params:{start:0, loginas:licensedefender.loggedinas}});
        var btnText = (licensedefender.loggedinas !="") ? lang('Add License(s) to')+" "+licensedefender.loggedinas : lang('Add License(s)');
        $('#addButton').text(btnText);
    }

    licensedefender.searchLicense = function() {

        var searchkey = $('#searchlicense').val();
        licensedefender.searchkey = trim(searchkey);
        licensedefender.grid.reload({params:{start:0, searchkey:licensedefender.searchkey}});
        if (licensedefender.searchkey =="") {
            $('#searchkey').text("");
            document.getElementById('usedlicenses').style.display = '';
            if ( licensedefender.loggedinas !="" ) {
                document.getElementById('totallicenses').style.display = '';
                document.getElementById('remaininglicenses').style.display = '';
            } else {
                document.getElementById('totallicenses').style.display = 'none';
                document.getElementById('remaininglicenses').style.display = 'none';
            }
        } else {
            $('#searchkey').html("searching <span style='color:orange;'>"+licensedefender.searchkey+"</span>");
            document.getElementById('usedlicenses').style.display = 'none';
            document.getElementById('totallicenses').style.display = 'none';
            document.getElementById('remaininglicenses').style.display = 'none';
        }
    }

    var licensePopup = function(title, action, olddomain, license, resellerid, isOwned, attributes) {
        var attributesArr = [];
        if (attributes) {
          attributesArr = attributes.split(',');
        }
        var content = '<div>'
          + '<input type="hidden" name="plugin" value="licensedefender">'
          + '<input type="hidden" name="pluginaction" value="' + action + '">'
          + '<input type="hidden" name="olddomain" value="' + olddomain + '">'
          + '<input type="hidden" name="loginas" value="' + licensedefender.loggedinas +'">'
          + '<input type="hidden" name="resellerid" value="' + resellerid +'">'
          + '<textarea name="licenses" placeholder="License Domain(s)">' + license + '</textarea>'
          + '</div>';
        if (isViewingAsAdmin) {
          content += '<div>'
          + '<label class="radio inline"><input type="radio" name="is_owned" value="0" ' + (isOwned != 1? 'checked' : '') + '> Leased</label>'
          + '<label class="radio inline"><input type="radio" name="is_owned" value="1" ' + (isOwned == 1? 'checked' : '') + '> Owned</label>'
          + '</div>'
          + '<div style="margin-top:10px">'
          + 'Attributes: '
          + '<select name="attributes[]" multiple class="input-large">'
          + '  <option value="plus" ' + (attributesArr.indexOf('plus') > -1? 'selected' : '') + '>Plus</option>'
          + '  <option value="nobranding" ' + (attributesArr.indexOf('nobranding') > -1? 'selected' : '') + '>No-branding</option>'
          // + '  <option value="logkeen" ' + (attributesArr.indexOf('logkeen') > -1? 'selected' : '') + '>Log</option>'
          + '</select>'
          + '</div>';
        }
        new RichHTML.window({
            type: 'prompt',
            title: title,
            content: content,
            showSubmit: true,
            actionUrl: 'index.php?fuse=admin&controller=plugin&action=doplugin&type=snapin',
            onSubmit: function (result) {
              ce.parseResponse(result);
              licensedefender.grid.reload();
            }
        }).show();
        clientexec.postpageload();
    };

    licensedefender.editLicense = function(license,resellerid,isOwned,attributes) {
        licensePopup('Edit License', 'updateLicense', license, license, resellerid, isOwned, attributes);
    }

    $('#addButton').click(function() {
      licensePopup('Add License', 'addLicense', '', '', '', 0, '');
    });

    $('#deleteButton').click(function() {
        if ($(this).attr('disabled')) return false;
        RichHTML.msgBox(lang('Are you sure you want to delete the selected license(s)'),
        {
            type:"confirm"
        }, function(result) {
            if(result.btn === lang("Yes")) {
                rows = licensedefender.grid.getSelectedRowData();
                $.each(rows, function(i, row) {
                    $.post("index.php?fuse=admin&controller=plugin&action=doplugin&type=snapin", {
                        plugin: 'licensedefender',
                        pluginaction : 'deleteDomains',
                        loginas : licensedefender.loggedinas,
                        resellerid: row.resellerid,
                        domain: row.domain
                    },
                    function(data){ });
                });
                licensedefender.grid.reload();
            }
        });
    });

    $('#resellerPanel-grid-filter').change(function(){
        licensedefender.grid.reload({params:{start:0,limit:$(this).val()}});
    });

    $('#resellerPanel-grid-filterbystatus').change(function(){
        licensedefender.loggedinas = $(this).val();

        if ( licensedefender.loggedinas == 'all' ) {
            licensedefender.loggedinas = '';
        }

    	licensedefender.grid.reload({params:{start:0,loginas:licensedefender.loggedinas}});

        var btnText = (licensedefender.loggedinas !="") ? lang('Add License(s) to')+" "+licensedefender.loggedinas : lang('Add License(s)');
        $('#addButtonText').text(btnText);
    });

    $(licensedefender.grid).bind({
        "load" : function(event,data) {
            $('#usedlicenses_count').text(data.paging.totalItems);

            licensedefender.permissions = data.jsonData.permissions;
            if($.inArray("showresellers",licensedefender.permissions) > -1) {
                licensedefender.showResellerFilter();
            }

            isViewingAsAdmin = false;
            if($.inArray("isGroup0",licensedefender.permissions) > -1) {
                //reseller add check for distributor as well
                isViewingAsAdmin = false;
                $('th[dataindex=attributes]').css('display', 'none');
                $('td[data-localid=attributes]').css('display', 'none');
            } else if($.inArray("isGroup1",licensedefender.permissions) > -1) {
                isViewingAsAdmin = true;
                $('th[dataindex=attributes]').css('display', 'table-cell');
                $('td[data-localid=attributes]').css('display', 'table-cell');
            }

            //enable reseller tab
            if ((isViewingAsAdmin) || (licensedefender.loggedinas!="")) {
                $('#tab_Resellers').show();
            }

            //cm = licensedefender.grid.getColumnModel();
            //totallicenses usedlicenses remaininglicenses
            if (((!isViewingAsAdmin) && (licensedefender.searchkey=="")) ) {
                //we are showing a reseller
                document.getElementById('totallicenses').style.display = '';
                document.getElementById('remaininglicenses').style.display = '';
                $('#totallicenses_count').text(data.jsonData.licensetotals.allotted);
                $('#remaininglicenses_count').text(parseInt(data.jsonData.licensetotals.allotted) - parseInt(data.jsonData.licensetotals.used));
                //hide reseller column
                // TODO Hide reseller colum, if grid can support this
                //cm.setHidden(cm.getIndexById('colReseller'), true);
            } else {
                //we are showing an admin
                $('#totallicenses').hide();
                $('#remaininglicenses').hide();
                //show reseller column
                // todo show reseller col if we can
                //cm.setHidden(cm.getIndexById('colReseller'), false);
            }
            $('#usedlicenses_count').text(data.jsonData.licensetotals.used);
        },
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#deleteButton').removeAttr('disabled');
            } else {
                $('#deleteButton').attr('disabled','disabled');
            }
        }
    });
});
