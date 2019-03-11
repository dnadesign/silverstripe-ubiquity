<?php

class UbiquityDatabase extends DataObject
{
    /**
     * @var string
     */
    private static $db = [
        'Environment' => 'Varchar(255)',
        'Title' => 'Varchar(255)',
        'APIKey' => 'Varchar(255)',
    ];

    private static $has_one = [
        'SiteConfig' => 'SiteConfig',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('SiteConfigID');

        $env = (singleton('Director')->isLive()) ? 'production' : 'staging';

        $fields->addFieldsToTab('Root.Main', [
            ReadonlyField::create('Environment', 'Environment', $env),
            TextField::create('Title', 'Customer name'),
            TextField::create('APIKey', 'Customer API key'),
        ]);

        return $fields;
    }

    public function onBeforeWrite()
    {
        $env = (singleton('Director')->isLive()) ? 'production' : 'staging';
        $this->Environment = $env;

        parent::onBeforeWrite();
    }
}
