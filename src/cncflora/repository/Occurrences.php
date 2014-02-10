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
        $query  = $this->db->prepare('select * from occurrences where ("georeferenceVerificationStatus" = \'1\' or "georeferenceVerificationStatus" is null) and "scientificName" in (select "scientificName" from taxon where "acceptedNameUsage" = ? )');
        $query->execute(array($name));
        while($occ = $query->fetchObject()) {
            $m = new \Mustache_Engine();

            $status = $occ->validationStatus;
            $occ->valid = ($status != 'duplicate' && $status != 'uncertain taxonomy' && $status != 'wrong taxonomy' && $status != 'cultivated');
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

    public function eoo($name) {
        $query = $this->db->prepare('select count(distinct(coordinates)) from occurrences where '
                                    .' "scientificName" in (select "scientificName" from taxon where "acceptedNameUsage" = ? ) '
                                    .' and coordinates is not null and "georeferenceVerificationStatus" = \'1\' '
                                    .' and ("validationStatus" not in (\'duplicate\',\'uncertain taxonomy\',\'wrong taxonomy\',\'cultivated\') '
                                        .' or "validationStatus" is null)');
        $query->execute(array($name));
        $count = $query->fetchColumn(0);
        $eoo = 0 ;
        if($count <= 2) {
            $q = $this->db->prepare('select ST_Area(ST_Union(ST_Buffer_Meters(ST_SetSrid(coordinates,4326),10000))) * 10000 '
                .' as eoo from occurrences where '
                .' "scientificName" in (select "scientificName" from taxon where "acceptedNameUsage" = ? )'
                .' and coordinates is not null and "georeferenceVerificationStatus" = \'1\' '
                .' and ("validationStatus" not in (\'duplicate\',\'uncertain taxonomy\',\'wrong taxonomy\',\'cultivated\') '
                    .' or "validationStatus" is null)');
            $q->execute(array($name));
            $eoo = $q->fetchColumn(0);
        } else {
            $q = $this->db->prepare('select ST_Area(ST_ConvexHull(ST_Collect(ST_SetSrid(coordinates,4326)))) * 10000 '
                .' as eoo from occurrences where '
                .' "scientificName" in (select "scientificName" from taxon where "acceptedNameUsage" = ? )'
                .' and coordinates is not null and "georeferenceVerificationStatus" = \'1\' '
                .' and ("validationStatus" not in (\'duplicate\',\'uncertain taxonomy\',\'wrong taxonomy\',\'cultivated\') '
                    .' or "validationStatus" is null)');
            $q->execute(array($name));
            $eoo = $q->fetchColumn(0);
        }
        $eoo = number_format($eoo,2,'.','.');
        return $eoo;
    }

    public function eooPolygon($name) {
        $query = $this->db->prepare('select count(distinct(coordinates)) from occurrences where '
                                    .' "scientificName" in (select "scientificName" from taxon where "acceptedNameUsage" = ? ) '
                                    .' and coordinates is not null and "georeferenceVerificationStatus" = \'1\' '
                                    .' and ("validationStatus" not in (\'duplicate\',\'uncertain taxonomy\',\'wrong taxonomy\',\'cultivated\') '
                                        .' or "validationStatus" is null)');
        $query->execute(array($name));
        $count = $query->fetchColumn(0);
        $eoo = '()' ;
        if($count <= 2) {
            $q = $this->db->prepare('select ST_AsText(ST_Union(ST_Buffer_Meters(ST_SetSrid(coordinates,4326),10000))) '
                .' as eoo from occurrences where '
                .' "scientificName" in (select "scientificName" from taxon where "acceptedNameUsage" = ? )'
                .' and coordinates is not null and "georeferenceVerificationStatus" = \'1\' '
                .' and ("validationStatus" not in (\'duplicate\',\'uncertain taxonomy\',\'wrong taxonomy\',\'cultivated\') '
                    .' or "validationStatus" is null)');
            $q->execute(array($name));
            $eoo = $q->fetchColumn(0);
        } else {
            $q = $this->db->prepare('select ST_AsText(ST_ConvexHull(ST_Collect(ST_SetSrid(coordinates,4326)))) '
                .' as eoo from occurrences where '
                .' "scientificName" in (select "scientificName" from taxon where "acceptedNameUsage" = ? )'
                .' and coordinates is not null and "georeferenceVerificationStatus" = \'1\' '
                .' and ("validationStatus" not in (\'duplicate\',\'uncertain taxonomy\',\'wrong taxonomy\',\'cultivated\') '
                    .' or "validationStatus" is null)');
            $q->execute(array($name));
            $eoo = $q->fetchColumn(0);
        }
        return $eoo;
    }

    public function aoo($name) {
        $aoo = 0;
        $q = 'select count(cells)*4 as aoo from '
             .'(select distinct((st_dump(cells)).geom) as cells from grid_20km 
                    where intersects(the_geom, 
                                        (select st_collect(coordinates) from occurrences where 
                                            "scientificName" in (select "scientificName" from taxon where "acceptedNameUsage" = ?)
                                             and coordinates is not null and "georeferenceVerificationStatus" = \'1\' 
                                             and ("validationStatus" not in (\'duplicate\',\'uncertain taxonomy\',\'wrong taxonomy\',\'cultivated\') 
                                             or "validationStatus" is null)
                                        )
                                    )
                ) as grid where intersects(cells, (select st_collect(coordinates) from occurrences where "scientificName" in (select "scientificName" from taxon where "acceptedNameUsage" = ?)));';
        $query = $this->db->prepare($q);
        $query->execute(array($name,$name));
        $aoo = $query->fetchColumn(0);
        $aoo = number_format($eoo,2,'.','.');
        return $aoo;
    }

}

