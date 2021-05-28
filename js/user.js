jQuery(document).ready(function(){

	var pmpromm_map_element = document.getElementById( 'pmpromm_map' );

	if( typeof pmpromm_map_element === 'undefined' ){
		return;
	}

	//Set your own start location for a map
	if( pmpromm_vars.override_first_marker_location === true ){
		var pmpromm_map_start = { lat: parseFloat( pmpromm_vars.default_start['lat'] ), lng: parseFloat( pmpromm_vars.default_start['lng'] ) };		
	} else {
		//If there isn't any pmpromm_markers, then use our default or override with the pmpromm_default_pmpromm_map_start filter
		var pmpromm_map_start = { lat: parseFloat( pmpromm_vars.default_start['lat'] ), lng: parseFloat( pmpromm_vars.default_start['lng'] ) };
		//Else, use the first pmpromm_marker that's loaded as the starting point
		if( typeof pmpromm_vars.marker_data !== 'undefined' && pmpromm_vars.marker_data.length > 0 ){
			if( pmpromm_marker_data[0]['marker_meta']['lat'] !== null ){
				var pmpromm_map_start = { lat: parseFloat( pmpromm_vars.marker_data[0]['marker_meta']['lat'] ), lng: parseFloat( pmpromm_marker_data[0]['marker_meta']['lng'] ) };
			}
		}
	}
	
	var pmpromm_map_arguments = {
		center: pmpromm_map_start,
		zoom: parseInt( pmpromm_vars.zoom_level )
	};

	//Initiating the map
	var pmpro_map = new google.maps.Map( pmpromm_map_element, pmpromm_map_arguments);

	if( pmpromm_vars.map_styles !== "" ){
		pmpro_map.setOptions({ styles:  JSON.parse( pmpromm_vars.map_styles ) });
	}

	var pmpromm_infowindows = new Array();

	//Making sure we actually have pmpromm_markers
	if( typeof pmpromm_vars.marker_data !== 'undefined' ){

		for( i = 0; i < pmpromm_vars.marker_data.length; i++ ){

			var pmpromm_latlng = { lat: parseFloat( pmpromm_vars.marker_data[i]['marker_meta']['lat'] ), lng: parseFloat( pmpromm_vars.marker_data[i]['marker_meta']['lng'] ) };

			var pmpromm_contentString = '<div id="pmpro_pmpromm_infowindow_'+i+'" class="'+pmpromm_infowindow_classes+'" style="width: 100%; max-width: '+pmpromm_vars.infowindow_width+'px;">'+
				'<div class="bodyContent">'+
				pmpromm_vars.marker_data[i]['marker_content']+
				'</div>'+
			'</div>';

			var pmpromm_infowindow = new google.maps.InfoWindow({
				content: pmpromm_contentString
			});

			pmpromm_infowindows.push( pmpromm_infowindow );

			var pmpromm_marker = new google.maps.Marker({
				position: pmpromm_latlng,
				map: pmpro_map,
				content: pmpromm_contentString,
				pmpromm_infowindow: pmpromm_infowindow
			});

			google.maps.event.addListener( pmpromm_marker,'click', (function(pmpromm_marker,content,pmpromm_infowindow){ 
			    return function() {
			    	//Close all other pmpromm_infowindows before we open a new one
			    	for( i = 0; i < pmpromm_infowindows.length; i++ ){
			    		pmpromm_infowindows[i].close();
			    	}
			        pmpromm_infowindow.setContent(this.content);
			        pmpromm_infowindow.open(pmpro_map,pmpromm_marker);
			    };
			})(pmpromm_marker,pmpromm_contentString,pmpromm_infowindow));  

			
		}

	}

});
