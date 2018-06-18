var emailRule = emailRule || {};
$.extend(true, emailRule, {
    dom: {
        selectRoutingType: $('#select-routingType'),
        inputPipingEmails: $('#input-pipingEmails'),
        inputPop3Emails: $('#input-pop3Emails'),
        selectUserType: $('#select-userType'),
        divPublicSection: $('#div-publicSection'),
        divEmailPipeForwarding: $('#div-emailPipeForwarding'),
        divEmailPOP3Fetching: $('#div-emailPOP3Fetching'),
        divSelectRegisteredGroups: $('#div-selectRegisteredGroups'),
        inputTargetGroups: $('#input-targetGroups'),
        inputAutoresponderTemplateId: $('#input-autoresponderTemplateId'),
        inputSendCopyTo: $('#input-sendCopyTo'),
        inputOpenTicket: $('#input-openTicket'),
        divOpenTicketOptions: $('#div-openTicketOptions'),
        selectTargetPriority: $('#select-targetPriority'),
        inputTargetType: $('#input-targetType'),
        inputTargetAssignee: $('#input-targetAssignee'),
        formEmailRule: $('#form-emailRule'),
        buttonSaveChanges: $('#button-saveChanges')
    },
    flags: {
        lockTicketPriorityAndType: false
    }
});

$(document).ready(function(){

    $(emailRule.dom.inputPipingEmails)
    .add(emailRule.dom.inputPop3Emails)
    .add(emailRule.dom.inputSendCopyTo)
    .select2({
        multiple: true,
        allowclear: true,
        tags: [],
        width: 'resolve',
        tokenSeparators: [',', ' ']
    });

    emailRule.dom.selectUserType.change(function(){
        if ($(this).val() == '4') {
            emailRule.dom.divSelectRegisteredGroups.show();
        } else {
            emailRule.dom.divSelectRegisteredGroups.hide();
        }
    }).trigger('change');

    emailRule.dom.inputTargetGroups.select2({
        ajax: {
            url: 'index.php?fuse=support&action=GetGroupList',
            dataType: 'json',
            data: function() { return {} },
            results: function(data, page) {
                var list = [];
                $.each(data.groups, function(key, value) {
                    list.push({id: value.groupId, text: value.groupName});
                });
                return {results: list};
            }
        },
        multiple: true,
        width: '100%',
        placeholder: lang('Select Groups')
    }).select2('data', emailRule.initial.targetGroups);

    emailRule.dom.inputAutoresponderTemplateId.select2({
        formatResult: function(object, container) {
            return object.templateName;
        },
        formatSelection: function (object, container) {
            return object.templateName;
        },
        ajax: {
            url: 'index.php?fuse=admin&controller=emails&action=getemailtemplates',
            dataType: 'json',
            data: function() { return {} },
            results: function(data, page) {
                data.templates.unshift({id: 0, templateName: 'None'});
                return {results: data.templates};
            }
        },
        minimumResultsForSearch: 999,
        multiple: false,
        placeholder: lang('Select Autoresponder Template')
    });
    if (emailRule.initial.autoresponderTemplate.id != 0) {
        emailRule.dom.inputAutoresponderTemplateId.select2('data', emailRule.initial.autoresponderTemplate);
    } else {
        emailRule.dom.inputAutoresponderTemplateId.select2('data', {id: 0, templateName: 'None'});
    }

   emailRule.dom.inputTargetType.select2({
	  formatResult: function(object) {
            return '<div class="'+object.indentClass+'">'+object.text+'</div>';
        },
        ajax: {
            url: 'index.php?fuse=support&action=gettickettypes&controller=tickettype&simple=1',
            dataType: 'json',
            data: function() { return {} },
            results: function(data, page) {
                var list = [];
                $.each(data.tickettypes, function(key, value) {
                    if (value.ticketTypeId == 0) { return true; };
                    list.push({id: value.ticketTypeId, text: value.ticketTypeName});
                });
                return {results: list};
            }
        },
		 initSelection: function (element, callback) {
            var val = element.val();
            $.get('index.php?fuse=support&action=gettickettypes&controller=tickettype&simple=1',
			{}, function(response) {
				 $.each(response.tickettypes, function(key, value) {
					if (value.ticketTypeId == val) {
						emailRule.dom.inputTargetType.select2('data', { id: val, text: value.ticketTypeName });
                        return false;
                    }
                });
            });
        },
        minimumResultsForSearch: -1,
        multiple: false,
        width: 'resolve',
        placeholder: lang('Select Ticket Type')
    });
    if (emailRule.initial.targetType.id != 0) {
        emailRule.dom.inputTargetType.select2('data', emailRule.initial.targetType);
    }

    emailRule.dom.inputTargetAssignee.select2({
        formatResult: function(object) {
            return '<div class="'+object.indentClass+'">'+object.text+'</div>';
        },
        ajax: {
            url: 'index.php?fuse=support&controller=department&action=listdepartment&getDepartmentsWithMembers=1&additionalOptions=3,1',
            dataType: 'json',
            data: function() { return {} },
            results: function(data, page) {
                var list = [];
                $.each(data.groups, function(key, value) {
                    list.push({id: value.assigneeId, text: value.assigneeLabel, indentClass: value.indentClass});
                });
                return {results: list};
            }
        },
        initSelection: function (element, callback) {
            var val = element.val();
            $.get('index.php?fuse=support&controller=department&action=listdepartment&getDepartmentsWithMembers=1&additionalOptions=3,1', {}, function(response) {
                $.each(response.groups, function(key, value) {
                    if (value.assigneeId == val) {
                        emailRule.dom.inputTargetAssignee.select2('data', { id: val, text: value.assigneeLabel });
                        return false;
                    }
                });
            });
        },
        minimumResultsForSearch: 999,
        multiple: false,
        width: 'resolve',
        placeholder: lang('Select Ticket Assignee')
    });

    emailRule.dom.selectRoutingType.change(function(){
        $(emailRule.dom.divEmailPipeForwarding)
        .add(emailRule.dom.divEmailPOP3Fetching)
        .add(emailRule.dom.divPublicSection)
        .hide();
        switch ($(this).val()) {
            case '1':
                emailRule.dom.divEmailPipeForwarding.show();
                //emailRule.dom.selectTargetPriority.select2('val', 2).select2('enable');
                emailRule.dom.inputTargetType.select2('data', null).select2('enable');
                emailRule.flags.lockTicketPriorityAndType = false;
                break;
            case '2':
                emailRule.dom.divEmailPOP3Fetching.show();
                // emailRule.dom.selectTargetPriority.select2('val', 2).select2('enable');
                emailRule.dom.inputTargetType.select2('data', null).select2('enable');
                emailRule.flags.lockTicketPriorityAndType = false;
                break;
            case '3':
                emailRule.dom.divPublicSection.show();
                //emailRule.dom.selectTargetPriority.select2('data', { id: 0, text: lang('Selected by customer') }).select2('disable');
                emailRule.dom.inputTargetType.select2('data', { id: 0, text: lang('Selected by customer') }).select2('disable');
                emailRule.flags.lockTicketPriorityAndType = true;
                break;
        }
    }).change();

    emailRule.dom.inputOpenTicket.change(function(){
        if ($(this).prop('checked')) {
            emailRule.dom.divOpenTicketOptions.show();
        } else {
            emailRule.dom.divOpenTicketOptions.hide();
        }
    }).trigger('change');

    emailRule.dom.buttonSaveChanges.click(function(e){
        $.ajax({
            url: 'index.php?fuse=support&controller=routing&action=save',
            type: 'POST',
            data: emailRule.dom.formEmailRule.serialize(),
            success: function(response) {
                ce.parseResponse(response);
                if (response.success) {
                    setTimeout(function(){
                        window.location.href = 'index.php?fuse=admin&view=emailrouting&controller=settings&settings=support';
                    }, 3000);
                }
            }
        });
        e.preventDefault();
    });

});
