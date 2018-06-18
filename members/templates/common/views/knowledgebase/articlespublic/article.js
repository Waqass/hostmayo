function scriptPrettify()
{
  // hljs.initHighlightingOnLoad();
  $('#kbarticle pre').each(function(i, e) {hljs.highlightBlock(e)});
}

var script_tag_css = document.createElement('link');
script_tag_css.setAttribute("rel","stylesheet");
script_tag_css.setAttribute("type","text/css");
script_tag_css.setAttribute("href","//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/styles/tomorrow-night-eighties.min.css");
(document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag_css);

var script_tag_prettify = document.createElement('script');
script_tag_prettify.setAttribute("type","text/javascript");
script_tag_prettify.setAttribute("src","//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/highlight.min.js");
(document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag_prettify);
if (script_tag_prettify.readyState) {
  script_tag_prettify.onreadystatechange = function () { // For old versions of IE
      if (this.readyState == 'complete' || this.readyState == 'loaded') {
          scriptPrettify();
      }
  };
} else { // Other browsers
  script_tag_prettify.onload = scriptPrettify;
}

$(document).ready(function(){



   //  if (clientexec.isAdmin) {
   //      $('#kbarticle').editInPlace({
   //          url: "index.php?fuse=knowledgebase&controller=articles&action=updatearticlecontent",
   //          field_type: "textarea",
   //          success: function(a){
   //              a =  jQuery.parseJSON(a);
   //                  $(this).html(a.html);
   //              },
   //              textarea_rows: "20",
   //              bg_over: "#fff",
   //              use_html:true,
   //              update_value: 'articleContent',
   //              params: "articleId="+article.id
   //          });
   // }

    $('#kbarticle img').each(function(i,o){
          var title = $(o).attr('alt');
          if ( title === undefined ) {
            title = '';
          }
          $(o).addClass('article-image');
          $(o).wrap('<a class="btn-zoom fancybox fancybox.image" title="'+title+'" rel="article-group" href="'+$(o).attr("src")+'"></a>');
    });

    $(".fancybox").fancybox({
        openEffect  : 'elastic',
        closeEffect : 'elastic',
        margin      : [20, 60, 20, 60], // Increase left/right margin,
        helpers : {
          overlay : {
              css : {
                  'background' : 'rgba(136, 136, 136, 0.952941)'
              }
          },
          title: {
              type: 'over'
          }
        }
    });

   $('#ratearticle .rating .star').bind('click',function(){
        var rate = $(this).attr('data-rating');
        $.ajax({
            url: 'index.php?fuse=knowledgebase&action=rate&controller=articles',
            data: {rating : rate, articleId : article.id},
            dataType: 'json',
            success: function(data)
            {
                if (data.error) return;
                $('#ratearticle h2').after("<span class='alert alert-info'>"+data.message+"</span>");
                $('#ratearticle .rating').remove();
            }
        });
   });
});