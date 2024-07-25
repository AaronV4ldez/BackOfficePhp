<?php

// SearchController.php
// retorna resultados de busqueda de tramites y autos a PanelWeb
// LineaExpressApp

namespace Controllers;

class SearchController extends BaseController
{
    public function search()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(['term'], $params);

        $searchColumns = ['tramite', 'tramite_status', 'usuario_nombre', 'sentri', 'veh_marca', 'veh_modelo', 'veh_color', 'veh_anio', 'veh_placas', 'tag'];

        // ----- tramites
        $likes = $this->likeGen($params['term'], $searchColumns);
        $data = $this->f3->get('DB')->exec("SELECT 'Trámite' as tipob, v.* FROM vw_proc_search v WHERE $likes limit 21");

        // ----- autos de TP
        $searchColumns = ['marca', 'linea', 'color', 'modelo', 'placa', 'tag', 'user_name', 'user_email'];
        $likes = $this->likeGen($params['term'], $searchColumns);
        $tp = $this->f3->get('DB')->exec("SELECT 'TP Auto' as tipob, v.* FROM vw_vehicles v WHERE tipo = 0 and $likes limit 21");

        // append $tp to $data
        $data = array_merge($data, $tp);

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
