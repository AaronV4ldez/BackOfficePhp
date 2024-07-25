<?php

// PDF.php
// Funciones para generacion de PDFs en base a informacion de tramites
// LineaExpressApp

namespace Util;

require('fpdf.php');

class PDF
{
    public static function createPDF($descriptions, $filePaths, $header1, $header2, $outFileName)
    {
        $doc = new \FPDF();

        $doc->AddPage();
        $doc->SetFont('Arial', 'B', 12);
        $doc->Text(10, 20, $header1);
        $doc->Text(10, 26, $header2);

        $doc->SetFont('Arial', 'B', 15);
        for ($i = 0; $i < count($descriptions); $i++) {
            if ($i !== 0) {
                $doc->AddPage();
            }
            $doc->Text(10, 35, $descriptions[$i]);
            $doc->Image($filePaths[$i], 10, 40, 175);
        }

        $doc->Output('F', $outFileName);

        $descriptions_str = implode(", ", $descriptions);
        $filepaths_str = implode(", ", $filePaths);
    }


    public const fieldNames = [
        "__customer_info" => "Información del cliente",
        "usuario_nombre" => "Nombre de Usuario",
        "usuario_email" => "Email de usuario",
        "sentri" => "Sentri",
        "sentri_vencimiento" => "Vencimiento de Sentri",
        "operador_nombre" => "Tramitador",
        "operador_email" => "Email de tramitador",
        "operador_cita_nombre" => "Tramitador (Cita)",
        "operador_cita_email" => "Email de tramitador (Cita)",
        "start_dt" => "Fecha de inicio",
        "last_update_dt" => "Ultima actualización",
        "finish_dt" => "Fecha de finalización",
        "dom_calle" => "Calle",
        "dom_numero_ext" => "Número exterior",
        "dom_colonia" => "Colonia",
        "dom_ciudad" => "Ciudad",
        "dom_estado" => "Estado",
        "dom_cp" => "Codigo postal",
        "__customer_invoice" => "Datos de facturación",
        "fac_razon_social" => "Razon social",
        "fac_rfc" => "RFC",
        "fac_dom_fiscal" => "Domicilio fiscal",
        "fac_email" => "Correo electronico (fiscal)",
        "fac_telefono" => "Telefono (fiscal)",
        "__customer_vehicle" => "Información de vehiculo",
        "veh_marca" => "Marca de vehiculo",
        "veh_modelo" => "Modelo de vehiculo",
        "veh_color" => "Color de vehiculo",
        "veh_anio" => "Año de vehiculo",
        "veh_placas" => "Placas de vehiculo",
        "veh_origen" => "Origen de vehiculo",
        "__customer_vehicle1" => "Información de vehiculo 1",
        "veh1_marca" => "Marca de vehiculo origen",
        "veh1_modelo" => "Modelo de vehiculo origen",
        "veh1_color" => "Color de vehiculo origen",
        "veh1_anio" => "Año de vehiculo origen",
        "veh1_placas" => "Placas de vehiculo origen",
        "veh1_origen" => "Origen de vehiculo origen",
        "num_tag" => "Número de tag",
        "num_tag1" => "Número de tag 1",
        "__customer_vehicle2" => "Información de vehiculo 2",
        "veh2_marca" => "Marca de vehiculo destino",
        "veh2_modelo" => "Modelo de vehiculo destino",
        "veh2_color" => "Color de vehiculo destino",
        "veh2_anio" => "Año de vehiculo destino",
        "veh2_placas" => "Placas de vehiculo destino",
        "veh2_origen" => "Origen de vehiculo destino",
        "num_tag2" => "Número de tag 2",
        "__customer_proc" => "Información de trámite",
        "seguro_origen" => "Origen de seguro",
        "conv_saldo" => "Saldo de convenio",
        "conv_anualidad" => "Anualidad de convenio",
        "tramite" => "Tramite",
        "comments" => "Comentario",
        "motivo" => "Motivo",
        "tramite_status" => "Estato del tramite",
        "tag" => "Tag asignado",
    ];

    private static function i($text)
    {
        return iconv('UTF-8', 'iso-8859-1', $text);
    }

    private function is_jpeg(&$pict)
    {
        return (bin2hex($pict[0]) == 'ff' && bin2hex($pict[1]) == 'd8');
    }

    private function is_png(&$pict)
    {
        return (bin2hex($pict[0]) == '89' && $pict[1] == 'P' && $pict[2] == 'N' && $pict[3] == 'G');
    }


    public static function createPDF2(array $fields, array $descriptions, array $filePaths, $outFileName, $is_cancelled = false)
    {
        $doc = new \FPDF();
        $doc->AddPage();
        // header
        $doc->Image('/var/www/shared/cabezal.jpg', 10, 10, 100);
        $doc->SetFont('Arial', 'B', 15);
        $cancel_txt = "";

        if (array_key_exists('id_procedure_status', $fields)) {
            if ($fields['id_procedure_status'] == 100) {
                $cancel_txt = "-CANCELADO-";
            }
        }

        if (array_key_exists('tramite', $fields)) {
            $doc->Text(10, 40, self::i($fields['tramite'] . " " . $cancel_txt));
        }

        // tramite
        $doc->SetFont('Arial', 'B', 8);
        $col1 = 10;
        $col2 = 100;
        $startRow = 50;
        $rowsize = 5;
        $fieldNum = 2;
        $currentRow = $startRow;

        foreach (self::fieldNames as $key => $value) {
            if (array_key_exists($key, $fields) || $key[0] === '_') {
                $keyIndex = array_search($key, array_keys(self::fieldNames));

                if (str_starts_with($key, '__') && array_key_exists(array_keys(self::fieldNames)[$keyIndex + 1], $fields)) {
                    if ($keyIndex > 0) {
                        $currentRow += $rowsize * 2;
                    }
                    $doc->SetFont('Arial', 'B', 12);
                    $doc->Text($col1, $currentRow, self::i($value));
                    $currentRow += $rowsize + ($rowsize / 2);

                    $fieldNum = 2;

                    $doc->SetFont('Arial', 'B', 8);
                } else {
                    if (str_starts_with($key, '__')) {
                        continue;
                    }
                    $doc->Text($fieldNum % 2 == 0 ? $col1 : $col2, $currentRow, self::i($value));
                    $doc->Text(($fieldNum % 2 == 0 ? $col1 : $col2) + 40, $currentRow, self::i($fields[$key]));
                    $currentRow += ($fieldNum % 2 !== 0 ? $rowsize : 0);
                    $fieldNum++;
                }
            }
        }

        // documents
        $doc->SetFont('Arial', 'B', 15);
        for ($i = 0; $i < count($descriptions); $i++) {
            $doc->AddPage();
            $doc->Text(10, 35, $descriptions[$i]);

            if (\file_exists($filePaths[$i])) {
                $imgt = exif_imagetype($filePaths[$i]);
                // IMAGETYPE_GIF IMAGETYPE_JPEG
                $doc->Image($filePaths[$i], 10, 40, 175, 0, $imgt == IMAGETYPE_JPEG ? 'JPG' : 'PNG');
            } else {
                $doc->Text(10, 40, "No existe el archivo: " . $filePaths[$i]);
            }
        }

        $doc->Output('F', $outFileName);

        $descriptions_str = implode(", ", $descriptions);
        $filepaths_str = implode(", ", $filePaths);
    }
}
