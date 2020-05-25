<?php

namespace Ubiquity\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;

/**
 * Extension for EditableMultipleOptionField
 * Allows sending a different value to Ubiquity that the value of the option selected
 */
class UbiquityMultipleOptionExtension extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $database = $this->owner->Parent()->UbiquityDatabase;
        $options = $fields->dataFieldByName('Options');

        if ($database && $database->exists() && $options) {
            $conf = $options->getConfig();
            $editableColumns = new GridFieldEditableColumns();
            $editableColumns->setDisplayFields([
                'Title' => [
                    'title' => _t('EditableMultipleOptionField.TITLE', 'Title'),
                    'callback' => function ($record, $column, $grid) {
                        return TextField::create($column);
                    }
                ],
                'Value' => [
                    'title' => _t('EditableMultipleOptionField.VALUE', 'Value'),
                    'callback' => function ($record, $column, $grid) {
                        return TextField::create($column);
                    }
                ],
                'UbiquityFieldID' => [
                    'title' => 'Ubiquity Field ID',
                    'callback' => function ($record, $column, $grid) {
                        return TextField::create($column);
                    }
                ],
                'UbiquityValue' => [
                    'title' => 'Value for Ubiquity Form (optional)',
                    'callback' => function ($record, $column, $grid) {
                        return TextField::create($column);
                    }
                ],
                'Default' => [
                    'title' => _t('EditableMultipleOptionField.DEFAULT', 'Selected by default?'),
                    'callback' => function ($record, $column, $grid) {
                        return CheckboxField::create($column);
                    }
                ],
                'AllowOverride' => [
                    'title' => 'Allow Override Ubiquity DB',
                    'callback' => function ($record, $column, $grid) {
                        return CheckboxField::create($column);
                    }
                ]
            ]);

            $conf->removeComponentsByType('GridFieldEditableColumns');
            $conf->addComponent($editableColumns, 'GridFieldDeleteAction');
        }

        return $fields;
    }
}
