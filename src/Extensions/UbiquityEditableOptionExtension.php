<?php

namespace Ubiquity\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

/**
 * Extension for EditableOption
 * Allows sending a different value to Ubiquity that the value of the option selected
 */
class UbiquityEditableOptionExtension extends DataExtension
{
    private static $db = [
        'UbiquityFieldID' => 'Varchar',
        'UbiquityValue' => 'Varchar'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $database = $this->owner->Parent()->UbiquityDatabase;

        if (isset($database)) {
            $fields->addFieldsToTab('Root.Main', [
                TextField::create('UbiquityFieldID', 'Ubiquity Field ID'),
                TextField::create('UbiquityValue', 'Value override to submit to Ubiquity Form')
            ]);
        }

        return $fields;
    }
}
