# Access PASTA cite service

This module is a Block plugin. It needs to be placed on the dataset content type. There it will get the Drupal ID for the dataset and then obtain the latest version of that dataset in PASTA before buildng the URL that retrieves the data citation suggestion.

It goes into the module folder:

root/web/modules/custom/deims_data_citation .... with the folder structure in this repo

go to 'Extend' and turn on the module 'DEIMS Data Citation'

go to 'Configuration' -> 'Performance' -> 'clear all caches'

go to 'Structure' -> 'Block Layout' -> 'Place block' in the content area. Find 'Data Citation Suggestion' in the list. Configure the block to work with content type 'data_set' and 'Select node value' 'Node from URL'

