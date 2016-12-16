<?php

// This function checks if the UA string matches the database
// If so, it displays the GoMobile theme

function gomobile_forcetheme()
{
    global $db, $mybb, $plugins, $current_page;

    // We're going to work around the per forum theme setting by altering the $current_page value throughout global.php
    // Then set it back to what it's supposed to be at global_end so it doesn't muck anything up (hopefully)
    $valid = array(
        "showthread.php",
        "forumdisplay.php",
        "newthread.php",
        "newreply.php",
        "ratethread.php",
        "editpost.php",
        "polls.php",
        "sendthread.php",
        "printthread.php",
        "moderation.php"
    );

    gomobile_load_language();

    if($mybb->session->is_spider == false){
        // Force some changes to our footer, but only if we're not a bot
        $GLOBALS['gmb_orig_style'] = intval($mybb->user['style']);
        $GLOBALS['gmb_post_key'] = md5($mybb->post_code);
        $plugins->add_hook("global_end", "gomobile_forcefooter");
    }

    // Has the user chosen to disable GoMobile completely?

    if(
        isset($mybb->user['usemobileversion']) &&
        $mybb->user['usemobileversion'] == 0 &&
        $mybb->user['uid'] &&
        $mybb->cookies['gomobile'] != "force"
    )
    {
        return false;
    }

    // Has the user temporarily disabled GoMobile via cookies?

    if($mybb->cookies['gomobile'] == "disabled")
    {
        return false;
    }

    // Is the admin using theme permission settings?
    // If so, check them

    if($mybb->settings['gomobile_permstoggle'] == 1)
    {
        // Fetch the theme permissions from the database
        $tquery = $db->simple_select("themes", "*", "tid = '{$mybb->settings['gomobile_theme_id']}'");
        $tperms = $db->fetch_field($tquery, "allowedgroups");

        // Also explode our user's additional groups

        $userag = array();

        if($mybb->user['additionalgroups'])
        {
            $userag = explode(",", $mybb->user['additionalgroups']);
        }

        // If the user doesn't have permission to use the theme...

        if($tperms != "all")
        {
            $canuse = explode(",", $tperms);

            if(!in_array($mybb->user['usergroup'], $canuse) && !in_array($userag, $canuse)){
                return false;
            }

        }

    }

    // Grab the strings and put them into an array
    $list = $mybb->settings['gomobile_strings'];
    $replace = array("\n", "\r");
    $list = str_replace($replace, ",", $list);
    $list = str_replace(",,", ",", $list);
    $list = explode(",", $list);
    $switch = false;

    foreach($list as $uastring)
    {
        // Run as long as there hasn't been a match yet

        if(!$switch && $uastring)
        {
            // Switch to GoMobile if the UA matches our list

            if(stristr($_SERVER['HTTP_USER_AGENT'], $uastring))
            {
                $switch = true;
                $mybb->user['style'] = $mybb->settings['gomobile_theme_id'];
            }

        }

    }

    // Have we got this far without catching somewhere? Have we enabled mobile version?

    if($mybb->cookies['gomobile'] == "force" && $switch == false)
    {
        $mybb->user['style'] = $mybb->settings['gomobile_theme_id'];
    }


    if(in_array($current_page, $valid) && $mybb->user['style'] == $mybb->settings['gomobile_theme_id'])
    {
        $current_page = "gomobile_temp";
    }

}