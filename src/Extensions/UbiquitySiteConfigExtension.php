<?php

class UbiquitySiteConfigExtension extends DataExtension
{
    private static $db = array(
        'UbiquityEnabledSubsites' => 'Varchar(100)',
        'EnableUbiquityAnalytics' => 'Boolean(1)',
        'DebugUbiquityAnalytics'  => 'Boolean',
    );

    private static $has_many = array(
        'UbiquityDatabases' => 'UbiquityDatabase'
    );

    public function updateCMSFields(FieldList $fields)
    {
        if (UbiquityService::is_ubiquity_enabled()) {
            $config = GridFieldConfig_RecordEditor::create();
            $gridfield = GridField::create('UbiquityDatabases', 'Ubiquity Database', $this->owner->UbiquityDatabases(), $config);

            // get name of current subsite for providing clarity in heading field
            $currentSubsiteName = 'Main site';
            if (class_exists('Subsite') && $currentSubsite = Subsite::currentSubsite()) {
                $currentSubsiteName = $currentSubsite->Title;
            }

            $fields->addFieldsToTab('Root.Ubiquity', [
                HeaderField::create('Ubiquity databases for ' . $currentSubsiteName, 'Ubiquity databases for ' . $currentSubsiteName),
                $gridfield,
            ]);

            $fields->addFieldToTab('Root.Ubiquity', 
                CheckboxField::create('EnableUbiquityAnalytics', 'Enable Ubiquity Analytics')
            );

            $fields->addFieldToTab('Root.Ubiquity', 
                CheckboxField::create('DebugUbiquityAnalytics', 'Allow Ubiquity Analytics Data to be sent to from Staging.')
            );
        }

        if (class_exists('Subsite')) {
            $allSubsites = Subsite::all_sites(false)->map();
            if (!Subsite::currentSubsiteID()) {
                // enable subsite checkboxes on the main site only
                $fields->addFieldsToTab('Root.Ubiquity', [
                    HeaderField::create('Ubiquity enabled subsites', 'Ubiquity enabled subsites'),
                    CheckboxSetField::create('UbiquityEnabledSubsites', 'UbiquityEnabledSubsites', $allSubsites),
                ]);
            }
        }
    }
}
