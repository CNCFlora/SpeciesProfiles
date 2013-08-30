<?php

namespace cncflora\controller;

use cncflora\Utils;

class OccurrencesTest extends \PHPUnit_Framework_TestCase {

    public function setup() {
        Utils::setupTest();
        $this->user = new \StdClass;
        $this->user->name = "foo";
        $this->user->email = "foo@bar.com";
    }

    public function testOccurrences() {
        $rest = new \Rest\Server;
        $rest->getRequesT()->setURI("");
        $rest->setParameter("logged",true);
        $rest->setParameter("user",$this->user);
        $rest->setMatch("");

        $control = new Occurrences;
        $r = $control->execute($rest);

        $this->assertEquals($r,$rest);
    }

    public function testComment() {
        $this->markTestIncomplete();
    }

    public function testValidation() {
        $this->markTestIncomplete();
    }
}
