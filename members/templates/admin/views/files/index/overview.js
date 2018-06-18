var files = {
    inputName:              $('#inputName'),
    inputDesc:              $('#inputDesc'),
    inputNotes:             $('#inputNotes'),
    inputPublic:            $('#inputPublic'),
    inputLoggedIn:          $('#inputLoggedin'),
    inputUsersSelect:       $('#inputUsersSelect'),
    inputClientTypesSelect: $('#inputClientTypesSelect'),
    inputServersSelect:     $('#inputServersSelect'),
    inputStatusSelect:      $('#inputStatusSelect'),
    divInfoLoadingScreen:   $('#divInfoLoadingScreen'),
    divFileDetails:         $('#divFileDetails'),
    aFileLink:              $('#download-link'),
    spanDownloads:          $('#spanDownloads'),
    buttonSave:             $('#buttonSave'),
    buttonDelete:           $('#buttonDelete'),
    fileID:                 0,
    fileList:               {},
    selectedNode:           {},
    parentNode:             {},
    fileUploadWindow:       {},
    createDir:              false,
    divEntryInfo:           $('#divEntryInfo'),
    fileUploadQueueTemplate:$('.fileupload-queue-template'),
    fileQueueObj:           {},
    progressWindowShown:    false,
    mainContainer:          $('.maincontainer'),

    _formatFileSize:        function (bytes) {
        if (typeof bytes !== 'number') {
            return '';
        }
        if (bytes >= 1000000000) {
            return (bytes / 1000000000).toFixed(2) + ' GB';
        }
        if (bytes >= 1000000) {
            return (bytes / 1000000).toFixed(2) + ' MB';
        }
        return (bytes / 1000).toFixed(2) + ' KB';
    },

    _formatBitrate: function (bits) {
        if (typeof bits !== 'number') {
            return '';
        }
        if (bits >= 1000000000) {
            return (bits / 1000000000).toFixed(2) + ' Gbit/s';
        }
        if (bits >= 1000000) {
            return (bits / 1000000).toFixed(2) + ' Mbit/s';
        }
        if (bits >= 1000) {
            return (bits / 1000).toFixed(2) + ' kbit/s';
        }
        return bits + ' bit/s';
    },

    _formatTime: function (seconds) {
        var date = new Date(seconds * 1000),
            days = parseInt(seconds / 86400, 10);
        days = days ? days + 'd ' : '';
        return days +
            ('0' + date.getUTCHours()).slice(-2) + ':' +
            ('0' + date.getUTCMinutes()).slice(-2) + ':' +
            ('0' + date.getUTCSeconds()).slice(-2);
    },

    _formatPercentage: function (floatValue) {
        return (floatValue * 100).toFixed(2) + ' %';
    },

    getFileInfo:       function (fileID, data) {

        $.ajax({
            url: 'index.php?fuse=files&action=getinfo',
            data: { id: fileID },
            success: function(response) {
                response = response.response;
                files.inputName.val(response.name);
                if (fileID == 0) {
                    files.inputName.prop('disabled', true);
                    files.buttonDelete.prop('disabled', true);
                } else {
                    files.inputName.prop('disabled', false);
                    files.buttonDelete.prop('disabled', false);
                }
                files.inputDesc.val(response.desc);
                files.inputNotes.val(response.notes);
                switch (response.permissions.public) {
                    case 'false': files.inputPublic.prop('checked', false); files.inputPublic.prop('disabled', false); break;
                    case 'inherited': files.inputPublic.prop('checked', true); files.inputPublic.prop('disabled', true); break;
                    case 'true': files.inputPublic.prop('checked', true); files.inputPublic.prop('disabled', false); break;
                }
                switch (response.permissions.loggedin) {
                    case 'false': files.inputLoggedIn.prop('checked', false); files.inputLoggedIn.prop('disabled', false); break;
                    case 'inherited': files.inputLoggedIn.prop('checked', true); files.inputLoggedIn.prop('disabled', true); break;
                    case 'true': files.inputLoggedIn.prop('checked', true); files.inputLoggedIn.prop('disabled', false); break;
                }
                files.inputClientTypesSelect.select2('data', response.permissions.clientTypes);
                files.inputUsersSelect.select2('data', response.permissions.users);
                files.inputServersSelect.select2('data', response.permissions.servers);
                files.inputStatusSelect.select2('data', response.permissions.status);
                if (!response.hash) {
                    files.divFileDetails.hide();
                } else {
                    files.divFileDetails.show();
                    files.spanDownloads.html(response.downloads);
                }
                if (data.rslt.obj.find('li').length > 0) {
                    files.buttonDelete.prop('disabled', true);
                } else {
                    files.buttonDelete.prop('disabled', false);
                }
                if (files.mainContainer.scrollTop() > 0) {
                    files.divEntryInfo.animate({top: files.mainContainer.scrollTop() + 'px'}, 1000);
                } else {
                    files.divEntryInfo.animate({top: '0'}, 1000);
                }
                files.hideLoading();
            }
        });


    },

    showLoading:       function() {

        files.divEntryInfo.show();
        var eio = files.divEntryInfo.position();
        files.divInfoLoadingScreen.css({
            top:     eio.top,
            left:    eio.left,
            height:  files.divEntryInfo.outerHeight(true)+"px",
            width:   files.divEntryInfo.outerWidth(true)+"px",
            display: 'block'
        });

    },

    hideLoading:       function() {
        files.divInfoLoadingScreen.hide();
    },

    progressWindow:    new RichHTML.window({
        height:     '450',
        width:      '400',
        el:         'divUploadWindow',
        title:      lang('Upload Progress')
    }),

    filterDisabled:    function (selected) {

        var selectedFiltered = [];
        for (var i = 0; i < selected.length; i++) {
            if (selected[i].disabled == false) {
                selectedFiltered.push(selected[i].id);
            }
        }

        return selectedFiltered;

    }

}

$(document).ready(function(){

    files.aFileLink.click(function(e) {
        e.stopImmediatePropagation();
    });

    $('#divEntryInfo .nav-tabs span').click(function(){
        $(this).tab('show');
    })

    $('#fileList').on('loaded.jstree.init', function(e, data) {
        files.fileList = $.jstree._reference('#fileList');
        files.fileList.select_node('#rootNode');
        $(this).off('loaded.jstree.init');
    }).jstree({
        plugins: ['ui', 'json_data', 'themes', 'sort', 'crrm', 'dnd'],
        core: {
            initially_open: [ 'rootNode' ],
            load_open: true
        },
        themes: {
            theme: 'default',
            url:   '../templates/default/js/jquery-jstree/themes/default/style.css',
            dots:  true,
            icons: true
        },
        json_data: {
            data: {
                data: 'Root',
                attr: {
                    id:        'rootNode',
                    "data-id":   0,
                    "data-name": 'Root',
                    "data-type": 'dir'
                },
                state: 'closed'
            },
            ajax: {
                url: 'index.php?fuse=files&action=getchildren',
                data: function(n) {
                    return { id : n.attr ? n.attr('data-id') : 0 };
                }
            }
        },
        ui: {
            'select_limit': 1
        },
        sort: function(a, b) {
            var dataA = this._get_node(a).data();
            var dataB = this._get_node(b).data();
            if (dataA.type == dataB.type) {
                if (dataA.name > dataB.name) {
                    return 1;
                } else {
                    return -1;
                }
            } else if (dataA.type > dataB.type) {
                return 1;
            } else {
                return -1;
            }
        },
        crrm: {
            move: {
                check_move: function(m) {
                    if (m.r.data().type == 'dir') {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }
    }).on('select_node.jstree', function(e, data) {
        files.showLoading();
        data.inst.open_node(data.rslt.obj);
        files.selectedNode = files.fileList.get_selected();
        files.parentNode = files.fileList._get_parent(files.selectedNode);
        files.fileID = files.selectedNode.data().id;
        files.getFileInfo(files.fileID, data);
        return data;
    }).on('create.jstree', function(e, data) {
        if (files.createDir) {
            files.createDir = false;
            $.ajax({
                url: 'index.php?fuse=files&action=adddirectory',
                type: 'POST',
                data: {
                    parentID: data.rslt.parent.data().id,
                    name:     data.rslt.name
                },
                success: function(response) {
                    if (response.success) {
                        data.rslt.obj.data({
                            id:   response.response.id,
                            name: data.rslt.name,
                            type: 'dir'
                        });
                    } else {
                        $.jstree.rollback(data.rlbk);
                    }
                    ce.parseActionResponse(response);
                }
            });
        }
        return data;
    }).on('move_node.jstree', function(e, data) {
        $.ajax({
           url: 'index.php?fuse=files&action=moveitem',
           type: 'POST',
           data: {
               fileID:      data.rslt.o.data().id,
               newParentID: data.rslt.r.data().id,
               fileName:    data.rslt.o.data().name
           },
           success: function(response) {
               ce.parseActionResponse(response);
               if (response.success) {
                   files.fileList.select_node(data.rslt.o, true);
               } else {
                   $.jstree.rollback(data.rlbk);
               }
           }
        });
        return data;
    }).on('hover_node.jstree', function(e, data) {
        if (data.rslt.obj.data().type == 'dir') { return; }
        data.rslt.obj.append(files.aFileLink.attr('href', 'index.php?fuse=files&view=serve&id='+data.rslt.obj.data().id).show());
        return data;
    }).on('dehover_node.jstree', function(e, data) {
        files.aFileLink.hide();
        return data;
    });

    $('#inputUsersSelect').select2({
        minimumInputLength: 2,
        multiple: true,
        placeholder: 'Visible to all Users',
        ajax: {
            url: 'index.php?fuse=files&action=getusers',
            dataType: 'json',
            data: function (term, page) {
                return {
                    q: term
                }
            },
            results: function(data, page) {
                return { results: data }
            }
        },
        dropdownCssClass: 'select2-drop-focused'
    }).on('focus', function() {
        $('#s2id_inputUsersSelect').addClass('select2-container-focused');
    }).on('blur', function() {
        $('#s2id_inputUsersSelect').removeClass('select2-container-focused');
    });

    $('#inputClientTypesSelect').select2({
        multiple: true,
        placeholder: 'Visible to all Client Types',
        data: allClientTypes,
        dropdownCssClass: 'select2-drop-focused'
    }).on('focus', function() {
        $('#s2id_inputClientTypesSelect').addClass('select2-container-focused');
    }).on('blur', function() {
        $('#s2id_inputClientTypesSelect').removeClass('select2-container-focused');
    });

    $('#inputServersSelect').select2({
        multiple: true,
        placeholder: 'Visible to all Servers',
        data: allServers,
        dropdownCssClass: 'select2-drop-focused'
    }).on('focus', function() {
        $('#s2id_inputServersSelect').addClass('select2-container-focused');
    }).on('blur', function() {
        $('#s2id_inputServersSelect').removeClass('select2-container-focused');
    });

    $('#inputStatusSelect').select2({
        multiple: true,
        placeholder: 'Visible to all Statuses',
        data: allStatuses,
        dropdownCssClass: 'select2-drop-focused'
    }).on('focus', function() {
        $('#s2id_inputStatusSelect').addClass('select2-container-focused');
    }).on('blur', function() {
        $('#s2id_inputStatusSelect').removeClass('select2-container-focused');
    });

    files.buttonSave.click(function(){
        files.showLoading();
        var formData = {
            id:          files.fileID,
            name:        files.inputName.val(),
            desc:        files.inputDesc.val(),
            notes:       files.inputNotes.val(),
            loggedin:    !files.inputLoggedIn.prop('disabled') && files.inputLoggedIn.prop('checked') ? true : false,
            public:      !files.inputPublic.prop('disabled') && files.inputPublic.prop('checked') ? true : false,
            clientTypes: files.filterDisabled(files.inputClientTypesSelect.select2('data')),
            users:       files.filterDisabled(files.inputUsersSelect.select2('data')),
            servers:     files.filterDisabled(files.inputServersSelect.select2('data')),
            status:      files.filterDisabled(files.inputStatusSelect.select2('data'))
        }
        $.ajax({
           url: 'index.php?fuse=files&action=saveinfo',
           type: 'POST',
           data: formData,
           success: function(response) {
               ce.parseActionResponse(response);
               if (files.selectedNode.data().type != 'dir') {
                   files.selectedNode.attr('rel', response.newRel);
               }
               files.fileList.rename_node(files.selectedNode, response.newName);
               files.getFileInfo(files.fileID);
               files.hideLoading();
           }
        });
        return false;
    });

    files.buttonDelete.click(function(){
        RichHTML.msgBox(
            'Are you sure you want to delete '+files.inputName.val()+'?',
            {
                type: 'confirm',
                buttons: {
                    button1: {
                        text: 'Yes'
                    },
                    button2: {
                        text: 'No',
                        type: 'cancel'
                    }
                }
            },
            function (result) {
                if (result.btn == lang('Yes')) {
                    files.showLoading();
                    $.ajax({
                        url: 'index.php?fuse=files&action=delete',
                        type: 'POST',
                        data: { id: files.fileID },
                        success: function(response) {
                            files.hideLoading();
                            if (response.success) {
                                files.fileList.delete_node(files.selectedNode);
                            } else {
                                ce.parseActionResponse(response);
                            }
                        }
                    });
                }
            }
        );
        return false;
    });

    $('#buttonAddDirectory').click(function(){
        files.createDir = true;
        if (files.selectedNode.data().type == 'dir') {
            files.fileList.create(files.selectedNode, 'inside');
        } else {
            files.fileList.create(files.parentNode, 'inside');
        }

        return false;
    });

    // Initialize the jQuery File Upload widget:
    $('#fileupload').fileupload({
        url: '../modules/files/upload_handler/index.php',
        singleFileUploads: true,
        progressall: function (e, data) {
            files.fileUploadProgressBar.width(parseInt(data.loaded / data.total * 100, 10)+'%');
            files.progressExtended.speed.html(files._formatBitrate(data.bitrate));
            files.progressExtended.time.html(files._formatTime((data.total - data.loaded) * 8 / data.bitrate));
            files.progressExtended.percent.html(files._formatPercentage(data.loaded / data.total));
            files.progressExtended.uploaded.html(files._formatFileSize(data.loaded)+' / '+files._formatFileSize(data.total));
        },
        progress: function(e, data) {
            $('#file-upload-queue-'+MD5(data.files[0].name)+' .bar').width(parseInt(data.loaded / data.total * 100, 10)+'%');
        }
    }).on('fileuploadadd', function(e, data) {
        $('#buttonAddFile').tooltip('hide');
        if (!files.progressWindowShown) {
            files.progressWindow.show();
            files.fileUploadProgressBar = $('.window-description-elements .fileupload-progress .progress .bar');
            files.fileUploadProgressData = $('.window-description-elements .fileupload-progress .progress-extended');
            files.fileUploadQueue = $('.window-description-elements .fileupload-queue');
            files.progressExtended = {
                speed: $('.window-description-elements .fileupload-progress .progress-extended-speed'),
                time: $('.window-description-elements .fileupload-progress .progress-extended-time'),
                percent: $('.window-description-elements .fileupload-progress .progress-extended-percent'),
                uploaded: $('.window-description-elements .fileupload-progress .progress-extended-uploaded')
            }
            files.progressWindowShown = true;
        }
        $.each(data.files, function(i, elm) {
            files.fileQueueObj = files.fileUploadQueueTemplate.clone().removeClass('fileupload-queue-template').attr('id', 'file-upload-queue-'+MD5(elm.name));
            files.fileQueueObj.find('.filename').html(elm.name);
            files.fileQueueObj.find('.filesize').html(files._formatFileSize(elm.size));
            files.fileUploadQueue.append(files.fileQueueObj.css('display', 'block'));
        });
    }).on('fileuploadstop', function(e, data) {
        files.progressWindow.hide();
        files.progressWindowShown = false;
    }).on('fileuploaddone', function(e, data) {
        $('#file-upload-queue-'+MD5(data.files[0].name)).fadeOut();
        var currentParent = files.selectedNode.data().type == 'dir' ? files.selectedNode : files.parentNode;
        $.ajax({
            url: 'index.php?fuse=files&action=addfile',
            type: 'POST',
            data: {
                fileInfo: data.result,
                parentID: currentParent.data().id
            },
            success: function(response) {
                files.processAddFile(response, currentParent);
            }
        });
    });

});

files.processAddFile = function(response, currentParent) {
    ce.parseActionResponse(response);
    $.each(response.response, function(index, file){
        if (file.status == -1) {
            RichHTML.msgBox(
                '"'+file.name+'" already exists under '+file.path+'. Please enter a new name or delete the file.',
                {
                    type: 'prompt',
                    buttons: {
                        button1: {
                            text: 'Rename'
                        },
                        button2: {
                            text: 'Delete',
                            type: 'cancel'
                        }
                    }
                },
                function (result) {
                    if (result.btn == 'Rename') {
                        $.ajax({
                            url: 'index.php?fuse=files&action=addfile',
                            type: 'POST',
                            data: {
                                fileInfo: JSON.stringify([{ newName: result.elements.value, name: file.name, size: file.size, original: file.original }]),
                                parentID: currentParent.data().id
                            },
                            success: function (response) {
                                files.processAddFile(response, currentParent);
                            }
                        });
                    } else {
                        $.ajax({
                            url: 'index.php?fuse=files&action=removetemp',
                            type: 'POST',
                            data: {
                                fileName: file.name
                            }
                        })
                    }

                }
            );
        } else {
            files.fileList.create(
                currentParent,
                'inside',
                {
                    data: file.name,
                    attr: {
                        "data-id": file.id,
                        "data-name": file.name,
                        "data-type": "file",
                        "rel": file.rel
                    }
                },
                false,
                true
            );
        }
    });
}