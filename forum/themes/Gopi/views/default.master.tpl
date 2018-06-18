<!DOCTYPE html>
<html lang="{$CurrentLocale.Lang}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    {asset name="Head"}
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:100,400,600" rel="stylesheet">
    <link href='https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' rel='stylesheet' type='text/css'>
</head>
<body id="{$BodyID}" class="{$BodyClass}">
    <div class="Head" id="Head">
        <div class="Container">
            <nav class="Row">
                 <div class="col-2-nav">
                    <a class="logo" href="{link path="/"}">{logo}</a>
                </div>
                <div class="col-4-nav nav-icon">
                    <ul class="mobile-close">
                        <li><a href="https://hostmayo.com/"><i class="fa fa-home" aria-hidden="true"></i> Home</a></li>
                        <li><a href="https://forums.hostmayo.com"><i class="fa fa-comments" aria-hidden="true"></i> Forums</a></li>
                        <li><a href="https://hostmayo.com/blog/"><i class="fa fa-wpforms" aria-hidden="true"></i> Blog</a></li>
                        <li><a class="search" href="#search"><i class="fa fa-search"></i> Search</a></li>
                        {custom_menu}
                    </ul>
                    
                </div>
                <div class="col-3-nav nav-icon">
                    {if $User.SignedIn}
                    <span class="mobile-close">
                        {module name="MeModule"}
                    </span> 
                    
                    <a class="mobile-open mobile-menu" href="#mobile-menu"><i class="icon icon-menu"></i></a>
                    {else}
                        {signin_link wrap="span" format="<a href='%url' title='%text' class='%class'><span class='icon icon-signin'><span></a>"}
                    {/if} 
                </div>    
            </nav>
        </div>
    </div>
    <div id="Body" class="Container">
        
        {if !InSection(array("CategoryList", "CategoryDiscussionList", "DiscussionList", "Entry", "Profile", "ActivityList", "ConversationList", "PostConversation", "Conversation", "PostDiscussion"))}
        <div class="Row">
            <div class="col-12 BreadcrumbsWrapper">{breadcrumbs}</div>
        </div>
        {/if}
        <div class="Row">
            <div class="col-9" id="Content">
                <div class="ContentColumn">
                    {asset name="Content"}
                </div>
            </div>
            {if !InSection(array("Entry", "PostDiscussion"))}
            <div class="col-3 PanelColumn" id="Panel">
                {asset name="Panel"}
            </div>
            {/if}
        </div>
    </div>
    {if $Homepage}
    <div id="Body" class="Container">
       <div class="Row statrow">
            <div class="col-8 stats" id="Content">
                <div class="top">Forums Stats</div>
                <div class="row">
                    <div class="col-3">Threads: {$threads}
                    </div>
                    <div class="col-3">Posts: {$posts}
                    </div>
                    <div class="col-3">Members: {$members}
                    </div>
                </div>
            </div>
        </div>	
    </div>
    {else}
        <style>
          .ContentColumn
            {
                border: 1px solid rgba(219, 219, 219, 0.5);

            }
        </style>
    {/if}
    <footer>
        <div class="Container">
            <div class="Row footer">
                <div class="col-12">
                    <p class="logo"> {t c="Host Mayo"} &copy; {$smarty.now|date_format:"%Y"} </p>  
                </div>
            </div>
        </div>
        {asset name="Foot"}
    </footer>
<div id="search">
    <button class="modal-close"></button>
    {searchbox}
</div>
<div id="mobile-menu">
    <button class="modal-close"></button>
    <ul>
        <li><a href="https://hostmayo.com/"><i class="fa fa-home" aria-hidden="true"></i> Home</a></li>
        <li><a href="https://forums.hostmayo.com"><i class="fa fa-comments" aria-hidden="true"></i> Forums</a></li>
        <li><a href="https://hostmayo.com/blog/"><i class="fa fa-wpforms" aria-hidden="true"></i> Blog</a></li>
        {custom_menu}
        {profile_link}
        {inbox_link}
        {bookmarks_link}
        {dashboard_link}
        {signinout_link}
    </ul>
</div>
{event name="AfterBody"}
{literal}
 <script>
 $('body').show();
 $('.version').text(NProgress.version);
 NProgress.start();
 setTimeout(function() { NProgress.done(); $('.fade').removeClass('out'); }, 1000);
 </script>
 {/literal}
</body>
</html>