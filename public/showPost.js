
$(document).on("click", "#btnComment", function (){
    $('#btnComment').html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true">'+
            '</span>'+' Loading...')
    let ruta = Routing.generate('commentPost');
    let id = $(this).data('id');
    let title = $('#titlePost').val();
    let content = $('#contentPost').val();
    $.ajax({
        type: 'GET',
        url: ruta,
        data: {id: id, title: title, content: content},
        dataType: 'json',
        async: true,
        success: function (data){
            $('#btnComment').html('Post');
            console.log(data['commentTitle'])
            $('.comment').append(
                '<li className="media">'+
                '<a href="#" className="pull-left">'+
                '<img src="https://bootdey.com/img/Content/user_1.jpg" alt="" className="img-circle">'+
                '</a>'+
                '<div className="media-body">'+
                '<strong className="text-success">'+data['commentTitle']+'</strong>'+
                '<p>'+data['commentContent']+'</p>'+
                '</div>'+
                '</li>'
            );
        }, error: function (){

        }
    })
})