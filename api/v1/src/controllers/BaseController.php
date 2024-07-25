<?php

// BaseController.php
// Clase padre de todos los contrioladores del API
// LineaExpressApp

namespace Controllers;

class BaseController
{
    protected $f3;
    protected ?\ApiAuth $auth;

    public function __construct()
    {
        $this->f3 = \Base::instance();
        $this->auth = null;
    }

    public function checkPostParamsOrDie()
    {
        if ($this->f3->BODY == null) {
            http_response_code(400);
            echo json_encode([ 'message' => 'No se recibieron parametros.' ]);
            die;
        }
    }

    public function trimx($str)
    {
        return trim(str_replace(array( '\r\n', '\n', '\r' ), '', $str));
    }

    public function checkRequiredParametersOrDie(array $reqParamList, array $params): void
    {
        $params = array_map(array( $this, 'trimx' ), $params);
        $params = array_filter($params, 'strlen');

        $missing = [];
        foreach ($reqParamList as $paramName) {
            if (!array_key_exists($paramName, $params)) {
                $missing[] = $paramName;
            }
        }
        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'No se recibieron los parametros necesarios (' . implode(', ', $missing) . ')' ]);
            die;
        }
    }

    public function checkUserSetPasswordOrDie(): void
    {
        $user = new \Data\UserModel();
        $lr = $user->getUserById($this->auth->getUserId());
        if ($lr === false) {
            http_response_code(500);
            echo json_encode([ 'message' => 'Error al cargar usuario' ]);
            die;
        }
        if ($user[ 'user_pass_set' ] == 0) {
            http_response_code(401);
            echo json_encode([ 'message' => 'El usuario no tiene contraseña personalizada.' ]);
            die;
        }
    }

    public function hasAuthOrDie()
    {
        if ($this->auth == null) {
            $codec = new \JWTCodec($_ENV[ 'SECRET_KEY' ]);
            $this->auth = new \ApiAuth($codec);
        }

        if (!$this->auth->authenticateAccessToken()) {
            die;
        }
    }

    public function respondNotImplemented(string $errorDesc = ''): void
    {
        http_response_code(501);
        $res = [ 'message' => 'No implementado' ] ;
        if ($errorDesc != '') {
            $res[ 'error' ] = $errorDesc;
        }
        echo json_encode($res);
        die;
    }

    public function respondNotFound(string $id): void
    {
        http_response_code(404);
        echo json_encode([ 'message' => "Recurso con ID $id no encontrado." ]);
        die;
    }

    public function respondUnprocessableEntity(array $errors): void
    {
        http_response_code(422);
        echo json_encode([ 'message' => 'Peticion no puede ser procesada.', 'errors' => $errors ]);
        die;
    }

    public function getProcModelInstance(int $id_procedure)
    {
        switch ($id_procedure) {
            case 1:
                return new \Data\Proc01Model();
                break;
            case 2:
                return  new \Data\Proc02Model();
                break;
            case 3:
                return  new \Data\Proc03Model();
                break;
            case 4:
                return  new \Data\Proc04Model();
                break;
            case 5:
                return  new \Data\Proc05Model();
                break;
            default:
                $this->respondNotImplemented();
        }
    }

    public function logErr($data)
    {
        $cn = get_class($this);
        $cn = preg_replace("/[^a-zA-Z0-9]/", ".", $cn);
        \error_log("liex $cn " . $data); // stores in apache error log
    }

    public function getParamsFromRequestBodyOrDie(): array
    {
        $paramStr = $this->f3->BODY;
        $paramStr = mb_convert_encoding($paramStr, "UTF-8", "auto");
        $params = json_decode($paramStr, true);

        if ($error = json_last_error()) {
            $errorReference = [
                JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded.',
                JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON.',
                JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded.',
                JSON_ERROR_SYNTAX => 'Syntax error.',
                JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded.',
                JSON_ERROR_RECURSION => 'One or more recursive references in the value to be encoded.',
                JSON_ERROR_INF_OR_NAN => 'One or more NAN or INF values in the value to be encoded.',
                JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given.',
            ];
            $errStr = isset($errorReference[$error]) ? $errorReference[$error] : "Unknown error ($error)";
            $this->logErr('JSON error: ' . $paramStr . "\r\nError: $errStr");
        }

        if ($params == null) {
            $this->logErr('Invalid JSON in params: ' . $paramStr);
            http_response_code(400);
            echo json_encode([ 'message' => 'No se recibieron parametros.' ]);
            die;
        }

        // trim all values from $params
        array_walk_recursive($params, function (&$value) {
            $value = trim($value);
        });

        // remove empty values from $params
        $params = array_filter($params, function ($value) {
            return $value !== '';
        });

        return $params;
    }


    public function getUserData($user_id)
    {
        $user = new \Data\UserModel();
        $user->load(['id=?', $user_id]);
        if ($user->dry()) {
            return null;
        }
        return $user;
    }

    public function checkCurrentUserIdAdminOrDie($user)
    {
        if ($user->usertype < 3 || $user->usertype > 4) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Solo usuarios administradores pueden obtener informacion de usuarios.' ]);
            die;
        }
    }

    public function amIAdmin($user) {
        return $user->usertype == 3 || $user->usertype == 4;
    }

    public function removeNonNumericChars($str)
    {
        return preg_replace('/[^0-9]/', '', $str);
    }
    //cambio 13/02/24
    public function removeSpaces($str) {
        return str_replace(' ', '', $str);
    }

    public function db_log($event)
    {
        $this->f3->DB->exec('INSERT INTO applog (event) VALUES (?)', [$event]);
    }

    public function paramArrayToUppercase(array $params)
    {
        $newParams = [];
        foreach ($params as $key => $value) {
            $newParams[ \strtolower($key) ] = \trim(\strtoupper($value));
        }
        return $newParams;
    }

    public function setUserSentriData($user_id, $sentri, $sentri_ed)
    {
        $user = new \Data\UserModel();
        $user->load(['id=?', $user_id]);
        if ($user->dry()) {
            return false;
        }
        if (trim($user->sentri_number) == "" && trim($sentri) != "") {
            $user->sentri_number = $sentri;
            $user->sentri_exp_date = $sentri_ed;
            $user->save();
        } 
        
        return true;
    }

    public function genProcPDF($idp, $id, $is_cancelled = false)
    {
        // BEGIN: create pdf with images, remove image files and send email
        $data = $this->f3->get('DB')->exec(
            '
                        select fi.file_name, fi.file_type_desc, fi.status
                        from vw_file_info fi 
                        where fi.id_proc=:id and fi.id_procedure=:idproc 
                        order by fi.id_file_type
                        ',
            array(':idproc' => $idp, ':id' => $id)
        );
        if (count($data) > 0) {
            $descs=[];
            $paths=[];
            foreach ($data as $row) {
                $descs[]=$row['file_type_desc'];
                $paths[]=$row['file_name'];
            }

            // get data from proc views
            switch ($idp) {
                case 1:
                    $pv = new \Data\VUserProc01Model();
                    break;
                case 2:
                    $pv = new \Data\VUserProc02Model();
                    break;
                case 3:
                    $pv = new \Data\VUserProc03Model();
                    break;
                case 4:
                    $pv = new \Data\VUserProc04Model();
                    break;
                case 5:
                    $pv = new \Data\VUserProc05Model();
                    break;
                default:
                    $this->respondUnprocessableEntity(["BaseController::genProcPDF Informacion de tramite invalida: idp=$idp, id=$id"]);
                    break;
            }
            $lr = $pv->loadByID($id);
            $lr = $lr->cast();

            $pdfname = str_pad($idp, 4, "0", STR_PAD_LEFT) . str_pad($id, 6, "0", STR_PAD_LEFT) . ".pdf";
            \Util\PDF::createPDF2($lr, $descs, $paths, FileController::DOC_DIR . $pdfname, $is_cancelled);

            // delete all files in $paths array
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
        // END: pdf creation
    }

    public function cancelProcedure($id_procedure, $id, $comments = '')
    {
        // cancel procedure
        $proc = $this->getProcModelInstance($id_procedure);
        $proc->load(['id=? and id_procedure=?', $id, $id_procedure]);
        if ($proc->dry()) {
            $this->respondUnprocessableEntity(["cancelProcedure. No se puede procesar: $id"]);
        }
        $proc->id_procedure_status = 100;
        $proc->last_update_dt = date('Y-m-d H:i:s');
        $proc->comments .= ' ' . $comments;
        $proc->save();

        // generate pdf
        $this->genProcPDF($id_procedure, $id, true);

        // delete appointment (if any)
        $this->f3->get('DB')->exec(
            'delete from appointments where id_up = :id and id_procedure = :idp',
            array(':id' => $id, ':idp' => $id_procedure)
        );

        // delete documents (if any)
        $data = $this->f3->get('DB')->exec(
            '
            select fi.file_name, fi.file_type_desc, fi.status
            from vw_file_info fi 
            where fi.id_proc=:id and fi.id_procedure=:idproc 
            order by fi.id_file_type
            ',
            array(':idproc' => $id_procedure, ':id' => $id)
        );
        if (count($data) > 0) {
            $this->f3->get('DB')->exec(
                'delete from file_info where id_proc = :id and id_procedure = :idp',
                array(':id' => $id, ':idp' => $id_procedure)
            );
        }
    }
}
