<?php

class UbiquityTests extends BuildTask
{
    private static $segment = 'UbiquityTests';

    protected $title = 'Ubiquity: Test fetch URL';

    protected $description = 'Test the Ubiquity Service and returns the Fetch URL for the API data';

    public function run($request)
    {
        $database = DataObject::get_one('UbiquityDatabase');
        if ($database) {
            $service = new UbiquityService($database);
        }

        try {
            echo $service->getUbiquityEmailFieldID();
        } catch (Error $e) {
            var_dump($e->getMessage());
        }
    }
}
