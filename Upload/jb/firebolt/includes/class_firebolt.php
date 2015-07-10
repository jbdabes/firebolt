<?php

class Firebolt
{
    private $vbulletin;
    private $command_output = false;
    public $usersettings = array();
    public $stylizer_fonts = '';
    public $stylizer_colors = '';
    private $keep_shouting = true;
    public $activeusers = '';
    
    function __construct()
    {
        global $vbulletin;
        $this->vbulletin = $vbulletin;
        
        $this->check_user_settings();
    }
    
    public function fetch_editor_settings()
    {
        $fonts = explode(PHP_EOL, $this->vbulletin->options['jb_firebolt_allowed_fonts']);
        foreach($fonts as $font)
        {
            $font = trim($font);
            if($font == null)
            {
                continue;
            }
            $templater = vB_Template::create('jb_firebolt_stylizer_option_font');
            $templater->register('font', $font);
            if($this->usersettings['font'] == $font)
            {
                $templater->register('selected', ' selected');
            }
            else
            {
                $templater->register('selected', '');
            }
            $this->stylizer_fonts .= $templater->render();
        }
        
        $colors = explode(PHP_EOL, $this->vbulletin->options['jb_firebolt_allowed_colors']);
        foreach($colors as $color)
        {
            $color = trim($color);
            if($color == null)
            {
                continue;
            }
            $templater = vB_Template::create('jb_firebolt_stylizer_option_color');
            $templater->register('color', $color);
            if($this->usersettings['color'] == $color)
            {
                $templater->register('selected', ' selected');
            }
            else
            {
                $templater->register('selected', '');
            }
            $this->stylizer_colors .= $templater->render();
        }
    }
    
    public function update_stylizer($property, $value = null)
    {
        switch($property)
        {
            case 'bold':
            case 'underline':
            case 'italic':
                if($this->usersettings[$property])
                {
                    $this->usersettings[$property] = '0';
                    $this->vbulletin->db->query("
                        UPDATE " . TABLE_PREFIX . "jb_firebolt_users
                        SET
                            shout_" . $property . " = '0'
                        WHERE userid = '" . intval($this->vbulletin->userinfo['userid']) . "'
                    ");
                }
                else
                {
                    $this->usersettings[$property] = '1';
                    $this->vbulletin->db->query("
                        UPDATE " . TABLE_PREFIX . "jb_firebolt_users
                        SET
                            shout_" . $property . " = '1'
                        WHERE userid = '" . intval($this->vbulletin->userinfo['userid']) . "'
                    ");
                }
            break;
            case 'font':
            case 'color':
                if($this->usersettings[$property] == $value)
                {
                    echo 'nochange';
                    exit;
                }
                
                $allowed_properties = explode(PHP_EOL, $this->vbulletin->options['jb_firebolt_allowed_' . $property . 's']);
                $real_allowed_properties = array();
                
                foreach($allowed_properties as $key => $allowed_property)
                {
                    $allowed_property = trim($allowed_property);
                    $real_allowed_properties[$key] = $allowed_property;
                }
                
                if(!is_array($real_allowed_properties))
                {
                    echo 'failed';
                    exit;
                }
                
                if(!in_array(trim($value), $real_allowed_properties))
                {
                    echo 'failed';
                    exit;
                }
                
                $this->usersettings[$property] = $value;
                $this->vbulletin->db->query("
                    UPDATE " . TABLE_PREFIX . "jb_firebolt_users
                    SET
                        " . $property . " = '" . $this->vbulletin->db->escape_string($value) . "'
                    WHERE userid = '" . intval($this->vbulletin->userinfo['userid']) . "'
                ");
            break;
            default:
                echo 'failed';
                exit;
            break;
        }
        
        echo 'updated';
        exit;
    }
    
    public function check_user_settings()
    {        
        $query = $this->vbulletin->db->query("
            SELECT * FROM " . TABLE_PREFIX . "jb_firebolt_users
            WHERE userid = '" . intval($this->vbulletin->userinfo['userid']) . "'
        ");
        
        if($this->vbulletin->db->num_rows($query) < 1)
        {
            $this->create_user_settings();
        }
        
        while($user = $this->vbulletin->db->fetch_array($query))
        {
            $this->usersettings['font'] = $user['font'];
            $this->usersettings['color'] = $user['color'];
            $this->usersettings['bold'] = $user['shout_bold'];
            $this->usersettings['underline'] = $user['shout_underline'];
            $this->usersettings['italic'] = $user['shout_italic'];
            $this->usersettings['banned'] = $user['is_banned'];
        }
    }
    
    private function check_user_in_table($userid)
    {
        $query = $this->vbulletin->db->query("
            SELECT * FROM " . TABLE_PREFIX . "jb_firebolt_users
            WHERE userid = '" . intval($userid) . "'
        ");
        
        if($this->vbulletin->db->num_rows($query) < 1)
        {
            $this->create_user_settings($userid);
        }
    }
    
    private function create_user_settings($userid = null)
    {
        if($userid == null)
        {
            $userid = $this->vbulletin->userinfo['userid'];
        }
        else
        {
            if($userid < 1)
            {
                exit;
            }
        }
        
        $add_user = $this->vbulletin->db->query("
            INSERT INTO " . TABLE_PREFIX . "jb_firebolt_users
            (
                userid,
                font,
                color,
                shout_bold,
                shout_underline,
                shout_italic,
                is_banned
            )
            VALUES
            (
                '" . intval($userid) . "',
                'Arial',
                'Black',
                '0',
                '0',
                '0',
                '0'
            )
        ");
    }
    
    public function fetch_shouts($limit = 20, $userid = 0)
    {
        if($userid < 1)
        {
            $query = $this->vbulletin->db->query("
                SELECT * FROM " . TABLE_PREFIX . "jb_firebolt_shout
                WHERE pmto = '0' OR pmto = '" . intval($this->vbulletin->userinfo['userid']) . "' OR userid = '" . ($this->vbulletin->userinfo['userid']) . "'
                ORDER BY id DESC LIMIT 0," . intval($limit)
            );
        }
        else
        {
            $query = $this->vbulletin->db->query("
                SELECT * FROM " . TABLE_PREFIX . "jb_firebolt_shout
                WHERE
                    ( userid = '" . intval($userid) . "' && pmto = '" . intval($this->vbulletin->userinfo['userid']) . "' )
                OR
                    ( userid = '" . intval($this->vbulletin->userinfo['userid']) . "' && pmto = '" . intval($userid) . "' )
                ORDER BY id DESC LIMIT 0," . intval($limit)
            );
        }
        
        $output = '';
        
        if($this->usersettings['banned'])
        {
            $notice = 'You are currently banned from the shoutbox.';
        }
        else
        {
            $notice = $this->vbulletin->options['jb_firebolt_notice'];
        }
        
        if(trim($notice) != null)
        {
            $bbcode_parser = new vB_BbCodeParser($this->vbulletin, fetch_tag_list());
            if (!function_exists('convert_url_to_bbcode'))
            {
                require_once(DIR . '/includes/functions_newpost.php');
            }
            $notice = convert_url_to_bbcode($notice);
            $notice = $bbcode_parser->parse_bbcode($notice, true, false, false);
            
            $output .= "<b>Notice:</b> " . $notice . "<br />";
        }
        
        if(!$this->usersettings['banned'])
        {
            while($shout = $this->vbulletin->db->fetch_array($query))
            {
                $bbcode_parser = new vB_BbCodeParser($this->vbulletin, fetch_tag_list());
                
                if (!function_exists('convert_url_to_bbcode'))
                {
                    require_once(DIR . '/includes/functions_newpost.php');
                }
                
                $sdate = vbdate($this->vbulletin->options['dateformat'], $shout['shouttime']);
                $stime = vbdate($this->vbulletin->options['timeformat'], $shout['shouttime']);
                $message = $shout['shout'];
                $message = convert_url_to_bbcode($message);
                $message = $bbcode_parser->parse_bbcode($message, true, false, false);
                
                if(trim($message) == null)
                {
                    $this->vbulletin->db->query("
                        DELETE FROM " . TABLE_PREFIX . "jb_firebolt_shout
                        WHERE id = '" . intval($shout['id']) . "'
                    ");
                    continue;
                }
                
                $user = fetch_userinfo($shout['userid']);
                
                if(!$this->vbulletin->options['jb_firebolt_new_shout_layout'])
                {
                    $message = $this->stylize($message, $user['userid']);
                }
                
                if($this->vbulletin->options['jb_firebolt_new_shout_layout'])
                {
                    $username = $user['username'];
                    $templater = vB_Template::create('jb_firebolt_shout_modern');
                    $templater->register('user', $user);
                }
                else
                {
                    $username = fetch_musername($user);
                    $templater = vB_Template::create('jb_firebolt_shout');
                }
                $templater->register('sdate', $sdate);
                $templater->register('stime', $stime);
                $templater->register('username', $username);
                $templater->register('message', $message);
                
                $output .= $templater->render();
            }
        }
        
        
        return $output;
    }
    
    private function stylize(&$message, $userid = null)
    {
        if($userid == null)
        {
            return $message;
        }
        
        $shout_customization = $this->vbulletin->db->query("
            SELECT font, color, shout_bold, shout_underline, shout_italic FROM " . TABLE_PREFIX . "jb_firebolt_users WHERE userid = '" . intval($userid) . "'
        ");
        
        if($this->vbulletin->db->num_rows($shout_customization) < 1)
        {
            return $message;
        }
        
        while($style = $this->vbulletin->db->fetch_array($shout_customization))
        {
            $message = "<font color='" . $style['color'] . "'><span style='font-family: " . $style['font'] . ";" . (($style['shout_bold']) ? ' font-weight: bold;' : '') . (($style['shout_underline']) ? ' text-decoration: underline;' : '') . (($style['shout_italic']) ? ' font-style: italic;' : '') . "'>" . $message . "</span></font>";
            return $message;
        }
    }
    
    public function shout($message, $touserid = 0)
    {
        if($this->usersettings['banned'])
        {
            exit;
        }
        $this->is_command($message);
        
        if(!$this->keep_shouting)
        {
            echo 'completed';
            exit;
        }
        
        $message = preg_replace('/\&#(.+?);/i','',$message);
        $message = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', html_entity_decode($message));
        
        /*if($this->command_output)
        {
            echo 'completed';
            exit;
        }*/
        
        $chk = count(trim($message));
        
        if($chk == 0)
        {
            echo 'nope';
            exit;
        }
        
        if(strlen($message) > 140)
        {
            echo 'maxchars';
            exit;
        }
        
        $message = addslashes(convert_urlencoded_unicode(htmlspecialchars_uni($message)));

        $this->vbulletin->db->query("
            INSERT INTO " . TABLE_PREFIX . "jb_firebolt_shout
            (
                userid,
                shout,
                shouttime,
                pmto
            )
            VALUES
            (
                '" . intval($this->vbulletin->userinfo['userid']) . "',
                '" . $message . "',
                '" . TIMENOW . "',
                '" . intval($touserid) . "'
            )
        ");
        
        $this->update_activity();
        
        echo 'completed';
        exit;
    }
    
    private function is_banned($userid)
    {
        $query = $this->vbulletin->db->query("
            SELECT is_banned FROM " . TABLE_PREFIX . "jb_firebolt_users
            WHERE userid = '" . intval($userid) . "'
        ");
        
        if($this->vbulletin->db->num_rows($query) < 1)
        {
            return false;
        }
        
        while($user = $this->vbulletin->db->fetch_array($query))
        {
            if($user['is_banned'])
            {
                return true;
            }
        }
        
        return false;
    }
    
    private function is_command(&$message)
    {
        if(trim($message) == '/prune' && $this->can_admin())
        {
            $this->vbulletin->db->query("
                TRUNCATE TABLE " . TABLE_PREFIX . "jb_firebolt_shout
            ");
            
            $message = 'Shoutbox pruned successfully.';
            $this->command_output = true;
            
            return true;
        }
        
        if(preg_match("#^(/prune\s+?)#i", $message, $matches) && $this->can_mod())
        {
            $user = htmlspecialchars_uni(addslashes(trim(str_replace($matches[0], '', $message))));

            if ($user = $this->vbulletin->db->query_first("SELECT userid, username, usergroupid, membergroupids FROM " . TABLE_PREFIX . "user WHERE userid = '$user' OR username = '$user'"))
            {
                $message = 'Pruned all shouts by ' . $user['username'] . ' successfully.';
                $this->command_output = true;
                
                $this->vbulletin->db->query("
                    DELETE FROM " . TABLE_PREFIX . "jb_firebolt_shout
                    WHERE userid = '" . intval($user['userid']) . "'
                ");
            }
        
        return true;
        }
        
        if(preg_match("#^(/ban\s+?)#i", $message, $matches) && $this->can_mod())
        {
            $user = htmlspecialchars_uni(addslashes(trim(str_replace($matches[0], '', $message))));
            
            if ($user = $this->vbulletin->db->query_first("SELECT userid, username, usergroupid, membergroupids FROM " . TABLE_PREFIX . "user WHERE userid = '$user' OR username = '$user'"))
            {
                $this->check_user_in_table($user['userid']);
                if (!$this->is_banned($user['userid']))
                {
                    $message = 'User ' . $user['username'] . ' banned from the shoutbox successfully.';
                    $this->command_output = true;
                    
                    $this->vbulletin->db->query("
                        UPDATE " . TABLE_PREFIX . "jb_firebolt_users
                        SET
                            is_banned = '1'
                        WHERE userid = '" . intval($user['userid']) . "'
                    ");
                }
                else
                {
                    $this->keep_shouting = false;
                }
            }
            
            return true;
        }
        
        if(preg_match("#^(/unban\s+?)#i", $message, $matches) && $this->can_mod())
        {
            $user = htmlspecialchars_uni(addslashes(trim(str_replace($matches[0], '', $message))));
            
            if ($user = $this->vbulletin->db->query_first("SELECT userid, username, usergroupid, membergroupids FROM " . TABLE_PREFIX . "user WHERE userid = '$user' OR username = '$user'"))
            {
                $this->check_user_in_table($user['userid']);
                if ($this->is_banned($user['userid']))
                {
                    $message = 'User ' . $user['username'] . ' unbanned from the shoutbox successfully.';
                    $this->command_output = true;
                    
                    $this->vbulletin->db->query("
                        UPDATE " . TABLE_PREFIX . "jb_firebolt_users
                        SET
                            is_banned = '0'
                        WHERE userid = '" . intval($user['userid']) . "'
                    ");
                }
                else
                {
                    $this->keep_shouting = false;
                }
            }
            
            return true;
        }
        
        if ((preg_match("#^(/notice\s+?)#i", $message, $matches) || trim($message) == '/removenotice') && $this->can_mod())
        {
            if (trim($message) != '/removenotice')
            {
                $message = addslashes(convert_urlencoded_unicode(trim(str_replace($matches[0], '', $message))));
            }
            else
            {
                $message = '';
            }

            $this->vbulletin->db->query("
                UPDATE " . TABLE_PREFIX . "setting
                SET
                    value = '" . $message . "'
                WHERE varname = 'jb_firebolt_notice'
            ");

            $this->keep_shouting = false;

            $this->fetch_shouts();
            $this->build_options();

            return true;
        }
    }
    
    private function can_admin()
    {
        $admin_usergroups = unserialize($this->vbulletin->options['jb_firebolt_canadmin']);
        if(is_array($admin_usergroups))
        {
            foreach($admin_usergroups as $key => $value)
            {
                if(is_member_of($this->vbulletin->userinfo, $value))
                {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private function can_mod()
    {
        $mod_usergroups = unserialize($this->vbulletin->options['jb_firebolt_canmod']);
        if(is_array($mod_usergroups))
        {
            foreach($mod_usergroups as $key => $value)
            {
                if(is_member_of($this->vbulletin->userinfo, $value))
                {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function can_use()
    {
        $usergroups = unserialize($this->vbulletin->options['jb_firebolt_canuse']);
        if(is_array($usergroups))
        {
            foreach($usergroups as $key => $value)
            {
                if(is_member_of($this->vbulletin->userinfo, $value))
                {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private function update_activity()
    {
        $this->vbulletin->db->query("
            UPDATE " . TABLE_PREFIX . "jb_firebolt_users
            SET
                lastactivity = '" . TIMENOW . "'
            WHERE userid = '" . intval($this->vbulletin->userinfo['userid']) . "'
        ");
    }
    
    public function fetch_active_users()
    {
        $inactive = TIMENOW - (60 * 5);
        $activeusers = '';
        $templater = vB_Template::create('jb_firebolt_activeusers_header');
        $activeusers .= $templater->render();
            
        $query = $this->vbulletin->db->query("
            SELECT * FROM " . TABLE_PREFIX . "jb_firebolt_users
            WHERE lastactivity > " . intval($inactive) . "
            ORDER BY userid ASC
        ");
        
        if($this->vbulletin->db->num_rows($query) < 1)
        {
            $username = 'No active users';
            $userid = '0';
            $templater = vB_Template::create('jb_firebolt_activeuser');
            $templater->register('musername', $username);
            $templater->register('username', $username);
            $templater->register('userid', $userid);
            $activeusers .= $templater->render();
        }
        else
        {
            while($activeuser = $this->vbulletin->db->fetch_array($query))
            {
                $user = fetch_userinfo($activeuser['userid']);
                $musername = fetch_musername($user);
                $templater = vB_Template::create('jb_firebolt_activeuser');
                $templater->register('musername', $musername);
                $templater->register('username', $user['username']);
                $templater->register('userid', $user['userid']);
                $activeusers .= $templater->render();
            }
        }
        
        return $activeusers;
    }
    
    private function build_options()
    {
        require_once(DIR . '/includes/adminfunctions.php');

        build_options();
    }
}

?>