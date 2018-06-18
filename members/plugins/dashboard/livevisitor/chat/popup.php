<?php
$hostname = $_SERVER['HTTP_HOST'];
$dirName = dirname($_SERVER['SCRIPT_NAME']);
?>
<div id="wrapper" class="outline">

	<table id="content" cellspacing="0" cellpadding="0">
		<tbody>
			<tr id="title-container" >
				<td>
					<div id="title-container" class="topclip">
						<a id="title" onclick="parent.chat_ui.close_chat_window()" href="#" class="title title-bg title-font">
							<img src="plugins/dashboard/livevisitor/assets/minimize.png" style="position:relative;top:8px;right:14px;display:block;float:right;width:16px;height:12px;-moz-box-shadow:-8px 0 6px rgba(0, 0, 0, 0);-webkit-box-shadow:-8px 0 6px rgba(0, 0, 0, 0);box-shadow:-8px 0 6px rgba(0, 0, 0, 0);cursor:pointer" />
							<span href="" id="close-chat" class="title-button s-common-close" data-title="Stop chat"></span>
							<span href="" id="minimize" class="title-button s-common-minimize" data-title="Minimize window"></span>
							<span id="title-text"><?php echo $this->user->lang('How can we help you?') ?></span>
						</a>
					</div>
				</td>
			</tr>
		</tbody>
	</table>

	<h1 id="status-message" class="loading"><span><?php echo $this->user->lang('Loading...') ?></span></h1>

	<div id="log" style="display: none;"><div class="scrolltome"></div></div>

	<form id="messageform" action="#" style="display: none;">
		<fieldset>
			<legend><?php echo $this->user->lang('Chat') ?></legend>
			<input id="msg" placeholder="<?php echo $this->user->lang('Enter your comment here') ?>&hellip;" />
		</fieldset>
		<div class="restartchatdiv" style="display:none;">
			<a href="#" class="restartchatbtn button large orange"><?php echo $this->user->lang('Start a New Conversation') ?></a>
		</div>
		<footer>
			<span class="typing_wrapper"></span>
			<?php if (!CE_Lib::hasAttribute("nobranding")) { ?>
			<span id="powered-by"><?php echo $this->user->lang('Powered by') ?> <a href="//www.clientexec.com/?source=chat" target="_blank"><img class="brand-logo" src="templates/default/img/logo_text.png"  style="height: 20px;position: relative;top: 4px;"></a></span>
			<?php } ?>
		</footer>
	</form>

	<form id="loginform" action="#" style="dispaly:none;">
		<label class="newuserlabel"><?php echo $this->user->lang('Hi there! It is really great to see you.') ?><br/>
<?php echo $this->user->lang("Tell us a little about yourself and let's chat.") ?></label>
		<label class="returninguserlabel"><?php echo $this->user->lang('Welcome back %s!', '<span class="label_fullname"></span>') ?><br/>
<?php echo $this->user->lang('How may we assist you?') ?></label>
		<input type="text" id="fullname" name="fullname" placeholder="<?php echo $this->user->lang('Full Name') ?>" class="validate[required]" />
		<input type="text" id="email" name="email" value="" placeholder="<?php echo $this->user->lang('E-mail') ?>" autocapitalize="none" class="validate[custom[email],required]" />
		<input type="text" id="firstquestion" name="firstquestion" placeholder="<?php echo $this->user->lang('Your Question') ?>" class="validate[required]" />
		<a href="#" class="button large orange startchattingbtn" target="_blank"><?php echo $this->user->lang('Start Chatting') ?></a>
		<?php if (!CE_Lib::hasAttribute("nobranding")) { ?>
		<span id="powered-by"><?php echo $this->user->lang('Powered by') ?> <a href="//www.clientexec.com/?source=chat" target="_blank"><img class="brand-logo" src="templates/default/img/logo_text.png" style="height: 20px;position: relative;top: 4px;"></a></span>
		<?php } ?>
	</form>

	<audio preload="auto" id="soundreceived">
		<source src="plugins/dashboard/livevisitor/assets/message_received.mp3" type="audio/mp3">
	</audio>

	<dl id="skeleton" style="display: none;">
		<dt><span class="avatar"><img class="usergravatar" src="plugins/dashboard/livevisitor/assets/avatar.png" alt="<?php echo $this->user->lang('Avatar') ?>" /></span></dt>
		<dd></dd>
	</dl>

	<script type="text/javascript">
	    chat_ui = {};
		$(document).ready(function(){

		   	//check to see if we have an fullname and email if not show the screen to get it
    		chat_ui.start();
    		chat_ui.state = null;
		    chat_ui.roomid = null;

		    //if we have a saved roomid then we have
		    //contact information in cookie
			var newroom = chat_ui.setchatroomid();

		    if ($.cookie('chatid') == null) {
				chat_ui.change_status("notloggedon");
			} else if ( newroom && ($.cookie('chatid') != null ) ) {
				//let's just ask for the question.  No need to ask for username and email
				chat_ui.change_status("notloggedonwithuser");
		    } else {
				chat_ui.change_status("online");
				chat.roomispublic = 0;
    			chat.registerwithserver();
		    }

		    //bind the register button
	    	$(document).on("click", ".startchattingbtn",function(e){
                e.preventDefault();

	    		if (!chat_ui.check_fields(1)) return false;

			    //let's make sure we have cookie id
			    if ($.cookie('chatid') == null) {
			        $.cookie('chatid',chat.getuniqueid());
			        $.cookie('chatfullname',$('#fullname').val());
			    	$.cookie('chatemail',$('#email').val());
			    } else {
			    	//we are assuming we have chatemail and chatfullname if we have chatid
			    }

			    //I should now update the last visitor entry with my chatterid
			    $.ajax({
			        type: "GET",
			        url:'index.php?fuse=admin&controller=plugin&action=doplugin&pluginaction=track&plugin=livevisitor&chatterid='+$.cookie('chatid')+'&callback=?',
			        data:parent.window.session,
			        dataType:"jsonp"
			    });

			    var date = new Date();
				var minutes = 6;
				date.setTime(date.getTime() + (minutes * 60 * 1000));
				$.removeCookie("chatroomid");
			    $.cookie('chatroomid',chat_ui.roomid,{expires: date});

		        chat_ui.change_status("online");
				chat.registerwithserver($('#firstquestion').val());

				$('#messageform fieldset').show();
    			$('.restartchatdiv').hide();
		    });

	    	/*Your Reference Number for this chat is LTK4970463342X*/


			$(document).on("click", ".restartchatbtn",function(){

				$('#log dl').remove();
		    	$.removeCookie('chatroomid');
				chat_ui.setchatroomid();
		    	chat_ui.change_status("notloggedonwithuser");
		    	$('.hellobar-left').remove();


	    		/*
	    		//when are we here ..
	    		$('#messageform fieldset').show();
        		$('.restartchatdiv').hide();
        		//let's start event service if we are not connected
		        if (chat.data.readyState === 2) {
		            chat.startMyEventService();
		        }*/

		    });

		});

		chat_ui.validateEmail = function(email) {
		    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
		    if( !emailReg.test( email ) ) {
		        return false;
		    } else {
		        return true;
		    }
		}

		chat_ui.check_fields = function()
		{
			valid = true;
			if (type = 1) { //initial chat
				if ($('#fullname').is(':visible')) {
					if ($.trim($('#fullname').val()) == "") {
						valid = false;
						$('#fullname').css('border','1px solid orangered');
					} else {
						$('#fullname').css('border','0');
					}
					if ($.trim($('#email').val()) == "") {
						valid = false;
						$('#email').css('border','1px solid orangered');
					} else if (!chat_ui.validateEmail($('#email').val())) {
						valid = false;
						$('#email').css('border','1px solid orangered');
					} else {
						$('#email').css('border','0');
					}
				}

				if ($.trim($('#firstquestion').val()) == "") {
					valid = false;
					$('#firstquestion').css('border','1px solid orangered');
				} else {
					$('#firstquestion').css('border','0');
				}
			}

			return valid;

		}

		chat_ui.setchatroomid = function()
		{
			var newroom = false;
			var newroomid = 0;
			//let's see if we need another chatroom
			//roomids are set for 30 minutes only
		    if ($.cookie('chatroomid') == null) {
				chat_ui.roomid = chat.getuniqueid();
		    	newroom = true;
		    } else {
		    	chat_ui.roomid = $.cookie('chatroomid');
		    }

		    return newroom;
		}

	    chat_ui.start = function()
	    {
	        //let's get state of window
	        if($.cookie('chat_ui_state') == null) {
	            $.cookie('chat_ui_state','closed');
	        }
	        chat_ui.state = $.cookie('chat_ui_state');

	        //show compact view
	        //if there is a chat we should show continue chat instead of start a chat
	        parent.window.frames['livevisitor-compact-view'].document.body.innerHTML = '<div id="content-container" style="position:absolute;top:0;right:0;bottom:0;left:0;width:100%;height:100%;z-index:6;line-height:22px;"><div id="content" style="background-color:#000;border:1px solid rgb(0, 0, 0);-moz-border-radius:10px;-moz-border-radius-bottomleft:0;-moz-border-radius-bottomright:0;-webkit-border-radius:10px;-webkit-border-bottom-left-radius:0;-webkit-border-bottom-right-radius:0;border-radius:10px;border-bottom-left-radius:0;border-bottom-right-radius:0;-moz-box-shadow:inset 1px 1px 1px rgba(255, 255, 255, 0.2);-webkit-box-shadow:inset 1px 1px 1px rgba(255, 255, 255, 0.2);box-shadow:inset 1px 1px 1px rgba(255, 255, 255, 0.2)"><a href="javascript:void(null)" onclick="parent.chat_ui.open_chat_window();" id="full-view-button" style="display:block;position:relative;padding:0;cursor:pointer;outline:0;font-size:14px;font-family:\'Lucida Grande\',\'Lucida Sans Unicode\',Arial,Verdana,sans-serif;color:rgb(255, 255, 255);text-shadow:rgb(0, 0, 0) 1px 1px 0px;text-decoration:none;font-weight:bold;"><span style="display:block;width:196px;height:100%;padding:6px 15px;overflow:hidden;white-space:nowrap;"><?php echo $this->user->lang('Questions?') ?>&nbsp;&nbsp;<?php echo $this->user->lang('Start a Chat') ?></span></a></div></div>';

	        if (chat_ui.state == "closed" || typeof(chat_ui.state) == "undefined") {
	            $('#livevisitor-compact-container').show();
	        } else {
	            parent.chat_ui.open_chat_window();
	        }

	    };
	    parent.chat_ui.close_chat_window = function(){
	        $.cookie('chat_ui_state','closed');
	        parent.$('#livevisitor-full-container').hide();
	        parent.$('#livevisitor-compact-container').show();
	    };

	    parent.chat_ui.open_chat_window = function() {

	        $.cookie('chat_ui_state','open');
	        parent.$('#livevisitor-compact-container').hide();
	        parent.$('#livevisitor-full-container').show();
	        // $.get('popup.php?roomid=<?php echo uniqid('', false);?>',function(xhr){
	            //window.frames['livevisitor-full-view'].document.body.innerHTML = xhr;
	            parent.$('#livevisitor-full-container').show();
	        // });

        // this is not only good for UX, but also necessary for Safari cuz otherwise
        // it'll hide the fullname and email fields for some reason
        // https://github.com/clientexec/webapp/issues/819
				parent.$("#livevisitor-full-view").contents().find('#loginform #fullname').focus();

	    };

	    chat_ui.change_status = function(status)
	    {

	        switch(status){
	        	case 'notloggedonwithuser':
	        		parent.$("#livevisitor-full-view").contents().find('#loginform .label_fullname').text($.cookie('chatfullname'));
	        		parent.$("#livevisitor-full-view").contents().find('#loginform').addClass('haschatterinfo');
					parent.$("#livevisitor-full-view").contents().find('#loginform #fullname').hide();
					parent.$("#livevisitor-full-view").contents().find('#loginform #email').hide();
					parent.$("#livevisitor-full-view").contents().find('.newuserlabel').hide();
					parent.$("#livevisitor-full-view").contents().find('.returninguserlabel').show();
					parent.$("#livevisitor-full-view").contents().find('#status-message').hide();
	                parent.$("#livevisitor-full-view").contents().find('.operator-status, #log, #messageform').hide();
	                parent.$("#livevisitor-full-view").contents().find('#loginform').show();
	            break;
	            case 'notloggedon':
	            	parent.$("#livevisitor-full-view").contents().find('#loginform').removeClass('haschatterinfo');
					parent.$("#livevisitor-full-view").contents().find('#loginform #fullname').show();
					parent.$("#livevisitor-full-view").contents().find('#loginform #email').show();
					parent.$("#livevisitor-full-view").contents().find('.returninguserlabel').hide();
	        		parent.$("#livevisitor-full-view").contents().find('.newuserlabel').show();
	                parent.$("#livevisitor-full-view").contents().find('#status-message').hide();
	                parent.$("#livevisitor-full-view").contents().find('.operator-status, #log, #messageform').hide();
	                parent.$("#livevisitor-full-view").contents().find('#loginform').show();
	            break;
	            case 'online':
	            	chat.hellobar = new HelloBar("<?php echo $this->user->lang('Waiting for an operator to respond ...') ?>", {
						positioning:'fixed',
						showWait   : 300,
						tabSide    : 'left'
					}, 1.0 );
					$('#hellobar-content').addClass('operator-status');
	                parent.$("#livevisitor-full-view").contents().find('#status-message').hide();
	                parent.$("#livevisitor-full-view").contents().find('#loginform').hide();
	                parent.$("#livevisitor-full-view").contents().find('.operator-status, #log, #messageform').show();
	            break;
	            case 'offline':
	                parent.$("#livevisitor-full-view").contents().find('#status-message').show();
	                parent.$("#livevisitor-full-view").contents().find('#loginform').hide();
	                parent.$("#livevisitor-full-view").contents().find('.operator-status, #log, #messageform').hide();

	                parent.$("#livevisitor-full-view").contents().find('.operator-status').text("<?php echo $this->user->lang('Waiting for an operator to respond ...') ?>");
	                parent.$("#livevisitor-full-view").contents().find('#status-message').attr('class', 'unavailable').find('span').text("<?php echo $this->user->lang('Chat is currently unavailable, please come back later.') ?>");
	            break;
	        }

	    };



	</script>
