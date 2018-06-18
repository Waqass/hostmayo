// State vars
function getTax()
{
    var country = "";
    var state = "";

    if(typeof countryVAR != 'undefined' && mainForm.elements[countryVAR] !=null){
        country = mainForm.elements[countryVAR].value;
    }

    if(typeof stateVAR != 'undefined' && mainForm.elements[stateVAR] !=null){
        state = mainForm.elements[stateVAR].value;
    }

    if(country){
        $.ajax({
           url: 'index.php?fuse=clients&controller=user&action=gettax&country='+country+'&state='+state+'&ignoreuser=1',
           dataType: 'json',
           success: setTax
        });
    }else{
        if(typeof vatVAR != 'undefined' && mainForm.elements[vatVAR] != null){
            mainForm.elements[vatVAR].disabled = true;
            mainForm.elements[vatVAR].value = '';
        }

        if(document.getElementById('vatBlock') != undefined){
            document.getElementById('vatBlock').style.display = 'none';
        }
    }
}

function setTax(responseObj)
{

    json = ce.parseResponse(responseObj);

    if(typeof vatVAR != 'undefined' && mainForm.elements[vatVAR] !=null){
        if(json.taxes.vat == "1" || json.taxes.vat2 == "1"){
            mainForm.elements[vatVAR].disabled = false;

            if(document.getElementById('vatBlock') != undefined){
                document.getElementById('vatBlock').style.display = '';
            }
        }else{
            mainForm.elements[vatVAR].disabled = true;
            mainForm.elements[vatVAR].value = '';

            if(document.getElementById('vatBlock') != undefined){
                document.getElementById('vatBlock').style.display = 'none';
            }
        }

        checkVAT();
    }else{
        if(document.getElementById('vatBlock') != undefined){
            document.getElementById('vatBlock').style.display='none';
        }
    }
}

function checkVAT()
{
    if(document.getElementById('vat_validating') != undefined){
        document.getElementById('vat_validating').style.display = '';
        document.getElementById('vat_valid').style.display = 'none';
        document.getElementById('vat_invalid').style.display = 'none';
        document.getElementById('vat_error').style.display = 'none';
    }

    var country = '';
    var vatnum = '';

    if(typeof countryVAR != 'undefined' && mainForm.elements[countryVAR] !=null){
        country = mainForm.elements[countryVAR].value;
    }

    if(document.getElementById('vat_country') != undefined){
        // Greece has to be different...
        if(country == 'GR'){
            document.getElementById('vat_country').innerHTML = 'EL';
        }else{
            document.getElementById('vat_country').innerHTML = country;
        }
    }

    if(typeof vatVAR != 'undefined'
      && mainForm.elements[vatVAR] != null
      && mainForm.elements[vatVAR].value != ''){
        vatnum = mainForm.elements[vatVAR].value;
    }

    if(country){
        appendToRequest = '';
        if(mainForm.elements['userid'] != undefined){
            appendToRequest = '&userid='+mainForm.elements['userid'].value;
        }
        $.ajax({
           url: 'index.php?fuse=billing&action=checkvat&country='+country+'&vat='+vatnum+'&ignoreuser=1'+appendToRequest,
           dataType: 'json',
           success: checkVAT_Callback
        });
    }else{
        if(document.getElementById('vat_validating') != undefined){
            document.getElementById('vat_validating').style.display = 'none';
            document.getElementById('vat_valid').style.display = 'none';
            document.getElementById('vat_invalid').style.display = 'none';
            document.getElementById('vat_error').style.display = 'none';
        }

        if(typeof vatVAR != 'undefined' && mainForm.elements[vatVAR] != null){
            mainForm.elements[vatVAR].disabled = true;
            mainForm.elements[vatVAR].value = '';
        }

        if(document.getElementById('vatBlock') != undefined){
            document.getElementById('vatBlock').style.display = 'none';
        }
    }
}

function checkVAT_Callback(responseObj)
{
    respArr = responseObj.responseText.split("|");

    if(respArr[1] != ""
      && typeof vatVAR != 'undefined'
      && mainForm.elements[vatVAR] != null
      && mainForm.elements[vatVAR].value == ''){
        mainForm.elements[vatVAR].value = respArr[1];
    }

    if(document.getElementById('vat_validating') != undefined){
        document.getElementById('vat_validating').style.display = 'none';
        if(respArr[1] != ""){
            switch(respArr[0]){
                case "-1":
                    document.getElementById('vat_error').style.display = '';
                    break;
                case "0":
                    document.getElementById('vat_invalid').style.display = '';
                    break;
                case "1":
                    document.getElementById('vat_valid').style.display = '';
                    break;
            }
        }
    }
}