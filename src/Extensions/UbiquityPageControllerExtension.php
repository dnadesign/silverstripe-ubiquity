<?php

class UbiquityPageControllerExtension extends DataExtension
{
    public function onAfterInit() {
        $UbiquityAnalyticsKeys = ArrayList::create(UbiquityService::get_analytics_keys());

        foreach ($UbiquityAnalyticsKeys as $key) {
            Requirements::javascript('https://wt.engage.ubiquity.co.nz/device/register/'.$key);
        }
	}
}
