var comments = {};

$(document).ready(function() {


    comments.expanderRenderer = function(text,row)
    {
    	var html="";
        text = nl2br(text);
    	html += "<b>"+lang("User")+"</b>: "+ row.commentUser+"<br/>";
    	html += "<b>"+lang("Email")+"</b>: "+ row.commentEmail+"<br/>";
    	html += "<b>"+lang("Comment")+"</b>: " + text;

    	return html;
    }


    // ** grid definition
    comments.grid = new RichHTML.grid({
        el: 'KBcomments-grid',
        root: "data",
        totalProperty: "totalcount",
        url: 'index.php?fuse=knowledgebase&controller=comments&action=getkbcomments',
	    baseParams: { limit: clientexec.records_per_view, filterComents: $('#KBcomments-grid-filterby').val(),sort: 'commentAddedOn', dir: 'desc'},
        columns: [{
        		dataIndex:	"comment",
        		xtype:	    "expander",
        		renderer:	comments.expanderRenderer
	        },{
                id:         "cb",
                dataIndex:  "commentId",
                xtype:      "checkbox"
            },{
            	id: 		"articleId",
            	dataIndex:	"articleId",
            	text:		lang("Art"),
            	align:		"center",
            	sortable:	true,
            	width:		50,
            	renderer:   function(text, row) {
            		var arr = text.split('/');
                var str = '';
                if (row.canViewArticles) {
                    str += "<a target='blank' href='../index.php?fuse=knowledgebase&view=article&controller=articles&articleId="+arr[0]+"'>"
                }
                str += arr[0];
                if (row.canViewArticles) {
                  str += "</a>";
                }
            		return str;
            	}
            },{
            	id: 		"articleTitle",
            	dataIndex:	"articleTitle",
            	text:		lang("Title"),
            	align:		"left",
                flex: 1
            },{
            	id:			"commentAddedOn",
            	dataIndex:	"commentAddedOn",
            	text:		lang("Added"),
            	align:		"center",
            	sortable:	true,
            	width:		110
            },{
                id:         'commentIsApproved',
                text:     lang("Approved"),
                width:      80,
                dataIndex:  "commentIsApproved",
                align: 'center'
            }
        ],
    });
    comments.grid.render();
    // **** start click binding

    // set initial label
    $('#kb-comment-label').text(lang("Viewing")+" "+$('#KBcomments-grid-filterby option:selected').text()+" "+lang("Comments"));


    $('#KBcomments-grid-filter').change(function(){
    	comments.grid.reload({params:{start:0,limit:$(this).val()}});
    });

    $('#KBcomments-grid-filterby').change(function(){
    	if ($(this).val()!=="articleId") {
    		comments.grid.reload({params:{start:0,filterComents:$(this).val()}});
			$('#kb-comment-label').text(lang("Viewing")+" "+$(this).find('option:selected').text()+" "+lang("Comments"));
    		$('#KBcomments-grid-filterby-artid-td').hide();
    	} else {
    		$('#KBcomments-grid-filterby-artid-td').show();
    	}
    });

    // **** listeners to grid
    $(comments.grid).bind({
	    "rowselect": function(event,data) {
	        if (data.totalSelected > 0) {
	        	$('#delComments').removeAttr('disabled');
	        	$('#appComments').removeAttr('disabled');
	        } else {
	        	$('#delComments').attr('disabled','disabled');
	        	$('#appComments').attr('disabled','disabled');
	        }
	    }
    });

	$('#KBcomments-grid-filterby-artid-td').keydown(function(e){
		if (e.keyCode === 13) {
			if (trim($(this).find('input').val()) === ""){
				comments.grid.reload({params:{start:0,filterComents:"all"}});
				$('#kb-comment-label').text(lang("Viewing All Comments"));
			} else {
    			comments.grid.reload({params:{start:0,filterComents:"articleId",articleId:$(this).find('input').val()}});
				$('#kb-comment-label').text(lang("Viewing Comments for Article "+$(this).find('input').val()));
    		}
		}
	});

    // **** lets bind our buttons
    $('#delComments').click(function () {

        if ($(this).attr('disabled')) { return false; }
     	RichHTML.msgBox(lang('Are you sure you want to delete the selected comment(s)'),
    		{
    			type:"confirm"
    		}, function(result) {
				if(result.btn === lang("Yes")) {
					$.post("index.php?controller=comments&action=delete&fuse=knowledgebase", {commentId:comments.grid.getSelectedRowIds()},
					   function(data){
	   						comments.grid.reload({params:{start:0}});
					});
				}
			});
    });

	$('#appComments').click(function () {
		if ($(this).attr('disabled')) { return false; }
		$.post("index.php?action=approve&controller=comments&fuse=knowledgebase", {commentId:comments.grid.getSelectedRowIds()},
					   function(data){
	   						comments.grid.reload();
					});
	});


});
