<?php

namespace Ubiquity\Forms\Fields;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\UserForms\Model\EditableFormField\EditableCheckbox;
use Ubiquity\Forms\Fields\EditableSignupField;

/**
 * A generic Signup Field that allows the checkbox label to be long text that includes Links
 */
class EditableSignupField extends EditableCheckbox
{
    private static $singular_name = 'Ubiquity Sign up Field';

    private static $plural_name = 'Ubiquity Sign up Fields';

    private static $table_name = 'EditableSignupField';

    protected $extraClasses = [
        'checkbox',
        'signupfield'
    ];

    private static $db = [
        'HTMLLabel' => 'HTMLText'
    ];

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function ($fields) {
            $fields->addFieldToTab(
                'Root.Main',
                HTMLEditorField::create('HTMLLabel', 'Label HTML')
                    ->setRows(3)
                    ->setDescription('Note all HTML except links will be stripped out'),
                'RightTitle'
            );
        });

        return parent::getCMSFields();
    }

    /**
     * For the front end, return an UbiquityTermsAndConditionsField instead of a simple checkbox
     * Alows us to have a specific template and reuse the UbiquityTermsAndConditionsField elsewhere
     */
    public function getFormField()
    {
        $field = CheckboxField::create($this->Name, $this->getLabel(), $this->CheckedDefault)
            ->addExtraClass('ubiquitysignup');

        $this->doUpdateFormField($field);

        return $field;
    }

    /**
     * Get the label for the field
     *
     * @return DBField
     */
    public function getLabel()
    {
        return ($this->HTMLLabel) ? $this->getEscapedHTMLLabel() : $this->getEscapedTitle();
    }

    /**
     * Strip HTML but keep links
     *
     * @return DBField
     */
    public function getEscapedHTMLLabel()
    {
        return DBField::create_field('HTMLText', strip_tags($this->HTMLLabel, '<a>'));
    }
}
