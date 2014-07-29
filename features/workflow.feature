Feature: Workflow view

    Scenario: List families
        Given I am on "/workflow"
        And I logout
        Then I should not see "ACANTHACEAE"
        Then I should not see "BROMELIACEAE"
        Then I login as "Diogo", "diogo@diogok.net", "analyst", "ACANTHACEAE"
        Then I should see "ACANTHACEAE"
        Then I should not see "BROMELIACEAE"
        Then I login as "Diogo", "diogo@diogok.net", "admin"
        Then I should see "ACANTHACEAE"
        Then I should see "BROMELIACEAE"

    Scenario: List species
        Given I am on "/workflow"
        Then I login as "Diogo", "diogo@diogok.net", "analyst", "ACANTHACEAE"
        And I follow "ACANTHACEAE"
        Then I should see "ACANTHACEAE"
        Then I wait 5000
        Then I should see "Aphelandra longiflora"
        Then I should see "Aphelandra espirito-santensis"
        Then I should not see "Aphelandra longiflora2"
        Then I follow "Fechados"
        Then I wait 1000
        Then I should see "Vazio"
        Then I should not see "Aphelandra longiflora"

    Scenario: Open specie, update view
        Given I am on "/workflow"
        Then I login as "Diogo", "diogo@diogok.net", "analyst", "ACANTHACEAE"
        And I follow "ACANTHACEAE"
        And I wait 4000
        And I follow "Aphelandra longiflora"
        Then I press "create-btn"
        And I wait 1000
        Then I go to "/workflow"
        And I follow "ACANTHACEAE"
        Then I should see "Aphelandra espirito-santensis"
        Then I should not see "Aphelandra longiflora"
        Then I follow "Análise"
        Then I wait 1000
        Then I should not see "Aphelandra espirito-santensis"
        Then I should see "Aphelandra longiflora"

