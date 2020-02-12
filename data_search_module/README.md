#Dataset Search Page
### Instructions to build a dataset search page mostly with Drupal core functionality except for the last name search by creator


Warning: all of this only works from Drupal 8.8.1 up and it is very frustrating trying it on any lower version!

This is not really a module, but a plugin that will produce a filter based on creator last name

However, it goes into the same folder:

root/web/modules/custom/deims_data_search .... with the folder structure in this repo

go to 'Extend' and turn on the module 'Deims Data Search'

go to 'Configuration' -> 'Performance' -> 'clear all caches'

## Building the search page:

Our search page has four filters: taxonomy-term 'core areas', taxonomy-term 'NTL Themes', Creator last name, full text keyword search

1. Under Structure -> Views -> add new view 'Data Search'

1. pick content of type 'data_set'

1. pick format 'html table', pages and pager as you like

1. save and edit

	1. under 'Advanced' (right side) add Relationship: Content referenced from field_data_set_creator appears in data_set 
	
	1. under 'Advanced' turn on aggregation for a dataset to show only once and not for each creator

	1. Add Fields - those you want to search on, e.g., title, abstract, methods
	
	1. Add Filters
		* taxonomy terms as very easy, just add and configure to pull down or if you allow multiple selections it will be a box with bar on the right side.
		* add 'Dataset author filter' - that's the custom one from above
			* configure to use the relationship
		* add 'Global: Combine fields filter' and configure to the fields you want searched (e.g., title, abstract, methods, in our case) and set operator to 'contains'
		
	1. Make things nicer
		* under format -> table -> settings make title sortable and the default sort
		* give all filters proper labels
		* sort the filters meaningfully by pulling down on the 'add' button and choose 'rearrange'. Here you can also choose if you want the filters linked by 'and' or 'or'.
		
That's it.


resources I have used to figure this out:

to write the plugin:
https://www.webomelette.com/creating-custom-views-field-drupal-8
https://www.webomelette.com/creating-custom-views-filter-drupal-8

for the full text keyword search
https://www.webwash.net/search-across-fields-in-views-using-combine-fields-filter-in-drupal-8/ 