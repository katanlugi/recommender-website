<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LdapController extends Controller
{
    private $ldapConnection;
    private $ldapParams;

    public function __construct($ldapUrl, $ldapParams)
    {
        $this->ldapParams = $ldapParams;
        $this->ldapConnection = ldap_connect($ldapUrl) or die('Could not connect to LDAP server.');
        ldap_set_option( $this->ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3 );
        ldap_set_option( $this->ldapConnection, LDAP_OPT_REFERRALS, 0 );
    }

    public function authenticate($id, $password)
    {
        $ldapUsername = $id.'@bfh.ch';
        
        if ($this->ldapConnection) {
            $ldapBind = ldap_bind($this->ldapConnection, $ldapUsername, $password);
            
            if ($ldapBind) {
                return true;
            }
        }
        return false;
    }

    /*
     * Gets User Data From LDAP server
     */
    public function getUserData($id)
    {
        // Sets search filter for Ldap AD
        $filter = "(sAMAccountName=$id)";
        $result = ldap_search($this->ldapConnection, $this->ldapParams, $filter);
        $rawData = ldap_get_entries($this->ldapConnection, $result);
        // Creates userData Array and fills it
        $userData = array();
        $userData['surname'] =  $rawData[0]['sn'][0];
        $userData['name'] =  $rawData[0]['givenname'][0];
        $userData['email'] =  $rawData[0]['mail'][0];
        $userData['type'] =  $rawData[0]['extensionattribute2'][0];
        return $userData;
    }

}
