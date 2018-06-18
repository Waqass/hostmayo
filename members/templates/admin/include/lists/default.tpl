<div id="{TABLEID}">
<!-- BEGIN DYNAMIC BLOCK: hiddeninputs --> 
{HIDDENINPUTS}
<!-- END DYNAMIC BLOCK: hiddeninputs -->
<table width=100% cellpadding=0 border=0 cellspacing=1>
<!-- BEGIN DYNAMIC BLOCK: match -->
<tr>
<td>[l]Showing Records from[/l] {FIRSTRECORD} [l]to[/l] {LASTRECORD} [l]of[/l] {TOTALRECORDS}
<input type="hidden" value="{TOTALRECORDS}" name="totalrecs_c" id="totalrecs_c"> 
</td>
<td align=right>{TABLENAVLINKS}</td>
</tr>
<!-- END DYNAMIC BLOCK: match -->
</table>
<table width=100% cellpadding=0 border=0 cellspacing=0><tr>
<td class="snapshot-header-left"></td>
<td class="snapshot-header"><font class=snapshot-header-text>{LABEL}</font><font class=snapshot-header-description></font></td>
<td align=right class="snapshot-header"></td><td class="snapshot-header-right"></td></tr></table>

<table width=100% class="sort-table" id="{TABLEID}_table" cellpadding=0 border=0 cellspacing=0>
<thead>
    <tr>
        <!-- BEGIN DYNAMIC BLOCK: headercol --><td {ALIGN} {WIDTH} onClick="tableNav('1', '{TABLEID}', '', '{COLNAME}', '{ORDERSORT}', '', '', '{FUSE}', '{ACTION_TABLE}', '{LINKSID}')">{HEADERCOL}{ORDERIMG}</td><!-- END DYNAMIC BLOCK: headercol -->
    </tr>
</thead>
<tbody id="{TABLEID}_rows">
  <!-- BEGIN DYNAMIC BLOCK: row --><tr id="row_{TABLEID}_{ROWID}" class="{ROWCLASS}" style="{ROWSTYLE}">
      <!-- BEGIN DYNAMIC BLOCK: column --><td {COLWIDTH} {NOWRAP}>{COLUMN}</td><!-- END DYNAMIC BLOCK: column -->
    </tr>
    <!-- END DYNAMIC BLOCK: row -->
</tbody>  
</table>
<!-- BEGIN DYNAMIC BLOCK: nomatch -->
{NOMATCH}
<!-- END DYNAMIC BLOCK: nomatch -->
</div>
