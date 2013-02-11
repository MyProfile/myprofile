<?php

class Wall {
    private $rows = 0; // By default there are no posts to display
    private $owner = null; // The user who owns the wall
    private $offset; // Offset for wall posts
    
    function __construct($owner_hash) {
        $this->owner = $owner_hash;
    }

    function load($count=20, $offset=0, $activity=False) {
        $posts = '';
        $this->offset = $offset;
        
        // display news feed for a certain user
        if (($_SESSION['webid']) && ($activity == True)) {
            $webids = sparql_get_people_im_friend_of($_SESSION['webid'], SPARQL_ENDPOINT);
            // Prepare the activity stream SQL query only if the user has friends (foaf:knows)
            if (sizeof($webids) > 0) {
                $query = 'SELECT * FROM pingback_messages WHERE to_hash IS NOT NULL AND wall=\'1\' AND (';
                foreach ($webids as $key => $from) {
                    $add = ($key > 0) ? ' OR' : '';
                    $query .= $add . " from_uri='" . mysql_real_escape_string($from) . "'";
                }
                $query .= ' OR from_uri="' . mysql_real_escape_string($_SESSION['webid']) . '") ORDER by date DESC LIMIT ' . $count;
                // Contains the offset value for fetching wall messages
                if (isset($this->offset))
                    $query .= ' OFFSET ' . mysql_real_escape_string($this->offset);
                    
                $result = mysql_query($query);

                if (!$result)
                    return 'Unable to connect to the database! Cannot display Activity Stream.';
                else
                    $rows = mysql_num_rows($result);       
            }
            
        } else {
            // get the last $count messages for a user
            $query = 'SELECT * FROM pingback_messages WHERE ' . 
                        'to_hash=\''.mysql_real_escape_string($this->owner).'\' ' .
                        'AND wall=\'1\' ' . 
                        'ORDER by date DESC ' .
                        'LIMIT ' . $count;
            // Contains the offset value for fetching wall messages
            if (isset($this->offset))
                $query .= ' OFFSET ' . mysql_real_escape_string($this->offset);   
            
            $result = mysql_query($query);

            if (!$result)
                return 'Unable to connect to the database! Cannot display wall posts.';
            else
                $rows = mysql_num_rows($result);
        }
        
        
        $posts .= '<!-- query='.htmlentities($query).' -->';
        
        if ($rows == 0) {
            // There are no messages on the wall
            $posts .= "<p><font style=\"font-size: 1.3em;\">There are no messages.</font></p>\n";
            mysql_free_result($result);
        } else {
            // update offset value
            $this->offset = $this->offset + $count;

            // populate table
            $i = 0;
            while ($row = mysql_fetch_assoc($result)) {
                // get name
                $name = $row['name'];
                // get picture
                $pic = $row['pic'];
                // get the date and multiply by 1000 for milliseconds, otherwise moment.js breaks
                $timestamp = $row['date'] * 1000;

                // to whom it is addressed
                if (strlen($row['to_uri']) > 0) {
                    $to_person = new MyProfile($row['to_uri'], $base_uri, SPARQL_ENDPOINT);
                    $to_person->load();
                    $to_name = $to_person->get_name();
                } else {
                    $to_name = 'MyProfile';
                }
                $msg = htmlentities($row['msg']);
                // replace WebIDs with actual names and links to the WebID
                $msg = preg_replace_callback("/&lt;(.*)&gt;/Ui", "preg_get_handle_by_webid", $msg);

                // store everything in this table
                $posts .= "<a class=\"anchor\" name=\"post_" . $row['id'] . "\"></a>\n";
                $posts .= "<div class=\"wall-box shadow r3 clearfix\">\n";
                $posts .= "<table border=\"0\" class=\"wall-message\" >\n";
                $posts .= "<tr valign=\"top\">\n";
                $posts .= "<td align=\"left\" class=\"speaker\">\n";
                // image
                $posts .= "<a class=\"avatar-link\" href=\"view?webid=" . urlencode($row['from_uri']) . "\" target=\"_blank\">";
                $posts .= " <img title=\"" . $name . "\" alt=\"" . $name . "\" width=\"50\" src=\"" . $pic . "\" class=\"r5 image\" />";
                $posts .= "</a>\n";
                $posts .= "</td>\n";
                $posts .= "<td>";
                $posts .= "<table border=\"0\">\n";
                $posts .= "<tr valign=\"top\">\n";
                $posts .= "<td>\n";
                // author's name
                $posts .= "<b><a href=\"view?webid=" . urlencode($row['from_uri']) . "\" target=\"_blank\" style=\"font-color: black;\">";
                $posts .= "   <span>" . $name . "</span>";
                $posts .= "</a></b> wrote ";       
                // activity stream
                if ($activity == True) {
                    $posts .= "on <a href=\"wall?user=" . $row['to_hash'] . "\" target=\"_blank\" style=\"font-color: black;\">";
                    $posts .= $to_name . "'s Wall ";
                    $posts .= "</a>";
                }
                // time of post
                $posts .= "<font color=\"grey\">";
                $posts .= "<span id=\"date_" . $row['id'] . "\">";
                $posts .= "<script type=\"text/javascript\">$('#date_" . $row['id'] . "').text(moment(" . $timestamp . ").from());</script>";
                $posts .= "</span></font>\n";
                
                $posts .= "<span class=\"pull-right\"><a href=\"#post_" . $row['id'] . "\">Link to this post.</a></span>\n";
                $posts .= "</td>\n";
                $posts .= "</tr>\n";
                // message
                $posts .= "<tr>\n";
                $posts .= "<td><div id=\"message_" . $row['id'] . "\"><pre class=\"wall-message\" id=\"message_text_" . $row['id'] . "\">\n";
                $posts .= put_links($msg);
        /*
                $ret .= put_links(preg_replace('/(.*?)(<.*?>|$)/se', 'html_entity_decode("$1").htmlentities("$2")', $row['msg'])); 
          */    
                $posts .= "</pre></div></td>\n";
                $posts .= "</tr>\n";
                // show options only if we are the source of the post
                $posts .= "<tr>\n";
                $posts .= "<td class=\"options\">";
                if (($_SESSION['webid'])
                    && (
                        ($_SESSION['webid'] == $row['from_uri'])
                        || (
                            ($_SESSION['webid'] == $row['to_uri'])
                            && ($this->owner))
                            && ($this->owner != 'local')
                        )
                ) {
                    $add = '?user=' . $owner_hash;
                    // add option to edit post
                    $posts .= "<a onclick=\"updateWall('message_text_" . $row['id'] . "', 'wall" . $add . "', '" . $row['id'] . "')\" style=\"cursor: pointer;\">Edit</a>";
                    // add option to delete post
                    $posts .= " | <a href=\"wall" . $add . "&del=" . $row['id'] . "\">Delete</a>\n";
                }
                
                // show vote counters and buttons for logged users
                $posts .= "<div class=\"options-vote\">".add_vote_buttons($row['id'])."</div>\n";
                
                $posts .= "</td>\n";
                $posts .= "</tr>\n";
                $posts .= "</table>\n";
                $posts .= "</td>\n";
                $posts .= "</tr>\n";
                $posts .= "</table>\n";
                $posts .= "</div>\n";
            $i++;
            }
            mysql_free_result($result);
        }
        if ($rows >= $count)
            $posts .= "<input type=\"button\" class=\"r5 btn loadmore\" id=\"more_".$row['id']."\" value=\"Load more\" onclick=\"loadWall('wall', 'more_".$row['id']."', '20', '".$this->offset."', '$this->owner', '".$activity."')\" />\n";
        return $posts;
    }
    
    function get_offset() {
        return $this->offset;
    }
}


