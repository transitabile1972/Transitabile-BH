<?php
//https://transitabile.com.br/api/CETSP/demo.php
/* API Transitabile V 1.0 */

// Allow Cross Origin:
header("Access-Control-Allow-Origin: *");

// UTF8:
header("Content-type: text/html; charset=utf-8");

// Display Errors:
error_reporting(E_ALL);

// Connection & Requests:
$conn = null;

$userid = null;
$type = null;

$request = new stdClass();
$request->status = "failed";

if (empty($_GET['userid']) || empty($_GET['type'])) {

    $request->message = 'Erro: Parâmetros de requisição inválidos.';
    $request = json_encode($request);
    showResponse($request);

} else {

    $servername = "localhost";
    $username = "trans593_user";
    $password = "32486502transitabile";
    $databasename = "trans593_app";

    $userid = $_GET['userid'];
    $type = $_GET['type'];

    $conn = new mysqli($servername, $username, $password, $databasename);

    if ($conn->connect_error) {

        $request->message = 'Erro: Tipo de requisição inválido (' . $type . ').';
        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    } else {

        // Connection ok, checking request type.
        // echo 'API Ready';

        switch ($type) {

            case "login":
                checkLogin($conn);
                break;

            case "signup":
                signUp($conn);
                break;

            case "recover":
                recover($conn);
                break;

            case "addvehicle":
                addVehicle($conn);
                break;

            case "updateuserinfo":
                updateUserInfo($conn);
                break;

            case "getvehicles":
                getVehicles($conn);
                break;

            case "updatevehicle":
                updateVehicle($conn);
                break;

            case "deletevehicle":
                deleteVehicle($conn);
                break;

            case "report":
                report($conn);
                break;

            case "reportvehicle":
                reportVehicle($conn);
                break;

            case "contact":
                contact($conn);
                break;

            default:
                $request->message = 'Erro: Tipo de requisição inválido (' . $type . ').';
                $request = json_encode($request);
                showResponse($request);
                $conn->close();

        }

    }

}

// User Requests:

function checkLogin($conn)
{

    // Request example: http://transitabile.com.br/api.php?userid=1&type=login&email=andre@gameloop.com.br&pass=p

    $request = new stdClass();
    $request->status = "failed";

    if (empty($_GET['email']) || empty($_GET['pass'])) {

        $request->message = "Erro: Parâmetros de requisição 'login' inválidos.";
        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    } else {

        $email = strtolower($_GET['email']);
        $pass = strtolower($_GET['pass']);

        $sql = "SELECT * FROM users WHERE email = '" . $email . "' AND password = '" . $pass . "'";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {

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
            $request = json_encode($request);
            showResponse($request);
            saveLog($conn, $row["id"], "Login", "Acessou o sistema com sucesso.");
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
            $request->message = "Erro: Email ou senha incorretos.";
            $request = json_encode($request);
            showResponse($request);
            saveLog($conn, 0, "Login", "Erro ao fazer login, email ou senha incorretos.");
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

function signUp($conn)
{

    // Request example: http://transitabile.com.br/api.php?userid=1&type=signup&name=n&email=e&pass=p&cpf=c

    $request = new stdClass();
    $request->status = "failed";

    if (empty($_GET['name']) || empty($_GET['email']) || empty($_GET['pass']) || empty($_GET['cpf'])) {

        $request->message = "Erro: Parâmetros de requisição 'signup' inválidos.";
        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    } else {

        $name = strtolower($_GET['name']);
        $email = strtolower($_GET['email']);
        $pass = strtolower($_GET['pass']);
        $cpf = strtolower($_GET['cpf']);

        // Check if email or cpf already exists
        $sql = "SELECT * FROM users WHERE email = '" . $email . "' OR cpf = '" . $cpf . "'";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {

            $request->message = "Erro: Email e/ou CPF já cadastrados, por favor tente novamente.";
            $request = json_encode($request);
            showResponse($request);
            $conn->close();

        } else {

            // Subscribe new user
            $sql = "INSERT INTO users (type, name, email, cpf, password, created, modified ) VALUES ('user', '" . $name . "', '" . $email . "', '" . $cpf . "', '" . $pass . "', NOW(), NOW())";
            $result = $conn->query($sql);

            if ($result === true) {

                $request->status = "success";
                $request->message = "Cadastro realizado com sucesso.";
                $request = json_encode($request);
                showResponse($request);
                saveLog($conn, 0, "SignUp", "Novo usuario cadastrado - CPF: " . $cpf);
                $conn->close();

            } else {

                $request->message = "Erro: Não foi possível realizar o cadastro, tente novamente mais tarde (" . $conn->error . ")";
                $request = json_encode($request);
                showResponse($request);
                $conn->close();

            }

        }

    }

}

function recover($conn)
{

    // Request example: http://transitabile.com.br/api.php?userid=1&type=recover&email=e

    $request = new stdClass();
    $request->status = "failed";

    if (empty($_GET['email'])) {

        $request->message = "Erro: Parâmetros de requisição 'recover' inválidos.";
        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    } else {

        $email = strtolower($_GET['email']);

        $sql = "SELECT password FROM users WHERE email = '" . $email . "'";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {

            $row = $result->fetch_assoc();

            $to = $email;
            $subject = "Transitabile - Recuperação de Senha";
            $txt = "Foi solicitada uma recuperação de senha no aplicativo Transitabile, segue sua senha: " . $row["password"];
            $headers = "From: recover@transitabile.com.br";

            if (mail($to, $subject, $txt, $headers)) {

                $request->status = "success";
                $request->message = "Foi enviado um email com a recuperação de senha, por favor verifique a caixa de entrada e de span.";
                $request = json_encode($request);
                showResponse($request);
                saveLog($conn, 0, "Recover", "Usuario tentou recuperar senha com o email: " . $email);
                $conn->close();

            } else {

                $request->message = "Erro: Não foi possível enviar o email com a recuperão de senha, por favor tente novamente.";
                $request = json_encode($request);
                showResponse($request);
                $conn->close();

            }

        } else {

            $request->message = "Erro: Email não encontrado, por favor tente novamente.";
            $request = json_encode($request);
            showResponse($request);
            saveLog($conn, 0, "Recover", "Falha ao usuario tentar recuperar senha com email invalido: " . $email);
            $conn->close();

        }

    }

}

function report($conn)
{

    // Request example: http://transitabile.com.br/api.php?userid=8&type=report&desc=desc

    $request = new stdClass();
    $request->status = "failed";

    if (empty($_GET['desc'])) {

        $request->message = "Erro: Parâmetros de requisição 'report' inválidos.";
        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    } else {

        $desc = strtolower($_GET['desc']);

        $to = "andre@gameloop.com.br";
        $subject = "Transitabile - Informe de Erro";
        $txt = "Usuário " . $_GET['userid'] . " reportou um erro em Transitabile: " . $desc;
        $headers = "From: report@transitabile.com.br";

        if (mail($to, $subject, $txt, $headers)) {

            $request->status = "success";
            $request->message = "Obrigado por reportar, nossa equipe de desenvolvimento irá analisar o problema em breve.";
            $request = json_encode($request);
            showResponse($request);
            saveLog($conn, $_GET['userid'], "Report", "Usuario reportou um problema no sistema.");
            $conn->close();

        } else {

            $request->message = "Erro: Desculpe, não foi possível enviar, por favor tente novamente.";
            $request = json_encode($request);
            showResponse($request);
            $conn->close();

        }

    }

}

function contact($conn)
{

    // Request example: 'http://transitabile.com.br/api.php?userid=1&type=contact&name=teste&email=andre@gameloop.com.br&cpf=08954718898&subject=Assunto&msg=msg;

    $request = new stdClass();
    $request->status = "failed";

    if (empty(empty($_GET['userid']) || empty($_GET['name']) || $_GET['email']) || empty($_GET['cpf']) || empty($_GET['subject']) || empty($_GET['msg'])) {

        $request->message = "Erro: Parâmetros de requisição 'contact' inválidos.";
        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    } else {

        $userID = strtolower($_GET['userid']);
        $name = strtolower($_GET['name']);
        $email = strtolower($_GET['email']);
        $cpf = strtolower($_GET['cpf']);
        $subjecttitle = strtolower($_GET['subject']);
        $msg = strtolower($_GET['msg']);

        $to = "contato@transitabile.com.br";
        $subject = "Transitabile - Contato";
        $txt = "Usuário " . $name . ", ID " . $userID . ", email " . $email . ", cpf " . $cpf . " entrou em contato escrevendo sobre " . $subjecttitle . ": " . $msg;
        $headers = "From: contato@transitabile.com.br";

        if (mail($to, $subject, $txt, $headers)) {

            $request->status = "success";
            $request->message = "Sua mensagem foi enviada, obrigado por entrar em contato.";
            $request = json_encode($request);
            showResponse($request);
            saveLog($conn, $_GET['userid'], "Contact", "Usuario entrou em contato.");
            $conn->close();

        } else {

            $request->message = "Erro: Desculpe, não foi possível enviar, por favor tente novamente.";
            $request = json_encode($request);
            showResponse($request);
            $conn->close();

        }

    }

}

function addVehicle($conn)
{

    // Request example: http://transitabile.com.br/api.php?userid=1&type=addvehicle&name=n&plate=p&model=m

    $request = new stdClass();
    $request->status = "failed";

    if (empty($_GET['userid']) || empty($_GET['name']) || empty($_GET['plate']) || empty($_GET['model'])) {

        $request->message = "Erro: Parâmetros de requisição 'addVehicle' inválidos.";
        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    } else {

        $userid = strtolower($_GET['userid']);
        $name = strtolower($_GET['name']);
        $plate = strtolower($_GET['plate']);
        $model = strtolower($_GET['model']);

        // Check if plate already exists
        $sql = "SELECT * FROM vehicles WHERE plate = '" . $plate . "'";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {

            $request->message = "Erro: Placa já cadastrada, por favor tente novamente ou entre em contato conosco.";
            $request = json_encode($request);
            showResponse($request);
            saveLog($conn, $userid, "Add Vehicle", "Falha ao adicionar veiculo com placa ja cadastrada, placa: " . $plate);
            $conn->close();

        } else {

            // Subscribe new vehicle
            $sql = "INSERT INTO vehicles ( user_id, type, name, plate, model, status, created, modified ) VALUES ( '" . $userid . "', 'car', '" . $name . "', '" . $plate . "', '" . $model . "', 'idle', NOW(), NOW() )";
            $result = $conn->query($sql);

            if ($result === true) {

                $request->status = "success";
                $request->message = "Cadastro de veículo realizado com sucesso.";
                $request = json_encode($request);
                showResponse($request);
                saveLog($conn, $userid, "Add Vehicle", "Novo veiculo adicionado, placa: " . $plate);
                $conn->close();

            } else {

                $request->message = "Erro: Não foi possível realizar o cadastro do veículo, tente novamente mais tarde (" . $conn->error . ")";
                $request = json_encode($request);
                showResponse($request);
                $conn->close();

            }

        }

    }

}

function getVehicles($conn)
{

    // Request example: http://transitabile.com.br/api.php?userid=8&type=getvehicles

    $request = new stdClass();
    $request->status = "failed";

    if (empty($_GET['userid'])) {

        $request->message = "Erro: Parâmetros de requisição 'getVehicles' inválidos.";
        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    } else {

        $userid = strtolower($_GET['userid']);

        $sql = "SELECT * FROM vehicles WHERE user_id = '" . $userid . "'";
        $result = $conn->query($sql);

        $results = $result->num_rows;

        $request->status = "success";
        $request->results = $results;
        $request->message = $results . " veículos encontrados.";

        $list = array();

        while ($row = $result->fetch_assoc()) {

            $list[] = array('id' => $row["id"], 'userid' => $row["user_id"], 'type' => $row["type"], 'name' => $row["name"], 'plate' => $row["plate"], 'model' => $row["model"], 'status' => $row["status"], 'created' => $row["created"], 'modified' => $row["modified"]);

        }

        $request->list = $list;

        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    }

}

function updateVehicle($conn)
{

    // Request example: http://transitabile.com.br/api.php?userid=8&type=updatevehicle&id=12&plate=pvd5247&model=uno

    $request = new stdClass();
    $request->status = "failed";

    if (empty($_GET['id']) || empty($_GET['plate']) || empty($_GET['model'])) {

        $request->message = "Erro: Parâmetros de requisição 'updateVehicle' inválidos.";
        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    } else {

        $id = strtolower($_GET['id']);
        $plate = strtolower($_GET['plate']);
        $model = strtolower($_GET['model']);

        $sql = "UPDATE vehicles SET plate = '" . $plate . "', model = '" . $model . "' WHERE id = " . $id;
        $result = $conn->query($sql);

        if ($result === true) {

            $request->status = "success";
            $request->message = "Atualização realizada com sucesso.";
            $request = json_encode($request);
            showResponse($request);
            saveLog($conn, $_GET['userid'], "Update Vehicle", "Informações do veiculo atualizadas, id: " . $id);
            $conn->close();

        } else {

            $request->message = "Erro: Não foi possível realizar a atualização, tente novamente mais tarde (" . $conn->error . ")";
            $request = json_encode($request);
            showResponse($request);
            $conn->close();

        }

    }

}

function deleteVehicle($conn)
{

    // Request example: http://transitabile.com.br/api.php?userid=8&type=deletevehicle&id=13

    $request = new stdClass();
    $request->status = "failed";

    if (empty($_GET['id'])) {

        $request->message = "Erro: Parâmetros de requisição 'deleteVehicle' inválidos.";
        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    } else {

        $id = strtolower($_GET['id']);

        $sql = "DELETE FROM vehicles WHERE id = " . $id;
        $result = $conn->query($sql);

        if ($result === true) {

            $request->status = "success";
            $request->message = "Veículo removido com sucesso.";
            $request = json_encode($request);
            showResponse($request);
            saveLog($conn, $_GET['userid'], "Delete Vehicle", "Veiculo deletado do sistema, id: " . $id);
            $conn->close();

        } else {

            $request->message = "Erro: Não foi possível remover o veículo, tente novamente mais tarde (" . $conn->error . ")";
            $request = json_encode($request);
            showResponse($request);
            $conn->close();

        }

    }

}

function reportVehicle($conn)
{

    // Request example: http://transitabile.com.br/api.php?userid=1&type=reportvehicle&plate=PVD5247

    $request = new stdClass();
    $request->status = "failed";

    if (empty($_GET['plate'])) {

        $request->message = "Erro: Parâmetros de requisição 'reportVehicle' inválidos.";
        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    } else {

        $userid = strtolower($_GET['userid']);
        $plate = strtolower($_GET['plate']);

        // Check if plate already exists
        $sql = "SELECT * FROM vehicles WHERE plate = '" . $plate . "'";

        $result = $conn->query($sql);


        if ($result->num_rows > 0) {

            $row = $result->fetch_assoc();

            if ($row["status"] == "irregular") {

                $sql = "INSERT INTO proceedings ( user_id, plate, created, modified ) VALUES ( '" . $userid . "', '" . $plate . "', NOW(), NOW() )";
                $result = $conn->query($sql);

                $request->status = "success";
                $request->message = "Veículo irregular autuado.";
                $request = json_encode($request);
                showResponse($request);
                saveLog($conn, $_GET['userid'], "Report Vehicle", "Veiculo irregular autuado: " . $plate);
                $conn->close();

            } else {

                $request->status = "success";
                $request->message = "Veículo em situação regular.";
                $request = json_encode($request);
                showResponse($request);
                saveLog($conn, $_GET['userid'], "Report Vehicle", "Veiculo em situação regular fiscalizado: " . $plate);
                $conn->close();

            }

        } else {

            $request->message = "Erro: Placa não cadastrada no sistema.";
            $request = json_encode($request);
            showResponse($request);
            saveLog($conn, $_GET['userid'], "Report Vehicle", "Veiculo com placa não cadastrada no sistema fiscalizado: " . $plate);
            $conn->close();

        }

    }

}

function updateUserInfo($conn)
{

    // Request example: http://transitabile.com.br/api.php?userid=8&type=updateuserinfo&name=n&email=andre@gameloop.com.br&pass=123&cpf=234

    $request = new stdClass();
    $request->status = "failed";

    if (empty($_GET['userid']) || empty($_GET['name']) || empty($_GET['email']) || empty($_GET['pass']) || empty($_GET['cpf'])) {

        $request->message = "Erro: Parâmetros de requisição 'addVehicle' inválidos.";
        $request = json_encode($request);
        showResponse($request);
        $conn->close();

    } else {

        $userid = strtolower($_GET['userid']);
        $name = strtolower($_GET['name']);
        $email = strtolower($_GET['email']);
        $pass = strtolower($_GET['pass']);
        $cpf = strtolower($_GET['cpf']);

        $sql = "UPDATE users SET name = '" . $name . "', email = '" . $email . "', password = '" . $pass . "', cpf = '" . $cpf . "', modified = 'NOW()' WHERE id = " . $userid;
        $result = $conn->query($sql);

        if ($result === true) {

            $sql = "SELECT * FROM users WHERE id = " . $userid;
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {

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
                $request = json_encode($request);
                showResponse($request);
                saveLog($conn, $_GET['userid'], "Update User", "Informações de usuario atualizadas.");
                $conn->close();

            } else {

                $request->message = "Erro: Não foi possível realizar a atualização, tente novamente mais tarde ( User id not found )";
                $request = json_encode($request);
                showResponse($request);
                $conn->close();

            }

        } else {

            $request->message = "Erro: Não foi possível realizar a atualização, tente novamente mais tarde (" . $conn->error . ")";
            $request = json_encode($request);
            showResponse($request);
            $conn->close();

        }

    }

}

// Park Lots Requests:

function getParkLotsNear($lat, $lng, $distance)
{

}

function parkLotCheckIn($parklotid)
{


}

function parkLotCheckOut($parklotid)
{


}

// Other Requests:

function checkSystem()
{

    $request->status = "success";
    $request->message = "Sistema online.";
    $request = json_encode($request);
    showResponse($request);

}

function saveLog($conn, $userid, $type, $msg)
{

    $sql = "INSERT INTO logs ( type, user_id, vehicle_id, parklot_id, name, description, created, modified ) VALUES ( '" . $type . "','" . $userid . "', '0', '0', 'log','" . $msg . "',NOW() , NOW())";
    $result = $conn->query($sql);

}

function setSale($userid, $type, $value, $created, $modified, $userid)
{


}

function showResponse($response)
{

    $response = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
    }, $response);

    echo $response;

}

?>