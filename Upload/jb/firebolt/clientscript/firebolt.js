fetch_object = function(elementid)
{
    return document.getElementById(elementid);
}
Firebolt = function()
{
    this.shout_frame = '';
    this.shout_editor = '';
    this.informant = '';
    this.informant_frame = '';
    this.fetching_shouts = false;
    this.inactive = false;
    this.inactive_time = 0;
    this.inactive_time_limit = 300;
    this.updating_stylizer = '';
    this.shout_limit = 0;
    this.pm_userid = 0;
    
    this.init = function(refreshtime, shout_limit)
    {
        console.log('Firebolt engine initiating..');
        
        this.shout_frame = fetch_object('jb_firebolt_shout_frame');
        this.shout_editor = fetch_object('jb_firebolt_shout_editor');
        this.informant = fetch_object('jb_firebolt_informant');
        this.informant_frame = fetch_object('jb_firebolt_informant_frame');
        
        this.shout_limit = shout_limit;
        
        this.fetch_shouts();
        
        this.inactive_check();
        
        setInterval("Firebolt.fetch_shouts();", parseInt(refreshtime) * 1000);
        
        console.log('Firebolt engine initiated!');
    }
    
    this.inform = function(message)
    {
        this.informant.innerHTML = message;
        this.informant_frame.style.display = 'block';
    }
    
    this.dismiss_informant = function()
    {
        this.informant_frame.style.display = 'none';
    }
    
    this.inactive_check = function()
    {
        if(this.inactive || this.inactive_time > this.inactive_time_limit)
        {
            setTimeout("Firebolt.inactive_check()", 1000);
			return false;
        }
        
        this.inactive_time++;
        
        if (this.inactive_time > this.inactive_time_limit)
		{
			this.inactive = true;
            this.inform('You are now inactive in the shoutbox, and messages will no longer be refreshed. <a href="#" onclick="return Firebolt.go_active();">Go active</a>');
		}

		setTimeout("Firebolt.inactive_check()", 1000);
    }
    
    this.go_active = function()
    {
        this.dismiss_informant();
        this.inactive_time = 0;
        this.inactive = false;
        
        return false;
    }
    
    this.fetch_shouts = function()
    {
        if(this.inactive)
        {
            return false;
        }
        
        console.log('Fetching shouts and active users..');
        
        if(this.pm_userid < 1)
        {
            $('#jb_firebolt_shout_frame').load('firebolt.php?do=shouts&limit=' + this.shout_limit);
        }
        else
        {
            $('#jb_firebolt_shout_frame').load('firebolt.php?do=shouts&pm=' + this.pm_userid.toString() + '&limit=' + this.shout_limit);
        }
        
        $('#jb_firebolt_activeusers').load('firebolt.php?do=fetch_active_users');
        console.log('Shouts and active users fetched!');
    }
    
    this.shout = function()
    {
        shout = PHP.trim(this.shout_editor.value);
        this.clear();
        
        if(shout == '')
        {
            return false;
        }        
        
        if(this.inactive)
        {
            this.go_active();
        }
        
        this.shout.ajax = new vB_AJAX_Handler(true);
        this.shout.ajax.onreadystatechange(Firebolt.post_shout);
        if(this.pm_userid == 0)
        {
            this.shout.ajax.send('firebolt.php', 'do=shout&message=' + PHP.urlencode(shout));
        }
        else
        {
            this.shout.ajax.send('firebolt.php', 'do=shout&pmto=' + this.pm_userid.toString() + '&message=' + PHP.urlencode(shout));
        }
        
        
        return false;
    }
    
    this.post_shout = function()
    {
        ajax = Firebolt.shout.ajax;
        
        if(ajax.handler.readyState == 4 && ajax.handler.status == 200)
		{
            if(PHP.trim(ajax.handler.responseText) == 'completed')
			{
                Firebolt.fetch_shouts();
            }
        }
    }
    
    this.clear = function()
    {
        this.shout_editor.value = '';
    }
    
    this.change_stylizer = function(property)
    {
        switch(property)
        {
            case 'bold':
            case 'underline':
            case 'italic':
            {
                this.updating_stylizer = property;
                this.set_stylizer();
                return false;
            }
            case 'font':
            case 'color':
            {
                this.updating_stylizer = property;
                this.set_stylizer_select();
                return false;
            }
            default:
            {
                return false;
            }
        }
    }
    
    this.set_stylizer = function()
    {
        property = this.updating_stylizer;
        $('#jb_firebolt_button_' + property).toggleClass('jb_firebolt_editor_button_selected');
        this.inform('Your stylizer properties were updated successfully!');
        setTimeout('Firebolt.dismiss_informant()', 5000);
        this.set_stylizer.ajax = new vB_AJAX_Handler(true);
        this.set_stylizer.ajax.onreadystatechange(Firebolt.stylizer_set);
        this.set_stylizer.ajax.send('firebolt.php', 'do=set_stylizer&property=' + property);
        
        return false;
    }
    
    this.set_stylizer_select = function()
    {
        property = this.updating_stylizer;
        stylizer_select = fetch_object('jb_firebolt_stylizer_' + property);
        new_value = stylizer_select.value;
        this.inform('Your stylizer properties were updated successfully!');
        setTimeout('Firebolt.dismiss_informant()', 5000);
        this.set_stylizer_select.ajax = new vB_AJAX_Handler(true);
        this.set_stylizer_select.ajax.onreadystatechange(Firebolt.stylizer_set_select);
        this.set_stylizer_select.ajax.send('firebolt.php', 'do=set_stylizer&property=' + property + '&value=' + new_value);
        
        return false;
    }
    
    this.stylizer_set = function()
    {
        ajax = Firebolt.set_stylizer.ajax;
        
        if(ajax.handler.readyState == 4 && ajax.handler.state == 200)
        {
            if(PHP.trim(ajax.handler.responseText) == 'updated')
            {
                console.log('r u der');
                return true;
            }
        }
    }
    
    this.stylizer_set_select = function()
    {
        ajax = Firebolt.set_stylizer_select.ajax;
        
        if(ajax.handler.readyState == 4 && ajax.handler.state == 200)
        {
            if(PHP.trim(ajax.handler.responseText) == 'updated')
            {
                console.log('r u der');
                return true;
            }
        }
    }
    
    this.fetch_smilies = function()
    {
        openWindow('misc.php?' + SESSIONURL + 'do=getsmilies&editorid=jb_firebolt_shout_editor', 400, 480, 'smiley_window');
    }
    
    this.open_pm = function(userid, username)
    {
        if(parseInt(userid) == 0)
        {
            this.dismiss_informant();
        }
        else
        {
            this.inform('You are now in private message with ' + username + '. <a onclick="Firebolt.open_pm(\'0\', \'\');">Return</a>');
        }
        this.pm_userid = parseInt(userid);
        this.fetch_shouts();
    }
}