<?php

namespace Ubiquity\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use Ubiquity\Models\UbiquityDatabase;
use Ubiquity\Services\UbiquityService;

/**
 * Adds Ubiquity setup to a DataObject that contains a form.
 */
class UbiquityFormExtension extends Extension
{
    private static $db = [
        'UbiquityEnabled' => 'Boolean',
        'UbiquityFormID' => 'Varchar(100)'
    ];

    private static $has_one = [
        'UbiquityDatabase' => UbiquityDatabase::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        // Check if Ubiquity is enabled and display a message if not
        if (!UbiquityService::get_ubiquity_enabled()) {
            $fields->addFieldToTab('Root.UbiquityConfig', new LiteralField('Warning', "<p class=\"message\">Ubiquity is not enabled in site config.</p>"));
            return;
        }

        $enabled = CheckboxField::create('UbiquityEnabled', 'Enable Ubiquity');

        // Ubiquity Database to post data to
        $databaseOptions = UbiquityDatabase::get_database_options();
        $database = DropdownField::create('UbiquityDatabaseID', 'Ubiquity Database', $databaseOptions)
            ->setEmptyString('-- Select one --');

        // Ubiquity allows submitting data to a form as well, usually tied to the Ubiquity database
        $formID = TextField::create('UbiquityFormID', 'Ubiquity Form ID')
            ->setDescription('ID of the form used to send the data to.');

        $fields->addFieldsToTab('Root.Ubiquity', [
            $enabled,
            $database,
            $formID
        ]);
    }
}
