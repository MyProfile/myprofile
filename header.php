<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
        xml:lang="en" 
        xmlns:sioc="http://rdfs.org/sioc/ns#"
        xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
        xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
        xmlns:dc="http://purl.org/dc/elements/1.1/"
        xmlns:dcterms="http://purl.org/dc/terms/">

<head>
    <meta charset="utf-8">
    <title>MyProfile</title>
    <meta name="author" content="Andrei Sambra">

    <!-- Le HTML5 shim, for IE6-8 support of HTML elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
  
    <!-- Styles --> 
    <link type="text/css" href="css/jquery-ui-1.8.16.custom.css" rel="stylesheet" />
    <link href="bootstrap/bootstrap.css" rel="stylesheet">
    <link href="css/demo.css" rel="stylesheet">
    <link href="third-party/wijmo/jquery.wijmo-open.1.5.0.css" rel="stylesheet" type="text/css" />
    <style type="text/css">
      /* Override some defaults */
      html, body {
        background-color: #eee;
      }
      body {
        padding-top: 0px; /* 40px to make the container go all the way to the bottom of the topbar */
      }
      .container > footer p {
        text-align: center; /* center align it with the container */
      }
      .container {
        width: 820px; /* downsize our container to make the content feel a bit tighter and more cohesive. NOTE: this removes two full columns from the grid, meaning you only go to 14 columns and not 16. */
      }

      /* The white background content wrapper */
      .container > .content {
        background-color: #fff;
        padding: 20px;
        margin: 0 -20px; /* negative indent the amount of the padding to maintain the grid system */
        -webkit-border-radius: 0 0 6px 6px;
           -moz-border-radius: 0 0 6px 6px;
                border-radius: 0 0 6px 6px;
        -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.15);
           -moz-box-shadow: 0 1px 2px rgba(0,0,0,.15);
                box-shadow: 0 1px 2px rgba(0,0,0,.15);
      }

      /* Page header tweaks */
      .page-header {
        background-color: #f5f5f5;
        padding: 20px 20px 10px;
        margin: -20px -20px 20px;
      }

      /* Styles you shouldn't keep as they are for displaying this base example only */
      .content .span10,
      .content .span4 {
        min-height: 500px;
      }
      /* Give a quick and non-cross-browser friendly divider */
      .content .span4 {
        margin-left: 0;
        padding-left: 19px;
        border-left: 1px solid #eee;
      }

     .topbar .btn {
        border: 0;
      }
    </style>

<link rel="SHORTCUT ICON" href="favicon.ico" />
</head>

<body>
<!--scripts-->

<!-- Local scripts -->
<script type="text/javascript" src="js/form.js"></script>
<script type="text/javascript" src="js/form-add.js"></script>
<script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.16.custom.min.js"></script>
 
<!--daterangepicker-->
<script type="text/javascript" src="third-party/jQuery-UI-Date-Range-Picker/js/date.js"></script>
<script type="text/javascript" src="third-party/jQuery-UI-Date-Range-Picker/js/daterangepicker.jQuery.js"></script>

<!--wijmo-->
<script type="text/javascript" src="third-party/wijmo/jquery.mousewheel.min.js"></script>
<script type="text/javascript" src="third-party/wijmo/jquery.bgiframe-2.1.3-pre.js"></script>
<script type="text/javascript" src="third-party/wijmo/jquery.wijmo-open.1.5.0.min.js"></script>

<!-- FileInput -->
<script type="text/javascript" src="third-party/jQuery-UI-FileInput/js/enhance.min.js"></script>
<script type="text/javascript" src="third-party/jQuery-UI-FileInput/js/fileinput.jquery.js"></script>
         
<!--init for this page-->
<script type="text/javascript" src="js/demo.js"></script>
  
<!--begin wijmo menu-->
  <style>
    .wijmo-container
    {
        display: block;
        clear: both;
        width: 900px;
        padding: 0px;
    }
  </style>
  <div align="center">
    <input type="hidden" id="rangeA" />     
    <input type="hidden" id="rangeBa" />
    <input type="hidden" id="rangeBb" />      

    <div class="wijmo-container">
    <ul id="menu1">
        <li>
            <h3><a href="wall.php"><img alt="MyProfile" height="22" src="img/myprofile-logo.png" style="float:left; display:inline; margin-right:10px;" /> MyProfile</a></h3>
        </li>
        <?php
            if (isset($_SESSION['user_hash']))
                echo "<li><a href=\"wall.php?user=" . $_SESSION['user_hash'] . "\">My wall</a></li>\n";

        ?>
        <li><a href="friends.php">Friends</a></li>
        <li><a href="lookup.php">Lookup</a></li>
        <li><a href="#">Additional Features</a>
            <ul>
            <li><a href="profile.php">Create WebID</a></li>
            <li><a href="certgen.php">Issue certificate</a></li>
            <li><a href="export.php">Convert/Export</a></li>
            <li>&nbsp;</li>
            <li><a href="http://myprofile-project.org/" target="_blank">About</a></li>
            </ul>
        </li>
        <?php 
            if (isset($_SESSION['webid'])) {
                // User info
                $username = (strlen($_SESSION['usr']) > 30) ? substr($_SESSION['usr'], 0, 30) . '...' : $_SESSION['usr'];
                echo "<li style=\"float: right; position: relative;\"><a href=\"#\"><img alt=\"" . $_SESSION['usr'] . "\" height=\"24\" src=\"" . $_SESSION['img'] . "\" style=\"float:left; display:inline; margin: 0px 10px 0px 10px;\" /> " . $username . "</a>";
                echo "<ul>\n";
                echo "<li><a href=\"lookup.php?uri=" . urlencode($_SESSION['webid']) . "\">View my profile</a></li>\n";
                if (webid_is_local($_SESSION['webid'])) {
                    echo "<li><a href=\"profile.php?action=edit\">Edit profile</a></li>\n";
                    echo "<li><a href=\"account.php\">Manage account</a></li>";
                    echo "<li><a href=\"export.php\">Export profile</a></li>\n";
                }
                echo "<li><a href=\"subscription.php\">Subscriptions</a></li>\n";
                echo "<li><a href=\"index.php?logoff\">Log out?</a></li>";
                echo "</ul>\n";
                echo "</li>\n";
                
                // Notifications (wall & private)
                $bg = ($messages > 0) ? '#dc4212;' : 'grey';
                // Wall message
                $wbg = ($wall_msg > 0) ? '#dc4212;' : 'grey';
                // Wall message
                $pbg = ($private_msg > 0) ? '#dc4212;' : 'grey';
                    
   				echo "<li style=\"float: right; position: relative;\"><a href=\"#\" style=\"color: white;\"><table><tr><td style=\"width: 10px; margin-top: 10px; padding: 5px 10px 5px 10px; background-color: " . $bg . "\">" . $messages . "</td></tr></table></a>";
   				echo "<ul>\n";
   				echo "<li><a href=\"my_notifications.php\">";
   				    echo "<table><tr><td style=\"float: left; display: inline; padding: 5px 10px 5px 10px; background-color: " . $pbg . "\">" . $private_msg . "</td><td style=\"float: left; padding: 5px 0px 0px 5px;\">Notifications</td></tr></table></a></li>\n";
   				echo "<li><a href=\"wall.php?user=" . $_SESSION['user_hash'] . "\">";
   				    echo "<table><tr><td style=\"float: left; display: inline; padding: 5px 10px 5px 10px; background-color: " . $wbg . "\">" . $wall_msg . "</td><td style=\"float: left; padding: 5px 0px 0px 5px;\">Wall messages</td></tr></table></a></li>\n";
   				echo "<li><a href=\"notification.php\">Send Notification</a></li>\n";
   				echo "</ul></li>\n";
            } else {
                echo "<li><a href=\"profile.php\">Get a WebID!</a></li>\n";
                echo "<li style=\"float: right; position: relative;\"><a href=\"https://auth.my-profile.eu/auth/index.php?authreqissuer=" . $_SESSION['page_uri'] . "\">WebID Login</a></li>\n";
            }
        ?>

    </ul>
    </div>
  </div>
  <div class="container">
    <div class="content">
      <div class="row">
       <div class="span10" id="content">

