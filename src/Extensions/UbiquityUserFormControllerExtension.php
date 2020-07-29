<?php

/**
 * Submit to ubiquity after a UDF submission
 * This will be skipped if a databaseID is not defined on the form.
 * A user will be created or updated depending on wether or not they exist in the ubiquity database.
 * Source data can be submitted to Ubiquity
 * This will be skipped if a T&C's field exists in the form and hasnt been cheched, or
 *
 * todo, use try/catch in this class instead of UbiquityService, which shouldnt define how errors are handled
 */
class UbiquityUserFormControllerExtension extends Extension
{

    /**
     * Submit data to ubiquity after UDF processing
     */
    public function updateAfterProcess()
    {
        if (!UbiquityService::get_ubiquity_enabled()) {
            return;
        }

        $userForm = $this->owner->parent()->data();

        if (!$userForm->UbiquityEnabled) {
            return;
        }

        if (!$userForm->UbiquityDatabase()->exists()) {
            return;
        }

        try {
            $database = $userForm->UbiquityDatabase();

            // Set the Datatbase ID on the service
            $service = new UbiquityService($database);

            // set the source data to send
            $data = $this->formatData();
            $data = array_filter($data);

            // No need to go any further if no ubiquity data found
            if (empty($data)) {
                return false;
            }

            $submitSource = true;

            // Deal with TermsConditions field.
            // If this field is included in the form, the form processor will need to determine if the field is checked.
            // if not included in the form, data is sent to ubiquity (but only if there are ubiquity fields)
            $signupField = $userForm->Fields()->filter('ClassName', 'EditableSignupField')->first();

            if ($signupField && $signupField->exists()) {
                // if Checked, data will be submitted as normal (but only if there are ubiquity fields)
                $checked = isset($data[$signupField->Name]) ? $data[$signupField->Name] : false;

                // if unchecked, we submit only the source field if 'UbiquitySubmitSource' is set on the Form
                // If its not set, we don't submit anything.
                // This allows an existing user in the database to be updated with the source only
                if (filter_var($checked, FILTER_VALIDATE_BOOLEAN) === false && !$userForm->UbiquitySubmitSource) {
                    $submitSource = false;
                }
            }

            if ($submitSource && $userForm->UbiquitySourceFieldID && $userForm->UbiquitySourceName) {
                array_push($data, [
                    'fieldID' => $userForm->UbiquitySourceFieldID,
                    'value' => $userForm->UbiquitySourceName,
                    'allowOverride' => false
                ]);
            }

            // submit to ubiquity
            $service->triggerForm($userForm->UbiquitySuccessFormID, $data);
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
            ->exclude('UbiquityFieldID', '');

        // not fields are set to update ubiquity
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
                    }
                }

                $fieldData = [
                    'fieldID' => $field->UbiquityFieldID,
                    'value' => $value,
                    'allowUpdate' => ($field->AllowOverride) ? true : false,
                ];
            } elseif (is_a($field, 'EditableCheckbox')) {
                // If the field is a checkbox
                // Send a value event if unchecked
                $fieldData = [
                    'fieldID' => $field->UbiquityFieldID,
                    'value' => '',
                    'allowUpdate' => ($field->AllowOverride) ? true : false,
                ];
            }

            array_push($data, $fieldData);
        }

        // We need to send the editable options separately
        // as they can have their own UbiquityFieldID even if their parent doesn't.
        $options = EditableOption::get()
            ->filter('ParentID', $userForm->Fields()->column('ID'))
            ->exclude('UbiquityFieldID', '');

        if ($options) {
            foreach ($options as $option) {
                $fieldData = [];

                // Finds it's parent value
                $parent = $option->Parent();
                $selectedValue = (isset($data[$parent->Name])) ? $data[$parent->Name] : null;

                // By default, set the value of a checkbox to null
                // so it can be reset to default value on Ubiquity database
                // If the box has been checked, it will be picked up
                // by the next if
                $fieldData = [
                    'fieldID' => $option->UbiquityFieldID,
                    'value' => '',
                    'allowUpdate' => ($option->AllowOverride) ? true : false,
                ];

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

                        $fieldData['value'] = $optionValue;
                    }
                }

                array_push($data, $fieldData);
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

        SS_Log::log($e->getMessage(), SS_Log::WARN);
        exit();
    }
}
