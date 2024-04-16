<?php

namespace Ubiquity\Forms\Fields;

use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\UserForms\Model\EditableFormField\EditableCheckbox;

/**
 * A generic Signup Field that allows the checkbox label to be long text that includes Links
 */
class EditableSignupField extends EditableCheckbox
{
    private static $table_name = 'EditableSignupField';

    private static $singular_name = 'Sign up Field';

    private static $plural_name = 'Sign up Fields';

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
     * Allows us to have a specific template and reuse the UbiquityTermsAndConditionsField elsewhere
     */
    public function getFormField()
    {
        $field = parent::getFormField()
            ->setTitle($this->getTitleForTemplate())
            ->addExtraClass('ubiquitysignup');

        $this->doUpdateFormField($field);

        return $field;
    }


    /**
     * Get the label for the field, strip HTML but keep links
     *
     * @return DBField
     */
    public function getHTMLLabelForTemplate()
    {
        return DBField::create_field(DBHTMLText::class, strip_tags($this->HTMLLabel, '<a>'));
    }

    /**
     * Returns the field label - used by templates.
     *
     * @return string
     */
    public function getTitleForTemplate()
    {
        return ($this->HTMLLabel) ? $this->getHTMLLabelForTemplate() : $this->Title;
    }
}
