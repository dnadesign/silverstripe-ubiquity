<?php

class UbiquityTests extends BuildTask
{
    private static $segment = 'UbiquityTests';

    protected $title = 'Ubiquity: Test fetch URL';

    protected $description = 'Test the Ubiquity Service and returns the Fetch URL for the API data';

    public function run($request)
    {
        $database = UbiquityDatabase::get()->filter('Environment', Director::get_environment_type())->first();
        if ($database) {
            $service = new UbiquityService($database);
        }

        try {
            echo sprintf('Ubiquity Email Field ID for database %s: %s', $database->Title, $service->getUbiquityEmailFieldID());
        } catch (Error $e) {
            var_dump($e->getMessage());
        }
    }
}
