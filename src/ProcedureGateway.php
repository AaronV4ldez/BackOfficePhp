<?php

class ProcedureGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getAllProcedures()
    {
        $sql = "SELECT * from procedures";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $res;
    }

    public function getProcedureById(int $proc_id)
    {
        $sql = "SELECT * from procedures where id = :proc_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":proc_id", $proc_id, PDO::PARAM_INT);
        $stmt->execute();
       
        return $stmt->fetch(PDO::FETCH_ASSOC);;
    }

}
