<?php

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################

define('THIS_SCRIPT', 'firebolt');
define('CSRF_PROTECTION', true);  
// change this depending on your filename

// ######################### REQUIRE BACK-END ############################
// if your page is outside of your normal vb forums directory, you should change directories by uncommenting the next line
// chdir ('/path/to/your/forums');
require_once('./global.php');
require_once(DIR . '/jb/firebolt/includes/class_firebolt.php');
require_once(DIR . '/includes/class_humanverify.php');
require_once(DIR . '/includes/functions_editor.php');
require_once(DIR . '/includes/functions_bigthree.php');
require_once(DIR . '/includes/class_bbcode.php');
require_once(DIR . '/includes/class_wysiwygparser.php');

$firebolt = new Firebolt;

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

if(!isset($_REQUEST['do']) or trim($_REQUEST['do']) == null)
{
    exit;
}

if(!$vbulletin->options['jb_firebolt_enabled'])
{
    exit;
}

if($_REQUEST['do'] == 'shouts')
{
    if(isset($_REQUEST['limit']))
    {
        if(isset($_REQUEST['pm']))
        {
            echo $firebolt->fetch_shouts($_REQUEST['limit'], $_REQUEST['pm']);
        }
        else
        {
            echo $firebolt->fetch_shouts($_REQUEST['limit']);
        }
    }
    exit;
}

if($_REQUEST['do'] == 'shout')
{
	if($vbulletin->userinfo['userid'] > 0)
	{
		if(isset($_REQUEST['message']))
		{
			if(trim($_REQUEST['message']) != null)
			{
				$vbulletin->input->clean_gpc('r', 'message', TYPE_NOHTML);
                $message = $vbulletin->GPC['message'];
                
                if(isset($_REQUEST['pmto']))
                {
                    $vbulletin->input->clean_gpc('r', 'pmto', TYPE_INT);
                    $firebolt->shout($message, $vbulletin->GPC['pmto']);
                }
                else
                {
                    $firebolt->shout($message);
                }
			}
			else
			{
				die('nomsg');
			}
		}
		else
		{
			die('nomsg');
		}
	}
	else
	{
		die('notloggedin');
	}
}

if($_REQUEST['do'] == 'set_stylizer')
{
    if(!isset($_REQUEST['property']))
    {
        echo 'failed';
        exit;
    }
    
    switch($_REQUEST['property'])
    {
        case 'bold':
        case 'underline':
        case 'italic':
            echo $firebolt->update_stylizer($_REQUEST['property']);
        break;
        case 'font':
        case 'color':
            if(!isset($_REQUEST['value']))
            {
                echo 'failed';
                exit;
            }
            echo $firebolt->update_stylizer($_REQUEST['property'], $_REQUEST['value']);
        break;
        default:
            echo 'failed';
            exit;
        break;
    }
}

if($_REQUEST['do'] == 'fetch_active_users')
{
    if($vbulletin->options['jb_firebolt_activeusers'] != 'disabled')
    {
        $active_users = $firebolt->fetch_active_users();
        
        echo $active_users;
        exit;
    }
}

if($_REQUEST['do'] == 'xl')
{
    if($firebolt->can_use())
    {
        $firebolt->fetch_editor_settings();
        $fonts = $firebolt->stylizer_fonts;
        $colors = $firebolt->stylizer_colors;
        
        $templater = vB_Template::create('jb_firebolt_editor');
        $templater->register('usersettings', $firebolt->usersettings);
        $templater->register('fonts', $fonts);
        $templater->register('colors', $colors);
        $templater->register('firebolt_xl', true);
        $editor = $templater->render();
        
        $templater = vB_Template::create('jb_firebolt_css');
        $templater->register('firebolt_xl', true);
        $css = $templater->render();
        
        if($vbulletin->options['jb_firebolt_activeusers'] != 'disabled')
        {
            $active_users = $firebolt->fetch_active_users();
            $templater = vB_Template::create('jb_firebolt_activeusers');
            $templater->register('firebolt_activeusers', $active_users);
            $activeusers = $templater->render();
        }
        
        $templater = vB_Template::create('jb_firebolt_xl_frame');
        $templater->register_page_templates();
        $templater->register('firebolt_editor', $editor);
        $templater->register('firebolt_css', $css);
        if($vbulletin->options['jb_firebolt_activeusers'] != 'disabled') { $templater->register('firebolt_activeusers_frame', $activeusers); }
        print_output($templater->render());
    }
    else
    {
        print_no_permission();
    }
}

?>