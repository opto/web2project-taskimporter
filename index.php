<?php  /* $Id$ $URL$ */
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
if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly.');
}

global $AppUI, $project_id, $contact_id, $company_id;
        $fff=$AppUI->getPref('MAILALL');

$perms = $AppUI->acl();
if (!$perms->checkModuleItem('taskimporter', 'access')) {
    $AppUI->redirect('m=public&a=access_denied');
}


$user_list = $users = $perms->getPermittedUsers('projects');

$titleBlock = new w2p_Theme_TitleBlock('Taskimporter', '', $m, "$m.$a");
//$titleBlock->addCell('<table><tr><form action="?m=todos" method="post" name="userIdForm" accept-charset="utf-8"><td nowrap="nowrap" align="right">' . $AppUI->_('Owner') . '</td><td nowrap="nowrap" align="left">' . arraySelect($user_list, 'todo_user', 'size="1" class="text" onChange="document.userIdForm.submit();"', $owner, false) . '</td></form></tr></table>', '', '', '');
$titleBlock->show();


$task_imp=new CTaskimporter();
$task_imp->processEmailsToTasks($AppUI);

?>
 
<?php /* $Id$ $URL$ */