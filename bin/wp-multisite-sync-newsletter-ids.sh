#!/bin/bash
# Loops over each site and syncs Newsletter List IDs with ActiveCampaign

for url in $(wp site list --field=url)
do
  echo $url #Used for progress purposes
  wp pedestal sync-newsletter-ids --url=$url
done
