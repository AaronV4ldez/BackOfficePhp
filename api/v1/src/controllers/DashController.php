<?php

// DashController.php
// Envia informacion de graficas y reportes a PanelWeb
// LineaExpressApp

namespace Controllers;

class DashController extends BaseController
{
    private const REQ_DASH_PAYEMENTS = ['date1', 'date2'];
    
// |  2 | Tramitador          |
// |  3 | Administrador       |
// |  4 | Super Admin

    private function genericQuery(string $sql)
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $ru = $this->getUserData($this->auth->getUserId());
        if ($ru === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'La petición la realizó un usuario no válido.' ]);
            die;
        }

        if ($ru->usertype == 1 || $ru->usertype == 5) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Este reporte solo lo pueden consultar tramitadores y administradores' ]);
            die;
        }

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(self::REQ_DASH_PAYEMENTS, $params);

        $userlimit = "";
        if ($ru->usertype == 2) {
            $userlimit = " and id_user_operator = " . $ru->id;
        }

        $sql = str_replace("__userlimit__", $userlimit, $sql);
        // \var_dump($sql);
        // \var_dump($qparams);
        $res =  $this->f3->get('DB')->exec($sql, array(':f1' => $params['date1'], ':f2' => $params['date2']));

        echo json_encode($res);
    }

    public function paymentData()
    {
        $this->genericQuery(
            "
            select Puente, sum(Monto) Cobros 
            from vw_payments 
            where date(`Fecha-Hora`) between date(:f1) and date(:f2) 
            group by Puente
            "
        );
    }

    public function paymentDetail()
    {
        $this->genericQuery(
            "
            select * from vw_payments 
            where date(`Fecha-Hora`) between date(:f1) and date(:f2) 
            order by `Fecha-Hora` desc
            "
        );
    }

    public function completedProceduresSummary()
    {
        $this->genericQuery(
            "
            select tramite as Tramite, count(tramite) as Cantidad 
            from vw_user_procs 
            where id_procedure_status=6
              and finish_dt between date(:f1) and date(:f2) 
              __userlimit__
            group by tramite;
            "
        );
    }

    public function completedProceduresDetail()
    {
        $this->genericQuery(
            "
            select tramite as Tramite, tramite_status as `Estado del Trámite`, usuario_nombre as Usuario,
            usuario_email as `Correo de usuario`, operador_nombre as Tramitador, operador_email as `Correo de tramitador`,
            sentri as SENTRI, sentri_vencimiento as `Vencimiento de SENTRI`, 
            start_dt as `Inicio de tramite`, last_update_dt as `Última actualización`, finish_dt as `Fin de tramite`
            from vw_user_procs 
            where id_procedure_status=6
              and finish_dt between date(:f1) and date(:f2) 
              __userlimit__
            "
        );
    }

    public function cancelledProceduresSummary()
    {
        $this->genericQuery(
            "
            select tramite as Tramite, count(tramite) as Cantidad 
            from vw_user_procs 
            where id_procedure_status=100
              and last_update_dt between date(:f1) and date(:f2) 
              __userlimit__
            group by tramite;
            "
        );
    }

    public function cancelledProceduresDetail()
    {
        $this->genericQuery(
            "
            select tramite as Tramite, tramite_status as `Estado del Trámite`, usuario_nombre as Usuario,
            usuario_email as `Correo de usuario`, operador_nombre as Tramitador, operador_email as `Correo de tramitador`,
            sentri as SENTRI, sentri_vencimiento as `Vencimiento de SENTRI`, 
            start_dt as `Inicio de tramite`, last_update_dt as `Última actualización`, finish_dt as `Fin de tramite`
            from vw_user_procs 
            where id_procedure_status=100
              and last_update_dt between date(:f1) and date(:f2) 
              __userlimit__
            "
        );
    }

    public function wipProceduresSummary()
    {
        $this->genericQuery(
            "
            select tramite as Tramite, count(tramite) as Cantidad 
            from vw_user_procs 
            where id_procedure_status in (2,3,4,5)
              and start_dt between date(:f1) and date(:f2) 
              __userlimit__
            group by tramite;
            "
        );
    }

    public function wipProceduresDetail()
    {
        $this->genericQuery(
            "
            select tramite as Tramite, tramite_status as `Estado del Trámite`, usuario_nombre as Usuario,
            usuario_email as `Correo de usuario`, operador_nombre as Tramitador, operador_email as `Correo de tramitador`,
            sentri as SENTRI, sentri_vencimiento as `Vencimiento de SENTRI`, 
            start_dt as `Inicio de tramite`, last_update_dt as `Última actualización`, finish_dt as `Fin de tramite`
            from vw_user_procs 
            where id_procedure_status in (2,3,4,5)
              and start_dt between date(:f1) and date(:f2) 
              __userlimit__
            "
        );
    }


}
