/**
 * Created by Dave on 1/15/2015.
 */


$('#testAPIGET').on('click', function(){
    console.log("clicked");
    $.ajax({
        url: 'api/test',
        data : {
            token: "23l4jhknbrf2345lkj4"
        },
        type: "GET"
    })
    .done(function(data){
            console.log("done", data);
        $('#responseNode').html(
            JSON.stringify(data, null, "   ")
        );
    });
});
