

// Logout WebID 
function logout(elem) {
   if (document.all == null) {
      if (window.crypto) {
          try{
              window.crypto.logout();
              return false; //firefox ok -- no need to follow the link
          } catch (err) {//Safari, Opera, Chrome -- try with tis session breaking
          }
      } else { //also try with session breaking
      }
   } else { // MSIE 6+
      document.execCommand('ClearAuthenticationCache');
      return false;
   };
   return true
}

// resize a textarea element
function textAreaResize(o) {
  o.style.height = "1px";
  o.style.height = (25+o.scrollHeight)+"px";
}

String.prototype.capitalize = function(){
   return this.replace( /(^|\s)([a-z])/g , function(m,p1,p2){ return p1+p2.toUpperCase(); } );
  };
  
// validate requirements (profile uri and full name of user)
function validateReq (serverURI, uri, fullname, submit) {
        
    var uri = document.getElementById(uri);
    var uri_val = uri.value.toLowerCase();
    
    var fullname = document.getElementById(fullname);
    var fullname_val = fullname.value;

    var submit = document.getElementById(submit);

    var okURI = false;
    var okUser = false;
	var regex = /^[a-z0-9_\.-]+$/;
	
    /* --- Test the uri (username) --- */
	if (!regex.test(uri_val)) {
        uri.setAttribute('style', 'border-color: red !important;');
    } else if (uri_val.length > 2) {
        // check whether username URI exists or not (through http return status)
        if (UrlExists(serverURI + escape(uri_val)) == 404) {
            uri.setAttribute('style', 'border-color: blue !important;');
            okURI = true;
        }
    } else {
        uri.setAttribute('style', 'border-color: red !important;');
    }

    /* --- Test the full name --- */
    if (fullname_val.length < 2) {
        fullname.setAttribute('style', 'border-color: red !important;');
    } else {
        fullname.setAttribute('style', 'border-color: blue !important;');
        okUser = true;
    }

    /* --- Finally decide whether to allow submit or not --- */
    if ((okURI) && (okUser)) {
        submit.enabled = true;
        submit.disabled = false;
        submit.className = "btn btn-primary";
    } else {
        submit.enabled = false;
        submit.disabled = true;
        submit.className = "btn";
    }
}

function validateCert (field1, field2, submit, len) {
    var field1 = document.getElementById(field1);
    var field2 = document.getElementById(field2);
    var field1_val = field1.value;
    var field2_val = field2.value;

    var submit = document.getElementById(submit);
    var ok1 = false;
    var ok2 = false;

    /* --- Test the first field --- */
    if (field1_val.length < len) {
        field1.setAttribute('style', 'border-color: red !important;');
    } else {
        field1.setAttribute('style', 'border-color: blue !important;');
        ok1 = true;
    } 
    
    /* --- Test the first field --- */
    if (field2_val.length < len) {
        field2.setAttribute('style', 'border-color: red !important;');
    } else {
        field2.setAttribute('style', 'border-color: blue !important;');
        ok2 = true;
    } 

    /* --- Finally decide whether to allow submit or not --- */
    if ((ok1) && (ok2)) {
        submit.enabled = true;
        submit.disabled = false;
        submit.className = "btn btn-primary";
    } else {
        submit.enabled = false;
        submit.disabled = true;
        submit.className = "btn btn-primary";
    }   
}

// validate requirements for the full name field
function validateLength (field, submit, len) {
    var name = document.getElementById(field);
    var name_val = name.value;
    var submit = document.getElementById(submit);
    
    /* --- Test the full name --- */
    if (name_val.length < len) {
        name.setAttribute('style', 'border-color: red !important;');
        submit.enabled = false;
        submit.disabled = true;
    } else {
        name.setAttribute('style', 'border-color: blue !important;');
        submit.enabled = true;
        submit.disabled = false;
    } 
}

function setKeygen (checkbox, pubkey) {
    var pubkey = document.getElementById('pubkey');
    if (checkbox.checked)
        pubkey.disabled = false;
    else
        pubkey.disabled = true;
}

function UrlExists(url)
{
  try
  {
      var http = new XMLHttpRequest();
  }
  catch(e)
  {
    // assume IE6 or older
    try
    {
      http = new ActiveXObject("Microsoft.XMLHttp");
    }
    catch(e) { }
  }
  if (http) {
      http.open('HEAD', url, false);
      http.send();
      return http.status;
  } else {
    return 1;
  }
}

function removeElement (id) {
    $('#' + id).remove();
}

// Vote on a message
function setVote (base, vote, message_id) {
    var other_base = '';
    var other_vote = '';
    if (vote == 'no') {
        other_base = 'yes_' + message_id;
        other_vote = 'yes';
    } else if (vote == 'yes') {
        other_base = 'no_' + message_id;
        other_vote = 'no';
    }

    $.post('include.php?vote=' + vote + '&message_id=' + message_id, function(data) {
        // disable the link for the current vote button
        $('#' + base).parent().removeAttr('onclick');
        $('#' + base).parent().removeAttr('style');
        $('#' + base).parent().attr('style', 'text-decoration: none; cursor: pointer;');
        $('#' + base).empty().append(data);
        
        // enable the link for the other vote button
        $('#' + other_base).parent().attr('onClick', "setVote('" + other_vote + "_" + message_id + "', '" + other_vote + "', '" + message_id + "')");
        $('#' + other_base).parent().attr('style', 'text-decoration: none; cursor: pointer;');
        
        // substract one vote from the other vote type (no -> yes / yes -> no)      
        var new_val = parseInt($('#' + other_base).text());
        if (new_val > 0) {
            new_val = new_val - 1;
            $('#' + other_base).text(new_val);
        }
    });
}

// Add fields on the profile information tab (profile form)
function addInfo (type, table) {
    // create table row
    var row = document.createElement("tr");
    // create table cells
    var cell_l = document.createElement("td"); 
    var cell_r = document.createElement("td"); 
    
    // append label to left cell
    var text = type.split(':')[1];
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
    cell_r.appendChild(element);
    
    // append cells to row
    row.appendChild(cell_l);
    row.appendChild(cell_r);
    
    var tab = document.getElementById(table);
    // append row to table
    tab.appendChild(row);
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
    element.setAttribute("size", '10');
    element.setAttribute("value", '');
    element.setAttribute("name", 'dc:title[]');

    //Create an input type.
    var url_element = document.createElement("input");
    //Assign different attributes to the element.
    url_element.setAttribute("type", 'text');
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
    var label = document.createTextNode(text.capitalize() + ": ");
    cell_l.appendChild(label);

    //Create an input type.
    var element = document.createElement("input");
 
    //Assign different attributes to the element.
    element.setAttribute("type", 'text');
    element.setAttribute("size", '30');
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

        var hex = document.createTextNode(" Hex ID: ");

        //Create an input type.
        var fingerprint = document.createElement("input");
        //Assign different attributes to the element.
        fingerprint.setAttribute("type", 'text');
        fingerprint.setAttribute("size", '20');
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
        cell_r.appendChild(hex);
        cell_r.appendChild(hex_element);
        cell_r.appendChild(document.createTextNode(' (wot:hasKey)'));
    }
    
    if (type == 'rsa#RSAPublicKey') {
        // append label to left cell
        var label = document.createTextNode("Modulus: ");
        var exponent = document.createTextNode(" Exponent: ");
        
        //Create textarea for certificate modulus.
        var modulus = document.createElement("textarea");
        //Assign different attributes to the element.
        modulus.setAttribute("cols", '48');
        modulus.setAttribute("rows", '7');
        modulus.setAttribute("value", '');
        modulus.setAttribute("style", 'margin:1px; padding:1px; border-style:solid; border-color: #666; border-width:1px;');
        modulus.setAttribute("name", 'modulus[]');

        //Create input field for hexa value.
        var exp_element = document.createElement("input");
        //Assign different attributes to the element.
        exp_element.setAttribute("type", 'text');
        exp_element.setAttribute("size", '10');
        exp_element.setAttribute("value", '65537');
        exp_element.setAttribute("name", 'exponent[]');

        // create new table row
        var row = document.createElement("tr");
        row.setAttribute("valign", 'top');
        // create table cells
        var cell_l = document.createElement("td");
        cell_l.setAttribute("valign", 'top');
        var cell_r = document.createElement("td"); 
        cell_r.setAttribute("valign", 'top');

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
        inner_td_right.appendChild(exponent);
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
