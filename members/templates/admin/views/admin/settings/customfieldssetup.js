function CustomFieldsSetup(options) {
    'use strict';
    var that = this;

    this.settings = {
        fieldBelongsTo:   options.fieldBelongsTo || '',
        getFieldsUrl:     'index.php?fuse=admin&action=GetCustomFields',
        getFieldInfoUrl:  'index.php?fuse=clients&controller=customfields&action=getcustomfieldinfo',
        reorderFieldsUrl: 'index.php?fuse=admin&action=reordercustomfields',
        addFieldUrl:      'index.php?fuse=admin&action=addcustomfield',
        saveFieldUrl:     'index.php?fuse=admin&action=savecustomfield',
        deleteFieldUrl:   'index.php?fuse=admin&action=deletecustomfield'
    };
    $.extend(true, this.settings, options);
    if (this.settings.fieldBelongsTo === '') {
        alert('fieldBelongsTo was not set!');
        return;
    }

    this.dom = {
        divCustomFieldTree:       $('#divCustomFieldTree'),
        divCustomFieldInfo:       $('#divCustomFieldInfo'),
        buttonAdd:                $('#buttonAdd'),
        buttonSave:               $('#buttonSave'),
        buttonDelete:             $('#buttonDelete'),
        formCustomFieldInfo:      $('#formCustomFieldInfo'),
        inputCustomFieldID:       $('#inputCustomFieldID'),
        divLoading:               {}
    };
    this.inputs = {};
    for (var e in options.inputs) {
        if (options.inputs[e] instanceof jQuery) {
            this.inputs[e] = options.inputs[e];
        } else {
            this.inputs[e] = options.inputs[e].element;
            for (var b in options.inputs[e].bind) {
                this.inputs[e].bind(b, this, options.inputs[e].bind[b]);
            }
        }
    }

    this._init = function () {
        this._initializeTree();
        this._setHandlers();
        this.clearInputs();
    };

    function getcustomfieldinfo(customfieldid) {
        $.ajax({
            url: that.settings.getFieldInfoUrl,
            data: {
                customfieldid: customfieldid,
                type: that.settings.fieldBelongsTo
            },
            success: function(response) {

                //system fields shouldn't even show the fields that can not be changed
                if (response.data.customfieldchangable == 2) {
                    $('#fieldsetExtraInformation').hide();
                    $('#fs-tickettypeassociations').hide();

                    $('#fromplugin').html("");

                    $('#formCustomFieldInfo').append('<input type="hidden" class="unchangeable_customfield" name="customfieldtypevalue" value="'+response.data.customfieldtype+'" />');
                    $('#formCustomFieldInfo').append('<input type="hidden" class="unchangeable_customfield" name="customfieldoptions" value="'+response.data.customfieldoptions+'" />');
                    if (response.data.customfieldsignup == 1) $('#formCustomFieldInfo').append('<input class="unchangeable_customfield" type="hidden" name="customfieldsignup" value="'+response.data.customfieldsignup+'" />');
                    if (response.data.customfieldshowingridadmin == 1) $('#formCustomFieldInfo').append('<input class="unchangeable_customfield" type="hidden" name="customfieldshowingridadmin" value="'+response.data.customfieldshowingridadmin+'" />');
                    if (response.data.customfieldshowingridportal == 1) $('#formCustomFieldInfo').append('<input class="unchangeable_customfield" type="hidden" name="customfieldshowingridportal" value="'+response.data.customfieldshowingridportal+'" />');
                    if (response.data.customfieldadminprofile == 1) $('#formCustomFieldInfo').append('<input class="unchangeable_customfield" type="hidden" name="customfieldadminprofile" value="'+response.data.customfieldadminprofile+'" />');
                    if (response.data.customfieldcustomerprofile == 1) $('#formCustomFieldInfo').append('<input class="unchangeable_customfield" type="hidden" name="customfieldcustomerprofile" value="'+response.data.customfieldcustomerprofile+'" />');
                    if (response.data.customfieldrequired == 1) $('#formCustomFieldInfo').append('<input class="unchangeable_customfield" type="hidden" name="customfieldrequired" value="'+response.data.customfieldrequired+'" />');
                    if (response.data.customfieldadminonly == 1) $('#formCustomFieldInfo').append('<input class="unchangeable_customfield" type="hidden" name="customfieldadminonly" value="'+response.data.customfieldadminonly+'" />');
                    if (response.data.customfieldreadonly == 1) $('#formCustomFieldInfo').append('<input class="unchangeable_customfield" type="hidden" name="customfieldreadonly" value="'+response.data.customfieldreadonly+'" />');


                    $('label[for="inputIncludeInSignup"]').hide();
                    $('#inputIncludeInSignup').hide();
                    $('label[for="inputAdminOnly"]').hide();
                    $('#inputAdminOnly').hide();
                    // Full name and email are always available
                    if (response.data.customfieldtype == 63 || response.data.customfieldtype == 13) {
                        $('label[for="selectShowInGridAdmin"]').hide();
                        $('#selectShowInGridAdmin').hide();
                    } else {
                        $('label[for="selectShowInGridAdmin"]').show();
                        $('#selectShowInGridAdmin').show();
                    }
                    $('label[for="inputIsRequired"]').hide();
                    $('#inputIsRequired').hide();
                    $('label[for="inputReadOnly"]').hide();
                    $('#inputReadOnly').hide();
                    $('label[for="inputEncrypted"]').hide();
                    $('#inputEncrypted').hide();
                    $('label[for="inputAllTypes"]').hide();
                    $('#inputAllTypes').hide();



                } else {

                    //let's remove any possible hidden elements
                    if (response.data.usedbyplugin != "") {
                        $('#fromplugin').html("<div class='alert alert-warning'>"+lang("Custom field created by plugin:")+" <b>"+response.data.usedbyplugin+"</b></div>");
                    }else {
                        $('#fromplugin').html("");
                    }

                    $('.unchangeable_customfield').remove();
                    $('#fieldsetExtraInformation').show();
                    $('#fs-tickettypeassociations').show();
                    $('label[for="inputIsRequired"]').show();
                    $('#inputIsRequired').show();
                    $('label[for="inputIncludeInSignup"]').show();
                    $('#inputIncludeInSignup').show();
                    $('label[for="inputAdminOnly"]').show();
                    $('#inputAdminOnly').show();
                    $('label[for="selectShowInGridAdmin"]').show();
                    $('#selectShowInGridAdmin').show();
                    $('label[for="inputReadOnly"]').show();
                    $('#inputReadOnly').show();
                    $('label[for="inputEncrypted"]').show();
                    $('#inputEncrypted').show();
                    $('label[for="inputAllTypes"]').show();
                    $('#inputAllTypes').show();


                }

                for (var d in response.data) {
                    if (that.inputs.hasOwnProperty(d)) {
                        switch (that.inputs[d].get(0).tagName) {
                            case 'INPUT':
                                switch (that.inputs[d].attr('type')) {
                                    case 'text':
                                    case 'hidden':
                                        that.inputs[d]
                                            .val($('<div/>').html(response.data[d]).text())
                                            .change()
                                            .prop('disabled', response.data.customfieldchangable == 1 || (response.data.customfieldchangable == 2 && d == that.settings.fieldName) ? false : true)
                                        ;
                                        break;
                                    case 'checkbox':
                                        that.inputs[d]
                                            .prop('checked', response.data[d] ? true : false)
                                            .change()
                                            .prop('disabled', (response.data.customfieldchangable == 1 || response.data.customfieldchangable == 2)? false : true)
                                        ;
                                        break;
                                }
                                break;
                            case 'TEXTAREA':
                                that.inputs[d]
                                    .val(response.data[d])
                                    .change()
                                    .prop('disabled', response.data.customfieldchangable == 1 || (response.data.customfieldchangable == 2 && d == 'customfielddesc') ? false : true)
                                ;
                                break;
                            case 'SELECT':
                                that.inputs[d]
                                    .val(response.data[d])
                                    .change()
                                    .select2(response.data.customfieldchangable == 1 ? 'enable' : 'disable')
                                ;
                                break;
                            case 'DIV':
                                that.inputs[d].html(response.data[d]);
                                break;
                        }
                    }
                }
                that.dom.buttonSave.prop('disabled', false);
                that.dom.buttonDelete.prop('disabled', response.data.customfieldchangable == 1 ? false : true);
                that._hideLoading();
            }
        });
    }

    this._initializeTree = function () {
        this.dom.divCustomFieldTree.jstree({
            plugins: ['ui', 'json_data', 'themes', 'crrm', 'dnd'],
            core: {
                load_open: true
            },
            themes: {
                theme: 'default',
                url:   '../templates/default/js/jquery-jstree/themes/default/style.css',
                dots:  true,
                icons: true
            },
            json_data: {
                ajax: {
                    url: that.settings.getFieldsUrl,
                    data: {
                        type: that.settings.fieldBelongsTo
                    },
                    success: function(response) {
                        var list = [];
                        for (var i = 0; i < response.length; i++) {
                            list.push({data: $('<div/>').html(response[i].text).text(), attr: { 'data-id': response[i].id }});
                        }
                        return list;
                    }
                }
            },
            ui: {
                'select_limit': 1
            },
            crrm: {
                move: {
                    check_move: function(m) {
                        var p = this._get_parent(m.o);
                        if(!p) return false;
                        p = p == -1 ? this.get_container() : p;
                        if(p === m.np) return true;
                        if(p[0] && m.np[0] && p[0] === m.np[0]) return true;
                        return false;
                    }
                }
            },
            dnd: {
                drop_target: false,
                drag_target: false
            }
        }).on('select_node.jstree', function(e, data) {
            //$('#divCustomFieldInfo').show();
            $('#divCustomFieldInfo').css('display','inline-block');
            that._showLoading();
            that.treeSelectedNode = data.inst.get_selected();
            if (typeof(data.rslt.obj.attr('data-id')) == 'undefined') { return; }
            that.dom.inputCustomFieldID.val(data.rslt.obj.attr('data-id'));
            getcustomfieldinfo(data.rslt.obj.attr('data-id'));
            return data;
        }).on('move_node.jstree', function(e, data) {
            var newOrder = [];
            that.dom.divCustomFieldTree.find('.jstree-leaf').each(function(){
                newOrder.push($(this).attr('data-id'));
            });
            $.ajax({
                url: that.settings.reorderFieldsUrl,
                type: 'POST',
                data: {
                    type: that.settings.fieldBelongsTo,
                    fields: newOrder.join(',')
                },
                success: function(response) {
                    if (response.success) {
                        ce.msg(lang('Custom fields reordered successfully'));
                    } else {
                        ce.parseActionResponse(response);
                    }
                }
            });
            return data;
        });
        that.treeCustomFields = $.jstree._reference('#divCustomFieldTree');
    };

    this._setHandlers = function() {

        var that = this;

        this.dom.buttonAdd.click(function(){
            RichHTML.msgBox(
                lang('Enter the name of the new custom field'),
                {
                    type: 'prompt'
                },
                function(result) {
                    if (result.btn == lang('OK')) {
                        $.ajax({
                            url: that.settings.addFieldUrl,
                            type: 'POST',
                            data: {
                                type: that.settings.fieldBelongsTo,
                                customfieldname: result.elements.value
                            },
                            success: function(response) {
                                $('#divCustomFieldInfo').css('display','inline-block');
                                if (response.success) {
                                    var newLeaf = that.treeCustomFields.create(-1, 'last', { attr: { 'data-id': response.data.newFieldID }, data: result.elements.value }, null, true);
                                    that.treeCustomFields.select_node(newLeaf, true);
                                    ce.msg(result.elements.value + lang(' added to custom fields.'));
                                } else {
                                    ce.parseActionResponse(response);
                                }
                            }
                        });
                    }
                }
            );
        });

        this.dom.buttonSave.click(function(){
            $.ajax({
                url: that.settings.saveFieldUrl,
                type: 'POST',
                data: that.dom.formCustomFieldInfo.serialize() + '&type=' + that.settings.fieldBelongsTo,
                success: function(response) {
                    if (response.success) {
                        that.treeCustomFields.rename_node(that.treeSelectedNode, that.inputs[that.settings.fieldName].val());
                        ce.msg(that.inputs[that.settings.fieldName].val() + lang(' saved successfully.'));
                        getcustomfieldinfo(that.dom.inputCustomFieldID.val());
                    }
                }
            });
        });

        this.dom.buttonDelete.click(function(){
            RichHTML.msgBox(
                lang('Are you sure you want to delete ') + that.inputs[that.settings.fieldName].val() + ' ?',
                {
                    type: 'confirm',
                    buttons: {
                        confirm: {
                            text: lang('Yes')
                        },
                        cancel: {
                            text: lang('Cancel'),
                            type: 'cancel'
                        }
                    }
                },
                function(result) {
                    if (result.btn == lang('Yes')) {
                        $.ajax({
                            url: that.settings.deleteFieldUrl,
                            type: 'POST',
                            data: {
                                type: that.settings.fieldBelongsTo,
                                customfieldid: that.dom.inputCustomFieldID.val()
                            },
                            success: function(response) {
                                if (response.success) {
                                    that.treeCustomFields.delete_node(that.treeSelectedNode);
                                    ce.msg(lang(that.inputs[that.settings.fieldName].val() + ' successfully deleted.'));
                                    that.clearInputs();
                                    $('#divCustomFieldInfo').css('display','none');
                                } else {
                                    ce.parseActionResponse(response);
                                }
                            }
                        });
                    }
                }
            );
        });

    };

    this.clearInputs = function() {
        for (var e in this.inputs) {
            if (typeof this.inputs[e].get != 'function') { continue; }
            switch (this.inputs[e].get(0).tagName) {
                case 'INPUT':
                    switch (this.inputs[e].attr('type')) {
                        case 'text':
                        case 'hidden':
                            this.inputs[e].val(''); break;
                        case 'checkbox':
                            this.inputs[e].prop('checked', false); break;
                    }
                    break;
                case 'TEXTAREA':
                    this.inputs[e].val('');
                    break;
                case 'SELECT':
                    this.inputs[e].val(this.inputs[e].find('option:first').val()).change();
                    break;
                case 'DIV':
                    this.inputs[e].html('');
                    break;
            }
        }
    };

    this._showLoading = function() {
        this.dom.divLoading = $('<div></div>');
        var offset = this.dom.divCustomFieldInfo.offset();
        this.dom.divLoading.css({
            'background': 'url("../templates/default/img/loading.gif") no-repeat center center',
            'background-color': '#ffffff',
            opacity: '0.7',
            position: 'absolute',
            top: offset.top + 'px',
            left: (offset.left - Number(this.dom.divCustomFieldInfo.css('margin-left').slice(0,2))) + 'px',
            width: this.dom.divCustomFieldInfo.outerWidth(true) + 'px',
            height: this.dom.divCustomFieldInfo.outerHeight(true) + 'px'
        });
        this.dom.divCustomFieldInfo.append(this.dom.divLoading);
    };

    this._hideLoading = function() {
        this.dom.divLoading.remove();
    };

    this._init();

}
