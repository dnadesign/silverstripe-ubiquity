<?php

namespace SilverStripe\UserForms\Model;

use SilverStripe\Forms\HiddenField;
use SilverStripe\UserForms\Model\EditableFormField;

class EditableHiddenField extends EditableFormField
{
    private static $table_name = 'EditableHiddenField';

    private static $singular_name = 'Hidden Field';

    private static $plural_name = 'Hidden Fields';

    private static $defaults = [
        'ShowInSummary' => false,
    ];

    public function getCMSFields()
    {
        $fields =  parent::getCMSFields();

        $fields->removeByName([
            'Title',
            'Required',
            'CustomErrorMessage',
            'ExtraClass',
            'RightTitle',
            'ShowOnLoad',
            'ShowInSummary',
            'Placeholder',
            'Validation',
            'DisplayRules',
            'UbiquityValue'
        ]);

        return $fields;
    }

    public function getFormField()
    {
        $field = HiddenField::create($this->Name, $this->Title, $this->Default);

        $this->doUpdateFormField($field);

        return $field;
    }
}
