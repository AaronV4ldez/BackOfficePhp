<?php

// LDataController.php
// Consulta de informacion "legacy" que se importo de excel.
// LineaExpressApp

namespace Controllers;

class LDataController extends BaseController
{
    public function search()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(['term'], $params);

        $searchColumns = [ 
            'clave', 'puente', 'nombre_completo', 'sentri', 'rfc', 'nombre_razon',
            'telefono1', 'colonia', 'calle', 'placa2', 'num_exterior', 'saldo2',
            'ciudad', 'cp', 'correo_electronico', 'correo_electronico_fact', 'marca', 
            'sub_marca', 'modelo', 'color', 'placa', 'tag', 'cve_promo', 'modalidad', 
            'saldo', 'fecha_creacion', 'fecha_fin_vigencia', 'fecha_ultima_modificacion'
        ];
       
        // ----- tramites
        $likes = $this->likeGen($params['term'], $searchColumns);
        $data = $this->f3->get('DB')->exec("SELECT * FROM ldata WHERE $likes limit 31");

        // append $tp to $data
        $data = array_merge($data);

        echo json_encode($data);
    }

    private function likeGen($term, $columns)
    {
        $words = explode(' ', $term);
        $words = array_map('trim', $words);
        $words = array_filter($words, function ($word) {
            return $word !== '';
        });
        $likes = '';
        $cc = 0;
        foreach ($words as $w) {
            if ($cc > 0) {
                $likes .= "\n AND ";
            }
            $likes .= '(';
            foreach ($columns as $col) {
                $likes .= " $col LIKE '%$w%' OR ";
            }
            $likes = substr($likes, 0, -3);
            $likes .= ')';
            $cc++;
        }
        return $likes;
    }
}
