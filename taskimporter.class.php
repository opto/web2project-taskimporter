<?php /* $Id$ $URL$ */
/*
todo:
temp save of email: replace c:/tmp
parsedatestring t, t+3
bei xml anhang zeigt tooltip description, aber der taskview nicht

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
* 
* parsing of multipart emails: source of code (partially): comments to imap_fetchstructure at php.net
*
*/
if (!defined('W2P_BASE_DIR'))
{
  die('You should not access this file directly.');
}


 global $emailserver, $userid, $passwd, $AppUI, $Importeruserid;


 
 $serverfilename=W2P_BASE_DIR.'/modules/taskimporter/serverfilename.txt';
if (file_exists($serverfilename)) {
	
include $serverfilename;
/* $emailserver="{mailserver:110/pop3/notls}";
 $userid="userid";
 $passwd="password";
*/
}
else {
$emailserver="mailserver:110/pop3/notls}"; //also imap is possible, see php manual.
 //for some php versions, it seems /notls is needed if no security
 //see php manual on imap_open() for pop3, imap string format instructions
 $userid="username";
 $passwd="password";
 $Importeruserid=1; //w2p userid for adding/creating the tasks. If set to a dedicated
 //userid, it is easy to idenify emaill added tasks in the database
 	
}


##
## CTaskimporter Class
##
	include_once W2P_BASE_DIR . '/modules/taskimporter/fMailbox.php';

function getmsg($mbox,$mid) {
     // input $mbox = IMAP stream, $mid = message id
     // output all the following:
     global $charset,$htmlmsg,$plainmsg,$attachments;
     $htmlmsg = $plainmsg = $charset = '';
     $attachments = array();
 
    // HEADER
     $h = imap_header($mbox,$mid);
     // add code here to get date, from, to, cc, subject...
 
    // BODY
     $s = imap_fetchstructure($mbox,$mid);
     if (!$s->parts)  // simple
         getpart($mbox,$mid,$s,0);  // pass 0 as part-number
     else {  // multipart: cycle through each part
         foreach ($s->parts as $partno0=>$p)
             getpart($mbox,$mid,$p,$partno0+1);
     }
 }
 


 
 //script will fetch an email identified by $msgid, and parse the its parts into an
 //array $partsarray
 //structure of array:
 //$partsarray[<name of part>][<attachment/text>]
 //if attachment- subarray is [filename][binary data]
 //if text- subarray is [type of text(HTML/PLAIN)][text string]
 
//i.e.
 //$partsarray[3.1][attachment][filename]=filename of attachment in part 3.1
 //$partsarray[3.1][attachment][binary]=binary data of attachment in part 3.1
 //$partsarray[2][text][type]=type of text in part 2
 //$partsarray[2][text][string]=decoded text string in part 2
 //$partsarray[not multipart][text][string]=decoded text string in message that isn't multipart
 
function parsepart($p,$i){
     global $link,$msgid,$partsarray;
     //where to write file attachments to:
     $filestore = 'c:\\tmp\\]';
 
    //fetch part
     $part=imap_fetchbody($link,$msgid,$i);
     //if type is not text
     if ($p->type!=0){
         //DECODE PART        
        //decode if base64
         if ($p->encoding==3)$part=base64_decode($part);
         //decode if quoted printable
         if ($p->encoding==4)$part=quoted_printable_decode($part);
         //no need to decode binary or 8bit!
         
        //get filename of attachment if present
         $filename='';
         // if there are any dparameters present in this part
         if (count($p->dparameters)>0){
             foreach ($p->dparameters as $dparam){
                 if ((strtoupper($dparam->attribute)=='NAME') ||(strtoupper($dparam->attribute)=='FILENAME')) $filename=$dparam->value;
                 }
             }
         //if no filename found
         if ($filename==''){
             // if there are any parameters present in this part
             if (count($p->parameters)>0){
                 foreach ($p->parameters as $param){
                     if ((strtoupper($param->attribute)=='NAME') ||(strtoupper($param->attribute)=='FILENAME')) $filename=$param->value;
                     }
                 }
             }
         //write to disk and set partsarray variable
         if ($filename!=''){
             $partsarray[$i][attachment] = array('filename'=>$filename,'binary'=>$part);
             $fp=fopen($filestore.$filename,"w+");
             fwrite($fp,$part);
             fclose($fp);
             }
     //end if type!=0        
    }
     
    //if part is text
     else if($p->type==0){
         //decode text
         //if QUOTED-PRINTABLE
         if ($p->encoding==4) $part=quoted_printable_decode($part);
         //if base 64
         if ($p->encoding==3) $part=base64_decode($part);
         
        //OPTIONAL PROCESSING e.g. nl2br for plain text
         //if plain text
 
        if (strtoupper($p->subtype)=='PLAIN')1;
         //if HTML
         else if (strtoupper($p->subtype)=='HTML')1;
         $partsarray[$i][text] = array('type'=>$p->subtype,'string'=>$part);
     }
     
    //if subparts... recurse into function and parse them too!
     if (count($p->parts)>0){
         foreach ($p->parts as $pno=>$parr){
             parsepart($parr,($i.'.'.($pno+1)));            
            }
         }
 return;
 }
 

 
 

function getTxtBody($imap,$uid ) {
        $body = get_part($imap, $uid, "TEXT/PLAIN");
/*    $body = get_part($imap, $uid, "TEXT/HTML");
    // if HTML body is empty, try getting text body
    if ($body == "") {
        $body = get_part($imap, $uid, "TEXT/PLAIN");
    }
 */
        return $body;
}
 
 
 
/*
function getBody($uid, $imap) {
    $body = get_part($imap, $uid, "TEXT/HTML");
    // if HTML body is empty, try getting text body
    if ($body == "") {
        $body = get_part($imap, $uid, "TEXT/PLAIN");
    }
    return $body;
}
*/
function get_part($imap, $uid, $mimetype, $structure = false, $partNumber = false) {
    if (!$structure) {
           $structure = imap_fetchstructure($imap, $uid);
    }
    if ($structure) {
        if ($mimetype == get_mime_type($structure)) {
            if (!$partNumber) {
                $partNumber = 1;
            }
            $text = imap_fetchbody($imap, $uid, $partNumber);
            $encodingint=$structure->encoding;
            switch ($structure->encoding) {
                case 1: return imap_utf8($text);
                case 3: return imap_base64($text);
                case 4: return imap_qprint($text);
                default: return $text;
           }
       }

        // multipart 
        if ($structure->type == 1) {
            foreach ($structure->parts as $index => $subStruct) {
                $prefix = "";
                if ($partNumber) {
                    $prefix = $partNumber . ".";
                }
                $data = get_part($imap, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
                if ($data) {
                    return $data;
                }
            }
        }
    }
    return false;
}

function get_mime_type($structure) {
    $primaryMimetype = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");

    if ($structure->subtype) {
       return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
    }
    return "TEXT/PLAIN";
}



 
 function getpart($mbox,$mid,$p,$partno) {
     // $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple
     global $htmlmsg,$plainmsg,$charset,$attachments;
 
    // DECODE DATA
     $data = ($partno)?
         imap_fetchbody($mbox,$mid,$partno):  // multipart
         imap_body($mbox,$mid);  // simple
     // Any part may be encoded, even plain text messages, so check everything.
     if ($p->encoding==4)
         $data = quoted_printable_decode($data);
     elseif ($p->encoding==3)
         $data = base64_decode($data);
 
    // PARAMETERS
     // get all parameters, like charset, filenames of attachments, etc.
     $params = array();
     if ($p->parameters)
         foreach ($p->parameters as $x)
             $params[strtolower($x->attribute)] = $x->value;
     if ($p->dparameters)
         foreach ($p->dparameters as $x)
             $params[strtolower($x->attribute)] = $x->value;
 
    // ATTACHMENT
     // Any part with a filename is an attachment,
     // so an attached text file (type 0) is not mistaken as the message.
     if ($params['filename'] || $params['name']) {
         // filename may be given as 'Filename' or 'Name' or both
         $filename = ($params['filename'])? $params['filename'] : $params['name'];
         // filename may be encoded, so see imap_mime_header_decode()
         $attachments[$filename] = $data;  // this is a problem if two files have same name
     }
 
    // TEXT
     if ($p->type==0 && $data) {
         // Messages may be split in different parts because of inline attachments,
         // so append parts together with blank row.
         if (strtolower($p->subtype)=='plain')
             $plainmsg.= trim($data) ."\n\n";
         else
             $htmlmsg.= $data ."<br><br>";
         $charset = $params['charset'];  // assume all parts are same charset
     }
 
    // EMBEDDED MESSAGE
     // Many bounce notifications embed the original message as type 2,
     // but AOL uses type 1 (multipart), which is not handled here.
     // There are no PHP functions to parse embedded messages,
     // so this just appends the raw source to the main message.
     elseif ($p->type==2 && $data) {
         $plainmsg.= $data."\n\n";
     }
 
    // SUBPART RECURSION
     if ($p->parts) {
         foreach ($p->parts as $partno0=>$p2)
             getpart($mbox,$mid,$p2,$partno.'.'.($partno0+1));  // 1.2, 1.2.1, etc.
     }
 }
 
class CContactExt extends CContact
{
   
    
    public function getIDfromUserName($userName)
    {
        if ($userName!="") {
			$q = new w2p_Database_Query();
		$q->addTable('users');
		$q->addQuery('user_id,  user_username');
		$q->addWhere('user_username LIKE "'.$userName.'%"');
                $res=$q->loadHash('user_id');
				}
				else $res=NULL;
		return $res;
        
    }
    
    public function getEmailFromUserID($user_id)
    {
        $q = new w2p_Database_Query();
		$q->addTable('contacts','con');
		$q->addQuery('con.contact_email');
                $q->addJoin('users','us','us.user_contact=con.contact_id');
		$q->addWhere('us.user_id="'.$user_id.'"');
                $res=$q->loadHash('con.contact_email');
		return $res;
        
    }
    
}
class CProjectExt extends CProject
{
    public function getIDfromShortName($shortName)
    {

       if ($shortName!="") {
        
        $q = new w2p_Database_Query();
		$q->addTable('projects');
		$q->addQuery('project_id,  project_name, project_short_name');
		$q->addWhere('project_short_name="'.$shortName.'"');
                $res=$q->loadList();
				}
				else $res=NULL;
		return $res;
        
       
    }
}

class CTaskimporter extends w2p_Core_BaseObject
{

	public $taskimporter_id = 0;

        public $_tbl = 'taskimporter';
	public $_tbl_key = 'taskimporter_id';
	//TODO: support table prefixes

	public function __construct()
	{
		parent::__construct('taskimporter', 'taskimporter_id');
	}


	public function check()
	{
        $errorArray = array();
        $baseErrorMsg = get_class($this) . '::store-check failed - ';

        return $errorArray;
	}
        
        
    public function getMaxEmailID()
    {
      $q = new w2p_Database_Query();
                $q->clear();
                $q->addTable('taskimporter');
                $q->addQuery('max_email_id');
                $q->addWhere("taskimporter_id=1");
               $res=$q->loadHash('max_email_id');
               $q->clear();
                
                return $res[max_email_id];
        return (int)0;
    }
    
    public function setMaxEmailID($id)
    {
		$id1=(int)$id;
       $q = new w2p_Database_Query();
                $q->clear();
                $q->addTable('taskimporter');
                $q->addUpdate('max_email_id',"$id1");
                $q->addWhere("taskimporter_id=1");
		$res=$q->exec();
                $q->clear();
                
                return $res;
        
    }
    
  
 
    public function addTaskFromEmail($AppUI,$mailbox, $mail_id, $perms=NULL)
    {
		global $Importeruserid;
        $proceed=true;
        $fff=$AppUI->getPref('MAILALL');
/*
         $mess='';
         $partsarray=array();
     global $charset,$htmlmsg,$plainmsg,$attachments;
     global $link,$msgid,$partsarray;
     $link=$mailbox;
     $msgid=$mail_id;
 $s=imap_fetchstructure($mailbox,$mail_id);
 $ddd=$s->parts[0]->value;
 $ddd=$s->parts[0]->parts[0]->value;
 $sss=  imap_bodystruct($mailbox, $mail_id, 1.1);
 $cp='parts: '+count($s->parts);
//print_r($s->parts[0]);

    $message = imap_fetchbody($mailbox,$mail_id,'0.1');
    $message = imap_8bit($message);//utf8_encode(quoted_printable_decode($message));
 // print_r($message);
 //   print_r($message);
  if ($message == "") { // no attachments is the usual cause of this        
        $message = imap_fetchbody($mailbox, $mail_id, 1);     
    $message = quoted_printable_decode($message);
  //print($message);
    }
*/     
     $textbody=getTxtBody($mailbox,$mail_id);
 /*
     $drr1=substr($textbody,1,7);
             $irr=strlen($textbody);
             $iind=strpos($textbody, '<endtask>')+9;

*/             
             $nbody=explode("\n",$textbody);
/*
     $rrrrr=  quoted_printable_decode($txtbody);
     mb_detect_encoding($str);

     $rrrr=mb_detect_encoding ($textbody);
      $ttt=  imap_base64($textbody);
    $ttt=mb_convert_encoding($ttt, "ASCII");
     $ttt=  imap_base64($textbody);
$vvv="wer";
     $rrrr=mb_detect_encoding ($ttt);
//     getmsg($mailbox,$mail_id);
//source: comments to imap_fetchstructure at php.net
//fetch structure of message

 /*
//see if there are any parts
 if (count($s->parts)>0){
 foreach ($s->parts as $partno=>$partarr){
     //parse parts of email
     parsepart($partarr,$partno+1);
     }
 }
 
//for not multipart messages
 else{
     //get body of message
     $text=imap_body($link,$msgid);
     //decode if quoted-printable
     if ($s->encoding==4) $text=quoted_printable_decode($text);
     //OPTIONAL PROCESSING
     if (strtoupper($s->subtype)=='PLAIN') $text=$text;
     if (strtoupper($s->subtype)=='HTML') $text=$text;
     
    $partsarray['not multipart'][text]=array('type'=>$s->subtype,'string'=>$text);
 }
      
 */    
/*
 *      $mheader=imap_header($mailbox,$mail_id);
$mbody=imap_body($mailbox,$mail_id);
$mFrom=$mheader->from[0]->mailbox.'@'.$mheader->from[0]->host;
//$nbody=explode("\n",$mbody);
//$nbody=explode("\n",$partsarray['1.1']['text']['string']);
//if (count($nbody)<2)  $nbody=explode("\n",$partsarray['1.1']['html']['string']);
 */

      $mheader=imap_header($mailbox,$mail_id);
      $mFrom=$mheader->from[0]->mailbox.'@'.$mheader->from[0]->host;

//identify allowed emails:  userid must be ok
//$AppUI = new w2p_Core_CAppUI();
	if (!isset($perms))
        {
            $perms = &$GLOBALS['AppUI']->acl();
        }
        $perm='add';
        $mod='tasks';
        $proj=new CProjectExt;
        $user=new CContactExt;

if (stripos($nbody[0],"<userid>") !==FALSE)
{
    $userid= (int) $nbody[1];
$AppUI->user_id=(int)$Importeruserid;
           $perms = $AppUI->acl();
 	$proceed = $perms->checkModule($mod, $perm,$userid);
if ($proceed)
{
		$AppUI->loadPrefs($userid);	
        $w2p_emadr=$user->getEmailFromUserID($userid);
        
        if (strcasecmp($w2p_emadr[contact_email],$mFrom)<>0) $proceed=false; ;
    $project_short_name=mysql_real_escape_string (strip_tags(rtrim($nbody[3])));
    $projids=$proj->getIDfromShortName($project_short_name);
    $iLen=count($projids);
    if ( ($iLen<2)&&($iLen>0)) //short name is existing and unique
    {
        $perm='access';
        $mod='projects';
        $proceed =$proceed&& $perms->checkModule($mod, $perm,$userid);

          $nTask= new CTask;
          $nTask->task_owner= (int) $userid;
		  $nTask->task_creator= (int) $Importeruserid;
          $nTask->task_notify=1;
        $nTask->task_project=(int)$projids[0]['project_id'];
        
    $nTask->task_name=mysql_real_escape_string (strip_tags(rtrim($nbody[5])));
    if (strlen($nTask->task_name)<2)
    {
        $proceed=false;
        $mess.='task name too short ';
    }
    $nTask->task_description=mysql_real_escape_string (strip_tags(rtrim($nbody[7])));
    $descr_ex="";
    $nolines= sizeof($nbody);
    $mailisfrwd= ($nolines>18);
    for ($ii=18;$ii<$nolines;$ii++)
    {
        $descr_ex.=$nbody[$ii];
        
    }
    $nTask->task_description.="\n\n". (strip_tags(rtrim($descr_ex)));
//    $nTask->task_description=$nTask->task_description."\n\n";


//get duration before dates as it is needed later
	$nTask->task_duration=(int)rtrim($nbody[13]); 
	if	($nTask->task_duration==0) 	$nTask->task_duration= 1;  //for some reason $nbody[13] is not "" if empty
    $nTask->task_duration_type=1;
	
//get start end dates
    $nsd = new w2p_Utilities_Date(); //Default is today
    $end_date=substr(strip_tags(rtrim($nbody[11])),0,6);//there seem to be other chars in the string
	$start_date=substr(strip_tags(rtrim($nbody[9])),0,6);
	if ((strlen($start_date)===6)&(is_int($start_date)))
   { 
                $nsd->setDay(substr($start_date,0,2));
                $nsd->setMonth(substr($start_date,2,2));
                $nsd->setYear(substr($start_date,4,2));

            // prefer Wed 8:00 over Tue 16:00 as start date
     //       $nsd = $nsd->next_working_day();

            // prepare the creation of the end date
	};
    $ned = new w2p_Utilities_Date($nsd);
           //     $nsd->setDayMonthYear(substr($end_date,0,2),substr($end_date,2,2),substr($end_date,4,2));
		
	if (strlen($end_date)===6) {
		
	
                $ned->setDay(substr($end_date,0,2));
                $ned->setMonth(substr($end_date,2,2));
                $ned->setYear(substr($end_date,4,2));
}
else {
	$ned->addDuration($nTask->task_duration,$nTask->task_duration_type);
	
};

    $nTask->task_start_date = $nsd->format(FMT_DATETIME_MYSQL);
    $nTask->task_end_date = $ned->format(FMT_DATETIME_MYSQL);
    $nTask->task_start_date = $AppUI->convertToSystemTZ($nTask->task_start_date);
    $nTask->task_end_date = $AppUI->convertToSystemTZ($nTask->task_end_date);
 /**/
	if ($nbody[17]=="")  $nTask->task_priority=0; else 
    		$nTask->task_priority=(int)rtrim($nbody[17]);
    if ($proceed) $succ=$nTask->store(); else $succ=false;
if ($succ) {
    if (canAdd('files')) {
            $head=  imap_fetchheader($mailbox, $mail_id);
            $body= imap_body($mailbox, $mail_id);
            $fulltext=$head.$body;
 //           $upload['tmp_name']= "c:\\tmp\\ttt.eml" ;
/*
            $tt=fopen("c:\\tmp\\ttt.eml", "w");
            fwrite($tt, $fulltext);
            fclose($tt);
*/
            $obj = new CFile();
            $obj->_message = 'added';
            $obj->file_project=$nTask->task_project;
            $obj->file_task=$nTask->task_id;
            if ($mailisfrwd)
                $obj->file_description="Forwarded Email and source for task"; else
                   $obj->file_description="Email source for task"; 
  //          $obj->file_folder=;
            $obj->file_id=0;
            $obj->file_owner=$userid;
            $obj->file_version=1;
            $obj->file_parent=0;
            $obj->file_type="application/octet-stream";
            
            $obj->file_category = 1;
            $obj->file_name ="task source email.eml";
	//	$obj->file_type = 0;
	//$obj->file_size = 0;
		$obj->file_real_filename = uniqid(rand());
  //              $succ2=$obj->store();
//                $obj->moveTemp($upload);
		if (!is_dir(W2P_BASE_DIR . '/files')) {
			$res = mkdir(W2P_BASE_DIR . '/files', 0777);
			if (!$res) {
			//	return false;
			}
		}
		if (!is_dir(W2P_BASE_DIR . '/files/' . $obj->file_project)) {
			$res = mkdir(W2P_BASE_DIR . '/files/' . $obj->file_project, 0777);
			if (!$res) {
				$this->_AppUI->setMsg('Upload folder not setup to accept uploads - change permission on files/ directory.', UI_MSG_ALLERT);
		//		return false;
			}
		}

	//	$obj->_
                        $filepath = W2P_BASE_DIR . '/files/' . $obj->file_project . '/' . $obj->file_real_filename;
		// save it
 //           $upload['tmp_name']= "c:\\tmp\\ttt.eml" ;
            $tt=fopen("$filepath", "w");
            fwrite($tt, $fulltext);
            fclose($tt);
            $fillen=filesize("$filepath");
            $mailstrlen=strlen($fulltext);
            $obj->file_size=filesize("$filepath");
                $succ2=$obj->store();

    }


}
    if (($nolines >=18) &&(canAdd('files')))  
        {
    $objF = new CFile();

    }
 //   $assigned_users
    $assigned_users=strip_tags(rtrim($nbody[15]));
    if (strlen($assigned_users)>1) {
		$usernames=explode(" ",$assigned_users);
//    $users=explode(",",$assigned_users);
     foreach ($usernames as $userName)
    {
         $res1=$user->getIDfromUserName(mysql_real_escape_string ($userName));
         if (is_array($res1)) $users[]=$res1['user_id']; else 
         {
             $proceed=false;
             $mess.='task may be inserted, but cannot add assignees ';
         }
         
    }
	}
	else $users[]=$userid;  //use $userid as default if no assignees have been given
    $assigned_users=implode($users,",");

     foreach ($users as $usern)
    {
         $perc_assigned[$usern]=100;

 //      $assigned_users= implode($ass_userids,",");
 // $nTask->updateAssigned($assigned_users, 100, false);
    }
    if ($proceed && $succ) 
    {
        $nTask->updateAssigned($assigned_users, $perc_assigned, false);
        $nTask->notify();
    }
    else
    {
        $proceed=false;
    }
        
    } else $proceed=false;
      
// notify assigned users!!
}



}
else 
{
    $proceed=false;
    
}
if ($proceed==false)
{
        $mail = new w2p_Utilities_Mail();
        $mail->Body($mess.$mbody);
        $mail->To($mFrom);

        $mail->Subject("Cannot add task to web2project");//, $locale_char_set);
        $succ=$mail->Send();
/*
        $mail = new w2p_Utilities_Mail();
        $mail->Body($mbody);
        $mail->To($mFrom);

        $mail->Subject("Cannot add task");//, $locale_char_set);
        $succ=$mail->Send();
*/        
}
return ($proceed&&$succ);
        
    }
    
    public function processEmailsToTasks($AppUI)
    {
		global $emailserver, $userid, $passwd;
//		        $this->setMaxEmailID(22);
         $first_id=$this->getMaxEmailID()+1;
//        $first_id=1;//(int)$this->getMaxEmailID()+1;
        $fff=$AppUI->getPref('MAILALL');
  //      $mailbox=new fMailbox('pop3', 'mailserver', "userid", "password");
 //       $mailbox->connect();
 //       $messages=   array();
   //     $messages = $mailbox->listMessages();        
 //       $drr=$mailbox->fetchMessage($first_id);
 //       foreach ($messages as $uid => $message) {
 //           if ($uid>=$first_id) {
     //           $this->addTaskFromEmail ($AppUI, $mailbox, $uid, $perms);
 //               $last=$uid;
 //           }
 //        $mailbox->close();
 //           $rrrrr=$index;
 //           $ffff=$subStruct;
        
  //      }
 
       // print_r($drr);
//        $rtr=$drr['text'];
 //       $drr1=substr($rtr,1,7);
 //       var_dump($rtr);
		
        $link=$success=$mailbox=imap_open($emailserver,$userid,$passwd);
        $str = imap_errors();  
        if (strlen($str) > 0) echo("imap_errors():$str\n");  
		
		if ($link)  {
			
		
        $last=$first_id;//(int)imap_num_msg($mailbox);
        $perms = &$GLOBALS['AppUI']->acl();

        $MC = imap_check($mailbox); //recalc no of msg

// Fetch an overview for all messages in INBOX
        $result = imap_fetch_overview($mailbox,"1:{$MC->Nmsgs}",0);
        foreach ($result as $overview) {
            if ($overview->msgno >=$first_id)
            {
                $last=$overview->msgno;
                    $this->addTaskFromEmail ($AppUI, $mailbox,$last , $perms);
		        $this->setMaxEmailID($last);
            }
        }
//}
 /*       
        for ($ii=$first_id;$ii<=$last;$ii++) {
            $this->addTaskFromEmail ($AppUI, $mailbox, $ii, $perms);
        }
*/
        imap_close($mailbox);

 //       $this->setMaxEmailID($last);
 }
        return 0;
        
    }
 

 public function hook_cron()
 {
 	global $AppUI;
 	$this->processEmailsToTasks($AppUI);
 }      
        
    	public function combineStringBeginEnd( $str, $iChars=6 )
        {
            //take iChars characters from begin and from end of string
            $URL=substr($str,0,$iChars);
            if (strlen($str)>2*$iChars) 
            {
                $URL=$URL."..";
                $URL=$URL.substr($str,-$iChars,$iChars);
            }

            return $URL;
        }
        
          
        
        
        public function delete(w2p_Core_CAppUI $AppUI)
	{
		$this->load();
		return $this->store($AppUI);
	}

	public function store(w2p_Core_CAppUI $AppUI)
	{
        $perms = $AppUI->acl();
        $stored = false;

        $errorMsgArray = $this->check();
        if (count($errorMsgArray) > 0) {
          return $errorMsgArray;
        }
        $q = new w2p_Database_Query;
		$this->w2PTrimAll();


        if ($this->dokuwiki_id && $perms->checkModuleItem('taskimporter', 'edit', $this->taskimporter_id)) {
            if (($msg = parent::store())) {
                return $msg;
            }
            $stored = true;
        }
        if (0 == $this->dokuwiki_id && $perms->checkModuleItem('taskimporter', 'add')) {
            if (($msg = parent::store())) {
                return $msg;
            }
            $stored = true;
        }
        return $stored;
	}
/*

	public function hook_calendar($userId) {
		return $this->getOpenTodoItems($userId);
	}

    public function hook_search() {
        $search['table'] = 'todos';
        $search['table_alias'] = 't';
        $search['table_module'] = 'todos';
        $search['table_key'] = $search['table_alias'].'.todo_id'; // primary key in searched table
        $search['table_link'] = 'index.php?m=todos&todo_id='; // first part of link
        $search['table_title'] = 'Todos';
        $search['table_orderby'] = 'todo_title';
        $search['search_fields'] = array('todo_title');
        $search['display_fields'] = $search['search_fields'];

        return $search;
    }

*/    
    
    
}
