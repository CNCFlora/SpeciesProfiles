<?php

namespace cncflora\controller; 

use cncflora\View;

class Search implements \Rest\View {

    public function execute(\Rest\Server $r) {
        $result = array();
        $query  = null;
        if(isset($_GET["query"])) {
            $query = $r->GetRequest()->getGet("query");
            $r  = json_decode(file_get_contents(ES."/profile/_search?q=".rawurlencode($query)))->hits->hits;
            foreach($r as $rr) {
                $result[] = $rr->_source;
            }
        }
        return new View("index.html",array("result"=>$result,"query"=>$query));
    }

    public function biblio($r) {
        $term=$r->getRequest()->getGet("term");
        $r  = json_decode(file_get_contents(ES."/biblio/_search?q=".rawurlencode($term)))->hits->hits;
        $res = array();
        foreach($r as $row) {
            $res[] = array("label"=>$row->_source->fullCitation,'value'=>$row->_source->_id);
        }
        return new \Rest\View\JSon($res);
    }
}
