<?php
	$va_home = caNavLink($this->request, "City Readers", '', '', '', '');
	$va_visualizations = caNavLink($this->request, "Visualizations", '', '', 'About', 'visualizations');
	MetaTagManager::setWindowTitle($va_home." > ".$va_visualizations." > Publication City Mapper");

	$vb_dont_show_catalogue_list = $this->getVar('dont_show_catalogue_list');
	if(!($vs_map_id = $this->getVar('map_css_id'))) { $vs_map_id = 'publisherMap'; }
?>

     		
<div class="container" id="groupMap">
	<div class="row">
		<div class="col-sm-10 col-md-10 col-lg-10 col-sm-offset-2 col-md-offset-2 col-lg-offset-2">
<?php		
			if (!$vb_dont_show_catalogue_list) {
				print "<h1 style='margin-top:20px;'>Publication City Mapper</h1>";
			}
?>						
		</div>
	</div>
	<div class="row">

<?php
	if (!$vb_dont_show_catalogue_list) {
?>
		<div class="col-sm-2" id='publisherContentContainer'>
			<p class="vizTitle" style='text-align:left;'>Display books from checked catalogs</p>
			<div id='publisherContent' style="height: 600px;">
				<form id="catalogue_list">
<?php
					$qr_cats = ca_objects::find(['type_id' => 'catalog'], ['returnAs' => 'searchResult']);
		
					$va_opts = [];
					while($qr_cats->nextHit()) {
						$vn_catalogue_id = $qr_cats->get('ca_objects.object_id');
						$vs_name = ucWords($qr_cats->get('ca_objects.preferred_labels.name'));
						if (preg_match('/1825/', $vs_name)) { continue; }		// Skip 1825 for some reason
						$va_opts[$vs_name] = "<div>".caHTMLCheckboxInput('catalogue_ids', ['value' => $vn_catalogue_id, 'checked' => true])." {$vs_name}</div>";
					}
					ksort($va_opts);
					print join("\n", $va_opts);
?>
				</form>
			</div>
		</div>
<?php
	}
?>
		<div class="col-sm-<?php print $vb_dont_show_catalogue_list ? '12' : '10'; ?>" id='<?php print $vs_map_id; ?>'>
			
		</div>
	</div>
	<div class="row">
		<?php print $vb_dont_show_catalogue_list ? '' : '<div class="col-sm-2 col-md-2 col-lg-2"></div>'; ?>

		<div class="col-sm-1 col-md-1 col-lg-1" style="text-align:center;">
			<span id="publisherMapYearSliderStart"></span> 
		</div>
		<div class="<?php print $vb_dont_show_catalogue_list ? 'col-sm-10 col-md-10 col-lg-10' : 'col-sm-8 col-md-8 col-lg-8'; ?>">
			<input id="publisherMapYear" data-slider-id="publisherMapYearSlider" type="text" value="" data-slider-min="1700" data-slider-max="1900" data-slider-step="1" data-slider-value="[1740,1850]"/>
		</div>
		<div class="col-sm-1 col-md-1 col-lg-1" style="text-align:center;">
			<span id="publisherMapYearSliderEnd"></span>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-10 col-md-10 col-lg-10 col-sm-offset-2 col-md-offset-2 col-lg-offset-2">
			<p style='margin-top:30px; font-size:16px;'>Track the growth of the Library's collections by place and year of publication. Use the sliding bar to select a range of publication dates. Choose catalogs from the list at the left to see the publishing history of the collection as it grew over time.</p>
		</div>
	</div>
</div>


<script type="text/javascript">
	jQuery(document).ready(function() {	
		
		//
		// Set up map 
		//
		var map = {l: null, data: null, baseLayer: null, startYear: null, endYear: null};
		map.generateMap = function(start, end, dontFitToBounds) {
			var m = this;
			m.l.eachLayer(function (layer) {
				if (layer != m.baseLayer) { m.l.removeLayer(layer); }
			});
			m.startYear = m.endYear = null;
			
			var allMarkers = L.featureGroup().addTo(m.l);
<?php
	if (!$vb_dont_show_catalogue_list) {
?>			
			var selectedCatalogIDs = jQuery("form#catalogue_list input[type=checkbox]:checked").map(function() {
				return this.value;
			}).get();
<?php
	} else {
?>
			var selectedCatalogIDs = ["<?php print $this->getVar('show_catalogue_id'); ?>"];
<?php
	}
?>
			jQuery.each(m.data, function(k, map_data_by_location) {
				
				if(!map_data_by_location) { return; }
				if(!map_data_by_location['by_date']) { return; }
				
				
				var count_for_current_range = 0;
				jQuery.each(map_data_by_location['by_date'], function(i, by_catalog) {
					jQuery.each(by_catalog, function(catalog_id, date_range) {
						if ((catalog_id > 0) && (selectedCatalogIDs.indexOf(catalog_id) === -1)) { console.log("skip", catalog_id, selectedCatalogIDs);  return; }
						if (
							(start > 0) && (end > 0)
							&& (!(
							((start <= parseInt(date_range['start']))
							&&
							(end >= parseInt(date_range['start'])))
							||
							(
							(start <= parseInt(date_range['end']))
							&&
							(end >= parseInt(date_range['end'])))))
						
						) {
							return;
						}
						count_for_current_range += date_range['count'];
					
						if (!m.startYear || (parseInt(date_range['start']) < m.startYear)) {
							m.startYear = parseInt(date_range['start']);
						}
						if (!m.endYear || (parseInt(date_range['end']) > m.endYear)) {
							m.endYear = parseInt(date_range['end']);
						}
					});
				});
				
				var r = count_for_current_range; 
				if (r == 0) { return; }
				if (r < 10) { r *= 1.5; }
				if (r < 5) { r = 5; }
				if (r > 50 && r < 500) { r = 50; }
				if (r > 500) { r = 80; }
				var circle = L.circleMarker([map_data_by_location['latitude'], map_data_by_location['longitude']], {
					color: '#444', weight: 2, radius: r,
					fillColor: '#cc0000',
					fillOpacity: 0.5
				}).bindPopup((map_data_by_location['name'] ? map_data_by_location['name'] : map_data_by_location['latitude'] + ", " + map_data_by_location['longitude']) + " (" + count_for_current_range + ")").addTo(allMarkers);
				
			});
			
			if (!dontFitToBounds) { m.l.fitBounds(allMarkers.getBounds()); }
		};
		map.setMapSlider = function() {
			
			jQuery('#publisherMapYearSliderStart').html(this.startYear);
			jQuery('#publisherMapYearSliderEnd').html(this.endYear);
			
			jQuery('#publisherMapYear').bootstrapSlider('setAttribute', 'min', this.startYear).bootstrapSlider('setAttribute', 'max', this.endYear);
			jQuery('#publisherMapYear').bootstrapSlider('setValue', [this.startYear, this.endYear]);
		}
		

		// Load map data
		jQuery.getJSON('<?php print caNavUrl($this->request, '*', 'Map', 'GetMapData'); ?>', function(d) {
			map.data = d;
			map.l = L.map('<?php print $vs_map_id; ?>').setView([51.505, -0.09], 13);
			map.baseLayer = L.tileLayer('http://{s}.tile.stamen.com/toner-lite/{z}/{x}/{y}.{ext}', {
				maxZoom: 18, 
				ext: 'png'
			}).addTo(map.l);

			map.generateMap();
			
			jQuery('#<?php print $vs_map_id; ?>').data('_map_', map);
			
			 jQuery("#publisherMapYear").bootstrapSlider({min: map.startYear, max: map.endYear}).on('slide', function(v) {
				jQuery('#publisherMapYearSliderStart').html(v.value[0]);
				jQuery('#publisherMapYearSliderEnd').html(v.value[1]);
				// rebuild map
				map.generateMap(v.value[0], v.value[1], true);
			});
			
			map.setMapSlider();
		});
		
		// Catalogue filters
		jQuery("form#catalogue_list input[type=checkbox]").on('change', function(e) {
			var value = jQuery("#publisherMapYear").bootstrapSlider('getValue');
			map.generateMap(value[0], value[1], true);
		});
	});
</script>