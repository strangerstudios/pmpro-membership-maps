jQuery(document).ready(function(){

	var map_element = document.getElementById( 'pmpromm_map' );

	if( typeof map_element === 'undefined' ){
		return;
	}

	//Set your own start location for a map
	if( override_first_marker_location === true ){
		var map_start = { lat: parseFloat( default_start['lat'] ), lng: parseFloat( default_start['lng'] ) };		
	} else {
		//If there isn't any markers, then use our default or override with the pmpromm_default_map_start filter
		var map_start = { lat: parseFloat( default_start['lat'] ), lng: parseFloat( default_start['lng'] ) };
		//Else, use the first marker that's loaded as the starting point
		if( typeof marker_data !== 'undefined' && marker_data.length > 0 ){
			if( marker_data[0]['marker_meta']['lat'] !== null ){
				var map_start = { lat: parseFloat( marker_data[0]['marker_meta']['lat'] ), lng: parseFloat( marker_data[0]['marker_meta']['lng'] ) };
			}
		}
	}
	
	//Initiating the map
	var pmpro_map = new google.maps.Map( map_element, {
		center: map_start,
		zoom: parseInt( zoom_level )
	});

	var infowindows = new Array();

	//Making sure we actually have markers
	if( typeof marker_data !== 'undefined' ){

		for( i = 0; i < marker_data.length; i++ ){

			var latlng = { lat: parseFloat( marker_data[i]['marker_meta']['lat'] ), lng: parseFloat( marker_data[i]['marker_meta']['lng'] ) };

			var contentString = '<div id="pmpro_infowindow_'+i+'" class="'+infowindow_classes+'" style="width: 100%; max-width: '+infowindow_width+'px;">'+
				'<div class="bodyContent">'+
				marker_data[i]['marker_content']+
				'</div>'+
			'</div>';

			var infowindow = new google.maps.InfoWindow({
				content: contentString
			});

			infowindows.push( infowindow );

			var marker = new google.maps.Marker({
				position: latlng,
				map: pmpro_map,
				content: contentString,
				infowindow: infowindow
			});

			google.maps.event.addListener( marker,'click', (function(marker,content,infowindow){ 
			    return function() {
			    	//Close all other infowindows before we open a new one
			    	for( i = 0; i < infowindows.length; i++ ){
			    		infowindows[i].close();
			    	}
			        infowindow.setContent(this.content);
			        infowindow.open(pmpro_map,marker);
			    };
			})(marker,content,infowindow));  

			
		}

	}

});
