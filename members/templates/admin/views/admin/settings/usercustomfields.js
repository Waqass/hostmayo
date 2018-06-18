var userCustomFields = {
    divDropDownOptions:         $('#divDropDownOptions')
};

userCustomFields.form = new CustomFieldsSetup({
    fieldBelongsTo: 'profile',
    fieldName: 'customfieldname',
    inputs: {
        customfieldname:            $('#inputFieldName'),
        customfielddesc:            $('#textareaFieldDescription'),
        customfieldtype:            {
            element: $('#selectFieldType'),
            bind: {
                'change': function(e) {
                    if ($(this).val() == 9) { // typeDROPDOWN
                        userCustomFields.divDropDownOptions.slideDown();
                    } else {
                        userCustomFields.divDropDownOptions.slideUp();
                    }
                }
            }
        },
        customfieldoptions:         $('#inputDropDownOptions'),
        customfieldrequired:        $('#inputIsRequired'),
        customfieldadminonly:       {
            element: $('#inputAdminOnly'),
            bind: {
                'change': function(e) {
                    if ($(this).prop('checked')) {
                        e.data.inputs.customfieldreadonly.prop({
                            checked: false,
                            disabled: true
                        });
                    } else {
                        e.data.inputs.customfieldreadonly.prop('disabled', false);
                    }
                }
            }
        },
        customfieldshowingridadmin: $('#selectShowInGridAdmin'),
        customfieldreadonly:        $('#inputReadOnly'),
        customfieldsignup:          $('#inputIncludeInSignup'),
        customfieldadminprofile:    $('#inputInludeInAdmin'),
        customfieldcustomerprofile: $('#inputIncludeInCustomer')
    }
});

$(document).ready(function(){
    $('#selectFieldType').select2({
        width: 'resolve',
        minimumResultsForSearch: 10,
        formatResult: function(object, container) {
            if (object.element[0].getAttribute('disabled') !== null) {
                container.addClass('select2-disabled');
            } else {
                return object.text;
            }
        }
    });
});
