String.prototype.capitalize = function(){
   return this.replace( /(^|\s)([a-z])/g , function(m,p1,p2){ return p1+p2.toUpperCase(); } );
  };

function addInfo (type, table) {
    // create table row
    var row = document.createElement("tr");
    // create table cells
    var cell_l = document.createElement("td"); 
    var cell_m = document.createElement("td"); 
    var cell_r = document.createElement("td");

    // append label to left cell
    var text = type.split(':')[1];
    if (text == "mbox")
        txt = 'Email';
    var label = document.createTextNode(text.capitalize() + ": ");
    cell_l.appendChild(label);

    //Create an input type.
    var element = document.createElement("input");
 
    //Assign different attributes to the element.
    element.setAttribute("type", 'text');
    element.setAttribute("size", '50');
    element.setAttribute("value", '');
    element.setAttribute("name", type + '[]');
    // append input field to right table cell
    cell_m.appendChild(element);
    cell_r.appendChild(document.createTextNode(' (' + type + ')'));
    
    // append cells to row
    row.appendChild(cell_l);
    row.appendChild(cell_m);
    row.appendChild(cell_r);
    
    var foo = document.getElementById(table);
    // append row to table
    foo.appendChild(row);
}

function addInterests (type, table) {
    // create table row
    var row = document.createElement("tr");
    // create table cells
    var cell_l = document.createElement("td"); 
    var cell_r = document.createElement("td"); 

    // append label to left cell
    var label = document.createTextNode("Interest name: ");
    cell_l.appendChild(label);

    var url = document.createTextNode(" URI: ");


    //Create an input type.
    var element = document.createElement("input");
    //Assign different attributes to the element.
    element.setAttribute("type", 'text');
    element.setAttribute("size", '20');
    element.setAttribute("value", '');
    element.setAttribute("name", 'dc:title[]');

    //Create an input type.
    var url_element = document.createElement("input");
    //Assign different attributes to the element.
    url_element.setAttribute("type", 'text');
    url_element.setAttribute("size", '40');
    url_element.setAttribute("value", '');
    url_element.setAttribute("name", 'foaf:interest[]');


    // append input field to right table cell
    cell_r.appendChild(element);
    cell_r.appendChild(url);
    cell_r.appendChild(url_element);
    cell_r.appendChild(document.createTextNode(' (' + type + ')'));
    
    // append cells to row
    row.appendChild(cell_l);
    row.appendChild(cell_r);
    var foo = document.getElementById(table);
    // append row to table
    foo.appendChild(row);
}

function addFriends (type, table) {
    // create table row
    var row = document.createElement("tr");
    // create table cells
    var cell_l = document.createElement("td"); 
    var cell_r = document.createElement("td"); 

    // append label to left cell
    var text = type.split(':')[1];
    var label = document.createTextNode("Person: ");
    cell_l.appendChild(label);

    //Create an input type.
    var element = document.createElement("input");
 
    //Assign different attributes to the element.
    element.setAttribute("type", 'text');  
    element.setAttribute("size", '70');
    element.setAttribute("value", '');
    element.setAttribute("name", type + '[]');
    // append input field to right table cell
    cell_r.appendChild(element);
    cell_r.appendChild(document.createTextNode(' (' + type + ')'));
    
    // append cells to row
    row.appendChild(cell_l);
    row.appendChild(cell_r);
    
    var foo = document.getElementById(table);
    // append row to table
    foo.appendChild(row);
}

function addAccount (type, table) {
    // create table row
    var row = document.createElement("tr");
    // create table cells
    var cell_l = document.createElement("td"); 
    var cell_r = document.createElement("td"); 

    // append label to left cell
    var label = document.createTextNode("Username: ");
    cell_l.appendChild(label);

    var homepage = document.createTextNode(" Service URL: ");
    var profilepage = document.createTextNode(" Profile URL: "); 

    //Create an input type.
    var element = document.createElement("input");
    //Assign different attributes to the element.
    element.setAttribute("type", 'text');
    element.setAttribute("size", '10');
    element.setAttribute("value", '');
    element.setAttribute("name", 'accountName[]');

    //Create an input type.
    var url_element = document.createElement("input");
    //Assign different attributes to the element.
    url_element.setAttribute("type", 'text');
    url_element.setAttribute("size", '15');
    url_element.setAttribute("value", '');
    url_element.setAttribute("name", 'serviceUrl[]');

    //Create an input type.
    var profile_element = document.createElement("input");
    //Assign different attributes to the element.
    profile_element.setAttribute("type", 'text');
    profile_element.setAttribute("size", '15');
    profile_element.setAttribute("value", '');
    profile_element.setAttribute("name", 'profileUrl[]');
    
    // append input field to right table cell
    cell_r.appendChild(element);
    cell_r.appendChild(homepage);
    cell_r.appendChild(url_element);
    cell_r.appendChild(profilepage);
    cell_r.appendChild(profile_element);
    
    // append cells to row
    row.appendChild(cell_l);
    row.appendChild(cell_r);
    var foo = document.getElementById(table);
    // append row to table
    foo.appendChild(row);
}

function addSecurity (type, table) {
    // create table row
    var row = document.createElement("tr");
    row.setAttribute("valign", 'top');
    // create table cells
    var cell_l = document.createElement("td");
    cell_l.setAttribute("valign", 'top');
    var cell_r = document.createElement("td"); 
    cell_r.setAttribute("valign", 'top');

    if (type == 'wot:hasKey') {
        // append label to left cell
        var label = document.createTextNode("Key fingerprint: ");

        var url = document.createTextNode(" Hex ID: ");

        //Create an input type.
        var fingerprint = document.createElement("input");
        //Assign different attributes to the element.
        fingerprint.setAttribute("type", 'text');
        fingerprint.setAttribute("size", '40');
        fingerprint.setAttribute("value", '');
        fingerprint.setAttribute("name", 'fingerprint[]');

        //Create an input type.
        var hex_element = document.createElement("input");
        //Assign different attributes to the element.
        hex_element.setAttribute("type", 'text');
        hex_element.setAttribute("size", '10');
        hex_element.setAttribute("value", '');
        hex_element.setAttribute("name", 'hexkey[]');

        // append input field to right table cell
        cell_l.appendChild(label);
        cell_r.appendChild(fingerprint);
        cell_r.appendChild(url);
        cell_r.appendChild(hex_element);
        cell_r.appendChild(document.createTextNode(' (wot:hasKey)'));
    }
    
    if (type == 'rsa#RSAPublicKey') {
        // append label to left cell
        var label = document.createTextNode("Modulus: ");
        var url = document.createTextNode(" Exponent: ");

        //Create an input type.
        var modulus = document.createElement("textarea");
        //Assign different attributes to the element.
        modulus.setAttribute("style", 'height: 130px !important; ');
        modulus.setAttribute("value", '');
        modulus.setAttribute("name", 'modulus[]');

        //Create an input type.
        var exp_element = document.createElement("input");
        //Assign different attributes to the element.
        exp_element.setAttribute("type", 'text');
        exp_element.setAttribute("placeholder", '65537');
        exp_element.setAttribute("size", '10');
        exp_element.setAttribute("value", '');
        exp_element.setAttribute("name", 'exponent[]');

        // append input field to right table cell
        cell_l.appendChild(label);
        
        // create an inner table
        var inner_table = document.createElement("table");
        var inner_tr = document.createElement("tr");
        inner_tr.setAttribute("valign", 'top');
        var inner_td_left = document.createElement("td");
        var inner_td_right = document.createElement("td");
        inner_td_right.setAttribute("valign", 'top');
        
        // append tr to inner table
        inner_table.appendChild(inner_tr);
        // append left cell
        inner_tr.appendChild(inner_td_left);
        // append right cell
        inner_tr.appendChild(inner_td_right);
        // append data to left cell
        inner_td_left.appendChild(modulus);
        // append data to right cell
        inner_td_right.appendChild(url);
        inner_td_right.appendChild(exp_element);
        cell_r.appendChild(inner_table);
    }    
    
    // append cells to row
    row.appendChild(cell_l);
    row.appendChild(cell_r);
    var foo = document.getElementById(table);
    // append row to table
    foo.appendChild(row);
}
