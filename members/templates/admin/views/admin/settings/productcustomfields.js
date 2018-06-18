var productCustomFields = {
    divDropDownOptions: $('#divDropDownOptions')
};

productCustomFields.form = new CustomFieldsSetup({
    fieldBelongsTo: 'package',
    fieldName: 'customfieldname',
    inputs: {
        customfieldname:            $('#inputFieldName'),
        customfielddesc:            $('#textareaFieldDescription'),
        customfieldtype:            {
            element: $('#selectFieldType'),
            bind: {
                'change': function(e) {
                    if ($(this).val() == 9) { // typeDROPDOWN
                        productCustomFields.divDropDownOptions.slideDown();
                    } else {
                        productCustomFields.divDropDownOptions.slideUp();
                    }
                }
            }
        },
        customfieldoptions:         $('#inputDropDownOptions'),
        customfieldrequired:        $('#inputIsRequired'),
        customfieldadminonly:       $('#inputAdminOnly'),
        includetoproductidentifier: $('#inputProductIdentifier'),
        customfieldsignup:          $('#inputIncludeInSignup'),
        customfieldshowingridadmin: $('#selectShowInGridAdmin'),
        customfieldshowingridportal:$('#selectShowInGridPortal'),
        globalCustomField:          $('#inputAllTypes'),
        associatedList:             $('#divTypesAssociated')
    }
});

$(function() {
    $('#inputAdminOnly').change(function() {
        if ($(this).prop('checked')) {
            $('#selectShowInGridPortal').attr('checked', false);
            $('label[for=selectShowInGridPortal]').hide();
            $('#selectShowInGridPortal').hide();
        } else {
            $('label[for=selectShowInGridPortal]').show();
            $('#selectShowInGridPortal').show();
        }
    });
});
