<?php
	require_once "config.php";
	date_default_timezone_set('ASIA/JAKARTA');
	
	$request_method = $_SERVER['REQUEST_METHOD'];
	$uri = explode('/', $_SERVER['REQUEST_URI']);
	$uri2 = explode('?', $uri[count($uri)-1]);
	
	if ($request_method == 'POST') {
		switch ($uri[count($uri)-1]) {
			case 'get_token':
				$cek_header = get_header();
				$username = (@$cek_header['Username']) ? $cek_header['Username'] : '';
				$password = (@$cek_header['Password']) ? $cek_header['Password'] : '';

				if (is_null($username) || is_null($password)) {
					$result = not_found_params($validasi);
				}
				else {
					$input = 
					array(
						"username" => $username,
						"password" => $password
					);
					$validasi = validate($input, array("username","password"));
					
					if ($validasi === true) {
						$username 	= ($input['username'] == $username) ? $username : '';
						$password 	= ($input['password'] == $password) ? $password : '';

						if(empty($username)) {
							http_response_code(200);
							$data['message'] = 'Username tidak valid';
							$data['code'] = 201;
							$result = array("metadata" => $data);
							
							header("Content-Type: application/json; charset=UTF-8");
							echo json_encode($result);
							exit;
						}
						
						if(empty($password)) {
							http_response_code(200);
							$data['message'] = 'Password tidak valid';
							$data['code'] = 201;
							$result = array("metadata" => $data);
							
							header("Content-Type: application/json; charset=UTF-8");
							echo json_encode($result);
							exit;
						}
						
						$url = $ws_url . 'fndata.php?fn=get_token';
						$data = array("username" => $username,"password" => $password);
						
						$response = post_data_curl($url, $data);
						$result = json_decode($response);
						
						if (is_object($result)) {
							if ($result->metadata->code == 200) {
								$message = $result->metadata->message;
								$metadata = $result->metadata;
								ws_activity('token',$message, $data);
								$result = array("metadata" => $metadata);			
							}
							else {
								http_response_code(200);
								$message = $result->metadata->message;
								$metadata['message'] = $message;
								$metadata['code'] = 201;
								ws_activity('token',$message, $data);
								$result = array("metadata" => $metadata);			
							}
						}
						else {
							http_response_code(200);
							$message = $response;
							$metadata['message'] = $message;
							$metadata['code'] = 201;
							ws_activity('token',$message, $data);
							$result = array("metadata" => $metadata);
						}					
					} 
					else {
						$result = not_found_params($validasi);
					}
				}
			break;
			case 'sendmail':
				$inputJSON = file_get_contents('php://input');
				$input = json_decode($inputJSON, TRUE);

				if ($input == NULL) {
					http_response_code(200);
					$data['message'] = 'Tidak ada parameter inputan';
					$data['code'] = 201;
					$result = array("metadata" => $data);
					
					header("Content-Type: application/json; charset=UTF-8");
					echo json_encode($result);
					exit;
				}
				
				$validasi = validate($input, array("emailTo", "subject", "message"));
				
				if ($validasi === true) {
					$emailTo = $input['emailTo'];
					$subject = $input['subject'];
					$message = $input['message'];

					$cek_header = get_header();
					 
					if(empty($cek_header['X-Token'])) {
						http_response_code(200);
						$data['message'] = 'Token tidak ditemukan';
						$data['code'] = 201;
						$result = array("metadata" => $data);
						
						header("Content-Type: application/json; charset=UTF-8");
						echo json_encode($result);
						exit;
					}else {
						$token = $cek_header['X-Token'];
					}
					
					if(empty($emailTo)) {
						http_response_code(200);
						$data['message'] = 'Email to is NULL';
						$data['code'] = 201;
						$result = array("metadata" => $data);
						
						header("Content-Type: application/json; charset=UTF-8");
						echo json_encode($result);
						exit;
					}

					if(empty($subject)) {
						http_response_code(200);
						$data['message'] = 'Email subject is NULL';
						$data['code'] = 201;
						$result = array("metadata" => $data);
						
						header("Content-Type: application/json; charset=UTF-8");
						echo json_encode($result);
						exit;
					}

					if(empty($message)) {
						http_response_code(200);
						$data['message'] = 'Email message is NULL';
						$data['code'] = 201;
						$result = array("metadata" => $data);
						
						header("Content-Type: application/json; charset=UTF-8");
						echo json_encode($result);
						exit;
					}
															
					$url = $ws_url . 'fndata.php?fn=sendmail';					
					$data = array("emailTo" => $emailTo, "subject" => $subject, "message" => $message, "token" => $token);

					$data_request = json_encode(
					array(
						"emailTo" => $emailTo,
						"subject" => $subject,
						"message" => $message
					));
					
					$metadata 	= post_data_curl($url, $data);
					$result 	= json_decode($metadata);

					if (is_object($result)) {
						if ($result->metadata->code == 200) {
							$message = $result->metadata->message;
							$metadata = $result->metadata;
							ws_activity('message_replies',$message, $data_request);
							$result = array("metadata" => $metadata);			
						}
						else {
							http_response_code(200);
							$message = $result->metadata->message;
							$code = ($result->metadata->code) ? $result->metadata->code : '201';
							// var_dump($message, $code);die();
							ws_activity('message_replies',$message, $data_request);
							$result = array("metadata" => array("code" => $code, "message" => $message));			
						}
					}
					else {
						http_response_code(200);
						$message = $metadata;
						ws_activity('message_replies',$message, $data_request);
						$result = array("metadata" => array("code" => 201, "message" => $message));
					}
										
				} 
				else {
					$result = not_found_params($validasi);
				}

			break;
			default:	
				http_response_code(404);
				$result = array("status" => "404", "message" => "Alamat tidak ditemukan.");
		}
	} 
	else {
		http_response_code(404);
		$result = array("status" => "404", "message" => "Alamat tidak ditemukan.");
	}

	function unsupported_method($method_name) {
		http_response_code(404);
		return array("status" => "404", "message" => "Metode ".$method_name." tidak didukung");
	}

	function validate($array, $mandatoryKeys) {
		$not_found_keys = array();
		foreach ($mandatoryKeys as $key) {
			if (!array_key_exists($key, $array)) {
				array_push($not_found_keys, $key);
			}
		}
		return count($not_found_keys) > 0 ? $not_found_keys : true;
	}

	function not_found_params($params_name) {
		http_response_code(404);
		return array("status" => "404", "message" => "Tidak ditemukan parameter ".implode(", ", $params_name));
	}

	function post_data_curl($url, $data) {
		
		$ch = curl_init( $url );
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 400);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$response = curl_exec( $ch );
		curl_close($ch);
		
		return $response;
	}
	
	function get_data_jkn($url, $data) {
		$url = $url;
		$params = http_build_query($data);
		$url .= $params;
		
		$ch = curl_init( $url );
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec( $ch );
		curl_close($ch);
		
		return $response;
	}
	
	function get_header() {

        foreach ($_SERVER as $name => $value)
       {
		
			
           if (substr($name, 0, 5) == 'HTTP_')
           {
               $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
               $headers[$name] = $value;
           } else if ($name == "CONTENT_TYPE") {
               $headers["Content-Type"] = $value;
           } else if ($name == "CONTENT_LENGTH") {
               $headers["Content-Length"] = $value;
           }else if ($name == "X-Token") {
               $headers["X-Token"] = $value;
		   }else if ($name == "X-Username") {
               $headers["X-Username"] = $value;
           }else if ($name == "x-username") {
				$headers["x-username"] = $value;
			}else if ($name == "x-password") {
				$headers["x-password"] = $value;
		 	}

       }
       return $headers;
    }
	
	function ws_activity($jns_activity, $pesan, $req='') {
		$req = json_encode($req);
		$emr_path = "log/log_" . date('Ymd') . ".txt";
		
		$emr_str = "\n$jns_activity " . date('d-m-Y H:i:s') . " => result:$pesan";

		$emr_str = "Waktu : ".date('d-m-Y H:i:s')."\n   Function : ".$jns_activity."\n   Request : ".$req."\n   Response : ".$pesan."\n\n-----\n\n";
		
		write_file($emr_path, $emr_str, 'at');
	}
	
	function write_file($path, $data, $mode = 'wb') {
		if ( ! $fp = @fopen($path, $mode)) {
			return FALSE;
		}

		flock($fp, LOCK_EX);

		for ($result = $written = 0, $length = strlen($data); $written < $length; $written += $result) {
			if (($result = fwrite($fp, substr($data, $written))) === FALSE) {
				break;
			}
		}

		flock($fp, LOCK_UN);
		fclose($fp);

		return is_int($result);
	}

	header("Content-Type: application/json; charset=UTF-8");
	echo json_encode($result);
?>