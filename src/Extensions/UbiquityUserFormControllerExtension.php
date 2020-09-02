<?php

namespace Ubiquity\Extensions;

use Exception;
use Psr\Log\LoggerInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\UserForms\Model\EditableFormField\EditableOption;
use Ubiquity\Forms\Fields\EditableSignupField;
use Ubiquity\Services\UbiquityService;

/**
 * Submit to ubiquity after a UDF submission
 * This will be skipped if a databaseID or a FormID is not defined on the form.
 *
 * This will be skipped if a T&C's field exists in the form and hasn't been checked.
 *
 */
class UbiquityUserFormControllerExtension extends Extension
{

    /**
     * Submit data to ubiquity after UDF processing
     */
    public function updateAfterProcess()
    {
        // Skip if Ubiquity is disabled at a global level
        if (!UbiquityService::get_ubiquity_enabled()) {
            return;
        }

        $userForm = $this->owner->parent()->data();

        // Skip if Ubiquity is disabled at a form level
        if (!$userForm->UbiquityEnabled) {
            return;
        }

        // Skip if no ubiquity form id supplied
        $ubiquityFormID = $userForm->UbiquityFormID;
        if (!$ubiquityFormID) {
            return;
        }

        // Skip if no database set up
        $database = $userForm->UbiquityDatabase();
        if (!$database || !$database->exists()) {
            return;
        }

        try {
            // Set the Database ID on the service
            $service = new UbiquityService($database);

            // set the source data to send
            $data = $this->formatData();
            $data = array_filter($data);

            // No need to go any further if no ubiquity data found
            if (empty($data)) {
                return false;
            }

            // Deal with TermsConditions field.
            // If this field is included in the form, the form processor will need to determine if the field is checked.
            // if not included in the form, data is sent to ubiquity (but only if there are ubiquity fields)
            $signupField = $userForm->Fields()->filter('ClassName', EditableSignupField::class)->first();

            if ($signupField && $signupField->exists()) {
                // Check the data from the form, as if the signup field is not linked to a ubiquity field
                // it will not show up in the $data
                $checked = $this->owner->Values()->filter('Name', $signupField->Name)->first();

                // if unchecked, exit here
                if (!$checked || filter_var($checked->Value, FILTER_VALIDATE_BOOLEAN) === false) {
                    return false;
                }
            }

            // submit to ubiquity
            $service->triggerForm($ubiquityFormID, $data);
        } catch (Exception $e) {
            $this->exitWithError($e);
            return false;
        }
    }


    /**
     * Parse the user defined form fields to look for the ones with an
     * Ubiquity fieldID and return an array of UbiquityFieldID => value
     * to be posted to Ubiquity, appending the mandatory fields.
     *
     * @return array
     */
    public function formatData()
    {
        $userForm = $this->owner->parent()->data();
        $userFormData = $this->owner->Values()->map('Name', 'Value')->toArray();
        $data = [];

        // Check if there are any ubiquity fields in the form
        $fields = $userForm
            ->Fields()
            ->exclude('UbiquityFieldID', ['', NULL]);

        // not all fields are set to update ubiquity
        if (empty($fields)) {
            return $data;
        }

        foreach ($fields as $field) {
            $fieldData = [];
            if (isset($userFormData[$field->Name])) {
                // Check to see if we need to override the value sent to Ubiquity
                if (!empty($field->UbiquityValue)) {
                    $value = ($field->UbiquityValue == '[submission_date]') ? date(DATE_ATOM) : $field->UbiquityValue;
                } else {
                    // Check if field is an optionSet
                    if ($field->hasMethod('Options')) {
                        // Find the option for the value of the dropdown
                        $option = $field->Options()->filter('Title', $userFormData[$field->Name])->first();
                        // And send it's override value if set
                        if ($option && $option->UbiquityValue) {
                            $value = ($option->UbiquityValue == '[submission_date]') ? date(DATE_ATOM) : $option->UbiquityValue;
                        } else {
                            $value = $userFormData[$field->Name]; // a.k.a $option->Title
                        }
                    } else {
                        // Allows for empty strings
                        $value = $userFormData[$field->Name];
                        // Allow for [submission_date] as value
                        if ($value == '[submission_date]') {
                            $value =  date(DATE_ATOM);
                        }
                    }
                }

                $fieldData = [
                    'fieldID' => $field->UbiquityFieldID,
                    'value' => $value
                ];
            } elseif (is_a($field, 'EditableCheckbox')) {
                // If the field is a checkbox
                // Send a value even if unchecked
                $fieldData = [
                    'fieldID' => $field->UbiquityFieldID,
                    'value' => ''
                ];
            }

            if (!empty($fieldData)) {
                array_push($data, $fieldData);
            }
        }

        // We need to send the editable options separately
        // as they can have their own UbiquityFieldID even if their parent doesn't.
        $options = EditableOption::get()
            ->filter('ParentID', $userForm->Fields()->column('ID'))
            ->exclude('UbiquityFieldID', ['', NULL]);

        if ($options) {
            foreach ($options as $option) {
                $fieldData = [];

                // Finds it's parent value
                $parent = $option->Parent();
                $selectedValue = (isset($userFormData[$parent->Name])) ? $userFormData[$parent->Name] : null;

                // This option has been selected
                // Send it's value or overwritten value to Ubiquity
                if ($selectedValue) {
                    // If multiple options, the data is an array
                    if ((is_array($selectedValue) && in_array($option->Title, $selectedValue))
                        // Otherwise, it should be a string
                        || $selectedValue == $option->Title
                    ) {
                        // Override value
                        if ($option->UbiquityValue) {
                            $optionValue = ($option->UbiquityValue == '[submission_date]') ? date(DATE_ATOM) : $option->UbiquityValue;
                        } else {
                            // OR use option Title
                            $optionValue = $option->Title;
                        }

                        $fieldData['fieldID'] = $option->UbiquityFieldID;
                        $fieldData['value'] = $optionValue;
                    }
                }
                if (!empty($fieldData)) {
                    array_push($data, $fieldData);
                }
            }
        }

        return $data;
    }

    /**
     * Log Error and Exit
     *
     * @param Exception Exception containing a message
     */
    public function exitWithError(Exception $e)
    {
        if (Director::isDev()) {
            echo $e->getMessage();
            exit();
        }

        Injector::inst()->get(LoggerInterface::class)->error($e->getMessage());
    }
}
