<?php

function username() {
        if (isset ($_SERVER['REMOTE_USER'])) $user = $_SERVER['REMOTE_USER'];
                else if (isset ($_SERVER['USER'])) $user = $_SERVER['USER'];
                        else $user='unknown';
        return $user;
}

function mysql_conn ($dbhost,$dbuser,$dbpass,$dbname,$dbport,$userlog) {
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport);
	if ($mysqli->connect_error) {
	            syslog (LOG_EMERG, $userlog."\t".'Connect Error (' . $mysqli->connect_errno . ') '
	                    . $mysqli->connect_error);
	            exit ($userlog."\t".'Connect Error (' . $mysqli->connect_errno . ') '
	                    . $mysqli->connect_error);
	}
	syslog(LOG_INFO, $userlog."\t".'Successfully mysql connected to ' . $mysqli->host_info) ;
	return $mysqli;
}

function report($mysqli,$sql,$lim,$desc) {
	if ($result = $mysqli->query($sql)) {

		/* fetch associative array */
		if ( $row = $result->fetch_assoc() ) { /* First line */
			print '<table id="rounded-corner" summary="DMARC Domains asking RUA report">';
			printTableRowH($col = array_keys($row),$desc);
			printTableRow($mysqli,$col, $row, $lim);
		}
		else print('<p>No domains found.</p>');
		while ($row = $result->fetch_assoc()) {
			printTableRow($mysqli,$col, $row,$lim);
		}
		print '</table>';
		print '<p>Click on domain name to see more detailed statistics.</p>';

		/* free result set */
	        $result->free();
	}
	else print("<p>Error in query:<br><pre>$sql</pre>.</p>");
}


function to_json($myconn, $sql) {
	$rows = array();
        if ($result = $myconn->query($sql)) {

		$keys = NULL;
                /* fetch associative array */
		while ($r = $result->fetch_assoc()) {
			if (is_null($keys)) {
	                        $keys = array_keys($r);
	                        if ( count($keys) != 2 ) {
	                                syslog (LOG_EMERG, 'Database integrity compromised on domain '.$r['name']);
	                                return 'ERR'; /* Database integrity compromised */
	                        }
	                        $table['cols'] = array(
                                        array('label' => $keys[0], 'type' => 'string'),
	                                array('label' => $keys[1], 'type' => 'number')
	                        );
			}

			$temp = array();
			// the following line will be used to slice the Pie chart
			foreach ($keys as $key) 
				switch ( $key )  {
					case 'policy':
						$temp[] = array('v' => (string) human($myconn,$key,$r["$key"]));
						break;
					default:
						$temp[] = array('v' => (int) $r["$key"]);
				}
			
			$rows[] = array('c' => $temp);
		}
 
		$result->free();
		$table['rows'] = $rows;
		return json_encode($table);
	}
	return NULL;
}

function to_table($myconn, $sql,$desc) {
        if ($result = $myconn->query($sql)) {
                /* fetch associative array */
                if ( $row = $result->fetch_assoc() ) { /* First line */
                        print '<table id="rounded-corner" summary="DMARC Domains asking RUA report">';
                        printTableRowH($col = array_keys($row),$desc);
                        printTableRow($myconn,$col, $row, NULL);
                }
		else print '<p>No record found.</p>';
                while ($row = $result->fetch_assoc()) {
                        printTableRow($myconn,$col, $row, NULL);
                }
                print '</table>';
	        /* free result set */
	        $result->free();
        }
	else print("<p>Error in query:<br><pre>$sql</pre>.</p>");
}
			

function printTableRowH($headers,$description) {
	print '<thead><tr>';
	$count = count($headers)-1;
	foreach ($headers as $h)
		if ($h != 'from_domain') print "<th scope=\"col\">$h</th>";
		else $count --;
	print '</tr></thead>'."\n";
	print <<<END
        <tfoot>
    	<tr>
        	<td colspan="$count"><em>$description</em></td>
        	<td>&nbsp;</td>
        </tr>
    	</tfoot>
END;
}

function printTableRow($myconn,$k, $r,$lim) {
        print '<tr>';
        foreach ($k as $keyvalue)
		switch ( $keyvalue ) {
			case 'name':
				$domencoded = base64_encode($r["$keyvalue"]);
				print <<<ENDED
                			<td onClick="xmlhttpPost('result.php', 'Request-$domencoded-$r[policy]', 'Result', '<img src=\'/include/pleasewait.gif\'>', true); return false;" id="$keyvalue" class="selectable" nowrap><form method="POST" name="Request-$domencoded-$r[policy]" action="result.php" onSubmit="xmlhttpPost('result.php', 'Request-$domencoded-$r[policy]', 'Result', '<img src=\'/include/pleasewait.gif\'>', true); return false;">$r[$keyvalue]<input type="hidden" name="$domencoded" size="0" value="$r[from_domain]"><input type="hidden" name="policy" value="$r[policy]"><input type="hidden" name="limit" value="$lim"></form></td>
ENDED;
				break;
			case 'from_domain':
				break;
			default:
				print '<td id="'.$keyvalue.'" nowrap>'.human($myconn,$keyvalue,$r["$keyvalue"]).'</td>';
		}
        print '</tr>'."\n";
}


function human($myconn,$key,$value) {
	switch ($key) {
		case 'policy':
			switch ($value) {
				case 14: return 'Unknown';
				case 15: return 'Pass';
				case 16: return 'Reject';
				case 17: return 'Quarantine';
				case 18: return 'None';
			}
			break;
		case 'align_dkim':
		case 'align_spf' :
			switch ($value) {
                                case 4: return 'Yes';
				case 5: return 'No';
			}
			break;
		case 'spf' :
		case 'dkim':
			switch ($value) {
				case NULL: return 'N/A';
                                case 0: return 'Pass';
				case 2: return 'SoftFail';
				case 3: return 'Neutral';
				case 4: return 'TmpError';
				case 7: return 'Fail';
				case 12: return 'Discard';
				case 6: return 'None';
				case -1: return 'N/A';
			}
			break;
		case 'sigdomain' :
                case 'Env Domain':
		case 'RFC5322 From Dom':
			return id_to_domain($myconn,'domains',$value);
			break;
                case 'reporter':
			return id_to_domain($myconn,'reporters',$value);
			break;

		default: return $value;
	}
}

function id_to_domain($myconn,$table,$val) {
	if ( is_null($val) ) {
                        return 'N/A'; /* No value */
	}

	$sql = "SELECT DISTINCT `name` FROM `$table` WHERE `id`=$val"; 
	if ($result = $myconn->query($sql)) {
		$row = $result->fetch_assoc();
		$num = $result->num_rows;
		/* free result set */
                $result->free();

		if ( $num != 1 ) {
			syslog (LOG_EMERG, 'Database integrity compromised on $table '.$row['name']);
			return 'ERR'; /* Database integrity compromised */
		}
		return $row['name'];
	}
	syslog (LOG_ERR,"Error in query: $sql");
	return 'ERR';
}


?>
