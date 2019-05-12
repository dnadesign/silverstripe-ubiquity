<?php

namespace DNADesign\Ubiquity\Extensions;

use TextField;
use FieldList;
use DataExtension;
use CheckboxField;

/**
 * Extension for EditableOption
 * Allows sending a different value to Ubiquity that the value of the option selected
 */
class UbiquityEditableOptionExtension extends DataExtension
{
    private static $db = [
        'UbiquityFieldID' => 'Varchar',
        'UbiquityValue' => 'Varchar',
        'AllowOverride' => 'Boolean'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $database = $this->owner->Parent()->UbiquityDatabase;

        if (isset($database)) {
            $fields->addFieldToTab('Root.Main', TextField::create('UbiquityFieldID', 'Ubiquity Field ID'));
            $fields->addFieldToTab('Root.Main', TextField::create('UbiquityValue', 'Value override to submit to Ubiquity Form'));

            $update = CheckboxField::create('AllowOverride', 'Allow Override Ubiquity DB ')
                ->setDescription('Allow to update the information of this field when a user already exists in DB.');
            $fields->addFieldToTab('Root.Main', $update);
        }

        return $fields;
    }
}
