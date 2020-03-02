# adding the bibliography

Use Composer to install module with all its dependencies:
[bibcite] (https://www.drupal.org/project/bibcite)
 
Export from bib manager as Bibtex file. (At NTL we manage our pubs in Zotero, but any other system will work.)

Eneable in Drupal

Problems encountered that should be eliminated befor uploading:
	1. Too many commas in one name. Only one comma after the last name not between initials or other places among first names.
	1. some random quotation marks in abstracts
	1. special characters - although some come through very well.
	
Go to: Content, use tab 'Bibliography', click on 'import', choose bibtex file and uplod

Set up a view to display the pubs (Structure -> views -> add new view):
	1. Name
	1. choose show 'Reference' type 'all'
	1. create page
	1. set correct path, number to display, save and edit
	
	in Edit screen:
	1. format: Table
	1. add fields:
		1. ID field
			1. exclude from display
		1. year of publication
			1. exclude from display
		1. citation
			1. default style
			1. turn off labe
			1. rewrite results: overwrite output: {{ citation }} <a href="/bibcite/reference/{{ id }}">view</a>
	1. format: Settings: Group by year
	1. add filter
		1. year of publication
			1. expose to users
		

