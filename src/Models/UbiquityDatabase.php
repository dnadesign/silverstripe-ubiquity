<?php

namespace Ubiquity\Models;

use Exception;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;
use Ubiquity\Services\UbiquityService;

/**
 * Ubiquity Database model
 */
class UbiquityDatabase extends DataObject
{
    private static $table_name = 'UbiquityDatabase';

    private static $db = [
        'Title' => 'Varchar(255)',
        'APIKey' => 'Varchar(255)',
    ];

    private static $has_one = [
        'SiteConfig' => SiteConfig::class,
    ];

    private static $summary_fields = [
        'Title' => 'Name',
        'APIKey' => 'API Key'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('SiteConfigID');

        $fields->addFieldsToTab('Root.Main', [
            TextField::create('Title', 'Ubiquity Database name'),
            TextField::create('APIKey', 'Ubiquity Database API token'),
        ]);

        // List database fields for reference
        if ($this->IsInDB() && $this->isValidDatabase()) {
            $table = LiteralField::create('FieldTable', $this->generateFieldsTable());
            $fields->addFieldToTab('Root.Main', $table);
        }

        return $fields;
    }

    /**
     * Return an array of databases available for the environment
     *
     * @return array A key => title array of databases
     */
    public static function get_available_databases()
    {
        $siteConfig = SiteConfig::current_site_config();
        if ($siteConfig) {
            return $siteConfig->UbiquityDatabases();
        }
    }

    public static function get_database_options()
    {
        $databases = self::get_available_databases();

        $options = [];
        foreach ($databases as $database) {
            $options[$database->ID] = $database->Title;
        }

        return $options;
    }

    /**
     * Validate the database
     */
    public function isValidDatabase()
    {
        return strlen(trim($this->APIKey)) > 0;
    }

    /**
     * Return a HTML table listing all the fields available in this database
     * with their unique ID
     *
     * @return string
     */
    public function generateFieldsTable()
    {
        try {
            $service = new UbiquityService($this);
            if ($service) {
                $fields = $service->getUbiquityDatabaseFields();
                if ($fields) {
                    $output = '<table style="border:1px solid #333; float: left; margin-right: 20px;">';
                    $output .= '<thead><tr><td colspan="2" style="border:1px solid #333; text-align: center; text-transform:uppercase;">' . $this->Title . '</td></tr></thead>';
                    $output .= '<tboby>';

                    foreach ($fields as $field) {
                        $output .= '<tr >';
                        $output .= '<td style="padding: 5px; border-bottom: 1px solid #DDD">' . $field['name'] . '</td>';
                        $output .= '<td style="padding: 5px; border-bottom: 1px solid #DDD">' . $field['fieldID'] . '</td>';
                        $output .= '</tr>';
                    }

                    $output .= '</tbody>';
                    $output .= '</table>';

                    return $output;
                }
            }
        } catch (Exception $e) {
            return sprintf('Something went wrong: %s', $e->getMessage());
        }

        return 'No data available';
    }
}
