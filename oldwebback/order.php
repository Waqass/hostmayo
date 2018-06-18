div class="about">
	<div class="container">
		<div class="col-md-8 grid_2">
			<h1>Order</h1>
			<div class="box-4 box-5">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<input id="domainname" type="text" name="domainname" value='yournewsite.com' />
<input id="submit-button" type="submit" value="Search Domain">
<script type="text/javascript">
    var ceURL = 'http://www.hostmayo.com/members/';
    var groupId = 2;
    $('#submit-button').click(function(e) {
        e.preventDefault();
        var fullname = $('#domainname').val();
        if ( fullname.indexOf(".") == -1 ) {
            $('#domainname').addClass('domain_fail');
            $('#domainname').val(fullname+' - not a valid domain name');
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
                        alert('domain is available');
                    } else if ( domainStatus == '1' ) {
                        alert('domain is not available');
                    } else {
                        alert('there was an error, please try again later');
                    }
                }
            }, 'json');
        }
        return false;
    });
</script>
                        </div>
                <div class="clearfix"></div>
            </div>
            <div class="box-4">
                </div>
                <div class="caption">
                    <h3><a href="#">Head Office<?php

$link = 'http://speed.bezeqint.net/big.zip';
$start = time();
$size = filesize($link);
$file = file_get_contents($link);
$end = time();

$time = $end - $start;

$size = $size / 1048576;

$speed = $size / $time;

echo "Server's speed is: $speed MB/s";


?></a></h3>
                </div>
                <div class="clearfix"></div>
            </div>
		</div>
</div>
