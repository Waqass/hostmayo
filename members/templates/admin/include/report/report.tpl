<!-- BEGIN DYNAMIC BLOCK: reportHeader -->
<table width="80%" border=0 cellpadding=0 cellspacing=0>
 <tr>
  <td>
<!-- END DYNAMIC BLOCK: reportHeader -->

<!-- BEGIN DYNAMIC BLOCK: groupHeader -->
       <div id='{GROUPID}' style='display:{GROUPVISIBILITY};' >
        <!-- BEGIN DYNAMIC BLOCK: title -->
            &nbsp;<b><u>{TITLE}</u></b>
        <!-- END DYNAMIC BLOCK: title -->
	    <table border=0 cellpadding=1 cellspacing=1 width=100%>
	      <tr bgcolor='#eeeeee'>
	      <!-- BEGIN DYNAMIC BLOCK: label -->
	      <td width={WIDTH}><b>{LABEL}</b></td>
	      <!-- END DYNAMIC BLOCK: label -->
	      </tr>
<!-- END DYNAMIC BLOCK: groupHeader -->

      <!-- BEGIN DYNAMIC BLOCK: row -->
          <tr>
		    <!-- BEGIN DYNAMIC BLOCK: column -->
		    <td {COLPROPERTIES}>{COLUMN}</td>
		    <!-- END DYNAMIC BLOCK: column -->
		  </tr>
      <!-- END DYNAMIC BLOCK: row -->

<!-- BEGIN DYNAMIC BLOCK: groupFooter -->
	    </table>
        <br/><br/>        
        </div>
<!-- END DYNAMIC BLOCK: groupFooter -->

<!-- BEGIN DYNAMIC BLOCK: reportFooter -->
  </td>
 </tr>
</table>
<!-- END DYNAMIC BLOCK: reportFooter -->