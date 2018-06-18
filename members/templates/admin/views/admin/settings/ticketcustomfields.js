var ticketCustomFields = {
    divDropDownOptions:       $('#divDropDownOptions')
}

ticketCustomFields.form = new CustomFieldsSetup({
    fieldBelongsTo: 'tickettypes',
    fieldName: 'customfieldname',
    inputs: {
        customfieldname:          $('#inputFieldName'),
        customfielddesc:          $('#textareaFieldDescription'),
        customfieldtype:          {
            element: $('#selectFieldType'),
            bind: {
                'change': function(e) {
                    if ($(this).val() == 9) { // typeDROPDOWN
                        ticketCustomFields.divDropDownOptions.slideDown();
                    } else {
                        ticketCustomFields.divDropDownOptions.slideUp();
                    }
                }
            }
        },
        customfieldoptions:       $('#inputDropDownOptions'),
        customfieldrequired:      $('#inputIsRequired'),
        customfieldadminonly:     $('#inputAdminOnly'),
        customfieldencrypted:     $('#inputEncrypted'),
        globalCustomField:        $('#inputAllTypes'),
        associatedList:           $('#divTypesAssociated')
    }
});
