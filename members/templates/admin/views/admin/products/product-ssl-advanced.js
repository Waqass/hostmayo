productview.ssl_advanced_load = function() {
    $('#product-tab-content').load('index.php?nolog=1&fuse=admin&controller=products&view=advancedtabforssl&groupid='+productview.groupid+'&productid='+productview.productid, productview.postloadactions_ssl_advanced );
};

productview.postloadactions_ssl_advanced = function()
{

    //TODO load via ajax instead with the url below
    $('#comboCertificates').select2({
        minimumResultsForSearch: 10,
        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
            url: "index.php?fuse=admin&controller=products&action=getsslcerttypes&productid="+productview.productid,
            dataType: 'json',
            quietMillis: 0,
            data : function () {
                return {
                    registrar: $('#comboRegistrars').val()
                }
            },
            results: function (data, page) { // parse the results into the format expected by Select2.
                // since we are using custom formatting functions we do not need to alter remote JSON data
                productview.options = {results:[]};
                $(data.certificates).each(function(index) {
                    name = this.name.toString();
                    productview.options.results.push({id:this.id.toString(),text:name});
                });
                return productview.options;
            }
        }
    });

    $("#comboCertificates").select2("data",{id:productview.ssl.certificateId,text:productview.ssl.certificateText});

    clientexec.postpageload('#product-tab-content');

    $('#comboRegistrars').on('change',function(e){
        $("#comboCertificates").select2("enable");
        $("#comboCertificates").select2("data",{id:0,text:"-- Select a Certificate Type -- "});
    });

    $('.submit-ssl-advanced').click(function(e){

        var certId = $('#comboCertificates').select2("data").id;
        var certText = $('#comboCertificates').select2("data").text;
        var registrar = $('#comboRegistrars').val();

        // Allow cert to be 0 if no registrar
        if ( certId == 0 && registrar != '-- None --' ) {
            RichHTML.msgBox("You must have a Cert Type selected before saving.",{type:'error'});
            return;
        }

        // set to no registrar, if set to none.
        if ( registrar == '-- None --' ) {
            registrar = ''
        }

        e.preventDefault();
        $.ajax({
            url: 'index.php?fuse=admin&tab=general&controller=products&action=saveadvancedforssl&productid='+productview.productid,
            type: 'POST',
            data: {
                certs: certId,
                certText: certText,
                registrar: registrar
            },
            success : function(xhr) {
                json = ce.parseResponse(xhr);
            }
        });
    });

};