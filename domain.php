<input id="domainname" name="domain" class="input" type="text" placeholder="Get .com domain for 12$/year!">
<input id="submit-button" name="Submit" value="Search" class="input" type="submit">

<script type="text/javascript">

    var ceURL = 'https://hostmayo.com/members/';

    var groupId = 2;

    $('#submit-button').click(function(e) {

        e.preventDefault();

        var fullname = $('#domainname').val();

        if ( fullname.indexOf(".") == -1 ) {

            $('#domainname').addClass('domain_fail');
            $('#domainname').val('Please enter a valid domain name with extension.');
            //$('#domainname').val(fullname+'Please enter a valid domain name');

        } else {

            var name_array = fullname.split('.');

            $.post(ceURL + 'index.php?fuse=clients&action=checkdomain',

            {

                name: name_array[0],

                tld: name_array[1],

                group: groupId

            }, function(response) {

                console.log(response);

                if ( response.error ) {

                    alert(response.message);

                } else {

                    var domainStatus = response.search_results.status;

                    if ( domainStatus == '0' ) {

                        window.location = ceURL + 'order.php?step=0&productGroup=' + groupId + '&domainName=' + name_array[0] + '&tld=' + name_array[1];

                    } else if ( domainStatus == '1' ) {

                        window.location = ceURL + 'order.php?step=0&productGroup=' + groupId + '&domainName=' + name_array[0] + '&tld=' + name_array[1];

                    } else {

                        alert('there was an error, please try again later');

                    }

                }

            }, 'json');

        }

        return false;

    });

</script>