<?php
/**
 * Copy this file to  smtp-config.php  in the folder ABOVE public_html
 * (e.g. /home/<user>/domains/supads.ajcreationz.co/smtp-config.php).
 * That keeps the password out of git and off the web.
 */
return [
    'host'      => 'smtp.hostinger.com',
    'port'      => 465,                          // 465 = SSL, 587 = STARTTLS
    'user'      => 'forms@ajcreationz.co',       // the mailbox that sends
    'pass'      => 'PUT-THE-MAILBOX-PASSWORD-HERE',
    'from'      => 'forms@ajcreationz.co',       // must be the same mailbox
    'from_name' => 'Supads website form',
    'to'        => ['developerajcreationz@gmail.com'], // add more addresses to get copies
];
