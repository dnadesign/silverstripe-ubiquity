<?php

namespace Ubiquity\Tasks;

use SilverStripe\Dev\BuildTask;
use Ubiquity\Models\UbiquityDatabase;
use Ubiquity\Services\UbiquityService;

class UbiquityFieldsList extends BuildTask
{
    /**
     * @var string
     */
    private static $segment = 'UbiquityFieldsList';

    protected $title = 'Ubiquity: Test fetch URL';

    protected $description = 'Test the Ubiquity Service and returns tables showing the database fields';

    public function run($request)
    {
        $databases = UbiquityDatabase::get();

        foreach ($databases as $database) {
            echo $database->generateFieldsTable();
        };
    }
}
