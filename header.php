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
    <link type="text/css" href="css/bootstrap.css" rel="stylesheet">
    <link type="text/css" href="css/bootstrap-responsive.css" rel="stylesheet">
    <link type="text/css" href="css/demo.css" rel="stylesheet">
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

<link rel="SHORTCUT ICON" href="favicon.png" />
</head>

<body>

<!-- Local scripts -->
<script type="text/javascript" src="js/form.js"></script>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/bootstrap-dropdown.js"></script>
<script type="text/javascript" src="js/bootstrap-tab.js"></script>
<script type="text/javascript" src="js/moment.min.js"></script>
<script type="text/javascript" src="js/prettify.js"></script>
<script type="text/javascript" src="js/autocomplete.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>

<div class="navbar navbar-classic" align="center">
  <div class="navbar-inner" style="height: 40px; width: 900px;">
    <div class="container">
    <h3><a class="brand" href="wall.php"><img alt="MyProfile Wall" title="MyProfile Wall" height="22" src="img/myprofile-logo.png" style="height: 22px; float:left; display:inline; margin-right:10px;" />MyProfile</a></h3>
    <ul class="nav">
        <?php
            if (isset($_SESSION['webid']))
                echo "<li><a href=\"wall.php?activity=1\">News feed</a></li>\n";
            if (isset($_SESSION['user_hash'])) {
                echo "<li><a href=\"wall.php?user=" . $_SESSION['user_hash'] . "\">My wall</a></li>\n";
            } else {
                echo "<li><a href=\"profile.php\">Get a WebID!</a></li>\n";
            }
        ?>
        <li><a href="friends.php">Friends</a></li>
        <li><a href="lookup.php">Lookup</a></li>
        <li class="dropdown"><a data-toggle="dropdown" class="dropdown-toggle" href="#menu1">More<b class="caret"></b></a>
            <ul class="dropdown-menu">
            <?php
                if (isset($_SESSION['webid']))
                    echo "<li><a href=\"profile.php\">Create a new WebID</a></li>\n";
            ?>
            <li><a href="certgen.php">Issue certificate</a></li>
            </ul>
        </li>
        <li>
            <a href="http://flattr.com/thing/715474/MyProfile" target="_blank"><img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a>
        </li>
    </ul>
    <ul class="nav pull-right">
    <?php 
    if (isset($_SESSION['webid'])) {
        // Messages (wall & private)
        $bg = ($messages > 0) ? '#dc4212;' : 'grey';
        // Wall message
        $wbg = ($wall_msg > 0) ? '#dc4212;' : 'grey';
        // Wall message
        $pbg = ($private_msg > 0) ? '#dc4212;' : 'grey';
            
		echo "<li class=\"dropdown\"><a data-toggle=\"dropdown\" class=\"dropdown-toggle\" href=\"#menu2\"><table><tr><td class=\"rounded\" style=\"padding: 2px 9px 2px 9px; color: white; background-color: " . $bg . "\">" . $messages . "</td><td> <b class=\"caret\"></b></td></tr></table></a>";
		echo "<ul class=\"dropdown-menu\">\n";
		echo "<li><a href=\"messages.php\">";
		echo "<div class=\"rounded\" style=\"float: left; margin-right: 5px; padding: 0px 7px 0px 7px; color: white; background-color: " . $pbg . "\">" . $private_msg . "</div><div align=\"left\">Message";
        // add plural if more than one or less than one message (0 messages)
        echo ($private_msg != 1) ? 's': '';
        echo "</div>\n";
		echo "</a></li>\n";
		echo "<li><a href=\"wall.php?user=" . $_SESSION['user_hash'] . "\">";
		echo "<div class=\"rounded\" style=\"float: left; margin-right: 5px; padding: 0px 7px 0px 7px; color: white; background-color: " . $wbg . "\">" . $wall_msg . "</div><div align=\"left\">Wall messages</div>\n";
//		echo " <table><tr><td style=\"float: left; padding: 2px 9px 2px 9px; color: white; background-color: " . $wbg . "\">" . $wall_msg . "</td><td style=\"float: left; padding: 5px 0px 0px 5px;\">Wall messages</td></tr></table>\n";
		echo "</a></li>\n";
		echo "<li class=\"divider\"></li>\n";
		echo "<li><a href=\"messages.php?new=true\">Send message</a></li>\n";
		echo "</ul>\n";
		echo "</li>\n";

        // User info
        $username = (strlen($_SESSION['usr']) > 18) ? substr($_SESSION['usr'], 0, 18) . '...' : $_SESSION['usr'];
        echo "<li class=\"dropdown\"><a data-toggle=\"dropdown\" class=\"dropdown-toggle\" href=\"#menu3\"><img class=\"rounded\" alt=\"" . $_SESSION['usr'] . "\" src=\"" . $_SESSION['img'] . "\" style=\"height: 22px; float:left; display:inline; margin: 0px 10px 0px 10px;\" /> " . $username . " <b class=\"caret\"></b></a>";
        echo "<ul class=\"dropdown-menu\">\n";
        echo "<li><a href=\"view.php?webid=" . urlencode($_SESSION['webid']) . "\">View my profile</a></li>\n";
        if (webid_is_local($_SESSION['webid'])) {
            echo "<li><a href=\"profile.php?action=edit\">Edit profile</a></li>\n";
            echo "<li><a href=\"account.php\">Manage account</a></li>";
            echo "<li><a href=\"export.php\">Export profile</a></li>\n";
        }
        echo "<li><a href=\"".BASE_URI."/subscriptions\">Subscriptions</a></li>\n";
        echo "</ul>\n";
        echo "</li>\n";
        echo "<li class=\"divider\"></li>\n";
        echo "<li><a href=\"".BASE_URI."/?logout=1\"><img title=\"Sign out\" alt=\"Sign out\" src=\"".BASE_URI."/img/signout.png\"></a></li>";
    } else {
        echo "<li><a href=\"" . IDP . "" . $page_uri . "\" style=\"\padding-top: 8px;\">\n";
        echo "  <img alt=\"WebID Login\" title=\"WebID Login\" src=\"img/webid.png\" style=\"height: 22px; \" />";
        echo "</a></li>\n";
    }
    ?>
    </ul>
   </div>
  </div>
  </div>
  <div class="container">
    <div class="content">
      <div class="row">
       <div class="span10" id="content">

