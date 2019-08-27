<?php

namespace Ubiquity\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Requirements;

/**
 * Register analytics for all Ubiqutiy analytics tracking keys
 */
class UbiquityPageControllerExtension extends Extension
{
    public function onAfterInit() {
        $SiteConfig = SiteConfig::current_site_config();

        if ($SiteConfig->UbiquityAnalyticsEnabled && $SiteConfig->UbiquityAnalyticsKey) {
            Requirements::javascript('https://wt.engage.ubiquity.co.nz/device/register/'.$SiteConfig->UbiquityAnalyticsKey, [
                'async' => true,
                'set_write_js_to_body' => false
            ]);
        }
    }
}
