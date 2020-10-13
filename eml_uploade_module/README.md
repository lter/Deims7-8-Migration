This is still a bit experimental and needs testing.

The files need to be in and keeping the rest of the folder structure:

webroot/modules/custom/deims_eal_importer

The upload form may then be accessed within the Drupal website with this URL format:

/eal_import

to store uploaded text files create a folder in:

web/sites/default/files/eal_imports

and to keep the unzipped csv files create a folder:

web/sites/default/files/data

Put the EML file generated with EMLassemblyline R package and the corresponding csv data fiel into a zip archive and upload it.
