<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class UbiquityService
{
    protected $targetDatabase;

    protected $targetEnvironment;

    /**
     * @var Client
     */
    protected $client;

    public function __construct() 
    {
        $this->client = new Client(
            array(
                'base_uri' => Config::inst()->get('UbiquityService', 'base_end_point'),
                'headers' => array(
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ),
                'query' => array(
                    'apiToken' => ''
                ),
            )
        );
    }

    public function processData($dataList) {
        $result = [];

        foreach ($dataList as $data) {
            array_push(
                $result,
                [
                    'fieldID' => $data['fieldID'],
                    'value'   => $data['value']
                ]
            );
        }

        return $result;
    }

    /**
     * Update a single query option within request options
     *
     * @param  String $k Key to change/add
     * @param  Mixed $v Value to set
     */
    public function updateQueryOption($k, $v)
    {
        $this->client->requestOptions['query'][$k] = $v;
    }

    /**
     * Allows to switch environment (ie, staging, live)
     */
    public function getTargetEnvironment()
    {
        if (!$this->targetEnvironment) {
            $this->targetEnvironment = (singleton('Director')->isLive()) ? 'production' : 'staging';
        }

        if (!$this->targetEnvironment) {
            user_error('No default environment set!', E_USER_ERROR);
            return null;
        }

        return $this->targetEnvironment;
    }

    public function setTargetEnvironment($env)
    {
        $this->targetEnvironment = $env;
    }

    /**
     * Return the list of databases available for the environment
     */
    public static function get_available_databases()
    {
        $env = (singleton('Director')->isLive()) ? 'production' : 'staging';
        $siteConfig = SiteConfig::current_site_config();
        $dbs = $siteConfig->UbiquityDatabases()->filter('Environment', $env)->toArray();

        $options = false;
        foreach ($dbs as $db) {
            $options[$db->ID] = $db->Title;
        }

        return $options;
    }

    /**
     * Determine if ubiquity is enabled for current subsite
     */
    public static function is_ubiquity_enabled()
    {
        $currentSubsiteID = Subsite::singleton()->currentSubsiteID();

        if (!$currentSubsiteID) {
            // always enable for main site
            return true;
        }

        $mainSiteConfig = DataObject::get_one('SiteConfig', 'SubsiteID=0');

        if ($allEnabledSites = explode(',', $mainSiteConfig->UbiquityEnabledSubsites)) {
            return in_array($currentSubsiteID, $allEnabledSites);
        }

        return false;
    }

    /**
     * Allow to switch the database to query
     * Names and API Keys are defined in subsite's config
     *
     * @return Boolean
     */
    public function setTargetDatabase($databaseId)
    {
        $env = (singleton('Director')->isLive()) ? 'production' : 'staging';
        $database = UbiquityDatabase::get()->byID($databaseId);

        if ($database && $database->exists()) {
            $name = $database->Title;
            $token = $database->APIKey;
            if ($token) {
                $this->targetDatabase = array('Name' => $name, 'Env' => $env, 'Token' => $token);
                $this->updateQueryOption('apiToken', $token);
                $this->updateQueryOption('format', 'json');
                return $this->targetDatabase;
            } else {
                user_error('api_key is not set for database ' . $name . '!', E_USER_ERROR);
            }
        } else {
            user_error("Unknown database with ID $databaseId", E_USER_ERROR);
        }

        return false;
    }

    public function getTargetDatabase()
    {
        return $this->targetDatabase;
    }

    public function hasTargetDatabase()
    {
        return ($this->targetDatabase !== null && !empty($this->targetDatabase));
    }

    public function getTargetDatabaseTitle()
    {
        if ($this->hasTargetDatabase()) {
            $database = $this->getTargetDatabase();
            return sprintf('%s [%s]', $database['Name'], $database['Env']);
        }
        return '';
    }

    // Get database fields
    public function getDatabaseFields()
    {
        if (!$this->hasTargetDatabase()) {
            user_error('No target database is set!', E_USER_ERROR);
        }

        try {
            $response = $this->client->get('database/fields', [
                'query' => [
                    'apiToken' => $this->targetDatabase['Token']
                ]
            ]);
        } catch (Exception $e) {
            $this->exitWithError($e);
        }

        if ($response->getStatusCode() == 200) {
            $result = (string) $response->getBody();
            $resultArray = json_decode($result, true);
            return $resultArray['fields'];
        }

        return false;
    }

    // Return the Ref ID of the email field
    public function getEmailFieldRefID()
    {
        $fields = $this->getDatabaseFields();
        if ($fields && is_array($fields)) {
            $list = ArrayList::create($fields);
            $emailField = $list->filter(array("type" => 'Email', "isNullable" => true))->First();
            if ($emailField && !empty($emailField) && isset($emailField['fieldID'])) {
                return $emailField['fieldID'];
            }
        }
        return false;
    }

    // Post data
    public function post($fields, $emailField)
    {
        $client = new Client();

        if (!$this->hasTargetDatabase()) {
            user_error('No target database is set!', E_USER_ERROR);
        }

        // First check if we have an email address
        if (!$emailField && !is_array($emailField)) {
            return;
        }

        // Check if contact already exists
        $existingRef = $this->getExistingContact($emailField);
        $existingRefID = $existingRef['referenceID'];
        // If contact doesn't already exists
        // create it with PUT

        $fields = array_filter($fields);

        $data = [
            'query' => [
                'format' => 'json',
                'apiToken' => $this->targetDatabase['Token'],
            ],
            'json' => [
                'data' => $fields
            ]
        ];

        if (!$existingRefID) {
            try {
                $response = $this->client->post('database/contacts', $data);
                // Return the reference ID of the newly created entry
                $returnedData = $this->parseBody($response->getBody());
                if (isset($returnedData['referenceID'])) {
                    return $returnedData['referenceID'];
                }
            } catch (Exception $e) {
                // Handle API error
                $this->exitWithError($e);
            }
            return false;
        } else {
            // Otherwise, update it with UPDATE
            // In the case of an update, make sure we update only the fields
            // that are empty on Ubiquity
            // or have the AllowOverride option checked
            $fields = self::prepare_data_for_update($fields, $existingRef);

            // If there is no data to update, exit here
            if (empty($fields)) {
                return true;
            }

            try {
                $response = $this->client->post('database/contacts/' . $existingRefID, $data);
            } catch (Exception $e) {
                // Handle API error
                $this->exitWithError($e);
            }

            if ($response && $response->getStatusCode() === 200) {
                return true;
            }
            return false;
        }
    }

    /**
     * Send data to a Ubiquity Form
     * Usually to trigger an email being sent from
     * their end.
     */
    public function postToForm($form, $refID)
    {
        if (!$form->UbiquitySuccessFormEmailTriggerID ||
            !$form->UbiquitySuccessFormID ||
            !$form->UbiquitySuccessFormAction
        ) return false;

        $data = [
            'json' => [
                [
                    'fieldID' => $form->UbiquitySuccessFormEmailTriggerID,
                    'value'   => $form->UbiquitySuccessFormAction,
                    'referenceID' => $refID,
                    'source' => $form->Link()
                ]
            ],
            'query' => [
                'apiToken' => $this->targetDatabase['Token'],
                'format' => 'json',
            ]
        ];

        try {
            $response = $this->client->post('forms/'.$formID.'/submit', $data);

            // Return the reference ID of the newly created entry
            $returnedData = $this->parseBody($response->getBody());

            if (isset($returnedData['referenceID'])) return $returnedData['referenceID'];
            return true;
        } catch (Exception $e) {
            // Handle API error
            $this->exitWithError($e);
        }

        return false;
    }

    /**
     * Retrieves the ID of an existing entry
     * if exists
     *
     * @return Int (ID) or Boolean (false)
     */
    public function getExistingContact($emailField)
    {
        $email = $this->getFieldValue($emailField);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            user_error('Email address is no valid!', E_USER_ERROR);
        }

        $filter = $this->builFilterQueryString(array($emailField));

        $data = [
            'query' => [
                'apiToken' => $this->targetDatabase['Token'],
                'filter'   => $filter
            ]
        ];

        try {
            $response = $this->client->get('database/contacts', $data);
            // Always clear the query params
            // in case we reuse the same request
        } catch (Exception $e) {
            $this->exitWithError($e);
        }

        $body = $response->getBody();
        $data = $this->parseBody($body);

        // Check if
        if ($data && isset($data['totalReturned']) && $data['totalReturned'] > 0) {
            return $data['selectedContacts'][0];
        }

        return false;
    }

    public function getFieldValue($field = array())
    {
        return (isset($field['value'])) ? $field['value'] : null;
    }

    /**
     * Build query string in format readable by ubiquity
     *
     * @param array (Ubiquity field id => value)
     * @return String
     */
    public function builFilterQueryString($params = array())
    {
        $count = count($params);
        $queryString = '';

        foreach ($params as $param) {
            $count--;
            $queryString .= sprintf("[%s]eq'%s'", $param['fieldID'], $param['value']);
            if ($count) {
                $queryString .= 'and';
            }
        }

        return $queryString;
    }

    /**
     * Parse the form fields to look for the ones with a Ubiquity fieldID
     * and return an array of UbiquityFieldID => value
     * to be posted, appending the mandatory fields.
     *
     * @return array
     */
    public static function prepare_data(DataList $fields, Datalist $options = null, $data)
    {
        $userData = array();

        /**
         * Loop through the fields
         */
        foreach ($fields as $field) {
            if (isset($data[$field->Name])) {
                // Check to see if we need to override the value sent to Ubiquity
                if (!empty($field->UbiquityValue)) {
                    $value = ($field->UbiquityValue == '[submission_date]') ? date(DATE_ATOM) : $field->UbiquityValue;
                } else {
                    // Check If field is an optionSet
                    if ($field->hasMethod('Options')) {
                        // Find the option for the value of the dropdown
                        $option = $field->Options()->filter('Title', $data[$field->Name])->First();
                        // And send it's override value if set
                        if ($option && $option->UbiquityValue) {
                            $value = ($option->UbiquityValue == '[submission_date]') ? date(DATE_ATOM) : $option->UbiquityValue;
                        } else {
                            $value = $data[$field->Name]; // a.k.a $option->Title
                        }
                    } else {
                        // ALlows for empty strings
                        $value = $data[$field->Name];
                    }
                }

                $fieldData = array(
                    'fieldID' => $field->UbiquityFieldID,
                    'value' => $value,
                    'allowUpdate' => ($field->AllowOverride) ? true : false,
                );

                array_push($userData, $fieldData);
            } elseif (is_a($field, 'EditableCheckbox')) {
                // If the field is a checkbox
                // Send a value event if unchecked
                $fieldData = array(
                    'fieldID' => $field->UbiquityFieldID,
                    'value' => '',
                    'allowUpdate' => ($field->AllowOverride) ? true : false,
                );

                array_push($userData, $fieldData);
            }
        }

        /**
         * Loop through the options
         */
        if (isset($options)) {
            foreach ($options as $option) {
                // Finds it's parent value
                $parent = $option->Parent();
                $selectedValue = (isset($data[$parent->Name])) ? $data[$parent->Name] : null;

                // By default, set the value of a checkbox to null
                // so it can be reset to default value on Ubiquity database
                // If the box has been checked, it will be picked up
                // by the next if
                $fieldData = array(
                    'fieldID' => $option->UbiquityFieldID,
                    'value' => '',
                    'allowUpdate' => ($option->AllowOverride) ? true : false,
                );

                // This option has been selected
                // Send it's value or overriden valeu to Ubiquity
                if ($selectedValue) {
                    // If multiple options, the data is an array
                    if ((is_array($selectedValue) && in_array($option->Title, $selectedValue))
                        // Otherwise, it should be a string
                         || $selectedValue == $option->Title
                    ) {
                        // Override value
                        if ($option->UbiquityValue) {
                            $optionValue = ($option->UbiquityValue == '[submission_date]') ? date(DATE_ATOM) : $option->UbiquityValue;
                        } else {
                            // OR use option Title
                            $optionValue = $option->Title;
                        }

                        $fieldData['value'] = $optionValue;
                    }
                }

                array_push($userData, $fieldData);
            }
        }

        return $userData;
    }

    /**
     * Filter out the fields that should not be sent to Ubiquity
     * in the case of an update
     * This includes fields that are blank in Ubiquity
     * or have a value but a AllowOverride set to true
     *
     * @param array
     * @return array
     */
    public static function prepare_data_for_update($fields, $ubiquityData)
    {
        // Figure out which field is blank
        $emptyRemoteFields = array_filter($ubiquityData['data'], function ($remote) {
            return (empty($remote['value']));
        });

        $updatables = array_filter($fields, function ($field) use ($emptyRemoteFields) {
            // Check if the field has an empty value in the database
            $emptyRemoteField = array_filter($emptyRemoteFields, function ($remote) use ($field) {
                if (isset($remote['fieldID']) && isset($field['fieldID'])) {
                    return ($remote['fieldID'] == $field['fieldID']);
                }
            });
            // If the remote field has an empty value
            // and we have an actual value to push
            // inlcude the field
            if (!empty($emptyRemoteField) && isset($field['value']) && !empty($field['value'])) {
                return true;
            }
            // Otherwise, if the remote field is not empty
            // make sure we can override it
            if (empty($emptyRemoteField)) {
                $allowed = (isset($field['allowUpdate'])) ? $field['allowUpdate'] : false;
                if (filter_var($allowed, FILTER_VALIDATE_BOOLEAN)) {
                    return true;
                }
            }
            return false;
        });

        return array_values($updatables);
    }

    /**
     * Convert json string to an array
     *
     * @param  String $json The Json to convert
     * @return mixed Array|false
     */
    public function parseBody($json)
    {
        return json_decode($json, true);
    }

    /**
     * Handle Errors
     */
    public function exitWithError($e)
    {
        $this->getLogger()->logError($e->getMessage());
        throw new Exception($e->getMessage());
        exit();
    }

    /**
     * Helper to get the Analytics keys
     */
    public static function get_analytics_keys()
    {
        $siteConfig = SiteConfig::current_site_config();
        $analytics_keys = array();

        if ($siteConfig->EnableUbiquityAnalytics && (Director::isLive() || $siteConfig->DebugUbiquityAnalytics)) {
            $keys = Config::inst()->get('UbiquityService', 'analytics_keys');
            if ($keys && is_array($keys)) {
                foreach ($keys as $key) {
                    array_push($analytics_keys, array('Key' => $key));
                }
            }
        }

        return $analytics_keys;
    }
}
