<h2>Statistics for <?=$_POST['domain'] ?: 'all domains'?></h2>
<?php

$conf = parse_ini_file("dmarc.conf", true);
require_once('function.php');

/* Register POST valiable */
$dom = $_POST['domain'];
$policy = $_POST['policy'];

/* And now define the query */
$WHERE_POL = NULL;
$WHERE_DOM = NULL;
$LIMIT = NULL;
if ($dom) $WHERE_DOM = "`name` = '$dom' AND ";
if ($policy!='ALL') $WHERE_POL = "`policy` = $policy AND ";
if ($_POST['limit'] != 0) $LIMIT = 'LIMIT '.$_POST['limit'];

$query_dom = "SELECT `from_domain`, `name`, COUNT(`policy`) AS countp, `policy` FROM `messages` INNER JOIN domains ON domains.id=messages.`from_domain` WHERE $WHERE_DOM $WHERE_POL `date` >= DATE_SUB(NOW(), INTERVAL ".$conf['db']['INTERVAL'].") GROUP BY name,policy ORDER by countp DESC $LIMIT";


openlog($conf['syslog']['tag'], LOG_PID, $conf['syslog']['fac']);
$user = username();

$mysqli = mysql_conn ($conf['db']['HOST'], $conf['db']['USER'], $conf['db']['PASS'], $conf['db']['NAME'], $conf['db']['PORT'],$user);
report($mysqli,$query_dom,$LIMIT,'DMARC Domains from messages of last '.$conf['db']['INTERVAL'].' until now.');
$mysqli->close();
closelog();
?>
<div ID="Result"></div>
<p>Keep in mind that the <i>policy</i> is not the published policy, but the <b>enforced</b> policy.</p>
