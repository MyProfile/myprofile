
function loadWall (wallID, moreID, count, offset, owner, activity) {
    $("#"+moreID).remove();
    $("#"+wallID).append('<img id="'+moreID+'" class="loading" src="img/loading.gif" />');
    $.ajax({
        type: "get",
        url: "ajax_wall.php",
        data: { owner: owner, count: count, offset: offset, activity: activity},
        cache: false,
        success: function(html){
            $("#"+moreID).remove();
            $("#"+wallID).append(html);
        }
    });
}


// Update a wall post
function updateWall (base, action, postId) {
    // fetch text content
    var text = $('#' + base).text();
    
    // build the form
    var form = '<div id="form_' + postId + '">';
    form = form + '<form action="' + action + '&#post_' + postId + '" method="post">';
    form = form + '<input type="hidden" name="edit" value="' + postId + '">';
    form = form + '<p><textarea name="comment" class="textarea-wall" onfocus="textAreaResize(this)">' + text + '</textarea></p>';
    form = form + '<p>';
    form = form + '<input class="btn btn-primary" type="submit" name="update" value="Update"> ';
    form = form + '<a onClick="cancelUpdateWall(\'' + base + '\', \'' + postId + '\')">';
    form = form + '<input class="btn" type="button" name="cancel" value="Cancel"></a>';
    form = form + '</p></form></div>';

    // hide the previous text (we may reuse it in case the user cancels the form)
    $('#' + base).hide();
    // display form instead of text
    $('#' + base).parent().append(form);
}

// Remove the form used for updating a wall post
function cancelUpdateWall (base, postId) {
    // remove the form
    $('#form_' + postId).remove();
    // display previous text
    $('#' + base).show();
    //$('#' + base).html('<pre id="message_val_' + postId + '">' + text + '</pre>');
}
