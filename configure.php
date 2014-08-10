<?php /* $Id:  $ $URL:  $ */
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
require_once W2P_BASE_DIR . '/modules/taskimporter/taskimporter.class.php';

// deny all but system admins
$canEdit = canEdit('system');
if (!$canEdit) {
	$AppUI->redirect('m=public&a=access_denied');
}
$AppUI->savePlace();
$obj= new CTaskimporter();

// setup the title block
$titleBlock = new w2p_Theme_TitleBlock('Configure Taskimporter Module', 'support.png', $m, $m . '.' . $a);
$titleBlock->addCrumb('?m=system', 'system admin');
$titleBlock->addCrumb('?m=system&a=viewmods', 'modules list');
$titleBlock->show();
?>
