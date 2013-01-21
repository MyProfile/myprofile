
function split(val) {
    return val.split(/@\s*/);
}

function extractLast(term) {
    return split(term).pop();
}

// autocomplete handles when posting to wall
function do_autocomplete (id, handle) {
    $("#"+id)
        // don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
                    $( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            minLength: 0,
            source: function( request, response ) {
                var term = request.term;
                var results = [];
                if (handle != null) {
                    if (term.indexOf(handle) >= 0) {
                        $.getJSON( "shorthandle.php", {
                            term: extractLast( request.term )
                        }, response );
                    }
                } else {
                    $.getJSON( "shorthandle.php", {
                        term: extractLast( request.term )
                    }, response );
                }
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                var terms = split( this.value );
                // remove the current input
                terms.pop();
                // add the selected item
                if (handle != null) {
                    terms.push( '<'+ui.item.value+'>' );
                } else {
                    terms.push( ui.item.value );
                }
                // add placeholder to get the space at the end
                terms.push( " " );
                this.value = terms.join( "" );
                return false;
            }
        });
}

// autocomplete name for sending messages
function do_autocomplete_msg (name, to) {
    $("#"+name)
        // don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
                    $( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            minLength: 0,
            source: function( request, response ) {
                var term = request.term;
                var results = [];
                $.getJSON( "shorthandle.php", {
                    term: extractLast( request.term )
                }, response );
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                var terms = split( this.value );
                // set the WebID in the "to" input
                $("#"+to).val(ui.item.value);
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push( ui.item.label );
                // add placeholder to get the space at the end
                terms.push( " " );
                this.value = terms.join( "" );
                return false;
            }
        });
}
