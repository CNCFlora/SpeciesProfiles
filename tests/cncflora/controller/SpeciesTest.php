<?php

namespace cncflora\controller;

class SpeciesTest extends \PHPUnit_Framework_TestCase {


    public function testSpecies() {
        $control = new Species;
        $this->assertNotNull($control);
    }

    public function testFamilyListing() {
        $rest = new \Rest\Server;
        $controller = new Species;
        $view = $controller->execute($rest);

        $this->assertNotEmpty($view->props);
        $this->assertContainsOnly('string',$view->props['families']);
    }

    public function testSpeciesListing() {
        $rest = new \Rest\Server;
        $rest->setMatch(array(":family"));
        $rest->getRequest()->setURI("ACANTHACEAE");

        $controller = new Species;
        $view = $controller->family($rest);
        $this->assertNotEmpty($view->props);
        $this->assertNotEmpty($view->props['species']);
        $this->assertEquals($view->props['family'],"ACANTHACEAE");
        foreach($view->props['species'] as $spp) {
            $this->assertEquals($spp->family,"ACANTHACEAE");
        }
    }

    public function testSpeciesListingMarkExistingProfiles() {
        $this->markTestIncomplete();
    }

    public function testSpecieView() {
        $this->markTestIncomplete();
    }

}
