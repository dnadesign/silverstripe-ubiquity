<?php

class UbiquityEditableOptionExtension extends DataExtension
{
    private static $db = array(
        'UbiquityFieldID' => 'Varchar',
        'UbiquityValue' => 'Varchar',
        'AllowOverride' => 'Boolean',
    );

    public function updateCMSFields(FieldList $fields)
    {
        $database = $this->owner->Parent()->UbiquityDatabase;

        if (isset($database)) {
            $fields->addFieldToTab('Root.Main', TextField::create('UbiquityFieldID', 'Ubiquity Field ID'));
            $fields->addFieldToTab('Root.Main', TextField::create('UbiquityValue', 'Value override to submit to Ubiquity Form'));

            $update = CheckboxField::create('AllowOverride', 'Allow Override Ubiquity DB ')->setRightTitle('Allow to update the information of this field when a user already exists in DB.');
            $fields->addFieldToTab('Root.Main', $update);
        }

        return $fields;
    }
}
