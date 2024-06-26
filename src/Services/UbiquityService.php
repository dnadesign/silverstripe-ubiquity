<?php

namespace Ubiquity\Services;

use Exception;
use GuzzleHttp\Client;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\ArrayList;
use SilverStripe\SiteConfig\SiteConfig;
use Ubiquity\Models\UbiquityDatabase;

class UbiquityService
{
    const METHOD_GET = 'GET';

    const METHOD_POST = 'POST';

    const METHOD_PUT = 'PUT';

    protected $database;

    private static $base_uri = 'https://api.ubiquity.co.nz/';

    static $_cached_ubiquity_fields;

    /**
     * @param UbiquityDatabase
     */
    public function __construct(UbiquityDatabase $database)
    {
        if (!$database->exists()) {
            user_error('Ubiquity Database does not exist', E_USER_ERROR);
        }

        $valid = $database->isValidDatabase();
        if ($valid !== true) {
            throw new Exception('Database is invalid!');
        }

        $this->database = $database;
    }

    /**
     * Determine if ubiquity is enabled
     *
     * @return boolean
     */
    public static function get_ubiquity_enabled()
    {
        return SiteConfig::current_site_config()->UbiquityEnabled;
    }

    /**
     *  Return the Ref ID of the email field
     */
    public function getUbiquityEmailFieldID()
    {
        $fields = $this->getUbiquityDatabaseFields();

        if (!$fields || !is_array($fields)) {
            return false;
        }

        $list = ArrayList::create($fields);
        $field = $list->filter([
            'type' => 'Email',
            'isNullable' => false
        ])->first();

        if (!$field || empty($field) || !isset($field['fieldID'])) {
            return false;
        }

        return $field['fieldID'];
    }

    /**
     * Get fields for the database from ubiquity
     * Caches the fields so we only make one call in this class
     *
     * @return array|false
     */
    public function getUbiquityDatabaseFields()
    {
        if (self::$_cached_ubiquity_fields) {
            return self::$_cached_ubiquity_fields;
        }

        $response = $this->call(self::METHOD_GET, 'database/fields');
        $result = $this->decodeResponse($response);

        if (!isset($result['fields'])) {
            self::$_cached_ubiquity_fields = null;
        } else {
            self::$_cached_ubiquity_fields = $result['fields'];
        }

        return self::$_cached_ubiquity_fields;
    }

    /**
     * Decode response from Ubiquity, catching any errors
     *
     * @param $response
     * @return Array
     */
    public function decodeResponse($response)
    {
        $result = json_decode((string) $response->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Could not decode Ubiquity response');
        }

        if (!is_array($result)) {
            throw new Exception('Ubiquity response is not an array');
        }

        return $result;
    }

    /**
     * get default options for API calls
     */
    public function getDefaultOptions()
    {
        if (!$baseURI = Config::inst()->get(self::class, 'base_uri')) {
            throw new Exception('No base URI defined for Ubiquity service');
        }

        if (!$this->database) {
            throw new Exception('No Ubiquity database is set');
        }

        $options = [
            'base_uri' => $baseURI,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'query' => [
                'format' => 'json',
                'apiToken' => $this->database->APIKey
            ]
        ];

        return $options;
    }

    /**
     * Call the Ubiquity API
     *
     * @param string $method  get or post
     * @param string $uri  URI to append to the base_url
     * @param string $query additional query or filter to add the endpoint
     * @param array $data to include in post body as json
     * @return  GuzzleHttp\Psr7\response
     */
    public function call($method = null, string $uri = null, $query = null, $data = null)
    {
        if (!in_array($method, [self::METHOD_GET, self::METHOD_POST, self::METHOD_PUT])) {
            throw new Exception('Invalid Ubiquity request method');
        }

        $options = $this->getDefaultOptions();

        if ($query && is_array($query)) {
            $options['query'] = array_merge($options['query'], $query);
        }

        if ($data) {
            $options['json'] = ['data' => $data];
        }

        $client = new Client;
        $response = $client->request($method, $uri, $options);

        return $response;
    }

    /**
     * Retrieves an array of subscriber data for an existing contact if it exists
     *
     * @param array The $params array should contain the fieldID of the email field (in the Ubiquity Database) and the value (an email address) of a subscriber
     * @return array|false array of user data or false if it doesn't exist
     */
    public function getContact($params)
    {
        // check the function has been passed a fieldID
        $fieldID = (isset($params['fieldID'])) ? $params['fieldID'] : null;
        if (!$fieldID) {
            throw new Exception('The fieldID of the email field is required.');
        }

        // get the email address submitted
        $email = (isset($params['value'])) ? $params['value'] : null;

        // check the email address is valid
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new Exception('Email field value is not a valid email address');
        }

        $query = [
            'filter' => $this->buildFilterQueryString(array($params))
        ];

        $response = $this->call(self::METHOD_GET, 'database/contacts', $query);

        if ($response->getStatusCode() !== 200) {
            throw new Exception('An ubiquity API error occurred (getContact)');
        }

        $result = $this->decodeResponse($response);

        if ($result && isset($result['totalReturned']) && $result['totalReturned'] > 0) {
            return $result['selectedContacts'][0];
        }

        return false;
    }

    /**
     * extract the contact email address for the submission
     */
    public function getEmailData(array $data)
    {
        // Figure out which field is the email
        $ubiquityEmailID = $this->getUbiquityEmailFieldID();
        if (!$ubiquityEmailID) {
            throw new Exception('No Email field ID set in Ubiquity');
        }

        $emailData = null;
        foreach ($data as $row) {
            if ($row['fieldID'] === $ubiquityEmailID) {
                $emailData = $row;
                break;
            }
        }

        if (!$emailData) {
            throw new Exception('No data fieldID matching Ubiquity email ID found');
        }

        // validate email
        if (filter_var($emailData['value'], FILTER_VALIDATE_EMAIL) === false) {
            throw new Exception('Invalid email address for ubiquity');
        }

        return $emailData;
    }

    /**
     * Send data to a Ubiquity Form
     * Usually to trigger an email being sent from their end.
     */
    public function triggerForm($formID, $data)
    {
        $uri = Controller::join_links('forms', $formID, 'submit');
        $response = $this->call(self::METHOD_POST, $uri, null, $data);

        if ($response->getStatusCode() !== 200) {
            throw new Exception($response->getReasonPhrase());
        }

        return true;
    }

    /**
     * Build query string in format readable by ubiquity
     *
     * @param array (Ubiquity field id => value)
     * @return String
     */
    public function buildFilterQueryString($params = array())
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
     * Filter out the fields that should not be sent to Ubiquity
     * in the case of an update
     * This includes fields that are blank in Ubiquity
     * or have a value but a AllowOverride set to true
     *
     * @param array $data new data to send
     * @return array $contact existing contact data to check against
     */
    public function filterUpdateData($data, $contact)
    {
        // Figure out which field is blank
        $emptyRemoteFields = array_filter($contact['data'], function ($remote) {
            return (empty($remote['value']));
        });

        $updatedData = array_filter($data, function ($row) use ($emptyRemoteFields) {
            // Check if the field has an empty value in the database
            $emptyRemoteField = array_filter($emptyRemoteFields, function ($remote) use ($row) {
                if (isset($remote['fieldID']) && isset($row['fieldID'])) {
                    return ($remote['fieldID'] == $row['fieldID']);
                }
            });
            // If the remote field has an empty value
            // and we have an actual value to push
            // include the field
            if (!empty($emptyRemoteField) && isset($row['value']) && !empty($row['value'])) {
                return true;
            }

            // Otherwise, if the remote field is not empty
            // make sure we can override it
            if (empty($emptyRemoteField)) {
                $allowed = (isset($row['allowUpdate'])) ? $row['allowUpdate'] : false;
                if (filter_var($allowed, FILTER_VALIDATE_BOOLEAN)) {
                    return true;
                }
            }

            return false;
        });

        return array_values($updatedData);
    }
}
