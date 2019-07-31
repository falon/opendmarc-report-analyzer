<?php


$conf = parse_ini_file("opendmarc-report-analyzer.conf", true);
$queue_id = $_POST['queue_id'] ?: '';
if ( $queue_id == '') exit ("<p>Null queue ID</p>");

/* Query */

/* Stats by mail */
$sql_msg = 'SELECT `date`, `reporter`, ipaddr.addr as IP,`env_domain` as `Env Domain` ,`from_domain` AS `RFC5322 From Dom`,`spf`, signatures.pass as dkim, signatures.domain as sigdomain,signatures.error AS `sign error`,align_dkim,align_spf,policy FROM messages JOIN domains ON domains.id = messages.from_domain JOIN ipaddr ON ipaddr.id=messages.ip LEFT OUTER JOIN signatures on signatures.message=messages.id WHERE `jobid` = '."'$queue_id'".' AND `date` >= DATE_SUB(NOW(), INTERVAL '.$conf['db']['INTERVAL'].')';

require_once('function.php');

openlog($conf['syslog']['tag'], LOG_PID, $conf['syslog']['fac']);
$user = username();

$mysqli = mysql_conn ($conf['db']['HOST'], $conf['db']['USER'], $conf['db']['PASS'], $conf['db']['NAME'], $conf['db']['PORT'],$user);
print "<h2>Report for $queue_id message</h2>";
to_table($mysqli, $sql_msg, "Report for RFC5322.From domain");
$mysqli->close();
closelog();
?>
