<?php
/*
User Documents: http://www.quyu.net/info/userdocs
OTE Platform: http://ote.quyu.net
Official Website: http://www.quyu.net
API Documents: http://www.quyu.net/knowledgebase.php?action=displaycat&catid=8
*/
/* ***********************************************************************
 * Customization Development Services by QuYu.Net                        *
 * Copyright (c) ShenZhen QuYu Tech CO.,LTD, All Rights Reserved         *
 * (2013-09-23, 12:16:25)                                                *
 *                                                                       *
 *                                                                       *
 *  CREATED BY QUYU,INC.           ->       http://www.quyu.net          *
 *  CONTACT                        ->       support@quyu.net             *
 *                                                                       *
 *                                                                       *
 *                                                                       *
 *                                                                       *
 * This software is furnished under a license and may be used and copied *
 * only  in  accordance  with  the  terms  of such  license and with the *
 * inclusion of the above copyright notice.  This software  or any other *
 * copies thereof may not be provided or otherwise made available to any *
 * other person.  No title to and  ownership of the  software is  hereby *
 * transferred.                                                          *
 *                                                                       *
 *                                                                       *
 * ******************************************************************** */

class quyunet extends DomainModule {

	protected $description	= 'quyunet';
	protected $modname		= "quyunet";
	protected $version		= "1.0";

	protected $client_data	= array();
	protected $configuration= array(
		'user_email' => array(
			'value'		=> '',
			'type'		=> 'input',
			'default'	=> false
		),
		'api_key' => array(
			'value'		=> '',
			'type'		=> 'input',
			'default'	=> false
		),
	);
	protected $lang = array(
		'english' => array(
			'user_email'=> 'User Email',
			'api_key'	=> 'API Key',
		)
	);
	protected $commands = array('Register','Transfer','Renew','getNameServers','updateNameServers','getEppCode','RequestDelete');




	public function synchInfo(){
		$response = $this->_callApi(array(
			"action"			=> "Sync",
			"token"				=> $this->configuration['api_key']['value'],
			"authemail"			=> $this->configuration['user_email']['value'],
			"sld"				=> $this->options['sld'],
			"tld"				=> $this->options['tld'],
		));

		if (isset($response['result'])){
			NativeMySQL::connect();
			if ($response['result'] == 'success'){

				if ($response['expirydate']){
					if (mysql_query('UPDATE hb_domains SET expires = "'.$response['expirydate'].'"'))
						$this->addInfo('Expiry date has been updated');
				}
				if ($response['status']=='Active'){
					$this->addInfo('Domain is active');
					return true;
				} else {
					if (strtotime(date( "Ymd" )) <= strtotime( $response['expirydate'] )) {
						$this->addInfo('Domain is active');
						return true;
					} else {
						mysql_query('UPDATE hb_domains SET status = "Expired"');
						$this->addError('Domain is expired');
						return false;
					}
				}
			}

			$this->addError($response['msg']);
			return false;
		}

		$this->addError('Connection error');
	}

	public function testConnection(){
		$response = $this->_callApi(array(
			"action"	=> "Version",
			"token"     => $this->configuration['api_key']['value'],
			"authemail" => $this->configuration['user_email']['value']
		));

		return isset($response['result']) && $response['result'] == 'success';
	}

	public function Register(){
		$response = $this->_callApi(array(
			"action"			=> "RegisterDomain",
			"token"				=> $this->configuration['api_key']['value'],
			"authemail"			=> $this->configuration['user_email']['value'],
			"sld"				=> $this->options['sld'],
			"tld"				=> $this->options['tld'],
			"regperiod"			=> $this->period,
			"nameserver1"		=> $this->options['ns1'] ? $this->options['ns1'] : $this->details['ns1'],
			"nameserver2"		=> $this->options['ns2'] ? $this->options['ns2'] : $this->details['ns2'],
			"nameserver3"		=> $this->options['ns3'] ? $this->options['ns3'] : $this->details['ns3'],
			"nameserver4"		=> $this->options['ns4'] ? $this->options['ns4'] : $this->details['ns4'],
			"nameserver5"		=> "",
			"dnsmanagement"		=> 0,
			"emailforwarding"	=> 0,
			"idprotection"		=> 0,
			"firstname"			=> $this->client_data['firstname'],
			"lastname"			=> $this->client_data['lastname'],
			"companyname"		=> $this->client_data['companyname'],
			"address1"			=> $this->client_data['address1'],
			"address2"			=> $this->client_data['address2'],
			"city"				=> $this->client_data['city'],
			"state"				=> $this->client_data['state'],
			"country"			=> $this->client_data['country'],
			"postcode"			=> $this->client_data['postcode'],
			"phonenumber"		=> $this->client_data['phonenumber'],
			"fullphonenumber"	=> $this->client_data['phonenumber'],
			"email"				=> $this->client_data['email'],
			"adminfirstname"	=> $this->domain_contacts['admin']['firstname'],
			"adminlastname"		=> $this->domain_contacts['admin']['lastname'],
			"admincompanyname"	=> $this->domain_contacts['admin']['companyname'],
			"adminaddress1"		=> $this->domain_contacts['admin']['address1'],
			"adminaddress2"		=> $this->domain_contacts['admin']['address2'],
			"admincity"			=> $this->domain_contacts['admin']['city'],
			"adminstate"		=> $this->domain_contacts['admin']['state'],
			"admincountry"		=> $this->domain_contacts['admin']['country'],
			"adminpostcode"		=> $this->domain_contacts['admin']['postcode'],
			"adminphonenumber"	=> $this->domain_contacts['admin']['phonenumber'],
			"adminfullphonenumber" => $this->domain_contacts['admin']['phonenumber'],
			"adminemail"		=> $this->domain_contacts['admin']['email'],
//			"domainfields"		=> base64_encode(serialize(array_values(array(
				// un supported
//			))))
		));

		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$this->addInfo('Domain has been registered');
				return true;
			}

			$this->addError($response['msg']);
			return false;
		}

		$this->addError('Connection error while registering');
	}

	public function Transfer(){
		$response = $this->_callApi(array(
			"action"			=> 'TransferDomain',
			"token"				=> $this->configuration['api_key']['value'],
			"authemail"			=> $this->configuration['user_email']['value'],
			"sld"				=> $this->options['sld'],
			"tld"				=> $this->options['tld'],
			'transfersecret'	=> $this->details['epp_code'],
			"regperiod"			=> $this->period,
			"nameserver1"		=> $this->options['ns1'] ? $this->options['ns1'] : $this->details['ns1'],
			"nameserver2"		=> $this->options['ns2'] ? $this->options['ns2'] : $this->details['ns2'],
			"nameserver3"		=> $this->options['ns3'] ? $this->options['ns3'] : $this->details['ns3'],
			"nameserver4"		=> $this->options['ns4'] ? $this->options['ns4'] : $this->details['ns4'],
			"nameserver5"		=> "",
			'dnsmanagement'		=> 0,
			'emailforwarding'	=> 0,
			'idprotection'		=> 0,
			"firstname"			=> $this->client_data['firstname'],
			"lastname"			=> $this->client_data['lastname'],
			"companyname"		=> $this->client_data['companyname'],
			"address1"			=> $this->client_data['address1'],
			"address2"			=> $this->client_data['address2'],
			"city"				=> $this->client_data['city'],
			"state"				=> $this->client_data['state'],
			"country"			=> $this->client_data['country'],
			"postcode"			=> $this->client_data['postcode'],
			"phonenumber"		=> $this->client_data['phonenumber'],
			"email"				=> $this->client_data['email'],
			'fullphonenumber'   => $this->client_data['phonenumber'],

			"adminfirstname"	=> $this->domain_contacts['admin']['firstname'],
			"adminlastname"		=> $this->domain_contacts['admin']['lastname'],
			"admincompanyname"	=> $this->domain_contacts['admin']['companyname'],
			"adminaddress1"		=> $this->domain_contacts['admin']['address1'],
			"adminaddress2"		=> $this->domain_contacts['admin']['address2'],
			"admincity"			=> $this->domain_contacts['admin']['city'],
			"adminstate"		=> $this->domain_contacts['admin']['state'],
			"admincountry"		=> $this->domain_contacts['admin']['country'],
			"adminpostcode"		=> $this->domain_contacts['admin']['postcode'],
			"adminphonenumber"	=> $this->domain_contacts['admin']['phonenumber'],
			"adminfullphonenumber" => $this->domain_contacts['admin']['phonenumber'],
			"adminemail"		=> $this->domain_contacts['admin']['email'],
//			"domainfields"		=> base64_encode(serialize(array_values(array(
				// un supported
//			)))),
		));

		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$this->addInfo('Domain has been transfered');
				return true;
			}

			$this->addError($response['msg']);
			return false;
		}

		$this->addError('Connection error while transfering');
	}

	public function Renew(){
		$response = $this->_callApi(array(
			"action"		=> 'RenewDomain',
			"token"			=> $this->configuration['api_key']['value'],
			"authemail"		=> $this->configuration['user_email']['value'],
			"sld"			=> $this->options['sld'],
			"tld"			=> $this->options['tld'],
			'regperiod'		=> $this->period
		));

		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$this->addInfo('Domain has been Renewed');
				return true;
			}

			$this->addError($response['msg']);
			return false;
		}

		$this->addError('Connection error while renewing');
	}

	public function getNameServers(){
		$response = $this->_callApi(array(
			"action"		=> 'GetNameservers',
			"token"			=> $this->configuration['api_key']['value'],
			"authemail"		=> $this->configuration['user_email']['value'],
			"sld"			=> $this->options['sld'],
			"tld"			=> $this->options['tld'],
		));

		if (isset($response['result'])){
			$details = array();
			if ($response['result'] == 'success'){
				for($i = 1; $i <= 5; $i++){
					$this->details['ns'.$i] = $response['ns'.$i];
					$details[] = $response['ns'.$i];
				}
				NativeMySQL::connect();
				mysql_query('UPDATE hb_domains SET nameservers = "' . implode('|', $details).'"');

				$this->addInfo('Nameservers has beed updated in Hostbill');
				return $details;
			}

			$this->addError($response['msg']);
			return false;
		}

		$this->addError('Connection error while getting nameservers');
	}

	public function updateNameServers(){
		$response = $this->_callApi(array(
			"action"		=> 'SaveNameservers',
			"token"			=> $this->configuration['api_key']['value'],
			"authemail"		=> $this->configuration['user_email']['value'],
			"sld"			=> $this->options['sld'],
			"tld"			=> $this->options['tld'],
			"nameserver1"	=> $this->options['ns1'] ? $this->options['ns1'] : $this->details['ns1'],
			"nameserver2"	=> $this->options['ns2'] ? $this->options['ns2'] : $this->details['ns2'],
			"nameserver3"	=> $this->options['ns3'] ? $this->options['ns3'] : $this->details['ns3'],
			"nameserver4"	=> $this->options['ns4'] ? $this->options['ns4'] : $this->details['ns4'],
			"nameserver5"	=> "",
		));

		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$this->addInfo('Nameservers has been updated in the registrar');
				return true;
			}

			$this->addError($response['msg']);
			return false;
		}

		$this->addError('Connection error');
	}

	public function getEppCode(){
		$response = $this->_callApi(array(
			"action"		=> 'GetEPPCode',
			"token"			=> $this->configuration['api_key']['value'],
			"authemail"		=> $this->configuration['user_email']['value'],
			"sld"			=> $this->options['sld'],
			"tld"			=> $this->options['tld'],
		));

		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$this->details['epp_code'] = $response['eppcode'];
				$this->addInfo('Epp Code: ' . $response['eppcode']);
				return true;
			}

			$this->addError($response['msg']);
			return false;
		}

		$this->addError('Connection error');
	}

	public function RequestDelete(){
		$response = $this->_callApi(array(
			"action"		=> 'RequestDelete',
			"token"			=> $this->configuration['api_key']['value'],
			"authemail"		=> $this->configuration['user_email']['value'],
			"sld"			=> $this->options['sld'],
			"tld"			=> $this->options['tld'],
//			'regperiod'     => $this->period,
//          'regtype'       => $params['regtype']
		));

		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$this->addInfo('Request has been placed');
				return true;
			}

			$this->addError($response['msg']);
			return false;
		}

		$this->addError('Connection error');
	}



	protected function _callApi($data){

		$url = 'http://api.quyu.net/api.php';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$result = curl_exec($ch);
		$res    = json_decode($result, true);
		curl_close($ch);

		return $res;
	}
}

