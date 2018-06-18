 <div id="domain" class="bar">
                <h6>check domain</h6>
                <form action="http://hostmayo.com/members/" id="search-domain">
                    <fieldset>
                        <input type="hidden" name="fuse" value="clients"/>
                        <input type="hidden" name="action" value="CheckDomain"/>
                        <input id="domainname" type="text" name="name" value="http://"/>
                        <input type="hidden" name="product" value="2"/>
                        <input type="submit" value="" />
                    </fieldset>
                </form>
            </div>
            <script type="text/javascript">
            /* <![CDATA[ */
            $('#domainname').click(function(){
                $('#domainname').removeClass('domain_success');
                $('#domainname').removeClass('domain_fail');
            });
            $('#search-domain').submit(function(){
                var fullname = $('#domainname').val();
                if(fullname.length < 8){
                    $('#domainname').addClass('domain_fail');
                    $('#domainname').val(fullname+' - not a valid domain name');
                }else if(fullname.indexOf(".") == -1){
                    $('#domainname').addClass('domain_fail');
                    $('#domainname').val(fullname+' - not a valid domain name');
                }else{
                    if(fullname.indexOf("www") != -1){
                        fullname = fullname.substr(11);
                    }else{
                        fullname = fullname.substr(7);
                    }
                    var name_array = fullname.split('.');
                    var baseURL = 'http://hostmayo.com/members/index.php?fuse=clients&action=CheckDomain&name=Domain&tld=com&products&groupid=2&id=2;
                    $.post(baseURL,function(response){
                        var data = $.parseJSON(response);
                        var results = data.results;
                        if(results[0].text == 'Available'){
                            $('#domainname').addClass('domain_success');
                        }else{
                            $('#domainname').addClass('domain_fail');
                        }
                    });
                }
                return false;
            });
            /* ]]> */
            </script>