<?php

// ConfigController.php
// Funciones para administrar y retornar al cliente la informacion de configuracion
// LineaExpressApp


namespace Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ConfigController extends BaseController
{
    private const SAVE_PARAMS = [
        'mbVideoURL',
        // 'portada',
        // 'interiores'
    ];

    public function mobileConfig(): void
    {
        // $this->hasAuthOrDie();

        $config = new \Data\ConfigModel();
        $found = $config->find();

        if ($found === false) {
            \http_response_code(404);
            echo json_encode(array("message" => "No hay datos de configuración"));
        }

        $res = [];

        foreach ($found as $row) {
            $res[] = array_filter(
                $row->cast(),
                function ($key) {
                    return strpos($key, 'mb') === 0 || strpos($key, '_mx') > 0 || strpos($key, '_us') > 0;
                },
                ARRAY_FILTER_USE_KEY
            );
            break;
        }
        echo json_encode($res[0]);
    }

    public function getConfig()
    {
        $this->hasAuthOrDie();

        $config = new \Data\ConfigModel();
        $found = $config->load();

        if ($found === false) {
            \http_response_code(404);
            echo json_encode(array("message" => "No hay datos de configuración"));
        }

        echo json_encode($found->cast());
    }

    private function loadHolidaysFromExcel($path)
    {
        $errors = "";
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        try {
            $spreadsheet = $reader->load($path);
        } catch (\Exception $e) {
            $errors = "Error: Archivo de festivos inválido, no hay hojas en el archivo";
            $this->db_log("loadTagsFromExcel: " . $errors);
            return $errors;
        }
        $d=$spreadsheet->getSheet(0)->toArray();

        $expectedColumns = ["FECHA", "NOTA"];
        for ($colNo = 0; $colNo < count($expectedColumns); $colNo++) {
            if (trim(strtoupper($d[0][$colNo])) !== $expectedColumns[$colNo]) {
                $errors += "Error: No se encontro columna " + $expectedColumns[$colNo] + " en la primera hoja del archivo. ";
                $this->db_log("loadTagsFromExcel: " . $errors);
                // exit;
            }
        }

        if ($errors !== "") {
            return trim($errors);
        }

        for ($rowNo = 1; $rowNo < count($d); $rowNo++) {
            $row = $d[$rowNo];
            $fecha = trim($row[0]);
            if ($fecha === "") {
                // skip row if folio is empty
                continue;
            }
            $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha);
            $fecha = $fecha->format('Y-m-d');
            $nota = trim($row[1]);
           
            $h = new \Data\HolidayModel();
            $hf = $h->load(array("dt = :d", ":d" => $fecha));
            if ($hf !== false) {
                $this->db_log("loadTagsFromExcel: row skip because tag $folio already exists.");
                continue;
            }
            $h->reset();
            $h->dt = $fecha;
            $h->comment = $nota;
            $h->save();
        }

        return $errors;
    }

    private function loadTagsFromExcel($path)
    {
        $errors = "";
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        try {
            $spreadsheet = $reader->load($path);
        } catch (\Exception $e) {
            $errors = "Error: Archivo de tags invalido, no hay hojas en el archivo";
            $this->db_log("loadTagsFromExcel: " . $errors);
            return $errors;
        }
        $d=$spreadsheet->getSheet(0)->toArray();

        $expectedColumns = ["FOLIO"]; // , "TID", "EPC"
        for ($colNo = 0; $colNo < count($expectedColumns); $colNo++) {
            if (trim(strtoupper($d[0][$colNo])) !== $expectedColumns[$colNo]) {
                $errors += "Error: No se encontro columna " + $expectedColumns[$colNo] + " en la primera hoja del archivo. ";
                $this->db_log("loadTagsFromExcel: " . $errors);
                // exit;
            }
        }

        if ($errors !== "") {
            return trim($errors);
        }

        for ($rowNo = 1; $rowNo < count($d); $rowNo++) {
            $row = $d[$rowNo];
            $folio = trim($row[0]);
            // $tid = $row[1];
            // $epc = $row[2];
            if ($folio === "") {
                // skip row if folio is empty
                $this->db_log("loadTagsFromExcel: row skip because no folio is empty.");
                continue;
            }
            $tag = new \Data\TagModel();
            $tf = $tag->load(array("tag = :tag", ":tag" => $folio));
            if ($tf !== false) {
                $this->db_log("loadTagsFromExcel: row skip because tag $folio already exists.");
                continue;
            }
            $tag->reset();
            $tag->tag = $folio;
            // $tag->tid = $tid;
            // $tag->epc = $epc;
            $tag->save();
        }
    }

    public function setConfig()
    {
        $this->hasAuthOrDie();

        if (empty($_POST)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'No se recibieron parametros.' ]);
            die;
        }

        $params = $_POST;
        $this->checkRequiredParametersOrDie(self::SAVE_PARAMS, $params);

        $config = new \Data\ConfigModel();
        $found = $config->load();
        $found->mbVideoURL = $params['mbVideoURL'];
        
        $found->anual_zaragoza_mx = $params['anual_zaragoza_mx'] ? $params['anual_zaragoza_mx'] : 0;
        $found->anual_lerdo_mx = $params['anual_lerdo_mx'] ? $params['anual_lerdo_mx'] : 0; 
        $found->anual_mixto_mx = $params['anual_mixto_mx'] ? $params['anual_mixto_mx'] : 0;
        $found->anual_zaragoza_us = $params['anual_zaragoza_us'] ? $params['anual_zaragoza_us'] : 0;
        $found->anual_lerdo_us = $params['anual_lerdo_us'] ? $params['anual_lerdo_us'] : 0;
        $found->anual_mixto_us = $params['anual_mixto_us'] ? $params['anual_mixto_us'] : 0;
        $found->saldo_zaragoza1_mx = $params['saldo_zaragoza1_mx'] ? $params['saldo_zaragoza1_mx'] : 0;
        $found->saldo_zaragoza2_mx = $params['saldo_zaragoza2_mx'] ? $params['saldo_zaragoza2_mx'] : 0;
        $found->saldo_zaragoza1_us = $params['saldo_zaragoza1_us'] ? $params['saldo_zaragoza1_us'] : 0;
        $found->saldo_zaragoza2_us = $params['saldo_zaragoza2_us'] ? $params['saldo_zaragoza2_us'] : 0;

        $found->anual_zaragoza_mx_r = $params['anual_zaragoza_mx_r'] ? $params['anual_zaragoza_mx_r'] : 0;
        $found->anual_lerdo_mx_r = $params['anual_lerdo_mx_r'] ? $params['anual_lerdo_mx_r'] : 0;
        $found->anual_mixto_mx_r = $params['anual_mixto_mx_r'] ? $params['anual_mixto_mx_r'] : 0;
        $found->anual_zaragoza_us_r = $params['anual_zaragoza_us_r'] ? $params['anual_zaragoza_us_r'] : 0;
        $found->anual_lerdo_us_r = $params['anual_lerdo_us_r'] ? $params['anual_lerdo_us_r'] : 0;
        $found->anual_mixto_us_r = $params['anual_mixto_us_r'] ? $params['anual_mixto_us_r'] : 0;
        $found->saldo_zaragoza1_mx_r = $params['saldo_zaragoza1_mx_r'] ? $params['saldo_zaragoza1_mx_r'] : 0;
        $found->saldo_zaragoza2_mx_r = $params['saldo_zaragoza2_mx_r'] ? $params['saldo_zaragoza2_mx_r'] : 0;
        $found->saldo_zaragoza1_us_r = $params['saldo_zaragoza1_us_r'] ? $params['saldo_zaragoza1_us_r'] : 0;
        $found->saldo_zaragoza2_us_r = $params['saldo_zaragoza2_us_r'] ? $params['saldo_zaragoza2_us_r'] : 0;

        $found->pago_minimotp_mx = $params['pago_minimotp_mx'];
        $found->mbPuenteLive1 = $params['mbPuenteLive1'];
        $found->mbPuenteLive2 = $params['mbPuenteLive2'];
        $found->mbPuenteLive3 = $params['mbPuenteLive3'];
        $found->mbPuenteLive4 = $params['mbPuenteLive4'];
        $found->mbPuenteLive5 = $params['mbPuenteLive5'];
        $found->mbPuenteLive6 = $params['mbPuenteLive6'];
        $found->mbPuenteLive7 = $params['mbPuenteLive7'];
        $found->wsapkey = $params['wsapkey'];

        $found->citas_inicio = $params['citas_inicio'];
        $found->citas_fin = $params['citas_fin'];

        $found->save();

        $errores = [];
        $mensajes = [];

        if (!empty($_FILES)) {
            if (!is_writable('/tmp')) {
                http_response_code(500);
                echo json_encode([ 'message' => 'No es posible guardar archivo en directorio temporal.' ]);
                die;
            }

            if (!is_writable(FileController::UPLOAD_DIR)) {
                http_response_code(500);
                echo json_encode([ 'message' => 'No es posible guardar archivo en carpeta de servidor.' ]);
                // $this->logErr('No es posible guardar archivo en carpeta de servidor.');
                die;
            }

            if (array_key_exists('portada', $_FILES)) {
                $upload_file_name = '/var/www/apis/config/portada.jpg';

                if (move_uploaded_file($_FILES[ 'portada' ][ 'tmp_name' ], $upload_file_name)) {
                    $mensajes[] = 'Archivo de portada recibido';
                } else {
                    $errores[] = 'Error en recepcion/procesamiento de archivo de portada: ' . $_FILES[ 'portada' ][ 'error' ];
                }
            }

            if (array_key_exists('interiores', $_FILES)) {
                $upload_file_name = '/var/www/apis/config/interiores.jpg';

                if (move_uploaded_file($_FILES[ 'interiores' ][ 'tmp_name' ], $upload_file_name)) {
                    $mensajes[] = 'Archivo de interiores recibido';
                } else {
                    $errores[] = 'Error en recepcion/procesamiento de archivo de interiores: ' . $_FILES[ 'interiores' ][ 'error' ];
                }
            }

            if (array_key_exists('precios', $_FILES)) {
                $upload_file_name = '/var/www/apis/config/precios.jpg';

                if (move_uploaded_file($_FILES[ 'precios' ][ 'tmp_name' ], $upload_file_name)) {
                    $mensajes[] = 'Archivo de precios recibido';
                } else {
                    $errores[] = 'Error en recepcion/procesamiento de archivo de precios: ' . $_FILES[ 'precios' ][ 'error' ];
                }
            }

            // holidays
            if (array_key_exists('hdays', $_FILES)) {
                $upload_file_name = '/var/www/apis/config/tags.xlsx';

                if (move_uploaded_file($_FILES[ 'hdays' ][ 'tmp_name' ], $upload_file_name)) {
                    $errTags = $this->loadHolidaysFromExcel($upload_file_name);
                    if (!empty($errTags)) {
                        $errores[] = $errTags;
                    } else {
                        $mensajes[] = 'Archivo de festivos recibido';
                    }
                    unlink($upload_file_name);
                } else {
                    $errores[] = 'Error en recepcion/procesamiento de archivo de tags: ' . $_FILES[ 'hdays' ][ 'error' ];
                }
            }

            // carga tags
            if (array_key_exists('tagsfile', $_FILES)) {
                $upload_file_name = '/var/www/apis/config/tags.xlsx';

                if (move_uploaded_file($_FILES[ 'tagsfile' ][ 'tmp_name' ], $upload_file_name)) {
                    //echo json_encode([ 'message' => 'Archivo recibido' ]);
                    $errTags = $this->loadTagsFromExcel($upload_file_name);
                    if (!empty($errTags)) {
                        $errores[] = $errTags;
                    } else {
                        $mensajes[] = 'Archivo de tags recibido';
                    }
                    unlink($upload_file_name);
                } else {
                    // http_response_code(400);
                    // echo json_encode([ 'message' => 'Error en recepcion/procesamiento de archivo: ' . $_FILES[ 'docfile' ][ 'error' ] ]);
                    // var_dump($_FILES);
                    $errores[] = 'Error en recepcion/procesamiento de archivo de tags: ' . $_FILES[ 'tagsfile' ][ 'error' ];
                }
            }
        }

        if (empty($errores)) {
            http_response_code(200);
            echo json_encode([ 'message' => 'Configuración guardada correctamente.' ]);
        } else {
            http_response_code(400);
            echo json_encode([ 'message' => 'Error en recepcion/procesamiento de archivo(s): ' . implode(', ', $errores) ]);
        }
    }
}
