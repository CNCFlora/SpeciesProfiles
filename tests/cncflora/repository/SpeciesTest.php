<?php

namespace cncflora\repository ;

include_once 'vendor/autoload.php';

use cncflora\Utils;

class SpeciesTest extends \PHPUnit_Framework_TestCase {

    public function testSpecies() {
        $repo = new Species;
        $this->assertEquals($repo,$repo);
        Utils::setupTest();
    }

    public function testTaxons() {
        $repo = new Species;
        $families = $repo->getFamilies();
        $this->assertNotEmpty($families,'Families from Species should not come empty');
        foreach($families as $family) {
            $this->assertRegExp("/^[A-Z]+$/",$family,"Families should come as strings and all uppercase");
        }
        $species  = $repo->getSpecies('ACANTHACEAE');
        $this->assertNotEmpty($species,"Species from a valid family should come as a non-empty array");
        foreach($species as $spp) {
            $this->assertEquals($spp->family,'ACANTHACEAE','Return only species of provided family');
        }
        $species  = $repo->getSpecies('NONEXISTENTFAMILY');
        $this->assertEmpty($species,'Species from a invalid family should come empty');
    }

    public function testPermissions() {
        $this->markTestIncomplete();
    }

}


