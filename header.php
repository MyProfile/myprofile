<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <!-- Stylesheets -->
    <link href="css/style.css" type="text/css" rel="stylesheet" />
    <link href="css/jquery-ui-1.9.2.custom.min.css" type="text/css" rel="stylesheet" />
    <link href="css/font.css" rel="stylesheet" type="text/css" />
    <!-- Javascripts -->
    <script src="js/jquery.js" type="text/javascript"></script>
    <script src="js/jquery-ui.min.js" type="text/javascript"></script>
    <script src="js/moment.min.js" type="text/javascript"></script>
    <script src="js/prettify.js" type="text/javascript"></script>
    <script src="js/common.js" type="text/javascript"></script>
    <script src="js/wall.js" type="text/javascript"></script>
    <script src="js/autocomplete.js" type="text/javascript"></script>
    <script src="js/doc-ready.js" type="text/javascript"></script>
    <!-- Title -->
    <title>MyProfile</title>
</head>

<body>
    <div id="more-menu" class="more-menu"></div>
    <div class="container">
        <div class="top">
            <a href="./" class="logo"><img src="img/myprofile-logo.png" alt="Logo" /> <img src="img/myprofile.png" alt="Slogan" /></a>
            <div class="page-title">
        	    <h1 class="text-shadow h1"><strong><?= $title ?></strong></h1>
            </div>
            <div class="login">
                <?php 
                if (isset($_SESSION['webid'])) {
                    // Messages (wall & private)
                    $bg = ($messages > 0) ? 'notification-active' : 'notification-inactive';
                    $wm_bg = ($wall_msg > 0) ? 'notification-active' : 'notification-inactive';
                    $wrapper = 'wrapper'
                    ?>
                    
                    <!-- Notifications -->
                    <span class="r5 <?= $bg ?>">
                        <a href="messages"><strong><?= $messages ?></strong> Notification<?php echo ($messages != 1) ? 's': ''; ?></a>
                    </span>
                    <?php if ($wall_msg > 0) { ?>
                        <span class="r5 <?= $wm_bg ?>">
                            <a href="<?= 'wall?user='.$_SESSION['user_hash'] ?>"><strong><?= $wall_msg ?></strong> Post<?php echo ($wall_msg != 1) ? 's': ''; ?> on your wall</a>
                        </span>
                    <?php } ?>
                    
                    <!-- user info -->
                    <div class="pull-left">
                        <a href="view?webid=<?= urlencode($_SESSION['webid']) ?>">
                        <img class="r3 login-img" height="38" alt="<?= $_SESSION['usr']?>" src="<?= $_SESSION['img'] ?>" />
                        <span class="login-user"><?= (strlen($_SESSION['usr']) > 20) ? substr($_SESSION['usr'], 0, 20) . '...' : $_SESSION['usr'] ?></span>
                        </a>
                    </div>
                    <div class="pull-right"><a href="?logout=1">
                        <img id="logout" src="img/logout.png" onmouseover="this.src='img/logout-on.png'" onmouseout="this.src='img/logout.png'" alt="Logout" title="Logout" />
                    </a></div>

                <?php } else { ?>
                    <span class="login-webid r5"><a href="<?= IDP.$page_uri ?>">WebID Login</a></span>
                    <span class="login-webid r5"><a href="profile">Get a WebID account</a></span>
                    <span class="login-webid r5"><a href="recovery">Account recovery / Pairing</a></span>
                <?php $wrapper = 'wrapper-max'; } ?>
            </div>
        </div>
        
    <?php if (isset($_SESSION['webid'])) { ?>
        <div id="nav" class="main-nav">
            <ul id="left-nav">
                <li><a href="wall" class="home margin-top-30 <?= $home_on ?>"><small>Home</small></a></li>
                <li><a href="<?= 'wall?user='.$_SESSION['user_hash'] ?>" class="wall margin-top-30 <?= $wall_on ?>"><small>My Wall</small></a></li>
                <li><a href="wall?activity=1" class="news margin-top-30 <?= $news_on ?>"><small>Activity</small></a></li>
                <li><a href="friends" class="friends margin-top-30 <?= $friends_on ?>"><small>Friends</small></a></li>
                <li id="messages"><a href="messages" class="messages margin-top-30 <?= $messages_on ?>"><small>Messages</small></a></li>
                <li id="profile"><a href="view" class="profile margin-top-30 <?= $profile_on ?>"><small>My Profile</small></a></li>
                <li id="settings"><a href="preferences" class="settings margin-top-30 <?= $pref_on ?>"><small>Preferences</small></a></li>
                <li id="more"><a href="#" class="more"><small>More...</small></a></li>
            </ul>
        </div>
    <?php } ?>
        <!-- wrapper -->
        if 
        <div id="main" class="<?= $wrapper ?> clearfix">
        
        
