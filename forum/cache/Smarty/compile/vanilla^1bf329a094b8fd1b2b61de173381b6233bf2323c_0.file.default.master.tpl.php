<?php
/* Smarty version 3.1.31, created on 2018-04-20 21:51:36
  from "D:\xampp\htdocs\hostmayo\forum\themes\Gopi\views\default.master.tpl" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.31',
  'unifunc' => 'content_5ada60e86105f5_02217850',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '1bf329a094b8fd1b2b61de173381b6233bf2323c' => 
    array (
      0 => 'D:\\xampp\\htdocs\\hostmayo\\forum\\themes\\Gopi\\views\\default.master.tpl',
      1 => 1524261087,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5ada60e86105f5_02217850 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_function_asset')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.asset.php';
if (!is_callable('smarty_function_link')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.link.php';
if (!is_callable('smarty_function_logo')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.logo.php';
if (!is_callable('smarty_function_custom_menu')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.custom_menu.php';
if (!is_callable('smarty_function_module')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.module.php';
if (!is_callable('smarty_function_signin_link')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.signin_link.php';
if (!is_callable('smarty_function_breadcrumbs')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.breadcrumbs.php';
if (!is_callable('smarty_function_t')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.t.php';
if (!is_callable('smarty_modifier_date_format')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\vendor\\smarty\\smarty\\libs\\plugins\\modifier.date_format.php';
if (!is_callable('smarty_function_searchbox')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.searchbox.php';
if (!is_callable('smarty_function_profile_link')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.profile_link.php';
if (!is_callable('smarty_function_inbox_link')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.inbox_link.php';
if (!is_callable('smarty_function_bookmarks_link')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.bookmarks_link.php';
if (!is_callable('smarty_function_dashboard_link')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.dashboard_link.php';
if (!is_callable('smarty_function_signinout_link')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.signinout_link.php';
if (!is_callable('smarty_function_event')) require_once 'D:\\xampp\\htdocs\\hostmayo\\forum\\library\\SmartyPlugins\\function.event.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_smarty_tpl->tpl_vars['CurrentLocale']->value['Lang'];?>
">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php echo smarty_function_asset(array('name'=>"Head"),$_smarty_tpl);?>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:100,400,600" rel="stylesheet">
    <link href='https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' rel='stylesheet' type='text/css'>
</head>
<body id="<?php echo $_smarty_tpl->tpl_vars['BodyID']->value;?>
" class="<?php echo $_smarty_tpl->tpl_vars['BodyClass']->value;?>
">
    <div class="Head" id="Head">
        <div class="Container">
            <nav class="Row">
                 <div class="col-2-nav">
                    <a class="logo" href="<?php echo smarty_function_link(array('path'=>"/"),$_smarty_tpl);?>
"><?php echo smarty_function_logo(array(),$_smarty_tpl);?>
</a>
                </div>
                <div class="col-4-nav nav-icon">
                    <ul class="mobile-close">
                        <li><a href="https://hostmayo.com/"><i class="fa fa-home" aria-hidden="true"></i> Home</a></li>
                        <li><a href="https://forums.hostmayo.com"><i class="fa fa-comments" aria-hidden="true"></i> Forums</a></li>
                        <li><a href="https://hostmayo.com/blog/"><i class="fa fa-wpforms" aria-hidden="true"></i> Blog</a></li>
                        <li><a class="search" href="#search"><i class="fa fa-search"></i> Search</a></li>
                        <?php echo smarty_function_custom_menu(array(),$_smarty_tpl);?>

                    </ul>
                    
                </div>
                <div class="col-3-nav nav-icon">
                    <?php if ($_smarty_tpl->tpl_vars['User']->value['SignedIn']) {?>
                    <span class="mobile-close">
                        <?php echo smarty_function_module(array('name'=>"MeModule"),$_smarty_tpl);?>

                    </span> 
                    
                    <a class="mobile-open mobile-menu" href="#mobile-menu"><i class="icon icon-menu"></i></a>
                    <?php } else { ?>
                        <?php echo smarty_function_signin_link(array('wrap'=>"span",'format'=>"<a href='%url' title='%text' class='%class'><span class='icon icon-signin'><span></a>"),$_smarty_tpl);?>

                    <?php }?> 
                </div>    
            </nav>
        </div>
    </div>
    <div id="Body" class="Container">
        
        <?php if (!InSection(array("CategoryList","CategoryDiscussionList","DiscussionList","Entry","Profile","ActivityList","ConversationList","PostConversation","Conversation","PostDiscussion"))) {?>
        <div class="Row">
            <div class="col-12 BreadcrumbsWrapper"><?php echo smarty_function_breadcrumbs(array(),$_smarty_tpl);?>
</div>
        </div>
        <?php }?>
        <div class="Row">
            <div class="col-9" id="Content">
                <div class="ContentColumn">
                    <?php echo smarty_function_asset(array('name'=>"Content"),$_smarty_tpl);?>

                </div>
            </div>
            <?php if (!InSection(array("Entry","PostDiscussion"))) {?>
            <div class="col-3 PanelColumn" id="Panel">
                <?php echo smarty_function_asset(array('name'=>"Panel"),$_smarty_tpl);?>

            </div>
            <?php }?>
        </div>
    </div>
    <?php if ($_smarty_tpl->tpl_vars['Homepage']->value) {?>
    <div id="Body" class="Container">
       <div class="Row statrow">
            <div class="col-8 stats" id="Content">
                <div class="top">Forums Stats</div>
                <div class="row">
                    <div class="col-3">Threads: <?php echo $_smarty_tpl->tpl_vars['threads']->value;?>

                    </div>
                    <div class="col-3">Posts: <?php echo $_smarty_tpl->tpl_vars['posts']->value;?>

                    </div>
                    <div class="col-3">Members: <?php echo $_smarty_tpl->tpl_vars['members']->value;?>

                    </div>
                </div>
            </div>
        </div>	
    </div>
    <?php } else { ?>
        <style>
          .ContentColumn
            {
                border: 1px solid rgba(219, 219, 219, 0.5);

            }
        </style>
    <?php }?>
    <footer>
        <div class="Container">
            <div class="Row footer">
                <div class="col-12">
                    <p class="logo"> <?php echo smarty_function_t(array('c'=>"Host Mayo"),$_smarty_tpl);?>
 &copy; <?php echo smarty_modifier_date_format(time(),"%Y");?>
 </p>  
                </div>
            </div>
        </div>
        <?php echo smarty_function_asset(array('name'=>"Foot"),$_smarty_tpl);?>

    </footer>
<div id="search">
    <button class="modal-close"></button>
    <?php echo smarty_function_searchbox(array(),$_smarty_tpl);?>

</div>
<div id="mobile-menu">
    <button class="modal-close"></button>
    <ul>
        <li><a href="https://hostmayo.com/"><i class="fa fa-home" aria-hidden="true"></i> Home</a></li>
        <li><a href="https://forums.hostmayo.com"><i class="fa fa-comments" aria-hidden="true"></i> Forums</a></li>
        <li><a href="https://hostmayo.com/blog/"><i class="fa fa-wpforms" aria-hidden="true"></i> Blog</a></li>
        <?php echo smarty_function_custom_menu(array(),$_smarty_tpl);?>

        <?php echo smarty_function_profile_link(array(),$_smarty_tpl);?>

        <?php echo smarty_function_inbox_link(array(),$_smarty_tpl);?>

        <?php echo smarty_function_bookmarks_link(array(),$_smarty_tpl);?>

        <?php echo smarty_function_dashboard_link(array(),$_smarty_tpl);?>

        <?php echo smarty_function_signinout_link(array(),$_smarty_tpl);?>

    </ul>
</div>
<?php echo smarty_function_event(array('name'=>"AfterBody"),$_smarty_tpl);?>


 <?php echo '<script'; ?>
>
 $('body').show();
 $('.version').text(NProgress.version);
 NProgress.start();
 setTimeout(function() { NProgress.done(); $('.fade').removeClass('out'); }, 1000);
 <?php echo '</script'; ?>
>
 
</body>
</html><?php }
}
