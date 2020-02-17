( function( $, Drupal, drupalSettings  ) { 
		Drupal.behaviors.d3_lake_bio_zoom = { 
			attach : function( context, settings ) { 
				
				$('.plotDiv').once().each(function() {
						
						//Variables brought over from .module
						var plot_title = drupalSettings.d3_lake_bio_zoom.plot_title; 
						var units = drupalSettings.d3_lake_bio_zoom.plot_units; 
						var Dates = drupalSettings.d3_lake_bio_zoom.Dates; 
						var Richness = drupalSettings.d3_lake_bio_zoom.Richness; 
						var Common = drupalSettings.d3_lake_bio_zoom.Common; 
						var Species = drupalSettings.d3_lake_bio_zoom.Species; 
						var dataset = drupalSettings.d3_lake_bio_zoom.dataset; 
						
						console.log('js is running');
						
						
						// PLOT HALF
						//Set up margin and plot limits
						var margin = { top : 20, right : 20, bottom : 50, left : 50 }, 
						width = 700 - margin.left - margin.right, 
						height = 350 - margin.top - margin.bottom; 
						
						//set up drag for zoom
						var drag = d3.behavior.drag( ); 
						
						//variables for positioning zoom area
						var pos; 
						var bandPos = [ - 1, - 1 ]; 
						
						//get the data
						var data = []; 
						for( var i = 0; i < Dates.length; i ++ ) { 
							data.push( { 
									value: Richness [ i ], 
									date: Dates [ i ], 
							} ); 
						}
						
						//Massage Data
						var parseDate = d3.time.format("%Y").parse; 
						data.forEach( function( d ) { 
								d.date = parseDate( d.date ); 
								d.value = Number( d.value ); 
						} ); 
						
						//function to display tooltip
						bisectDate = d3.bisector( function( d ) { return d.date; } ).left; 
						
						
						//figure out the data ranges min and max
						var xdomain = d3.max( data, function( d ) { return d.date; } ); 
						var xmin = d3.min( data, function( d ) { return d.date; } ); 
						var ydomain = d3.max( data, function( d ) { return d.value; } );
						var ymin = d3.min(data, function( d ) { return d.value; } );
						
						//starting area to zoom in on
						var zoomArea = { 
							x1 : xmin, 
							y1 : ymin, 
							x2 : xdomain, 
							y2 : ydomain
						}; 
						
						//put the plot together
						//add the svg canvas
						var plotSVG = d3.select( ".plotDiv" )
						.append( "svg" )
						.attr( "width", width + margin.left + margin.right )
						.attr( "height", height + margin.top + margin.bottom )
						.append( "g" )
						.attr( "transform", "translate(" + margin.left + "," + margin.top + ")" ); 
						
						//define the actual line
						var line = d3.svg.line( )
						.x( function( d ) { return x( d.date ); } )
						.y( function( d ) { return y( d.value ); } ); 
						
						//set the axes ranges    
						var x = d3.time.scale( )
						.range( [ 0, width ] ) 
						.domain(d3.extent(data, function(d) {return d.date;} )); 
						
						var y = d3.scale.linear( )
						.range( [ height, 0 ] ) 
						.domain(d3.extent(data, function(d) {return d.value;} )); 
						
						//define the axes
						var xAxis = d3.svg.axis( )
						.scale( x )
						.orient( "bottom" ); 
						
						var yAxis = d3.svg.axis( )
						.scale( y )
						.orient( "left" ); 
						
						//define and append the zoom rectangle
						var band = plotSVG.append( "rect" )
						.attr( "width", 0 )
						.attr( "height", 0 )
						.attr( "x", xmin )
						.attr( "y", 0 )
						.attr( "class", "band" ); 
						
						//append the axes to the graph
						plotSVG.append( "g" )
						.attr( "class", "x axis" )
						.attr( "transform", "translate(0," + height + ")" )
						.call( xAxis ); 
						
						plotSVG.append( "g" )
						.attr( "class", "y axis" )
						.call( yAxis ).append( "text" )
						.attr( "transform", "rotate(-90)" )
						.attr( "y", 6 ).attr( "dy", ".71em" )
						.style( "text-anchor", "end" )
						.text( units ); 
						
						//append the zoom path      
						plotSVG.append( "clipPath" )
						.attr( "id", "clip" )
						.append( "rect" )
						.attr( "width", width )
						.attr( "height", height ); 
						
						//append the line
						plotSVG.append( "path" )
						.datum( data )
						.attr( "class", "line" )
						.attr( "clip-path", "url(#clip)" )
						.attr( "d", line ); 
						
						
						//append area for tooltip display
						var focus = plotSVG.append( "g" )
						.style( "display", "none" ); 
						
						// append the x line
						focus.append( "line" )
						.attr( "class", "x" )
						.style( "stroke", "black" )
						.style( "stroke-dasharray", "3,3" )
						.style( "opacity", 0.5 )
						.attr( "y1", 0 )
						.attr( "y2", height ); 
						
						// append the circle at the intersection
						focus.append( "circle" )
						.attr( "class", "y" )
						.style( "fill", "none" )
						.style( "stroke", "black" )
						.attr( "r", 4 ); 
						
						// place the value at the intersection
						focus.append( "text" )
						.attr( "class", "y1" )
						.style( "stroke", "white" )
						.style( "stroke-width", "3.5px" )
						.style( "opacity", 0.8 ).attr( "dx", 8 )
						.attr( "dy", "-.3em" ); 
						
						focus.append( "text" )
						.attr( "class", "y2" )
						.attr( "dx", 8 )
						.attr( "dy", "-.3em" ); 
						
						//define the area for zooming and tooltips
						var zoomOverlay = plotSVG.append( "rect" )
						.attr( "width", width - 10 )
						.attr( "height", height )
						.attr( "class", "zoomOverlay" )
						.on( "mouseover", function( ) { focus.style( "display", null ); } )
						.on( "mouseout", function( ) { focus.style( "display", "none" ); } )
						.on( "mousemove", mousemove ).call( drag ); 
						//apend get data button and set up
						var getdata = plotSVG.append( "g" ); 
						
						getdata.append( "a" )
						.attr("xlink:href", dataset)
						.append( "rect" )
						.attr( "class", "getData" )
						.attr( "width", 75 )
						.attr( "height", 35 )
						.attr( "x", 450 )
						.attr( "y", -30 ); 
						
						getdata.append( "text" )
						.attr( "class", "getDataText" )
						.attr( "width", 75 )
						.attr( "height", 30 )
						.attr( "x", 460 )
						.attr( "y", -7 )
						.text( "Get Data" ); 
						
						//append zoom instructions
						plotSVG.append( "g" )
						.append( "text" )
						.attr( "class", "zoomInText" )
						.attr( "x", 30 )
						.attr( "y", -7 )
						.text( "Drag mouse over area of interest to zoom in" ); 
						//apend zoom out button and set up
						var zoomout = plotSVG.append( "g" ); 
						
						zoomout.append( "rect" )
						.attr( "class", "zoomOut" )
						.attr( "width", 75 )
						.attr( "height", 35 )
						.attr( "x", 350 )
						.attr( "y", -30 )
						.on( "click", function( ) { 
								zoomOut( ); 
						} ); 
						
						zoomout.append( "text" )
						.attr( "class", "zoomOutText" )
						.attr( "width", 75 )
						.attr( "height", 30 )
						.attr( "x", 360 )
						.attr( "y", -7 )
						.text( "Zoom Out" ); 
						
						zoom( );
						
						drag.on( "dragend", function( ) { 
								var pos = d3.mouse( this ); 
								var x1 = x.invert( bandPos [ 0 ] ); 
								var x2 = x.invert( pos [ 0 ] ); 
								
								if( x1 < x2 ) { 
									zoomArea.x1 = x1; 
									zoomArea.x2 = x2; 
								} else { 
									zoomArea.x1 = x2; 
									zoomArea.x2 = x1; 
								} 
								
								var y1 = y.invert( pos [ 1 ] ); 
								var y2 = y.invert( bandPos [ 1 ] ); 
								
								if( x1 < x2 ) { 
									zoomArea.y1 = y1; 
									zoomArea.y2 = y2; 
								} else { 
									zoomArea.y1 = y2; 
									zoomArea.y2 = y1; 
								} 
								
								bandPos = [ - 1, - 1 ]; 
								
								d3.select( ".band" ).transition( )
								.attr( "width", 0 )
								.attr( "height", 0 )
								.attr( "x", bandPos [ 0 ] )
								.attr( "y", bandPos [ 1 ] ); 
								
								zoom( ); 
						} ); 
						
						drag.on( "drag", function( ) { 
								
								var pos = d3.mouse( this ); 
								
								if( pos [ 0 ] < bandPos [ 0 ] ) { 
									d3.select( ".band" )
									.attr( "transform", "translate(" +( pos [ 0 ] ) + "," + bandPos [ 1 ] + ")" ); 
								} 
								if( pos [ 1 ] < bandPos [ 1 ] ) { 
									d3.select( ".band" )
									.attr( "transform", "translate(" +( pos [ 0 ] ) + "," + pos [ 1 ] + ")" ); 
								} 
								if( pos [ 1 ] < bandPos [ 1 ] && pos [ 0 ] > bandPos [ 0 ] ) { 
									d3.select( ".band" )
									.attr( "transform", "translate(" +( bandPos [ 0 ] ) + "," + pos [ 1 ] + ")" ); 
								} 
								
								//set new position of band when user initializes drag
								if( bandPos [ 0 ] == - 1 ) { 
									bandPos = pos; 
									d3.select( ".band" )
									.attr( "transform", "translate(" + bandPos [ 0 ] + "," + bandPos [ 1 ] + ")" ); 
								} 
								
								d3.select( ".band" ).transition( ).duration( 1 )
								.attr( "width", Math.abs( bandPos [ 0 ] - pos [ 0 ] ))
								.attr( "height", Math.abs( bandPos [ 1 ] - pos [ 1 ] )); 
						} ); 
						
						
						function zoom( ) { 
							//recalculate domains
							if( zoomArea.x1 > zoomArea.x2 ) { 
								x.domain( [ zoomArea.x2, zoomArea.x1 ] ); 
							} else { 
								x.domain( [ zoomArea.x1, zoomArea.x2 ] ); 
							} 
							
							if( zoomArea.y1 > zoomArea.y2 ) { 
								y.domain( [ zoomArea.y2, zoomArea.y1 ] ); 
							} else { 
								y.domain( [ zoomArea.y1, zoomArea.y2 ] ); 
							} 
							
							//update axis and redraw lines
							var t = plotSVG.transition( ).duration( 750 ); 
							t.select( ".x.axis" ).call( xAxis ); 
							t.select( ".y.axis" ).call( yAxis ); 
							
							t.selectAll( ".line" ).attr( "d", line ); 
						} 
						
						var zoomOut = function( ) { 
							x.domain( [ xmin, xdomain ] ); 
							y.domain( [ ymin, ydomain ] ); 
							
							var t = plotSVG.transition( ).duration( 750 ); 
							t.select( ".x.axis" ).call( xAxis ); 
							t.select( ".y.axis" ).call( yAxis ); 
							
							t.selectAll( ".line" ).attr( "d", line );     
						} 
						
						function mousemove( ) {
							var x0 = x.invert( d3.mouse( this )[ 0 ] ),
							i = bisectDate( data, x0, 1 ),
							d0 = data [ i - 1 ],
							d1 = data [ i ],
							d = x0 - d0.date > d1.date - x0 ? d1 : d0;
							
							focus.select( "circle.y" )
							.attr( "transform",
								"translate(" + x( d.date ) + "," +
								y( d.value ) + ")" );
							focus.select( "text.y1" ).attr( "transform", 
								"translate(" + x( d.date ) + "," + 
								y( d.value ) + ")" ).text( d.value ); 
								
								focus.select( "text.y2" ).attr( "transform", 
									"translate(" + x( d.date ) + "," + 
									y( d.value ) + ")" ).text( d.value ); 
									focus.select( ".x" ).attr( "transform", 
										"translate(" + x( d.date ) + "," + 
										y( d.value ) + ")" ).attr( "y2", height - y( d.value ) ); 
						}
						
						//////////////////////////////////////////
				});
			}
			
		}; 
} )( jQuery, Drupal, drupalSettings ); 



