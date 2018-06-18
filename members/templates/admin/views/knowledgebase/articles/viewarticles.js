var articles = {};
articles.categoryid = -1;

$(document).ready(function() {

    $('#addArticleBtn').click(function() {
        articles.window.show();
    });

    $('#addcategorybtn').click(function() {
        articles.categorywindow.show({
            params:{
                parentId: articles.categoryid,
                categoryId: 0
            }
        });
    });

    $('#deletecategorybtn').click(function() {
        if ($(this).attr('disabled')) { return false; }
        RichHTML.msgBox(lang('Are you sure you want to delete this category?'),
        {
            type:"confirm",
            buttons: {
                confirm: {
                    text: lang('Yes')
                },
                cancel: {
                    text: lang('Cancel'),
                    type: 'cancel'
                }
            }
        },
        function(result) {
            if (result.btn == lang('Yes')) {
                $.ajax({
                   url: 'index.php?action=deletecategory&fuse=knowledgebase',
                   data: {categoryId:  articles.categoryid},
                   success: function(data) {
                       json = ce.parseResponse(data);
                       if (!json.error) {
                           //remove options from both dropdowns
                           $('#article-category-move option[value='+json.categoryid+'], #article-category-filter option[value='+json.categoryid+']').remove();
                           $("#article-category-filter").val(json.parentId).trigger('change');
                       }
                   }
                });
            }
        });
    });

    $('#editcategorybtn').click(function() {
        articles.categorywindow.show({params:{ categoryId: articles.categoryid}});
    });

    articles.ArticleTitleRenderer = function(text,row) {

        if (row.isdraft == 1) {
            row.excerpt = lang('Type')+': '+lang('Draft')+'<br/>'+row.excerpt;
            row.access = 999;
        } else {
            row.excerpt = lang('Type')+': '+row.accessstring+"<br/>";
        }

        var name = String.format('<span class="grid-color-highlight grid-color-articleaccess-{0}"></span>',row.access);

        if (row.canViewArticles) {
            name += String.format('<a data-toggle="tooltip" data-html="true" title="{1}" onclick="javascript:articles.window.show({params:{ articleId: {0}, article_image_files: null}});">',
              row.id, row.excerpt);
        }
        name += ce.htmlspecialchars(text);
        if (row.canViewArticles) {
            name += '</a>';
        }
        name += String.format('<br/>on {0} rated: {1}', row.datecreated, row.rating);

        return name;
    }

    $('#article-category-move').change(function() {
        $.post("index.php?fuse=knowledgebase&action=movearticles", {newCategories: $(this).val(),articleId:articles.grid.getSelectedRowIds()},
                   function(data){ articles.grid.reload({params:{start:0}}); });
        $(this).select2("val",-1);
    });

    $('#article-category-filter').change(function() {
        articles.categoryid = $(this).val();
        articles.grid.reload({params:{category:articles.categoryid, start:0}});
        if ($(this).val() == -1) {
            $('#editcategorybtn').attr('disabled','disabled');
            $('#deletecategorybtn').attr('disabled','disabled');
        } else {
            $('#editcategorybtn').removeAttr('disabled');
            $('#deletecategorybtn').removeAttr('disabled');
        }
    });

    $('#delArticles').click(function(){
       if ($(this).attr('disabled')) { return false; }
       RichHTML.msgBox(lang('Are you sure you want to delete the selected article(s)'),
       {
            type:"confirm",
            buttons: {
                confirm: {
                    text: lang('Yes')
                },
                cancel: {
                    text: lang('Cancel'),
                    type: 'cancel'
                }
            }
       }, function(result) {
            if(result.btn == lang('Yes')) {
                $.post("index.php?fuse=knowledgebase&action=deletearticles", {articleId:articles.grid.getSelectedRowIds()},
                   function(data){ articles.grid.reload({params:{start:0}}); });
            }
       });
       return true;
    });

    articles_category_format = function (option) {
        var prename = "";
        if (option.element != undefined) {
            var blanks = option.element[0].attributes.getNamedItem('data-blanks').value;
            for (var x=0; x < blanks; x++) {
                prename += $('<div/>').html("&nbsp;&nbsp;&nbsp;&nbsp;").text();
            }
            if (blanks > 0) {
                prename += "|-";
            }
        }
        return prename+option.text;
    }

    $('#article-grid-filter').change(function(){
    	articles.grid.reload({params:{start:0,limit:$(this).val()}});
    });

    $('#article-sort-filter').change(function(){
    	articles.grid.reload({params:{start:0,articlefilters:$(this).val()}});
    });

    articles.grid = new RichHTML.grid({
        el: 'articles-grid',
        metaEl: 'articlelistlabel',
        root: "data",
        totalProperty: "totalcount",
        url: 'index.php?fuse=knowledgebase&action=getarticlelist',
        baseParams: { limit: clientexec.records_per_view, articlefilters:"all" ,sort: "id" },
        columns:[{
            text:       "",
            xtype:      "drag"
        },{
            id:         "cb",
            dataIndex:  "id",
            xtype:      "checkbox"
        },{
            id: 'id', // id assigned so we can apply custom css (e.g. .x-grid-col-topic b { color:#333 })
            text: "Id",
            width: 30,
            dataIndex : "id",
            sortable: true,
            align:"center"
        },{
            id: 'articletitle', // id assigned so we can apply custom css (e.g. .x-grid-col-topic b { color:#333 })
            text: lang("Title"),
            dataIndex : "articlename",
            renderer : articles.ArticleTitleRenderer,
            sortable: false,
            flex: 1,
            align: 'left'
        },{
            id: 'author',
            text: lang("Author"),
            dataIndex : "author",
            width: 120,
            sortable: true,
            align:"right"
        },{
            id: 'totalvisitors', // id assigned so we can apply custom css (e.g. .x-grid-col-topic b { color:#333 })
            text: lang("Views"),
            width: 50,
            dataIndex : "totalvisitors",
            align:"center",
            sortable: true
        },{
            id: 'totalcomments', // id assigned so we can apply custom css (e.g. .x-grid-col-topic b { color:#333 })
            text: lang("Modified"),
            width: 140,
            dataIndex : "modified",
            align:"center",
            sortable: true
        },{
            id: 'myorder', // id assigned so we can apply custom css (e.g. .x-grid-col-topic b { color:#333 })
            text: lang("Order"),
            width: 55,
            dataIndex : "myorder",
            align:"center",
            renderer: function(val,record) {
                var result = '';
                if (record.canViewArticles) {
                  result += "<a href='javascript:void(0);' class='update-order' data-article-id='"+record.id+"' data-myorder='"+record.myorder+"'>";
                }
                result += val;
                if (record.canViewArticles) {
                  result += "</a>";
                }
                return result;
            },
            sortable: true
        }
        ]
    });

    articles.grid.render();

    $(articles.grid).bind({
        "drop": function (event, data) {

            RichHTML.mask('#'+articles.grid.id);
            $.ajax({
                url: 'index.php?fuse=knowledgebase&controller=articles&action=updateorder',
                dataType: 'json',
                type: 'POST',
                data: {sessionHash: gHash,ids:articles.grid.getRowValues('id'),orders:articles.grid.getRowValues('myorder')},
                success: function(data) {
                    data = ce.parseResponse(data);
                    if (!data.error) {
                        $.each(data.articles,function(i,o){
                            $('a[data-article-id='+i+']').text(o.order).attr("data-myorder",o.order);
                        });
                    }
                    RichHTML.unMask();
                }
            });

        }
    });

    $(document).on('click','.update-order',function(){
        var article_id = $(this).attr("data-article-id");
        var self = $(this);
        RichHTML.prompt("Article Order", {value:$(this).attr("data-myorder")},function(ret){
            if ( (trim(ret.elements.value) !== "") && (isNum(ret.elements.value)) ){
                var neworder = ret.elements.value;
                $.ajax({
                    url: 'index.php?fuse=knowledgebase&controller=articles&action=updatearticleorder',
                    dataType: 'json',
                    type: 'POST',
                    data: {sessionHash: gHash,id: article_id, myorder: ret.elements.value},
                    success: function(response) {
                        response = ce.parseResponse(response);
                        if (!response.error) {
                            $(self).text(neworder).attr("data-myorder",neworder);
                        }
                    }
                });

            } else {
                ce.msg(lang('Invalid value entered'));
            }
        });
    });


    articles.categorywindow = new RichHTML.window({
        id: 'category-window',
        escClose: true,
        height: '305',
        showSubmit: true,
        actionUrl: 'index.php?fuse=knowledgebase&controller=index&action=savecategory',
        width: '450',
        title: lang("Category Window"),
        url: 'index.php?fuse=knowledgebase&view=category&controller=index',
        onSubmit: function(e, data) {
            data = e.data; // wtf
            if (data.newCat) {
                var blanks = parseInt($('#article-category-filter option[value='+data.parentId+']').attr('data-blanks'))+1;
                $('#article-category-move option[value='+data.parentId+'],#article-category-filter option[value='+data.parentId+']').after('<option value="'+e.data.catId+'" data-blanks="'+blanks+'">'+data.catName+'</option>');
                $("#article-category-filter").val(data.catId).trigger('change');
            } else {
                $('div#s2id_article-category-filter a.select2-choice span').text(data.catName);
                $('#article-category-move option[value='+data.catId+'],#article-category-filter option[value='+data.catId+']').text(data.catName);
            }
        }
    });

    articles.window = new RichHTML.window({
        id: 'article-window',
        escClose: false,
        height: '616',
        grid: articles.grid,
        showSubmit: true,
        actionUrl: 'index.php?fuse=knowledgebase&controller=articles&action=save',
        width: '720',
        title: lang("Article Window"),
        url: 'index.php?fuse=knowledgebase&view=article&controller=articles'
    });

    // **** listeners to grid
    $(articles.grid).bind({
        "load" : function(event,data) {
            $('#article-category-move-label').css('color','#ddd');
            $('#article-category-move').select2("disable");
        },
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#delArticles').removeAttr('disabled');
                $('#article-category-move-label').css('color','#333');
                $('#article-category-move').select2("enable");
            } else {
                $('#delArticles').attr('disabled','disabled');
                $('#article-category-move-label').css('color','#ddd');
                $('#article-category-move').select2("disable");
            }
        }
    });

    //if we have tags
    $.get("index.php?action=availabletagsarticles&fuse=knowledgebase&controller=articles",function(response){
        response = ce.parseResponse(response);
        $.each(response.tags, function(i,o){
            $('.existing_filters').append("<option value='"+o+"'>"+o+"</option>");
        });

        if (response.tags.length > 0) {
            $('.existing_filters').select2({
                minimumResultsForSearch: 10,
                width:'resolve'
            });
            $('.td_filter_by_tag').show();
        }
    });

    $(document).on('change','.td_filter_by_tag',function(el){
        articles.grid.reload({params:{start:0,filterbytag:el.val}});
    });


});
