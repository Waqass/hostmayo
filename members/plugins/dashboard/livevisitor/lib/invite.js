cechat_invite = {};

cechat_invite.rpc_img = new Image();
window.onscroll = cechat_invite.onScroll;

cechat_invite.updateStatus = function(a_adi){
    /*
   if(document.bt_invitation_id != ''){
      if(window.pageViewer && pageViewer.reinvite) {
         pageViewer.reinvite(document.bt_invitation_id, a_adi);
      } else {
         var rpc_url = 'http://rpc.boldchat.com/aid/2307475884/bc.irpc/PublicServer?m=inviteStatus&p0=2307475884';
         rpc_url += ('&p1=' + document.bt_invitation_id);
         rpc_url += ('&p2=' + (document.bt_auto_invite ? '1' : '0'));
         rpc_url += ('&p3=' + a_adi);
         rpc_url += ('&kill=' + (new Date()).getTime());
         cechat_invite.rpc_img.src = rpc_url;
      }
    }*/
};

cechat_invite.onScroll = function() {
  if(!document.ce_document_relative && ce_animation_box_sty) {
    ce_animation_box_sty.top = ce_top(ce_currentY) + 'px';
    ce_animation_box_sty.left = ce_left(ce_currentX) + 'px';
  }
};


//console.debug(cechat_invite);
