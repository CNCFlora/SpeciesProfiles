<?php

include __DIR__.'/../../vendor/autoload.php';

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;

use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use Behat\MinkExtension\Context\MinkContext;


class FeatureContext extends MinkContext {

    /**
     * @When /^I click on "([^"]*)"$/
     */
    public function iClickOn($selector) {
        $this->getMainContext()->getSession()->getPage()->find('css',$selector)->click();
    }

    /**
     * @Then /^I login as "([^"]*)", "([^"]*)", "([^"]*)"$/
     */
    public function iLoginAs($name,$email,$roles) {
        $doc = ['name'=>$name,'email'=>$email];
        $roles = explode(",",$roles);
        foreach($roles as $role) {
            $doc['roles'][] = ['role'=>$role,'entities'=>[]];
        }
        $this->getMainContext()->getSession()->executeScript('$.post("/login",JSON.stringify('.json_encode($doc).'),function(){location.reload()})');
        $this->getSession()->wait(10000);
    }

    /**
     * @Then /^I login as "([^"]*)", "([^"]*)", "([^"]*)", "([^"]*)"$/
     */
    public function iLoginAs2($name,$email,$roles,$ents) {
        $doc = ['name'=>$name,'email'=>$email];
        $roles = explode(",",$roles);
        $ents  = explode(",",$ents);
        foreach($roles as $role) {
            $doc['roles'][] = ['role'=>$role,'entities'=>$ents];
        }
        $this->getMainContext()->getSession()->executeScript('$.post("/login",JSON.stringify('.json_encode($doc).'),function(){location.reload()})');
        $this->getSession()->wait(10000);
    }


    /**
     * @Then /^I logout$/
     */
    public function iLogout() {
        $this->getMainContext()->getSession()->executeScript('$.post("/logout","",function(){location.reload()})');
        $this->getSession()->wait(10000);
    }

    /**
     * @Given /^I save the page "([^"]*)"$/
     */
    public function iSaveThePage($name) {
        file_put_contents($name,$this->getMainContext()->getSession()->getPage()->getHtml());
    }
}

