<?php

/**
 * A generic Signup Field that allows the checkbox label to be long text that includes Links
 */
class EditableSignupField extends EditableCheckbox
{
    private static $singular_name = 'Sign up Field';

    private static $plural_name = 'Sign up Fields';

    protected $extraClasses = [
        'checkbox',
        'signupfield'
    ];

    private static $db = [
        'HTMLLabel' => 'HTMLText'
    ];

    protected $htmlLabel;

    /**
     * @param
     */
    public function __construct($name = "", $title = "", $checked = null, $htmlLabel = null)
    {
        parent::__construct($name, $title, $checked);
        $this->htmlLabel = $htmlLabel;
    }

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function ($fields) {
            $fields->addFieldToTab(
                'Root.Main',
                HtmlEditorField::create('HTMLLabel', 'Label HTML')
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
        $field = EditableSignupField::create($this->Name, $this->EscapedTitle, $this->CheckedDefault, $this->HTMLLabel)
            ->setFieldHolderTemplate('EditableSignupField_holder')
            ->setTemplate('EditableSignupField')
            ->addExtraClass('ubiquitysignup');

        $this->doUpdateFormField($field);

        return $field;
    }


    /**
     * Get the label for the field, strip HTML but keep links
     *
     * @return DBField
     */
    public function getHTMLLabel()
    {
        return DBField::create_field('HTMLText', strip_tags($this->htmlLabel, '<a>'))->forTemplate();
    }
}
