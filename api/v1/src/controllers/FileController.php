<?php

// FileController.php
// Funciones para recepcion, rechazo y autorizacion de documentos adjuntos en un tramite
// LineaExpressApp

namespace Controllers;

class FileController extends BaseController
{
    public const REQ_UPLOAD_PARAMS = [ 'filetype', 'id_proc', 'id_proc_type' ];
    public const REQ_LIST_PARAMS = [ 'id_proc', 'id_proc_type' ];
    public const REQ_REJECT_PARAMS = [ 'id', 'comment' ];
    public const REQ_ACCEPT_PARAMS = [ 'id' ];
    public const UPLOAD_DIR = '/var/www/liexupload/';
    public const DOC_DIR = '/var/www/liexdoc/';

    public function list()
    {
        $this->hasAuthOrDie();

        $params = $_GET;
        $this->checkRequiredParametersOrDie(FileController::REQ_LIST_PARAMS, $params);

        $id_proc = $params[ 'id_proc' ];
        $id_proc_type = $params[ 'id_proc_type' ];

        if (!is_numeric($id_proc) || !is_numeric($id_proc_type)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'id_proc and id_proc_type deben ser numericos.' ]);
            die;
        }

        $fi = new \Data\VFileInfoModel();
        $rl = $fi->find(array(
            'id_proc = :idp and id_procedure = :idproc', // and id_user = :idu',
            ':idp' => $id_proc,
            ':idproc' => $id_proc_type
            // ,            ':idu' => $this->auth->getUserId()
        ));

        // return empty array if no files found for current user
        if ($rl === false) {
            echo json_encode([]);
            die;
        }

        $res = [];
        if ($rl) {
            foreach ($rl as $row) {
                $row->file_name = basename($row->file_name);
                $res[] = $row->cast();
            }
        }
        echo json_encode($res);
    }

    private function getFileType(string $p_filename): string
    {
        $res = '';
        try {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $res = finfo_file($finfo, $p_filename);
            finfo_close($finfo);
        } catch (\Throwable $th) {
            $res = 'error';
        }
        return \strtolower($res);
    }

    public function upload()
    {
        $this->hasAuthOrDie();

        $this->checkUserSetPasswordOrDie();

        if (empty($_POST)) {
            http_response_code(400);
            $this->logErr('ERROR: no params received');
            echo json_encode([ 'message' => 'No se recibieron parametros.' ]);
            die;
        }

        $params = $_POST;

        $this->checkRequiredParametersOrDie(FileController::REQ_UPLOAD_PARAMS, $params);

        if (empty($_FILES)) {
            http_response_code(400);
            $this->logErr('ERROR: no file received');
            echo json_encode([ 'message' => 'No se recibio archivo.' ]);
            die;
        }

        if (!array_key_exists('docfile', $_FILES)) {
            http_response_code(400);
            $this->logErr('ERROR: no actual FILE type parameter received (docfile)');
            echo json_encode([ 'message' => "El archivo debe estar identificado como 'docfile'." ]);
            die;
        }

        $id = $params[ 'id_proc' ];
        if (!is_numeric($id)) {
            http_response_code(400);
            $this->logErr('ERROR: invalid id: ' . $id);
            echo json_encode([ 'message' => 'ID debe ser numerico' ]);
            die;
        }

        $file_type = $params[ 'filetype' ];
        if (!is_numeric($id)) {
            http_response_code(400);
            $this->logErr('ERROR: invalid filetype: ' . $file_type);
            echo json_encode([ 'message' => "'filetype' debe ser numerico" ]);
            die;
        }

        $ft = new \Data\FileTypeModel();
        $validFileTypes = $ft->loadIDsToArray();

        if (!in_array($file_type, $validFileTypes)) {
            http_response_code(400);
            $this->logErr('ERROR: invalid filetype: ' . $file_type . ' valid filetypes: ' . \implode(', ', $validFileTypes));
            echo json_encode([ 'message' => "'filetype' invalido, revise tipos validos con el administrador de la App." ]);
            die;
        }

        $proc_type = $params[ 'id_proc_type' ];
        if (!is_numeric($id)) {
            http_response_code(400);
            $this->logErr('ERROR: invalid proc_type: ' . $proc_type);
            echo json_encode([ 'message' => "'proc_type' debe ser numerico" ]);
            die;
        }

        if (!in_array($proc_type, [ '1', '2', '3', '4', '5', '6' ])) {
            http_response_code(400);
            $this->logErr('ERROR: invalid proc_type: ' . $proc_type);
            echo json_encode([ 'message' => "'proc_type' invalido, revise trámites validos con el administrador de la App." ]);
            die;
        }

        $proc = $this->getProcModelInstance($proc_type);

        $lr = $proc->loadByID($id);
    	// echo $proc_type . "\n";
    	// echo $id . "\n"; 
        if ($lr === false) {
            $this->respondNotFound($id);
        }

        $this->logErr('INFO: preupload params: ' . \var_export($params, true));

        $upload_file_name = // user_id + proc_id + proc_type_id + filetype . EXT    ej: 0008-0120-03.jpg
        FileController::UPLOAD_DIR .
        str_pad($proc->id_user, 8, '0', STR_PAD_LEFT) . '-' .
        str_pad($id, 8, '0', STR_PAD_LEFT) . '-' .
        str_pad($proc_type, 2, '0', STR_PAD_LEFT) . '-' .
        str_pad($file_type, 2, '0', STR_PAD_LEFT) . '.jpeg';
        //  . end((explode('.', $_FILES[ 'docfile' ][ 'name' ])));

        if (!is_writable('/tmp')) {
            http_response_code(500);
            $this->logErr('ERROR: /tmp not writable');
            echo json_encode([ 'message' => 'No es posible guardar archivo en directorio temporal.' ]);
            die;
        }

        if (!is_writable(FileController::UPLOAD_DIR)) {
            http_response_code(500);
            $this->logErr('ERROR: ' . FileController::UPLOAD_DIR . ' not writable');
            echo json_encode([ 'message' => 'No es posible guardar archivo en carpeta de servidor.' ]);
            die;
        }

        $fi = new \Data\FileInfoModel();
        $rl = $fi->load(
            array(
                'id_proc = :idp and id_procedure = :idpt and id_file_type = :idft and id_user = :idu',  // and file_name = :fn
                ':idp' => $id,
                ':idpt' => $proc_type,
                ':idft' => $file_type,
                ':idu' => $proc->id_user,
                // ':fn' => $upload_file_name
                )
        );

        if ($rl !== false) {
            if ($fi->approved_dt) {
                http_response_code(400);
                $this->logErr('ERROR: file already approved: ' . $upload_file_name);
                echo json_encode([ 'message' => 'Este archivo ya se habia aprobado. No se permite una nueva carga.' ]);
                die;
            }
        }

        $tmp_name = $_FILES[ 'docfile' ][ 'tmp_name' ];

        $ft = $this->getFileType($tmp_name);
        if ($ft === false) {
            http_response_code(400);
            $this->logErr('ERROR: invalid file type: ' . $tmp_name);
            echo json_encode([ 'message' => 'Tipo de archivo invalido.' ]);
            die;
        }

        if (\strpos($ft, 'pdf') !== false) {
            // get number of pages in PDF
            $image = new \Imagick($tmp_name);
            $page_count = $image->getNumberImages();

            if ($page_count < 1) {
                http_response_code(400);
                $this->logErr('ERROR: invalid file type (no pages): ' . $tmp_name);
                echo json_encode([ 'message' => 'Archivo PDF debe tener al menos una página.' ]);
                die;
            }

            $image = new \Imagick();
            $image->setResolution(144, 144);
            $image->readImage($tmp_name . '[0]');
            $image->setImageFormat("png");
            $image->setImageBackgroundColor('#ffffff');
            $image->setImageAlphaChannel(11);
            $image = $image->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $image->writeImage($upload_file_name);

            // si archivo es poliza de seguro (tipo 5), y el pdf tiene mas de una pagina, la segunda hoja se carga como imagen tipo 9 (segunda hoja de poliza)
            if ($file_type == 5 && $page_count > 1) {
                $upload_file_name2 =
                FileController::UPLOAD_DIR .
                str_pad($proc->id_user, 8, '0', STR_PAD_LEFT) . '-' .
                str_pad($id, 8, '0', STR_PAD_LEFT) . '-' .
                str_pad($proc_type, 2, '0', STR_PAD_LEFT) . '-' .
                str_pad(9, 2, '0', STR_PAD_LEFT) . '.jpeg';

                $image = new \Imagick();
                $image->setResolution(144, 144);
                $image->readImage($tmp_name . '[1]');
                $image->setImageFormat("png");
                $image->setImageBackgroundColor('#ffffff');
                $image->setImageAlphaChannel(11);
                $image = $image->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $image->writeImage($upload_file_name2);

                $fi2 = new \Data\FileInfoModel();
                $rl2 = $fi2->load(
                    array(
                        'id_proc = :idp and id_procedure = :idpt and id_file_type = :idft and id_user = :idu',  // and file_name = :fn
                        ':idp' => $id,
                        ':idpt' => $proc_type,
                        ':idft' => 9,
                        ':idu' => $proc->id_user
                        )
                );

                if ($rl2 === false) {
                    $fi2->reset();
                    $fi2 = new \DB\SQL\Mapper($this->f3->get('DB'), 'file_info');
                    $fi2[ 'file_name' ] = $upload_file_name2;
                    $fi2[ 'id_proc' ] = $id;
                    $fi2[ 'id_procedure' ] = $proc_type;
                    $fi2[ 'id_file_type' ] = 9;
                    $fi2[ 'upload_dt' ] = date('Y-m-d h:i:s', time());
                    $fi2[ 'upload_count' ] = 1;
                    $fi2[ 'id_user' ] = $this->auth->getUserId();
                } else {
                    $fi2[ 'upload_count' ] = $fi[ 'upload_count' ] + 1;
                    $fi2[ 'file_status' ] = 0;
                }
                $fi2->save();

            }
            echo json_encode([ 'message' => 'Archivo recibido' ]);
        } else {
            if (move_uploaded_file($tmp_name, $upload_file_name)) {
                echo json_encode([ 'message' => 'Archivo recibido' ]);
            } else {
                http_response_code(400);
                $this->logErr('ERROR: error in file upload: ' . $_FILES[ 'docfile' ][ 'error' ]);
                echo json_encode([ 'message' => 'Error en recepcion/procesamiento de archivo: ' . $_FILES[ 'docfile' ][ 'error' ] ]);
                // var_dump($_FILES);
            }
        }

        // save file info in table
        if ($rl === false) {
            $fi->reset();
            $fi = new \DB\SQL\Mapper($this->f3->get('DB'), 'file_info');
            $fi[ 'file_name' ] = $upload_file_name;
            $fi[ 'id_proc' ] = $id;
            $fi[ 'id_procedure' ] = $proc_type;
            $fi[ 'id_file_type' ] = $file_type;
            $fi[ 'upload_dt' ] = date('Y-m-d h:i:s', time());
            $fi[ 'upload_count' ] = 1;
            $fi[ 'id_user' ] = $this->auth->getUserId();
        } else {
            $fi[ 'upload_count' ] = $fi[ 'upload_count' ] + 1;
            $fi[ 'file_status' ] = 0;
        }
        $fi->save();

        $this->logErr('INFO: end result: ' . var_export($fi->cast(), true));
    }

    public function getImage()
    {
        // $this->hasAuthOrDie();

        $fn = $this->f3->get('PARAMS.fn');

        $file = FileController::UPLOAD_DIR . $fn;
        // var_dump( $file );

        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            // readfile( $file );
            $fp = fopen($file, 'rb');
            fpassthru($fp);
            exit;
        }
    }

    public function getPDF()
    {
        $this->hasAuthOrDie();

        $usr = $this->getUserData($this->auth->getUserId());
        if ($usr->usertype != 3 && $usr->usertype != 4 && $usr->usertype != 2) {
            http_response_code(403);
            echo json_encode([ 'message' => 'No tiene permisos para acceder a este recurso.' ]);
            die;
        }

        $fn = $this->f3->get('PARAMS.fn');

        $file = FileController::DOC_DIR . $fn;
        // var_dump( $file );

        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            // readfile( $file );
            $fp = fopen($file, 'rb');
            fpassthru($fp);
            exit;
        } else {
            http_response_code(404);
            echo json_encode([ 'message' => 'Archivo no encontrado.' ]);
            die;
        }
    }

    public function rejectFile(): void
    {
        $this->hasAuthOrDie();

        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(FileController::REQ_REJECT_PARAMS, $params);

        $id = $params[ 'id' ];
        $comment = $params[ 'comment' ];

        if (!is_numeric($id)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'ID de archivo debe ser numerico' ]);
            die;
        }
        $fi = new \Data\VFileInfoModel();
        $rl = $fi->load(array( 'id = :id', ':id' => $id ));
        if ($rl === false) {
            $this->respondNotFound($id);
        }
        $filedesc = $fi[ 'file_type_desc' ];

        $fi = new \Data\FileInfoModel();
        $fi->load(array( 'id = :id', ':id' => $id ));
        $fi[ 'file_status' ] = 1;
        $fi[ 'comment' ] = $comment;
        $fi->save();

        // get user record to send email
        $user = new \Data\UserModel();
        $rl = $user->load(array( 'id = :id', ':id' => $fi[ 'id_user' ] ));
        if ($rl === false) {
            $this->respondNotFound($fi[ 'id_user' ]);
        }
        $email = $user[ 'userlogin' ];
        $name = $user[ 'fullname' ];

        $pu = new \Controllers\ProcUtils($this);
        $pu->assignOperatorIfMissing($fi[ 'id_proc' ], $fi[ 'id_procedure' ], $this->auth->getUserId());
        $pu->setProcStatus($fi[ 'id_proc' ], $fi[ 'id_procedure' ], 3);

        $procedure = new \Data\VUserProcsModel();
        $procedure->load(array( 'id = :id and id_procedure= :idp', ':id' => $fi[ 'id_proc' ], ':idp' => $fi[ 'id_procedure' ] ));

        \Util\Mail::sendRejectedFileNotification($email, $name, $filedesc . " del tramite: " . $procedure['tramite'], $comment);

        if (!empty($user[ 'device_id' ])) {
            \Util\Notify::sendFirebaseMessage("Documento rechazado: $filedesc" . ' de su trámite: ' . $procedure['tramite'], $user[ 'device_id' ]);
        }

        echo json_encode([ 'message' => 'Documento rechazado. Se notificará a usuario.' ]);
    }

    public function setStatusPreAut(int $id, int $idp)
    {
        // check if all files have been approved, if yes then set status to 4 (pre-autorizado), send notification
        $faltan = $this->f3->get('DB')->exec("select count(*) cnt from file_info where id_proc = :id and id_procedure = :idp and approved_dt is null;", array(':id' => $id, ':idp' => $idp));
        if ($faltan[0]['cnt'] == 0) {
            $proc = $this->getProcModelInstance($idp);
            $fnd = $proc->load(array('id = :id', ':id' => $id));
            if ($fnd !== false) {
                $proc->id_procedure_status = 4;
                $proc->save();

                $up = new \Data\VUserProcsModel();
                $fnd = $up->load(array('id = :id and id_procedure = :idp', ':id' => $id, ':idp' => $idp));
                if ($fnd !== false) {
                    $email = $up['usuario_email'];
                    $name = $up['usuario_nombre'];
                    $tramite = $up['tramite'];
                    $startdt = $up['start_dt'];
                    $finishdt = $up['finish_dt'];
                    $operador_nombre = $up['operador_nombre'];
                    if (empty($up["sentri"])) {
                        $up["sentri"] = $proc["sentri"];
                        $up->save();
                    }

                    $elsubproc = 0;
                    try {
                        $elsubproc = $proc->exists('subproc') ? $proc["subproc"] : 0;
                    } catch (\Throwable $th) {
                        $elsubproc = 0;
                    }

                    $this->logErr("SetStatusPreaut: idp: $idp, id: $proc->id, subproc: $elsubproc");

                    if ($idp == 2 || $idp == 4 || $idp == 5 ||
                        ($idp == 3 && $elsubproc && \in_array(\intval($elsubproc), array(3, 4, 5)))
                    ) {
                        $proc->id_procedure_status = 6;
                        $proc->finish_dt = date("Y-m-d G:i:s");
                        $proc->last_update_dt = date("Y-m-d G:i:s");
                        $proc->save();

                        $this->genProcPDF($idp, $id);

                        \Util\Mail::sendApprovedNotification($email, $name, $tramite, false);

                        $user = new \Data\UserModel();
                        $fnd = $user->load(array('userlogin = :email', ':email' => $email));
                        $device_id = $user['device_id'];

                        if (!empty($device_id)) {
                            \Util\Notify::sendFirebaseMessage("Trámite autorizado: $tramite", $device_id);
                        }

                        $this->setUserSentriData($this->auth->getUserId(), $proc->sentri, $proc->sentri_vencimiento);
                    } else {
                        $proc->id_procedure_status = 4;
                        $proc->last_update_dt = date("Y-m-d G:i:s");
                        $proc->save();

                        \Util\Mail::sendPreApprovedNotification($email, $name, $tramite);

                        $user = new \Data\UserModel();
                        $fnd = $user->load(array('userlogin = :email', ':email' => $email));
                        $device_id = $user['device_id'];

                        if (!empty($device_id)) {
                            \Util\Notify::sendFirebaseMessage("Trámite pre-autorizado: $tramite", $device_id);
                        }
                    }
                }
            }
        }
    }

    public function acceptFile(): void
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();
        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(FileController::REQ_ACCEPT_PARAMS, $params);
        $id = $params[ 'id' ];
        if (!is_numeric($id)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'ID de archivo debe ser numerico' ]);
            die;
        }
        $fi = new \Data\FileInfoModel();
        $fi->load(array( 'id = :id', ':id' => $id ));
        $fi[ 'file_status' ] = 2;
        $fi[ 'approved_dt' ] = date('Y-m-d h:i:s', time());
        $fi[ 'id_user_operator' ] = $this->auth->getUserId();
        $fi->save();

        $user = new \Data\UserModel();
        $user->load(array( 'id = :id', ':id' => $fi[ 'id_user' ] ));

        $filedesc = new \Data\FileTypeModel();
        $filedesc->load(array( 'id = :id', ':id' => $fi[ 'id_file_type' ] ));

        $procedure = new \Data\VUserProcsModel();
        $procedure->load(array( 'id = :id and id_procedure= :idp', ':id' => $fi[ 'id_proc' ], ':idp' => $fi[ 'id_procedure' ] ));

        $pu = new \Controllers\ProcUtils($this);
        $pu->assignOperatorIfMissing($fi[ 'id_proc' ], $fi[ 'id_procedure' ], $this->auth->getUserId());

        $this->setStatusPreAut($fi[ 'id_proc' ], $fi[ 'id_procedure' ]);

        if (!empty($user[ 'device_id' ])) {
            \Util\Notify::sendFirebaseMessage("Documento aprobado: " . $filedesc['description'] . ", de su tramite: " . $procedure['tramite'], $user[ 'device_id' ]);
        }

        echo json_encode([ 'message' => 'Aprobación de archivo se registro con exito.' ]);
    }

    public function getFileTypes()
    {
        $this->hasAuthOrDie();

        $ft = new \Data\FileTypeModel();
        echo \json_encode(['data' => $ft->loadAll()]);
    }
}
