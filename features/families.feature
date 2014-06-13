Feature: Listing families and species 

    Scenario: Open families listing
        Given I am on "/families"
        Then I should see "ACANTHACEAE"
        Then I should see "BROMELIACEAE"

    Scenario: Open a family 
        Given I am on "/family/ACANTHACEAE"
        Then I save the page "families.html"
        Then I should see "Aphelandra longiflora"
        Then I should see "Aphelandra espirito-santensis"
        Then I should not see "Aphelandra longiflora2"

