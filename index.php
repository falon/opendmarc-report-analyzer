<head>
<meta http-equiv="Content-Language" content="en">
<title>DMARC-IN statistics</title>
<meta charset="UTF-8">
<link rel="icon" href="https://dmarc.org/favicon.ico" />
<link rel="stylesheet" type="text/css" href="/include/style.css"> 
<!-- https://getflywheel.com/layout/add-sticky-back-top-button-website/ -->
<link rel="stylesheet" id="font-awesome-css" href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" type="text/css" media="screen">
<script  src="/include/ajaxsbmt.js" type="text/javascript"></script>
<!--Load the Ajax API-->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<?php
/* Stats by policy */
$conf = parse_ini_file("dmarc.conf", true);
require_once('function.php');
$user = username();
$sql_F = 'SELECT policy, count(domains.name) as countdom FROM messages JOIN domains ON domains.id = messages.from_domain WHERE `date` >= DATE_SUB(NOW(), INTERVAL '.$conf['db']['INTERVAL'].') GROUP BY policy ORDER BY countdom DESC';
$sql_countip = 'SELECT policy, COUNT(DISTINCT `ip`) AS countip FROM `messages` WHERE (`date` >= DATE_SUB(NOW(), INTERVAL '.$conf['db']['INTERVAL'].')) GROUP BY `policy` ORDER BY countip DESC';
$mysqli = mysql_conn ($conf['db']['HOST'], $conf['db']['USER'], $conf['db']['PASS'], $conf['db']['NAME'], $conf['db']['PORT'],$user);
/* Cache all accesses to tables with the name "new%" in schema/database "db_example" for 500 second */
/* if (!mysqlnd_qc_set_cache_condition(MYSQLND_QC_CONDITION_META_SCHEMA_PATTERN, $conf['db']['NAME'].'%', 500)) {
  die("Failed to set cache condition on Mysql!");
} */
$jsonTableMail = to_json($mysqli, $sql_F);
$jsonTableIP = to_json($mysqli, $sql_countip);
$mysqli->close();
?>
<script type="text/javascript">

        // Load the Visualization API and the piechart package.
        google.load('visualization', '1', {'packages':['corechart']});

        // Set a callback to run when the Google Visualization API is loaded.
        google.setOnLoadCallback(drawMailChart);
        google.setOnLoadCallback(drawIPChart);

        function drawMailChart() {

        // Create our data table out of JSON data loaded from server.
        var data = new google.visualization.DataTable(<?=$jsonTableMail?>);
        var options = {
           title: ' Count mail by DMARC policy ',
          is3D: 'true',
          width: 800,
          height: 600
        };
        // Instantiate and draw our chart, passing in some options.
        // Do not forget to check your div ID
        var chart = new google.visualization.PieChart(document.getElementById('chartMail_div'));
        chart.draw(data, options);
	}

        function drawIPChart() {
        var data = new google.visualization.DataTable(<?=$jsonTableIP?>);
        var options = {
           title: ' Count IPs by DMARC policy ',
          is3D: 'true',
          width: 800,
          height: 600
        };
        // Instantiate and draw our chart, passing in some options.
        // Do not forget to check your div ID
        var chart = new google.visualization.PieChart(document.getElementById('chartIP_div'));
        chart.draw(data, options);
        }
</script>
<style>
td.selectable:hover { background: yellow }
td.selectable:active { background: orangered }
td.selectable:visited { background: orange }

.back-to-top {
background: none;
margin: 0;
position: fixed;
bottom: 0;
right: 0;
width: 50px;
height: 50px;
z-index: 100;
display: none;
text-decoration: none;
color: #ffffff;
background-color: lightgray;
}
 
.back-to-top i {
  font-size: 60px;
}
</style>
<base target="_self">
</head>
<body>
<h1 style="margin: 0px">Inbound DMARC statistics</h1>
<!--this is the div that will hold the pie chart-->
<div style="float: left; width: 50%; margin:0; overflow: hidden" id="chartMail_div"></div>
<div style="float: right; width: 50%; margin:0; overflow: hidden" id="chartIP_div"></div>
<form method="POST" style="margin:0" name="QueryDef" action="list.php" onSubmit="xmlhttpPost('list.php', 'QueryDef', 'List', '<img src=\'/include/pleasewait.gif\'>', true); return false;">
<table style="float:left">
<thead>
<caption>DMARC Query</caption>
<tr><th></th><th></th></tr></thead>
<tr>
<td><label>Domain</label><input maxlength="255" value="" type="text" name="domain" placeholder="RFC5322.From domain"></td>
<td><label>Policy</label><select name="policy"> 
<option value="14">Unknow</option>
<option value="15">Pass</option>
<option value="16" selected>Reject</option>
<option value="17">Quarantine</option>
<option value="18">None</option> 
<option value="ALL">ALL</option>
</select></td>
</tr><tr>
<td colspan="2"><label>Limit query to first top </label><input type="number" min="0" max="50" size="2" placeholder="n"  name="limit"><label> results.</label></td>
</tr>
<tfoot><tr>
<td colspan="2"><input name="submit" value="Submit" type="submit" class="btn"></td>
</tr></tfoot>
</table>
</form>
<form method="POST" style="margin:0" name="QueryMail" action="message.php" onSubmit="xmlhttpPost('message.php', 'QueryMail', 'List', '<img src=\'/include/pleasewait.gif\'>', true); return false;">
<table style="float:right">
<thead>
<caption>Report for single message</caption>
<tr><th></th><th></th></tr></thead>
<tr>
<td colspan="2"><label>Original Envelope Id </label><input type="text" size="20" placeholder="Original Envelope ID"  name="queue_id"></td>
</tr>
<tfoot><tr>
<td><input name="submit" value="Make Report" type="submit" class="btn"></td>
</tr>
</tfoot>
</table>
</form>
<div id="List" style="clear:left"></div>
<h6>All data taken from OpenDMARC database. Based on your conf, domains outside DMARC could have not been included. <a href="http://www.trusteddomain.org/opendmarc/">OpenDMARC</a> is an open source software by <a href="http://www.trusteddomain.org/">The Trusted Domain Project</a>.</h6>
<a href="#" class="back-to-top" style="display: inline;">
<i class="fa fa-arrow-circle-up"></i>
</a> 
</body>
</html>
