<?php

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use Ubiquity\Models\UbiquityDatabase;
use Ubiquity\Services\UbiquityService;

class UbiquityTests extends BuildTask
{
    private static $segment = 'UbiquityTests';

    protected $title = 'Ubiquity: Test fetch URL';

    protected $description = 'Test the Ubiquity Service and returns the Fetch URL for the API data';

    public function run($request)
    {
        $database = DataObject::get_one(UbiquityDatabase::class);
        $service = new UbiquityService($database);

        try {
            echo $service->getUbiquityEmailFieldID();
        } catch (Error $e) {
            var_dump($e->getMessage());
        }
    }
}
