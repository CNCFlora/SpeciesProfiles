<?php

namespace cncflora\controller; 

use cncflora\View;

class Search implements \Rest\View {

    public function execute(\Rest\Server $rest) {
        $result = array();
        $query  = null;
        if(isset($_GET["query"])) {
            $query = $r->GetRequest()->getGet("query");
            $q = array(
                "size"=>25,
                "from"=>0,
                "facets"=>new StdClass,
                "query"=> array(
                    "constant_score"=> array(
                        "query"=> array(
                            "query_string"=> array(
                                "query"=> $query
                            )
                        )
                    )
                )
            );
            $r  = json_decode(file_get_contents(ES."/profile/_search?source=".rawurlencode(json_encode($q))))->hits->hits;
            foreach($r as $rr) {
                $result[] = $rr->_source;
            }
        }
        return new View("index.html",array("result"=>$result,"query"=>$query));
    }

    public function biblio($r) {
        $term=$r->getRequest()->getGet("term");
        $q = array(
            "size"=>25,
            "from"=>0,
            "facets"=>new \StdClass,
            "query"=> array(
                "constant_score"=> array(
                    "query"=> array(
                        "query_string"=> array(
                            "query"=> $term
                        )
                    )
                )
            )
        );
        $r  = json_decode(file_get_contents(ES."/biblio/_search?source=".rawurlencode(json_encode($q))))->hits->hits;
        $res = array();
        foreach($r as $row) {
            $res[] = array("label"=>$row->_source->fullCitation,'value'=>$row->_source->_id);
        }
        return new \Rest\View\JSon($res);
    }
}
