<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\SMTP;
	use PHPMailer\PHPMailer\Exception;

	if(!isset($_GET['fn'])) {$_GET['fn'] = "" ;}
	if(function_exists($_GET['fn'])) {
		$_GET['fn']();
	}

	function get_token() {
		$username = $_POST['username'];
		$password = $_POST['password'];
			
		$result = array();

		$log_arr = db_user_login($username, $password);
		
		if ($log_arr['metadata_code'] == 200) {
			$token = generate_token($username, $password);

			$data['metadata'] = 
				array(
					'code' 		=> 200,
					'message' 	=> $log_arr['metadata_message'],
					'response' 	=> 
						array(
							'token' => $token
						)
				);
		}
		else {
			$data['metadata'] = 
				array(
					'code' 		=> 201,
					'message' 	=> $log_arr['metadata_message']
				);
		}
		
		$result = $data;

		echo json_encode($result);
	}

	function sendmail() {
		require_once 'config.php';

		$result = array();

		$istoken_clien = $_POST['token'];
		$istoken_server = generate_token($username, $password);

		if($istoken_clien != $istoken_server) {
			$data['metadata'] = 
				array(
					'code' 		=> 201,
					'message' 	=> "Invalid token"
				);
		}
		else {
			$emailTo = $_POST['emailTo'];
			$subject = $_POST['subject'];
			$message = $_POST['message'];

			$log_arr = db_sendmail($emailTo, $subject, $message);
			
			if ($log_arr['metadata_code'] == 200) {
				$data['metadata'] = 
					array(
						'code' 		=> 200,
						'message' 	=> $log_arr['metadata_message'],
						'response' 	=> $log_arr['response']
					);
			}
			else {
				$data['metadata'] = 
					array(
						'code' 		=> $log_arr['metadata_code'],
						'message' 	=> $log_arr['metadata_message']
					);
			}
		}
		
		$result = $data;

		echo json_encode($result);
	}

	// ---------------------
	// ----- get to db -----
	// ---------------------

	function db_user_login() {
		try {
		    //include database
		    include_once 'koneksi.php';

		    //mulai mengkoneksikan database
		    $database = new Database();
		    $conn_1     = $database->getConnection();

		    $results    = '';

		    $data_category = "SELECT * FROM users";

		    $stmt_datacategory = $conn_1->prepare($data_category);
		    $stmt_datacategory->execute();

		    $stmt_datacategory_rows   = $stmt_datacategory->rowCount();
		    $result_stmt_datacategory = $stmt_datacategory->fetchAll();

		    // var_dump($stmt_datacategory_rows);die();

		    if ($stmt_datacategory_rows > 0) {
				$data = 
					array(
						'metadata_code' => 200,
						'metadata_message' => "OK",
						'response' => "Login berhasil."
					);
		    }
		    else {
		    	$data = 
					array(
						'metadata_code' => 201,
						'metadata_message' => "Username & Password tidak ditemukan!"
					);
		    }
		}
		catch (PDOException $e) {
			$data = 
				array(
					'metadata_code' => 201,
					'metadata_message' => 'Connection error: '.$exception->getMessage()
				);
		}

		// var_dump($data);die();

		return $data;
	}

	function db_sendmail($emailTo, $subject, $message) {
		require 'config.php';
		require 'vendor/autoload.php';

		$mail = new PHPMailer(true);

		try {
		    $mail->SMTPDebug = 0;
		    $mail->isSMTP();
		    $mail->Host       = 'smtp.googlemail.com';
		    $mail->SMTPAuth   = true;
		    $mail->Username   = $mail_username;
		    $mail->Password   = $mail_password;
		    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		    $mail->Port       = 465;

		    $mail->setFrom($mail_username, $mail_name);
		    $mail->addAddress($emailTo);

		    $mail->isHTML(true);
		    $mail->Subject = $subject;
		    $mail->Body    = $message;
		    $mail->AltBody = $message;

		    $mail->send();
		    $data = 
				array(
					'metadata_code' => 200,
					'metadata_message' => "OK",
					'response' => "Message has been sent"
				);
		}
		catch(Exception $e) {
			$data = 
				array(
					'metadata_code' => 201,
					'metadata_message' => "Message could not be sent. Mailer Error : ".$mail->ErrorInfo
				);
		}
		
		return $data;
	}

	function generate_token($client_id, $client_secret) {
		$tgl = date('Y-m-d');
		
		// get the local secret key
		// $secret = getenv('SECRET');
		$secret = $client_secret;

		// Create the token header
		$header = json_encode([
			'typ' => 'JWT',
			'alg' => 'HS256'
		]);

		// Create the token payload
		$payload = json_encode([
			'sub' => $client_id,
			'role' => 'admin',
			'exp' => timestmp($tgl)
		]);

		// Encode Header
		$base64UrlHeader = base64UrlEncode($header);

		// Encode Payload
		$base64UrlPayload = base64UrlEncode($payload);

		// Create Signature Hash
		$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

		// Encode Signature to Base64Url String
		$base64UrlSignature = base64UrlEncode($signature);

		// Create JWT
		$jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
		
		return $jwt;
	}

	function encrypt($userid, $username, $password) {
		$plain = substr($username, 0, 3) . substr($password, 0, 3) . substr($userid, 0, 3);
		
		if((intval($userid) % 2) > 0)
		{
		}elseif((intval($userid) % 4) > 0 || (intval($userid) % 6) > 0)
		{
			$plain = substr($userid, 0, 3) . substr($username, 0, 3) . substr($password, 0, 3);
		} else {
			$plain = substr($password, 0, 3) . substr($userid, 0, 3) . substr($username, 0, 3);
		}
		
		$pass = substr($username, 3, strlen($username) - 3) . substr($password, 3, strlen($password) - 3) . substr($userid, 3, 1);
		
		$idpass = $plain . $pass;
		$panjang = strlen($idpass);
		
		$char = array();
		$i_encrypt = array();
		$c_encrypt = array();
		
		$i = 0;
		
		While ($panjang-1 >= $i){
			
			 $char[$i]    = substr($idpass, $i, 1);
			 $i_encrypt[$i] = ord($char[$i]) + 128;
			 $c_encrypt[$i] = chr($i_encrypt[$i]);
			 
			 $i++;
		}
		$i = 0;
		$encrypt = "";
		While ($panjang-1 >= $i){
			$encrypt .= $c_encrypt[$i];
			$i++;
		}
		
		$res = $encrypt;
				
		return $res;
	}

	function base64UrlEncode($text) {
		return str_replace(
			['+', '/', '='],
			['-', '_', ''],
			base64_encode($text)
		);
	}

	function timestmp($date) {
		$set_date = new DateTime($date);
		$set_date->setTimezone(new DateTimeZone('Asia/Jakarta'));
		$set_timestamp = strtotime($set_date->format('Y-m-d H:i:sP')) * 1000;

		return $set_timestamp;
	}

	function timestmp_to_date($timestmp) {
		$milisec = $timestmp / 1000;
		$datetime = date('Y-m-d H:i:s', $milisec);
		
		return $datetime;	
	}

	function generate_result($sql) {
		$result = @sybase_query($sql);
		
		$result_object = array();
		while ($row = @sybase_fetch_object($result)) {
			$result_object[] = $row;
		}

		@sybase_close($conn_id); 
		return $result_object[0];
	}
?>