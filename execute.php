<?php
	# Start: loading the 3rd-party libraries
	echo "[+] loading the 3rd-party libraries .. ";
		require_once 'lib/php-curl-class.php';
		require_once 'lib/simple-html-dom.php';
		require_once 'lib/random-user-agent.php';
	echo "DONE !\n";
	# End

	# Start: creating all the classes' instance
	echo "[+] creating all the classes' instance .. ";
		$fmg = new Fakemailgenerator();
		$fng = new Fakenamegenerator();
		$msf = new Metasploit();
	echo "DONE !\n";
	# End

	# Start: checking all the mail domains are valid or not
	echo "[+] checking all the mail domains are valid or not .. \n";
		$domains	= $fmg->get_available_domains();
		$fields		= $fng->get_profile_fields();
		$address	= $msf->check_mail_address( $fields['user_name'], $domains );
	echo "[+] ALL DONE !\n";
	# End

	# Start: choosing a valid domain randomlly and generating an email address
	echo "[+] choosing a valid domain randomlly and generating an email address .. ";
		$total			= count( $address['valid'] ) - 1;
		$fields['email']	= sprintf( '%s%s', $fields['user_name'], $address['valid'][rand( 0, $total )] );
	echo "DONE !\n";
	# End

	# READY TO FIRE !
	echo "\n[+] READY TO FIRE !\n\n";

	# Start: submitting the trial request to metasploit
	echo "[+] submitting the trial request to metasploit ..\n";
		$hidden = $msf->get_hidden_values();
		$msf->submit_trial_request( $fields, $hidden );
	echo "[+] ALL DONE !\n";
	# End

	# Start: looping to retrieve the trial mail content
	echo "[+] looping to retrieve the trial mail content ..\n";
	echo $fmg->get_trial_license( $fields['email'], 60 );
	# End

	# MISSION COMPLETED :-D
	echo "\n\nif you like this script, buy me a coffee ?\nPaypal: Chris#skiddie.me, BitCoin: 1mKbj7Dmmy7U1sJ3KFezK4NC5t2qJHT11\n";

	/** 
	 * @author Chris Lin <Chris#skiddie.me>
	 * @version 2014-02-16
	 */
	class Fakenamegenerator {
		private $curl_resource;
		private $html_resource;
		private $provider_address;
		private $return_result;

		public function __construct() {
			$this->curl_resource	= new Curl();
			$this->html_resource	= new simple_html_dom();
			$this->provider_address = 'http://www.fakenamegenerator.com/advanced.php';
			$this->return_result	= NULL;
		}

		public function __destruct() {
			$this->curl_resource->close();
			$this->html_resource->clear();
		}

		/**
		 * parsing the fakenamegenerator profile content to get the fake fields likes name, phone, etc.
		 * 
		 * @author Chris Lin <Chris#skiddie.me>
		 * @link http://www.fakenamegenerator.com/advanced.php?t=country&n[]=us&c[]=us&gen=85&age-min=19&age-max=45
		 * @return array returns an array of fake profile fields
		 * @version 2014-02-16
		 */
		public function get_profile_fields() {
			$field		= [];
			$pattern	= 'div[class=extra]';
			$payload	= array (
				'age-max'	=> '45',
				'age-min'	=> '19',
				'c[]'		=> 'us',
				'gen'		=> '85',
				'n[]'		=> 'us',
				't'		=> 'country'
			);

			# sending the GET request to retrieve the HTML raw code
			$this->curl_resource->get( $this->provider_address, $payload );
			# ready to parse some fields we're interested
			$this->html_resource->load( $this->curl_resource->response );

			# Start: parsing the name info from response
			echo "		[*] parsing the name info from response .. ";
				$full_name		= explode( ' ', $this->html_resource->find( 'div[class=info]', 0 )->children( 0 )->children( 0 )->children( 0 )->plaintext );
				$fields['first_name']	= $full_name[0];
				$fields['last_name']	= $full_name[2];
				$fields['user_name']	= strtolower( $this->html_resource->find( $pattern, 0 )->children( 0 )->children( 7 )->plaintext );
			echo "DONE !\n";
			# End

			# Start: parsing the additional info from response
			echo "		[*] parsing the additional info from response .. ";
				$fields['title']	= $this->html_resource->find( $pattern, 0 )->children( 0 )->children( 34 )->plaintext;
				$fields['company_name']	= $this->html_resource->find( $pattern, 0 )->children( 0 )->children( 37 )->plaintext;
				$fields['phone']	= sprintf( '+1%s', str_replace( '-', '', $this->html_resource->find( $pattern, 0 )->children( 0 )->children( 1 )->children( 0 )->plaintext ) );
				# $fields['email']	= strtolower( $this->html_resource->find( 'div[class=extra]', 0 )->children(0)->children(4)->children(0)->plaintext );
				# $fields['address']	= str_replace( '<br/>', ' ', trim( $this->html_resource->find( 'div[class=info]', 0 )->children(0)->children(0)->children(1)->innertext ) );
			echo "DONE !\n";
			# End

			# return the fields value we've parsed
			$this->return_result = $fields;
			return $this->return_result;
		}
	}

	/**
	 * @author Chris Lin <Chris#skiddie.me>
	 * @version 2014-02-16
	 */
	class Fakemailgenerator {
		private $curl_resource;
		private $html_resource;
		private $provider_address;
		private $return_result;

		public function __construct() {
			$this->curl_resource	= new Curl();
			$this->html_resource	= new simple_html_dom();
			$this->provider_address	= 'http://www.fakemailgenerator.com';
			$this->return_result	= NULL;
		}

		public function __destruct() {
			$this->curl_resource->close();
			$this->html_resource->clear();
		}

		/**
		 * parsing the fakemailgenerator mail content to get all available domains
		 * 
		 * @author Chris Lin <Chris#skiddie.me>
		 * @link http://www.fakemailgenerator.com/
		 * @return array returns an array of all the available mail domains
		 * @version 2014-02-16
		 */
		public function get_available_domains() {
			$domains = [];
			$pattern = 'option';

			# sending the GET request to retrieve the HTML raw code
			$this->curl_resource->get( $this->provider_address );
			# ready to parse some fields we're interested
			$this->html_resource->load( $this->curl_resource->response );

			# Start: parsing all the available domains from response
			echo "	[-] parsing all the available domains from response .. ";
				foreach ( $this->html_resource->find( $pattern ) as $domain ) {
					array_push( $domains, $domain->plaintext );
				}
			echo "DONE !\n";
			# End

			# return the domains value we've parsed
			$this->return_result = $domains;
			return $this->return_result;
		}

		/**
		 * parsing the fakemailgenerator mail content to get the trial license in looping
		 * 
		 * @author Chris Lin <Chris#skiddie.me>
		 * @link http://www.fakemailgenerator.com/inbox/einrot.com/murmiddly76/
		 * @param string $email a mail address parsed from fakemailgenerator to receive the trial license
		 * @param int $delay waiting for %d seconds to get again if the trial info has not delivered
		 * @return string the metasploit pro trial product key for 7-days
		 * @version 2014-02-16
		 */
		public function get_trial_license( $email, $delay = 45 ) {
			$address	= explode( '@', $email );
			$inbox		= sprintf( '%s/inbox/%s/%s/', $this->provider_address, $address[1], $address[0] );
			$license	= '';
			$pattern	= 'span[class=theme]';

			# checking the trial confirmation mail has delivered to inbox or not
			echo "	[-] checking the trial confirmation mail has delivered to inbox or not ..\n";
				do {
					# sending the GET request to retrieve the HTML raw code
					$this->curl_resource->get( $inbox );
					# ready to parse some fields we're interested
					$this->html_resource->load( $this->curl_resource->response );

					# <span class="theme">Rapid7 Metasploit Pro Trial License Activated</span>
					$content = $this->html_resource->find( $pattern, 0 );

					if ( empty( $content ) ) {
						# no luck, waiting for %d second(s) to step into the new loop to fetch again
						echo "		[*] waiting for trial mail delivered to inbox: $inbox ..\n";
						sleep( $delay );
					} else {
						# BINGO !
						echo "		[*] BINGO ! the mail just delivered, parsing it .. ";
							# http://www.fakemailgenerator.com/inbox/fleckens.hu/carljlange/message-21872104/
							$url = explode( '/', str_replace( '-', '/', $content->parent()->href ) );
							# http://www.fakemailgenerator.com/email.php?id=21872104
							$this->curl_resource->get( sprintf( '%s/email.php?id=%s', $this->provider_address, $url[5] ) );
							$this->html_resource->load( $this->curl_resource->response );
							# parsing the trial serial
							$license = $this->html_resource->find( 'span', 0 )->parent()->plaintext;
						echo "DONE !";
					}
				} while ( empty( $license ) );
			echo "\n	[-] ALL DONE !\n\n";
			# End

			# return the 7-DAYS pro serial we want, DONE !
			$this->return_result =  preg_replace( '/\s(?=\s)/', '', $license );
			return $this->return_result;
		}
	}

	/**
	 * @author Chris Lin <Chris#skiddie.me>
	 * @version 2014-02-16
	 */
	class Metasploit {
		private $check_address;
		private $curl_resource;
		private $form_address;
		private $html_resource;
		private $register_address;
		private $return_result;

		public function __construct() {
			$this->check_address	= 'https://forms.netsuite.com/app/site/hosting/scriptlet.nl';
			$this->curl_resource	= new Curl();
			$this->form_address	= 'https://forms.netsuite.com/app/site/hosting/scriptlet.nl?script=214&deploy=1&compid=663271&h=f545d011e89bdd812fe1';
			$this->html_resource	= new simple_html_dom();
			$this->register_address	= 'https://www.rapid7.com/register/metasploit-trial.jsp?product';
			$this->return_result	= NULL;

			/* $this->curl_resource->error( function( $instance ) {
				echo "\n[?] calling CURL was unsuccessful ..\n";
				echo "	[!] error code: $instance->error_code\n";
				echo "	[!] error message: $instance->error_message\n";
				echo "[?] please cancel and run this script agian, contact the author if the errors still continue :-(\n";
			}); */
		}

		public function __destruct() {
			$this->curl_resource->close();
			$this->html_resource->clear();
		}

		/**
		 * checking which the mail domains are valid from metasploit validation
		 *
		 * @author Chris Lin <Chris#skiddie.me>
		 * @link https://forms.netsuite.com/app/site/hosting/scriptlet.nl?script=177&deploy=1&compid=663271&h=5c107be29a3fe5ef6392&vd=emdf+eme+ips&ips=167.216.129.23&em=perat8678@teleworm.us
		 * @param string $name a user name to prepend to mail domain
		 * @param array $domains an array of all the available mail domains to be extracted
		 * @return array return an array of the valid and illegal check result
		 * @version 2014-02-16
		 */
		public function check_mail_address( $name, $domains ) {
			$illegal	= [];
			$pattern	= 'emdf:true';
			$valid		= [];

			# Start: extracting from all the available domains
			echo "	[-] extracting from all the available domains .. ";
				foreach ( $domains as $domain ) {
					$payload = array (
						'compid'	=> 663271,
						'deploy'	=> 1,
						'em'		=> sprintf( '%s%s', $name, $domain ),
						'h'		=> '5c107be29a3fe5ef6392',
						'ips'		=> long2ip( rand( 0, 255 * 255 ) * rand( 0, 255 * 255 ) ),
						'script'	=> 177,
						'vd'		=> 'emdf eme ips'
					);

					# sending the GET request to retrieve the HTML raw code
					$this->curl_resource->get( $this->check_address, $payload );

					# Start: checking the mail address is valid or not
					echo "\n		[*] checking the mail address: $payload[em] is valid or not .. ";
						if ( strpos( $this->curl_resource->response, $pattern ) === FALSE ) {
							echo "ILLEGAL !";
							array_push( $illegal,  $domain );
						} else {
							echo "VALID !";
							array_push( $valid,  $domain );
						}
					# End
				}
			echo "\n	[-] ALL DONE !\n";
			# End

			# return the validate info we've parsed
			$this->return_result = array ( 'valid' => $valid, 'illegal' => $illegal );
			return $this->return_result;
		}

		/**
		 * parsing the hidden filds' value from metasploit registration form
		 *
		 * @author Chris Lin <Chris#skiddie.me>
		 * @link https://www.rapid7.com/register/metasploit-trial.jsp?product
		 * @return array return an array of the hidden filds' value
		 * @version 2014-02-16
		 */
		public function get_hidden_values() {
			$keys	= [ 'custparamleadsource', 'custparamreturnpath', 'custparamproductaxscode' ];
			$values = [];

			# sending the GET request to retrieve the HTML raw code
			$this->curl_resource->get( $this->register_address );
			# ready to parse some fields we're interested
			$this->html_resource->load( $this->curl_resource->response );

			# Start: parsing the hidden filds' value from response
			echo "	[-] parsing the hidden filds' value from response ..";
				foreach ( $keys as $key ) {
					$value = $this->html_resource->find( "input[name=$key]", 0 )->value;
					$values[$key] = $value;
					echo "\n		[*] field: $key has value: $value";
				}
			echo "\n	[-] ALL DONE !\n";
			# End

			# return the hidden values we've parsed
			$this->return_result = $values;
			return $this->return_result;
		}
		# End

		/**
		 * submitting the trial request to the registration form
		 *
		 * @author Chris Lin <Chris#skiddie.me>
		 * @link https://forms.netsuite.com/app/site/hosting/scriptlet.nl?script=214&deploy=1&compid=663271&h=f545d011e89bdd812fe1
		 * @param array $profile the applicant's contact information
		 * @param array $hidden the hidden fields in this form
		 * @version 2014-02-16
		 */
		public function submit_trial_request( $profile, $hidden = NULL ) {
			echo "[+] preparing the registration payload .. ";
				$payload = array (
					# maybe there will have a captcha validation in the future ? handle it by yourself !
					# reference: http://www.dama2.com/
					# 'custparamcaptcha'		=> '',
					'custparamfirstname'		=> $profile['first_name'],
					'custparamlastname'		=> $profile['last_name'],
					'custparamtitle'		=> $profile['title'],
					'custparamcompanyname'		=> $profile['company_name'],
					'custparamcountry'		=> 'TW',
					'custparamstate'		=> 0,
					'custparamuse'			=> 'Business',
					'custparamphone'		=> $profile['phone'],
					'custparamemail'		=> $profile['email'],
					'custparamleadsource'		=> ( empty( $hidden ) ) ? 443597 : $hidden['custparamleadsource'],
					'submitted'			=> '',
					'custparamreturnpath'		=> ( empty( $hidden ) ) ? 'https://localhost:3790/setup/activation' : $hidden['custparamreturnpath'],
					'custparamproduct_key'		=> '',
					'custparamproductaxscode'	=> ( empty( $hidden ) ) ? 'msY5CIoVGr' : $hidden['custparamproductaxscode'],
					'custparamthisIP'		=> long2ip( rand( 0, 255 * 255 ) * rand( 0, 255 * 255 ) ) 
				);
			echo "DONE !\n";

			echo "[+] configuring the CURL options .. ";
				$headers = array (
					# 'Accept'		=>'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
					# 'Accept-Encoding'	=> 'gzip,deflate,sdch',
					# 'Accept-Language'	=> 'zh-TW,zh;q=0.8,en-US;q=0.6,en;q=0.4,zh-CN;q=0.2',
					# 'Cache-Control'	=> 'max-age=0',
					# 'Connection'		=> 'keep-alive',
					# 'Content-Type'	=> 'application/x-www-form-urlencoded',
					# 'DNT'			=> '1',
					# 'Origin'		=> 'https://www.rapid7.com',
					# 'Referer'		=> 'https://www.rapid7.com/register/metasploit-trial.jsp?product',
					'User-Agent'		=> random_user_agent()
				);

				foreach( $headers as $key => $value ) {
					$this->curl_resource->setHeader( $key, $value );
				}
			echo "DONE !\n";

			# sending the POST request to retrieve the HTML raw code
			echo "[+] sending the registration data to online form .. ";
				$this->curl_resource->post( $this->form_address, http_build_query( $payload ) );
			echo "DONE !\n";
		}
	}
?>
