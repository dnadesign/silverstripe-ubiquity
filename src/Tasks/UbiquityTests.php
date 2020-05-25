<?php

namespace Ubiquity\Tasks;

use Exception;
use SilverStripe\Dev\BuildTask;
use Ubiquity\Models\UbiquityDatabase;
use Ubiquity\Services\UbiquityService;

class UbiquityTests extends BuildTask
{
    private static $segment = 'UbiquityTests';

    protected $title = 'Ubiquity: Test fetch URL';

    protected $description = 'Test the Ubiquity Service and returns the Fetch URL for the API data';

    public function run($request)
    {
        $database = UbiquityDatabase::get()->first();
        if ($database) {
            $service = new UbiquityService($database);
        }

        try {
            echo sprintf('Ubiquity Email Field ID for database %s: %s', $database->Title, $service->getUbiquityEmailFieldID());
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit();
        }
    }
}
