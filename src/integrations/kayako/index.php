<?php

/*
 *  Usage examples:
 *
    $k = new KayakoAPI();

    $k->createUser(['fullname' => 'Calvin Froedge', 'password' => 'test', 'email' => 'calvinfroedge@gmail.com']);
    $user = $k->userExists(['query' => 'calvinfroedge@gmail.com']);
    echo $user->fullname;
    $hasOrg = $k->userHasOrganization($user);
    $org = $k->organizationExists(array('name' => 'test'));
 *
 */ 
class KayakoAPI {
    private $_api_url = 'https://softnas.com/helpdesk/api/index.php?e=';
    private $_api_key = '466f9475-9d41-edf4-bd3f-91aa295a20cb';
    private $_api_secret = 'N2FiN2M2MzEtMmYxNC1iNzQ0LWJkOTQtZjYxOTllMzg5Y2JmMDVjZDM2OGEtMjVkZS01ZGI0LWU1NjAtNjU2YjFiNDg2Mjk5';

    public function __construct(){
        libxml_use_internal_errors(true);
    }

    public function __destruct(){
        libxml_use_internal_errors(false);
    }

    /*
     *  Create the authentication fields
     */
    private function _authentication(){
        $salt = mt_rand();
        $signature =  base64_encode(hash_hmac('sha256', $salt, $this->_api_secret, true));

        return array(
            'salt' => $salt,
            'signature' => urlencode($signature),
            'apikey' => $this->_api_key
        );
    }

    /*
     * Get a Result
     */
    private function _result($result){
        try {
            return new SimpleXMLElement($result);
        } catch(\Exception $e) {
            return $result;
        }
    }

    /*
     *  Make a request to the API
     *
     *  Support is there for POST and GET
     */
    private function _request($type, $url, $fields){
        $ch = curl_init();
        $fields = $fields + $this->_authentication();

        $fields_string = "";
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
            rtrim($fields_string, '&');

        if($type == 'post'){
            //url-ify the data for the POST
            curl_setopt($ch,CURLOPT_POST, count($fields));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        } 
        else if($type == 'get'){
            $url = $url . '&' . $fields_string;
        }

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

        curl_setopt($ch,CURLOPT_URL, $this->_api_url.$url);

        //execute post
        $result = curl_exec($ch);

        //close connection
        curl_close($ch);

        return $result;
    }

    /*
     *  If a user exists, return the user, else return false
     */ 
    public function userExists($args){
        if(empty($args) || !isset($args['query'])){
            throw new \Exception("Query must be provided");
        }

        //returns user
        $search = $this->_request('post', '/Base/UserSearch', $args);

        $xml = $this->_result($search);

        if(isset($xml->user) && $args['query'] == $xml->user->email){
            return $xml->user;   
        } else {
            return false;
        }
    }

    /*
     *  Available args:
     *      fullname (required): Name of the user
     *      userorganizationid: Organization to assign the user to
     *      salutation
     *      designation: title
     *      phone
     *      timezone
     *      enabledst
     *      slaplanid
     *      slaplanexpiry
     *      
     */
    public function createUser($args){
        if(!isset($args['fullname'], $args['password'], $args['email'])){
            throw new \Exception("fullname, password and email are required fields");
        }

        $args['userexpiry'] = 0;
        $args['userrole'] = 'user';
        $args['sendwelcomeemail'] = true;
        $args['usergroupid'] = 2;
        $args['isenabled'] = 1;

        //Can we do grant permissions to receive product notices & updates, promotions 
        return $this->_result($this->_request('post', '/Base/User', $args));
    }

    /*
     * Does a user belong to an organization?
     */
    public function userHasOrganization($user){
        return (isset($user->userorganizationid) && !empty($user->userorganizationid)) ? $user->userorganizationid : false;       
    }

    /*
     *  Create a new organization
     *
     *  Available args:
     *      name (required)
     *      address
     *      city
     *      state
     *      postalcode
     *      country
     *      phone
     *      fax
     *      website
     *      slaplanid
     *      slaplanexpiry
     */
    public function createOrganization($args = array()){
        if(!isset($args['name'])){
            throw new \Exception("Organization name is required.");
        }

        $args['organizationtype'] = 'shared';

        return $this->_result($this->_request('post', '/Base/UserOrganization', $args));
    }

    /*
     * Get a list of organizations
     */ 
    public function getOrganizations(){
        return $this->_result($this->_request('get', '/Base/UserOrganization', array()));
    }

    /*
     * Query organization based on any key belonging to the organization (Most common usage would be name, ie $k->organizationExists(['name' => 'test']))
     *
     * 
     */ 
    public function organizationExists($args){
        $orgs = $this->getOrganizations();

        foreach($orgs->userorganization as $org){
            foreach($args as $k=>$arg){
                if(isset($org->$k) && $org->$k == $args[$k]) return $org;
            }
        }

        return null;
    }

    /*
     * Add a ticket 
     * 
     * opts:
     *  subject (required)
     *  fullname (required) - full name of creator
     *  email (required) - email of creator
     *  contents (required) - The content of the ticket
     *  departmentid
     *  ticketstatusid
     *  ticketpriorityid
     *  tickettypeid
     *  userid
     */
    public function createTicket($args){
        if(!isset($args['subject'], $args['fullname'], $args['email'], $args['contents'], $args['userid'])){
            throw new \Exception("subject, fullname, email, contents and userid are required fields");
        }

        //Changed Dep ID to 13 06/23/15 -njennings89
        $args['departmentid'] = 15;
        $args['ticketstatusid'] = 1;
        $args['ticketpriorityid'] = 1;
        $args['tickettypeid'] = 5;
        $args['staffid'] = 120;

        return $this->_result($this->_request('post', '/Tickets/Ticket', $args));
    }
}
