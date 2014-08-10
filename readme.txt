Taskimporter v1.0.0
(c) Klaus Buecher (opto)
   
The Taskimporter module allows to import tasks into web2project projects from emails.
It can process plain emails, but also forwarded emails, i.e. tasks generated from email communications with others.


The first few lines of plain or forwarded emails must contain some info on user, project, task name, see below for the appropriate format of this info.

The body text of the forwarded email will be added to the task description.
The full forwarded email will be added as file attachment in eml format to the task. This file attachment will also contain all attachments that were part of the original email.

Attachments to the plain email (if not forwarded) are not yet directly added as files to the task. But these emails are also attached as file to the task, which makes their attachments available in the eml file. 




=====================================
LICENSE

The Taskimporter module was built by Klaus Buecher and is released here
under modified BSD license (see GNU.org), except for those parts explcitly under a different license by their original authors.

Copyright (c) 2012/2014 Klaus Buecher (Opto)

NO WARRANTY- whatsoever - is given. Use at your own risk.
Test with a demo database first - it opens your database to contributions and entries by automated email. 

KNOWN/PREVIOUS ISSUES

=====================================

Open Issues:

* task description can only be 1 line long. But any text below the required part will be added to the description.
* only part of error handling implemented yet;
  no check of date


=====================================
INSTALLATION
The PHP imap extension must be activated.


0.  Previous installations of this module can simply be overwritten.

1.  To install this module, please follow the standard module installation
procedure.  Download the latest version and unzip this directory into your
web2project/modules directory.

2.  Select to System Admin -> View Modules and you should see "Taskimporter" near
the bottom of the list.

3.  On the "Taskimporter" row, select "install".  The screen should
refresh.  Now select "hidden" and then "disabled" from the same row to make it
display in your module navigation.

4. (!!) Configure the email adress and credentials: open taskimporter.class.php, 
At the top (currently around line 35), you will find the email credentials. Please add for your installation. More details, if necessary are in php manual for imap_open() function.
Also, add the userid which will be creating the new tasks. It might be advisable to create a special user for this. Then, email added tasks can easily be found in the database by filtering for this user.
IMAP and POP3 accounts are supported.
Add correct email adress/host/SSL etc. 

 $emailserver="mailserver:110/pop3/notls}"; //for pop3; also imap is possible, see php manual.
 //for some php versions, it seems /notls is needed if no security
 $userid="username";
 $passwd="password";
 $Importeruserid=(int) 1; //or whatever your userid that is chosen
   
You can also put this information into a file called serverfilename.txt. Then, your credentials are upgrade-safe, this file will not be overwritten by upgrades.
file content:
<?php /* $Id$ $URL$ */
 $emailserver="mailserver:110/pop3/notls}"; //for pop3; also imap is possible, see php manual.
 //for some php versions, it seems /notls is needed if no security
 $userid="username";
 $passwd="password";
 $Importeruserid=(int) 1; //or whatever your userid that is chosen
?>   
   
If in doubt, consult PHP documentation of imapmail. Even if no SSL/TLS,
 it seems noTLS needs to be explicitly added for some PHP versions.





=====================================
USAGE

1.  The mailbox is scanned whenever you call queuescanner.php in the top directory. Please set up a cron job or automatic task to do this at regular intervals. See w2p wiki on github (access via www.web2project.net -> documentation

You can also click on the menu item taskimporter.

Starting at the first line, the email body must contain the following specific text (see below).
In Thunderbird, templates can be set up for various project/assigned user combinations, or the clippings addon.
Then, adding a new task is just a doubleclick on the template, add task name, maybe description and dates, send and all is done.

Also, emails from inbox or folders can be forwarded as task if the text given below is added at the top.

Subject of email: can help distinguishing different templates in Thunderbird
Body of email: first line follows below, the lines with <text> must be in exactly that row in the email.
I have inserted some sample text to show usage
<userid, required>
7
<project short name, must be unique, required, no whitespace allowed>
todonow
<task name, required>
this is the task name
<task description, 1 line ONLY>
please do today
<start date DDMMYY  - not dot/comma or slash>
011113
<end date  DDMMYY>
111113
<duration hours>
7
<assigned user names:   user1 user2 - can be abbreviated if unique>
admin guest
<task priority: -1 0 1>
1

*userid is the userid of task owner, must be valid within w2p and emailaddress of userid must correspond to from-address of email
* task creator is set to the importeruserid, thus, imported emails tasks can easily be found in the db if a special user is created
*the project short name must be unique over all projects in the database,
otherwise the task is not added (must be without white space in name)
*date format: 211012 is 21st of October, 2012. Could easily be changed in code to be MMDDYY.
*assigned user names: must be user names as defined in web2project. 
Can be abbreviated if still unique:  adm instead of administrator
Not tested what happens if you have an admin and an admin2, for example 
(meaning one username being substring of another)

Userid, short project name (no whitespace allowed in name) and taskname are required inputs. The other entries will be filled with defaults if no input is provided (see below).

start date, duration, end date: default is today, duration 1 hour and end date = start date + duration, if no specific end date is given
Priority: default is 0 (normal)
assigned user: default is user id, if no explicit input. User names can be abbreviated if unique

Unrecognisable emails are forwarded to web2project administrator email address 
and to the sending email address.

PERMISSIONS:
The access permission to taskimporter is checked, also task add permission for <userid>.
The sending email address (FROM) is verified against the email address stored in web2project for <userid>. If not identical, the task will be not be added.

Parsing errors:
The task is not added if various lines cannot be parsed.
conditions for adding task:
		    task name > 2 characters, assigned user in db, permissions of userid for adding task, 
            for accessing the project
            sending email address identical to w2p user's email for userid

All tasks are notified to the assigned users by email, if web2project email handling is set up..

Unrecognisable emails are forwarded to web2project administrator email address 
and to the sending email address.

Safety: use at your own risk, it opens your database up to contributions by email.
I tested for  some very simple Mysql injection (adding tables) and the injection was ignored, as expected. Still, no guarantee is given.
 
All strings are processed using strip_tags().


