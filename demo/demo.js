/**
 * Created by Dave on 1/15/2015.
 */


$('#login').on('submit', function(e){
    e.preventDefault();

    $.ajax({
        url: 'api/login',
        data : $(this).serialize(),
        type: "POST"
    })
    .done(function(data){


        var responseString = '',
            responseNode = $("#responseNode");

        if( typeof data.username !== 'undefined' &&
            typeof data.token !== 'undefined'){
            sessionStorage['username'] = data.username;
            sessionStorage['token'] = data.token;
            responseString = "User " + sessionStorage['username'] + " is logged in";

        }else if (typeof data.error !== 'undefined'){
            sessionStorage['error'] = data.error;
            responseString = "Error: " + sessionStorage['error'];
        }else {
            responseString = "An undefined error occurred";
        }

        responseNode.html(responseString);
    });
});

$("#logout").on('click', function(e){
    console.log('clicked');
    $.ajax({
        url: 'api/logout',
        data: {
            username : sessionStorage['username'],
            token : sessionStorage['token']
        },
        type: "POST"
    })
        .done(function(data){
            var responseString = '',
                responseNode = $("#responseNode");
            if(typeof data.error === 'undefined'){
                responseString = data.username + "has logged out";
                sessionStorage['token'] = data.token;
            }else{
                responseString = data.error
            }

            responseNode.html( responseString );
        })
});
