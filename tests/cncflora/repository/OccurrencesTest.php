<?php

namespace cncflora\repository;

include_once 'vendor/autoload.php';

use cncflora\Utils;

class OccurrencesTest extends \PHPUnit_Framework_TestCase {

    public function setup() {
        $this->user = new \StdClass;
        $this->user->name = "Foo";
        $this->user->email = "foo@bar.com";
        Utils::setupTest();
    }

    public function testOccurrences() {
        $repo = new Occurrences();
        $this->assertEquals($repo,$repo);
    }

    public function testListOccurrences() {
        $repo = new Occurrences;
        $occs = $repo->listByName("Aphelandra longiflora");
        $this->assertNotEmpty($occs);
        foreach($occs as $occ) {
            $this->assertEquals("Aphelandra longiflora",$occ->scientificName);
        }
    }

    public function testCommentOccurrence() {
        $repo = new Occurrences($this->user);
        $occs = $repo->listByName("Aphelandra longiflora");
        $occ = $occs[0];

        $repo->comment($occ,"It is ok.");
        $occs = $repo->listByName("Aphelandra longiflora");
        $occ = $occs[0];
        $occ->analysisRemarks = 'It is ok.';
        $occ->analysisBy = "Foo";
    }

    public function testValidateOccurrence() {
        $repo = new Occurrences($this->user);
        $occs = $repo->listByName("Aphelandra longiflora");
        $occ = $occs[0];

        $repo->validate($occ,'valid',"It is nok.");
        $occs = $repo->listByName("Aphelandra longiflora");
        $occ = $occs[0];
        $occ->validationStatus = 'valid';
        $occ->validationRemarks = 'It is nok.';
        $occ->validationBy = "Foo";
    }

}
    
