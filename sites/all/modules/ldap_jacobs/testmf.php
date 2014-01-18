<?php

include "LDAP.php";
//include "login.php";

$r = new LdapSearch;

function msg($prefix, $r) {
	echo $prefix." ".$r->errmessage."\n";
}

$r->connect();

if (!$r->bind($argv[1], $argv[2])) {
	msg("could not bind", $r);
	return;
}




?>