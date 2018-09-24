<?php

	/* API Transitabile V 1.0 */
	
	// Allow Cross Origin:
	header("Access-Control-Allow-Origin: *");
	// header("access-control-allow-origin: https://pagseguro.uol.com.br");
	// header("access-control-allow-origin: https://sandbox.pagseguro.uol.com.br");
	
	// UTF8:
	header("Content-type: text/html; charset=utf-8");
	
	// Display Errors:
	error_reporting( E_ALL );

	// Connection & Requests:
	$conn = null;

	$userid = null;
	$type = null;
	
	$request = new stdClass();
	$request->status = "failed";

	// PagSeguro Requests:
	if( !empty( $_POST['notificationType'] ) && !empty( $_POST['notificationCode'] ) ) {

		$notificationCode = str_replace("-","", $_POST['notificationCode']);

		// Define time:
		date_default_timezone_set('America/Sao_Paulo');

		// Payment Support:
		require_once('psconfig.php');
		require_once('psutils.php');

		$params = array(
			'email' => $PAGSEGURO_EMAIL,
			'token' => $PAGSEGURO_TOKEN
		);
		$header = array();

		$header = array('Content-Type' => 'application/json; charset=UTF-8;');
		$response = curlExec("https://ws.sandbox.pagseguro.uol.com.br/v2/transactions/notifications/".$notificationCode."?email=".$PAGSEGURO_EMAIL."&token=".$PAGSEGURO_TOKEN);
		$transaction_obj = simplexml_load_string($response);

		$ref = $transaction_obj->reference;

		$header = array('Content-Type' => 'application/json; charset=UTF-8;');
		$response = curlExec("https://ws.sandbox.pagseguro.uol.com.br/v3/transactions?email=".$PAGSEGURO_EMAIL."&token=".$PAGSEGURO_TOKEN."&reference=".$ref);
		$transaction_obj = simplexml_load_string($response);

		$status = $transaction_obj->transactions->transaction->status;

		$finalUserID = null;
		$finalValue = null;

		$sql = "SELECT user_id, value FROM orders WHERE code = '".$ref."'";
		$servername = "localhost";
		$username = "trans593_userbh";
		$password = "NGc0MwTvaI0r";
		$databasename = "trans593_bh";
		$conn = new mysqli( $servername, $username, $password, $databasename );
		$result = $conn->query($sql);

		if ($result->num_rows > 0) {

			$row = $result->fetch_assoc();
			$finalUserID = $row["user_id"];
			$finalValue = $row["value"];

		}

		if( $status == 3 || $status == 4 ){
			// PAGAMENTO CONFIRMADO
			// Adiciona valor a carteira do user:
			$sql = "UPDATE users SET balance = balance + ".$finalValue." WHERE id = ".$finalUserID;
			$result = $conn->query($sql);
		} else {
			//PAGAMENTO PENDENTE...
		}

		$statusText = "Processando";

		switch ($status) {
			case 1:
				$statusText = "Processando";
				break;
			case 2:
				$statusText = "Processando";
				break;
			case 3:
				$statusText = "Paga";
				break;
			case 4:
				$statusText = "Disponível";
				break;
			case 5:
				$statusText = "Em disputa";
				break;
			case 6:
				$statusText = "Devolvida";
				break;
			case 7:
				$statusText = "Cancelada";
				break;
			default:
				$statusText = "Processando";
		}

		$sql = "UPDATE orders SET status = '".$statusText."', modified = 'NOW()' WHERE code = '".$ref."'";
		$result = $conn->query($sql);

		$msg = "Estado da compra atualizada para: ".$statusText;

		saveLog( $conn, $finalUserID, "Compra", "" . $msg );

	}

	// Requests processing:
	if ( empty( $_GET['userid'] ) || empty( $_GET['type'] ) ) {
	
		$request->message = 'Erro: Parâmetros de requisição inválidos.';
		$request = json_encode( $request );
		showResponse( $request );
		
	} else {
		
		$servername = "localhost";
		$username = "trans593_userbh";
		$password = "NGc0MwTvaI0r";
		$databasename = "trans593_bh";
		
		$userid = $_GET['userid'];
		$type = $_GET['type'];
		
		$conn = new mysqli( $servername, $username, $password, $databasename );
		
		if ( $conn->connect_error ) {
			
			$request->message = 'Erro: Tipo de requisição inválido ('.$type.').';
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();
			
		} else {
			
			// Connection ok, checking request type.
			// echo 'API Ready';
			
			switch ( $type ) {
				
				case "login":
					checkLogin( $conn );
					break;
					
				case "signup":
					signUp( $conn );
					break;
					
				case "recover":
					recover( $conn );
					break;
					
				case "addvehicle":
				// LA
					addVehicle( $conn );
					break;
					
				case "updateuserinfo":
				// LA
					updateUserInfo( $conn );
					break;
					
				case "getvehicles":
				// LA
					getVehicles( $conn );
					break;
					
				case "getvehiclesfull":
				// LA
					getVehiclesFull( $conn );
					break;
					
				case "updatevehicle":
				// LA
					updateVehicle( $conn );
					break;
					
				case "deletevehicle":
				// LA
					deleteVehicle( $conn );
					break;
					
				case "report":

					report( $conn );
					break;
					
				case "reportvehicle":
					reportVehicle( $conn );
					break;
					
				case "contact":
					contact( $conn );
					break;

				case "autorizacao":
				// LA
					getAutorizacao();
					break;
					
				case "getlogs":
					getUserLogs( $conn );
					break;

				case "getAddlogs":
					getUserAddLogs( $conn );
					break;
				
				case "gettic":
				// LA
					getTic($conn);
					break;

				case "updateSecretKey":
					updateSecretKey($conn, "234");
					break;

				case "getCurrentSecretKey":
					getCurrentSecretKey($conn);
					break;

				case "setCurrentSecretKey":
					setCurrentSecretKey($conn);
					break;

				case "getPagSeguroSession":
					getPagSeguroSession($conn);
					break;
				
				case "getBalance":
				// LA
					getBalance($conn);
					break;
				
				case "pay":
					pay($conn);
					break;

				case "park":
					park($conn);
					break;

				case "setVehicleStatus":
				// LA
					setVehicleStatus($conn);
					break;
				
				case "getStatusByReference":
					getStatusByReference($conn);
					break;					
					
				default:
					$request->message = 'Erro: Tipo de requisição inválido ('.$type.').';
					$request = json_encode( $request );
					showResponse( $request );
					$conn->close();
					
			}			
			
		}
		
	}

	// Server requests:

	function getPagSeguroSession($conn) {

		// Request example: http://transitabile.com.br/api.php?userid=1&type=getPagSeguroSession

		// Define time:
		date_default_timezone_set('America/Sao_Paulo');

		// Payment Support:
		require_once('psconfig.php');
		require_once('psutils.php');
		
		$params = array(
			'email' => $PAGSEGURO_EMAIL,
			'token' => $PAGSEGURO_TOKEN
		);
		$header = array();

		$response = curlExec($PAGSEGURO_API_URL."/sessions", $params, $header);
		$json = json_decode(json_encode(simplexml_load_string($response)));
		$sessionCode = $json->id;

        echo $sessionCode;

	}

	function pay($conn) {

		// Request example: http://transitabile.com.br/api.php?userid=22&type=pay&selectedCredits=10&brand=visa&token=fbeba73f01f049ea8f62bf7d87c15c77&senderHash=6cf00b801aff66cb8b4f0083e1325144b6d25764f83dd3858dd946262616f93b&amount=100.00&shippingCoast=1.00&cardNumber=4111%201111%201111%201111&cardExpiry=12%2F2030&cardCVC=123&installments=1&installmentValue=101

		// Define time:
		date_default_timezone_set('America/Sao_Paulo');

		// Payment Support:
		require_once('psconfig.php');
		require_once('psutils.php');

		$params = array(
			'email' => $PAGSEGURO_EMAIL,
			'token' => $PAGSEGURO_TOKEN
		);
		$header = array();

		$userid = $_GET['userid'];
		$selectedCredits = $_GET['selectedCredits'];
		$brand = $_GET['brand'];
		$token = $_GET['token'];
		$amount = $_GET['amount'];
		$senderHash = $_GET['senderHash'];
		$shippingCoast = $_GET['shippingCoast'];
		$cardNumber = $_GET['cardNumber'];
		$cardExpiry = $_GET['cardExpiry'];
		$cardCVC = $_GET['cardCVC'];
		$installments = $_GET['installments'];
		$installmentValue = $_GET['installmentValue'];

		$creditCardToken = htmlspecialchars($token);
		$senderHash = htmlspecialchars($senderHash);
	
		$itemAmount = number_format($amount, 2, '.', '');
		$shippingCoast = number_format($shippingCoast, 2, '.', '');
		$installmentValue = number_format($installmentValue, 2, '.', '');
		$installmentsQty = $installments;

		$referenceCode = generateRandomString();
	
		$params = array(
			'email'                     => $PAGSEGURO_EMAIL,  
			'token'                     => $PAGSEGURO_TOKEN,
			'creditCardToken'           => $creditCardToken,
			'senderHash'                => $senderHash,
			'receiverEmail'             => $PAGSEGURO_EMAIL,
			'paymentMode'               => 'default', 
			'paymentMethod'             => 'creditCard', 
			'currency'                  => 'BRL',
			// 'extraAmount'               => '1.00',
			'itemId1'                   => '0001',
			'itemDescription1'          => 'Transitabile',  
			'itemAmount1'               => $itemAmount,  
			'itemQuantity1'             => 1,
			'reference'                 => $referenceCode,
			'senderName'                => 'Transitabile App',
			'senderCPF'                 => '54793120652',
			'senderAreaCode'            => 83,
			'senderPhone'               => '999999999',
			'senderEmail'               => 'v97275341714831719209@sandbox.pagseguro.com.br',
			'shippingAddressStreet'     => 'Address',
			'shippingAddressNumber'     => '1234',
			'shippingAddressDistrict'   => 'Bairro',
			'shippingAddressPostalCode' => '58075000',
			'shippingAddressCity'       => 'João Pessoa',
			'shippingAddressState'      => 'PB',
			'shippingAddressCountry'    => 'BRA',
			'shippingType'              => 1,
			'shippingCost'              => $shippingCoast,
			'maxInstallmentNoInterest'      => 2,
			'noInterestInstallmentQuantity' => 2,
			'installmentQuantity'       => $installmentsQty,
			'installmentValue'          => $installmentValue,
			'creditCardHolderName'      => 'Chuck Norris',
			'creditCardHolderCPF'       => '54793120652',
			'creditCardHolderBirthDate' => '01/01/1990',
			'creditCardHolderAreaCode'  => 83,
			'creditCardHolderPhone'     => '999999999',
			'billingAddressStreet'     => 'Address',
			'billingAddressNumber'     => '1234',
			'billingAddressDistrict'   => 'Bairro',
			'billingAddressPostalCode' => '58075000',
			'billingAddressCity'       => 'João Pessoa',
			'billingAddressState'      => 'PB',
			'billingAddressCountry'    => 'BRA'
		);
	
		$header = array('Content-Type' => 'application/json; charset=UTF-8;');
		$response = curlExec($PAGSEGURO_API_URL."/transactions", $params, $header);

		$xml_obj = simplexml_load_string($response);
		$transactionCode = str_replace("-","", $xml_obj->code);
		echo "Transaction code = ".$transactionCode;

		$sql = "INSERT INTO orders (user_id, code, value, status, created, modified ) VALUES ('".$userid."', '".$referenceCode."', '".$selectedCredits."', 'Processando', NOW(), NOW())";
		$result = $conn->query($sql);

		saveLog( $conn, $userid, "Pagamento", "Comprou R$ ".$selectedCredits." em créditos.");

	}

	function generateRandomString($length = 40) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	
	function getStatusByReference($conn) {

		// Request example: http://transitabile.com.br/api.php?userid=22&type=getStatusByReference&ref=601097D70B9846E3A83CFBDD94DE0912

		// Define time:
		date_default_timezone_set('America/Sao_Paulo');

		// Payment Support:
		require_once('psconfig.php');
		require_once('psutils.php');

		$params = array(
			'email' => $PAGSEGURO_EMAIL,
			'token' => $PAGSEGURO_TOKEN
		);
		$header = array();

		$ref = $_GET['ref'];
	
		$header = array('Content-Type' => 'application/json; charset=UTF-8;');
		$response = curlExec("https://ws.sandbox.pagseguro.uol.com.br/v3/transactions/".$ref."?email=".$PAGSEGURO_EMAIL."&token=".$PAGSEGURO_TOKEN);
		$transaction_obj = simplexml_load_string($response);
		
		/*

		Check Status:

		1	Aguardando pagamento: o comprador iniciou a transação, mas até o momento o PagSeguro não recebeu nenhuma informação sobre o pagamento.
		2	Em análise: o comprador optou por pagar com um cartão de crédito e o PagSeguro está analisando o risco da transação.
		3	Paga: a transação foi paga pelo comprador e o PagSeguro já recebeu uma confirmação da instituição financeira responsável pelo processamento.
		4	Disponível: a transação foi paga e chegou ao final de seu prazo de liberação sem ter sido retornada e sem que haja nenhuma disputa aberta.
		5	Em disputa: o comprador, dentro do prazo de liberação da transação, abriu uma disputa.
		6	Devolvida: o valor da transação foi devolvido para o comprador.
		7	Cancelada: a transação foi cancelada sem ter sido finalizada.

		*/

		$status = $transaction_obj->status;

		$finalUserID = null;
		$finalValue = null;

		$sql = "SELECT user_id, value FROM orders WHERE code = '".$ref."'";
		$result = $conn->query($sql);

		if ($result->num_rows > 0) {

			$row = $result->fetch_assoc();
			$finalUserID = $row["user_id"];
			$finalValue = $row["value"];

		}

		if( $status == 3 || $status == 4 ){
			// PAGAMENTO CONFIRMADO
			// Adiciona valor a carteira do user:
			$sql = "UPDATE users SET balance = balance + ".$finalValue." WHERE id = ".$finalUserID;
			$result = $conn->query($sql);
		} else {
			//PAGAMENTO PENDENTE...
		}

		$statusText = "Processando";

		switch ($status) {
			case 1:
				$statusText = "Processando";
				break;
			case 2:
				$statusText = "Processando";
				break;
			case 3:
				$statusText = "Paga";
				break;
			case 4:
				$statusText = "Disponível";
				break;
			case 5:
				$statusText = "Em disputa";
				break;
			case 6:
				$statusText = "Devolvida";
				break;
			case 7:
				$statusText = "Cancelada";
				break;
			default:
				$statusText = "Processando";
		}

		$sql = "UPDATE orders SET status = '".$statusText."', modified = 'NOW()' WHERE code = '".$ref."'";
		$result = $conn->query($sql);

		$msg = "Estado da compra atualizada para: ".$statusText;

		saveLog( $conn, $finalUserID, "Compra", "" . $msg );

	}

	function updateSecretKey( $conn, $newKey ) {

		// Request example: http://transitabile.com.br/api.php?userid=1&type=updateSecretKey
				
		$sql = "UPDATE secretkey SET value='".$newKey."' WHERE id = '1'";
		$result = $conn->query($sql);
		
	}

	function getCurrentSecretKey( $conn ) {

		// Request example: http://transitabile.com.br/api.php?userid=1&type=getCurrentSecretKey
				
		$sql = "SELECT value FROM secretkey WHERE id = '1'";
		$result = $conn->query($sql);

		$finalResult = null;

		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			//echo $row["value"];
			$finalResult = $row["value"];
			return $finalResult;
		} else {
			//echo "0 results";
			return $finalResult;
		}
		
	}

	function setCurrentSecretKey( $conn ) {

		// Acessa o servidor de Bragança, obtem uma nova chave de segurança e atualiza a atual;
		// Esse processo é feito por Cron Job a cada 10 dias. 
		// ATENÇÃO: Só é permitida uma requesta a cada 10 dias.

		// Request example: http://transitabile.com.br/api.php?userid=1&type=setCurrentSecretKey

		$newKey = null;

		$curl = curl_init();

		curl_setopt_array($curl, array(
		CURLOPT_URL => "https://devapibraganca.estacionelegal.com.br/access_token",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"grant_type\"\r\n\r\nclient_credentials\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"client_id\"\r\n\r\ntransitable\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"client_secret\"\r\n\r\n66YVpx9WE7jR9\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"scope\"\r\n\r\napi\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
		CURLOPT_HTTPHEADER => array(
			"Cache-Control: no-cache",
			"Postman-Token: 9297dabb-2059-42c5-b695-4aee43478d90",
			"content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
		),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {
			$json = json_decode($response, true);
			$newKey = $json['access_token'];
		}
				
		updateSecretKey( $conn, $newKey );
		
	}
	
	// User Requests:

	function getTicket($conn){

		$request = new stdClass();
		$request->status = "failed";
			
		if ( empty( $_GET['vehicleid'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'getTickets' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {

			$vehicleid = strtolower( $_GET['vehicleid'] );

			$sql = "SELECT * FROM tickets WHERE vehicle_id = '".$vehicleid."'";
			$result = $conn->query( $sql );

			$results = $result->num_rows;

			$request->status = "success";
			$request->results = $results;
			$request->message = $results." tickets encontrados.";

			$list = array();

			while( $row = $result->fetch_assoc() ) {

				$list[] = array('id' => $row["id"], 'userid' => $row["user_id"], 'vehicleid' => $row["vehicle_id"], 'parklotid' => $row["parklot_id"], 'type' => $row["type"], 'value' => $row["value"], 'total_value' => $row["total_value"], 'status' => $row["status"], 'created' => $row["created"], 'modified' => $row["modified"], 'iniciohorario' => $row["iniciohorario"], 'fimhorario' => $row["fimhorario"], 'tempopermanencia' => $row["tempopermanencia"]);

			}

			$request->list = $list;

			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();		

		}
	}

	function checkLogin( $conn ) {
		
		// Request example: http://transitabile.com.br/api.php?userid=1&type=login&email=andre@gameloop.com.br&pass=p
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['cpf'] ) || empty( $_GET['pass'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'login' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
		
			$cpf = strtolower( $_GET['cpf'] );
			$pass = strtolower( $_GET['pass'] );
			
			$sql = "SELECT * FROM users WHERE cpf = '".$cpf."' AND password = '".$pass."'";
			
			$result = $conn->query( $sql );
			
			if ( $result->num_rows > 0 ) {
				
				// Get user information				
				$row = $result->fetch_assoc();
	
				$userInfo = new stdClass();
				$userInfo->id = $row["id"];
				$userInfo->type = $row["type"];
				$userInfo->name = convert_from_latin1_to_utf8_recursively($row["name"]);
				$userInfo->email = $row["email"];
				$userInfo->cpf = $row["cpf"];
				$userInfo->password = $row["password"];
				$userInfo->birthdate = $row["birthdate"];
				$userInfo->phone = $row["phone"];
				$userInfo->balance = $row["balance"];
				$userInfo->created = $row["created"];
				$userInfo->modified = $row["modified"];				
				
				$request->status = "success";
				$request->message = "Bem vindo ao Transitabile.";
				$request->info = $userInfo;
				$request = json_encode( $request );
				showResponse( $request );
				saveLog( $conn, $row["id"], "Login", "Acessou o sistema com sucesso." );
				$conn->close();
				
				/*switch (json_last_error()) {
					case JSON_ERROR_NONE:
						echo ' - No errors';
					break;
					case JSON_ERROR_DEPTH:
						echo ' - Maximum stack depth exceeded';
					break;
					case JSON_ERROR_STATE_MISMATCH:
						echo ' - Underflow or the modes mismatch';
					break;
					case JSON_ERROR_CTRL_CHAR:
						echo ' - Unexpected control character found';
					break;
					case JSON_ERROR_SYNTAX:
						echo ' - Syntax error, malformed JSON';
					break;
					case JSON_ERROR_UTF8:
						echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
					break;
					default:
						echo ' - Unknown error';
					break;
				}*/
					
			} else {
				
				$request->status = "failed";
				$request->message = "Erro: cpf/cnpj ou senha incorretos.";
				$request = json_encode( $request );
				showResponse( $request );
				saveLog( $conn, 0, "Login", "Erro ao fazer login, cpf/cnpj ou senha incorretos." );
				$conn->close();
				
			}			
		
		}
		
	}
	
	function convert_from_latin1_to_utf8_recursively($dat)
	{
		if (is_string($dat))
			return utf8_encode($dat);
		if (!is_array($dat))
			return $dat;
		$ret = array();
		foreach ($dat as $i => $d)
			$ret[$i] = self::convert_from_latin1_to_utf8_recursively($d);
		return $ret;
	}
	
	function signUp( $conn ) {
		
		// Request example: http://transitabile.com.br/api.php?userid=1&type=signup&name=n&email=e&pass=p&cpf=c
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['name'] ) || empty( $_GET['email'] ) || empty( $_GET['pass'] ) || empty( $_GET['cpf'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'signup' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$name = strtolower( $_GET['name'] );
			$email = strtolower( $_GET['email'] );
			$pass = strtolower( $_GET['pass'] );
			$cpf = strtolower( $_GET['cpf'] );
			
			// Check if email or cpf already exists
			$sql = "SELECT * FROM users WHERE email = '".$email."' OR cpf = '".$cpf."'";
			
			$result = $conn->query( $sql );
			
			if ( $result->num_rows > 0 ) {
				
				$request->message = "Erro: Email e/ou CPF já cadastrados, por favor tente novamente.";
				$request = json_encode( $request );
				showResponse( $request );
				$conn->close();
					
			} else {
				
				// Subscribe new user				
				$sql = "INSERT INTO users (type, name, email, cpf, password, created, modified ) VALUES ('user', '".$name."', '".$email."', '".$cpf."', '".$pass."', NOW(), NOW())";
				$result = $conn->query($sql);
				
				if( $result === true ) {
					
					$request->status = "success";
					$request->message = "Cadastro realizado com sucesso.";
					$request = json_encode( $request );
					showResponse( $request );
					saveLog( $conn, 0, "SignUp", "Novo usuario cadastrado - CPF: ".$cpf );
					$conn->close();
					
				} else {
					
					$request->message = "Erro: Não foi possível realizar o cadastro, tente novamente mais tarde (".$conn->error.")";
					$request = json_encode( $request );
					showResponse( $request );
					$conn->close();
					
				}				

			}		
			
		}
		
	}
	
	function recover( $conn ) {
		
		// Request example: http://transitabile.com.br/api.php?userid=1&type=recover&email=e
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['email'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'recover' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$email = strtolower( $_GET['email'] );
			
			$sql = "SELECT password FROM users WHERE email = '".$email."'";
			
			$result = $conn->query( $sql );
			
			if ( $result->num_rows > 0 ) {
				
				$row = $result->fetch_assoc();
				
				$to = $email;
				$subject = "Transitabile - Recuperação de Senha";
				$txt = "Foi solicitada uma recuperação de senha no aplicativo Transitabile, segue sua senha: ".$row["password"];
				$headers = "From: recover@transitabile.com.br";

				if (mail( $to, $subject, $txt, $headers )) {
					
					$request->status = "success";
					$request->message = "Foi enviado um email com a recuperação de senha, por favor verifique a caixa de entrada e de span.";
					$request = json_encode( $request );
					showResponse( $request );
					saveLog( $conn, 0, "Recover", "Usuario tentou recuperar senha com o email: ".$email );
					$conn->close();
					
				} else {
					
					$request->message = "Erro: Não foi possível enviar o email com a recuperão de senha, por favor tente novamente.";
					$request = json_encode( $request );
					showResponse( $request );
					$conn->close();
					
				}
				
			} else {
				
				$request->message = "Erro: Email não encontrado, por favor tente novamente.";
				$request = json_encode( $request );
				showResponse( $request );
				saveLog( $conn, 0, "Recover", "Falha ao usuario tentar recuperar senha com email invalido: ".$email );
				$conn->close();				

			}		
			
		}
		
	}
	
	function report( $conn ) {
		
		// Request example: http://transitabile.com.br/api.php?userid=8&type=report&desc=desc
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['desc'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'report' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$desc = strtolower( $_GET['desc'] );
			
			$to = "andre@gameloop.com.br";
			$subject = "Transitabile - Informe de Erro";
			$txt = "Usuário ".$_GET['userid']." reportou um erro em Transitabile: " .$desc ;
			$headers = "From: report@transitabile.com.br";

			if (mail( $to, $subject, $txt, $headers )) {
				
				$request->status = "success";
				$request->message = "Obrigado por reportar, nossa equipe de desenvolvimento irá analisar o problema em breve.";
				$request = json_encode( $request );
				showResponse( $request );
				saveLog( $conn, $_GET['userid'], "Report", "Usuario reportou um problema no sistema." );
				$conn->close();
				
			} else {
				
				$request->message = "Erro: Desculpe, não foi possível enviar, por favor tente novamente.";
				$request = json_encode( $request );
				showResponse( $request );
				$conn->close();
				
			}		
			
		}
		
	}
	
	function contact( $conn ) {
		
		// Request example: 'http://transitabile.com.br/api.php?userid=1&type=contact&name=teste&email=andre@gameloop.com.br&cpf=08954718898&subject=Assunto&msg=msg;
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( empty( $_GET['userid'] )  || empty( $_GET['name'] )  ||$_GET['email'] )  ||empty( $_GET['cpf'] )  || empty( $_GET['subject'] )  || empty( $_GET['msg'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'contact' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$userID = strtolower( $_GET['userid'] );
			$name = strtolower( $_GET['name'] );
			$email = strtolower( $_GET['email'] );
			$cpf = strtolower( $_GET['cpf'] );
			$subjecttitle = strtolower( $_GET['subject'] );
			$msg = strtolower( $_GET['msg'] );
			
			$to = "contato@transitabile.com.br";
			$subject = "Transitabile - Contato";
			$txt = "Usuário ".$name.", ID ".$userID.", email ".$email.", cpf ".$cpf." entrou em contato escrevendo sobre ".$subjecttitle.": ".$msg ;
			$headers = "From: contato@transitabile.com.br";

			if (mail( $to, $subject, $txt, $headers )) {
				
				$request->status = "success";
				$request->message = "Sua mensagem foi enviada, obrigado por entrar em contato.";
				$request = json_encode( $request );
				showResponse( $request );
				saveLog( $conn, $_GET['userid'], "Contact", "Usuario entrou em contato." );
				$conn->close();
				
			} else {
				
				$request->message = "Erro: Desculpe, não foi possível enviar, por favor tente novamente.";
				$request = json_encode( $request );
				showResponse( $request );
				$conn->close();
				
			}		
			
		}
		
	}

	function getBalance( $conn ) {

		// Request example: 'http://transitabile.com.br/api.php?userid=22&type=getBalance'

		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['userid'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'getBalance' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {

			$userid = $_GET['userid'];
							
			$sql = "SELECT balance FROM users WHERE id = '".$userid."'";
			$result = $conn->query($sql);

			$finalResult = null;

			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				//echo $row["value"];
				$finalResult = $row["balance"];
				$request->status = "success";
				$request->balance = $finalResult;
				$request = json_encode( $request );
				showResponse( $request );
				$conn->close();
			} else {
				$request->message = "Erro: Desculpe, não foi possível obter o valor da carteira, por favor tente novamente.";
				$request = json_encode( $request );
				showResponse( $request );
				$conn->close();
			}
			
		}
	}
	
	function addVehicle( $conn ) {
		
		// Request example: http://transitabile.com.br/api.php?userid=1&type=addvehicle&name=n&plate=p&model=m
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['userid'] ) || empty( $_GET['name'] ) || empty( $_GET['plate'] ) || empty( $_GET['model'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'addVehicle' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$userid = strtolower( $_GET['userid'] );
			$name = strtolower( $_GET['name'] );
			$plate = strtolower( $_GET['plate'] );
			$model = strtolower( $_GET['model'] );
			
			// Check if plate already exists
			$sql = "SELECT * FROM vehicles WHERE plate = '".$plate."'";
			
			$result = $conn->query( $sql );
			
			if ( $result->num_rows > 0 ) {
				
				$request->message = "Erro: Placa já cadastrada, por favor tente novamente ou entre em contato conosco.";
				$request = json_encode( $request );
				showResponse( $request );
				saveLog( $conn, $userid, "Add Vehicle", "Falha ao adicionar veiculo com placa ja cadastrada, placa: ".$plate );
				$conn->close();
					
			} else {
				
				// Subscribe new vehicle				
				$sql = "INSERT INTO vehicles ( user_id, type, name, plate, model, status, created, modified ) VALUES ( '".$userid."', 'car', '".$name."', '".$plate."', '".$model."', 'idle', NOW(), NOW() )";
				$result = $conn->query($sql);
				
				if( $result === true ) {
					
					$request->status = "success";
					$request->message = "Cadastro de veículo realizado com sucesso.";
					$request = json_encode( $request );
					showResponse( $request );
					saveLog( $conn, $userid, "Add Vehicle", "Novo veiculo adicionado, placa: ".$plate );
					$conn->close();
					
				} else {
					
					$request->message = "Erro: Não foi possível realizar o cadastro do veículo, tente novamente mais tarde (".$conn->error.")";
					$request = json_encode( $request );
					showResponse( $request );
					$conn->close();
					
				}				

			}		
			
		}
		
	}
	
	function getVehicles( $conn ) {
		
		// Request example: http://transitabile.com.br/api.php?userid=8&type=getvehicles
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['userid'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'getVehicles' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$userid = strtolower( $_GET['userid'] );
			
			$sql = "SELECT * FROM vehicles WHERE user_id = '".$userid."'";
			$result = $conn->query( $sql );

			$results = $result->num_rows;
				
			$request->status = "success";
			$request->results = $results;
			$request->message = $results." veículos encontrados.";
			
			$list = array();
			
			while( $row = $result->fetch_assoc() ) {
				
				$list[] = array('id' => $row["id"], 'userid' => $row["user_id"], 'type' => $row["type"], 'name' => $row["name"], 'plate' => $row["plate"], 'model' => $row["model"], 'status' => $row["status"], 'created' => $row["created"], 'modified' => $row["modified"]);
				
			}
			
			$request->list = $list;
			
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();		
			
		}
		
	}

	function getVehiclesFull( $conn ) {
		
		// Request example: http://transitabile.com.br/api.php?userid=8&type=getvehicles
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['userid'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'getVehiclesFull' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$userid = strtolower( $_GET['userid'] );
			
			$sql = "SELECT t.id, t.user_id, t.vehicle_id, t.parklot_id, t.type, t.value, t.total_value, t.status, t.iniciohorario, t.fimhorario, t.tempopermanencia, v.id, v.user_id, v.type, v.name, v.plate, v.model, v.status, v.created, v.modified FROM tickets t JOIN vehicles v ON t.vehicle_id = v.id WHERE v.status = 'regular' AND t.user_id= '".$userid."'";
			$result = $conn->query( $sql );

			$results = $result->num_rows;
				
			$request->status = "success";
			$request->results = $results;
			$request->message = $results." veículos encontrados.";
			
			$list = array();
			
			while( $row = $result->fetch_assoc() ) {
				
				$list[] = array('id' => $row["v.id"], 'userid' => $row["v.user_id"], 'type' => $row["v.type"], 'name' => $row["v.name"], 'plate' => $row["v.plate"], 'model' => $row["v.model"], 'status' => $row["v.status"], 'created' => $row["v.created"], 'modified' => $row["v.modified"], 'ticketid' => $row["tickets.id"], 'ticketuserid' => $row["tickets.user_id"], 'ticketvehicleid' => $row["tickets.vehicle_id"], 'ticketparklotid' => $row["tickets.parklot_id"], 'tickettypeid' => $row["tickets.type"], 'ticketvalueid' => $row["tickets.value"], 'tickettotalvalueid' => $row["tickets.total_value"], 'ticketstatusid' => $row["tickets.status"], 'ticketiniciohorarioid' => $row["tickets.iniciohorario"], 'ticketfimhorarioid' => $row["tickets.fimhorario"], 'tickettempopermanenciaid' => $row["tickets.tempopermanencia"]);
				
			}
			
			$request->list = $list;
			
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();		
			
		}
		
	}
	
	function getTic( $conn ) {
		
		// Request example: http://transitabile.com.br/api.php?userid=8&type=getvehicles
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['vehicleid'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'getTic' inválidos. vehicleid VAZIO";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$vehicleid = strtolower( $_GET['vehicleid'] );
			
			// $sql = "SELECT v.id, v.user_id, v.type, v.name, v.plate, v.model, v.status, v.created, v.modified, tickets.id, tickets.user_id, tickets.vehicle_id, tickets.parklot_id, tickets.type, tickets.value, tickets.total_value, tickets.status, tickets.iniciohorario, tickets.fimhorario, tickets.tempopermanencia FROM vehicles v JOIN tickets t ON v.id = t.vehicle_id WHERE user_id = '".$userid."'";
			$sql = "SELECT t.id, t.user_id, t.vehicle_id, t.parklot_id, t.type, t.value, t.total_value, t.status, t.iniciohorario, t.fimhorario, t.tempopermanencia, v.id, v.user_id, v.type, v.name, v.plate, v.model, v.status, v.created, v.modified FROM tickets t JOIN vehicles v ON t.vehicle_id = v.id WHERE v.user_id = '".$userid."'";
            
            // pegar id veiculo******
			// SELECT t.id, t.user_id, t.vehicle_id, t.parklot_id, t.type, t.value, t.total_value, t.status, t.iniciohorario, t.fimhorario, t.tempopermanencia FROM tickets t JOIN vehicles v ON t.vehicle_id = v.id WHERE t.vehicle_id = 51
            
			$result = $conn->query( $sql );

			$results = $result->num_rows;
				
			$request->status = "success";
			$request->results = $results;
			$request->message = $results." veículos encontrados.";
			
			$list = array();
			
			while( $row = $result->fetch_assoc() ) {
				
				$list[] = array('id' => $row["v.id"], 'userid' => $row["v.user_id"], 'type' => $row["v.type"], 'name' => $row["v.name"], 'plate' => $row["v.plate"], 'model' => $row["v.model"], 'status' => $row["v.status"], 'created' => $row["v.created"], 'modified' => $row["v.modified"], 'ticketid' => $row["tickets.id"], 'ticketuserid' => $row["tickets.user_id"], 'ticketvehicleid' => $row["tickets.vehicle_id"], 'ticketparklotid' => $row["tickets.parklot_id"], 'tickettypeid' => $row["tickets.type"], 'ticketvalueid' => $row["tickets.value"], 'tickettotalvalueid' => $row["tickets.total_value"], 'ticketstatusid' => $row["tickets.status"], 'ticketiniciohorarioid' => $row["tickets.iniciohorario"], 'ticketfimhorarioid' => $row["tickets.fimhorario"], 'tickettempopermanenciaid' => $row["tickets.tempopermanencia"]);
				
			}
			
			$request->list = $list;
			
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();		
			
		}
		
	}
		
	function updateVehicle( $conn ) {
		
		// Request example: http://transitabile.com.br/api.php?userid=8&type=updatevehicle&id=12&plate=pvd5247&model=uno
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['id'] ) || empty( $_GET['plate'] ) || empty( $_GET['model'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'updateVehicle' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$id = strtolower( $_GET['id'] );
			$plate = strtolower( $_GET['plate'] );
			$model = strtolower( $_GET['model'] );
						
			$sql = "UPDATE vehicles SET plate = '".$plate."', model = '".$model."' WHERE id = ".$id;
			$result = $conn->query($sql);
			
			if( $result === true ) {
			
				$request->status = "success";
				$request->message = "Atualização realizada com sucesso.";
				$request = json_encode( $request );
				showResponse( $request );
				saveLog( $conn, $_GET['userid'], "Update Vehicle", "Informações do veiculo atualizadas, id: ".$id );
				$conn->close();
				
			} else {
				
				$request->message = "Erro: Não foi possível realizar a atualização, tente novamente mais tarde (".$conn->error.")";
				$request = json_encode( $request );
				showResponse( $request );
				$conn->close();
				
			}		
			
		}
		
	}
	
	function deleteVehicle( $conn ) {
		
		// Request example: http://transitabile.com.br/api.php?userid=8&type=deletevehicle&id=13
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['id'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'deleteVehicle' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$id = strtolower( $_GET['id'] );
						
			$sql = "DELETE FROM vehicles WHERE id = ".$id;
			$result = $conn->query($sql);
			
			if( $result === true ) {
			
				$request->status = "success";
				$request->message = "Veículo removido com sucesso.";
				$request = json_encode( $request );
				showResponse( $request );
				saveLog( $conn, $_GET['userid'], "Delete Vehicle", "Veiculo deletado do sistema, id: ".$id );
				$conn->close();
				
			} else {
				
				$request->message = "Erro: Não foi possível remover o veículo, tente novamente mais tarde (".$conn->error.")";
				$request = json_encode( $request );
				showResponse( $request );
				$conn->close();
				
			}		
			
		}
		
	}
	
	function reportVehicle( $conn ) {
		
		// Request example: http://transitabile.com.br/api.php?userid=1&type=reportvehicle&plate=PVD5247
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['plate'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'reportVehicle' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$userid = strtolower( $_GET['userid'] );
			$plate = strtolower( $_GET['plate'] );
			
			// Check if plate already exists
			$sql = "SELECT * FROM vehicles WHERE plate = '".$plate."'";
			
			$result = $conn->query( $sql );
			
			
			if ( $result->num_rows > 0 ) {
			
				$row = $result->fetch_assoc();
				
				if( $row["status"] == "irregular" ) {
					
					$sql = "INSERT INTO proceedings ( user_id, plate, created, modified ) VALUES ( '".$userid."', '".$plate."', NOW(), NOW() )";
					$result = $conn->query($sql);
					
					$request->status = "success";
					$request->message = "Veículo irregular autuado.";
					$request = json_encode( $request );
					showResponse( $request );
					saveLog( $conn, $_GET['userid'], "Report Vehicle", "Veiculo irregular autuado: ".$plate );
					$conn->close();
					
				} else {
					
					$request->status = "success";
					$request->message = "Veículo em situação regular.";
					$request = json_encode( $request );
					showResponse( $request );
					saveLog( $conn, $_GET['userid'], "Report Vehicle", "Veiculo em situação regular fiscalizado: ".$plate );
					$conn->close();
					
				}
			
			} else {
				
				$request->message = "Erro: Placa não cadastrada no sistema.";
				$request = json_encode( $request );
				showResponse( $request );
				saveLog( $conn, $_GET['userid'], "Report Vehicle", "Veiculo com placa não cadastrada no sistema fiscalizado: ".$plate );
				$conn->close();				

			}				
			
		}
		
	}
	
	function updateUserInfo( $conn ) {
		
		// Request example: http://transitabile.com.br/api.php?userid=8&type=updateuserinfo&name=n&email=andre@gameloop.com.br&pass=123&cpf=234
		
		$request = new stdClass();
		$request->status = "failed";
		
		if ( empty( $_GET['userid'] ) || empty( $_GET['name'] ) || empty( $_GET['email'] ) || empty( $_GET['pass'] ) || empty( $_GET['cpf'] )) {

			$request->message = "Erro: Parâmetros de requisição 'addVehicle' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$userid = strtolower( $_GET['userid'] );
			$name = strtolower( $_GET['name'] );
			$email = strtolower( $_GET['email'] );
			$pass = strtolower( $_GET['pass'] );
			$cpf = strtolower( $_GET['cpf'] );
						
			$sql = "UPDATE users SET name = '".$name."', email = '".$email."', password = '".$pass."', cpf = '".$cpf."', modified = 'NOW()' WHERE id = ".$userid;
			$result = $conn->query($sql);
			
			if( $result === true ) {
			
				$sql = "SELECT * FROM users WHERE id = ".$userid;
				$result = $conn->query( $sql );
			
				if ( $result->num_rows > 0 ) {
								
					$row = $result->fetch_assoc();
		
					$userInfo = new stdClass();
					$userInfo->id = $row["id"];
					$userInfo->type = $row["type"];
					$userInfo->name = convert_from_latin1_to_utf8_recursively($row["name"]);
					$userInfo->email = $row["email"];
					$userInfo->cpf = $row["cpf"];
					$userInfo->password = $row["password"];
					$userInfo->birthdate = $row["birthdate"];
					$userInfo->phone = $row["phone"];
					$userInfo->balance = $row["balance"];
					$userInfo->created = $row["created"];
					$userInfo->modified = $row["modified"];				
					
					$request->status = "success";
					$request->message = "Atualização realizada com sucesso.";
					$request->info = $userInfo;
					$request = json_encode( $request );
					showResponse( $request );
					saveLog( $conn, $_GET['userid'], "Update User", "Informações de usuario atualizadas." );
					$conn->close();
					
				} else {
					
					$request->message = "Erro: Não foi possível realizar a atualização, tente novamente mais tarde ( User id not found )";
					$request = json_encode( $request );
					showResponse( $request );
					$conn->close();
					
				}
				
			} else {
				
				$request->message = "Erro: Não foi possível realizar a atualização, tente novamente mais tarde (".$conn->error.")";
				$request = json_encode( $request );
				showResponse( $request );
				$conn->close();
				
			}		
			
		}
		
	}
	
	// Park Lots Requests:

	function park( $conn ) {

		// Request example: http://transitabile.com.br/api.php?userid=22&type=park&vid=32%totalValue=2

		$request = new stdClass();

		if ( empty( $_GET['userid'] ) || empty( $_GET['vid'] ) || empty( $_GET['totalValue'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'park' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$userid = strtolower( $_GET['userid'] );
			$vid = strtolower( $_GET['vid'] );
			$totalValue = strtolower( $_GET['totalValue'] );

			$sql = "UPDATE users SET balance = balance - ".$totalValue." WHERE id = ".$userid;
			$result = $conn->query($sql);
						
			$sql = "UPDATE vehicles SET status = 'regular' WHERE id = ".$vid;
			$result = $conn->query($sql);
			
			if( $result === true ) {
			
				$request->status = "success";
				$request->message = "Veículo estacionado com sucesso.";
				$request = json_encode( $request );
				showResponse( $request );
				saveLog( $conn, $_GET['userid'], "Estacionamento", "Veiculo estacionado com sucesso, id: ".$vid );

				$sql = "SELECT plate FROM vehicles WHERE id = '".$vid."'";
				$result = $conn->query($sql);
		
				if ($result->num_rows > 0) {
		
					$row = $result->fetch_assoc();
					$plate = $row["plate"];
					$transactionIdInformer = rand();

					saveLog( $conn, $_GET['userid'], "Estacionamento", "Ativacao de ticket enviada, vid: ".$vid.", placa: ".$plate." e idT: ".$transactionIdInformer );
	
//					activateTicket($conn, $plate, $transactionIdInformer);
					activateTicket($conn, $plate, $transactionIdInformer, $userid, $vid, $totalValue);

				}


				$conn->close();
				
			} else {
				
				$request->message = "Erro: Nao foi possível realizar o estacionamento, tente novamente mais tarde (".$conn->error.")";
				$request = json_encode( $request );
				showResponse( $request );
				$conn->close();
				
			}		
			
		}

	}

	function setVehicleStatus( $conn ) {

		// Request example: http://transitabile.com.br/api.php?userid=22&type=setVehicleStatus&vid=32&status=idle

		$request = new stdClass();

		if ( empty( $_GET['userid'] ) || empty( $_GET['vid'] ) || empty( $_GET['status'] ) ) {

			$request->message = "Erro: Parâmetros de requisição 'park' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$userid = strtolower( $_GET['userid'] );
			$vid = strtolower( $_GET['vid'] );
			$status = strtolower( $_GET['status'] );
						
			$sql = "UPDATE vehicles SET status = '".$status."' WHERE id = ".$vid;
			$result = $conn->query($sql);
			
			if( $result === true ) {
			
				$request->status = "success";
				$request->message = "Estado do veículo atualizado.";
				$request = json_encode( $request );
				showResponse( $request );
				saveLog( $conn, $_GET['userid'], "Estacionamento", "Estado do veículo atualizado para: ".$status.", id: ".$vid );
				$conn->close();
				
			} else {
				
				$request->message = "Erro: Não foi possível atualizar o estado do veículo, tente novamente mais tarde (".$conn->error.")";
				$request = json_encode( $request );
				showResponse( $request );
				$conn->close();
				
			}		
			
		}

	}

	function getParkLotsNear( $lat, $lng, $distance ) {
	}
	
	function parkLotCheckIn( $parklotid ) {
	}
	
	function parkLotCheckOut( $parklotid ) {		
	}
	
	// Other Requests:
	
	function checkSystem() {
		
		$request->status = "success";
		$request->message = "Sistema online.";
		$request = json_encode( $request );
		showResponse( $request );
		
	}
	
	function saveLog( $conn, $userid, $type, $msg ) {
				
		$sql = "INSERT INTO logs ( type, user_id, vehicle_id, parklot_id, name, description, created, modified ) VALUES ( '".$type."','".$userid."', '0', '0', 'log','".$msg."',NOW() , NOW())";
		$result = $conn->query($sql);
		
	}

	function getUserLogs( $conn ) {

		// Request example: http://transitabile.com.br/api.php?userid=1&type=getlogs
			
		if ( empty($_GET['userid']) ) {

			$request->message = "Erro: Parâmetros de requisição 'getuserlogs' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$userID = strtolower( $_GET['userid'] );	

			$sql = "SELECT * FROM logs WHERE user_id ='".$userID."' ORDER BY modified DESC";
			$result = $conn->query($sql);

			$results = $result->num_rows;
		
			$request = new stdClass();
			$request->status = "success";
			$request->results = $results;
			$request->message = $results." logs encontrados.";

			$list = array();
		
			while( $row = $result->fetch_assoc() ) {
	
				$list[] = array('id' => $row["id"], 'type' => $row["type"], 'description' => $row["description"], 'created' => $row["created"]);
	
			}

			$request->list = $list;
	
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();
			
		}
		
	}

	function getUserAddLogs( $conn ) {

		// Request example: http://transitabile.com.br/api.php?userid=1&type=getlogs
			
		if ( empty($_GET['userid']) ) {

			$request->message = "Erro: Parâmetros de requisição 'getuserAddlogs' inválidos.";
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();

		} else {
			
			$userID = strtolower( $_GET['userid'] );	

			$sql = "SELECT * FROM logs WHERE user_id ='".$userID."' 
			and type = 'Compra'
			or type = 'Pendente'
			or type = 'Cancelado'
			or type = 'Estacionamento'
			or type = 'Negado' ORDER BY modified DESC";
			$result = $conn->query($sql);

			$results = $result->num_rows;
		
			$request = new stdClass();
			$request->status = "success";
			$request->results = $results;
			$request->message = $results." logs encontrados.";

			$list = array();
		
			while( $row = $result->fetch_assoc() ) {
	
				$list[] = array('id' => $row["id"], 'type' => $row["type"], 'description' => $row["description"], 'created' => $row["created"]);
	
			}

			$request->list = $list;
	
			$request = json_encode( $request );
			showResponse( $request );
			$conn->close();
			
		}
		
	}
	
	
	function setSale( $userid, $type, $value, $created, $modified, $userid ) {	
	}
	
	function showResponse ( $response ) {
		
		$response = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
			return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
		}, $response);
		
		echo $response;
		
	}

	// Functions API Bragança Paulista
	
	// Obter Chave:
	function getAutorizacao () {

		// Request example: http://transitabile.com.br/api.php?userid=1&type=autorizacao
		
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://devapibraganca.estacionelegal.com.br/access_token",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"grant_type\"\r\n\r\nclient_credentials\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"client_id\"\r\n\r\ntransitable\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"client_secret\"\r\n\r\n66YVpx9WE7jR9\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"scope\"\r\n\r\napi\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
		  CURLOPT_HTTPHEADER => array(
			"Cache-Control: no-cache",
			"Postman-Token: f4783f76-fdc3-48f8-a9dc-e2f30f10e548",
			"content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  echo $response;
		}

				
	}
	
	// Ativar Ticket:
//	function activateTicket($conn, $plate, $transactionIdInformer)  {
	function activateTicket($conn, $plate, $transactionIdInformer, $userid, $vid, $totalValue)  {
		
		$curl = curl_init();

		// Define time:
		date_default_timezone_set('America/Sao_Paulo');
		$datenow = date('c');

        $tempoP = $totalValue/2;
//        $tempoP = ($tempoP * 60)*60;
        $sqlI = "INSERT INTO tickets (user_id, vehicle_id, value, total_value, status, iniciohorario, fimhorario, tempopermanencia)
VALUES (".$userid.", ".$vid.", 2, ".$totalValue.",'regular', NOW(), NOW() + INTERVAL ".$tempoP." HOUR, '".$tempoP." Horas');";
		$result = $conn->query($sqlI);
        
        
        
        
		// Get Acess Token:
		$sql = "SELECT value FROM secretkey WHERE id = '1'";
		$result = $conn->query($sql);
        
        

		$atoken = null;

		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			$atoken = $row["value"];
		}

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://devapibraganca.estacionelegal.com.br/api/v1/ticket",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "{\n\t\"idempresa\": 2,\n\t\"idtransacaoempresa\": \"".$transactionIdInformer."\",\n\t\"idequipamento\": \"9800123456\",\n\t\"cpfcnpjusuario\": \"07342481000162\",\n\t\"latitude\": \"\",\n\t\"longitude\": \"\",\n\t\"setor\": \"1\",\n\t\"tipoveiculo\": \"1\",\n\t\"placa\": \"".$plate."\",\n\t\"cbad\": 1,\n\t\"datahoraenvio\": \"".$datenow."\"\n}",
			CURLOPT_HTTPHEADER => array(
			  "Cache-Control: no-cache",
			  "Content-Type: application/json",
			  "Postman-Token: d31c04ca-41b8-44e2-9c63-ec33b0951955",
			  "authorization: $atoken"
			),
		  ));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  //echo "cURL Error #:" . $err;
		} else {
		  //echo $response;
		}
		
	}
	
	// Consultar Ticket:
	function consultarTicket() {

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://devapibraganca.estacionelegal.com.br/api/v1/ticket/0118177055755000000000347",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\n\t\"idempresa\": 2,\n\t\"datahoraenvio\": \"2018-06-06T10:00:00\"\n}",
		  CURLOPT_HTTPHEADER => array(
			"Cache-Control: no-cache",
			"Content-Type: application/json",
			"Postman-Token: b0588672-930d-4ca7-adec-246c0251383b",
			"authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImIzNzU4NTBlMzBhMDljMGMzYjdlYjA4YmI3ZDg0NTNlMDcyYjliNmRkMGNiY2M4MTVjMTk1ZmFkMmMyMTAyMDA0ODhmMTY1YTZkOGFhYzBkIn0.eyJhdWQiOiJ0cmFuc2l0YWJsZSIsImp0aSI6ImIzNzU4NTBlMzBhMDljMGMzYjdlYjA4YmI3ZDg0NTNlMDcyYjliNmRkMGNiY2M4MTVjMTk1ZmFkMmMyMTAyMDA0ODhmMTY1YTZkOGFhYzBkIiwiaWF0IjoxNTMzNTYzMzEwLCJuYmYiOjE1MzM1NjMzMTAsImV4cCI6MTUzNDQyNzMxMCwic3ViIjoiIiwic2NvcGVzIjpbImFwaSJdfQ.VM_VwooaOaIApYW9l2eK5lGRHlzL983_sX7rNjsApX6R87aSwr4tf0gljMoALIw1PjjRrT5F7ESV6obxligBl1LnxZOlZoubttzWXMCos3HMAAGcawgOlkplnvWBBtpvYnAxi65E18a38DCvKG691mmRHeT5LDuWTbyxp8t5UBT2iRjbZ5GYBkW5AGfjsSLhqTua8Jdd1yQG3Xumi86X-O_tzFtKjwvFXvwG8aeHxfV2cAq811HmpHSS5m4Aw8127xaf5lQAaWCdJsZMuberuGl5qFkjQBm2demZ-5moTieCXMDSWXSq24nC0GqOS57tahouIEkUywnRRkT4gP9jOA"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  echo $response;
		}
		
	}
	
	// Ping
	function  ping() {
		
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://devapibraganca.estacionelegal.com.br/api/v1/ping",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
			"Cache-Control: no-cache",
			"Postman-Token: d59ea15c-52ef-4609-8a87-93074d25389d",
			"authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImIzNzU4NTBlMzBhMDljMGMzYjdlYjA4YmI3ZDg0NTNlMDcyYjliNmRkMGNiY2M4MTVjMTk1ZmFkMmMyMTAyMDA0ODhmMTY1YTZkOGFhYzBkIn0.eyJhdWQiOiJ0cmFuc2l0YWJsZSIsImp0aSI6ImIzNzU4NTBlMzBhMDljMGMzYjdlYjA4YmI3ZDg0NTNlMDcyYjliNmRkMGNiY2M4MTVjMTk1ZmFkMmMyMTAyMDA0ODhmMTY1YTZkOGFhYzBkIiwiaWF0IjoxNTMzNTYzMzEwLCJuYmYiOjE1MzM1NjMzMTAsImV4cCI6MTUzNDQyNzMxMCwic3ViIjoiIiwic2NvcGVzIjpbImFwaSJdfQ.VM_VwooaOaIApYW9l2eK5lGRHlzL983_sX7rNjsApX6R87aSwr4tf0gljMoALIw1PjjRrT5F7ESV6obxligBl1LnxZOlZoubttzWXMCos3HMAAGcawgOlkplnvWBBtpvYnAxi65E18a38DCvKG691mmRHeT5LDuWTbyxp8t5UBT2iRjbZ5GYBkW5AGfjsSLhqTua8Jdd1yQG3Xumi86X-O_tzFtKjwvFXvwG8aeHxfV2cAq811HmpHSS5m4Aw8127xaf5lQAaWCdJsZMuberuGl5qFkjQBm2demZ-5moTieCXMDSWXSq24nC0GqOS57tahouIEkUywnRRkT4gP9jOA"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  echo $response;
		}
		
	}
	
?>