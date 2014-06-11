<?php

namespace cncflora\controller; 

use cncflora\View;

class Search implements \Rest\View {

    public function execute(\Rest\Server $r) {
        $result = array();
        $query  = null;
        if(isset($_GET["query"])) {
            $query = $r->GetRequest()->getGet("query");
            $result = \cncflora\Utils::search("profile",$query);
        }
        return new View("index.html",array("result"=>$result,"query"=>$query));
    }

    public function biblio($r) {
        $term=$r->getRequest()->getGet("term");
        $r = \cncflora\Utils::search("biblio",$term);
        $res = array();
        foreach($r as $row) {
            $res[] = array("label"=>$row->fullCitation,'value'=>$row->_id);
        }
        return new \Rest\View\JSon($res);
    }
}
