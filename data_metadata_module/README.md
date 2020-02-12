# Build a link to display full metadata in PASTA

This module is a Block plugin. It needs to be placed on the dataset content type. There it will get the Drupal ID for the dataset and then obtain the latest version of that dataset in PASTA before buildng the URL to open the 'full metadata' page in PASTA.

It goes into the module folder:

root/web/modules/custom/deims_data_metadata .... with the folder structure in this repo

go to 'Extend' and turn on the module 'DEIMS full metadata'

go to 'Configuration' -> 'Performance' -> 'clear all caches'

go to 'Structure' -> 'Block Layout' -> 'Place block' in the content area. Find 'Link to Metadata Display' in the list. Configure the block to work with content type 'data_set' and 'Select node value' 'Node from URL'

