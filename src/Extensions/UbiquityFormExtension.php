<?php

/**
 * Adds Ubiquity setup to a DataObject that contains a form.
 */
class UbiquityFormExtension extends \Extension
{
    private static $db = [
        'UbiquityEnabled' => 'Boolean',
        'UbiquitySuccessFormID' => 'Varchar(100)',
        'UbiquitySuccessFormEmailTriggerID' => 'Varchar(100)',
        'UbiquitySuccessFormAction' => 'Varchar(100)',
        'UbiquitySourceFieldID' => 'Varchar(100)',
        'UbiquitySourceName' => 'Varchar(100)',
        'UbiquitySubmitSource' => 'Boolean',
    ];

    private static $has_one = [
        'UbiquityDatabase' => 'UbiquityDatabase'
    ];

    public function updateCMSFields(\FieldList $fields)
    {
        // Check if Ubiquity is enabled and display a message if not
        if (!UbiquityService::get_ubiquity_enabled()) {
            $fields->addFieldToTab('Root.UbiquityConfig', new LiteralField('Warning', "<p class=\"message\">Ubiquity is not enabled in siteconfig.</p>"));
            return;
        }

        // Check if Ubiquity databases exist for the current development
        // environment - staging (dev/test) or production (live)
        $databaseOptions = UbiquityDatabase::get_database_options();
        if (empty($databaseOptions)) {
            $fields->addFieldToTab('Root.UbiquityConfig', new LiteralField('Warning', sprintf(
                "<p class=\"message\">No Ubiquity databases are set up for this environment (%s) in SiteConfig</p>",
                Director::get_environment_type()
            )));
            return;
        }

        $enabled = CheckboxField::create('UbiquityEnabled', 'Ubiquity Enabled');

        // Ubiquity Database to post data to (from a list of databases for the current
        // development enviornment - staging (dev/test) or production (live)
        $database = DropdownField::create('UbiquityDatabaseID', 'Ubiquity Database', $databaseOptions)
            ->setEmptyString('-- Select one --');
        
        // Ubiquity allows submitting data to a form as well, usually tied to the Ubiquity database
        // By populating the Sucess Form ID, you can automatically trigger an email response to
        // be sent to the user, as if the Form was submitted directly.
        $formID = TextField::create('UbiquitySuccessFormID', 'Ubiquity Success Form ID')
            ->setDescription('ID of the form that will be used to send confirmation email once the user has signed up.');
        
        // To trigger emails of the form, you must define which field the form needs to have the email sent.
        $formFieldID = TextField::create('UbiquitySuccessFormEmailTriggerID', 'Ubiquity Success Form Field ID')
            ->setDescription('ID of the field that will be used to send the form action (ususally named EmailTrigger)');
        
        // Ubiquity forms can send different emails, define the Action that will send the email
        $formAction = TextField::create('UbiquitySuccessFormAction', 'Ubiquity Success Form Action')
            ->setDescription('Name of the Email that should be sent.');
        
        // Allows Ubiquity to track the soure of the submission, eg newsletter signup, event form, feedback form
        // Define the FieldID of the Ubiquity Field
        $sourceID = TextField::create('UbiquitySourceFieldID', 'Ubiquity Source Field ID')
            ->setDescription('ID of the field that will be use as primary source for the user.');
        
        // Define the Name of the source to populate the above field
        $sourceName = TextField::create('UbiquitySourceName', 'Ubiquity Source Name')
            ->setDescription('Reference of this form as source.');

        // Submit the source even if  a T&C's field is included yet is not checked
        $submitSource = CheckboxField::create('UbiquitySubmitSource', 'Submit Source')
            ->setDescription('Submit the source data even if a T&C\'s field exists but is not ticked');

        $fields->addFieldsToTab('Root.Ubiquity', [
            $enabled,
            $database,
            $formID,
            $formFieldID,
            $formAction,
            $sourceID,
            $sourceName,
            $submitSource
        ]);
    }
}
