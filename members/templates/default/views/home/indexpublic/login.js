ce_login= {};
$(document).ready(function(){

    $('.forgotpasswordurl').click(function(){
        ce_login.resetpwdwin = new RichHTML.window({
            width: '400',
            height: '197',
            title: lang('Reset password'),
            url: 'index.php?fuse=home&view=resetpwd',
            actionUrl: 'index.php?fuse=home&action=resetpassword',
            showSubmit: true,
            showerrors: false,
            onSubmit: function(xhr) {
                if (xhr.error) {
                    ce_login.showerror(xhr.message);
                } else {
                    $('.alert-reset-password-success').show();
                }
            }
        });
        ce_login.resetpwdwin.show();

        $(ce_login.resetpwdwin).bind({
            "validationerror": function(event,data) {
                ce_login.showerror(lang('Errors encountered. Please review and resubmit.'));
            }
        });

        ce_login.showerror = function(msg)
        {
            $('.resetpwd-description').addClass('alert-error').html(msg)
        }

    });

    $('.newaccounturl').click(function(){
        ce_login.newuserwin = new RichHTML.window({
            width: '400',
            height: clientexec.captcha? '380' : '225',
            title: lang('Create an account'),
            url: 'index.php?fuse=home&view=register',
            actionUrl: 'index.php?fuse=home&action=createaccount',
            showSubmit: true,
            showerrors: false,
            onSubmit: function(xhr) {
                if (xhr.error) {
                    ce_login.showerror(xhr.message);
                    if (xhr.error_code == '2332') {
                        Recaptcha.reload();
                    }
                } else {
                    $('.alert-registration-success').show();
                }
            }
        });
        ce_login.newuserwin.show();

        $(ce_login.newuserwin).bind({
            "validationerror": function(event,data) {
                ce_login.showerror(lang('Errors encountered. Please review and resubmit.'));
            }
        });

        ce_login.showerror = function(msg)
        {
            $('.register-description').addClass('alert-error').html(msg)
        }

    });

    $('input[name=email]').focus();
});
