<?php

namespace Wreda\Ubiquity\tests;

use SapphireTest;

class UbiquityApiTests extends SapphireTest
{
    protected static $fixture_file = 'userforms-ubiquity/tests/fixtures/UbiquityFixtures.yml';

    private $error = array();

    /**
     * Config
     */

    public function setUpOnce()
    {
        parent::setUpOnce();
    }

    public function tearDownOnce()
    {
        parent::tearDownOnce();
    }

    public function handleError($errno, $errstr, $errfile, $errline)
    {
        $this->error = array($errno => $errstr);
    }

    /**
     * Tests
     */

    public function testEnvironments()
    {
        $service = new UbiquityService();

        // The fixture should define the Config
        // to use the 'staging' environment
        $env = $service->getTargetEnvironment();
        $this->assertEquals($env, 'staging');

        // So we should be able to set a database from there
        $db = $service->setTargetDatabase('customer');
        $this->assertArrayHasKey('Name', $db);

        // But we should be able to set the environemnt
        // to whatever we want
        $service->setTargetEnvironment('whatever');
        $this->assertEquals('whatever', $service->getTargetEnvironment());

        // But if no database is set for this environment,
        // a user error is thrown
        set_error_handler(array($this, 'handleError'));
        $db = $service->setTargetDatabase('customer');
        $this->assertArrayHasKey(E_USER_ERROR, $this->error);
        $this->assertStringStartsWith('Unknown database [whatever]', $this->error[E_USER_ERROR]);
        restore_error_handler();
    }

    public function testContactDatabases()
    {
        $envs = Config::inst()->get(UbiquityService::class, 'databases');

        $service = new UbiquityService();
        $service->setUri('database/fields');

        foreach ($envs as $env => $databases) {
            foreach ($databases as $name => $info) {
                $service->setTargetEnvironment($env);
                $service->setTargetDatabase($name);
                $response = $service->call();

                $this->assertEquals($response->getStatusCode(), 200);
            }
        }
    }
}
