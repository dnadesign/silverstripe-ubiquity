<?php

class UbiquitySignupField extends EditableCheckbox
{
    private static $singular_name = 'Ubiquity Sign up Field';

    private static $plural_name = 'Ubiquity Sign up Fields';

    protected $extraClasses = array('ubiquitysignupfield');

    private static $db = array(
        'HTMLLabel' => 'HTMLText',
    );

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function ($fields) {
            $fields->addFieldToTab(
                'Root.Main',
                HTMLEditorField::create(
                    'HTMLLabel',
                    'Label HTML'
                )->setRows(3)
                    ->setDescription('Note all HTML except links will be stripped out'),
                'RightTitle'
            );
        });

        return parent::getCMSFields();
    }

    public function getFormField()
    {
        $field = TermsAndConditionsField::create($this->Name, $this->EscapedTitle, $this->CheckedDefault, $this->HTMLLabel)
            ->setFieldHolderTemplate('UserformsCheckboxField_holder')
            ->setTemplate('TermsAndConditionsField')
            ->addExtraClass('ubiquitysignup');

        $this->doUpdateFormField($field);

        return $field;
    }
}
