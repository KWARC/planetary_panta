<?
//include "/www/ssl/grader_login/ldap/data.php";

define("LDAP_USER","sys-csfaculty");




class LdapSearch
{
	var $host = "jacobs.jacobs-university.de";
	var $ds;
	var $errmessage;
	var $stat_connect = FALSE;
	var $stat_bind = FALSE;		
						
	static function displayName($displayname)
	{
    	/* format:
    	   Stamerjohanns, Heinrich Middlename
    	*/
    	$line = trim($displayname);
    	$arr =  explode(',', $displayname);

    	if ($arr[0] != '') {
            	$lastname = trim($arr[0]);
    	} else {
            	$lastname = "UNKNOWN";
    	}


    	if (!empty($arr[1])) {
            	$line = trim($arr[1]);
            	if (strpos($line, ' ')) {
                    	$arr2 = preg_split('/\s+/', $line, 2);
                    	$firstname = $arr2[0];
                    	$middlename = $arr2[1];
            	} else {
                    	$firstname = $line;
                    	$middlename = '';
            	}
    	} else {
            	$firstname = 'UNKNOWN';
            	$middlename = '';
    	}
       	return array($firstname, $middlename, $lastname);
	}
	
	function CNdisplayName($displayname)
	{
    	/* format:
    	   Stamerjohanns, Heinrich Middlename (1234)
    	*/
    	$line = trim($displayname);

    	$displayname = str_replace('\,', ',', $displayname);

    	// fetch the (xxxx) entry
    	$internid = preg_replace('/(.*\()(\d+)(\).*)/', '\2', $displayname);

    	// remove the CN and the stuff at end 
    	$displayname = preg_replace('/(CN=)(.*)\((\d+)\)/', '\2', $displayname);
    	$arr =  explode(',', $displayname);

    	if ($arr[0] != '') {
            	$lastname = str_replace('CN=', '', trim($arr[0]));
		} else {
    		$lastname = "UNKNOWN";
		}

		if (!empty($arr[1])) {
        	$line = trim($arr[1]);
        	if (strpos($line, ' ')) {
                	$arr2 = preg_split('/\s+/', $line, 2);
                	$firstname = $arr2[0];
                	$middlename = $arr2[1];
        	} else {
                	$firstname = $line;
                	$middlename = '';
        	}
    	} else {
        	$firstname = 'UNKNOWN';
        	$middlename = '';
    	}
    	return array($firstname, $middlename, $lastname, $internid);
	}

	function connect()
	{
		// Connecting to LDAP
		//$this->ds = @ldap_connect($this->host);
		$this->ds = ldap_connect($this->host);
        
        
		if (!$this->ds) {
			$this->errmessage = "Cannot connect to host.";
			return FALSE;
			
		} else {
			$this->stat_connect = TRUE;
			return TRUE;
		}
	}
	
	function bind($username = LDAP_USER, $password = '')
	{
		
		if (!$this->stat_connect) {
			if (!$this->connect())
					return FALSE;
		}
		
		$p = $password;
		if ($username == LDAP_USER && $password == '') {
			$p = str_rot13(LDAP_P);
		} elseif (empty($p)) {
			return FALSE;
		}
		

		$ldaprdn  = $username.'@jacobs.jacobs-university.de'; // ldap rdn or dn

	    
	    // bind to ldap server
		//$ldapbind = @ldap_bind($this->ds, $ldaprdn, $p);
		$ldapbind = ldap_bind($this->ds, $ldaprdn, $p);

		// verify binding
	    if (!$ldapbind) {
			$this->errmessage = "Wrong credentials.";
			return FALSE;
		} else {
			$this->stat_bind = TRUE;
			return TRUE;
		}
	}

	function getByUsername($username)
	{
		if (!$this->stat_bind) {
				$this->errmessage = "Failed to search, bind first\n";
				return FALSE;
		}
		
		// we have logged in successfully
		$ldap_dn = 'ou=users,ou=campusnet,dc=jacobs,dc=jacobs-university,dc=de';

		// displayname: Stamerjohanns, Heinrich
		// mail: h.stamerjohanns@jacobs-university.de
		// employeenumber: is matric_no if student
		// employeeid: is uid if not student
		try {
			$attributes = 
				array(
					'displayname', 
					'mail',
					'employeenumber',
					'employeeid');
				
			$filter = "(&(objectCategory=person)(samaccountName=$username))";

			$result = ldap_search($this->ds, $ldap_dn, $filter, $attributes);

			$entries = ldap_get_entries($this->ds, $result);

			if ($entries["count"] > 0) {
				//echo print_r($entries[0],1)."<br />";
				$displayname = $entries[0]['displayname'][0];
				list($firstname, $middlename, $lastname) = LdapSearch::displayName($displayname);
				$email = $entries[0]['mail'][0];
				if (isset($entries[0]['employeenumber'][0])) {
					$matric_no = $entries[0]['employeenumber'][0];
				} 
				elseif (isset($entries[0]['employeeid'][0])) {
					$matric_no = $entries[0]['employeeid'][0];
				} else {
					$retval = ERR_NO_UID;
				}
				
				$ret_arr = array(
                    'firstname' => $firstname,
                    'middlename' => $middlename,
                    'lastname' => $lastname,
                    'email' => $email,
                    'matric_no' => $matric_no);
            	ldap_free_result($result);
            	return $ret_arr;
			}
		} catch(Exception $e) {
			$this->unbind();
			$this->errmessage = 'Caught execption, unable to bind!';
			return array();
		}
		$this->unbind();
	}

	function unbind()
	{
		$this->stat_connect = FALSE;
		$this->stat_bind = FALSE;
		ldap_unbind($this->ds);
	}
		
				
	function getUserforMatricNo($matric_no)
	{
		if (!$this->stat_bind) {
				$this->errmessage = "Failed to search, bind first\n";
				return FALSE;
		}
		
		// we have logged in successfully
		$ldap_dn = 'ou=users,ou=campusnet,dc=jacobs,dc=jacobs-university,dc=de';

		// displayname: Stamerjohanns, Heinrich
		// mail: h.stamerjohanns@jacobs-university.de
		// employeenumber: is matric_no if student
		// employeeid: is uid if not student
		try {
			$attributes = 
				array(
					'displayname', 
					'mail',
					'employeenumber',
					'username',
					'samaccountname');
				
			$filter = "(&(objectCategory=person)(employeenumber=$matric_no))";

			$result = ldap_search($this->ds, $ldap_dn, $filter, $attributes);

			$entries = ldap_get_entries($this->ds, $result);

			if ($entries["count"] > 0) {
				//echo $matric_no."\n";
				//echo print_r($entries[0],1)."<br />";
				$displayname = $entries[0]['displayname'][0];
				$username = $entries[0]['samaccountname'][0];
				list($firstname, $middlename, $lastname) = LdapSearch::displayName($displayname);
				$email = $entries[0]['mail'][0];
				if (isset($entries[0]['employeenumber'][0])) {
					$matric_no = $entries[0]['employeenumber'][0];
				} 
				elseif (isset($entries[0]['employeeid'][0])) {
					$matric_no = $entries[0]['employeeid'][0];
				} else {
					$retval = ERR_NO_UID;
				}
				
				$ret_arr = array(
					'username' => $username,	
                    'firstname' => $firstname,
                    'middlename' => $middlename,
                    'lastname' => $lastname,
                    'email' => $email,
                    'matric_no' => $matric_no);
            	ldap_free_result($result);
            	return $ret_arr;
			}
		} catch(Exception $e) {
			$this->unbind();
			$this->errmessage = 'Caught execption, unable to bind!';
			return array();
		}
		$this->unbind();
	}

	function getUserforEmployeeId($employeeid)
	{
		if (!$this->stat_bind) {
				$this->errmessage = "Failed to search, bind first\n";
				return FALSE;
		}
		
		// we have logged in successfully
		$ldap_dn = 'ou=users,ou=campusnet,dc=jacobs,dc=jacobs-university,dc=de';

		// displayname: Stamerjohanns, Heinrich
		// mail: h.stamerjohanns@jacobs-university.de
		// employeenumber: is matric_no if student
		// employeeid: is uid if not student
		try {
			$attributes = 
				array(
					'displayname', 
					'mail',
					'employeeid',
					'username',
					'samaccountname');
				
			$filter = "(&(objectCategory=person)(employeeid=$employeeid))";

			$result = ldap_search($this->ds, $ldap_dn, $filter, $attributes);

			$entries = ldap_get_entries($this->ds, $result);

			if ($entries["count"] > 0) {
				//echo $matric_no."\n";
				//echo print_r($entries[0],1)."<br />";
				$displayname = $entries[0]['displayname'][0];
				$username = $entries[0]['samaccountname'][0];
				list($firstname, $middlename, $lastname) = LdapSearch::displayName($displayname);
				$email = $entries[0]['mail'][0];
				if (isset($entries[0]['employeenumber'][0])) {
					$matric_no = $entries[0]['employeenumber'][0];
				} 
				elseif (isset($entries[0]['employeeid'][0])) {
					$matric_no = $entries[0]['employeeid'][0];
				} 
				$employeeid = $entries[0]['employeeid'][0];
				
				$ret_arr = array(
					'username' => $username,	
                    'firstname' => $firstname,
                    'middlename' => $middlename,
                    'lastname' => $lastname,
                    'email' => $email,
                    'matric_no' => $matric_no,
                    'employeeid' => $employeeid);
						
            	ldap_free_result($result);
            	return $ret_arr;
			}
		} catch(Exception $e) {
			$this->unbind();
			$this->errmessage = 'Caught execption, unable to bind!';
			return array();
		}
		$this->unbind();
	}

	function getUserforEmail($email)
	{
		if (!$this->stat_bind) {
			$this->errmessage = "Failed to search, bind first\n";
			return FALSE;
		}
		
		// we have logged in successfully
		$ldap_dn = 'ou=users,ou=campusnet,dc=jacobs,dc=jacobs-university,dc=de';

		// displayname: Stamerjohanns, Heinrich
		// mail: h.stamerjohanns@jacobs-university.de
		// employeenumber: is matric_no if student
		// employeeid: is uid if not student
		try {
			$attributes = 
				array(
					'displayname', 
					'mail',
					'employeeid',
					'username',
					'samaccountname');
				
			$filter = "(&(objectCategory=person)(mail=$email))";

			$result = ldap_search($this->ds, $ldap_dn, $filter, $attributes);

			$entries = ldap_get_entries($this->ds, $result);

			if ($entries["count"] > 0) {
				//echo $matric_no."\n";
				//echo print_r($entries[0],1)."<br />";
				$displayname = $entries[0]['displayname'][0];
				$username = $entries[0]['samaccountname'][0];
				list($firstname, $middlename, $lastname) = LdapSearch::displayName($displayname);
				$email = $entries[0]['mail'][0];
				if (isset($entries[0]['employeenumber'][0])) {
					$matric_no = $entries[0]['employeenumber'][0];
				} 
				elseif (isset($entries[0]['employeeid'][0])) {
					$matric_no = $entries[0]['employeeid'][0];
				} 
				$employeeid = $entries[0]['employeeid'][0];
				
				$ret_arr = array(
					'username' => $username,	
                    'firstname' => $firstname,
                    'middlename' => $middlename,
                    'lastname' => $lastname,
                    'email' => $email,
                    'matric_no' => $matric_no,
                    'employeeid' => $employeeid);
						
            	ldap_free_result($result);
            	return $ret_arr;
			}
		} catch(Exception $e) {
			$this->unbind();
			$this->errmessage = 'Caught execption, unable to bind!';
			return array();
		}
		$this->unbind();
	}
		
	function getUsersForCourse($course_id)
	{
		if (!$this->stat_bind) {
				$this->errmessage = "Failed to search, bind first\n";
				return FALSE;
		}
		// we have logged in successfully
		$ldap_dn = 'ou=groups,ou=campusnet,dc=jacobs,dc=jacobs-university,dc=de';


	    try {
    	    $attributes =
        	    array(
            	    "description",
                	"member");
			$filter = "(cn=GS-CAMPUSNET-COURSE-".$course_id.")";

			$result = ldap_search($this->ds, $ldap_dn, $filter, $attributes);
			
			$entries = ldap_get_entries($this->ds, $result);

			$arr = array();
			$myarr = array();
        	if ($entries["count"] > 0) {
				foreach ($entries as $entry) {
					if ($entry['count'] > 0) {
                    	if (isset($entry['member'])) {
                        	//echo "COUNT: ".$entry['member']['count']."\n";
                        	unset($entry['member']['count']);
                        	foreach ($entry['member'] as $name) {
								//echo "User Information:\n";
                            	//echo "displayName: ".$name."\n";
                            	list($firstname, $middlename, $lastname, $internid) = LdapSearch::CNdisplayName($name);
                            	//echo "firstname: $firstname\n";
                            	//echo "middlename: $middlename\n";
                            	//echo "lastname: $lastname\n";
                            	//echo "internid: $internid\n";
								$arr[$internid] = $firstname.' '.$lastname;
								$myarr[$lastname.$firstname] = array('firstname' => $firstname, 'lastname' => $lastname, 'internid' => $internid);
							}
                        }
                    }
                }
            }

			ksort($myarr);
			$arr = array();
			foreach ($myarr as $key=>$val) {
				$arr[$val['internid']] = $val['firstname'].' '.$val['lastname'];
			}

				

            ldap_free_result($result);
			return $arr;
			
		} catch(Exception $e) {
			$this->unbind();
			$this->errmessage = 'Caught exception, unable to bind!';
			return array();
		}
		$this->unbind();
	}
}
