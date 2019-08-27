<?php

namespace Ubiquity\Models;

use SilverStripe\Control\Director;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Ubiquity Database model
 */
class UbiquityDatabase extends DataObject
{
    private static $singular_name = 'Ubiquity database';

    private static $plural_name = 'Ubiquity databases';

    private static $table_name = 'UbiquityDatabase';

    private static $db = [
        'Environment' => 'Varchar(255)',
        'Title' => 'Varchar(255)',
        'APIKey' => 'Varchar(255)',
    ];

    private static $has_one = [
        'SiteConfig' => SiteConfig::class,
    ];

    private static $summary_fields = [
        'Title' => 'Name',
        'Environment' => 'Environment'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('SiteConfigID');

        $environment = $this->isInDB() && $this->Environment
            ? $this->Environment
            : Director::get_environment_type();

        $fields->addFieldsToTab('Root.Main', [
            ReadonlyField::create('Environment', 'Environment', $environment),
            TextField::create('Title', 'Ubiquity Database name'),
            TextField::create('APIKey', 'Ubiquity Database API token'),
        ]);

        return $fields;
    }

    /**
     * Automatically save the environemnt with the data
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
            return sprintf("Invalid Ubiquity database (%s) for environnment", $this->NiceTitle());
        }

        if (!$this->APIKey) {
            return sprintf("API Key is not set on Ubiquity database (%s)", $this->NiceTitle());
        }

        return true;
    }
}
