<?php

include "LDAP.php";
//include "login.php";

$r = new LdapSearch;

function msg($prefix, $r) {
	echo $prefix." ".$r->errmessage."\n";
}

if(!$r->connect()){
	msg("could not connect", $r);
	return;
}
else{
	msg("connection works", $r);
	//return;
}

if (!$r->bind($argv[1], $argv[2])) {
	msg("could not bind", $r);
	return;
}
else{
	msg("Binding works", $r);
	return;
}




?>