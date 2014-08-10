<?php /* $Id$ $URL$ */
/*
Copyright (c) 2012 -2013 Klaus Buecher (opto)
*
* Description:	Taskimporter; add tasks by email
*
* Author:		Klaus Buecher
*
* License:		modified BSD (see GNU.org)
*
* Contact: via web2project forums
* 
* CHANGE LOG
*
* version 1.1.0
* 	Creation
*
*/
if (!defined('W2P_BASE_DIR')){
  die('You should not access this file directly.');
}





/**
 * Name:			Dokuwiki
 * Directory: dokuwiki
 * Type:			user
 * UI Name:		dokuwiki
 * UI Icon: 	?
 */

$config = array();
$config['mod_name']        = 'Taskimporter';			    // name the module
$config['mod_version']     = '1.0.0';			      	// add a version number
$config['mod_directory']   = 'taskimporter';             // tell web2project where to find this module
$config['mod_setup_class'] = 'CSetupTaskimporter';		// the name of the PHP setup class (used below)
$config['mod_type']        = 'user';				      // 'core' for modules distributed with w2p by standard, 'user' for additional modules
$config['mod_ui_name']	   = $config['mod_name']; // the name that is shown in the main menu of the User Interface
$config['mod_ui_icon']     = '';                  // name of a related icon
$config['mod_description'] = 'Import tasks';			    // some description of the module
$config['mod_config']      = false;					      // show 'configure' link in viewmods
$config['mod_main_class']  = 'CTaskimporter';

$config['permissions_item_table'] = 'taskimporter';
$config['permissions_item_field'] = 'taskimporter_id';
$config['permissions_item_label'] = 'taskimporter_title';

class CSetupTaskimporter
{
	public function install()
	{ 
		global $AppUI;

        $q = new w2p_Database_Query();
		$q->createTable('taskimporter');
		$sql = '(
			taskimporter_id int(10) unsigned NOT NULL AUTO_INCREMENT,
			max_email_id int(10) unsigned,
			
			PRIMARY KEY  (taskimporter_id))
			ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';
		$q->createDefinition($sql);
		$q->exec();
                $q->clear();
                $q->addTable('taskimporter','ti');
                $q->addInsert('max_email_id','0');
		$q->exec();
                $q->clear();

        $perms = $AppUI->acl();
        return $perms->registerModule('Taskimporter', 'taskimporter');
	}

	public function upgrade($old_version)
	{
        switch ($old_version) {
            case '1.0.0':
//            case '1.0.1':
            default:
				//do nothing
		}
		return true;
	}

	public function remove()
	{ 
		global $AppUI;

        $q = new w2p_Database_Query;
		$q->dropTable('taskimporter');
		$q->exec();

/**/	
        $perms = $AppUI->acl();
        return $perms->unregisterModule('taskimporter');
	}


    public function configure() {
        global $AppUI;
        $AppUI->redirect('m=taskimporter&a=configure');
        return true;
    }


}
