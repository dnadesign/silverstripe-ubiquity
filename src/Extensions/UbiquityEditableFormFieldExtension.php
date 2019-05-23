<?php

/**
 * Extension to add additional fields to EditableFormField
 * to enable a field to submit to an ubiquity form field.
 * UbiquityFieldID: the ID of the coreesponding field in the Ubiquity form
 * UbiquityValue: If set, the will override the value of the field to send to Ubiquity
 * AllowOverride: if checked, the eixting value in the Ubiquity database for this field
 * will be overriden with the new value submitted
 */
class UbiquityEditableFormFieldExtension extends DataExtension
{
    private static $db = [
        'UbiquityFieldID' => 'Varchar',
        'UbiquityValue' => 'Varchar',
        'AllowOverride' => 'Boolean',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $database = $this->owner->Parent()->UbiquityDatabase();

        if ($database && $database->exists()) {
            $fields->addFieldToTab('Root.Main', TextField::create('UbiquityFieldID', 'Ubiquity Field ID'));

            $value = TextField::create('UbiquityValue', 'Value override to submit to Ubiquity Form');
            $value->setRightTitle('Use [submission_date] to send the date on which the form has been submitted.');
            $fields->addFieldToTab('Root.Main', $value);

            $update = CheckboxField::create('AllowOverride', 'Allow Override Ubiquity DB ')
                ->setDescription('Allow to update the information of this field when a user already exists in DB.');
            $fields->addFieldToTab('Root.Main', $update);
        }

        return $fields;
    }
}
