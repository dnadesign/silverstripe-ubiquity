<?php

namespace Ubiquity\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Requirements;

/**
 * Register analytics for all Ubiquity analytics tracking keys
 */
class UbiquityPageControllerExtension extends Extension
{
    public function onAfterInit()
    {
        $siteConfig = SiteConfig::current_site_config();

        if ($siteConfig->UbiquityAnalyticsEnabled && $siteConfig->UbiquityAnalyticsKey) {
            Requirements::javascript('https://wt.engage.ubiquity.co.nz/device/register/' . $siteConfig->UbiquityAnalyticsKey, [
                'async' => true,
                'set_write_js_to_body' => false
            ]);
        }
    }
}
