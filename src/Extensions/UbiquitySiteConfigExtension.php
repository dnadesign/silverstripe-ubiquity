<?php

namespace Ubiquity\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use Ubiquity\Models\UbiquityDatabase;

/**
 * Add Ubiquity setup to Site Config
 */
class UbiquitySiteConfigExtension extends DataExtension
{
    private static $db = [
        'UbiquityEnabled' => 'Boolean',
        'UbiquityAnalyticsEnabled' => 'Boolean',
        'UbiquityAnalyticsKey' => 'Varchar(255)'
    ];

    private static $has_many = [
        'UbiquityDatabases' => UbiquityDatabase::class
    ];

    private static $defaults = [
        'UbiquityEnabled' => 0,
        'UbiquityAnalyticsEnabled' => 0
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $config = GridFieldConfig_RecordEditor::create();
        $gridfield = GridField::create('UbiquityDatabases', 'Ubiquity Database', $this->owner->UbiquityDatabases(), $config);
        $analyticsKey = TextField::create('UbiquityAnalyticsKey', 'Ubiquity Analytics Key');

        $fields->addFieldsToTab('Root.UbiquitySetup', [
            CheckboxField::create('UbiquityEnabled', 'Ubiquity Enabled'),
            CheckboxField::create('UbiquityAnalyticsEnabled', 'Ubiquity Analytics Enabled'),
            $analyticsKey,
            $gridfield
        ]);

        $analyticsKey->displayIf('UbiquityAnalyticsEnabled')->isChecked();
    }
}
