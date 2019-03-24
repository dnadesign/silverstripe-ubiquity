<?php

class UbiquityFormControllerExtension extends DataExtension
{
    public function updateAfterProcess()
    {

        $data = $this->owner->Values()->map('Name', 'Value')->toArray();

        // Check if the form has a Ubiquity database set up.
        $userForm = $this->owner->parent()->data();

        $databaseId = $userForm->UbiquityDatabase;

        if (!$databaseId) {
            return;
        }

        // Check if there are any ubiquity fields
        $ubiquityFields = $userForm->Fields()->exclude('UbiquityFieldID', '');

        if (!$ubiquityFields || $ubiquityFields->Count() == 0) {
            return;
        }

        // Since we have a database and some fields
        // we can use the Ubiquity Service
        $service = new UbiquityService();
        $service->setTargetDatabase($databaseId);
        // Figure out which field is the email
        $emailRefID = $service->getEmailFieldRefID();
        if (!$emailRefID) {
            return;
        }

        // Check if we have a valid value for this field
        $ubiquityEmailFields = $ubiquityFields->filter('UbiquityFieldID', $emailRefID);
        $ubiquityEmailField = $ubiquityEmailFields->first();

        if ($ubiquityEmailField && isset($data[$ubiquityEmailField->Name])) {
            $email = $data[$ubiquityEmailField->Name];
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return;
            }
        }

        // We need to send the editable option separatly
        // as they can have their own UbiquityFieldID even if their parent doesn't.
        $ubiquityOptions = EditableOption::get()->filter('ParentID', $userForm->Fields()->column('ID'))->exclude('UbiquityFieldID', '');

        // If an email Field is set up and has a proper value
        // we can build the array of values to be posted
        $ubiquityEmailData = UbiquityService::prepare_data($ubiquityEmailFields, null, $data);
        $ubiquityData = array();
        $ubiquitySource = array();

        if ($this->owner->UbiquitySourceFieldID && $this->owner->UbiquitySourceName) {
            $ubiquitySource = array(
                'fieldID' => $this->owner->UbiquitySourceFieldID,
                'value' => $this->owner->UbiquitySourceName,
                'allowOverride' => false,
            );
        }

        // Deal with TermsConditions field.
        // If this field is included in the form, the form processor will need to determine if the field is checked.
        // if not included in the form, data is sent to ubiquity (but only if there are ubiquity fields)
        $signupField = $userForm->Fields()->filter('ClassName', 'UbiquitySignupField')->first();

        if ($signupField && $signupField->exists()) {
            // if Checked, data will be submitted as normal (but only if there are ubiquity fields)
            $checked = isset($data[$signupField->Name]) ? $data[$signupField->Name] : false;
            // if unchecked, we submit only the source field
            // so if a user is present in the databse with a blank source
            // it can get populated
            if (filter_var($checked, FILTER_VALIDATE_BOOLEAN) === false && !empty($ubiquitySource)) {
                array_push($ubiquityData, $ubiquitySource);
            } else {
                // Otherwise, send all the data
                // (note: data will be filtered by the service in case of an update)
                $ubiquityData = UbiquityService::prepare_data($ubiquityFields, $ubiquityOptions, $data);
                // Always include the source
                array_push($ubiquityData, $ubiquitySource);
            }
            // var_dump($ubiquityData);
            // die();
        } else {
            // If no sign up field is present, do not interact with Ubiquity
            return;
        }

        try {
            $entryID = $service->post($ubiquityData, $ubiquityEmailData[0]);

            if ($entryID && $entryID !== true && $this->owner->UbiquitySuccessFormID) {
                // Once we sign up the user,
                // we need to send them an email
                // which is triggered by posting to a Ubiquity Form
                try {
                    $emailSent = $service->postToForm($this->owner, $entryID);
                } catch (Exception $e) {
                    // Any exception should already be logged by the service
                    // So do not throw any more error here.
                    // Just log it with SSLog
                    // So the process can keep going
                    //SS_Log::log($e->getMessage(), SS_Log::WARN);
                }
            }
        } catch (Exception $e) {
            // Any exception should already be logged by the service
            // So do no throw any more error here.
            // Just log it with SSLog
            // So the process can keep going
            //SS_Log::log($e->getMessage(), SS_Log::WARN);
        }
    }
}
