<?php

/**
 * Ubiquity Database model
 */
class UbiquityDatabase extends DataObject
{
    private static $db = [
        'Environment' => 'Varchar(255)',
        'Title' => 'Varchar(255)',
        'APIKey' => 'Varchar(255)',
    ];

    private static $has_one = [
        'SiteConfig' => 'SiteConfig',
    ];

    private static $summary_fields = [
        'Title' => 'Name',
        'Environment' => 'Environment'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('SiteConfigID');

        $fields->addFieldsToTab('Root.Main', [
            DropdownField::create('Environment', 'Environment', ['dev' => 'dev', 'test' => 'test', 'live' => 'live'])
                ->setDescription('Auto populates when saving.')
                ->setEmptyString('Select'),
            TextField::create('Title', 'Ubiquity Database name'),
            TextField::create('APIKey', 'Ubiquity Database API token'),
        ]);

        return $fields;
    }

    /**
     * Automatically save the environment with the data
     */
    public function onBeforeWrite()
    {
        if (!$this->isInDB() || !$this->Environment) {
            $this->Environment = Director::get_environment_type();
        }

        parent::onBeforeWrite();
    }

    /**
     * Nice Title
     *
     * @return string
     */
    public function NiceTitle()
    {
        return sprintf('%s [%s]', $this->Title, $this->Environment);
    }

    /**
     * Return an array of databases available for the environment
     *
     * @return array A key => title array of databases
     */
    public static function get_available_databases()
    {
        $siteConfig = SiteConfig::current_site_config();
        $environment = Director::get_environment_type();

        return $siteConfig->UbiquityDatabases()
            ->filter('Environment', $environment);
    }

    public static function get_database_options()
    {
        $databases = self::get_available_databases();

        $options = [];
        foreach ($databases as $database) {
            $options[$database->ID] = $database->NiceTitle();
        }

        return $options;
    }

    /**
     * Validate the database
     * - Is in the correct environment
     * - Has an API Token
     */
    public function isValidDatabase()
    {
        $environment = Director::get_environment_type();

        if ($environment !== $this->Environment) {
            return sprintf("Invalid Ubiquity database (%s) for environment", $this->NiceTitle());
        }

        if (!$this->APIKey) {
            return sprintf("API Key is not set on Ubiquity database (%s)", $this->NiceTitle());
        }

        return true;
    }
}
