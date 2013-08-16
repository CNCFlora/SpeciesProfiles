<?php
namespace cncflora\controller;
use cncflora\Utils;

class  ValidationTest extends \PHPUnit_Framework_TestCase {

    public function setup() {
        Utils::setupTest();
        $this->user = new \StdClass;
        $this->user->name = "foo";
        $this->user->email = "foo@bar.com";
        $this->repo = new \cncflora\repository\Profiles($this->user);

        $taxons = (new \cncflora\repository\Species)->getSpecies('ACANTHACEAE');
        $this->taxon0  = $taxons[0];
        $this->taxon1  = $taxons[1];
        $this->profile = $this->repo->create($this->taxon0);
    }

    public function tearDown() {
        $this->repo->delete($this->repo->get($this->profile->_id));
    }

    public function testValidate() {
        $_POST = array("field"=>'ecology',"comment"=>"Foobar");
        $rest = new \Rest\Server;
        $rest->getRequest()->setURI($this->profile->_id);
        $rest->setMatch(array(":id"));
        $rest->setParameter("user",$this->user);

        $control = new Validation;
        $view = $control->validate($rest);

        $profile = (new \cncflora\repository\Profiles)->get($this->profile->_id);

        $this->assertCount(1,$profile->validations);
        $this->assertEquals("Foobar",$profile->validations[0]->comment);
        $this->assertEquals("ecology",$profile->validations[0]->field);
        $this->assertEquals("foo",$profile->validations[0]->metadata->creator);
        $this->assertEquals("foo@bar.com",$profile->validations[0]->metadata->contact);
    }

    public function testValidationForm() {
        $rest = new \Rest\Server;
        $rest->getRequest()->setURI($this->profile->_id);
        $rest->setMatch(array(":id"));
        $rest->setParameter("user",$this->user);

        $control = new Validation;
        $view = $control->validateForm($rest);

        $this->assertNotNull($view->props['schema']);
        $this->assertNotNull($view->props['profile']);
        $this->assertNotNull($view->props['metadata']);
        $this->assertNotNull($view->props['taxon']);
        $this->assertNotNull($view->props['validations']);
    }
}

