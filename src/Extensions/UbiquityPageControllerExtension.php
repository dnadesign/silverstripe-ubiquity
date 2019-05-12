<?php

namespace DNADesign\Ubiquity\Extensions;

use Extension;
use DNADesign\Ubiquity\Services\UbiquityService;
use ArrayList;

/**
 * Register analytics for all Ubiqutiy analytics tracking keys
 */
class UbiquityPageControllerExtension extends Extension
{
    /**
     * Fetch Ubiquity traking keys for use in templates
     */
    public function UbiquityAnalyticsKeys()
    {
        // get the analytics keys, and check if tracking is enabled for this subsite
        $keys = UbiquityService::get_analytics_keys();
        return (!empty($keys)) ? new ArrayList($keys) : null;
    }
}
