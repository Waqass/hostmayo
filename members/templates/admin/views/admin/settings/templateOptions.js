$(document).ready(function() {
    templateOptions.template = "{{#arr}}<dt class='configsetting_name'><label>{{name}}</label></dt><dd class='configsetting_values'><select name='setting_{{name}}'>{{#values}}<option value='{{name}}' {{#selected}}selected='selected'{{/selected}}>{{name}}</option>{{/values}}</select></dd><dd class='configsetting_desc full desc'><span>{{description}}</span></dd>{{/arr}}";

    $('#templateselector').bind('change', function(){
         $('#updatesettingbtn').hide();
        $('.configsetting_name, .configsetting_values, .configsetting_desc').remove();

        var nameOfTemplate = $(this).val();
        if (templateOptions.defaultTemplate == nameOfTemplate) {
            $('#setDefault option[value="no"]').removeAttr('selected');
            $('#setDefault option[value="yes"]').attr('selected','selected');
            $("#setDefault").select2("val", "yes");
        } else {
            $('#setDefault option[value="no"]').attr('selected','selected');
            $('#setDefault option[value="yes"]').removeAttr('selected');
            $("#setDefault").select2("val", "no");
        }

        //lets get this via ajax instead of populating array
        $.ajax({
            url: 'index.php?action=getTemplateOptions&fuse=admin',
            type: 'GET',
            data: {'templateName':nameOfTemplate},
            success: function (data) {
                if (data.error) return;
                $('#updatesettingbtn').show();
                if((typeof(data.options)!=="undefined") && (data.options.length > 0)){
                    var html = Mustache.to_html(templateOptions.template, {arr:data['options']});
                    $('dl.form').append(html);
                    clientexec.postpageload('#sitesettings');
                } else {
                    $('dl.form').append("");
                }
            }
        });
    });

    $('#templateselector').trigger('change');

    $("#updatesettingbtn").click(function() {
        $('#updatesettingbtn').button('loading');
        if ($('#setDefault').val() == "yes") {
            templateOptions.defaultTemplate = $('#templateselector').val();
        }
        if (check($("#sitesettings").get(0),$("#sitesettings").get(0).elements.length,false)) {
            var contactForm = $("#sitesettings");
                $.ajax( {
                    url: contactForm.attr( 'action' ),
                    type: contactForm.attr( 'method' ),
                    data: contactForm.serialize(),
                    success: function (json){
                    json = ce.parseResponse(json);
                    if (!json.error) {
                        $('#updatesettingbtn').button('reset');
                        ce.msg("Settings Updated");
                    }
                }
            });
        }
        return false;
    });
});