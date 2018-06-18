clientexec = clientexec || {};

clientexec.populate_report = function(data, chart_id, args)
{

  //set loading images
  $('.report-title').html('<i class="icon-spinner icon-spin icon-large" style="margin-bottom:9px;"></i>&nbsp;&nbsp;'+lang("Loading Graph"));
  $('.report-description').text('');

  var addition_args = "";
  var sent_option_id = null;
  if(typeof(chart_id) == "undefined") chart_id = "#myChart";
  if(typeof(args) != "undefined") {
    var addition_args = "&"+$.param( args );
  }

  if (typeof(args) != "undefined" && typeof(args['option_id']) != "undefined") {
    //let's save this so we can set as default when we get graph with options back
    sent_option_id = args['option_id'];
  }

  var newGraph = data.split("-");
  var report = newGraph[0];
  var type = newGraph[1];

  var url = "index.php?fuse=reports&action=getreportgraph&graphdata=1&report="+report+"&type="+type+addition_args;

  var opts = {
      "axisPaddingLeft" : 25,
      "paddingLeft" : 50,
      "paddingRight": 0,
      "tickHintY":5,
      "paddingBottom" : 40,
      "dataFormatY": function (y) {
          return parseInt(y);
      },
      "tickFormatY": function (y) {

          if (clientexec.format == "addcomma") {
            y = y.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
          }
          return clientexec.graphpre+y+clientexec.graphpost;
      },
      "dataFormatX": function (x) {

        if (clientexec.graph_xType == "number") {
            return x;
        } else {
            // no idea why this, but it used to be hard-coded to "2013"
            // which was screwing up the daysofweek further down below
            x = new Date().getFullYear() + x.substring(4);
            return d3.time.format("%Y-%m-%d").parse(x);
        }

      },
      "tickFormatX": function (x) {
          //check if we have a label field
          if (x instanceof Array) {
            x = x[1];
          }

          if (clientexec.graph_xType == "number") {
            return x;
          } else if (clientexec.graph_xType == "daysofweek") {
            return d3.time.format("%A")(x);
          } else {
            return d3.time.format("%B")(x);
          }

      },
      "mouseover": function (d, i) {

          clientexec.graph_tt = $('<div data-toggle="popover-hover" data-container=".graph-wrapper" data-html="true" title="" class="graph-tool-tip tip-target"></div>')[0];
          leftOffset = -(~~$('html').css('padding-left').replace('px', '') + ~~$('body').css('margin-left').replace('px', '')),
          topOffset = -32;
          document.body.appendChild(clientexec.graph_tt);

          var pos = $(this).offset();
          var content;

          topOffset = -8;

          if ($(this).attr('width')) {
            leftOffset = (parseInt($(this).attr('width')) / 2)
          } else {
            leftOffset = 7;
          }


          $(clientexec.graph_tt).attr("data-placement","top");
          if (d["tip"]) {
              content = d["tip"];
          } else {
              content = d.x + ": " + d.y;
          }
          $(clientexec.graph_tt).attr("data-content",content)
            .css({top: topOffset + pos.top, left: pos.left + leftOffset})
            .show().popover("show");

      },
      "mouseout": function (x) {
          $(clientexec.graph_tt).hide().popover("hide");
          $(clientexec.graph_tt).remove();
          $('.popover-hover').remove();
      }
  };

  $.get(url, function(response){
      var json = ce.parseResponse(response);

      if(typeof(json.data) == "undefined") return;

      var url = 'index.php?fuse=reports&view=viewreport&controller=index&report='+report+'&type='+type

      $('.report-title').html("<a href='"+url+"'>"+json.title+"</a>");
      $('.report-description').text(json.subtitle);
      var data = jQuery.parseJSON(json.data);
      var bar_type = "line-dotted";
      if (data.type == "bar") {
        bar_type = "bar";
      }

      clientexec.graphpre = "";
      clientexec.graphpost = "";
      clientexec.format = null;
      if (data.yType == "currency") {
        clientexec.graphpre = data.yPre;
        clientexec.format = data.yFormat;
      } else if (data.yType == "percent") {
        clientexec.graphpost = "%";
      }

      clientexec.graph_xType = data.xType;
      if (data.main.length > 0) {
        opts.tickHintX = data.main[0].data.length;
        var containsData = false;
        $.each(data.main, function(key, val) {
          $.each(val.data, function(key, val) {
            if (val.y > 0) {
              containsData = true;
              return false;
            }
          });
        });
        if (!containsData) {
          // workaround xchart's nasty habit of showing negative y-axis value when there's no data
          opts.yMin = 0;
        }
      }

      if (typeof(clientexec.myChart) != "undefined") {
        $('.graph-tool-tip').remove();
        $(chart_id).empty();
      }
      clientexec.myChart = new xChart(bar_type, data , chart_id, opts);


      $('.report_options').html('');
      if ( typeof(response.options.label) != "undefined" ) {

        var option_content  = '<div class="dropdown pull-right">';
            option_content += '<a class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="report_option_label">'+response.options.label+'</span><b class="caret"></b></a>';
            option_content += '<ul class="dropdown-menu">';

            $.each(response.options.values, function(name,value){
              option_content += '<li><a href="#" class="report-option-link" data-graph-id="'+report+'-'+type+'" data-option-id="'+value+'">'+name+'</a></li>';
            });

            option_content += '</ul>';
            option_content += '</div>';

        $('.report_options').append($(option_content));

        //let's update label of default
        if (sent_option_id != null) {
          $('.report_option_label').text($('.report_options ul a[data-option-id="'+sent_option_id+'"]').text());
        } else if (typeof(response.options.defaultid) != "undefined") {
          $('.report_option_label').text($('.report_options ul a[data-option-id="'+response.options.defaultid+'"]').text());
        }

        $('.report-option-link').unbind('click');
        $('.report-option-link').bind('click', function(e){
          clientexec.populate_report($(this).attr('data-graph-id'),"#myChart", {indashboard:1,option_id:$(this).attr('data-option-id')});
          $('.report_option_label').text($('.report_options ul a[data-option-id="'+$(this).attr('data-option-id')+'"]').text());
        });

      }


  });
}
