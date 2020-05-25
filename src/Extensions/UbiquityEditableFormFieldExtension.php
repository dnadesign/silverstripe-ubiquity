<?php

namespace Ubiquity\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

/**
 * Extension to add additional fields to EditableFormField
 * to enable a field to submit to an ubiquity form field.
 * 
 * UbiquityFieldID: the ID of the corresponding field in the Ubiquity form
 * 
 * UbiquityValue: If set, the will override the value of the field to send to Ubiquity
 * 
 * AllowOverride: if checked, the existing value in the Ubiquity database for this field
 * will be overwritten with the new value submitted
 */
class UbiquityEditableFormFieldExtension extends DataExtension
{
    private static $db = [
        'UbiquityFieldID' => 'Varchar',
        'UbiquityValue' => 'Varchar'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $database = $this->owner->Parent()->UbiquityDatabase();

        if ($database && $database->exists()) {
            $value = TextField::create('UbiquityValue', 'Value override to submit to Ubiquity Form');
            $value->setRightTitle('Use [submission_date] to send the date on which the form has been submitted.');

            $fields->addFieldsToTab('Root.Main', [
                TextField::create('UbiquityFieldID', 'Ubiquity Field ID'),
                $value
            ]);
        }

        return $fields;
    }
}
