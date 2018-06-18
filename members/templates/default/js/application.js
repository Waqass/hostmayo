$(document).ready(function(){

    $('.language-selector').bind('click', function(){
        RichHTML.mask();
        $.get('index.php?lang=' + $(this).attr('data-language'), function(){
            window.location.reload();
        });
    });

    // disable the select2 boxes on mobile devices, so we just show HTML select boxes.
    if ( $('html').hasClass('mobile') ) {
        $('select').addClass('disableSelect2AutoLoad');
    }

    clientexec.postpageload();
});

if(typeof(RichHTML)!= "undefined"){
    $(RichHTML).bind({
        "postload": function (event, el) {
            $('.richtable table.small-table').remove();
            $('.richtable table:not("table.small-table")').stacktable({myClass:'small-table table-striped'});


            $('#paging-'+el.id).remove();//remove old paging

            //need to redo button styles
            if ($('#'+el.id).parent().find('.richgrid-pagenavi').length > 0) {

                var current, link_page, next_link;
                var wrapper = $("<ul>");
                var pages = $('#'+el.id+'-navigation .richgrid-pagenavi').attr('data-pages');
                var items_per_page = $('#'+el.id+'-navigation .richgrid-pagenavi').attr('data-items-per-page');
                var page_current = $('#'+el.id+'-navigation span.current').text();

                previous_link_class = "";
                if (page_current == 1) {
                    previous_link_class = "disabled";
                }
                previous_link = (page_current - 2) * items_per_page;
                wrapper.append($('<li data-'+el.id+'-link="'+previous_link+'" class="previous '+previous_link_class+'"><a><i class="icon-chevron-left"></i></a></li>'));
                for(var page = 1; page <= pages; page ++ ) {

                    link_page = (page - 1) * items_per_page;
                    current_class = "";

                    if (page_current == page) {
                        current_class = "active";
                        page_current == page
                        next_link_class = "";
                        if (page == pages) next_link_class = "disabled";
                        next_link = (page) * items_per_page;
                    }

                    current = $('<li data-'+el.id+'-link="'+link_page+'" class="'+current_class+'"><a>'+page+'</a></li>');
                    wrapper.append(current);

                }

                wrapper.append($('<li  data-'+el.id+'-link="'+next_link+'" class="next '+next_link_class+'"><a><i class="icon-chevron-right"></i></a></li>'));

                $('#'+el.id).parent().after($("<div id='paging-"+el.id+"' class='pagination ce-pagination'>").append(wrapper));

            }

        }
    });
}

/* pass parent filter you want to use if calling directly
 * Note: This is used if you want to call this method on dynamically
 * loaded content. Such as richhtml.window content or possibly expander on richhtml.grid */
clientexec.postpageload = function(parent)
{
    if (!parent) parent = 'body';

    $(parent).tooltip({
        html:    true,
        selector: '[data-toggle=tooltip]'
    });

    // New method of tooltips for any element data-toggle="tooltip" title="%message%" data-tooltip-placement-"%left|top|right|bottom%" (optional)
    $(parent).popover({
        trigger: 'hover',
        selector: '[data-toggle=popover-hover]'
    });

    // Initialize Select2 on all <select> elements
    $(parent+' select:not([data-format], .disableSelect2AutoLoad)').select2({
        minimumResultsForSearch: 10,
        width:'resolve',

        // It's the responsability of the view, not the select2 plugin, to escape stuff
        // because sometimes we'd like to have HTML in the options, sometimes not.
        // Select2 does some bad escaping anyway, so these 3 directives fix it.
        formatResult: function(o) {
            return $(o.element[0]).html();
        },
        escapeMarkup: function(m) {return m;},
        formatSelection: function (o, c) {
            c.text(o.text);
        }
    });
    $(parent+' select[data-format]:not(.disableSelect2AutoLoad)').select2({
        minimumResultsForSearch:10,
        width:'resolve',
        formatResult: function (o){
            var fnc = $(o.element[0]).closest('select').data('format');
            return window[fnc](o);
        },
        formatSelection: function (o){ return o.text; }
    });

    $.each($(".select2-container"), function (i, n) {
        $(n).find('select').fadeTo(0, 0).width("0").height("0").css("left", "-10000000px").css("position","absolute"); // make the original select visible for validation engine and hidden for us
        //$(n).prepend($(n).next());
    });
    //end of select2

    //lets prevent disabled btn-groups from showing dropdown
    $(parent+" span.btn-group a.btn.dropdown-toggle").click(function(event) {
        if ($(this).attr('disabled') =='disabled') {
            event.preventDefault();
            return false;
        } else {
            return true;
        }
    });


    $(parent+' input.datepicker:not(.disableDatePickerAutoLoad)').datepicker({
        format: 'mm/dd/yyyy',
        autoclose: true
    });
    $(parent+' input.timepicker:not(.disableTimePickerAutoLoad)').timepicker();

    if ( typeof(clientexec.sessionHash) != "undefined" ) {
        $('<input>').attr({
            type: 'hidden',
            id: 'sessionHash',
            name: 'sessionHash',
            value: clientexec.sessionHash
        }).appendTo('form');
    } else {
        console.log("clientexec.sessionHash not defined");
    }

};


/* move to common.js */

if (clientexec.sessionHash!==undefined) {
	$.ajaxSetup({beforeSend: function (xhr) {xhr.setRequestHeader('X-Session-Hash', clientexec.sessionHash);}});
}

function _sprintf(s)
{
    var re = /%/;
    var i = 0;
    while (re.test(s))
    {
       s = s.replace(re, _sprintf.arguments[++i]);
    }

    return s;
}

function lang(phrase)
{
    if ((typeof language != "undefined") && (typeof language[phrase] != "undefined") && (language[phrase] != '')) {
        switch (lang.arguments.length) {
            case 1:
                return language[phrase];
            case 2:
                return _sprintf(language[phrase], lang.arguments[1]);
            case 3:
                return _sprintf(language[phrase], lang.arguments[1], lang.arguments[2]);
            case 4:
                return _sprintf(language[phrase], lang.arguments[1], lang.arguments[2], lang.arguments[3]);
        }

        return language[phrase];
    }

    switch (lang.arguments.length) {
        case 1:
            return phrase;
        case 2:
            return _sprintf(phrase, lang.arguments[1]);
        case 3:
            return _sprintf(phrase, lang.arguments[1], lang.arguments[2]);
        case 4:
            return _sprintf(phrase, lang.arguments[1], lang.arguments[2], lang.arguments[3]);
    }

    return phrase;
}
