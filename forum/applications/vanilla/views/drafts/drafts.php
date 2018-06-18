<?php if (!defined('APPLICATION')) exit();

foreach ($this->DraftData->resultArray() as $Draft) {
    $Offset = val('CountComments', $Draft, 0);
    if ($Offset > c('Vanilla.Comments.PerPage', 30)) {
        $Offset -= c('Vanilla.Comments.PerPage', 30);
    } else {
        $Offset = 0;
    }

    $draftID = val('DraftID', $Draft);
    $discussionID = val('DiscussionID', $Draft);
    $excerpt = sliceString(Gdn_Format::text(val('Body', $Draft)), 200);

    $isDiscussion = (!is_numeric($discussionID) || $discussionID <= 0);
    $orphaned = !val('DiscussionExists', $Draft);

    $editUrl = ($isDiscussion || $orphaned) ? '/post/editdiscussion/0/'.$draftID : '/discussion/'.$discussionID.'/'.$Offset.'/#Form_Comment';
    $deleteUrl = 'vanilla/drafts/delete/'.$draftID.'/'.Gdn::session()->transientKey().'?Target='.urlencode($this->SelfUrl);
    ?>
    <li class="Item Draft">
        <div
            class="Options"><?php
                echo anchor(t('Draft.Delete', '&times;'), $deleteUrl, 'Delete'); ?></div>
        <div class="ItemContent">
            <?php echo anchor(Gdn_Format::text(val('Name', $Draft), false), $editUrl, 'Title DraftLink'); ?>
            <?php if ($excerpt) : ?>
                <div class="Excerpt">
                    <?php echo anchor($excerpt, $editUrl); ?>
                </div>
            <?php endif; ?>
        </div>
    </li>
<?php
}
