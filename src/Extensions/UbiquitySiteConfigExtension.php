<?php

/**
 * Add Ubiquity setup to Siteconfig
 */
class UbiquitySiteConfigExtension extends DataExtension
{
    private static $db = [
        'UbiquityEnabled' => 'Boolean',
        'UbiquityAnalyticsEnabled' => 'Boolean',
        'UbiquityAnalyticsKey' => 'Varchar(255)'
    ];

    private static $has_many = [
        'UbiquityDatabases' => 'UbiquityDatabase'
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
