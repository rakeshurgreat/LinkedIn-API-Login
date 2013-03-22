<?php

define('LINKEDIN_APPID','xxxxxxxxxxxx');
define('LINKEDIN_SECRET_KEY','xxxxxxxxxxxxx');
define('LINKEDIN_API_URL','https://www.linkedin.com/uas/oauth2/');
define('LINKEDIN_USER_URL','https://api.linkedin.com/v1/people/');
define('LINKEDIN_REDIRECT_URL','your redirect url');

class linkedin
{
	public function __construct()
	{
		$this->_objLog = new logs('linkedin_'.date("Y_m_d_H").'.txt');
		
		$this->_objLog->writeLog(__LINE__, __FILE__, '', '');
		
		$this->_apikey = LINKEDIN_APPID;
		$this->_secretkey = LINKEDIN_SECRET_KEY;
		$this->_baseurl = LINKEDIN_REDIRECT_URL;
		
		$this->_state = session_id();
		
		$this->_objLog->writeLog(__LINE__, __FILE__, 'Setting Class Init Value', 'Class Init');
	}
	
	public function setAuthorizeCode($code)
	{
		$this->_authorize_code = $code;
	}
	
	public function AuthenticatUser()
	{
		$arrData = array();
		
		$arrData['response_type'] = 'code';
		$arrData['client_id'] 	 = $this->_apikey;
		$arrData['scope']	 	 = 'r_fullprofile r_emailaddress rw_nus';
		$arrData['state']	 	 =  $this->_state;
		$arrData['redirect_uri']  = $this->_baseurl;
	
		$URL = LINKEDIN_API_URL."authorization?".http_build_query($arrData);
		
		$this->_objLog->writeLog(__LINE__, __FILE__, $URL, 'AuthenticatUser URL');
		
		return $URL;
	}
	
	
	public function getAuthorizationCode()
	{
		$arrData = array();
		
		$arrData['grant_type'] 	= 'authorization_code';
		$arrData['code']	 	  = $this->_authorize_code;		
		$arrData['client_id'] 	 = $this->_apikey;
		$arrData['client_secret'] = $this->_secretkey;
		$arrData['redirect_uri']  = $this->_baseurl;
			
		$URL = LINKEDIN_API_URL."accessToken?".http_build_query($arrData);
		
		$this->_objLog->writeLog(__LINE__, __FILE__, $URL, 'getAuthorizationCode URL');
				
		$result = $this->curl($URL);
		
		$this->_objLog->writeLog(__LINE__, __FILE__, $result, 'getAuthorizationCode Curl Response');
		
		$arrResult = json_decode($result);

		if($arrResult->access_token != '')
		{
			$this->_access_token = $arrResult->access_token;
			
			$this->_objLog->writeLog(__LINE__, __FILE__, $arrResult->access_token, 'getAuthorizationCode Access Token Found');
			$this->getUserDetails($arrResult->access_token);
		}
	}
	
	public function getUserDetails($token)
	{
		$arrData = array();
		
		$arrData['oauth2_access_token'] = $token;
			
		$URL = LINKEDIN_USER_URL."~?".http_build_query($arrData);
		
		$this->_objLog->writeLog(__LINE__, __FILE__, $URL, 'getUserDetails');
					
		$result = $this->curl($URL);
		
		$this->_objLog->writeLog(__LINE__, __FILE__, $result, 'getUserDetails Curl Response');
		
		$this->extractUserDetails($result);
	}
	
	
	public function extractUserDetails($result)
	{
		$xml = simplexml_load_string($result);
		
		if(is_object($xml))
		{
			$arrDetails = $this->fetch('GET', '~:(firstName,lastName,id,email-address)'); 
					
		}
		
		$this->_userdetails = array(
										'username' => $arrDetails->firstName, 
										'userid'   => $arrDetails->id, 
										'email' 	=> $arrDetails->emailAddress);	
	}
	
	function getUserInfo()
	{
		return $this->_userdetails;
	}
	
	
	public function fetch($method, $resource) 
	{
	    $params = array('oauth2_access_token' => $this->_access_token, 'format' => 'json',);
     
	 	//~:(firstName,lastName)
		$url = LINKEDIN_USER_URL . $resource . '?' . http_build_query($params);
		// Tell streams to make a (GET, POST, PUT, or DELETE) request
		$context = stream_context_create(array('http' =>  array('method' => $method)));
	 
		// Hocus Pocus
		$response = file_get_contents($url, false, $context);
	 
		// Native PHP object, please
	    return json_decode($response);
	}
	
	
	public function curl($urlPath)
	{
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $urlPath);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_SSLVERSION, 3); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_HEADER, 0); 

		$data = curl_exec($ch);
		
		if(curl_errno($ch)){ echo 'Curl error: ' . curl_error($ch); } 
		
		curl_close($ch);
		
		return $data;
	}
}

/******************************************************************************/

$objLinkedIn = new linkedin();
					
if($_REQUEST['code'] != '')
{
	$objLinkedIn->setAuthorizeCode($_REQUEST['code']);
	$objLinkedIn->getAuthorizationCode();
	
	$arrUserInfo = $objLinkedIn->getUserInfo();
	
	print_r($arrUserInfo);
}

?>