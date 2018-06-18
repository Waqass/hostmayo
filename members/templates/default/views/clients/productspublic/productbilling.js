$('.toogleautomaticcccharge').click(function() {
    $.post('index.php?fuse=clients&controller=products&action=toogleautomaticcccharge', { id: productid }, function(data) {
        ce.parseResponse(data);
    });
});
