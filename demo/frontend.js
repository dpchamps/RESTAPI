/**
 * Created by Dave on 1/16/2015.
 */

cookieModule = function(){
    return{
        createSessionCookie : function(name, value){
            sessionStorage[name] = value;
        }
    }
};