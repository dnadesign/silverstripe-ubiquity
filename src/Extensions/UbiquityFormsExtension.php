<?php

class UbiquityFormsExtension extends Extension
{
    private static $db = array(
        'UbiquityDatabase' => 'Varchar',
        'UbiquitySuccessFormID' => 'Varchar(255)',
        'UbiquitySuccessFormEmailTriggerID' => 'Varchar(255)',
        'UbiquitySuccessFormAction' => 'Varchar(100)',
        'UbiquitySourceFieldID' => 'Varchar(100)',
        'UbiquitySourceName' => 'Varchar(100)',
    );

    public function updateCMSFields(FieldList $fields)
    {
        if (UbiquityService::is_ubiquity_enabled()) {
            $ubiquityDB = DropdownField::create('UbiquityDatabase', 'Ubiquity Databases', $this->getDatabaseItems())
                ->setEmptyString('(Select one)');
            $fields->addFieldToTab('Root.UbiquityConfig', $ubiquityDB);

            $formID = TextField::create('UbiquitySuccessFormID', 'Ubiquity Success Form ID')->setRightTitle('ID of the form that will be used to send confirmation email once the user has signed up.');
            $formFieldID = TextField::create('UbiquitySuccessFormEmailTriggerID', 'Ubiquity Success Form Field ID')->setRightTitle('ID of the field that will be used to send the form action (ususally named EmailTrigger)');
            $formAction = TextField::create('UbiquitySuccessFormAction', 'Ubiquity Success Form Action')->setRightTitle('Name of the Email that should be sent.');

            $fields->addFieldsToTab('Root.UbiquityConfig', array($formID, $formFieldID, $formAction));

            $sourceID = TextField::create('UbiquitySourceFieldID', 'Ubiquity Source Field ID')->setRightTitle('ID of the field that will be use as primary source for the user.');
            $sourceName = TextField::create('UbiquitySourceName', 'Ubiquity Source Name')->setRightTitle('Reference of this form as source.');

            $fields->addFieldsToTab('Root.UbiquityConfig', array($sourceID, $sourceName));

            return $fields;
        }
    }

    public function getDatabaseItems()
    {
        $dbs = UbiquityService::get_available_databases();

        if (!is_array($dbs)) {
            user_error("Make sure you have an array of databases set up in for this environment in SiteConfig", E_USER_WARNING);
        }

        return $dbs;
    }
}
