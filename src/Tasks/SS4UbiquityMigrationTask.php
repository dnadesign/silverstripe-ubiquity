<?php

class SS4UbiquityMigrationTask extends BuildTask
{
    private static $segment = 'SS4UbiquityMigrationTask';

    protected $title = 'SS4 Migration: Ubiquity to new classnames';

    protected $description = 'SS4 Migration: Update Ubiquity models to namespaces';

    protected $enabled = false;

    public function run($request)
    {
        try {
            $this->updateUbiquityModels();

            exit('DONE');
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\r\n";
        }
    }

    public function updateUbiquityModels()
    {
        echo "Updating Ubiquity models\r\n";

        $baseRecords = UbiquityDatabase::get();
        foreach ($baseRecords as $record) {
            $className = $originalClassName = $record->ClassName;
            $pos = strrpos($className, '\\');
            if ($pos !== false) {
                $className = substr($className, $pos + 1);
            }
            // Lookup the new name space
            $mapping = Config::inst()->get('SilverStripe\ORM\DatabaseAdmin', 'classname_value_remapping');
            if (array_key_exists($className, $mapping)) {
                $qualifiedClassName = $mapping[$className];

                // Alter base table
                $update = SQLUpdate::create('"UbiquityDatabase"')->addWhere(array('ClassName' => $originalClassName));
                $update->assign('"ClassName"', $qualifiedClassName);
                $update->execute();
            } else {
                $err = ($className === null)? "Null" : $className;
                echo "Unable to find a mapping for " . $err . "\r\n";
            }
        }
    }
}
