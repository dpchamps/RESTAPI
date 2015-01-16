/**
 * Created by Dave on 1/15/2015.
 */


$('#testAPIFunc').on('click', function(){
    console.log("clicked");
    $.ajax({
        url: 'api/test',
        type: "GET"
    })
    .done(function(data){
        $('#responseNode').text(data);
    });
});