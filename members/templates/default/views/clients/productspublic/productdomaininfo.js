$('.reglock').click(function() {
    var reglockValue = $('input[name="registrarlock"]:checked').val();

    $.post('index.php?fuse=clients&controller=products&action=updateregistrarlock', { value: reglockValue, id: productid }, function(data) {
        ce.parseResponse(data);
    });
});
