<?php

namespace cncflora\repository;

use cncflora\Utils;

class Occurrences  {
    public $user = null;
    public $db = null;

    public function __construct($user=null) {
        Utils::config();
        $this->user = $user;

        $this->db  = new \PDO("pgsql:dbname=".POSTGRESQL_DB.";host=".POSTGRESQL_HOST,POSTGRESQL_USER,POSTGRESQL_PASSWORD);
    }

    public function listByName($name) {
        $occs = array();
        $query  = $this->db->prepare('select * from occurrences where "scientificName" in (select "scientificName" from taxon where "acceptedNameUsage" = ? )');
        $query->execute(array($name));
        while($occ = $query->fetchObject()) {
            $m = new \Mustache_Engine();
            $content = $m->render(file_get_contents("../resources/templates/unit.html"),$occ);
            $occ->content = $content;

            if($occ->decimalLatitude && $occ->decimalLongitude) {
                $occ->json = json_encode(array('geometry'=>array('coordinates'=>array($occ->decimalLatitude,$occ->decimalLongitude)),'properties'=>$occ,'content'=>$occ->content));
            } else {
                $occ->json = json_encode(array('properties'=>$occ ));
            }

            $occs[] = $occ;
        }
        return $occs;
    }

    public function getById($id) {
        $query = $this->db->prepare('select * from occurrences where "occurrenceID" = ? ');
        $query->execute(array($id));
        return $query->fetchObject();
    }

    public function comment($occ,$comment) {
        $updt = $this->db->prepare('update occurrences set "analysisRemarks" = ?, "analysisBy" = ? where "occurrenceID" = ? ');
        return $updt->execute(array($comment,$this->user->name,$occ->occurrenceID));
    }

    public function validate($occ,$presence,$status,$comment) {
        $updt = $this->db->prepare('update occurrences set "occurrenceStatus"=?, "validationStatus" = ?, "validationRemarks" = ?, "validationBy" = ? where "occurrenceID" = ? ');
        $r = $updt->execute(array($presence,$status,$comment,$this->user->name,$occ->occurrenceID));
        return $r;
    }

}

