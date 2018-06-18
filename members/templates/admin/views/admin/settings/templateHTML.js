$(document).ready(function() {
  templateOptions.template = "{{#arr}}<dt class='configsetting_name'><label>{{name}}</label></dt><dd class='configsetting_desc full desc' style='border-bottom:0px;'><span>{{description}}</span></dd>{{#input}}<dd class='full configsetting_values'><input id='value_{{name}}' name='value_{{name}}' value='{{html}}' /></dd>{{/input}}{{^input}}<dd class='configsetting_values full textarea'><textarea id='value_{{name}}' name='value_{{name}}' cols='' rows='20'>{{html}}</textarea></dd>{{/input}}{{/arr}}";
  $('#templateselector').bind('change', function(){

     $('#updatesettingbtn').hide();
     $('.configsetting_name, .configsetting_values, .configsetting_desc').remove();

     var nameOfTemplate = $(this).val();
     if (templateOptions.defaultTemplate == nameOfTemplate) {
         $('#setDefault option[value="yes"]').attr('selected','selected');
     } else {
         $('#setDefault option[value="no"]').attr('selected','selected');
     }

     //lets get this via ajax instead of populating array
     $.ajax({
         url: 'index.php?action=getTemplateCustomHTML&fuse=admin',
         type: 'GET',
         data: {'templateName':nameOfTemplate},
         success: function (data) {
             if (data.error) return;
             $('#updatesettingbtn').show();
             if((typeof(data.customizations)!=="undefined") && (data.customizations.length > 0)){
                var html = Mustache.to_html(templateOptions.template, {arr:data['customizations']});
                $('dl.form').append(html);
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
                    if (!json.error)
                    {
                        $('#updatesettingbtn').button('reset');
                        ce.msg("Settings Updated");
                    }
	      }
	    } );
    }
    return false;
  });

});
