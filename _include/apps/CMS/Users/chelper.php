<?php
namespace CMS_Users {

    class chelper {

    private $kernel;

	function __construct(&$kernel) {
	    $this->kernel = &$kernel;
    }

	function Login($array) {
	    $data = $this->kernel->db->users->findOne(['name' => $array['name']]);
	if (!empty($data) && isset($data['pass']) && password_verify($array['encr'], $data['pass'])) {
	    return["_id" => $data['_id'], "name" => $data['name']];
	    }
	return [];
    }

	function Register($array) {
	    if ($array['rencr'] !== $array['rencrR'] || $array['rencr'] != $array['rencrR'])
		return 'Passwords is not same';
	    if (strlen($array['rencr']) < 8 || !preg_match('#[A-Za-z0-9]*[\+\\\\\(\)|&\*\!\@\#\$\%\^\.]+[A-Za-z0-9]*#', $array['rencr']))
		return 'Password is too weak';
	    if (empty($this->kernel->db->users->findOne(['$or' => [['name' => $array['rname']], ['email' => $array['rmail']]]]))) {
		if ($this->kernel->db->users->insert(['name' => $array['rname'], 'email' => $array['rmail'], 'pass' => password_hash($array['rencr'], PASSWORD_DEFAULT)])) {
		    return '';
		}
		else
		    return 'Cannot create account';
	    }
	    else
		return 'Login or email is unavailable';
	}

	public function checkNameExists($name) {
	    return !empty($this->kernel->db->users->findOne(['name' => $name]));
	}

    }

}
?>
