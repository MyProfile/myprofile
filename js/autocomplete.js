
function split(val) {
    return val.split(/@\s*/);
}

function extractLast(term) {
    return split(term).pop();
}

function do_autocomplete (id) {
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
                if (term.indexOf("@") >= 0) {
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
                terms.push( ui.item.value );
                // add placeholder to get the comma-and-space at the end
                terms.push( " " );
                this.value = terms.join( "" );
                return false;
            }
        });
}
