Feature: Workflow

    Scenario: List families
        Given I am on "/workflow"
        Then I should not see "ACANTHACEAE"
        Then I should not see "BROMELIACEAE"
        Then I login as "Diogo", "diogo@diogok.net", "analyst", "ACANTHACEAE"
        Then I should see "ACANTHACEAE"
        Then I should not see "BROMELIACEAE"
        Then I login as "Diogo", "diogo@diogok.net", "admin"
        Then I should see "ACANTHACEAE"
        Then I should see "BROMELIACEAE"

