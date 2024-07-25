<?php

// FilaController.php
// Funciones de que regresan listados de tramites en proceso, terminadosm, etc. para pantalla principal de PanelWeb
// LineaExpressApp

namespace Controllers;

class FilaController extends BaseController
{
    private function findToArray($query)
    {
        $result = array();
        foreach ($query as $row) {
            $result[] = $row->cast();
        }
        return $result;
    }

    public function queue(): void
    {
        $this->hasAuthOrDie();

        $fila = new \Data\VUserProcsModel();
        $users = $fila->findAssignables();

        echo json_encode($this->findToArray($users));
    }

    public function scheduled(): void
    {
        $this->hasAuthOrDie();
        $user_id = $this->auth->getUserID();

        $fila = new \Data\VAppointmentsModel();
        // get appointments for current week
        $user = $this->getUserData($user_id);
        $admin = $this->amIAdmin($user);
        $citas = $fila->find(
            array('dt >= :hoy and dt <= :sem and (id_user_cita is null or id_user_cita = :user or 1 = :dios) ',
                ':dios' => $admin,
                ':hoy' => date('Y-m-d'),
                ':sem' => date('Y-m-d', strtotime('+7 days')),
                ':user' => $user_id),
            array('order' => 'dt asc')
        );

        echo json_encode($this->findToArray($citas));
    }

    public function wip(): void
    {
        $this->hasAuthOrDie();
        $user_id = $this->auth->getUserID();
        $user = $this->getUserData($user_id);
        $admin = $this->amIAdmin($user);

        $fila = new \Data\VUserProcsModel();
        $users = $fila->findWIP($user_id, $admin);

        echo json_encode($this->findToArray($users));
    }

    public function done(): void
    {
        $this->hasAuthOrDie();
        $user_id = $this->auth->getUserID();

        $user = $this->getUserData($user_id);
        $admin = $this->amIAdmin($user);

        $fila = new \Data\VUserProcsModel();
        $users = $fila->findDone($user_id, $admin);

        echo json_encode($this->findToArray($users));
    }
}
