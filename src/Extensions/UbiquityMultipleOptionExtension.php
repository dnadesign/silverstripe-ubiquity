<?php

class UbiquityMultipleOptionExtension extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $options = $fields->fieldByName('Root.Options.Options');
        $database = $this->owner->Parent()->UbiquityDatabase;

        if (isset($database) && $options) {
            $conf = $options->getConfig();

            $editableColumns = new GridFieldEditableColumns();
            $editableColumns->setDisplayFields(array(
                'Title' => array(
                    'title' => _t('EditableMultipleOptionField.TITLE', 'Title'),
                    'callback' => function ($record, $column, $grid) {
                        return TextField::create($column);
                    },
                ),
                'Value' => array(
                    'title' => _t('EditableMultipleOptionField.VALUE', 'Value'),
                    'callback' => function ($record, $column, $grid) {
                        return TextField::create($column);
                    },
                ),
                'UbiquityFieldID' => array(
                    'title' => 'Ubiquity Field ID',
                    'callback' => function ($record, $column, $grid) {
                        return TextField::create($column);
                    },
                ),
                'UbiquityValue' => array(
                    'title' => 'Value for Ubiquity Form (optional)',
                    'callback' => function ($record, $column, $grid) {
                        return TextField::create($column);
                    },
                ),
                'Default' => array(
                    'title' => _t('EditableMultipleOptionField.DEFAULT', 'Selected by default?'),
                    'callback' => function ($record, $column, $grid) {
                        return CheckboxField::create($column);
                    },
                ),
                'AllowOverride' => array(
                    'title' => 'Allow Override Ubiquity DB',
                    'callback' => function ($record, $column, $grid) {
                        return CheckboxField::create($column);
                    },
                ),
            ));

            $conf->removeComponentsByType('GridFieldEditableColumns');
            $conf->addComponent($editableColumns, 'GridFieldDeleteAction');
        }

        return $fields;
    }
}
