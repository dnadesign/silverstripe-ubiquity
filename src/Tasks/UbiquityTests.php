<?php
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use Ubiquity\Services\UbiquityService;

class UbiquityTests extends BuildTask
{
    private static $segment = 'UbiquityTests';

    protected $title = 'Ubiquity: Test fetch URL';

    protected $description = 'Test the Ubiquity Service and returns the Fetch URL for the API data';

    public function run($request)
    {
        $service = new UbiquityService();
        $databaseId = DataObject::get_one('UbiquityDatabase')->ID;

        $service->setTargetDatabase($databaseId);

        try {
            $service->getEmailFieldRefID();
        } catch (Error $e) {
            var_dump($e->getMessage());
        }
    }
}
