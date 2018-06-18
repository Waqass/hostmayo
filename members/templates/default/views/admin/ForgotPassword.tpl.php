<div id="right">
    <div id="kb" class="block">
        <?php echo $this->user->lang("Please enter your Registered E-mail Address below and click Submit. You will receive an E-mail shortly with instructions."); ?>
        <br/><br/>
        <ul>
            <li>
                <form name="forgotpassword" id="forgotpassword" action="index.php?fuse=admin&action=RequestPassword" method="post" id="submit_ticket">
                    <input type="hidden" name="publicSection" value="1" />
                        <div>
                            <span class="label130"><?php echo $this->user->lang("Your E-mail");?>:</span>
                            <input type="text" id="femail" name="femail" />
                            <br />
                        </div>
                    <div class="st_submit">
                        <input type="submit" value="<?php echo $this->user->lang("Submit");?>" />
                        <input type="reset" value="<?php echo $this->user->lang("Reset");?>" />
                    </div>
                </form>
            </li>
        </ul>
    </div>
</div>
