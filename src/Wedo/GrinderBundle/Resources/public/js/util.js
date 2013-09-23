$(document).ready(function() {
    $('input[name=add]').on('click', function(e) {
        var id = $(e.target).data('id');
        var quantity = $('select[name='+id+']').val();
        $.ajax(
        {
            type: "POST",
            url: '/grinder/add',
            data: { "id": id, "quantity": quantity }
        }
        ).done(function(html) {
            refresh();
        });
    });

    $('input[name=refresh]').on('click', refresh)

    var refresh = function(e) {
        $.ajax(
        {
            type: 'GET',
            url: '/grinder/get',
        }).done(function(html) {
            $('div[name=current]').html(function() {
                var finalContent = '';
                $.each(html, function(id, arr) {
                    finalContent += '<div><img src="'+arr.img+'" /> '+arr.quantity+' Bean'+(arr.quantity > 1 ? 's' : '')+'</div>';
                })
                return finalContent;
            });
        });
    };
});