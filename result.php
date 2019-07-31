<?php


/* The domain ID linking all tables */
$domain = base64_decode(array_values(array_keys($_POST))[0], TRUE);
$id = array_values($_POST)[0];

$conf = parse_ini_file("opendmarc-report-analyzer.conf", true);


/* Query */

/* Distinct ips count by policy */
$sql_countip = 'SELECT COUNT(DISTINCT `ip`) AS countip,policy FROM `messages` WHERE (`from_domain` = '.$id.' AND `date` >= DATE_SUB(NOW(), INTERVAL '.$conf['db']['INTERVAL'].')) GROUP BY `policy`';

/* Details on ips by policy */
$sql_ip = 'SELECT `addr`, COUNT(ip) as countip,policy FROM `messages` INNER JOIN ipaddr ON ipaddr.id=messages.ip WHERE (`from_domain` = '.$id.' AND `date` >= DATE_SUB(NOW(), INTERVAL '.$conf['db']['INTERVAL'].') AND `policy` = '.$_POST['policy'].') GROUP BY `ip`,`policy` ORDER BY `countip` DESC '.$_POST["limit"];


/* Stats by domain */
$sql_F = "SELECT count(domains.name) as countdom,domains.name AS `Envelope Domain`,spf,signatures.domain AS sigdomain,signatures.pass as dkim,signatures.error AS `sign error`,align_dkim,align_spf,policy FROM `messages` JOIN domains ON domains.id = messages.env_domain LEFT OUTER JOIN signatures on signatures.message=messages.id WHERE `from_domain` = $id AND `policy` = ".$_POST['policy'].' AND `date` >= DATE_SUB(NOW(), INTERVAL '.$conf['db']['INTERVAL'].') GROUP by domains.name,policy,spf,sigdomain,dkim,align_dkim,align_spf ORDER BY `countdom` DESC, policy DESC, name '.$_POST["limit"];

require_once('function.php');

openlog($conf['syslog']['tag'], LOG_PID, $conf['syslog']['fac']);
$user = username();

$mysqli = mysql_conn ($conf['db']['HOST'], $conf['db']['USER'], $conf['db']['PASS'], $conf['db']['NAME'], $conf['db']['PORT'],$user);
print "<h3>Detailed Statistics for $domain</h3>";
print "<h4>Statistics for RFC5322.From domain</h4>";
to_table($mysqli, $sql_F, 'Alignment for RFC5322.From domain '.$domain);
print '<h4>Statistics for IP addresses</h4>';
to_table($mysqli, $sql_countip,'Distinct ips grouped by ALL policy types');
to_table($mysqli, $sql_ip, 'All ips and their policy');
$mysqli->close();
closelog();
?>
