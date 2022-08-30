
$(document).on('keyup', '#searchUser', function () {
    let email = $('#searchUser').val();
    let ruta = Routing.generate('searchUser')
    $.ajax({
            method: 'GET',
            url: ruta,
            data: {email: email},
            async: true,
            dataType: 'json',
        success: function (data){
                console.log('Exito '+ data['email']);
                if($('#searchUser').val().length === 0){
                    $('#userResult').hide();
                }
                $('#userResult').html('').append(data['email']);
    },error: function (){
            }
    })
})

function deletePost(id){
    confirm("Are you sure you want to delete?");
    let ruta = Routing.generate('deletePost');
    $.ajax({
        method: 'DELETE',
        url: ruta,
        data: {id: id},
        async: true,
        dataType: 'json',
        success: function (data){
            Swal.fire(
                'Good job!',
                'You clicked the button!',
                'success'
            )
            $('#dataTable-'+id).hide();
        },error: function (){
        }
    })
}

$(document).on('click', '#updatePost', function () {
    let ruta = Routing.generate('updatePost');
    let id = $('#idPost').val();
    let title = $('#titlePost').val();
    let content = $('#contentPost').val();
    $.ajax({
        method: 'GET',
        url: ruta,
        data: {id: id, title: title, content: content},
        dataType: 'json',
        success: function (){
            $('#closeModal').click();
            Swal.fire(
                'Good job!',
                'You clicked the button!',
                'success'
            )
            $('#dataTable-'+id).load(' #dataTable-'+id);
        }, error: function (){
            console.log('ERRORRR!!')
        }
    })
});

$(document).on('click', '#btnUpdate', function () {
    let id = $(this).data('id');
    let title = $(this).data('title');
    let content = $(this).data('content');
    console.log(title);
    $('#idPost').val(id);
    $('#titlePost').val(title);
    $('#contentPost').val(content);
});

let count = 0;
window.addEventListener('scroll', ()=>{
    console.log("SCROLL!");
    if(window.scrollY + window.innerHeight >= document.documentElement.scrollHeight){
        loadTweets();
    }
})

function loadTweets(){
    let ruta = Routing.generate('loadTweets');
    count++;
    $.ajax({
        type: 'GET',
        url: ruta,
        data: {count : count},
        async: true,
        dataType: 'json',
        success: function (data){
            console.log(data['posts']);
            for (let i = 0; i < data['posts'].length; i++) {
                $('.scroll').append(
                    '<div class="card" style="width: 18rem;align-content: center;margin-bottom: 8px;" id="dataTable-{{ post.id }}">' +
                    '<img className="card-img-top" src="/images/'+data['posts'][i].Image+'" style="width: 700px;">' +
                    '<div class="card-body">' +
                    '<h5 class="card-title">' + data['posts'][i].title + '</h5>' +
                    '<p class="card-text">' + data['posts'][i].content + '</p>' +
                + '</div>' +
                    '</div>'
            )
                ;
            }
        },error: function (){

        }
    })
}


$(document).ready(function () {
    $('#tableUser').DataTable();
});


$(document).on("click",' #likeBtn', function (){
    let ruta = Routing.generate('setLikes');
    let id = $(this).data('id');
    $.ajax({
        type: 'GET',
        url: ruta,
        data: {id: id},
        async: true,
        dataType: 'json',
        success: function (data){
            console.log(data['likes']);
            $('.btn-'+id).prop('disabled', true);
            $('#countLikes-'+id).html(data['likes'] +' Likes');
        }, error: function (){

        }
    })
})

$(document).on("click", "#btnUser", function (){
    let ruta = Routing.generate('followUser');
    let id = $(this).data('id');
    $.ajax({
        type: 'GET',
        data: {id: id},
        url: ruta,
        async: true,
        dataType: 'json',
        success: function (data){
            console.log(data['userId']);
            $('.userFollow-'+data['userId']).remove();
            $('.follow').append(
                '<div class="d-flex justify-content-between">'+
                '<img alt="" src="/images/'+data['userImage']+'" style="border-radius:20px;width: 45px;height: 45px">\n' +
                '<p>'+data['userEmail']+' following</p>\n' +
                '</div>                '
            )

        },error: function (){

        }
    })
})


