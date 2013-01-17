$(document).ready(function() {
    do_autocomplete('comment', '@');
    // add variable nav bar buttons
    var messages = '<div><a href="messages" class="messages"><small>Messages</small></a></div>';
    var profile = '<div><a href="view" class="profile"><small>My Profile</small></a></div>';
    var settings = '<div><a href="preferences" class="settings"><small>Preferences</small></a></div>';
    var more = '<div><a href="#" class="more"><small>More...</small></a></div>';

    // hide by default
    $('#more').hide();
    $('#more-menu').hide();

    // try to resize after each page reload
    resizeNav(); 
    
    // display "More" if nav bar buttons don't fit anymore
    window.onresize = function () {
        resizeNav();
    }
    
    function resizeNav () { 
       // need to clean up the menu
        $('#more-menu').hide();
        $('#more-menu').css("top", "");
        $('#more-menu').css("left", "");
        
        if($(window).height() < 520) {
            $('#messages').hide();
            $('#profile').hide();
            $('#settings').hide();
            $('#more').show();
            
            // add to more menu
            $('#more-menu').empty();
            $('#more-menu').append(messages);
            $('#more-menu').append(profile);
            $('#more-menu').append(settings);
        } else if (($(window).height() > 500) && ($(window).height() < 570)) {
            $('#messages').show();
            $('#profile').hide();
            $('#settings').hide();
            $('#more').show();
                    
            // add to more menu
            $('#more-menu').empty();
            $('#more-menu').append(profile);
            $('#more-menu').append(settings);
        } else if (($(window).height() > 600) && ($(window).height() < 650)) {
            $('#messages').show();
            $('#profile').show();
            $('#settings').hide();
            $('#more').show();
                            
            // add to more menu
            $('#more-menu').empty();
            $('#more-menu').append(settings);
        } else if ($(window).height() > 650) {
            $('#messages').show();
            $('#profile').show();
            $('#settings').show();
            $('#more').hide();
        }
    }

    $('#more').click( function(){
        $("#more-menu").position({
            of: $("#more"),
            my: "left top",
            at: "right top",
            offset: "-20",
            collision: "fit"
        });
        $('#more-menu').show();
    });

    $('#more-menu').click( function(){
        $('#more-menu').hide();
        $('#more-menu').css("top", "");
        $('#more-menu').css("left", "");
    });
});
