var accounts = {};

$(document).ready(function () {

  accounts.grid = new RichHTML.grid({
    el: 'Accounts-grid',
    root: 'data',
    baseParams: {sort: 'dateActivated', dir: 'asc', limit:100},
    url: 'index.php?fuse=clients&action=getpendingorderslist',
    columns: [{
      dataIndex:  'pendingpackageid',
      xtype:      'checkbox',
    }, {
      id:         'date',
      text:     'Date',
      width:      100,
      sortable: true,
      dataIndex: 'dateActivated',
      renderer: function (text, row) {
        var date = text.split(' ');
        return date[0] + "<br/><span style='font-size:smaller;'>" + date[1] + '</span>';
      },

      align:      'right',
    }, {
    id:         'customername',
    text:     'Name',
    renderer:   function (text, row) {
      return String.format('<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID={0}">{1}</a><br/><span style="font-size:smaller;"><a href="index.php?fuse=clients&controller=userprofile&view=profileproduct&groupinfo&id={3}&frmClientID={0}">{2}</a></span>', row.pendingpackageuserid, text, row.pendingpackagereference, row.pendingpackageid);
    },

    hidden:     false,
    flex:1,
    dataIndex:  'pendingpackagecustomername',
  }, {
    id:         'name',
    text:     'Pkg Name',
    width:      150,
    dataIndex:  'pendingpackagename',
    align:      'center',
  }, {
    id:         'status',
    text:     'Status',
    dataIndex:  'packagepaidstatus',
    width:      130,
    align:      'center',
  },

],
  });
  accounts.grid.render();

  // **** listeners to grid
  $(accounts.grid).bind({
    rowselect: function (event, data) {
      if (data.totalSelected > 0) {
        $('#activateAccount').removeAttr('disabled');
        $('#delAccount').removeAttr('disabled');
      } else {
        $('#activateAccount').attr('disabled', 'disabled');
        $('#delAccount').attr('disabled', 'disabled');
      }
    },
  });

  // **** lets bind our buttons
  $('#activateAccount').click(function () {
    if ($(this).attr('disabled')) { return false; }

    var html = lang('Are you sure you want to activate the selected packages(s)');
    html += "<br/><div style='padding-top:8px;'><input type='checkbox' name='useplugin' id='useplugin' checked='checked'/> <span style='border-bottom: 1px solid #DFDFDF;cursor:help;' title='Activate the selected accounts then trigger the create action from their respective plugin.  Warning: This will also register any domains you are activating.'>Use their respective plugins?</span></div>";
    RichHTML.msgBox(html, {
      type:'yesno',
    }, function (result) {
      var usepackageplugin = 0;
      var useregistrarplugin = 0;
      if (typeof (result.elements.useplugin) !== 'undefined') {
        usepackageplugin = 1;
        useregistrarplugin = 1;
      }

      if (result.btn === lang('Yes')) {
        RichHTML.mask();
        $.post('index.php?controller=packages&action=activateclientpackages&fuse=clients', {
          domainids: accounts.grid.getSelectedRowIds(),
          usepackageplugin: usepackageplugin,
          useregistrarplugin: useregistrarplugin,
        }, function (data) {
          if (data.error == true) {
            RichHTML.msgBox(data.message, {type: 'error'});
          }

          accounts.grid.reload({params:{start:0}});
          RichHTML.unMask();
        }, 'json');
      }

    });
  });

  $('#delAccount').click(function () {
    if ($(this).attr('disabled')) { return false; }

    var html = lang('Are you sure you want to delete the selected packages(s)');
    RichHTML.msgBox(html, {
      type:'yesno',
    }, function (result) {
      if (result.btn === lang('Yes')) {
        RichHTML.msgBox(lang('Do you want to delete this customer if they have no packages?'), {
          type:'yesno',
        }, function (innerResult) {

          deleteCustomer = 0;
          if (innerResult.btn === lang('Yes')) {
            deleteCustomer = 1;
          }

          RichHTML.mask();
          $.post('index.php?action=deleteclientpackages&controller=packages&fuse=clients', {
            domainids: accounts.grid.getSelectedRowIds(),
            usedeletecustomer: deleteCustomer,
          }, function (data) {
            if (data.error == true) {
              RichHTML.msgBox(data.message, {type: 'error'});
            }

            accounts.grid.reload({params:{start:0}});
            RichHTML.unMask();
          }, 'json');
        });
      }

    });
  });
});
