<?php

namespace cncflora\controller;

class Occurrences implements \Rest\Controller {

    public function execute(\Rest\Server $rest) {
        return $rest;
    }

    public function comment($r) {
        $id = $r->getRequest()->getParameter("id");
        $user = $r->getparameter("user");
        $comment = $r->getRequest()->getPost("comment");
        $repo = new \cncflora\repository\Occurrences($user);
        $repo->comment($repo->getById($id),$comment);
        return new \Rest\Controller\Redirect('/'.BASE_PATH."profile/".$id."#occurrences");
    }

    public function validate($r) {
        $id = $r->getRequest()->getParameter("id");
        $user = $r->getparameter("user");
        $status = $r->getRequest()->getPost("status");
        $comment = $r->getRequest()->getPost("comment");
        $repo = new \cncflora\repository\Occurrences($user);
        $repo->validate($repo->getById($id),$status,$comment);
        return new \Rest\Controller\Redirect('/'.BASE_PATH."profile/".$id."#occurrences");
    }
}
