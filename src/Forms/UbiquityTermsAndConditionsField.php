<?php

class UbiquityTermsAndConditionsField extends CheckboxField
{
    protected $htmlLabel;

    protected $extraClasses = array('checkbox');

    public function __construct($name = "", $title = "", $checked = null, $htmlLabel = null)
    {
        parent::__construct($name, $title, $checked);
        $this->htmlLabel = $htmlLabel;
    }

    public function getHTMLLabel()
    {
        return DBField::create_field('HTMLText', strip_tags($this->htmlLabel, '<a>'))->forTemplate();
    }
}
