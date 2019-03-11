<?php

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
        $dbs = UbiquityDatabase::get();

        $service = new UbiquityService();

        foreach ($dbs as $database) {
            $service->getTargetEnvironment($database->Environment);
            $service->setTargetDatabase($database->ID);
            $fields = $service->getDatabaseFields();
            $this->outputTable($database->Title, $fields);
        };
    }

    public function outputTable($title, $fields)
    {
        $output = '<table style="border:1px solid #333; float: left; margin-right: 20px;">';
        $output .= '<thead><tr><td colspan="2" style="border:1px solid #333; text-align: center; text-transform:uppercase;">' . $title . '</td></tr></thead>';
        $output .= '<tboby>';

        foreach ($fields as $field) {
            $output .= '<tr >';
            $output .= '<td style="padding: 5px; border-bottom: 1px solid #DDD">' . $field['name'] . '</td>';
            $output .= '<td style="padding: 5px; border-bottom: 1px solid #DDD">' . $field['fieldID'] . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody>';
        $output .= '</table>';

        echo $output;
    }
}
