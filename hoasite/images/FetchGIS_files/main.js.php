// This automatically logs out users when they close the browser tab (PHP sessions work at the window level otherwise).
var unloaded = false;
//$(window).on('beforeunload', unload);
//$(window).on('unload', unload);
window.unload = function(){
	if(!unloaded){
		$('body').css('cursor','wait');
		var hackDate = new Date();
		$.ajax({
			type: 'get',
			async: false,
			url: 'ws/auth/logout.php?t=' + hackDate.getTime() + '&map=' + currentMap,
			success:function(){ 
				unloaded = true; 
				$('body').css('cursor','default');
				var locStr = location.href;
				locStr = locStr.substr(0, locStr.indexOf("&"));

				window.open(locStr, "_self", {}, true);
			},
			timeout: 5000
		});
	}

	// if service worker active, set caches to record when the map first loads.
	if(sw !== false){
		swSendMessage('{"recTiles":"true"}');
		swSendMessage('{"recData":"true"}');
	}
}

$( document ).ajaxComplete(function( event, response, settings ) { //listening for need to login errors on applyedit calls
	document.dispatchEvent(new CustomEvent('badSession', {bubbles:true,detail:{response:response}}));
});


document.addEventListener('badSession',function(event){
	if(document.querySelector('#reloadDialog')) return; //no need to check again if dialog is present
	let response = event && event.detail && event.detail.response ? event.detail.response : false;
	if(
		!response || (
			response && response.status == 401 && 
			(
				response.responseText && response.responseText.toLowerCase().indexOf('session') > -1 &&
				response.responseText.toLowerCase().indexOf('login') > -1
			)
		)
	){
		document.querySelector('.navbar').classList.add('disabledButton');
		document.querySelector('#mainWrapper').classList.add('disabledButton');
		let dialog = document.createElement('dialog');
		dialog.id = 'reloadDialog';
		dialog.style.cssText = 'text-align: center;border: 1px solid grey;padding: 24px;';
		dialog.innerHTML = `<div style="margin-bottom:8px">Your session appears to have ended.</div><div>Please <a href="${window.location.href}">reload the page</a></div>`
		document.querySelector('body').append(dialog);
		document.querySelector('#reloadDialog').showModal();
	}
})

if(ieVersion === "IE 11"){
	$('head').append('<style>.hideFromIE{display: none!important;}</style>');
}

for (var key in localStorage){ //remove old table settings
    if(key == ('map-tableSettings') || key == ('map-tableSettings2')){
        localStorage.removeItem(key)
    }
}

// #################################################
// ####				Init PHP vars 				####
// #################################################
var hasParcelLayer = true;
var failList = '';
var modules = [];
var sniffer = false;
var isPHapp = false;
var isFEHapp = false;
var permitting = false;
var featureEditEnabled = false;
var theme = '';
isDow = false;
var useLRP = true;
var config_taxh = "yes";
var hasFeeService = false, hasPrivateLayers = false;var helpURL = "https://help.fetchgis.com/"
var soilHorizons = false;
	
	var dynamicTocOptions = [
    {
        "layerId"   : "zoningLayer"
	},
	{
		"layerId"   : "trashPickupLayer"
	},
	{
		"layerId"   : "zoningBeaverLayer"
	},
	{
		"layerId"   : "zoningHamptonLayer"
	},	
	{
		"layerId" : "zoningKawkawlinLayer"
	},
	{
		"layerId" : "sanitarySewerServicePossibleLayer"
	},
	{
		"layerId" : "waterServicePossibleLayer"
	},
	{
		"layerId" : "noWakeLayer"
	},
];
// SEE THE TEMPLATE FILE'S COMMENTS BEFORE ADDING ANY LAYER DEFS
var layerDefs =	[
	{
		"id"			: "imageryLayers",
		"name" 			: "Imagery",
		"checkboxState"	: "checked",
		"images"		: [
							{"imageURL":"img/aerial08legend.png", "imageDesc":"Imagery"}
						  ],
		"parent"		: true,
		"child"			: false,
		"childLayers"	: [
			{
				"id"            : "imageryLayer",
				"name"          : "2020 Imagery",
				"checkboxState" : "checked",
				"images"        : [
									{"imageURL":"img/aerial08legend.png", "imageDesc":"Imagery"}
								],
				"parent"        : false,
				"child"         : true,
				"childLayers"   : null
			},
			{
				"id"            : "imagery2015Layer",
				"name"          : "2015 Imagery",
				"checkboxState" : "unchecked",
				"images"        : [
									{"imageURL":"img/aerial08legend.png", "imageDesc":"Imagery"}
								],
				"parent"        : false,
				"child"         : true,
				"childLayers"   : null
			},
            {
                "id"            : "imagery2010Layer",
                "name"          : "2010 Imagery",
                "checkboxState" : "unchecked",
                "images"        : [
                                    {"imageURL":"img/aerial08legend.png", "imageDesc":"Imagery"}
                                  ],
                "parent"        : false,
                "child"         : true,
                "childLayers"   : null
            },
			{
                "id"            : "imagery2005Layer",
                "name"          : "2005 Imagery",
                "checkboxState" : "unchecked",
                "images"        : [
                                    {"imageURL":"img/aerial08legend.png", "imageDesc":"Imagery"}
                                  ],
                "parent"        : false,
                "child"         : true,
                "childLayers"   : null
            }
        ]
	},
	{
		"id"			: "bayParcelLayer",
		"name" 			: "Parcels",
		"checkboxState"	: "checked",
		"images"		: [
							{"imageURL":"img/Parcelslegend.png", "imageDesc":"Parcel"}
						  ],
		"parent"		: false,
		"child"			: false,
		"childLayers"	: null
	},	
	{
		"id"			: "streetsLayer",
		"name" 			: "Streets",
		"checkboxState"	: "checked",
		"images"		: [
							{"imageURL":"img/fedrdlegend.png", "imageDesc":"Federal Highway"},
							{"imageURL":"img/staterdlegend.png", "imageDesc":"State Highway"},
							{"imageURL":"img/mainrdlegend.png", "imageDesc":"Major/Primary Highway"},
							{"imageURL":"img/localrdlegend.png", "imageDesc":"Minor/Local Road"}
						  ],
		"parent"		: false,
		"child"			: false,
		"childLayers"	: null 
	},
	{
		"id"			: "roadProjectLayers",
		"name" 			: "Road Projects",
		"checkboxState"	: "unchecked",
		"images"		: [
							{}
						  ],
		"childLayers"	: [
			{
				"id"            : "bcatsLayer",
				"name"          : "BCATS Projects",
				"checkboxState" : "unchecked",
				"images"        : [
									{"imageURL":"img/bay/bcats_completed.png", "imageDesc":"Completed"},
									{"imageURL":"img/bay/bcats_proposed.png", "imageDesc":"Proposed"},
									{"imageURL":"img/bay/bcats_inProgress.png", "imageDesc":"In Progress"},
								  ],
			},
			{
				"id"            : "bcrcLayer", 
				"name"          : "BCRC Projects",
				"checkboxState" : "unchecked",
				"images"        : [
									{"imageURL":"img/bay/bcrc.png", "imageDesc":"BCRC Project"},
								  ],
			},
			{
                "id"            : "roadProjectsLayer",
                "name"          : "Bay City Projects",
                "checkboxState" : "unchecked",
                "images"        : [
                                    {"imageURL":"img/bay/bayCityProjects.png", "imageDesc":"Bay City Project"}
                                  ],
            }
        ]
	},
	{
		"id"			: "hydroLayers",
		"name" 			: "Lakes, Streams and Drains",
		"checkboxState"	: "unchecked",
		"images"		: [
							{}
						  ],
		"childLayers"	: [
			{
				"id"            : "hydroLayer",
				"name"          : "Lakes and Streams",
				"checkboxState" : "checked",
				"images"        : [
									{"imageURL":"img/bayHydroRiversLegend.png", "imageDesc":"Rivers"},
									{"imageURL":"img/bayHydroWaterBodies1Legend.png", "imageDesc":"Water Bodies"}
								],
			},
			{
				"id"            : "drainsLayer", 
				"name"          : "Drains",
				"checkboxState" : "unchecked",
				"images"        : [
									{"imageURL":"img/bay/drains.png", "imageDesc":"Drains"},
								  ],
			}
        ]
	},

    {
        "id"            : "femaFloodLayer",
        "name"          : "FEMA Flood Hazard",
        "checkboxState" : "unchecked",
        "images"        : [
							{"imageURL":"img/FEMA_floodplain/1PercentAnnualFlood.png", "imageDesc":"1% Flood Hazard"},
							{"imageURL":"img/FEMA_floodplain/dot2PercentAnnualFlood.png", "imageDesc":".2% Flood Hazard"},
							{"imageURL":"img/FEMA_floodplain/undeterminedHazard.png", "imageDesc":"Undetermined Hazard"},
							{"imageURL":"img/FEMA_floodplain/regFloodway.png", "imageDesc":"Regulatory Floodway"},
							{"imageURL":"img/FEMA_floodplain/specFloodway.png", "imageDesc":"Special Floodway"},
							{"imageURL":"img/FEMA_floodplain/future1Percent.png", "imageDesc":"Future 1% Flood Hazard"},
							{"imageURL":"img/FEMA_floodplain/reducedLevee.png", "imageDesc":"Reduced Risk due to Levee"},
						  ],
		"printLegend"	: true,
		"opSlider"      : true,
        "parent"		: false,
		"child"			: false,
		"childLayers"	: null
    },
    {
        "id"            : "wetLayer",
        "name"          : "Wetlands",
        "checkboxState" : "unchecked",
        "images"        : [
							{"imageURL":"img/bay/wetlandArea.png", "imageDesc":"Wetlands as identified on NWI and MIRIS maps"},
							{"imageURL":"img/bay/wetlandSoil.png", "imageDesc":"Soil areas which include wetland soils"},
							{"imageURL":"img/bay/wetlandCombined.png", "imageDesc":"Areas which include both of the above"}
						  ],
		"printLegend"	: true,
		"opSlider"      : true,
        "parent"		: false,
		"child"			: false,
		"childLayers"	: null
    },
    {
        "id"            : "poiLayers",
        "name"          : "Points of Interest",
        "checkboxState" : "unchecked",
        "images"        : [],
        "parent"		: true,
		"child"			: false,
		"childLayers"	: [
            {
                "id"            : "airportsLayer",
                "name"          : "Airports",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/darkAirport.png", "imageDesc":"Airport"}],
                "printLegend"	: true,
				"parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
            {
                "id"            : "boatLaunchesLayer",
                "name"          : "Boat Launch Sites",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/darkBoatLaunches.png", "imageDesc":"Boat Launch"}],
                "printLegend"	: true,
				"parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
            {
                "id"            : "courthouseLayer",
                "name"          : "Courthouses",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/darkCourthouses.png", "imageDesc":"Courthouse"}],
                "printLegend"	: true,
				"parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
            {
                "id"            : "fairgroundsLayer",
                "name"          : "Fairgrounds",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/darkCountyFairgrounds.png", "imageDesc":"Fairgrounds"}],
                "printLegend"	: true,
				"parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
            {
                "id"            : "historicalMarkersLayer",
                "name"          : "Historical Markers",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/darkHistoricalMarkers.png", "imageDesc":"Historical Marker"}],
                "printLegend"	: true,
				"parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
            {
                "id"            : "librariesLayer",
                "name"          : "Libraries",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/darkLibraries.png", "imageDesc":"Library"}],
                "printLegend"	: true,
				"parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
            {
                "id"            : "planetariumLayer",
                "name"          : "Planetarium",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/darkPlanetarium.png", "imageDesc":"Planetarium"}],
                "printLegend"	: true,
				"parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },          
		]
    },
    {
		"id"			: "snowParkingLayer",
		"name" 			: "Snow Event Parking",
		"checkboxState"	: "unchecked",
		"images"		: [{"imageURL":"img/bay/snowParking.png", "imageDesc":"Snow Event Parking"}],
		"printLegend"	: true,
		"parent"		: false,
		"child"			: false,
		"childLayers"	: null
	},
    {
		"id"			: "noWakeLayer",
		"name" 			: "No Wake Zones",
		"checkboxState"	: "unchecked",
		"images"		: "dynamic",
	},
    {
		"id"			: "parksLayer",
		"name" 			: "Parks",
		"checkboxState"	: "unchecked",
		"images"		: [{"imageURL":"img/bayParksLegend.png", "imageDesc":"Park"}],
		"printLegend"	: true,
		"parent"		: false,
		"child"			: false,
		"childLayers"	: null
	},
    {
		"id"			: "trailLayers",
		"name" 			: "Trails",
		"checkboxState"	: "unchecked",
		"images"		: [],
		"parent"		: true,
		"child"			: false,
		"childLayers"	: [
			{
				"id"			: "trailheadLayer",
				"name"			: "Trail Heads",
				"checkboxState" : "unchecked",
				"images"		: [{"imageURL":"img/bay/trailheads.png", "imageDesc":"Trail Head"}],
				"printLegend"	: true,
				"parent"		: false,
				"child"			: true,
				"childLayers"	: null
			},
			{
				"id"			: "nonMotorTrailsLayer",
				"name" 			: "Non Motorized Trails",
				"checkboxState"	: "unchecked",
				"images"		: [
									{"imageURL":"img/bayNonMotGreenLegend.png", "imageDesc":"Asphalt, BoardWalk, Concrete"},
									{"imageURL":"img/bayNonMotBlueLegend.png", "imageDesc":"Water"}
								  ],
				"printLegend"	: true,
				"parent"		: false,
				"child"			: true,
				"childLayers"	: null
			},
			{
				"id"			: "proposedTrailsLayer",
				"name" 			: "Proposed Non Motorized Trails",
				"checkboxState"	: "unchecked",
				"images"		: [
									{"imageURL":"img/bay/proposedTrailsLegend.png", "imageDesc":"Asphalt, BoardWalk, Concrete"},
								  ],
				"printLegend"	: true,
				"parent"		: false,
				"child"			: true,
				"childLayers"	: null
			},
        ]
	},
    {
		"id"			: "schoolsLayers",
		"name" 			: "Schools",
		"checkboxState"	: "unchecked",
		"images"		: [],
		"parent"		: true,
		"child"			: false,
		"childLayers"	: [
            {
                "id"            : "schoolsLayer",
                "name"          : "Schools",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/darkSchools.png", "imageDesc":"School"},],
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
            {
                "id"            : "schoolDistrictsLayer",
                "name"          : "School Districts",
                "checkboxState" : "unchecked",
                "images"        : [
									{"imageURL":"img/baySchoolsBangorLegend.png", "imageDesc":"Bangor Township Schools"},
									{"imageURL":"img/baySchoolsBayCityLegend.png", "imageDesc":"Bay City Schools"},
									{"imageURL":"img/baySchoolsEssexHamLegend.png", "imageDesc":"Essexville-Hampton Schools"},
									{"imageURL":"img/baySchoolsFreelandLegend.png", "imageDesc":"Freeland Schools"},
									{"imageURL":"img/baySchoolsPinnLegend.png", "imageDesc":"Pinconning Area Schools"},
									{"imageURL":"img/baySchoolsReeseLegend.png", "imageDesc":"Reese Schools"},
									{"imageURL":"img/baySchoolsStandSterlLegend.png", "imageDesc":"Standish-Sterling Schools"}
								  ],
				"printLegend"	: true,
				"opSlider"      : true,
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
        ]
	},
    {
		"id"			: "publicSafetyLayers",
		"name" 			: "Public Safety",
		"checkboxState"	: "unchecked",
		"images"		: [],
		"parent"		: true,
		"child"			: false,
		"childLayers"	: [
            {
                "id"            : "fireStationsLayer",
                "name"          : "Fire Stations",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/bayFireStationLegend.png", "imageDesc":"Fire Station"}],
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
            {
                "id"            : "fireDistrictsLayer",
                "name"          : "Fire Districts",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/bayFireDistLegend.png", "imageDesc":"Fire District"}],
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
            {
                "id"            : "emsDistrictsLayer",
                "name"          : "EMS Districts",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/bayEMSdistLegend.png", "imageDesc":"EMS District"}],
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
        ]
	},
    {
		"id"			: "votingLayers",
		"name" 			: "Voting",
		"checkboxState"	: "unchecked",
		"images"		: [],
		"parent"		: true,
		"child"			: false,
		"childLayers"	: [
            {
                "id"            : "pollingPlaceLayer",
                "name"          : "Polling Places",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/bayVotingPollPlace.png", "imageDesc":"Polling Place"}],
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
            {
                "id"            : "votingPrecinctLayer",
                "name"          : "Voting Precincts",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/bayVotingPreLegend.png", "imageDesc":"Voting Precinct"}],
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
        ]
	},
    {
        "id"            : "commDistrictsLayer",
        "name"          : "County Commissioner Districts",
        "checkboxState" : "unchecked",
        "images"        : [{"imageURL":"img/bayComishAndWardDistLegend.png", "imageDesc":"Commissioner District"}],
        "parent"		: false,
		"child"			: false,
		"childLayers"	: null
    },
    {
        "id"            : "districtsWardsLayer",
        "name"          : "City Commissioner Districts/Wards",
        "checkboxState" : "unchecked",
        "images"        : [{"imageURL":"img/bayWardLegend.png", "imageDesc":"Ward"}],
        "parent"		: false,
		"child"			: false,
		"childLayers"	: null
    },
    {
        "id"            : "municipalFacilitiesLayer",
        "name"          : "Municipal Facilities",
        "checkboxState" : "unchecked",
        "images"        : [{"imageURL":"img/darkMunicipal1.png", "imageDesc":"Municipal Facilities"}],
        "parent"		: false,
		"child"			: false,
		"childLayers"	: null
    },
    {
        "id"            : "municipalBoundaryLayer",
        "name"          : "Municipal Boundary",
        "checkboxState" : "checked",
        "images"        : [{"imageURL":"img/bayMunicipalBoundLegend.png", "imageDesc":"Municipal Boundary"}],
        "parent"		: false,
		"child"			: false,
		"childLayers"	: null
    },
    {
        "id"            : "sectionsLayer",
        "name"          : "Sections",
        "checkboxState" : "unchecked",
        "images"        : [{"imageURL":"img/baySectionsLegend.png", "imageDesc":"Section"}],
        "parent"		: false,
		"child"			: false,
		"childLayers"	: null
	},
    {
		"id"			: "zoningLayers",
		"name" 			: "Zoning",
		"checkboxState"	: "unchecked",
		"images"		: [],
		"childLayers"	: [
			{
				"id"			: "zoningLayer",
				"name" 			: "Bangor TWP Zoning",
				"checkboxState"	: "unchecked",
				"opSlider"		: true,
				"images"        : "dynamic",
			},
			{
				"id"			: "zoningBeaverLayer",
				"name" 			: "Beaver TWP Zoning",
				"checkboxState"	: "unchecked",
				"opSlider"		: true,
				"images"        : "dynamic",
			},
			{
				"id"			: "zoningHamptonLayer",
				"name" 			: "Hampton TWP Zoning",
				"checkboxState"	: "unchecked",
				"opSlider"		: true,
				"images"        : "dynamic",
			},
			{
				"id"			: "zoningKawkawlinLayer",
				"name" 			: "Kawkawlin TWP Zoning",
				"checkboxState"	: "unchecked",
				"opSlider"		: true,
				"images"        : "dynamic",
			},
        ]
	},

    {
		"id"			: "terrainLayers",
		"name" 			: "Terrain",
		"checkboxState"	: "unchecked",
		"images"		: [],
		"parent"		: true,
		"child"			: false,
		"childLayers"	: [
			{
				"id"			: "contoursVectorLayer",
				"name" 			: "Contours",
				"checkboxState"	: "unchecked",
				"images"        : [{"imageURL":"img/blackSolidLine.png", "imageDesc":"Contours"}],
			},
			{
				"id"            : "demLayer",
				"name"          : "Digital Elevation Model",
				"checkboxState" : "unchecked",
				"images"        : [{"imageURL":"img/genesee/demColorRamp.png", "imageDesc":"&nbsp", "colorRamp":"true"},
					{"imageURL":"img/colorRampBlankSpace.png", "imageDesc":"&nbsp Feet: 559-843"}
				],
				"opSlider"		: true,
			},
        ]
	},
    {
		"id"			: "trashPickupLayer",
		"name" 			: "Trash Pick-Up Day",
		"checkboxState"	: "unchecked",
		"images"		: "dynamic",
		"opSlider"		: true,
	},
	{
		"id"			: "sanSewerLayers",
		"name" 			: "Utilities",
		"checkboxState"	: "unchecked",
		"images"		: [],
		"privateLayer"	: true,		"childLayers"	: [
			{
				"id"			: "countyWaterMainsLayer",
				"name" 			: "County Water Mains",
				"checkboxState"	: "unchecked",
				"images"        : [
									{"imageURL":"img/EH/bayEH/waterMains.png", "imageDesc":"County Water Mains"},
									],
				"privateLayer"	: true,			},
			
			{
				"id"			: "sanSewersLayer",
				"name" 			: "Sanitary Sewers",
				"checkboxState"	: "unchecked",
				"images"        : [
									{"imageURL":"img/EH/bayEH/ss_forcemain.png", "imageDesc":"Force Main"},
									{"imageURL":"img/EH/bayEH/ss_gravity.png", "imageDesc":"Gravity Main"}
									],
				"privateLayer"	: true,			},
			
		]
	},
	{
		"id"			: "cityUtilitiesAvailableLayer",
		"name" 			: "City Utilities Available",
		"checkboxState"	: "unchecked",
		"images"		: [],
		"parent"		: true,
		"child"			: false,
		"childLayers"	: [
			{
				"id"			: "sanitarySewerServicePossibleLayer",
				"name" 			: "Sanitary Sewer Possible",
				"checkboxState"	: "unchecked",
				"images"        : "dynamic",
				"opSlider"		: true,
			},
			{
				"id"            : "waterServicePossibleLayer",
				"name"          : "Water Service Possible",
				"checkboxState" : "unchecked",
				"images"        : "dynamic",
				"opSlider"		: true,
			},
        ]
	},
	/*{
		"id"			: "utilityLayers",
		"name" 			: "Utilities",
		"checkboxState"	: "unchecked",
		"images"		: [],
		"parent"		: true,
		"child"			: false,
		"childLayers"	: [ 
            {
                "id"            : "hydrantsLayer",
                "name"          : "Hydrants",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/bayHydrants.png", "imageDesc":"Fire Hydrants"}],
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
            {
                "id"            : "sewerManholeLayer",
                "name"          : "Sewer Manhole",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/bayManholeCover.png", "imageDesc":"Sewer/Manhole Cover"}],
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
			{
                "id"            : "waterValveLayer",
                "name"          : "Water Valve",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/bayWaterValve.png", "imageDesc":"Water Valves"}],
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
			{
                "id"            : "catchBasinLayer",
                "name"          : "Catch Basin",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/bayCatchBasin.png", "imageDesc":"Catch Basins"}],
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
			{
                "id"            : "waterLineLayer",
                "name"          : "Water Line",
                "checkboxState" : "unchecked",
                "images"        : [{"imageURL":"img/bayWaterLineLegend.png", "imageDesc":"Water Line"}],
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            },
			{
                "id"            : "sewerLineLayer",
                "name"          : "Sewer Line",
                "checkboxState" : "unchecked",
                "images"        : [
									{"imageURL":"img/redLineLegend.png", "imageDesc":"Sewage Line"},
									{"imageURL":"img/yellowLineLegend.png", "imageDesc":"Storm Sewer Line"},
									{"imageURL":"img/greenLineLegend.png", "imageDesc":"Combined Sewer Line"}
								],
                "parent"		: false,
                "child"			: true,
                "childLayers"	: null
            }
        ]
	}*/
];
currentMap = 'bay';
theme = '';
const pfeEnabled = false;
const insightsEnabled = false;
const insightsPro = false;
const usePublicMap = false;
var hasPrivateLayers = true;var appLogo = './img/baySeal.png';
$("#perfectFormEditor").remove();$("#query-results-actions-bulk-email-button").remove();
var isMac = false;

// #################################################
// ####				Init Global Vars			####
// #################################################
// base64 images (for offline image swaps)
var gpsPointIconB64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABsAAAAbCAMAAAC6CgRnAAAACXBIWXMAAAsTAAALEwEAmpwYAAAKT2lDQ1BQaG90b3Nob3AgSUNDIHByb2ZpbGUAAHjanVNnVFPpFj333vRCS4iAlEtvUhUIIFJCi4AUkSYqIQkQSoghodkVUcERRUUEG8igiAOOjoCMFVEsDIoK2AfkIaKOg6OIisr74Xuja9a89+bN/rXXPues852zzwfACAyWSDNRNYAMqUIeEeCDx8TG4eQuQIEKJHAAEAizZCFz/SMBAPh+PDwrIsAHvgABeNMLCADATZvAMByH/w/qQplcAYCEAcB0kThLCIAUAEB6jkKmAEBGAYCdmCZTAKAEAGDLY2LjAFAtAGAnf+bTAICd+Jl7AQBblCEVAaCRACATZYhEAGg7AKzPVopFAFgwABRmS8Q5ANgtADBJV2ZIALC3AMDOEAuyAAgMADBRiIUpAAR7AGDIIyN4AISZABRG8lc88SuuEOcqAAB4mbI8uSQ5RYFbCC1xB1dXLh4ozkkXKxQ2YQJhmkAuwnmZGTKBNA/g88wAAKCRFRHgg/P9eM4Ors7ONo62Dl8t6r8G/yJiYuP+5c+rcEAAAOF0ftH+LC+zGoA7BoBt/qIl7gRoXgugdfeLZrIPQLUAoOnaV/Nw+H48PEWhkLnZ2eXk5NhKxEJbYcpXff5nwl/AV/1s+X48/Pf14L7iJIEyXYFHBPjgwsz0TKUcz5IJhGLc5o9H/LcL//wd0yLESWK5WCoU41EScY5EmozzMqUiiUKSKcUl0v9k4t8s+wM+3zUAsGo+AXuRLahdYwP2SycQWHTA4vcAAPK7b8HUKAgDgGiD4c93/+8//UegJQCAZkmScQAAXkQkLlTKsz/HCAAARKCBKrBBG/TBGCzABhzBBdzBC/xgNoRCJMTCQhBCCmSAHHJgKayCQiiGzbAdKmAv1EAdNMBRaIaTcA4uwlW4Dj1wD/phCJ7BKLyBCQRByAgTYSHaiAFiilgjjggXmYX4IcFIBBKLJCDJiBRRIkuRNUgxUopUIFVIHfI9cgI5h1xGupE7yAAygvyGvEcxlIGyUT3UDLVDuag3GoRGogvQZHQxmo8WoJvQcrQaPYw2oefQq2gP2o8+Q8cwwOgYBzPEbDAuxsNCsTgsCZNjy7EirAyrxhqwVqwDu4n1Y8+xdwQSgUXACTYEd0IgYR5BSFhMWE7YSKggHCQ0EdoJNwkDhFHCJyKTqEu0JroR+cQYYjIxh1hILCPWEo8TLxB7iEPENyQSiUMyJ7mQAkmxpFTSEtJG0m5SI+ksqZs0SBojk8naZGuyBzmULCAryIXkneTD5DPkG+Qh8lsKnWJAcaT4U+IoUspqShnlEOU05QZlmDJBVaOaUt2ooVQRNY9aQq2htlKvUYeoEzR1mjnNgxZJS6WtopXTGmgXaPdpr+h0uhHdlR5Ol9BX0svpR+iX6AP0dwwNhhWDx4hnKBmbGAcYZxl3GK+YTKYZ04sZx1QwNzHrmOeZD5lvVVgqtip8FZHKCpVKlSaVGyovVKmqpqreqgtV81XLVI+pXlN9rkZVM1PjqQnUlqtVqp1Q61MbU2epO6iHqmeob1Q/pH5Z/YkGWcNMw09DpFGgsV/jvMYgC2MZs3gsIWsNq4Z1gTXEJrHN2Xx2KruY/R27iz2qqaE5QzNKM1ezUvOUZj8H45hx+Jx0TgnnKKeX836K3hTvKeIpG6Y0TLkxZVxrqpaXllirSKtRq0frvTau7aedpr1Fu1n7gQ5Bx0onXCdHZ4/OBZ3nU9lT3acKpxZNPTr1ri6qa6UbobtEd79up+6Ynr5egJ5Mb6feeb3n+hx9L/1U/W36p/VHDFgGswwkBtsMzhg8xTVxbzwdL8fb8VFDXcNAQ6VhlWGX4YSRudE8o9VGjUYPjGnGXOMk423GbcajJgYmISZLTepN7ppSTbmmKaY7TDtMx83MzaLN1pk1mz0x1zLnm+eb15vft2BaeFostqi2uGVJsuRaplnutrxuhVo5WaVYVVpds0atna0l1rutu6cRp7lOk06rntZnw7Dxtsm2qbcZsOXYBtuutm22fWFnYhdnt8Wuw+6TvZN9un2N/T0HDYfZDqsdWh1+c7RyFDpWOt6azpzuP33F9JbpL2dYzxDP2DPjthPLKcRpnVOb00dnF2e5c4PziIuJS4LLLpc+Lpsbxt3IveRKdPVxXeF60vWdm7Obwu2o26/uNu5p7ofcn8w0nymeWTNz0MPIQ+BR5dE/C5+VMGvfrH5PQ0+BZ7XnIy9jL5FXrdewt6V3qvdh7xc+9j5yn+M+4zw33jLeWV/MN8C3yLfLT8Nvnl+F30N/I/9k/3r/0QCngCUBZwOJgUGBWwL7+Hp8Ib+OPzrbZfay2e1BjKC5QRVBj4KtguXBrSFoyOyQrSH355jOkc5pDoVQfujW0Adh5mGLw34MJ4WHhVeGP45wiFga0TGXNXfR3ENz30T6RJZE3ptnMU85ry1KNSo+qi5qPNo3ujS6P8YuZlnM1VidWElsSxw5LiquNm5svt/87fOH4p3iC+N7F5gvyF1weaHOwvSFpxapLhIsOpZATIhOOJTwQRAqqBaMJfITdyWOCnnCHcJnIi/RNtGI2ENcKh5O8kgqTXqS7JG8NXkkxTOlLOW5hCepkLxMDUzdmzqeFpp2IG0yPTq9MYOSkZBxQqohTZO2Z+pn5mZ2y6xlhbL+xW6Lty8elQfJa7OQrAVZLQq2QqboVFoo1yoHsmdlV2a/zYnKOZarnivN7cyzytuQN5zvn//tEsIS4ZK2pYZLVy0dWOa9rGo5sjxxedsK4xUFK4ZWBqw8uIq2Km3VT6vtV5eufr0mek1rgV7ByoLBtQFr6wtVCuWFfevc1+1dT1gvWd+1YfqGnRs+FYmKrhTbF5cVf9go3HjlG4dvyr+Z3JS0qavEuWTPZtJm6ebeLZ5bDpaql+aXDm4N2dq0Dd9WtO319kXbL5fNKNu7g7ZDuaO/PLi8ZafJzs07P1SkVPRU+lQ27tLdtWHX+G7R7ht7vPY07NXbW7z3/T7JvttVAVVN1WbVZftJ+7P3P66Jqun4lvttXa1ObXHtxwPSA/0HIw6217nU1R3SPVRSj9Yr60cOxx++/p3vdy0NNg1VjZzG4iNwRHnk6fcJ3/ceDTradox7rOEH0x92HWcdL2pCmvKaRptTmvtbYlu6T8w+0dbq3nr8R9sfD5w0PFl5SvNUyWna6YLTk2fyz4ydlZ19fi753GDborZ752PO32oPb++6EHTh0kX/i+c7vDvOXPK4dPKy2+UTV7hXmq86X23qdOo8/pPTT8e7nLuarrlca7nuer21e2b36RueN87d9L158Rb/1tWeOT3dvfN6b/fF9/XfFt1+cif9zsu72Xcn7q28T7xf9EDtQdlD3YfVP1v+3Njv3H9qwHeg89HcR/cGhYPP/pH1jw9DBY+Zj8uGDYbrnjg+OTniP3L96fynQ89kzyaeF/6i/suuFxYvfvjV69fO0ZjRoZfyl5O/bXyl/erA6xmv28bCxh6+yXgzMV70VvvtwXfcdx3vo98PT+R8IH8o/2j5sfVT0Kf7kxmTk/8EA5jz/GMzLdsAAAAgY0hSTQAAeiUAAICDAAD5/wAAgOkAAHUwAADqYAAAOpgAABdvkl/FRgAAAPBQTFRFAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQEBAAAAAAAAAAAAAgICBQUFIiIiIiIiJycnNDQzNTQ0NjU1Ozs7PDw8eXZ0fXp5rKmmrKmnqaOgqaOgq6elr6upramn1M3J1M3J6ubk7urn7+vn//v5//z6//v4//z5AHK/AHXAAHjBAHnCAHrCDIDFDYHFEYHFFYLFWKjYWajYoNDsodDsu+D2wuL13vP+3vP/4vP84vP97Pr/7Pv/MydqXgAAADt0Uk5TAAECAwQFBgcJCwwNDxARExUXGBkaGxwdHh8gISIjJCYmPj8/TE1NUVSFhaamp6ioqaq1ttvd3fPz9PXZHl43AAABWklEQVQoz32S2XKCQBREgQEGGBBE2VwTNW6JS+IeFveoMcr//01GEeRBuU+36tSt6eluggiGJEmKAgBQFN6I2FwAoBkWQsgyNIjjC6JZTkCiKCKBY+k7xAsmKKWoWlZTlRTCNIQkCSAvqYapF14KummoEg9BwPAV5GXNKtXbna9Ou160NJmH9BWSgOVlw6r2p+f9z96ffFQtQ+ZZgBmWwUlarjn83S5dx11uj8NmTpM4LOhyhtJWZXxY267nea69PoxfrTS6HJI0lzKKvdPKmQfjrP4+S0aKo0mCYgTFrM029jwce/P9ZioCQxEUizJ6y1+4EXMXfkvPIJYiABSz+e7e9SLmuftuPitCcGXlwc6JMWc3KEfs2V3Se490zmqBzgf/O/WKwf9uvoxivowqoS+Bn43hMfKzEfoZ5fA+8XEO52n/nkOUX/GWXymWX1LuiX1J6lliP5/1+h90KkgRmr6FNgAAAABJRU5ErkJggg==';

//sniffer
if(sniffer || isPHapp || isFEHapp){
	$('head').append('<style>.titlePane .title{padding-right: 60px;}</style>'); // sniffer specific css (makes room for pagination buttons)
    //$('#showDetails').hide();//parcel popup show details link
    //$('#dataPaneExpando').hide();
	$('#mapSwitcher').remove();
	$('#mapSwitcherAlt').parent().remove();
    //$('#dataPaneHandle').hide();
    $('#bufferToolsHeading, #bufferToolsAccordion').addClass('hidden');
}

// Categories of functions (will be used to isolate code)
/*
1. Platform
2. offline tiles
3. offline features
4. feature creation/editing
5. drawing/measuring/misc tools
6. search
7. gps
8. print
9. help
10. parcel related
11. fee access
*/

//1. PLATFORM GLOBALS
	var globProtocol=location.protocol; // http or https
	var hostnameStr = window.location.hostname, // get hostname and set var for queries
		hostname = globProtocol +"//fetchgis.com/",

		// Set Viewport width and height vars and set event handler to keep up to date
		viewportWidth = $(window).width(), 
		viewportHeight = $(window).height(),
		
		// Init page vars. These can be overwritten on page load by any values passed in by the url string.
		selectedCounty, 
		activeControl = "layerControls", // set in function 'activateControl'
		featureEditControlsActiveOnLoad = false,
		insightsControlsActiveOnLoad = false,
		paramString = '', activeLayers = '', partialLayerGroups = '', pageOrientation = "landscape", pageSize = "letter", pageTitle = "", subTitle = "", northArrow = 1, reportPIN = '0', reportLayer = "", reportShowMap = '1', reportShowPic = '1', reportShowTax = '1', buff64 = undefined, data64 = "", soilPin="", /*isaParkParam="",*/ opacity64 = "", printLegendLayers = "", switchingMaps = 'false' , dOff = "", openAsPdf = false,
		centerCoords, centerLngLat = [], currentPin = "", currentParcelLayer = "", 
		
		// and the rest of the core vars
		generateReportPdF,
		pdfReady = 0,
		cancelDrawingRunning = false, // global to determine if cancel drawing selection is still running
		params = {},
		navCollapseHandler = "",
		insideBoundingPolygon = false,
		map_x, map_y,
		allLayersInfoTemplatesArr = {},
		swipeLayerActive = false;
		layerDescriptionArr = [];
		
		// determine if is embeded map - im leaving this in globals for now in case it needs to // be used throughout the rest of the code, if not the function where its used is around // line 1735 or so in main.js
		var isEmbededMap = window.top !== window.self;

	// These are map/dojo variables for the platform
	var map, popup, minimapLayer, navToolbar, wildcardInfoTemplate, scaleBar, userGraphicsLayer, offlineTileDLTempGfxLayer, highlightLayer, geoLocationLayer, parperpTempLayer, userLinesLayer, parperpHighlightLayer , zoomToAddress;


//2. OFFLINE TILE GLOBALS


//3. OFFLINE FEATURE GLOBALS

//4. FEATURE EDITING/CREATION GLOBALS

	var templateLayers, 
		editableFeatureLayers,
		snapshotLayers = [],
		attInspector,
		editGraphic,
		ehSepticLateralsController,
		backupEditGraphic,
		ehInfoTemplate,
		getFeatureEditTitle,
		getFeatureEditContent,
		layerInfos4EH,
		offlineEdit,
		loadedFeatureLayers = [],
		editableLayersAdded = false,
		layerOrderArray = [],
		updateFeature,
		saveStuff,
		templatePicker,
		cancelFeatureEditing,
		jtok,
		deniedLayers = [],
		isDrawEndFeature = false, //feature creator
		editableFeaturesBoundary,
		cameraDetected = false
	;

//5. DRAWING/MEASURING/MISC TOOL GLOBALS
var cancelDrawingSelection, 
	buffData = [], 
	bufferGeometrySelect = [],
	bufferParcels, 
	bufferedGeometries,
	restoreBase64Graphics, 
	drawDistanceHandler1, 
	drawDistanceHandler2, 
	selectionHandler,
	measureSelectionHandler,
	templateMoveHandler,
	rectangleClickHandler,
	parHoverOverHandler,
	parHoverOffHandler,
	parExtentChangeHandler,
	parClickHandler,
	parUserGraphHandler,
	parUserMoveHandler,
    ninetyClickStage1,
    ninetyClickStage3,
    ninetyZoomHandler,
    ninetyMouseMove,
	hoverOnHandler, 
	hoverOffHandler, 
	enableGraphicHover,
	selectedGeometry = [],
	setDrawColorOption,
	measurementLayer,
	measurementLabelsLayer,
	cancelMeasurement,
	textObj2edit,
	editTextColorPicker,
	cancelParperp, 
	disableAutoSnap, 
	cancelDrawingWithSnap,
	nextButtonHandler, 
	showLineDirectionHandler, 
	showPolyDirectionHandler, 
	measureDistanceUnitHandler, 
	parperpHandlerActive, 
	rectangleHandlerActive, 
	snappingActive, 
	editSnappingActive,
	ninetyHandlersActive,
	swipeWidget, 
	swipeLayers = [],  
	toggleSwipe;

//cogo
var changeVerticeHandler, editSnappingHandler, ensureSolidSnap, startMouseCorrectionHandler, mouseCorrectionHandler;

// proj4js definition globals
var webDef = new Proj4js.Proj('GOOGLE'), 
	stateDef = new Proj4js.Proj('EPSG:2253'), 
	wgs84Def = new Proj4js.Proj('EPSG:4326'),
	statePlaneCode = 2253;


//6. SEARCH GLOBALS
var queryIntersectCount = 0, 
	queryAddressCount = 0, 
	vetGraphic;


//7. GEOLOCATION GLOBALS (gps)
var locGraphic, accuracyGraphic, currentPosition;


//8. PRINT
var destroyReport;

//9. HELP


//10. PARCEL VIEWER RELATED GLOBALS
//variable for holding the parcel and parcel Graphic that a user clicks on.
var bayParcelLayer;
var infoWinParcel, infoWinGraphic, getParcelData, searchData, popupData = [], defaultParcelTemplate, parcelLayers, getPopupGraphic;
var getUserSearchDataNewPage, getLayerSearchNewPage;
var loadReport = false, loadSalesReport = false, salesQuery = {}, loadOandMReport = false, loadQIReport = false, loadDowReport = false, getPopupData;
var xhr = (window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP")); // for PARCEL popups

//11. FEE ACCESS
var userProfileName, isMasterUser, userCreditsLeft, showAddCredits, useCredits, isFreeUser, populateFeeServicePages, usersName, usersEmail;

var infoWindowHide, infoWindowShow, XHRhandler, populateUserInfo;

//12. JAYS
var pickmeupDateRange=[0,0];
var compDateRange ='';

// Snapping

var snappingLayerInfos = undefined;

//user permissions
var formDesignerPermission = false;
var canWrite = false;
var editDays = -1;


var userSignature;
var codeBooks = {};
var questionLists = {};
var isSFV = false;
var insightsOpenFeature = null;
var formViewer, reportManager, dashboardManager, commManager, queryWidgetHolder = {};
let textLibraryLookup = {};

var wmsVisibleLayers = [];

const mapState = "mi";
// #################################################
// ####				Init Map Vars   			####
// #################################################
// outputs mapSettingsFromDB for foodmaps, also deniedLayer for all editing maps
var mapSettingsFromDB = null;
deniedLayers = null;
var userList = null;

// #################################################
// ####			 Init Startup Functions			####
// #################################################
// This initializes things like recently viewed parcels, moving control/data pane handles to starting positions, url driven statefullness, and helper functions
// #################################################
// ####			Get layer desciptions 			####
// #################################################

$(function(){
	// ajax call to description retrieval service
    var postData = {
		map: currentMap
	};

	$.ajax({
        url: "ws/sso/getLayerDescriptions.php",
        method: "POST",
        data: postData,
		async: true,
		success: function(response){
            try{
				if(response.status == "success"){
					//setLayerDefDescription(response.data);
					window.layerDescriptionArr = response.data;
				}
            } catch(err){
                console.log('Error, no data returned from getLayerDescriptions: ' + err.message);
            }
		},
		error: function(err){
			console.log('Error, no data returned from getLayerDescriptions: ' + err);
		}
	});
});


// recently viewed parcels local storage variable
if(typeof(localStorage['recentList']) == 'undefined'){
	localStorage['recentList'] = JSON.stringify([]);
}

// #################################################
// ####				URL Functions 				####
// #################################################
// Get url params for JS
if (location.search) {
    var parts = location.search.substring(1).split('&');

    for (var i = 0; i < parts.length; i++) {
        var nv = parts[i].split('=');
        if (!nv[0]) continue;
        params[nv[0]] = nv[1] || true;
    }
}
if(typeof(params.rptPIN) !== "undefined"){
	if(params.rptPIN !== "0"){
		currentPin = LZString.decompressFromEncodedURIComponent(params.rptPIN);
		currentLayer = LZString.decompressFromEncodedURIComponent(params.rptLayer);
	}
}

if(params.rptPIN === '0'){
	var html2pdf = {
	 header: {
	  height: "0cm",
	  contents: 'blah'
	 },
	 footer: {
	  height: "0cm",
	  contents: 'blah'
	  }
	};
} else {
	var html2pdf = {
	 header: {
	  height: "0mm",
	  contents: 'hello'
	 },
	 footer: {
	  height: "6mm",
	  contents: '<div class="left">' + currentPin + '</div><div class="right">page {{pageNumber}} of {{totalPages}}</div>'
	  }
	};
}


var swReloadURL = window.location.origin; // this is for reloading the app. The url params must be stripped away for the service worker to function
swReloadURL += window.location.pathname + "?currentMap=" + params.currentMap;

function updateURLParams(){
	
	// url params of interest: zoom, center lat/lng, mode(active control), active layers (later), pageOrientation, pageSize, generate Pdf (later), custom shapes(an array of geo json or whatever).
	// maybe at some point, local storage could be used for history by storing multiple urls.
	paramString = ''; // wipe out the param string variable
	
	centerCoords = map.extent.getCenter();
	try{
		centerLngLat = webMercatorUtils.xyToLngLat(centerCoords.x, centerCoords.y);
	}catch(err){
		console.log(err.message);
	}

	paramString += 'currentMap=' + currentMap; // currentMap variable comes from beginning of variables.php
		(dOff !== '') ? paramString += '&dOff=' + dOff: false; // when set to 1 the disclaimer will not show
	if(usePublicMap) paramString += '&usePublicMap=yes'; // this url is visited via the public
	paramString += '&switchingMaps=' + switchingMaps; // used for going to a different map. The map will zoom to the extent of the new map on load
	paramString += '&centerLng=' + centerLngLat[0] + '&centerLat=' + centerLngLat[1]; // used to track the position of the map
	paramString += '&mapZoom=' + map.getZoom(); // used to track the zoom of the map
	paramString += '&pageSize=' + pageSize; // used to set the size of the printed map
	paramString += '&pageOrientation=' + pageOrientation; // used to set the printable map as portrait or landscape
	paramString += '&pageTitle=' +  $("#printTitleInput").val(); // page title for printed maps
	paramString += '&subTitle=' +  $("#subTitleInput").val(); // sub title for printed maps
	paramString += '&northArrow=' + northArrow;
    			paramString += '&rptPIN=' + reportPIN;		// pin number for making report PDF (function runs if not set to 0 (binary false))
	paramString += '&rptLayer=' + reportLayer; 	// parcel layer for report generation
	paramString += '&rptMap=' + reportShowMap;	// rptXXX are binary boolean values (0 or 1)
	paramString += '&rptPic=' + reportShowPic;
	paramString += '&rptTax=' + reportShowTax;
	paramString += '&activeControl=' + activeControl; // used to track which control was last open
	paramString += '&activeLayers=' + activeLayers; // used to track which layers are visible
	paramString += '&partialLayerGroups=' + partialLayerGroups; // used to track which layers are visible
	paramString += '&opacity64=' + opacity64;
	//paramString += '&pdf=' + openAsPdf; // This isn't always there... only for pdf...

	
	paramString += '&printLegendLayers=' + printLegendLayers;
	try { // data64 was moved out of the url and into session storage, but we still use this function to update
		if(data64 != undefined){
			sessionStorage.setItem('data64', data64);
		}
	} catch(err){
		console.log('error: ' + err.message);
	}
	
	history.pushState({},'', window.location.pathname + '?' + paramString);

	// build PDF link
	var printURL = window.location.origin+":8080/?url=";
	printURL += encodeURIComponent(window.location.href) + "%26pdf%3D1"; // this mess appended at the end assigns a 1 to pdf url param
	
	window.loadSitePlan = false;
		
	// fill in date element on map print 
	$("#sysDate").html(getSysDate() + ' ' + getSysTime());
	
	if ($('#paperOrientationPortrait').hasClass('fa-dot-circle-o')){
		printURL += '&orientation=portrait';
	} else {
		printURL += '&orientation=landscape';
	}

	if ($('#paperSizeLegal').hasClass('fa-dot-circle-o')){
		printURL += '&format=Legal';
	} else {
		printURL += '&format=Letter';
	}
	printURL += '&zoom=1';
	printURL += '&margin=1cm';
	
	$('#pdfPrint').attr('href', printURL);
	
}

var shareDataReturned = false;
function setURLbasedVars(){
	// first, find out of app was loaded from a share link
	if(typeof(params.data64code) !== "undefined"){
		//console.log("data64code: " + params.data64code);
		
		// *********************************************
		$.ajax({
			type: "GET",
			url: 'https://link.fetchgis.com/getData.php',
			data: {"id": params.data64code},
			crossDomain: true,
			success: function(data){
				//console.log("data64 from share service:");
				//console.log(data);
				data64 = data.data64;
				sessionStorage.setItem('data64', data64);
				shareDataReturned = true;
			}, 
			error: function(){
				showAlert('Notice', 'Nothing returned from data64 link share.');
			},
			complete: function(){
				$('#share').removeClass('disabledButton');
			}
		});
		
		// *********************************************
	}
	
	
	if(params.activeControl != undefined){
		activeControl = params.activeControl;
	}
	//center = map.extent.getCenter();
	//centerLngLat = esri.geometry.xyToLngLat(center.x, center.y);
	centerLngLat[0] = params.centerLng;
	centerLngLat[1] = params.centerLat;
	activeLayers = params.activeLayers;
	partialLayerGroups = params.partialLayerGroups;
	//buff64 = params.buff64; // this is in data64 now
	data64 = sessionStorage.getItem('data64', data64); // this is no longer in url
	if(params.rptPIN !== '0' && params.rptPIN !== undefined){
		loadReport = true;
	}
	if(params.pdf === "1"){
		pdfReady = 1;
		console.log("pdfReady = 1");
	}
	

	window.firePDFReadyWithTimeout = function(timeout){
		if(typeof(timeout) !== 'number'){
			timeout = 500
		}
		// this is just here to give fade-in animations a little more time
		setTimeout(function(){
			pdfReady = 2;
			console.log('pdfReady = 2');
		}, timeout);
	}

			}
setURLbasedVars();

// set selected county
/*if(params.currentMap != undefined){
	selectedCounty = params.currentMap;
	if(selectedCounty == "midEH" || selectedCounty == 'demoEH' || selectedCounty == 'devEH' || selectedCounty == 'fgdemoEH'){
		selectedCounty = 'midland';
	} else if (selectedCounty == "sagEH" || selectedCounty == "sagRC"){
        selectedCounty = 'saginaw';
    }else if (selectedCounty == "bayEH" || selectedCounty == "bay911" || selectedCounty == "bayRC"){
        selectedCounty = 'bay';
    }else if (selectedCounty == "mmdEH"){
        selectedCounty = 'gratiot';
    }
} else {
	selectedCounty = 'bay';
}
*/

// This function makes the bootstrap navbar close when the user clicks the map
$('#navbar').on('show.bs.collapse', function () {
  navCollapseHandler = $('#contentContainer').on("click", function(){
	  $('.navbar-toggle').trigger('click');
  });
});
$('#navbar').on('hide.bs.collapse', function () {
  navCollapseHandler = $('#contentContainer').off('click');
});

// #################################################
// ####			Set logo and county name		####
// #################################################


$('.mini-logo, #printSeal').attr('src', './img/baySeal.png');
$('.navbar-brand').html('Bay Area GIS');
$('.navbar-brand').attr('href', 'http://www.baycounty-mi.gov/GIS/');
	

	
// #################################################
// ####				Init Helpers 				####
// #################################################

	var getTocName = function(layer_id, def_array){

		if(!def_array) def_array = layerDefs;

		for(var i=0; i<def_array.length; i++){
			var layerDef = def_array[i];
			if(layerDef.id == layer_id){
				return layerDef.name;
			}
			if(layerDef.childLayers){
				var res = getTocName(layer_id, layerDef.childLayers);
				if(res != false){
					return res;
				}
			}
		}
		return false;
	}


    var getFieldAlias = function(layer, field){
        for(var i=0;i<layer.fields.length; i++){
            var fieldInfo = layer.fields[i];
            if(fieldInfo.name == field){
                return fieldInfo.alias || fieldInfo.name;
            }
        }

        return field;
    }


    var getFieldType = function(layer, field){
        for(var i=0; i<layer.fields.length; i++){
            var fieldInfo = layer.fields[i];
            if(fieldInfo.name == field){
                return fieldInfo.type;
            }
		}
		return false;
    }

function toggleLayerMouseEvents(activate, ignoreMouseEvents){ // toggles all mouse events including clicking (tmeplates)

	if(!this.template_store){
		this.template_store = {};
	}
	
	for(var i=0; i<map.graphicsLayerIds.length; i++){
		var layer = window[map.graphicsLayerIds[i]];
		try {
			if(activate != true){
				popup.hide();
				popup.clearFeatures();
				this.template_store[layer.id] = layer.infoTemplate;
				layer.setInfoTemplate(null);
				if(ignoreMouseEvents != true){
					layer.disableMouseEvents();
				}
			} else {
				var template = this.template_store[layer.id];
				if(template) layer.setInfoTemplate(template);
				if(ignoreMouseEvents != true){
					layer.enableMouseEvents();
				}
			}
		} catch(e) {}
	}
}


// rounding helper

function roundTo(n, digits) {
	if(n % 1 == 0) return n;

    var negative = false;
    if (digits === undefined) {
        digits = 0;
    }
        if( n < 0) {
        negative = true;
      n = n * -1;
    }
    var multiplicator = Math.pow(10, digits);
    n = parseFloat((n * multiplicator).toFixed(11));
    n = (Math.round(n) / multiplicator).toFixed(2);
    if( negative ) {    
        n = (n * -1).toFixed(2);
    }
    return n;
}


// Helper function to make xml into a JSON style object
function XML2jsobj(node) {
	var	data = {};
	// append a value
	function Add(name, value) {
		if (data[name]) {
			if (data[name].constructor != Array) {
				data[name] = [data[name]];
			}
			data[name][data[name].length] = value;
		}
		else {
			data[name] = value;
		}
	}
	// element attributes
	var c, cn;
	for (c = 0; cn = node.attributes[c]; c++) {
		Add(cn.name, cn.value);
	}
	// child elements
	for (c = 0; cn = node.childNodes[c]; c++) {
		if (cn.nodeType == 1) {
			if (cn.childNodes.length == 1 && cn.firstChild.nodeType == 3) {
				// text value
				Add(cn.nodeName, cn.firstChild.nodeValue);
			}
			else {
				// sub-object
				Add(cn.nodeName, XML2jsobj(cn));
			}
		}
	}
	return data;
}

function getSysDate(){
	// Get date in xx/xx/xxxx format
	var today = new Date();
	var dd = today.getDate();
	var mm = today.getMonth()+1;
	var yyyy = today.getFullYear();

	if(dd<10) {
		dd='0'+dd;
	} 

	if(mm<10) {
		mm='0'+mm;
	} 

	today = mm+'/'+dd+'/'+yyyy;
	return today;
	
}

function getSysTime() {
	var currentTime = new Date();
	var hours = currentTime.getHours();
	var morn;
	if(hours>11){
		morn = 'PM';
		if(hours > 12){
			hours = hours - 12;
		}
	} else {
		morn = 'AM';
		if(hours[0] == '0'){
			if(hours == '00'){
				hours = 12;
			} else {
				// removes leading zero
				hours = hours * 1;
			}
		}
	}

	var minutes = currentTime.getMinutes();
	var seconds = currentTime.getSeconds();
	if (minutes < 10){
		minutes = "0" + minutes;
	}
	if (seconds < 10){
		seconds = "0" + seconds;
	}
	var time = hours + ":" + minutes + " ";
	
	time += morn;
	return time;
}

function setjtok(layersArr,jtok){

	for(var i = 0; i < layersArr.length; i++){

		if(layersArr[i].currentMap === currentMap){
			layersArr[i].jtok=jtok;
		} else {

		}

	}
}


function abbreviateNumber(num,fixed) {
	if(!fixed) fixed = 0;

    if (num === null) { return null; } // terminate early
	num  = parseFloat(num);
  if (num === 0) { return '0'; } // terminate early
    fixed=parseInt(fixed);
  fixed = (!fixed || fixed < 0) ? 0 : fixed; // number of decimal places to show
  var b = (num).toPrecision(2).split("e"), // get power
      k = b.length === 1 ? 0 : Math.floor(Math.min(b[1].slice(1), 14) / 3), // floor at decimals, ceiling at trillions
      c = k < 1 ? num.toFixed(0 + fixed) : (num / Math.pow(10, k * 3) ).toFixed(1 + fixed), // divide by power
      d = c < 0 ? c : Math.abs(c), // enforce -0 is 0
      e = d + ['', 'K', 'M', 'B', 'T'][k]; // append power
  return e;
}


function getAbbreviation(inputString){
	if(typeof(inputString) === "undefined"){
		return "---";
	}
	var wordsArr = inputString.split(" ");
	var acronym = "";
	for(var i = 0; i < wordsArr.length; i++){
		var lwrCase = wordsArr[i].toLowerCase();
		if(lwrCase !== "and" && lwrCase !== "or" && lwrCase !== "the" && lwrCase !== "of" && lwrCase !== "to" && lwrCase !== "from" && lwrCase !== "by"){
			acronym += wordsArr[i].charAt(0);
		}
	}
	if(acronym.length > 1){
		return acronym;
	} else {
		// try same logic above, but this time looking for underscores
		wordsArr = inputString.split("_");

		acronym = ""; // wipe this because the loop above adds first letter no matter what
		for(var i = 0; i < wordsArr.length; i++){
			var lwrCase = wordsArr[i].toLowerCase();
			if(lwrCase !== "and" && lwrCase !== "or" && lwrCase !== "the" && lwrCase !== "of" && lwrCase !== "to" && lwrCase !== "from" && lwrCase !== "by"){
				acronym += wordsArr[i].charAt(0);
			}
		}
		if(acronym.length > 1){
			return acronym;
		} else {
			return inputString;
		}
	}
	
}

function removeSpecialChars(strVal, replacementChar){
	if(typeof(replacementChar) === "undefined"){
		replacementChar = '';
	}
	return strVal.replace(/[^a-zA-Z 0-9]+/g, replacementChar);
}

var popupDisabled = false;
// these two functions disable/enable popups safely by keeping record of the infoTemplates for every layer
function enablePopup(){
	// don't run this for nothing
	if(popupDisabled == true){
		var allMapLayerIds = map.layerIds.concat(map.graphicsLayerIds);
		// reset info templates for all layers...
		for(var i = 0; i < allMapLayerIds.length; i++){
			try{
				var layer = map.getLayer(allMapLayerIds[i]);
				if(typeof layer.infoTemplate !="undefined") {//graphic layers
					layer.setInfoTemplate(allLayersInfoTemplatesArr[allMapLayerIds[i]]);
				}
				if(typeof layer.infoTemplates !="undefined") {//dynamic maps
					layer.setInfoTemplates(allLayersInfoTemplatesArr[allMapLayerIds[i]]);
				}
				
										
			}catch(e){

			}
		}
		
		if(typeof editableFeatureLayers !="undefined"){
			try{
				// ... but then wipe out the info templates on isolation radius layers
				for(var i = 0; i < editableFeatureLayers.length; i++){
					if(editableFeatureLayers[i].radiusLayerId){
						window[editableFeatureLayers[i].radiusLayerId].setInfoTemplate(null);
					}
				}
			}catch(e){

			}
		}
		
		popupDisabled = false;
	}

}

function disablePopup(){
	// record and clear info templates for all layers... (no need to clear the array, this will overwrite all values)
	if(popupDisabled == false){ // make sure stuff's not already disabled... otherwise you'll wipe out all info templates on the map, requiring a restart
		popupDisabled = true;
		var allMapLayerIds = map.layerIds.concat(map.graphicsLayerIds);
		for(var i = 0; i < allMapLayerIds.length; i++){
			try{
				var layer=map.getLayer(allMapLayerIds[i]);
				if(typeof layer.infoTemplate !="undefined") {
					allLayersInfoTemplatesArr[allMapLayerIds[i]] = layer.infoTemplate;//graphic layers
					layer.setInfoTemplate(null);
				}
				if(typeof layer.infoTemplates !="undefined") {
					allLayersInfoTemplatesArr[allMapLayerIds[i]] = layer.infoTemplates;//dynamic maps
					layer.setInfoTemplates(null);
				}			
				
			}catch(e){

			}
			
		}		
	}

}

function disableToolSelection(hidePane){ //if hidePane(true) is passed in don't hide pane
	if(!hidePane){
		hideDataPane();
	}
	$('#toolButtons, #dataPaneHandle, #navToolbar, .controlsHeading, .createRelRecord, .editRelRecord').addClass('disabledButton');
}

function enableToolSelection(){
	$('#toolButtons, #dataPaneHandle, #navToolbar, .controlsHeading, .createRelRecord, .editRelRecord').removeClass('disabledButton');
}

// sorting and searching functions (makeSearchable and sortArray)
	
makeSearchable = function(selector, extraClass, options, callback) {//pass selector, like '.here', of existing div, and class, like 'newClass', to be applied to new search div
    var div = $(selector); 							// will place new search div immediately before existing div
    div.before('<div class="madeSearchableQuery ' + extraClass + '"><input type="text" class="searchQuery" placeholder="search for.."><span class="clearSearchableQuery" title="Clear"><i class="fa fa-times" aria-hidden="true"></i></span></div>');
    var search = div.prev().find('.searchQuery');
    var searchClear = div.prev().find('.clearSearchableQuery');
    var timeout = null;
    var options = $.extend({}, options); //store passed in options
    $(search).on('keyup.searchable', function(e){
        clearTimeout(timeout); //we are going to implement a time delay of 600 ms to give time for the user to stop typing
        timeout = setTimeout(function () {
            div.fadeOut('fast', function(){ //hide cards to process search
                if(e.target.value.length > 0){ //manage clear button
                    searchClear.css({'opacity': '1', 'pointer-events': 'all'});
                } else {
                    searchClear.css({'opacity': '0', 'pointer-events': 'none'});
                }
                var isGuided = false;
                if($('#guidedDiv').length > 0){
                    isGuided = true;
                }
                if(options.searchType == 'card'){
                    div.children().each(function(i2){ // then are individual cards
                        if(e.target.value == ''){ //show everything
                            if(options.clearType == 'hideAll' && !isGuided){ //if guided, we want to show all upon clearing
                                $(this).addClass('hidden')
                            } else {
                                $(this).removeClass('hidden')
                            }
                        } else { //else we have a search term
                            var isFound = searchFunc($(this)[0].className, $(this)[0].innerHTML.toLowerCase(), e.target.value.toLowerCase().split(' '));
                            if(isFound){
                                $(this).removeClass('hidden'); //found match
                            } else {
                                $(this).addClass('hidden'); //found match
                            }
                        }
                    })
                    $("#dataDictionaryWrapper").unmark(); //remove any previous highlighting
                    if(e.target.value != ''){ //if we have a term
                        let words = e.target.value.split(/ +(?=[\w]+\:)/g); //split by quoted segments, then by individual words not in quotes
                        for(w of words){
                            var markOptions = {
                                className: "searchHighlight",
                                filter: function(node, term, totalCount, counter){
                                    $(node.parentNode).parents('.card').removeClass('hidden'); //make sure card is visible
                                    return true;
                                }
                            };
                            if(w[0] == '"' && w[w.length-1] == '"'){ //if starting and ending in quotes
                                markOptions.accuracy = { //we want the exact string that is enclosed in quotes
                                    value: "exactly",
                                    limiters: [",", ".", "-"]
                                };
                                markOptions.separateWordSearch = false; //words together
                                w = w.slice(1,w.length-1); //remove quotes to search
                            }
                            $("#dataDictionaryWrapper").mark(w, markOptions);
                        }
                    }
                } else if(options.searchType == 'table'){
                    $(this).find('tr').each(function(){
                        if($(this)[0].innerHTML.indexOf('<th') == -1){ //don't touch the table headers
                            if(e.target.value == ''){ //clearing the table
                                if(options.clearType == 'hideAll'){ //hide everything
                                    $(this).addClass('hidden')
                                } else { //show everything
                                    $(this).removeClass('hidden')
                                }
                            } else {
                                var isFound = searchFunc($(this)[0].className, $(this)[0].innerText.toLowerCase(), e.target.value.toLowerCase().split(' '));
                                if(isFound){
                                    $(this).removeClass('hidden'); //found match
                                } else {
                                    $(this).addClass('hidden'); //found match
                                }
                            }
                        }
                    })
                } else {
                    $(this).children().each(function(index){ //just search through divs
                        if(e.target.value == ''){ //show everything
                            $(this).removeClass('hidden')
                        } else {
                            var isFound;
                            if(options.searchHTML){
                                isFound = searchFunc($(this)[0].className, $(this).html().toLowerCase(), e.target.value.toLowerCase().split(' '));
                            } else {
                                isFound = searchFunc($(this)[0].className, $(this)[0].innerText.toLowerCase(), e.target.value.toLowerCase().split(' '));
                            }
                            if(isFound){
                                $(this).removeClass('hidden'); //found match
                                $(this).prevAll('.dropdownOptionHeader').first().removeClass('hidden'); //only show the header for the shown values
                            } else {
                                $(this).addClass('hidden'); //found match
                            }
                        }
                    })
                    if(options.mark){
                        $(this).unmark();
                        if(e.target.value != ''){ //if we have a term
                            let words = e.target.value.split(/ +(?=[\w]+\:)/g); //split by quoted segments, then by individual words not in quotes
                            for(w of words){
                                var markOptions = {
                                    className: "searchHighlight",
                                    filter: function(node, term, totalCount, counter){
                                        $(node.parentNode).parents('.hidden').removeClass('hidden'); //make sure card is visible
                                        return true;
                                    }
                                };
                                if(w[0] == '"' && w[w.length-1] == '"'){ //if starting and ending in quotes
                                    markOptions.accuracy = { //we want the exact string that is enclosed in quotes
                                        value: "exactly",
                                        limiters: [",", ".", "-"]
                                    };
                                    markOptions.separateWordSearch = false; //words together
                                    w = w.slice(1,w.length-1); //remove quotes to search
                                }
                                $(this).mark(w, markOptions);
                            }
                        }
                    }
                }
                div.fadeIn(100, function(){ //after fades in perform div moving and update scrollbar
                    if(options.searchType == 'card'){
                        if(callback){
                            callback();
                        }
                    }
                    if($(selector).parent().hasClass('ps')){
                        $(selector).parent().perfectScrollbar('update');
                    } else {
                        $(selector).parent().scrollTop(0);
                    }
                });
                        
            })
            function searchFunc(className, textString, searchTerms){
                var foundMatches = 0;
                var foundAll = false; //did we find all search terms
                if(className.indexOf('alwaysShow') > -1){ //if always show then no need to search
                    foundAll = true;
                } else {
                    searchTerms.forEach(function(term){ //each search term
                        if(textString.indexOf(term) > -1){ //is term in text
                            foundMatches++; //found one
                        }
                    })
                    if(foundMatches == searchTerms.length){ //if we found all terms in text
                        foundAll = true; //return tue
                    }
                }
                return foundAll;
            }
        }, 600);
    })
    $(searchClear).on("click", function(){ //init search clear
        $(search).val('');
        $(search).trigger('keyup.searchable');
    })
}

// function to sort an array of objects in place. Returns nothing.
// example usage: sortArrayOfObjects(ehPermitLayer.relationships, "name");
function sortArrayOfObjects(arr, sortField) {

    if(arr && typeof(arr) == 'object' && Array.isArray(arr) && arr.length > 0){
        if(typeof(arr[0][sortField]) === "number"){
            // sort by value
            arr.sort(function (a, b) {
            return a.sortField - b[sortField];
            });
        }

        if(typeof(arr[0][sortField]) === "string"){
            // sort by name
            arr.sort(function(a, b) {
            var nameA = a[sortField].toUpperCase(); // ignore upper and lowercase
            var nameB = b[sortField].toUpperCase(); // ignore upper and lowercase
            if (nameA < nameB) {
                return -1;
            }
            if (nameA > nameB) {
                return 1;
            }
            
            // names must be equal
            return 0;
            });
        }
    }
	
}
	
/**
 * recursively test if two objects are the same
 * @param objA 
 * @param objB 
 * @returns true for same, false for not
 */
function areObjectsTheSame(objA,objB){
    // console.time('compare');
    let same = helper(objA,objB);
    // console.timeEnd('compare');
    return same;

    function helper(objA,objB){
        if(!objA || !objB) return false; //missing object
        let same = true;
        if(typeof(objA) == 'string' || typeof(objA) == 'number'){
            if(!compareVal(objA,objB)) return false;
        } else if(Array.isArray(objA)){
            if(!Array.isArray(objB)){
                return false;
            }
            if(!compareArray(objA,objB)){
                return false;
            }
        } else {
            if(!compareObj(objA,objB)){
                return false;
            }
        }
    
        function compareObj(objA,objB){
            let same = true;
            for(let att of Object.keys(objA)){
                if(!objA[att] && !objB[att]) continue; //skip if both are null
                if(Array.isArray(objA[att])){
                    if(!Array.isArray(objB[att])){
                        return false;
                    }
                    if(!compareArray(objA[att],objB[att])){
                        return false;
                    }
                } else if(typeof(objA[att]) == 'string' || typeof(objA[att]) == 'number'){
                    if(!compareVal(objA[att],objB[att])){
                        return false;
                    }
                } else {
                    if(!compareObj(objA[att],objB[att])){
                        return false;
                    }
                }
            }
            return same;
        }
    
        function compareArray(arrA,arrB){
            if(!arrA || !arrB) return false; //missing array
            if(arrA.length != arrB.length) return false; //different length
            let same = true;
            for(let i=0; i<arrA.length; i++){
                if(Array.isArray(arrA[i])){
                    if(!compareArray(arrA[i],arrB[i])){
                        return false;
                    }
                } else if(typeof(arrA[i]) == 'string' || typeof(arrA[i]) == 'number'){
                    if(!compareVal(arrA[i],arrB[i])){
                        return false;
                    }
                } else {
                    if(!compareObj(arrA[i],arrB[i])){
                        return false;
                    }
                }
            }
            return same;
        }
        function compareVal(valA,valB){
            if(valA != valB) return false;
            return true;
        }
    
        return same;
    
    }

}var failedLayerIdList = [], failedLayerList = [], requestErrorMessageTimeout, errorAlertCount = 0;

var buildErrorMessage = function(layerId, layerURL) {
	
	clearTimeout(requestErrorMessageTimeout);
	requestErrorMessageTimeout = setTimeout(function(){
		//var message = "Fetch has detected that one or more third party servers could not load! Some functions may not work correctly due to this.\n\nIf this is the first time seeing this message, try reloading the page. If this problem persists, you may need to contact your system administrator to modify your network's firewall.\n\nThe following layers are sourced from somewhere other than FetchGIS and are not responding, or may have been blocked by your network's firewall:\n\n";
		var message = "3rd party layers currently unavailable: \n\n<ul>"
		for(var i = 0; i < failedLayerIdList.length; i++){
			var layerName = getLayerNameById(failedLayerIdList[i]);
			if(layerName){ //  cant find the name? its probably a state layer were not using in this mapp
				//message += "<li>" + layerName + "</li>";
				if(failedLayerList.indexOf(layerName) == -1){
					failedLayerList.push(layerName);
				}
				//var message = "3rd party layers currently unavailable: \n\n<ul>"
			}
			//message += "<br>URL:&nbsp;<a href='" + failedRequestURLList[i] + "' target='_blank'>" + failedRequestURLList[i] + "</a><br><br>";
		}
		message += "</ul>";
		if(errorAlertCount < 2){
			//showAlert("Notice: " , message);
			setFailedLayerTOCItems();
			for(lay of failedLayerList){
				showMessage("3rd party layer currently unavailable: " + lay, 9000,'info');
			}
		}
		errorAlertCount++;
	},10000);

	failedLayerIdList.push(layerId);
};

function getLayerInfos(layerId){
	if(!layerInfos4EH)
		return false;

	for(var i=0;i<layerInfos4EH.length;i++){
		var layerInfo = layerInfos4EH[i];
		if(layerInfo.featureLayer == layerId || layerInfo.featureLayer.id == layerId){
			return layerInfo;
		}
	}
	return false;
}

function isNumber(input){
	return !isNaN(parseFloat(input)) && isFinite(input);
}

function generateHash(s){
  return s.split("").reduce(function(a,b){a=((a<<5)-a)+b.charCodeAt(0);return a&a},0);              
}


function generateRandomId(){
	var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	var text = "";
	for(var i=0; i<4;i++){
		text += possible.charAt(Math.floor(Math.random() * possible.length));
	}
	var id = text + new Date().getTime();
	return id;
}

function clickCorrectCreateFeatureButton(name, attributes){
	$('#templateDiv .itemLabel').each(function(i,ea){
		if($(ea).text() == name){
			$('#templatePickerHeading').trigger('click');
			$('#templatePickerScrollPane').scrollTop(0);
			var item = $('.itemLabel').get(i);
			if(item){
				item.click();
				if(attributes){
					toolbar.setUpdateNullGeometry(attributes);
				}
			}
			return false;
		}
	})
}

function ymdDateFormat(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
}


function titleCase(str) {
   var splitStr = str.toLowerCase().split(' ');
   for (var i = 0; i < splitStr.length; i++) {
       // You do not need to check if i is larger than splitStr length, as your for does that for you
       // Assign it back to the array
       splitStr[i] = splitStr[i].charAt(0).toUpperCase() + splitStr[i].substring(1);     
   }
   // Directly return the joined string
   return splitStr.join(' '); 
}

function collectLoadError(error){
	if(error.target.id !== undefined && error.target.url !== undefined){
		//console.log("Error loading Layer: " + error.target.id + " at URL - " + error.target.url);
		if(window[error.target.id].loaded === false){
			buildErrorMessage(error.target.id, error.target.url);
		}
		
	}
}

function getLayerNameById(layerName){
	var match;
	function findIt(node, layerName){
		for(var i = 0; i < node.length; i++){
			if(node[i].id === layerName){
				match = node[i].name;
			} else if(typeof(node[i].childLayers) !== "undefined"){
				if(node[i].childLayers !== null){
					findIt(node[i].childLayers, layerName);
				}
			}
		}
	}
	
	findIt(layerDefs, layerName);
	return match;
}

function recursiveNaming(name, names){ // names is an array of already taken names -right now this guy is used in insights but i found uses for it in other places
	var basename = name,
		counter= 0;
	function inner(){
		var name = basename;
		if(counter != 0){
			name += "("+counter+")";
		}
		if(names.indexOf(name) != -1){
			counter ++;
			return inner()
		}
		return name
	}
	return inner();
}

function tableToCsv(target, skipFirstEntry){ // takes any table and returs url encoded csv data - target can be any jquery selector (though it should be a table)
	var csvContent = "",
		$table = $(target);
	var $tableRows = $table.find('tr'),
		rows = [];
	$($tableRows).each(function(i, elem){
		var row = []; // initialize empty array as row
		$(elem).find('th,td').each(function(j, elem2){
			if((skipFirstEntry && j != 0) || !skipFirstEntry){
				var value = $(elem2).text();
				if(typeof value === "string" ){ // if theres a bug in this, it's here
					value=value.replace(/[^a-zA-Z0-9,.$#%@\-+() ]/g,"");//do this filter first
					if(value.indexOf(",") != -1){
						value = JSON.stringify(value); // stringify values with a comma
					}
	
				}
				row.push(value);
			}
		});
		rows.push(row);
	});
    // format array as comma seperated string
    for(var i=0; i<rows.length; i++){
        var row = rows[i].join(",");
        csvContent += row + "\r\n";
	}
	return csvContent;
}

function setFailedLayerTOCItems(){
	for(var i = 0; i < failedLayerList.length; i++){
		$('.legendLabel').each(function(index){
			if($('.legendLabel').eq(index).html() === " " +failedLayerList[i]){
				//console.log(failedLayerList[i]);
				$('.legendLabel').eq(index).parent().addClass("failedLayer");
				if($('.legendLabel').eq(index).parent().parent().parent().hasClass('legendLayerContainer')){
					$('.legendLabel').eq(index).parent().parent().parent().addClass("failedLayer");
				}
			}
		});
	}
}

var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
function formatPrettyDate(adate, useInfinity){
	try{//sometimes adate would not be a Date on initial load. - pdf report server
		if(adate.getTime() < -2200000000000 && useInfinity){ // if date is in the stone age (close to turn of the last century), return a negative infinity sign
			//console.log("show negative infinity");
			return ' &minus;<span style="font-size: 17pt; line-height: 13pt;">&infin; </span>';
		}
		return monthNames[adate.getMonth()] + " " + adate.getDate() + ", " + adate.getFullYear();
	}catch(e){
	   // console.log(e);
		return "";
	}
}

function watchFontSize(div, increment) { //takes jquery object as div, increment is number of characters that will decrease font size by 1, original font-size must be in pixels
	//setting property to store font-size when first called
	if (div.prop('orig-font-size') == undefined) {
		div.prop('orig-font-size', parseInt(div.css('font-size').slice(0,-2)));
	} 

	var updateFontSize = function() {
		//taking orignal size subtracting character count divided by increment
		var newSize = div.prop('orig-font-size')-(Math.floor(div.html().length/increment));
		div.css('font-size', newSize.toString());
	}

	updateFontSize();
	// Select the node that will be observed for mutations
	var targetNode = document.getElementById(div[0].id);
	// Options for the observer (which mutations to observe)
	var config = { attributes: true,
        childList: true,
        characterData: true,
		subtree: true};

	// Callback function to execute when mutations are observed
	var mutationCallback = function(mutationsList, observer) {
		for(var mutation in mutationsList) {
			if (mutationsList[mutation].type === 'childList' || mutationsList[mutation].type === 'characterData') {
				updateFontSize();
			}
		}
	};

	// Create an observer instance linked to the callback function
	var observer = new MutationObserver(mutationCallback);

	// Start observing the target node for configured mutations
	observer.observe(targetNode, config);

}




// native javascript extensions
String.prototype.replaceAll = function(search, replacement) {
	var target = this;
	return target.replace(new RegExp(search, 'g'), replacement);
};

/*
 ******************************************************************
	Function to send table to and get xlsx download from server
 ******************************************************************
*/
// note input data passed in must be stringified JSON compressed with LZString compressToEncodedURIComponent method
window.getxls = function(inputData){

	var url = '../../PHPExcel/download-xlsx.php';
	var xhr = new XMLHttpRequest();
	//var inputData = {"filename":"featureDemo.xlsx"};
	xhr.open('POST', url, true);
	xhr.responseType = 'arraybuffer';
	xhr.onload = function () {
		if (this.status === 200) {
			var filename = "";
			var disposition = xhr.getResponseHeader('Content-Disposition');
			if (disposition && disposition.indexOf('attachment') !== -1) {
				var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
				var matches = filenameRegex.exec(disposition);
				if (matches != null && matches[1]){
					filename = matches[1].replace(/['"]/g, '');
					filename += ".xlsx";
				}
			}
			var type = xhr.getResponseHeader('Content-Type');
	
			var blob = new Blob([this.response], { type: type });
			if (typeof window.navigator.msSaveBlob !== 'undefined') {
				// IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created. These URLs will no longer resolve as the data backing the URL has been freed."
				window.navigator.msSaveBlob(blob, filename);
			} else {
				var URL = window.URL || window.webkitURL;
				var downloadUrl = URL.createObjectURL(blob);
	
				if (filename) {
					// use HTML5 a[download] attribute to specify filename
					var a = document.createElement("a");
					// safari doesn't support this yet
					if (typeof a.download === 'undefined') {
						window.location = downloadUrl;
					} else {
						a.href = downloadUrl;
						a.download = filename;
						document.body.appendChild(a);
						a.click();
					}
				} else {
					window.location = downloadUrl;
				}
	
				setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 100); // cleanup
			}
			$("#dlSpinner").remove();
		} else {
			$("#dlSpinner").remove();
			showMessage('There was an error producing this xls.',null,'error')
		}
	};
	xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	xhr.send($.param(inputData));
	
	$('.footer').append('<div id="dlSpinner" style="position: absolute; top: 1px; left: 15px"><img id="dlSpinner" style="height: 36px;" src="img/spinner-black.gif"> Creating Spreadsheet</div>');
}

// this function returns html table markup from data created for the getxls function
window.getTableFromXlsData = function(tableData){
	//console.log(tableData);
	var outputHTML = '<table style="width: 100%;">';
	var stylesStr = "", cellDataStr = "";
	for(var i = 0; i < tableData.length; i++){
		outputHTML += '<tr>';
		for(var j = 0; j < tableData[i].length; j++){
			stylesStr = "";
			cellDataStr = "";
			
			// each cell can contain any of the following: data, bold, bgColor, borders, bottomBorder, and bottomBorderOnly
			// "borders" means thin black border on all sides
			// "bottomBorder" is like "borders" but the bottom border is thicker
			// "bottomBorderOnly" is just a thick bottom border without the thin borders everywhere else.
			if(typeof(tableData[i][j].data) !== "undefined"){
				if(tableData[i][j].data !== null && tableData[i][j].data !== "null"){
					cellDataStr = tableData[i][j].data;
				}
			}
			
			if(typeof(tableData[i][j].bold) !== "undefined"){
				if(tableData[i][j].bold){
					stylesStr += "font-weight: bold; ";
				}
			}
			
			if(typeof(tableData[i][j].bgColor) !== "undefined"){
				var bgc = '#' + tableData[i][j].bgColor.slice(2);
				stylesStr += 'background-color: ' + bgc + '; color: #000;';
			}
			
			if(typeof(tableData[i][j].borders) !== "undefined"){
				if(tableData[i][j].borders){
					stylesStr += "border: 1px solid #000; ";
				}
			}
			
			if(typeof(tableData[i][j].bottomBorder) !== "undefined"){
				if(tableData[i][j].bottomBorder){
					stylesStr += "border: 1px solid #000; border-bottom: 2px solid #000; ";
				}
			}
			
			if(typeof(tableData[i][j].bottomBorderOnly) !== "undefined"){
				if(tableData[i][j].bottomBorderOnly){
					stylesStr += "border-bottom: 2px solid #000; ";
				}
			}
			
			outputHTML += '<td style="' + stylesStr + '">' + cellDataStr + '</td>';
		}
		outputHTML += '</tr>';
	}
	outputHTML += '</table>';
	return outputHTML;
}

// assign each node in layerDefs a unique key. This will simplify things going forward if filter layers are used more... i.e. we need a different way to distinguish between nodes in the layerDefs that use the same layer
$(function(){
	function applyKeyToLayerDefs(layerDefs){
		for(var i=0;i<layerDefs.length; i++){
			var node = layerDefs[i];
			node.uniqueId = generateRandomId();
			if(node.childLayers && node.childLayers.length > 0){
				applyKeyToLayerDefs(node.childLayers);
			}
		}
	}
	applyKeyToLayerDefs(layerDefs);
});


/*
#####################################################################
####                  	Init map layers via Dojo                 ####
#####################################################################
*/
// Load Dojo and Set location on page load based on URL param
require([
	// platform core
	"dojo/dom",
	"dojo/_base/event",
	"dojo/_base/array",
	"dojo/_base/lang",
	"dojo/parser",
	"dojo/store/Memory",
	"dijit/form/ComboBox",
	"dijit/form/Select",
	"dojo/data/ObjectStore",
	"dojo/date/locale",
	"esri/config",
	"esri/InfoTemplate",
	"esri/map",
	//"esri/dijit/Legend",
	"esri/sniff", // maps to 'has' function and is used for device detection
	"esri/dijit/Popup",
	"esri/dijit/PopupTemplate",
	"esri/dijit/Search",
   	"dojo/on",
	"dojo/dom-construct",
	"dojo/dom-class",
	"dojo/dom-style",
	"esri/geometry/webMercatorUtils",
	"esri/geometry/screenUtils",
	"esri/SpatialReference",
	"esri/urlUtils",
	"esri/geometry/geodesicUtils",
	"esri/units",
	"esri/graphicsUtils",
	"dijit/registry",
	"dijit/TooltipDialog",
	"dijit/popup",
	"esri/layers/ArcGISImageServiceLayer",
	"esri/layers/ArcGISTiledMapServiceLayer",
	"esri/layers/ArcGISDynamicMapServiceLayer",
	"esri/layers/WMSLayer",
	"esri/layers/WMSLayerInfo",
	"esri/layers/WMTSLayerInfo",
	"esri/layers/WMTSLayer",
	"esri/layers/OpenStreetMapLayer",
	"esri/layers/VectorTileLayer",
	"esri/layers/WebTiledLayer",
	"esri/layers/GraphicsLayer",
	"esri/layers/FeatureLayer",
	"esri/layers/LabelLayer",
	"esri/layers/LabelClass",
	"esri/renderers/SimpleRenderer",
	"esri/renderers/UniqueValueRenderer",
	"esri/tasks/QueryTask",
	"esri/tasks/RelationshipQuery",
	"esri/tasks/query",
	"dijit/form/Button",
	"esri/basemaps",

	// core - but not for phones
	"esri/dijit/OverviewMap",
	"esri/toolbars/navigation",
	"esri/dijit/Scalebar",

	 // drawing
	"esri/SnappingManager",
	"esri/Color",
	"dojo/keys",
	"esri/dijit/Measurement",
	"esri/toolbars/draw",
	"esri/toolbars/edit",
	"esri/graphic",
	"esri/geometry/geometryEngine", // drawing/buffers
	"esri/dijit/editing/Editor",
	"esri/geometry/Point",
	"esri/geometry/Polyline",
	"esri/geometry/Circle",
	"esri/geometry/Polygon",
	"esri/geometry/Extent",
	"esri/geometry/ScreenPoint",
	"esri/tasks/GeometryService",
	"esri/symbols/SimpleMarkerSymbol",
	"esri/symbols/SimpleLineSymbol",
	"esri/symbols/SimpleFillSymbol",
	"esri/symbols/PictureFillSymbol",
	"esri/symbols/PictureMarkerSymbol",
	"esri/symbols/CartographicLineSymbol",
	"esri/symbols/TextSymbol",
	"esri/symbols/Font",
	"esri/dijit/LayerSwipe",
	// proj replacement
	"esri/geometry/projection",
	// insights

	"esri/renderers/ClassBreaksRenderer",
	"esri/renderers/HeatmapRenderer",
	"esri/renderers/ScaleDependentRenderer",
	"esri/layers/LayerDrawingOptions",
	"esri/layers/LabelClass",

	// locator for dist10EH
	"esri/tasks/locator",

	 // Feature creation and editing (EH)
	"esri/dijit/AttributeInspector",
	"esri/dijit/editing/TemplatePicker",
	"dijit/focus",

	"dijit/layout/ContentPane", // core
	"dijit/Toolbar", // core
	"js/filterLayer6.js", // filter layers
		"dojo/domReady!" // core
],
 // These dojo function names must be in same order as above!
function(
	// core
	dom, event, arrayUtils, lang, parser, Memory, ComboBox, Select, ObjectStore, locale, esriConfig, InfoTemplate, Map, /*Legend,*/ has, Popup, PopupTemplate, Search, on, domConstruct, domClass, domStyle,
	webMercatorUtils, screenUtils, SpatialReference, urlUtils, geodesicUtils, Units, graphicsUtils, registry, TooltipDialog, dijitPopup, ArcGISImageServiceLayer,
	ArcGISTiledMapServiceLayer, ArcGISDynamicMapServiceLayer, WMSLayer, WMSLayerInfo, WMTSLayerInfo, WMTSLayer, OpenStreetMapLayer, VectorTileLayer,WebTiledLayer, GraphicsLayer, FeatureLayer, LabelLayer, LabelClass, SimpleRenderer, UniqueValueRenderer,
	QueryTask, RelationshipQuery, query, Button, esriBasemaps,

	// core, but not mobile
	OverviewMap, Navigation, Scalebar,

	// drawing, and misc
	SnappingManager, Color, keys, Measurement, Draw, Edit, Graphic, geometryEngine, Editor, Point, Polyline, Circle, Polygon, Extent, ScreenPoint, GeometryService, 
	SimpleMarkerSymbol, SimpleLineSymbol, SimpleFillSymbol, PictureFillSymbol, PictureMarkerSymbol, CartographicLineSymbol, TextSymbol, Font, LayerSwipe, projection, ClassBreaksRenderer, HeatmapRenderer, ScaleDependentRenderer, LayerDrawingOptions, LabelClass, Locator,

	// Feature creation/editing (EH)
	AttributeInspector, TemplatePicker, focusUtil
){
		
	// PRIVATE VARIABLES THAT SHOULD NOT BE GLOBAL CAN BE PUT HERE
	// user login variables... these and their functions need to be relocated to dojo loader for security
	var loggedIn = false, freeUser = false, loginUser; // loginUser is a handle for a function
	
	// global helper function to validate if a fee user has totally free access by accessing a non global variable
	isFreeUser = function isFreeUser(){
		if(freeUser){
			return true;
		} else {
			return false;
		}
	};
	
	if(params.pdf == "1"){
		openAsPdf = true;
	}

	var editFeatureLayer = FeatureLayer;

	// Think of this code block as jQuery 'document ready' function.
	parser.parse();

	$('#layersToggleButton').addClass('active');

	//define our proxy urls
	ourProxyURL1="https://"+window.location.host+"/proxy/ags/proxy.ashx";
	ourProxyURL2="https://"+window.location.host+"/proxy/ags/proxy.ashx";
	ourProxyURL3="https://"+window.location.host+"/proxy/ags/proxy.ashx";
	ourProxyURL4="https://"+window.location.host+"/proxy/ags/proxy.ashx";
	ourProxyURL5="https://"+window.location.host+"/proxy/ags/proxy.ashx";
	ourProxyURL6="https://"+window.location.host+"/proxy/ags/proxy.ashx";
	ourProxyURL7="https://"+window.location.host+"/proxy/ags/proxy.ashx";

	urlUtils.addProxyRule({
		urlPrefix: "4.aerial.maps.ls.hereapi.com",
		proxyUrl: ourProxyURL5
	});
	urlUtils.addProxyRule({
		urlPrefix: "3.aerial.maps.ls.hereapi.com",
		proxyUrl: ourProxyURL5
	});
	urlUtils.addProxyRule({
		urlPrefix: "2.aerial.maps.ls.hereapi.com",
		proxyUrl: ourProxyURL5
	});
	urlUtils.addProxyRule({
		urlPrefix: "1.aerial.maps.ls.hereapi.com",
		proxyUrl: ourProxyURL5
	});

	urlUtils.addProxyRule({
		urlPrefix: "4.base.maps.ls.hereapi.com",
		proxyUrl: ourProxyURL5
	});
	urlUtils.addProxyRule({
		urlPrefix: "3.base.maps.ls.hereapi.com",
		proxyUrl: ourProxyURL5
	});
	urlUtils.addProxyRule({
		urlPrefix: "2.base.maps.ls.hereapi.com",
		proxyUrl: ourProxyURL5
	});
	urlUtils.addProxyRule({
		urlPrefix: "1.base.maps.ls.hereapi.com",
		proxyUrl: ourProxyURL5
	});

	urlUtils.addProxyRule({
		urlPrefix: "ibasemaps-api.arcgis.com",
		proxyUrl: ourProxyURL5
	});
	urlUtils.addProxyRule({
		urlPrefix: "basemaps.arcgis.com",
		proxyUrl: ourProxyURL5
	});
	// urlUtils.addProxyRule({
	// 	urlPrefix: "api.nearmap.com/wms/",
	// 	proxyUrl: ourProxyURL5
	// });
	urlUtils.addProxyRule({
		urlPrefix: "map.fetchgis.com/mapserverweb/",
		proxyUrl: ourProxyURL5
	});
	urlUtils.addProxyRule({
		urlPrefix: "app.fetchgis.com/geoservices/",
		proxyUrl: ourProxyURL5
	});
	urlUtils.addProxyRule({
		urlPrefix: "geo.fetchgis.com/geoserver/",
		proxyUrl: ourProxyURL5
	});

	urlUtils.addProxyRule({
		urlPrefix: "services3.arcgis.com/blE8NdGZSd9Sfngv/",
		proxyUrl: ourProxyURL5
	});

	urlUtils.addProxyRule({
		urlPrefix: "landscape11.arcgis.com",
		proxyUrl: ourProxyURL5
	});
	
	urlUtils.addProxyRule({
		urlPrefix: "services7.arcgis.com/xdHvC4rURPAw2lXw/",
		proxyUrl: ourProxyURL5
	});

	urlUtils.addProxyRule({
		urlPrefix: "tiles.arcgis.com/tiles/xdHvC4rURPAw2lXw",
		proxyUrl: ourProxyURL1
	});
	urlUtils.addProxyRule({
		urlPrefix: "tiles.arcgis.com/tiles/blE8NdGZSd9Sfngv",
		proxyUrl: ourProxyURL1
	});
	
	urlUtils.addProxyRule({
		urlPrefix: "tiles1.arcgis.com",
		proxyUrl: ourProxyURL1
	});
	
	urlUtils.addProxyRule({
		urlPrefix: "tiles2.arcgis.com",
		proxyUrl: ourProxyURL2
	});

	urlUtils.addProxyRule({
		urlPrefix: "tiles3.arcgis.com",
		proxyUrl: ourProxyURL3
	});
	
	urlUtils.addProxyRule({
		urlPrefix: "tiles4.arcgis.com",
		proxyUrl: ourProxyURL4
	});
	
	urlUtils.addProxyRule({
		urlPrefix: "naip.arcgis.com",
		proxyUrl: ourProxyURL4
	});

		
    //modify default map request xhr timeout from 60sec to 120sec.
	esriConfig.defaults.io.timeout=120000;

	//temp add corsenabled servers for use 
	/*
	esriConfig.defaults.io.corsEnabledServers.push({
		host: "devx.fetchgis.com",
		withCredentials: true
	  });
	  */

	
		
	// stateplane projection initilize
	window.statePlaneSR = new SpatialReference(statePlaneCode);

	if(loadReport === true || loadSalesReport === true ){
		esriConfig.defaults.map.panDuration = 1; // time in milliseconds, default panDuration: 350
        esriConfig.defaults.map.panRate = 1; // default panRate: 25
        esriConfig.defaults.map.zoomDuration = 1; // default zoomDuration: 500
        esriConfig.defaults.map.zoomRate = 1; // default zoomRate: 25
		
		$.fx.speeds._default = 10; // jQuery global animation speed... default is normaly 400ms
	}

	// get projection module loaded
	window.projectionPromise = projection.load();

	esriBasemaps.newWorld = {
      baseMapLayers: [{url: "https://ibasemaps-api.arcgis.com/arcgis/rest/services/World_Imagery/MapServer"}
      ],
      title: "World Imagery"
    };

	// globalize api components here if needed (UI handlers mainly)
	window.webMercatorUtils = webMercatorUtils; 		// used globally by .bingBirdsEyeLink and .googleStreetViewLink click functions in uiHandlers.php, and by updateURLParams() in startupFunctions.php
	window.relationshipQuery = RelationshipQuery;
	
	// Main map variable. Location defaults to Midland county's centroid as a place holder, but pans to tile layer's extent almost immediately unless url params are set.
	var mapInitCenter, mapInitZoom;
	if(params.centerLat == undefined || params.centerLng == undefined || params.mapZoom == undefined || params.centerLat == 'NaN' || params.centerLng == 'NaN' || params.mapZoom == 'NaN' ){
		mapInitCenter = [-83.95, 43.73];
		mapInitZoom = 10;
	} else {
		mapInitCenter = [parseFloat(params.centerLng), parseFloat(params.centerLat)];
		mapInitZoom = parseFloat(params.mapZoom);
	}

	// add method to graphics layer prototype to determine geomety types contained
	GraphicsLayer.prototype.getGeometryType = function(){
		var geometryType;
		for(var i=0; i<this.graphics.length; i++){
			var graphic = this.graphics[i];
			var type = "esriGeometry" + graphic.geometry.type.charAt(0).toUpperCase() + graphic.geometry.type.slice(1);
			if(!geometryType){
				geometryType = type; // similar to how feature layer geometry type is formatted
			} else if(type != geometryType){
				geometryType = "Mixed";
				break;
			}
		}

		return geometryType;
	}

	// handlers for basemap layer
$('.basemapCheckbox').on("click", function(){ // handles toggling basemap on/off
	if($(this).hasClass('fa-check-square-o')){
		$(this).removeClass('fa-check-square-o').addClass('fa-square-o');
		$('.basemapRadio').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
		// check if basemap is undefined to prevent errors/map breakage. (shouldn't be needed but just to be safe)
		//map._removeBasemap();
		try{
			map.getLayer(map.basemapLayerIds[0]).hide();
		} catch(err){
			console.log("Basemap was not ready to hide: "+ err.message);
		}

	} else {
		$(this).removeClass('fa-square-o').addClass('fa-check-square-o');
		
		// if basemap was never set, set it to the first available in the TOC (since sometimes we remove some)
		if(lastBasemap === ""){
			var buttonId = $('.basemapRadio').eq(0).attr('id').replace('LayerRadio', '');
			map.setBasemap(buttonId);
			$('#' + buttonId + 'LayerRadio').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
			lastBasemap = buttonId;
		} else {
			$('#' + lastBasemap + 'LayerRadio').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
			map.setBasemap(lastBasemap);
		}
		
		// expand the legend if not showing
		if($(this).prev().hasClass('fa-caret-right')){
			$(this).prev().trigger('click');
		}
	}
	getLayerCheckboxState();
});

$('.basemapRadio').on("click", function(){ // handles changing basemaps
	if($(this).hasClass('fa-dot-cirle-o')){
		return;
	} else {
		$('.basemapRadio').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
		$('#baseLayersCheckbox').removeClass('fa-square-o').addClass('fa-check-square-o');
		$(this).removeClass('fa-circle-o').addClass('fa-dot-circle-o');
		var strIndex = $(this).prop('id');
		var strId = strIndex.replace("LayerRadio", ""); //get index of "LayerCheckbox" in the id string
		lastBasemap = strId;
		map.setBasemap(strId);
	}
});


// basically just hash the params to get the unique id
function getFilterLayerCheckboxId(layerInfo){
	var layerId = layerInfo.id,
		showAll = layerInfo.filterShowAll === true ? 'showall' : "",
		exclude = layerInfo.filterExclude === true ? "exclude" : ""; 
	/*if(typeof filterValue == "string"){
		filterValue = filterValue.replace(/_/g, "").replace(/ /g, "-");
	}*/
	return layerId + generateHash(showAll+JSON.stringify(layerInfo.filterField)+exclude+JSON.stringify(layerInfo.filterValue));
	//return layerId + "-" + filterField.replace(/_/g, "") + "-" + exclude + filterValue;
}

function getAttributeRendererCheckboxId(layerInfo){
	return layerInfo.id + generateHash(layerInfo.renderer);
}

function getFilterLayerCheckboxIdFromArgs(layerId, field, values, exclude, showAll){
	var exclude = exclude === true ? "exclude" : "",
		showAll = showAll === true ? 'showall' : "";
	/*if(typeof filterValue == "string"){
		filterValue = filterValue.replace(/_/g, "").replace(/ /g, "-");
	}
	return layerId + "-" + field.replace(/_/g, "") + "-" + filterValue;*/
	return layerId + generateHash(showAll+JSON.stringify(field)+exclude+JSON.stringify(values));
}

// this function is for lazy loading feature layers so we don't have to load them all on page load.
function lazyLoadLayer(layerObj){
	if(typeof(layerObj) === "undefined"){
		return false;
	}
	found = false;
	/* This part of the function adds feature layers to the map that are NOT added on page load.
		Steps:
		-Find if the layer has been added to the map (we have an array called loaded feature layer where this is recorded).
		-Find the index of the first ADDED layer that is supposed to be before it in the layer order (we have a layer order array called... well... layerOrderArray)
		-Add the new feature right after the one that exists on the map and is before it in the intended stacking order.
	*/
	if(typeof(layerObj.type) !== "undefined"){
		if(layerObj.type == "Feature Layer"){
			var featureLayerIndex;
			for(var i = 0; i < loadedFeatureLayers.length; i++){
				if(loadedFeatureLayers[i] == layerObj.id){
					found = true;
				}
			}
			if(found === false){
				//console.log("not loaded: " + layerObj.id);
				// find the index of the layer to be added, then find the first added layer before it and get it's index.
				if(layerOrderArray !== undefined){
					for(var i = 0; i < layerOrderArray.length; i++){
						if(typeof(layerOrderArray[i]) !== "undefined"){
							if(layerOrderArray[i].id == layerObj.id){
								featureLayerIndex = i;
								//console.log("featureLayerIndex: " + featureLayerIndex);
							}
						}
					}
					// loop backwards to find the first present feature layer
					var previousLayerIndex = 0;
					for(var i = featureLayerIndex; i > 0; i--){
						for(var j = 0; j < loadedFeatureLayers.length; j++){
							if(loadedFeatureLayers[j] == layerOrderArray[i].id){
								//console.log("previous found layer: " + layerOrderArray[i].id);
								previousLayerIndex = j + 1;
								i = 0;
								break;
							}
						}
					}
					try{
						map.addLayer(layerObj, previousLayerIndex);
					} catch(err){
						console.log("Error lazy loading layer: " + layerObj.id);
					}
				}
			}
		}
	}

}

// this function is for lazy loading feature layers so we don't have to load them all on page load.
function lazyLoadTileLayer(layerObj){
	if(typeof(layerObj) === "undefined"){
		return false;
	}
	found = false;

	var loadedTileLayers = map.layerIds; // this gets all non graphics layers

	/* This part of the function adds tile layers to the map that are NOT added on page load.
		Steps:
		-Find if the layer has been added to the map (we have an array called loadedTileLayers where this is recorded).
		-Find the index of the first ADDED layer that is supposed to be before it in the layer order (we have a layer order array called... well... layerOrderArray)
		-Add the new feature right after the one that exists on the map and is before it in the intended stacking order.
	*/

	var tileLayerIndex;
	for(var i = 0; i < loadedTileLayers.length; i++){
		if(loadedTileLayers[i] == layerObj.id){
			found = true;
		}
	}
	if(found === false){
		// find the index of the layer to be added, then find the first added layer before it and get it's index.
		if(layerOrderArray !== undefined){
			for(var i = 0; i < layerOrderArray.length; i++){
				if(typeof(layerOrderArray[i]) !== "undefined"){
					if(layerOrderArray[i].id == layerObj.id){
						tileLayerIndex = i;
					}
				}
			}
			// loop backwards to find the first present feature layer
			var previousLayerIndex = 0;
			for(var i = tileLayerIndex; i > 0; i--){
				for(var j = 0; j < loadedTileLayers.length; j++){
					if(typeof(layerOrderArray[i]) !== "undefined"){
						if(loadedTileLayers[j] == layerOrderArray[i].id){
							previousLayerIndex = j + 1;
							i = 0;
							break;
						}
					}
				}
			}
			try{
				map.addLayer(layerObj, previousLayerIndex);
			} catch(err){
				console.log("Error lazy loading layer: " + layerObj.id);
			}
		}
	}
}

function closePopupIfLayerIsHidden(layerObj){
	if(popup.selectedIndex !== -1 && popup.features.length > 0){
		if(popup.features[popup.selectedIndex]._layer.id === layerObj.id){ // found a match. Close the popup
			popup.hide();
		}
	}
}

// This code handles the checkbox states for the 'layers' control
var legendCheckboxClickHandler;
function setLayersEventHandlers(){
	if(legendCheckboxClickHandler !== undefined){
		//legendCheckboxClickHandler.off();
	}
	legendCheckboxClickHandler = $('.legendCheckbox').on("click", function(event){
		var layerId = "";
		// first take care of the checkbox that was clicked
        // First block to handle non-imagery layers
		var isPrvt = $(this).parent().hasClass("privateLayer");
		if(isPrvt === true){
			return false;
		}
        if ( $(this).is('.fa-check-square-o, .fa-square-o')){ 
			if( $(this).hasClass('fa-check-square-o')){ // here, we are turning OFF a layer
                $(this).removeClass('fa-check-square-o').addClass('fa-square-o');
                $(this).next().css('opacity', '0');
				layerId = $(this).attr("layerid");
				var layerObj = window[layerId];
                //layerId = layerId.replace("Checkbox", "");
                if($(this).hasClass('legendLayerParent') === false){
					var data = $(this).data();
					var htmlId = layerId;
					if(data.filterfield && data.filtervalue || data.filtershowall){
						var layer = window[$(this).attr("layerid")];
						var exclude = data.filterexclude;
						var showAll = data.filtershowall;
						layer.removeFilter(data.filterfield, data.filtervalue, exclude, showAll);
						htmlId = getFilterLayerCheckboxIdFromArgs(layerId, data.filterfield, data.filtervalue, exclude, showAll);
					} else {
						//var layerObj = window[layerId];
						layerObj.hide();
					}
					closePopupIfLayerIsHidden(layerObj);
					if($(this).hasClass('printLegend')){
						let uid = this.getAttribute('uniqueId');
						$(`.printLegendCheckbox[uniqueid="${uid}"]`).parent().addClass('hidden');
						if($(`.printLegendCheckbox[uniqueid="${uid}"]`).hasClass('fa-check-square-o')){
							$(`.printLegendCheckbox[uniqueid="${uid}"]`).trigger('click');
						}
					}
	
					// turn off wms layer label if applicable
					if($(this).attr('wms') !== undefined){
						var wmsVisibleLayerIndex = null;
						for(var i = 0; i < wmsVisibleLayers.length; i++){
							
							if(wmsVisibleLayers[i] === $(this).attr('wms')){
								wmsVisibleLayerIndex = i;
							}
						}
						wmsVisibleLayers.splice(wmsVisibleLayerIndex,1);//remove
						wmsLabelLayer.suspend();
						wmsLabelLayer.setVisibleLayers(wmsVisibleLayers);
						updateWmsLabelLayer();
					}

					if($(this).attr('linkedlayerids') !== undefined){
						var linkedLayersStr = $(this).attr('linkedlayerids');
						linkedLayersStr = linkedLayersStr.replace(/ /g, ""); // remove blank spaces
						var linkedLayersArr = linkedLayersStr.split(",");
						for(var i = 0; i < linkedLayersArr.length; i++){
							var linkedLayerObj = window[linkedLayersArr[i]];
							linkedLayerObj.hide();
						}
					}
                } else {
					var childElems = $(this).parent().children('.legendLayerAccordion').find('.legendLayerChild');
					childElems.each(function(el){
						let uid = this.getAttribute('uniqueId');
						$(`.printLegendCheckbox[uniqueid="${uid}"]`).parent().addClass('hidden');
					})
				}
            } else { // here we are turning ON a layer
                $(this).removeClass('fa-square-o').addClass('fa-check-square-o');
                $(this).next().css('opacity', '0');
				layerId = $(this).attr("layerid");
				var layerObj = window[layerId];
                //layerId = layerId.replace("Checkbox", "");
                if($(this).hasClass('legendLayerParent') === false){
					var data = $(this).data();
					var htmlId = layerId;
					if(data.filterfield && data.filtervalue || data.filtershowall){
						var layer = window[$(this).attr("layerid")];
						var exclude = data.filterexclude;
						var showAll = data.filtershowall;
						layer.addFilter(data.filterfield, data.filtervalue, exclude, showAll);
						htmlId = getFilterLayerCheckboxIdFromArgs(layerId, data.filterfield, data.filtervalue, exclude, showAll);
					} else {
						//var layerObj = window[layerId];
						lazyLoadLayer(layerObj);
						layerObj.show();
					}
					if($(this).hasClass('printLegend')){
						let uid = this.getAttribute('uniqueId');
						$(`.printLegendCheckbox[uniqueid="${uid}"]`).parent().removeClass('hidden');
					}		
                }
				// expand the legend if not showing
				if($(this).prev().hasClass('fa-caret-right')){
					$(this).prev().trigger('click');
				}
				
				// turn on wms layer if applicable
				if($(this).attr('wms') !== undefined){
					var foundWMS = false;
					for(var i = 0; i < wmsVisibleLayers.length; i++){
						if(wmsVisibleLayers[i] === $(this).attr('wms')){
							foundWMS = true;
						}
					}
					if(foundWMS === false){
						wmsVisibleLayers.push($(this).attr('wms'));
						wmsLabelLayer.suspend();
						wmsLabelLayer.setVisibleLayers(wmsVisibleLayers);
						updateWmsLabelLayer();
					}
				}
				if($(this).attr('linkedlayerids') !== undefined){
					var linkedLayersStr = $(this).attr('linkedlayerids');
					linkedLayersStr = linkedLayersStr.replace(/ /g, ""); // remove blank spaces
					var linkedLayersArr = linkedLayersStr.split(",");
					for(var i = 0; i < linkedLayersArr.length; i++){
						var linkedLayerObj = window[linkedLayersArr[i]];
						lazyLoadLayer(linkedLayerObj);
						linkedLayerObj.show();
					}
				}
            }
			
			// show/hide the print legend builder placeholder
			var foundVisible = false;
			$('.printLegendCheckbox').parent().each(function(index){
				if($('.printLegendCheckbox').parent().eq(index).hasClass('hidden') === false ){
					foundVisible = true;					
				}
			});
			if(foundVisible === true){
				$('#printLegendBuilderPlaceHolder').addClass('hidden');
			} else {
				$('#printLegendBuilderPlaceHolder').removeClass('hidden');
			}
        }
        // block to handle imagery layer
        if ( $(this).is('.fa-circle-o')){ // no need to handle fa-dot-circle-o
			
			$(this).removeClass('fa-circle-o').addClass('fa-dot-circle-o');
			$(this).next().css('opacity', '0');
			layerId = $(this).attr("layerid");
			//layerId = layerId.replace("Checkbox", "");
			if($(this).hasClass('legendLayerParent') === false){
				var layerObj = window[layerId];
				if(layerId == "imageryNAIPLayer"){
					map.addLayer(imageryNAIPLayer, 2);
					imageryNAIPLayer.show();
				} else {
					var data = $(this).data();
					if (data.renderer) {
						layerObj.setRenderer(window[data.renderer]);
						layerObj.redraw();
					}
					lazyLoadTileLayer(layerObj); 
					layerObj.show();
					// hide all imagery print legend checkboxes, then show the one the user clicked
					var checkIt = false;
					// get sibling radios so you can hide em - there can only be one
					var siblingElems = $(this).parent().siblings('.legendLayerContainer').children('.legendCheckbox');
					siblingElems.each(function(el){
						let uid = this.getAttribute('uniqueId');
						$(`.printLegendCheckbox[uniqueid="${uid}"]`).parent().addClass('hidden');
					})

					if($(this).hasClass('printLegend')){
						let uid = this.getAttribute('uniqueId');
						$(`.printLegendCheckbox[uniqueid="${uid}"]`).parent().removeClass('hidden');
						if(checkIt === true){
							$(`.printLegendCheckbox[uniqueid="${uid}"]`).trigger('click');
						}
					}
					
					if($(this).attr('linkedlayerids') !== undefined){
						var linkedLayersStr = $(this).attr('linkedlayerids');
						linkedLayersStr = linkedLayersStr.replace(/ /g, ""); // remove blank spaces
						var linkedLayersArr = linkedLayersStr.split(",");
						for(var i = 0; i < linkedLayersArr.length; i++){
							var linkedLayerObj = window[linkedLayersArr[i]];
							lazyLoadTileLayer(linkedLayerObj);
							linkedLayerObj.show();
						}
					}
					
					// set radio button for right side layer swipe
					$('.rightSwipe').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
					$('.rightSwipe').each(function(index){
						if($('.rightSwipe').eq(index).attr('layer') === layerId){
							$('.rightSwipe').eq(index).removeClass('fa-circle-o').addClass('fa-dot-circle-o');
						}
					});
				}
			}

			// expand the legend if not showing
			if($(this).hasClass("radioCheckbox") && $(this).prev().hasClass('fa-caret-right')){
				$(this).prev().trigger('click');
			}
			
            setImageryRadioButton(this);
        }


		setParentCheckboxState(this, false);


		function turnOnNestedRadios(children){
			var firstChild = $(children).first();
			$(firstChild).removeClass('fa-circle-o').addClass('fa-dot-circle-o');
			if($(firstChild).hasClass("legendLayerParent")){
				var grandChildren = $(firstChild).parent().find('.legendLayerChild').not(firstChild);
				turnOnNestedRadios(grandChildren);
			}
		}
		
		
		function setParentCheckboxState(layer, calledRecursively){
			// check to see if the checkbox belongs to a '.legendLayerContainer' with children of the same type nested within (in other words, we're dealing with a layer with sub-layers)
			var layerIsParent = $(layer).hasClass('legendLayerParent');
			
			if(layerIsParent === true && calledRecursively === false){  // We only want this block to run on the first call. It sets all child checkboxes so we only want to do this to the one that is clicked (not parents)

				if($(layer).hasClass('fa-square-o')){
					
                    // handle all but imagery layer to turn off
                    if ( $(layer).parent().find('.legendLayerChild').hasClass('fa-check-square-o') ){
                        $(layer).parent().find('.legendLayerChild, .legendLayerParent').removeClass('fa-check-square-o').addClass('fa-square-o');
                        $(layer).parent().find('.legendLayerParent').next().css('opacity', '0');
                    } else if ( $(layer).parent().find('.legendLayerChild').hasClass('fa-dot-circle-o') ) {
						// handle imagery layer to turn off
                        $(layer).parent().find('.legendLayerChild').not(layer).removeClass('fa-dot-circle-o').addClass('fa-circle-o');
                        //$(layer).parent().find('.legendLayerParent').removeClass('fa-check-square-o').addClass('fa-square-o');
                        //$(layer).parent().find('.legendLayerParent').next().css('opacity', '0');
					}
					toggleLayer($(layer).parent().find('.legendLayerChild'));
				} else if ($(layer).hasClass('radioCheckbox')) {
						var children =  $(layer).parent().find('.legendLayerChild').not(layer);
						$(children).removeClass('fa-dot-circle-o').addClass('fa-circle-o'); 
						$(children).first().removeClass('fa-circle-o').addClass('fa-dot-circle-o'); // turn on top-most imagery layer if parent layer switched on
						toggleLayer(children);
				} else {
                    // handle all but imagery layer to turn on
                    if ( $(layer).parent().find('.legendLayerChild').hasClass('fa-square-o') ){
						
						var theArr = $(layer).parent().find('.legendLayerChild, .legendLayerParent');
						for(var i = 0; i < theArr.length; i++){
							var izPrvt = $(theArr[i]).parent().hasClass('privateLayer');
							if(izPrvt === false){
								$(theArr[i]).removeClass('fa-square-o').addClass('fa-check-square-o');
								$(theArr[i]).find('.legendLayerParent').next().css('opacity', '0');
							}
							
						}
						//$(layer).parent().find('.legendLayerChild, .legendLayerParent').removeClass('fa-square-o').addClass('fa-check-square-o');
						//$(layer).parent().find('.legendLayerParent').next().css('opacity', '0'); // this is the square for partials
						
                    } else if ( $(layer).parent().find('.legendLayerChild').hasClass('fa-circle-o') ){
					// handle imagery layer to turn on. 
						var children = $(layer).parent().find('.legendLayerChild').not(layer);
						turnOnNestedRadios(children);
                        //$(layer).parent().find('.legendLayerChild').first().removeClass('fa-circle-o').addClass('fa-dot-circle-o'); // turn on top-most imagery layer if parent layer switched on
                        //$(layer).parent().find('.legendLayerParent').removeClass('fa-square-o').addClass('fa-check-square-o');
                        //$(layer).parent().find('.legendLayerParent').next().css('opacity', '0');
					}

                    toggleLayer($(layer).parent().find('.legendLayerChild'));
                }
			}
		
			// check to see if the checkbox is nested within another '.legendLayerContainer' and if so, set the checkbox state of the parent (in other words, are we dealing with a sub-layer)
			var layerIsChild = $(layer).hasClass('legendLayerChild');
			if(layerIsChild === true){
				var parentCheckbox = $(layer).parent().parent().parent().children('.legendCheckbox');

				if($(parentCheckbox).hasClass("radioCheckbox") == false){ // parent is not a radio
					var count = 0, found = 0;
					var kids = $(event.target.parentElement).parent().find('.legendCheckbox'); // count the number of checkboxes and see how many are checked
					
					for (var i = 0; i < kids.length; i++){
						count++;
						if($(kids[i]).is('.fa-check-square-o, .fa-dot-circle-o') ){
							found++;
						}
					}
					
					if(found === 0/* && calledRecursively === false*/){ //found none checked and function is not called by itself (since we don't want to uncheck all ancestors)
						$(parentCheckbox).removeClass('fa-check-square-o').addClass('fa-square-o');
						$(layer).parent().parent().parent().children('.checkBoxInner').css('opacity', '0');
					} else if(found == count){ // found all to be checked
						$(layer).parent().parent().parent().children('.legendCheckbox').removeClass('fa-square-o').addClass('fa-check-square-o');
						$(layer).parent().parent().parent().children('.checkBoxInner').css('opacity', '0');
					} else {
						if(typeof($(layer).prop('id') !== "undefined")){
							if(found > 0 && found < count && $(layer).prop('id').substring(0,7) != 'imagery' && !$(layer).hasClass('radioCheckbox')){
								$(layer).parent().parent().parent().children('.legendCheckbox').removeClass('fa-check-square-o').addClass('fa-square-o');
								$(layer).parent().parent().parent().children('.checkBoxInner').css('opacity', '1');
							} else if (found > 0 && found < count && ($(layer).prop('id').substring(0,7) == 'imagery' || $(layer).hasClass('radioCheckbox'))) {
								$(layer).parent().parent().parent().children('.legendCheckbox').removeClass('fa-square-o').addClass('fa-check-square-o');
								$(layer).parent().parent().parent().children('.checkBoxInner').css('opacity', '0');
							}
						}
					}
				} else { // parent is a radio
					$(parentCheckbox).removeClass("fa-circle-o").addClass("fa-dot-circle-o"); // just set appropriate class and call switching function
					setImageryRadioButton(parentCheckbox);
				}
			}
			// Check if there is a grand parent, and if so, pass the parent into this function, and keep doing so recursively until no more parents.
			var needToCallRecursive = $(layer).parent().parent().parent().parent().parent().children('.legendCheckbox').hasClass('legendLayerParent');
			if (needToCallRecursive === true){
				setParentCheckboxState($(layer).parent().parent().parent().children('.legendCheckbox'), true);
			}
			
		}
		function toggleLayer(layer2Toggle){
			$(layer2Toggle).each(function(index){
				if(typeof(layer2Toggle[index]) !== "undefined"){
					var layerId = "";
					// first take care of the checkbox that was clicked
					// block to handle non-imagery layers
					if ($(layer2Toggle[index]).is('.fa-check-square-o, .fa-square-o')){
						if( $(layer2Toggle[index]).hasClass('fa-check-square-o')){
							//$(layer2Toggle).removeClass('fa-check-square-o').addClass('fa-square-o');
							//$(layer2Toggle).next().css('opacity', '0');
							layerId = $(layer2Toggle[index]).attr("layerid");

							//layerId = layerId.attr("layerid");
							if(layerId != "parent"){
								var layerObj = window[layerId];
								// make sure we're not trying to turn on a parent checkbox (which doesn't represent any real layer)
								// also "parcelLayers" checkbox has an object on the window object, so we also can't let that one trick us!
								if(typeof(layerObj) !== "undefined" && layerId !== "parcelLayers"){ 
									var data = $(layer2Toggle[index]).data();
									var htmlId = layerId;
									if(data.filterfield && data.filtervalue || data.filtershowall){
										var layer = window[$(layer2Toggle[index]).attr("layerid")];
										var exclude = data.filterexclude;
										var showAll = data.filtershowall;
										layer.addFilter(data.filterfield, data.filtervalue, exclude, showAll);
										htmlId = getFilterLayerCheckboxIdFromArgs(layerId, data.filterfield, data.filtervalue, exclude, showAll);
									} else {
										lazyLoadLayer(layerObj);
										layerObj.show();
									}
									if($(this).hasClass('printLegend')){
										let uid = this.getAttribute('uniqueId');
										$(`.printLegendCheckbox[uniqueid="${uid}"]`).parent().removeClass('hidden');
									}
								}
							}
							
							// LINKED LAYER CODE
							if($(this).attr('linkedlayerids') !== undefined){
								var linkedLayersStr = $(this).attr('linkedlayerids');
								linkedLayersStr = linkedLayersStr.replace(/ /g, ""); // remove blank spaces
								var linkedLayersArr = linkedLayersStr.split(",");
								for(var i = 0; i < linkedLayersArr.length; i++){
									var linkedLayerObj = window[linkedLayersArr[i]];
									lazyLoadLayer(linkedLayerObj);
									linkedLayerObj.show();
								}
							}
							
							// WMS LAYER CODE
							// turn on wms layer if applicable
							if($(this).attr('wms') !== undefined){
								var foundWMS = false;
								for(var i = 0; i < wmsVisibleLayers.length; i++){
									if(wmsVisibleLayers[i] === $(this).attr('wms')){
										foundWMS = true;
									}
								}
								if(foundWMS === false){
									wmsVisibleLayers.push($(this).attr('wms'));
									wmsLabelLayer.suspend();
									wmsLabelLayer.setVisibleLayers(wmsVisibleLayers);
									updateWmsLabelLayer();
								}
							}	
							
							// expand legend item if layer is to be shown
							if($(layer2Toggle[index]).prev().hasClass('fa-caret-right')){
								$(layer2Toggle[index]).prev().trigger('click');
							}
							
						} else {
							//$(layer2Toggle).removeClass('fa-square-o').addClass('fa-check-square-o');
							//$(layer2Toggle).next().css('opacity', '0');
							layerId = $(layer2Toggle[index]).attr("layerid");
							//layerId = layerId.attr("layerid");
							if(layerId != "parent"){
								var layerObj = window[layerId];
								if(typeof(layerObj) !== "undefined" && layerId !== "parcelLayers"){ // make sure we're not trying to turn on a parent checkbox (which doesn't represent any real layer)
									var data = $(layer2Toggle[index]).data();
									var htmlId = layerId;
									if(data.filterfield && data.filtervalue || data.filtershowall){
										var layer = window[$(layer2Toggle[index]).attr("layerid")];
										var exclude = data.filterexclude;
										var showAll = data.filtershowall;
										layer.removeFilter(data.filterfield, data.filtervalue, exclude, showAll);
										htmlId = getFilterLayerCheckboxIdFromArgs(layerId, data.filterfield, data.filtervalue, exclude, showAll);
									} else {
										layerObj.hide();
									}
									closePopupIfLayerIsHidden(layerObj);
									if($(this).hasClass('printLegend')){
										let uid = this.getAttribute('uniqueId');
										$(`.printLegendCheckbox[uniqueid="${uid}"]`).parent().addClass('hidden');
										if($(`.printLegendCheckbox[uniqueid="${uid}"]`).hasClass('fa-check-square-o')){
											$(`.printLegendCheckbox[uniqueid="${uid}"]`).trigger('click');
										}
									}

									// LINKED LAYER CODE
									if($(this).attr('linkedlayerids') !== undefined){
										var linkedLayersStr = $(this).attr('linkedlayerids');
										linkedLayersStr = linkedLayersStr.replace(/ /g, ""); // remove blank spaces
										var linkedLayersArr = linkedLayersStr.split(",");
										for(var i = 0; i < linkedLayersArr.length; i++){
											var linkedLayerObj = window[linkedLayersArr[i]];
											linkedLayerObj.hide();
										}
									}
									
									// WMS LAYER CODE
									// turn off wms layer label if applicable
									if($(this).attr('wms') !== undefined){
										var wmsVisibleLayerIndex = null;
										for(var i = 0; i < wmsVisibleLayers.length; i++){
											if(wmsVisibleLayers[i] === $(this).attr('wms')){
												wmsVisibleLayerIndex = i;
											}
										}
										wmsVisibleLayers.splice(wmsVisibleLayerIndex,1);//remove
										wmsLabelLayer.suspend();
										wmsLabelLayer.setVisibleLayers(wmsVisibleLayers);
										updateWmsLabelLayer();
									}
									
								}
							}
						}
					} else {
						// block to handle imagery layer
						if( $(layer2Toggle[index]).hasClass('fa-dot-circle-o')){
							//$(layer2Toggle).removeClass('fa-check-square-o').addClass('fa-square-o');
							//$(layer2Toggle).next().css('opacity', '0');
							layerId = $(layer2Toggle[index]).attr("layerid");
							//layerId = layerId.replace("Checkbox", "");
							if(layerId != "parent" && $(layer2Toggle[index]).hasClass("legendLayerParent") == false){
								var layerObj = window[layerId];

								if(layerObj.id === "imageryNAIPLayer"){ // in case it's the NAIP layer
									var lazyNaipInterval = setInterval(function(){
										if(imageryNAIPLayer !== undefined && map.updating === false){
											clearInterval(lazyNaipInterval);
											if(params.pdf){
												map.addLayer(imageryNAIPLayer, 2);
												imageryNAIPLayer.show();
											} else {
												setTimeout(function(){ // not sure why, but if there isn't a timeout when adding this layer, it breaks stuff within the ESRI API. Not critical when printing.
													map.addLayer(imageryNAIPLayer, 2);
													imageryNAIPLayer.show();
												}, 3500);
											}
										}
									}, 200);
								} else {
									layerObj.show();
									var data  =$(layer2Toggle[index]).data();
									if(data.renderer){
										setTimeout(function(){
											layerObj.setRenderer(window[data.renderer]);
											layerObj.redraw();
											layerObj.show();
										}, 500);
									}
									if($(this).hasClass('printLegend') && $(this).hasClass("radioCheckbox")){
										$('#' + layerId + 'PrintLegendCheckbox').parent().removeClass('hidden');
									}
									// linked imagery layers
									if($(layer2Toggle[index]).attr('linkedlayerids') !== undefined){
										var linkedLayersStr = $(layer2Toggle[index]).attr('linkedlayerids');
										linkedLayersStr = linkedLayersStr.replace(/ /g, ""); // remove blank spaces
										var linkedLayersArr = linkedLayersStr.split(",");
										for(var i = 0; i < linkedLayersArr.length; i++){
											var linkedLayerObj = window[linkedLayersArr[i]];
											lazyLoadTileLayer(linkedLayerObj);
											linkedLayerObj.show();
										}
									}
									//return false;
								}
							} 

							if($(layer2Toggle[index]).hasClass("radioCheckbox") && $(layer2Toggle[index]).prev().hasClass('fa-caret-right')){
								$(layer2Toggle[index]).prev().trigger('click');
							}
						} else {
							//$(layer2Toggle).removeClass('fa-square-o').addClass('fa-check-square-o');
							//$(layer2Toggle).next().css('opacity', '0');
							layerId = $(layer2Toggle[index]).attr("layerid");
							//layerId = layerId.replace("Checkbox", "");
							if(layerId != "parent" && $(layer2Toggle[index]).hasClass("legendLayerParent") == false){
								var layerObj = window[layerId];
								layerObj.hide(); 
								closePopupIfLayerIsHidden(layerObj);
								if($(this).hasClass('printLegend') && $(this).hasClass("radioCheckbox")){
									$('#' + layerId + 'PrintLegendCheckbox').parent().addClass('hidden');
									if($('#' + layerId + 'PrintLegendCheckbox').hasClass('fa-check-square-o')){
										$('#' + layerId + 'PrintLegendCheckbox').trigger('click');
									}
								}
							}

							// linked imagery layers
							if($(layer2Toggle[index]).attr('linkedlayerids') !== undefined){
								var linkedLayersStr = $(layer2Toggle[index]).attr('linkedlayerids');
								linkedLayersStr = linkedLayersStr.replace(/ /g, ""); // remove blank spaces
								var linkedLayersArr = linkedLayersStr.split(",");
								for(var i = 0; i < linkedLayersArr.length; i++){
									var linkedLayerObj = window[linkedLayersArr[i]];
									linkedLayerObj.hide();
								}
							}
						}
					}
				}
			});
		}

        function setImageryRadioButton(layer){
            // only allows one imagery layer to be on at a time
            var layerCount = 0, layerId;
			var imgParent = $(layer).parent().parent('.legendLayerAccordion');
			var imgChildren = $(imgParent).find('.legendCheckbox');
            $(imgChildren).each(function(index){
                if ($(imgChildren[index]).hasClass('fa-dot-circle-o')){
                    layerCount ++;
                }
            });
            if (layerCount > 1){
        
				// get all linked layers and turn them all off (in good radio button fashion)
				var checkedLayerIds = "";
				imgParent.find('.legendCheckbox.fa-dot-circle-o').each(function(){
					var linkedLayersStr = $(this).attr('linkedLayerIds');
					if(typeof(linkedLayersStr) !== "undefined"){ // this returns "undefined"  if the layer doesn't have linked layers
						checkedLayerIds += linkedLayersStr + ", ";
					}
				});

				// remove last coma and space
				checkedLayerIds = checkedLayerIds.slice(0, checkedLayerIds.length - 2);

				// hide all found linked layers whether they're showing or not. The next block will show the ones we want.
				if(checkedLayerIds !== ""){
					checkedLayerIds = checkedLayerIds.replace(/ /g, ""); // remove blank spaces
					var checkedLayerIdArr = checkedLayerIds.split(",");
					for(var i = 0; i < checkedLayerIdArr.length; i++){
						window[checkedLayerIdArr[i]].hide();
					}
				}

				var layerSiblings = $(layer).parent().siblings();
                $(layerSiblings).each(function(index){
					if($(layerSiblings[index]).hasClass('legendOptionsContainer') === false){ // skip legendOptionsContainer divs... 
						var currentLayer = $(layerSiblings[index]).find('.legendCheckbox')[0];
						$(currentLayer).removeClass('fa-dot-circle-o').addClass('fa-circle-o');
						$(currentLayer).next().css('opacity', '0');
						if($(currentLayer).hasClass("legendLayerParent")){ // if this is a parent checkbox it aint no layer, no need to turn anything on or off
							var children =  $(currentLayer).parent().find('.legendLayerChild').not(currentLayer); // similar to what is done in setParentCheckboxState when turning off a group
							$(children).removeClass('fa-dot-circle-o').addClass('fa-circle-o'); 
							toggleLayer(children);
							return false;
						}
						layerId = $(currentLayer).attr("layerid");
						//layerId = layerId.replace("Checkbox", "");
						if(layerId != "parent"){
							var layerObj = window[layerId];
							if(!$(currentLayer).data().renderer){ // this is an attribute renderer layer, dont turn it off
								layerObj.hide();
							}
							closePopupIfLayerIsHidden(layerObj);
							
							if(typeof(layer.attributes.linkedlayerids) !== "undefined"){
								var linkedLayersStr = layer.attributes.linkedlayerids.value;
								linkedLayersStr = linkedLayersStr.replace(/ /g, ""); // remove blank spaces
								var linkedLayersArr = linkedLayersStr.split(",");
								for(var i = 0; i < linkedLayersArr.length; i++){
									var linkedLayerObj = window[linkedLayersArr[i]];
									lazyLoadTileLayer(linkedLayerObj);
									linkedLayerObj.show();
								}
							}	
						}
					}
                });
            }
        }

		getLayerCheckboxState();
	});
	
	$('.legendLayerCaret').on("click", function(){
	  if($(this).hasClass('fa-caret-down')){
		 $(this).removeClass('fa-caret-down').addClass('fa-caret-right');
		 $(this.parentElement).find('.legendLayerAccordion:first').slideUp(400, function(){
			 $('#layerCheckboxAccordionPane').perfectScrollbar('update');
		 });
	  } else if($(this).hasClass('fa-caret-right')){
		 $(this).removeClass('fa-caret-right').addClass('fa-caret-down');
		 $(this.parentElement).find('.legendLayerAccordion:first').slideDown(400, function(){
			 $('#layerCheckboxAccordionPane').perfectScrollbar('update');
		 });
	  }
	});
}



// this function calls 
var layersOutputStr = "", layerLoadHandles = {};
window.initLayerDefState = function (callback){
	var activeLayersArr, partialLayerGroupsArr;
	if(params.activeLayers !== undefined && params.activeLayers !== true && params.activeLayers !== 'undefined'){ // if layer state saved in url
		activeLayersArr = params.activeLayers.split('_');
		if(params.partialLayerGroups !== undefined && params.partialLayerGroups !== true && params.partialLayerGroups !== 'undefined'){
			partialLayerGroupsArr = params.partialLayerGroups.split('_');
		}
		if(featureEditEnabled === false){
			findThem(layerDefs);
		} else {
			findThem(layerDefs);
			var waitForIt = setInterval(function(){
				// needs to run a second time to do lazy load cleanup
				if(editableLayersAdded){
					clearInterval(waitForIt);
					findThem(layerDefs);
				}
			}, 50);
		}
		checkBasemaps();
	}

	//set the basemap to what is in activelayers
	function checkBasemaps(){
		let basemapIndex = activeLayersArr.indexOf("baseLayersCheckbox");
		if(basemapIndex > -1){
			let basemap = activeLayersArr[++basemapIndex];
			$('#'+basemap).trigger('click');
		} else if(activeLayers){ //else if active layers and no basemap, turn off basemaps
			$('.basemapCheckbox').click();
		}
	}

	function initializeAttributeRendererLayer() {

	}

	function findThem(node){
		
		var isPrivate = false;
		for(var i = 0; i < node.length; i++){ // loop through all of the layer def objects, seeing if their id's are listed in the url
			var isParent = false;
			let nodeId;
			if(typeof node[i].childLayers !== "undefined"){
				if(node[i].childLayers !== null){
					isParent = true;
				}
			}
			var foundChecked = false, foundPartial = false, filterField = null, filterValue = null;
			for(var j = 0; j < activeLayersArr.length; j++){// search the layer ids from url to see if any match the current layer id
				nodeId = node[i].id;
				if(typeof node[i].filterField != "undefined" && typeof node[i].filterValue != "undefined" || typeof node[i].filterShowAll != "undefined"){
					nodeId = getFilterLayerCheckboxId(node[i]);
				} else if(typeof node[i].renderer != "undefined"){
					nodeId = getAttributeRendererCheckboxId(node[i]);
				}
				if((nodeId + 'Checkbox') == activeLayersArr[j]){
					if(typeof(node[i].privateLayer) !== "undefined"){
						if(node[i].privateLayer === true){
							isPrivate = true;
						}
					}
					if(isPrivate === false || params.pdf === "1"){
						foundChecked = true;
						break;
					}
				}
			}
			if(partialLayerGroupsArr !== undefined){
				for(var j = 0; j < activeLayersArr.length; j++){// search the layer ids from url to see if any match the current layer id
					if((node[i].id + 'Checkbox') == partialLayerGroupsArr[j]){
						foundPartial = true;
						break;
					}
				}
			}
			if(foundChecked){ // if the objects id matches one in the list in the url, show the layer... otherwise, hide it
				node[i].checkboxState = "checked";
				if(isParent === false){
					if(node[i].id != "imageryNAIPLayer"){
						var layerObj = window[node[i].id];
						// determine if feature or tile layer.
						// Note: when a layer is added to the map, the ".tileInfo" object will not be there right away. However, the ".type" attribute exists for feature layers

						if(typeof(layerObj.type) === "undefined"){
							lazyLoadTileLayer(layerObj);
						} else {
							lazyLoadLayer(layerObj);
						}
						//ensure parents have their checkbox partialed
						$('#' + nodeId + 'Checkbox').parents('.legendLayerContainer').each(function(i,ea){
							if(i>0){ //not the initial checkbox
								$(ea).children('.fa-stop.checkBoxInner').css('opacity',1)
							}
						})
						// if the layer is loaded, just show it directly, but since IE is what it is, we have to use this VooDoo hack instead
						// also, if the layer is private, don't bother with the else clause... the pdf server is not running IE :)
						if(layerObj.filterLayer === true){ // we dont need to worry about loading with setting filter layers
							//if(layerObj.visible === false){ // no point in doing this otherwise, it will already be on
								var exclude = node[i].filterExclude;
								var showAll = node[i].filterShowAll;
								layerObj.addFilter(node[i].filterField, node[i].filterValue, exclude, showAll);
							//}
						} else {
							if(node[i].renderer) {
								layerObj.setRenderer(window[node[i].renderer]);
								layerObj.redraw();
							}
							if(layerObj.loaded || isPrivate === true){
								layerObj.show();

							} else {
								// for whatever reason in IE, the legend checkboxes work if you click them, but using <layerObj>.show() doesn't work until they legend checkboxes are clicked.
								// so, what we do is create an object that holds handlers for layers to say when they've loaded, and THEN we show them
								var id4this = layerObj.id;
								var node4this = node[i];
								layerLoadHandles[id4this] = layerObj.on('load', function(evt){
									var layerId = evt.layer.id;
									if(node4this.renderer){
										layerId = getAttributeRendererCheckboxId(node4this);
										//evt.layer.setRenderer(window[node4this.renderer]);
										//evt.layer.redraw();
									}
									if($('#' + layerId + 'Checkbox').is('.fa-circle-o, .fa-dot-circle-o')){ // have to make sure not to contaminate checkbox/radio classes or wierd-ness could happen
										$('#' + layerId + 'Checkbox').removeClass('fa-dot-cirle-o').addClass('fa-circle-o');
									} else {
										$('#' + layerId + 'Checkbox').removeClass('fa-check-square-o').addClass('fa-square-o');
									}
									//console.log($('#' + layerId + 'Checkbox').hasClass('fa-dot-circle-o'));
									$('#' + layerId + 'Checkbox').trigger('click');
								});
							}
						}
					} else {
						// load naip layer once it's been added to the DOM
						var lazyNaipInterval = setInterval(function(){
							if(imageryNAIPLayer !== undefined && map.updating === false){
								clearInterval(lazyNaipInterval);
								if(params.pdf){
									map.addLayer(imageryNAIPLayer, 2);
									imageryNAIPLayer.show();
								} else {
									setTimeout(function(){ // not sure why, but if there isn't a timeout when adding this layer, it breaks stuff within the ESRI API. Not critical when printing.
										map.addLayer(imageryNAIPLayer, 2);
										imageryNAIPLayer.show();
									}, 3500);
								}
							}
						}, 200);
					}
				} else {
					findThem(node[i].childLayers);
				}
			} else if(foundPartial){
				node[i].checkboxState = "partial";
				findThem(node[i].childLayers);
			} else {
				node[i].checkboxState = "unchecked";
				if(isParent === false){
					// no need to do anything to the NAIP layer if it's not checked. It's disabled by default.
					if(node[i].id != "imageryNAIPLayer" && !node[i].renderer && window[node[i].id]){
						if(!window[node[i].id].filterLayer){ //filterlayers could have other toc records checked so don't override
							window[node[i].id].hide();
						}
					}
				} else {
					findThem(node[i].childLayers);
				}
			}
			
		}
	}
	initLayersHTML(callback); // moved this so it fires after the layers are read from the url. Also, the callback gets passed through this function
	//$('#layerCheckboxAccordionPane').html("");
	$('#layerCheckboxAccordionPane').append(layersOutputStr);
	setLayersEventHandlers();
	//getHiddenLinkedFeatures();
	if(typeof(dynamicTocOptions) != 'undefined'){ // if dynamic object present build the dynamic toc
		DynamicTOC.onLoad();
	}
	initOpacitySliders();
	initLinkedLayers();
	setSwipeLabels();
	// after all of the above is said and done, we gotta uncheck all private layers since the user has not yet logged in
	$('.privateLayer').each(function(index){
		$('.privateLayer').eq(index).find('.legendCheckbox').removeClass('fa-check-square-o').addClass('fa-square-o');
		$('.privateLayer, .privateVisible').eq(index).find('.checkBoxInner').css({'opacity': 0});
	});
	
	// create the print legend if not on a phone
	createPrintLegend(layerDefs, false);
	$('#imageryLayersCheckbox').on("click", function(){
		if($(this).hasClass('fa-square-o')){
			// evaluates to checked
			console.log('was checked'); // now unchecked
			if($('#baseLayersCheckbox').hasClass('fa-check-square-o')){
				map.setBasemap(lastBasemap);
			}
		} // no need for an else. The basemap will hide on next pan/zoom. We're only focused on cutting down on the number of requests.
	});
}

var layersOpacityObj = {};
if(typeof(params.opacity64) !== "undefined"){
	if(params.opacity64 !== "undefined" && params.opacity64 !== true){
		var tempStr = LZString.decompressFromEncodedURIComponent(params.opacity64);
		layersOpacityObj = JSON.parse(tempStr);
		//console.log("opacity64 load val: ");
		//console.log(layersOpacityObj);
	}
}


var getLayerDescription = function(layerDef){
	if(layerDef.description) // if specified in config (most important)
		return layerDef.description;
	
	for(var i=0; i<layerDescriptionArr.length; i++){ // defined in db by client 
		var _desc = layerDescriptionArr[i];
		if(layerDef.id == _desc.layer_id){
			return _desc.desc;
		}
	}
	return "";
	//return window[layerDef.id].description || ""; //dont want description on map service

}
window.updateWmsLabelLayer = function updateWmsLabelLayer(){
	if(typeof(wmsLabelLayer) !== "undefined"){
		//add filter field params 
		
		console.log('wms update filter');
		for(var wmslay of wmsLabelLayer.visibleLayers){
			wmslay=wmslay.split("~")[0];
			if(typeof(window[wmslay]) !== "undefined" && window[wmslay].attributeFilter){
				wmsLabelLayer.setCustomParameters({"ts":new Date().getTime()},{'addfilter':JSON.stringify(window[wmslay].attributeFilter)});
				break;
			}
		}
		
		wmsLabelLayer.resume();
	
		
	}
}

function initLayersHTML(callback){
	layersOutputStr = '';
	// NOTE THESE WILL NEED EVENT HANDLERS REFRESHED OR WHATEVER ONCE APPLIED
	for(var i=0; i < layerDefs.length; i++){ // this loop is top level only. There will be no parent layers of these.
		if(typeof(layerDefs[i].childLayers) !== "undefined"){ // found child layers obj
			if(layerDefs[i].childLayers !== null){
				if(layerDefs[i].childLayers.length > 0){
					// child layers obj has stuff in it
					layersOutputStr += addLayerHTML(layerDefs[i], true, false);
				} else {
					// child layers obj is empty...
					layersOutputStr += addLayerHTML(layerDefs[i], false, false);
				} 
			} else {
				layersOutputStr += addLayerHTML(layerDefs[i], false, false);
			}
		} else {
			// no child layers
			layersOutputStr += addLayerHTML(layerDefs[i], false, false);
		}
	}
	
	function addLayerHTML(layer, isParent, isChild){

		// Vars to be reinitiallized each loop. They are either sent as a html/css class name or just and empty string
		var checkboxStateStr = "", partialCheckboxStr = "0", parentClassStr = "", childClassStr = "", imageStr = "", childStr = "", checkboxHideStr = "", printLegendStr = "printLegend", radioStr = "",  linkedLayerStr = "", wmsLegendStr = "", isPrivate = false, colorRampStr = "";
		// conditional stuff

		if(typeof(layer.privateLayer) !== "undefined"){
			if(layer.privateLayer === true){
				isPrivate = true;
			}
		}
/*
		//check if layer is hosted on ago and is imagery, remove layer and don't build a toc entry
		if(window[layer.id] && window[layer.id].url && window[layer.id].url.indexOf('blE8NdGZSd9Sfngv') > -1 && window[layer.id].url.toLowerCase().indexOf('imagery') > -1){
			map.removeLayer(window[layer.id]);
			return '';
		}*/

		if(layer.checkboxState == "checked" && (isPrivate === false || params.pdf === "1")){
			if (layer.id.substr(0,7) == 'imagery' && layer.id.substr(0,13) != 'imageryLayers' || layer.radio == true){
				checkboxStateStr = " fa-dot-circle-o"; // child
			} else {
				checkboxStateStr = " fa-check-square-o"; // parent
			}
		} else if (layer.checkboxState == "partial"){
			checkboxStateStr = " fa-square-o";
			partialCheckboxStr = "1";
		} else {
			if (layer.id.substr(0,7) == 'imagery' && layer.id.substr(0,13) != 'imageryLayers' || layer.radio == true){
				checkboxStateStr = " fa-circle-o"; // child
			} else {
				checkboxStateStr = " fa-square-o"; // parent
			}
		}
		
		if(typeof(layer.checkboxHide) !== "undefined"){
			if(layer.checkboxHide === true){
				checkboxHideStr = " hidden";
			}
		}
		
		if(isParent === true){
			parentClassStr = " legendLayerParent"; 
		}
		if(isChild === true){
			childClassStr = " legendLayerChild";
		}
		
		if(typeof(layer.hidePrtLegend) !== "undefined"){
			if(layer.hidePrtLegend === true){
				printLegendStr = "";
			}
		}
		
		if(typeof(layer.linkedLayerIds) !== "undefined"){
			linkedLayerStr = ' linkedlayerids="' + layer.linkedLayerIds + '"';
		}

		if(layer.radio){
			radioStr = 'radioCheckbox';
		}
		
		if(typeof(layer.wmsLayer) !== "undefined"){
			// set the 'wms' attribute on the html element
			wmsLegendStr = ' wms="' + layer.wmsLayer + '"';
			
			// add the layer name to visible layers array
			// when the loop for all layer defs completes, the visible layer array gets applied
			if(checkboxStateStr === " fa-check-square-o"){
				wmsVisibleLayers.push(layer.wmsLayer);
			}
		}
		if(layer.name && window[layer.id] && window[layer.id].url && window[layer.id].url.indexOf('fetchgis.com/geoservices') > -1 && window[layer.id].url.indexOf('/MapServer') > -1){//give our tile layer "name"
			window[layer.id].name=layer.name;	
		}
		
		// create layer images if not a parent, or child string if a parent
		if(isParent === false){
			// if not a parent, loop through images and build a string, else build the child strings
			if(layer.images == "dynamic"){
				imageStr += '<div class="toc-dynamic" id="'+layer.id+'_toc-dynamic"><div class="legendThumbnail">No Data</div></div>';
			} else {
				for(var j = 0; j < layer.images.length; j++){
					colorRampStr = " "; // if graphic is a color ramp, add the colorRamp css class to allow extra icon width
					if(typeof(layer.images[j].colorRamp) !== "undefined"){
						if(layer.images[j].colorRamp === "true"){
							colorRampStr = ' class="legendColorRampDiv"';
						}
					}
					if(layer.images[j].fontColor !== undefined){
						imageStr += '<div class="legendThumbnail"><div' + colorRampStr + '><img src="' + layer.images[j].imageURL + '"></div><span  style="color: ' + layer.images[j].fontColor + '">' + layer.images[j].imageDesc + '</span></div>';
					} else {
						imageStr += '<div class="legendThumbnail"><div' + colorRampStr + '><img src="' + layer.images[j].imageURL + '"></div> ' + layer.images[j].imageDesc + '</div>';
					}
					//childStr = ""; // an array of empty strings for all children, otherwise, we have undefined stuff
				}
			}

		} else {
			imageStr = "";
			subOutputStr = "";
			for(var ii = 0; ii < layer.childLayers.length; ii++){
				//subOutputStr += addLayerHTML(layer.childLayers[ii]);
				
				// code coppied from above, but this time we know they're always child layers
				if(typeof(layer.childLayers[ii].childLayers) !== "undefined"){ // found child layers obj
					if(layer.childLayers[ii].childLayers !== null){
						if(layer.childLayers[ii].childLayers.length > 0){
							// child layers obj has stuff in it
							subOutputStr += addLayerHTML(layer.childLayers[ii], true, true);
						} else {
							// child layers obj is empty...
							subOutputStr += addLayerHTML(layer.childLayers[ii], false, true);
						} 	
					} else {
						subOutputStr += addLayerHTML(layer.childLayers[ii], false, true);
					}
				} else {
					// no child layers
					subOutputStr += addLayerHTML(layer.childLayers[ii], false, true);
				}
				
			}
			childStr = subOutputStr;
		}
		// if statement required to not have duplicate IDs for parent checkboxes
		var privateLayerStr = '';
		if(layer.privateLayer !== undefined){
			if(layer.privateLayer === true){
				privateLayerStr = 'privateLayer';
			}
		}
		
		var opSliderStr = '<div id="' + layer.id + 'OpSlider" class="opSlider hideMe"></div>'; //build for every layer, will remove after layer loads if not polygon
		if(layer.opSlider){ //honoring old settings, ie not polygon layers
			window[layer.id].opSlider = true;
		}
		if(typeof(layersOpacityObj[layer.id]) === "undefined"){
			if(typeof(layer.opSlider) === "number"){ //we will honor old settings
				layersOpacityObj[layer.id] = layer.opSlider; // build the associative array of layers with adjustable opacity to be populated once the layers load
			} else {
				layersOpacityObj[layer.id] = null; // build the associative array of layers with adjustable opacity to be populated once the layers load based on the services opacity
			}
		} 
		if((isParent || (isChild && layer.filterField))){ //dont need a slider here, ie at imagerytoc, there is a parent which holds the different imagery layers, we don't want a slider at that parent
			opSliderStr = '';
		}
		if(isParent && layer.childLayers && layer.childLayers.every(function(cl){return cl.filterField})){
			let layerId = layer.id.slice(0,layer.id.length-1);
			if(layer.childLayers.every(function(cl){return cl.id == layerId})){ //all same layer
				//keep this so all filter fields use one slider
				opSliderStr = '<div id="' + layer.id + 'OpSlider" class="opSlider hideMe parentOfFilterLayers"></div>'
			}
		
		}
		
		var optionsPopupStr = '';
		var layerNameStr = '<span id="'+layer.id+'_legendLabel" class="legendLabel"> ' + layer.name + '</span>';
		
		if(isParent == false){
			
			var description = getLayerDescription(layer);
			if(description != ""){
				optionsPopupStr = '<div class="legendOptionsContainer" id="' + layer.id + 'Options"><div class="legendOptionsCarrot"></div><div class="legendLayerOptions">' +
					'<p>' + description + '</p>' +
				'</div></div>';
				var layerNameStr = '<span id="'+layer.id+'_legendLabel" class="legendLabel legendOptionsLink" data-target="#' + layer.id + 'Options" href="javscritp:void(0)">' + layer.name + '</span>';
			}
		}
		//$("body").append(optionsCollapseStr);
		
		if(layer.id === ""){
			return 	optionsPopupStr + '<div class="legendLayerContainer '+ privateLayerStr + (layer.hidden ? 'hideMe' : '') +'">' +
					'<div class="fa fa-caret-right fa-2x legendLayerCaret"></div>'+

					'<div id="" class="fa ' + checkboxStateStr + ' legendCheckbox '+ parentClassStr + ' ' + childClassStr + ' ' + radioStr + ' ' + checkboxHideStr + ' ' + printLegendStr + '"' + wmsLegendStr + '></div><span class="fa fa-stop checkBoxInner '+ checkboxHideStr + '" style="opacity: ' + partialCheckboxStr + '"></span>' + layerNameStr + 

					/*'<div id="" class="fa ' + checkboxStateStr + ' legendCheckbox '+ parentClassStr + ' ' + childClassStr + ' ' + printLegendStr + '"' + wmsLegendStr + '></div><span class="fa fa-stop checkBoxInner" style="opacity: ' + partialCheckboxStr + '"></span>'  + layerNameStr +*/

					'<div class="legendLayerAccordion" style="display: none">' +
						'' + imageStr + '' + childStr + // image OR child, but one should always be an empty string.
					'</div>' +
				'</div>';
		} else {

			var id = layer.id;
			var dataAttr = "";
			if(layer.filterField && layer.filterValue || layer.filterShowAll){ // filter functions instead of turn off turn on functionality
				/*var layerObj = window[layer.id];
				console.log(layer);
				if(layerObj.visible === true){ // we're just going to do this here - if the filter layer has visibility set to true then we add the filters on load. - connor - 3/4/2019
					layerObj.addFilter(layer.filterField, layer.filterValue, layer.filterExclude);
				}*/
				id = getFilterLayerCheckboxId(layer);
				dataAttr = 'data-filtershowall=\''+(layer.filterShowAll ? true : false)+'\' data-filterField=\''+(layer.filterField && typeof(layer.filterField) == 'object' ? JSON.stringify(layer.filterField).replace(/'/g, '"') : layer.filterField ? layer.filterField : '')+'\' data-filterExclude=\''+(layer.filterExclude ? layer.filterExclude : false)+'\' data-filtervalue=\''+(layer.filterValue ? JSON.stringify(layer.filterValue).replace(/'/g, '"') : '')+'\'';
			} else if (layer.renderer){
				id = getAttributeRendererCheckboxId(layer);
				dataAttr = 'data-renderer="'+layer.renderer+'"';
			}

			return 	optionsPopupStr + '<div class="legendLayerContainer '+ privateLayerStr + (layer.hidden ? 'hideMe' : '') +'">' +
					'<div class="fa fa-caret-right fa-2x legendLayerCaret"></div>'+

					'<div id="' + id + 'Checkbox" layerid="'+layer.id+'" '+dataAttr+' uniqueid="'+layer.uniqueId+'" class="fa ' + checkboxStateStr + ' legendCheckbox '+ parentClassStr + ' ' + childClassStr + ' ' + radioStr + ' ' + checkboxHideStr + ' ' + printLegendStr + '"' + linkedLayerStr + wmsLegendStr + '></div><span class="fa fa-stop checkBoxInner' + checkboxHideStr + '" style="opacity: ' + partialCheckboxStr + '"></span>' + layerNameStr +

					'<div class="legendLayerAccordion" style="display: none">' +
					opSliderStr +
						'' + imageStr + '' + childStr + // image OR child, but one should always be an empty string.
					'</div>' +
				'</div>';
		}
	}
	
	if(typeof(wmsVisibleLayers[0]) !== "undefined"){
		//wmsVisibleLayers.push('wValvesLayer');
		wmsLabelLayer.suspend();
		wmsLabelLayer.setVisibleLayers(wmsVisibleLayers);	
		
		updateWmsLabelLayer();
	}
    if(typeof(wmsLabelLayer) !== "undefined"){
		//dont think using anymore
        wmsLabelLayer.fg_filterLAYERS = function(){
            function goFilter(layerObj){
                if(typeof(layerObj.childLayers) !== "undefined" && layerObj.childLayers !== null && layerObj.childLayers.length > 0){//recurse
                    for (var z = 0; z < layerObj.childLayers.length; z++){
                        if(typeof(layerObj.childLayers[z]) !== "undefined"){
                            goFilter(layerObj.childLayers[z]);
                        }
                    }
                }else{
                    if(typeof(layerObj.wmsLayer) !== "undefined" && typeof(window[layerObj.id]) !== "undefined" && typeof(window[layerObj.id].visible) !== "undefined" && window[layerObj.id].visible){//if has wms and is on
                        if(typeof(window[layerObj.id].currentMode) != "undefined" && !window[layerObj.id].filterLayer && window[layerObj.id].currentMode==0 && !window[layerObj.id].updating){//if snapshot mode
                            //check if any features are within extent
                            var doesIntersect=false;
                            if(window[layerObj.id].visibleAtMapScale){//important to add this here so it gets removed from wms vis layers list
                                for (var y = 0; y < window[layerObj.id].graphics.length; y++){
                                    try{
										if(typeof window[layerObj.id].graphics[y].geometry !='undefined'){
											doesIntersect = geometryEngine.intersects(window[layerObj.id].graphics[y].geometry,map.extent);
										}else{
											doesIntersect=false;
										}
                                        
                                    }catch(e){
                                        console.log('wms filter layers problem intersecting a feature in '+ layerObj.id);
                                    }
                                    if(doesIntersect){
                                        break;
                                    }
                                }
                            }
                            var match = $.inArray(layerObj.wmsLayer, wmsVisibleLayers);
                            if(!doesIntersect){
                                if(match > -1) {
                                    wmsVisibleLayers.splice(match,1);//remove
                                }
                            }else{
                                if(match === -1){
                                    wmsVisibleLayers.push(layerObj.wmsLayer);//add
                                }
                            }
                            //console.log(doesIntersect);
                        }
                    }
                }
            }
            //filter wms layers list
            //loop wms snapshot layers and determine if any features are within extent
            for (var x = 0; x < layerDefs.length; x++){
                goFilter(layerDefs[x]);

            }

            if(wmsVisibleLayers.length<=0) wmsVisibleLayers.push("dummy");//need at least one
            return wmsVisibleLayers;
        };
    }
    if(typeof(wmsLabelLayer) !== "undefined" && params.pdf==="1"){// if pdf, keeps the wms requests minimized
        wmsLabelLayer.suspend();
        var pauseWMSInterval = setInterval(function(){
                if(map.updating === false){
                    clearTimeout(pauseWMSInterval);
                    wmsLabelLayer.resume();
                }
            }, 200);
    }
	
	if(callback !== undefined){
		callback();
	}
}

$(function(){
	//var legendPopupTimeout;
	/*
	function setLegendTimeout(target){
		legendPopupTimeout = setTimeout(function(){
			$(target).hide();		
		},4000);
	}
	*/
	/// legend layers popup events
	$(document).on("click", '.legendOptionsLink', function(e){
		e.stopPropagation();
		var target = $(this).data().target;
		if($(target).css('display') == 'none'){
			$('.legendOptionsContainer').hide();
			$('.legendLayerContainer').css({"overflow":"visible"});
			$(target).show();
			$(target).find('.legendLayerOptions').perfectScrollbar();
			//setLegendTimeout(target);
		} else {
			$(target).hide();
			$('.legendLayerContainer').css({"overflow":"hidden"});
		}
	});
	
	$(document).on("click", '.legendLayerOptions', function(e){
		e.stopPropagation();
	});
	
	$(document).on("click", function(e){
		$('.legendOptionsContainer').hide();
		$('.legendLayerContainer').css({"overflow":"hidden"});
		//clearTimeout(legendPopupTimeout);
	});

});

function updateOpacity64(){
	var layersOpacityObjJSON = JSON.stringify(layersOpacityObj);
	opacity64 = LZString.compressToEncodedURIComponent(layersOpacityObjJSON);
	updateURLParams();
	//console.log(opacity64);
}



function getHiddenLinkedFeatures(){
	if(typeof(radiusLayers) !== "undefined"){
		for(var i = 0; i < radiusLayers.length; i++){
			// get hidden layers, then show() so they get all features from the server... and then hide so no one even knows this happened.
			if(window[radiusLayers[i].radiusLayerId].visible === false ){
				console.log(window[radiusLayers[i].radiusLayerId]);
				var originalMinScale = window[radiusLayers[i].radiusLayerId].minScale;
				window[radiusLayers[i].radiusLayerId].setMinScale(0);// this covers all the way out to zoom level 6.
				window[radiusLayers[i].radiusLayerId].show();
				window[radiusLayers[i].radiusLayerId].hide();
				window[radiusLayers[i].radiusLayerId].setMinScale(originalMinScale);
			}
		}
	}
	if(typeof(associatedLayers) !== "undefined"){
		for(var i = 0; i < associatedLayers.length; i++){
			// get hidden layers, then show() so they get all features from the server... and then hide so no one even knows this happened.
			if(window[associatedLayers[i].associatedLayerId].visible === false ){
				var originalMinScale = window[associatedLayers[i].associatedLayerId].minScale;
				window[associatedLayers[i].associatedLayerId].setMinScale(10000000);// this covers all the way out to zoom level 6.
				window[associatedLayers[i].associatedLayerId].show();
				window[associatedLayers[i].associatedLayerId].hide();
				window[associatedLayers[i].associatedLayerId].setMinScale(originalMinScale);
			}
		}		
	}
}

function initOpacitySliders(){
	$(".opSlider").each(function(index){
		//console.log("opSlider ID: " + opSliderId);
		var layerId = this.id.replace("OpSlider", "");
		//console.log(layerId + "loaded:" + window[layerId].loaded);
		
		if(window[layerId]){
			if(layerId != 'parcelLayers'){
				processLayer(layerId)
			} else { //we need to go through the keys in this object
				for(l in window[layerId]){
					processLayer(window[layerId][l].id);
				}
			}
		} else {
			let filterParent = $('#'+ layerId + 'OpSlider').hasClass('parentOfFilterLayers'); //ie drain fields
			if(filterParent){
				processLayer(layerId,true)
			}
		}

		function processLayer(id, override){
			if(override){ //needs a little tweaking
				let tempId = id;
				if(tempId[tempId.length-1] == 's'){ //remove the s (is layers want layer)
					tempId = tempId.slice(0,tempId.length-1);
				}
				initOpSlider(tempId,override)
			} else if(window[id].loaded === true){ //do it now
				initOpSlider(id);
			} else {
				window[id].on("load", function(){ //wait until loaded
					initOpSlider(id);
				});
			}
		}
		
		function initOpSlider(layerId, override){
			let opSliderId = layerId + 'OpSlider';
			if(override){ //needs some tweaking
				opSliderId = layerId + 'sOpSlider';
				$('#' + opSliderId).attr('alternateId',layerId); //remove the s (is layers want layer)
				if(!window[layerId]){ //still could not find layer
					override = false;
				}
			}
			if(override || (window[layerId] && (window[layerId].geometryType == "esriGeometryPolygon" || window[layerId].opSlider))){ //is polygon, or opslider is set in config
				var initialOpacity = window[layerId].opacity * 100;
			
				$('#' + opSliderId).removeClass('hideMe').slider({
					max: 100,
					min: 0,
					step: 1,
					value: initialOpacity,
					create: function(event, ui){
						if(typeof(layersOpacityObj !== "undefined")){
							var layerId = this.id.replace("OpSlider", "");
							let alt = $(this).attr('alternateId');
							if(alt){ //remove the s (is layers want layer)
								layerId = $(this).attr('alternateId');
							}
							if(layersOpacityObj[layerId] !== null){ // if null, the slider will be set to match the layer's intial opacity instead
								$('#' + layerId + 'OpSlider').slider("value", layersOpacityObj[layerId]);
							}
						}
					},
					
							slide: function(event, ui){
								var layerId = this.id.replace("OpSlider", "");
								let alt = $(this).attr("alternateId");
								if(alt){ //remove the s (is layers want layer)
									layerId = $(this).attr("alternateId");
								}
								var val = ui.value/100;
								window[layerId].setOpacity(val);
						},
											change: function(event, ui){
						//console.log(ui.value);
						var layerId = this.id.replace("OpSlider", "");
						let alt = $(this).attr('alternateId');
						if(alt){ //remove the s (is layers want layer)
							layerId = $(this).attr('alternateId');
						}
						var val = ui.value/100;
						window[layerId].setOpacity(val);
						layersOpacityObj[layerId] = ui.value;
						updateOpacity64();
						updateURLParams();
					}
				});
			} else { //dont need the slider
				$('#' + opSliderId).remove();
			}
		};
		
	});
}

function initLinkedLayers(){
	$('.legendCheckbox').each(function(index){
		if($('.legendCheckbox').eq(index).attr('linkedlayerids') !== undefined){
			var linkedLayersStr = $('.legendCheckbox').eq(index).attr('linkedlayerids');
			linkedLayersStr = linkedLayersStr.replace(/ /g, ""); // remove blank spaces
			var linkedLayersArr = linkedLayersStr.split(",");
			if($('.legendCheckbox').eq(index).hasClass('fa-check-square-o') || $('.legendCheckbox').eq(index).hasClass('fa-dot-circle-o')) {
				for(var i = 0; i < linkedLayersArr.length; i++){
					if(typeof(window[linkedLayersArr[i]].type) === "undefined"){
						lazyLoadTileLayer(window[linkedLayersArr[i]]);
					} else {
						lazyLoadLayer(window[linkedLayersArr[i]]);
					}
					window[linkedLayersArr[i]].show();
				}
			} else {
				for(var i = 0; i < linkedLayersArr.length; i++){
					window[linkedLayersArr[i]].hide();
				}
			}
			
		}
	});
}

var checkedLayers, partialLayerGroups;
function getLayerCheckboxState(){
	checkedLayers = [], partialLayerGroups = [];
	// fill arrays with names of checked and partially checked
	$('#layerControls, .insights-legend-item').find('.fa-check-square-o, .fa-dot-circle-o').each(function(){
		if($(this).prop('id') !== ""){
			checkedLayers.push($(this).prop('id'));
		}
	});
	$('#layerControls, .insights-legend-item').find('.legendLayerParent').each(function(){
		if($(this).next().css('opacity') == "1"){
			partialLayerGroups.push($(this).prop('id'));
		}
	});
	var checkedLayersStr = "", partialLayerGroupsStr = "";
	for(var i = 0; i < checkedLayers.length; i++){
		checkedLayersStr += checkedLayers[i] + '_';
	}
	for(var i = 0; i < partialLayerGroups.length; i++){
		partialLayerGroupsStr += partialLayerGroups[i] + '_';
	}
	
	activeLayers = checkedLayersStr.slice(0, -1);// remove last underscore
	partialLayerGroups = partialLayerGroupsStr.slice(0, -1);
	updateURLParams();
}

// automated test for duplicate ID's while developing config files
// step 1: determine if we are not in app or beta
var host = window.location.host;
if(host.indexOf("app") === -1 && host.indexOf("beta") === -1){
	// step 2, get all Id's.
	var layerIdArr = [];
	function findLayerIds(layerDefObj) {
		for(var i = 0; i < layerDefObj.length; i++){
			layerIdArr.push(layerDefObj[i].id);
			if(typeof(layerDefObj[i].childLayers) !== "undefined"){
				if(layerDefObj[i].childLayers !== null){
					if(layerDefObj[i].childLayers.length > 0){
						findLayerIds(layerDefObj[i].childLayers);
					}
				}
			}
		}
	}
	
	setTimeout(function(){
		/*findLayerIds(layerDefs);
		var foundDuplicates = [];
		// step 3: compare each Id to each other ID and show an alert if duplicate found
		for(var i = 0; i < layerIdArr.length; i++){
			for(var j = 0; j < layerIdArr.length; j++){
				if(i !== j){// make sure we're not comparing anything to itself
					if(layerIdArr[i] === layerIdArr[j]){
						var match = $.inArray(layerIdArr[i], foundDuplicates);
						if(match === -1){
							foundDuplicates.push(layerIdArr[i]);
						}
					}
				}
			}
		}*/
		var outputMessage = "";
		var checkboxIds = [];
		$(".legendCheckbox").each(function(n, i){
			var id = $(this).attr("id");
			if(checkboxIds.indexOf(id) != -1){
				outputMessage += id + "\n";
			} else {
				checkboxIds.push(id);
			}
		});

		if(outputMessage != ""){
			alert("Duplicate Id's found in TOC layer defs:\n" + outputMessage);
		}
		/*
		// build output message if there were any duplicates found
		if(foundDuplicates.length > 0){
			var outputMessage = "Duplicate Id's found in TOC layer defs:\n";
			for (var i = 0; i < foundDuplicates.length; i++){
				outputMessage += foundDuplicates[i] + "\n";
			}
			//alert(outputMessage);
		}*/
	}, 5000);
}

	
	// this reprojects a point using the esri projection module if it can, if not it falls back on proj4js
	function projectGeometry(geometry, toProjection, useProj4){ // toProjeciton should be esri spatial reference object - geometry should be a point (for now 1/21/2019)
		if(geometry.type != "point" && !projection.isSupported()){
			console.log("Non point geometry cannot be input for projectGeometry()."); // lets make sure no one does this wrong
		}
		var projected;
		if (!projection.isSupported() || useProj4) { // esri projection not supported (prob i.e.)
			try {
				if(!this.projStore){
					this.projStore = {}; // so we dont have ot keep requesting them from the service
				}
				var fromWkid = geometry.spatialReference.wkid ? geometry.spatialReference.wkid.toString() : geometry.spatialReference.wkt,
					toWkid = toProjection.wkid.toString();
				// handle some proj4js probs
				if(fromWkid == '2251' || fromWkid == '2252' || fromWkid == '2253' || fromWkid == '54032' || fromWkid == '4802' || fromWkid == '28992'){
					fromWkid = "EPSG:"+fromWkid;
				}
				if(fromWkid == '102100'){
					fromWkid = "GOOGLE";
				}
				if(toWkid == '2251' || toWkid == '2252' || toWkid == '2253' || toWkid == '54032' || toWkid == '4802'){
					toWkid = "EPSG:"+toWkid;
				}
				if(toWkid == '102100'){
					toWkid = "GOOGLE";
				}
				var fromProj4js, toProj4js;
				if(this.projStore[fromWkid]){
					fromProj4js = this.projStore[fromWkid];
				} else {
					fromProj4js =new Proj4js.Proj(fromWkid);
					this.projStore[fromWkid] = fromProj4js;
				}
				if(this.projStore[toWkid]){
					toProj4js = this.projStore[toWkid]; 
				} else {
					toProj4js = new Proj4js.Proj(toWkid);
					this.projStore[toWkid] = toProj4js;
				}
				var transform = Proj4js.transform(fromProj4js, toProj4js, new Proj4js.Point(geometry.x, geometry.y));
				projected = new Point(transform.x, transform.y, toProjection);
			} catch(error) {
				console.log(error);
			}
			
		} else {
			// just use esri implementation
			if(toProjection == 90000){
				toProjection ={'wkt':'PROJCS["dowtnz9",GEOGCS["GCS_WGS_1984",DATUM["D_WGS_1984",SPHEROID["WGS_1984",6378137.0,298.257223563]],PRIMEM["Greenwich",0.0],UNIT["Degree",0.0174532925199433]],PROJECTION["Local"],PARAMETER["False_Easting",0.0],PARAMETER["False_Northing",0.0],PARAMETER["Scale_Factor",1.0],PARAMETER["Azimuth",359.4],PARAMETER["Longitude_Of_Center",3.78701],PARAMETER["Latitude_Of_Center",51.348036],UNIT["Meter",1.0]]'}
			}
			projected = projection.project(geometry, new SpatialReference(toProjection));
		}
		return projected;
	}

	popup = new Popup({
		markerSymbol: new SimpleMarkerSymbol(SimpleMarkerSymbol.STYLE_SQUARE, 14,
			new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID,
			new Color([255,0,0]), 1),
			new Color([0,255,0,0.25])),
		lineSymbol: new SimpleLineSymbol(SimpleLineSymbol.STYLE_DASH,
			new Color([255, 0, 0]), 4),
		fillSymbol: new SimpleFillSymbol(SimpleFillSymbol.STYLE_SOLID,
			new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID,
			new Color([255, 0, 0]), 2),
			new Color([255, 255, 0, 0.05])),
		pagingControls: false,

					  pagingInfo: false
		
	}, domConstruct.create("div"));
    
    function openToolTip(evt, content){
		if(isMobile === false){
			popDialog.setContent(content);
            $(".dijitTooltipConnector").show();
			domStyle.set(popDialog.domNode, "opacity", 0.85);
			if(evt.screenPoint.y < 90){
				dijitPopup.open({
					popup: popDialog,
					around: evt.graphic._shape.rawNode,
					orient: ["below-centered", "above-centered"]
				});
			} else {
				dijitPopup.open({
					popup: popDialog,
					around: evt.graphic._shape.rawNode,
					orient: ["above-centered", "below-centered"]
				});
			}
		}
        
    }
    function openToolTip2(evt, content){
		if(isMobile === false){
			popDialog.setContent(content);


			domStyle.set(popDialog.domNode, "opacity", 0.85);
			if(evt.screenPoint.y < 90){
				dijitPopup.open({
					popup: popDialog,
					//around: evt.graphic._shape.rawNode,
					x: evt.pageX,
					y: evt.pageY,
					orient: ["below-centered", "above-centered"]
				});
			} else {
				dijitPopup.open({
					popup: popDialog,
					//around: evt.graphic._shape.rawNode,
					 x: evt.pageX,
					  y: evt.pageY,
					orient: ["above-centered", "below-centered"]
				});
			}
            $(".dijitTooltipConnector").hide();
		}
       
    }

	domClass.add(popup.domNode, "myTheme");
	
	var lastBasemap = "";

	map = new Map("map", {
	  center: mapInitCenter,
	  zoom: mapInitZoom,
	  minZoom: 6,
	  basemap: "osm",	  	  maxZoom: 20,	  showAttribution: true,
	  infoWindow: popup,
	  slider: true,
	  showLabels: true,
      fadeOnZoom: !isMobile, //ios really hates this (crashes)
	  lods:[{"level":0,"scale":591657527.591555,"resolution":156543.033928,"startTileRow":0,"startTileCol":0,"endTileRow":0,"endTileCol":0,"_frameInfo":[1,0,0,256]},{"level":1,"scale":295828763.795777,"resolution":78271.5169639999,"startTileRow":0,"startTileCol":0,"endTileRow":1,"endTileCol":1,"_frameInfo":[2,0,1,512]},{"level":2,"scale":147914381.897889,"resolution":39135.7584820001,"startTileRow":0,"startTileCol":0,"endTileRow":3,"endTileCol":3,"_frameInfo":[4,0,3,1024]},{"level":3,"scale":73957190.948944,"resolution":19567.8792409999,"startTileRow":0,"startTileCol":0,"endTileRow":7,"endTileCol":7,"_frameInfo":[8,0,7,2048]},{"level":4,"scale":36978595.474472,"resolution":9783.93962049996,"startTileRow":0,"startTileCol":0,"endTileRow":15,"endTileCol":15,"_frameInfo":[16,0,15,4096]},{"level":5,"scale":18489297.737236,"resolution":4891.96981024998,"startTileRow":0,"startTileCol":0,"endTileRow":31,"endTileCol":31,"_frameInfo":[32,0,31,8192]},{"level":6,"scale":9244648.868618,"resolution":2445.98490512499,"startTileRow":0,"startTileCol":0,"endTileRow":63,"endTileCol":63,"_frameInfo":[64,0,63,16384]},{"level":7,"scale":4622324.434309,"resolution":1222.99245256249,"startTileRow":0,"startTileCol":0,"endTileRow":127,"endTileCol":127,"_frameInfo":[128,0,127,32768]},{"level":8,"scale":2311162.217155,"resolution":611.49622628138,"startTileRow":0,"startTileCol":0,"endTileRow":255,"endTileCol":255,"_frameInfo":[256,0,255,65536]},{"level":9,"scale":1155581.108577,"resolution":305.748113140558,"startTileRow":0,"startTileCol":0,"endTileRow":511,"endTileCol":511,"_frameInfo":[512,0,511,131072]},{"level":10,"scale":577790.554289,"resolution":152.874056570411,"startTileRow":0,"startTileCol":0,"endTileRow":1023,"endTileCol":1023,"_frameInfo":[1024,0,1023,262144]},{"level":11,"scale":288895.277144,"resolution":76.4370282850732,"startTileRow":0,"startTileCol":0,"endTileRow":2047,"endTileCol":2047,"_frameInfo":[2048,0,2047,524288]},{"level":12,"scale":144447.638572,"resolution":38.2185141425366,"startTileRow":0,"startTileCol":0,"endTileRow":4095,"endTileCol":4095,"_frameInfo":[4096,0,4095,1048576]},{"level":13,"scale":72223.819286,"resolution":19.1092570712683,"startTileRow":0,"startTileCol":0,"endTileRow":8191,"endTileCol":8191,"_frameInfo":[8192,0,8191,2097152]},{"level":14,"scale":36111.909643,"resolution":9.55462853563415,"startTileRow":0,"startTileCol":0,"endTileRow":16383,"endTileCol":16383,"_frameInfo":[16384,0,16383,4194304]},{"level":15,"scale":18055.954822,"resolution":4.77731426794937,"startTileRow":0,"startTileCol":0,"endTileRow":32767,"endTileCol":32767,"_frameInfo":[32768,0,32767,8388608]},{"level":16,"scale":9027.977411,"resolution":2.38865713397468,"startTileRow":0,"startTileCol":0,"endTileRow":65535,"endTileCol":65535,"_frameInfo":[65536,0,65535,16777216]},{"level":17,"scale":4513.988705,"resolution":1.19432856685505,"startTileRow":0,"startTileCol":0,"endTileRow":131072,"endTileCol":131072,"_frameInfo":[131072,0,131071,33554432]},{"level":18,"scale":2256.994353,"resolution":0.597164283559817,"startTileRow":0,"startTileCol":0,"endTileRow":262143,"endTileCol":262143,"_frameInfo":[262144,0,262143,67108864]},{"level":19,"scale":1128.497176,"resolution":0.298582141647617,"startTileRow":0,"startTileCol":0,"endTileRow":524288,"endTileCol":524288,"_frameInfo":[524288,0,524287,134217728]},{"level":20,"resolution":0.14929107082380833,"scale":564.248588,"startTileRow":0,"startTileCol":0,"endTileRow":1048576,"endTileCol":1048576,"_frameInfo":[1048576,0,1048575,268435456]},{"level":21,"resolution":0.07464553541190416,"scale":282.124294,"startTileRow":0,"startTileCol":0,"endTileRow":2097152,"endTileCol":2097152,"_frameInfo":[2097152,0,2097151,536870912]},{"level":22,"resolution":0.03732276770595208,"scale":141.062147,"startTileRow":0,"startTileCol":0,"endTileRow":4194304,"endTileCol":4194304,"_frameInfo":[4194304,0,4194303,1073741824]},{"level":23,"resolution":0.01866138385297604,"scale":70.5310735,"startTileRow":0,"startTileCol":0,"endTileRow":8388608,"endTileCol":8388608,"_frameInfo":[8388609,0,8388608,2147483649]},{"level":24,"resolution":0.00933069192648802,"scale":35.26553675}]
	});

	/// keep the below code for getting toc images
	/*
	var legend = new Legend({
		map: map
	}, "legendDiv");
	legend.startup();
*/
	if(isFEHapp){ //set the highlight around feature to a circle
		popup.set('anchor', 'right'); //normalize so we can position it correctly
		popup.set('offsetX', 10);
		popup.set('offsetY', 10);
		var lastLayer = null; //keeping track so we can ensure all graphics get shown again
		var newGraphicLayer = new GraphicsLayer({id:'selectedPointLayer'}); //holds our new graphic
		var newPointMarker = new PictureMarkerSymbol();
		newPointMarker.setWidth(24); //setting our new marker
		newPointMarker.setHeight(36);
		newPointMarker.setUrl("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAABSCAYAAAAWy4frAAAOyElEQVR4nN2ba2ylx1nHfzPzXs7xsb3eXe+uk90tSRCplKbpFyAVSBSkorZJIWlEHBRnt6pAEVJFxAckUFRFpKIRDaJtLqVq1QoSk22zK+0NNemqUWkrCk0/RBUqiEDSZLMXe318O7bP5b3MPHyY95y1d+3j23EQPNJI9jnvO/P85z/Pf2aemQP/T0z1usKXxsYOGV05WNbRIVEccuIOa8SgzNtKuNh06UXr6peW7rlnYnR01Paq3Z4AOXfk4TsjHdwPjGqlDm+w6VknnLJivzX78d/6p+2C2jKQl8bGDpWDXX8CjCpY4bxSCh1owiAgiAIA8jQny3Nc7hCRlU4oXYCSJz/8/Jf/ayv+bBrI80eOVPZL5c8ipf9Ua8rtz0vlmPJAmcpgHyoMutYheU5rqUVzqUl9sdn53DmaSvO3SaP6xEdPnJjdMSD/+MAffqq/FD0u4hkII8Pg7gHvfKBX8VhAiiaUgFqlOQfNxQZz0zWy1I8uQaqBNp+7ctdvPLvRIbchIE9/7I/j9++XLzuxfwBglGL3/iH6d1dW+p1bXGoR63xx1zRWYNVRgAo1+hrmlubqzE3NY4uhZ0VemqQ+enR8vL5tIN+5//49pjR8Uis+BDA42MfuA7tRxr8qVnBJhkvz6xzfiOlQY/riFfXNV2vU5pcAyJ38m5XFu+964YWLWwbyytFP34qzL4tStwDsH9lN/1C/b1AcrpVhm9nmvV/FdGQ8IO1pay42uHJ5BiegFdVc7O98ZPxrr24ayEtjY4diM/iaE/YZpRh5zzBxuQSAS1rkDetjoJemFKYUYMoxAEmzxdSFaTInGFRzPl381fteHP/ZhoE8f+RI5YBU/kUrdUeoFTfcfAATGABsPcGlPZvHVjUTKnS/F0SbWyYuVMlSixL5eZ7M/MpqiraK1MBwXvoHQe4QJew/POxBOEdea+BaKTi7o8UmOXmtAc5hAsPIjXvQCnLkFlMaPnn8+HGzLpDvPPRHn1Va3SsiHDiwhyiOwDmyWstPZqh3pbjct4lzBFHEgRv3IiLkzn5o19nv/fW1fq8YWq8c/fStmc3/A8Ts27uLoeEBAPKlBJfmGx0ZVys3ZoUaid38kNShIRjwsTk/vUh1pgYoG5rgtuWrgBVCnmbp55VWJo6jDgjbzHDJBkFohSmFKKNRgeooUNtEBMkyxCpcK92QXLvUki8lBP0xQ8MDLNWbNFupcWK/CNzdfq7DyLkjD99pc/djgIOH9tPXHyNZRr6QbAxDpDH95etm7yyzYC1hKVr5ggi2nuKSjcl3MNiHCjVJM+Od85MARGH42x9+/iuvwDJGcuu+JCj6+2L6KhGI4Brp+hKrFEFfhCqFHcdrs4skSUYrycit73ajFXEcUS6FDAxWiEsBphKhA8jr2brt2HqLYFeZuBQwUClTq7ewTv4G+AAUjJw78vCdWS4/Brjlpv2EcYgkKflSuj6IwRgV+P5YnK8zMTnLRPUStYU5cruyt6MwZtfAbvbtOcDI/j0M7amA1l4RFxMk7x5DQSVGlUKyJOPnb08BEAbqgx8Z/9qrAYCIvhvJ6esrEYaF1NbXpzzoi1BaY61j8vIsFycuMzV9iSxf/d00S6jOTjJbqzK/cJAbR0a44Ya9hKHB9AXkC92B5I2UMDKEoaGvHNNotEhS+/vAqxqglaajIsJAn59RXSaIE794XaPoUKMiL+eXL83yxvk3uTT59poglpu1lstT73D+4nneuTCNFa9wuhR1bVOc4DI/BAf7y4gITvgYQHDygSO3G9R7ASoDJcQ5XOpQsnbviDLovjLiLAsLCZNTk8zMXlkXwLVWnb1CEEQMVGL27RtExxrXwk+Ma5hLLcpoH8eAUeq9Jx84crvGmrsFIY5Dgrbmt5pde8aUDGDJrTBZnWdq+vKmQbRtamaCmblFmkkhCn1Bd1ZafiMWGEUchwgC1tytA6NHAMqRVx2sW1+oCsAzM4vMzE+RpK0tA7E2Z3L6MtNVv3xSwXWrjxUmAuTewXIh6QI360Cbm5zQ2VuLc126w5d2Y81Wytxcdcsg2jY3P02jvR1Qyk9FXdqXYtgHgcEJoDisE2sPAwTFLCy2Ox1KKz/pidBK0m2xcdUEK0JnFWTW2fMXPrZ91kofCETkoCCYQHvEeU7XtYP2k2UzFXK3hS3hGubE0WokRAMROlDYtEvdeQ4SYAKNEweog4ETV9FKoRXgBArZXcuMcuAEm/VmZ7jcsjwHF4JW3eO08FOr9r+uogNtLosIWVb0gFYgrFlcQWupHF2Xn9qOiQjl2A8pl9iuPohpL4d8jizQ5rK24t4UIG33sKZroLliGREghG2l6wUQIGqrkLXdxUb5YEqzDAGsuDc1wgVxjix3XrFMd/nDCS7PEeeoRAFmncDciCmtiaIQ4yziHJKts23QmhU+Cxe0grcEaBXL6c4eQtzaJfOsDPZXqFR2bRvIQP9udvf3+Wat7d62OFTR2a3EM6LgLd1MG/8KsNBoYItho8KgK7W25UEPVkJuPngTRq/DYhfT2nDjDe9h31Cxzmu0ug+r0A9nEctCowFAM0t/qsOHfu9HWutFgGbhoAm65+0ky5GmX+K/Z2SI4b0jWwayZ/cBfmFkD0oZJMt8oHcxE/oRU++sznVjJt37fT06OmqTPPueA+brLT9rBspvS7uUvN5Csgxt4H2/dCtDQ/s2DWJo1zC33nwLpdCvKPKF1rrtEhlELPP1Fg5w2O8/8vIziQYYjPvOiAi1egtEgTEFhWrNItLe2SkGyoYPvv929m2CmT1D+/nl225j364SiMLWM6RYb6xVVBgWqwpFre5B59Z+G4qtrgma3yVVJHlOvZFSKYfokO6zKyBpil0E0x8Th5pf+8D7uDh1mP98+7+pL82v+o42AYdHDnHHrb/oJzQRXKOF20DqNYh97NabGUnulW1Pvz4Ly5IP37rvoW9bJ3ft7e/nppFBn8uaq7OhtKhSfv9dijsfJZmj2cqoJynWQaUcUYkNpeiqXEueYxeTjaWJlCLc6zM7b03UmK3XATkzdvrYvbAs+WCtexql7ppZWuJQXiEIDLpUwjWbq1e83ESwSwnYHF0ug9bEoSYOY4YG4uufdw7XbGKbG89zmbJXqzy3BQhA+Er7+07iKRy79xUnct6KML2YgnPosPty/jpZbuRkM4tkM4vYhSVcvY6kKZKmuEYLu7BENrvkv2/km6pbRQE4x5X5BlYEJ+71sTPHzl0HZHR01DrnEVbnagCoIOhkSDZlzuESi23k5LUmea3pk9+Jz3Ft1lTkjxvEOaoLng0FTy9/ZkUqUAXNvwNJmtYyX8/AgY7NupK400XHChxML+Wk1oKw4AaD59YEcvTUqSnQxwGm5pcQHCoKUMVG6n+jKGNQgUFwVGuLRY8zfu1x3HXZeFHyLMB8q0WrmGV1vPUlyHbNlLyLjUxYSlMApyT70rXPXQfkk6eP/QSlXgO4sujzvrqdty02NO9WUVqjYq96E7MdpfrukbMn3lgXCIA4vggwtbjYYaUtf++mmT6vVK3EMtvwQAz5E6s9uyqQ8kP3fBP4d7iGFb8ffleKCkxnpXuxw4b8YOzs8R9uGMjo6Kg1msegYKVIU5rSu6dgndhoZswVSTmDfWw1f9cEAjB26tjJdqxMzDcQJ6i4OD4WtaNFhxGEEeKEC7Ui3SScW4uNrkAAjMijANONBs3i1MrHys4Oq7ZK1pspC4kHYnX059187Qpk7Myxc0r4EcDl4uTKsxLsGBsqCFFFUuPSot+8GcWZT53++59uGYh/IH8UYK7VvMpKX8BOsdFWx2VsuHJsPrO+n+vY2NnjP0Q4B3BpoeX1PYxQxvgzlB6WztrOCe/UfICLyItr3XbYFBAAKRRsvtWiXmRbTF/U9Z2tmCkOmmqNhLrPszlNvqZSLbcNAfnk6WM/MYozUIxbsV7nA9OzNZWOQn9cIZbLS0U8Yp9bbRbfMhCAYpy6haRFreljJejr3Wzfjo1aMy/YkARxq87iq9mGgdz34vjPRORF4GqPhWFx7U+2VXQUds5cLi60d6Tq6xtlY1NA/MP5Y4CrZ5lnxTmCcrjtIDdlv6aaWUxoWgtIMtyv/mpzvm3Cjpw98YbCPgdXe04FBh1vPfB1XCigKCYaxbm+8Ox6N+auq2ezDe/tDx5r7yJric/FBuWQ9fK1qxaKOBPHbCOlVez+MK0nN+vXpoH4nlJfB8+KiEIZhS6VNluVZ0MrRBQX622l4im/U91kXZtuHUA3P9tmZbaZI6IISnpTigv4+BJFtZ6R+eOBhX3x9bu/HQNy9NSpKYRnAS41Uj9MtMHE3bP4K+eNwKcHxTFRJMQVPLXZi8vbAgKwP86fQFhIraXa8EdlphStfkn5WituFCFQbVjPBkzJLvP5rfqzZSAfPXFiVsFTwNUeNQUr6y0M4wCUwTrhUsPHhji+sJGLyj0HAlD04FTmHNUi/WlK0TrVas8GUG1k7VvXU2rIPLsdX7YF5Oj4eF0cXwC41EiwTlAaTClcMzZMcUHNOmGy2BZo5PHtsLFtIABFT05ZEaqNYmXcTh+tIlXt7640cqwICs7PpsPf2K4f2wZydHy8rpHHASaTHCvas7LKbG/KJZQGK5qp9uEr8uQjLz+zsYuTXWzbQABm0+FvKDhvRbjS8Jf9dTleqWBKoeMQcXBpMemwEY194qu98KEnQB55+ZlEIU8CTCUZee5QSq1gxcQRSiny3FEtjredqM/06ndWPQECEI194qsKed2KMNHyZ+XtwIa2ADj/HaCQ14tEYE+sZ0BGR0etE/2XANXM0r4wakoxpjiSyy0dNrRWj/byV289AwIrU63tnjelsMNM+zOUem3s1LGTvWy7p0CWp1qrmSWxdKQ3WcaGUfK5XrYLPQYCK1Otk62rx9udv3eADdgBIHA11TqTW5o5NHP/9/Lvem09/4lr28Z/98F/FsWvDxnfV/PWgcgPjp795m/uRHs7woiv2Kda563zIOh+LLD99nbIlqdagXWPBbZrOwYErqZar/37/6S9cO+Dp1+498HTO93O9i8krmMp0V/sdBsA/wNaWV9Dtx2TSwAAAABJRU5ErkJggg==");
		var newGraphicLayerRenderer = new SimpleRenderer(newPointMarker);
		newGraphicLayer.setRenderer(newGraphicLayerRenderer);
		map.addLayer(newGraphicLayer); //add new layer
		var refreshEvent = null; //will reset graphic to hidden after editing (is a fix for when the layer uses a filter)
		popup.on("selection-change", function(e) {
			if(formViewerOverride){
				return;
			}
			var thisLayer = e.target.selectedIndex > -1 ? window[e.target.features[e.target.selectedIndex]._layer.id] : false; //do we have feature, or are we clicking away from feature
			newGraphicLayer.clear(); //remove past marker
			if(refreshEvent){ //if one has been set, clear it
				refreshEvent.remove();
				refreshEvent = null;
			}
			if(lastLayer){ //show all features from previous layer
				if(lastLayer.filterLayer){
					lastLayer.refreshFilter(); //respect current filter
				} else { //just show all
					lastLayer.graphics.forEach(function(g){
						let setVisible = true;
						if(g.hasOwnProperty('activeVisible') && !g.activeVisible){ //don't show ones that are not active
							setVisible = false;
						}
						g.visible = setVisible; 
					})
					lastLayer.redraw(); //show all
				}
				lastLayer = null;
			}
			if(thisLayer && thisLayer.id == 'foodRestaurantLayer'){
				popup.set('highlight', false);
				var thisFeat = thisLayer.graphics.find(function(g){ return g.attributes.OBJECTID == e.target.features[e.target.selectedIndex].attributes.OBJECTID});
				lastLayer = thisLayer; //this layer will now be last layer next time
				thisFeat.visible = false;
				thisLayer.redraw(); //hide this feature
				newGraphicLayer.add(new Graphic(new Point(thisFeat.geometry.x,thisFeat.geometry.y,map.spatialReference))); //add new point where old one was
				map._layers['selectedPointLayer'].show();
				if(foodRestaurantLayer.filterLayer){ //on filterlayers, the icon reshows, so we will loop one more time and make sure it doesn't
					refreshEvent = thisLayer.on('edits-complete', function(e){
						let popupGraphic = popup.getSelectedFeature(); //facility
						if(popupGraphic){
							let graphic = foodRestaurantLayer.graphics.find(function(g){return g.attributes.OBJECTID == popupGraphic.attributes.OBJECTID}); //get graphic so we can adjust visibility
							if(graphic){
								foodRestaurantLayer.refreshFilter();
								graphic.hide();
							}
						}
					})
				}
			} else {
				popup.set('highlight', true);
			}
		})
		popup.on('show', function(){
			$('.esriPopup .outerPointer.left').css('left','-7px'); //don't want to edit main.css
		})
		map.on('zoom-end', function(){ //we want to ensure that our popup is in the correct spot
			if(popup && popup.isShowing && popup.features && popup.features.length > 0){
				popup.show(new Point(popup.features[popup.selectedIndex].geometry.x, popup.features[popup.selectedIndex].geometry.y, map.spatialReference))
			}
		})
		var moveSelectPointLayer = setInterval(function(){ //wait until layers have loaded
            if($('#foodRestaurantLayer_layer').length == 1){
                clearInterval(moveSelectPointLayer);
				$('#foodRestaurantLayer_layer').after($('#selectedPointLayer_layer').detach()); //rearrange layers, place new selectedPointLayer after restaurants
            }
        }, 1000);
	}

	
				minimapLayer = new VectorTileLayer("https://www.arcgis.com/sharing/rest/content/items/de26a3cf4cc9451298ea173c4b324736/resources/styles/root.json",{
					id: "minimapLayer",
					visible: false
				});
				map.addLayer(minimapLayer);
				
	// Symbol definition for selected parcels
	var hilightedParcelSymbol = new SimpleFillSymbol(SimpleFillSymbol.STYLE_SOLID,
		new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID,
		new Color([255,0,0]), 3),new Color([255,255,0,0])
	);
	
	var defaultPrintTitle = "Bay Area GIS";
		var wait4extent;
	if(map.loaded){ // if map is loaded, make sure map's extent is ready to consume

		// just because the map onLoad event fires, doesn't mean the map is truly ready for use.
		if(typeof(map.extent) !== "undefined"){
			 wait4extent = setInterval(function(){
				if(typeof(map.extent) !== "undefined"){
					clearInterval(wait4extent);
					runMapStartupFunctions();
				}
			}, 100);
		} else {
			runMapStartupFunctions();
		}

	} else {
		dojo.connect(map, "onLoad", function() { // if map is NOT loaded, wait for that first, and then make sure map's extent is ready to consume
			// just because the map onLoad event fires, doesn't mean the map is truly ready for use.
			if(typeof(map.extent) !== "undefined"){
				 wait4extent = setInterval(function(){
					if(typeof(map.extent) !== "undefined"){
						clearInterval(wait4extent);
						runMapStartupFunctions();
					}
				}, 100);
			} else {
				runMapStartupFunctions();
			}
		});
	}



	restoreUserGraphicsLayer = function(restoredObjects){ // i pulled this function out seperately from run map startup functions so it could be overidden. - connor
		for(var i=0; i < restoredObjects.userGraphicsLayer.length; i++){
			userGraphicsLayer.add(new Graphic(restoredObjects.userGraphicsLayer[i], parcelRenderer));
		}
	};

	function runMapStartupFunctions(){

		lastBasemap = map.getBasemap();
		
		dojo.connect(map, "onExtentChange", function(extent, delta, levelChange, lod){
			var viewerLimits = new Polygon({"rings":[[[-9368770.325388892,5464390.751324644],[-9356540.400863258,5464085.003211504],[-9356540.400863258,5450784.960289878],[-9342017.36548907,5450632.086233308],[-9342475.98765878,5413789.438599839],[-9318016.138607515,5403699.750866191],[-9318169.012664085,5385966.360304024],[-9329328.818793725,5385966.360304024],[-9329175.944737155,5392845.692849693],[-9354858.786240984,5393151.440962833],[-9354705.912184414,5399572.151338791],[-9369076.073502032,5399572.151338791],[-9368770.325388892,5464390.751324644]]],"spatialReference":{"wkid":102100,"latestWkid":3857}})

								if(geometryEngine.contains(viewerLimits,extent)){ // if the extents intersect in any way return true, the graphic is within the extent
									//console.log("hide"); hide the base layer
									// hide basemap layer if imagery is turned off
									if($("#imageryLayersCheckbox").hasClass("fa-check-square-o") === true){
										//map._removeBasemap();
										try{
											map.getLayer(map.basemapLayerIds[0]).hide();
										} catch(err){
											console.log("Basemap was not ready to hide: "+ err.message);
										}
									} else if($("#baseLayersCheckbox").hasClass("fa-check-square-o")){
										try{
											// a hidden basemap is a suspended map..., so if the baesmap isnt suspended, its already visible
											if(map.getLayer(map.basemapLayerIds[0]).suspended){
												map.setBasemap(lastBasemap);
											}
										}catch(err){
											console.log(err.message);
										}
									}

									insideBoundingPolygon = true;
								} else {
									//console.log("show"); show the base layer
									if($("#baseLayersCheckbox").hasClass("fa-check-square-o")){
										// a hidden basemap is a suspended map..., so if the baesmap isnt suspended, its already visible
										try{
											if(map.getLayer(map.basemapLayerIds[0]).suspended){
												map.setBasemap(lastBasemap);
											}
										}catch(err){
											console.warn(err.message);
										}
										insideBoundingPolygon = false;
									}
								}
									});

		var mapStartupLayers = [countySwitchingLayer, imageryLayer, imagery2015Layer, imagery2010Layer, imagery2005Layer, demLayer, wetLayer, hydroLayer, noWakeLayer, municipalBoundaryLayer, streetsLayer, contoursVectorLayer, femaFloodLayer,trashPickupLayer,  sectionsLayer, sanitarySewerServicePossibleLayer, waterServicePossibleLayer, zoningBeaverLayer, zoningKawkawlinLayer, zoningHamptonLayer, zoningLayer, schoolDistrictsLayer, votingPrecinctLayer, fireDistrictsLayer, emsDistrictsLayer, commDistrictsLayer, districtsWardsLayer, bayParcelLayer, pollingPlaceLayer, parksLayer, snowParkingLayer, nonMotorTrailsLayer, proposedTrailsLayer, bcatsLayer, bcrcLayer, drainsLayer, roadProjectsLayer, municipalFacilitiesLayer,  fireStationsLayer, airportsLayer, boatLaunchesLayer, fairgroundsLayer, courthouseLayer, historicalMarkersLayer, librariesLayer, planetariumLayer, schoolsLayer, trailheadLayer,sanSewersLayer,countyWaterMainsLayer, /*hydrantsLayer, sewerManholeLayer, waterValveLayer, catchBasinLayer, waterLineLayer, sewerLineLayer,*/ geoLocationLayer, highlightLayer, measurementLayer, measurementLabelsLayer, userGraphicsLayer, offlineTileDLTempGfxLayer, selectParGraphicsLayer, parperpTempLayer, sectionLabels,schoolsDistLabels,emsDistLabels,votingDistLabels,fireDistLabels,commDistrictsLabels1,commDistrictsLabels2,wardLabels1,wardLabels2,parksLabels];
		// grab any filter layers
		window.filterLayers = $.map(mapStartupLayers, function(layer){
			return layer instanceof filterLayer ? layer : null;                        
		});

		if(filterLayers.length > 0){
			var filterLayerInitializationHandler = map.on("update-end", function(){
				filterLayerInitializationHandler.remove(); // remove event - run once
				var layersToUpdate = filterLayers.slice();
				var wait4FilterLayers = setInterval(function(){
					if(layersToUpdate.length == 0)
						clearInterval(wait4FilterLayers);
					for(var i=0; i<layersToUpdate.length; i++){
						var layer = layersToUpdate[i];
						if(layer.loaded === true){
							if(layer.visibleAtMapScale != true || layer.visible != true){
								var originalMinScale = layer.minScale,
									originalVisibility = layer.visible;
								layer.setMinScale(0);// this covers all the way out to zoom level 6.
								if(layer.filterReady == false){ // update-end not triggered yet
									layer.show();
									if(originalVisibility == false){
										layer.hide();
									}
								}
								layer.setMinScale(originalMinScale);
							}
							layersToUpdate.splice(i, 1);
						}
					}

					/*
					var filterLayesLoaded = true;
					for(var i=0; i<filterLayers.length; i++){
						var layer = filterLayers[i];
						if(layer.loaded === true){
							if(layer.visibleAtMapScale != true || layer.visible != true){
								var originalMinScale = layer.minScale;
								layer.setMinScale(0);// this covers all the way out to zoom level 6.
								if(layer.visible === false ){ // layer is not already set to default visibiliy = true
									console.log(layer.id);
									layer.show();
									layer.hide();
								}
								layer.setMinScale(originalMinScale);
							}
						} else {
							filterLayesLoaded = false;
						}
					}
					if(filterLayesLoaded === false){
						clearInterval(wait4FilterLayers);
					}*/
				}, 50);
			});
		}

		// handle startup for editable layers
		
				window.arrayOfErrors = [];
		/* // This handler should show errors for layers added individually (not EH editable layers).
		on(map, "layer-add-result", function(result){  
			console.info("layer:", result.layer);  
			if(result.error){  
			  var errMess = result.error.message;  
			  arrayOfErrors.push(errMess);  
			  console.info("Error loading: " + errMess);  
			}  
		}); */
		on(map, "layers-add-result", function(results){  
		  //console.info("layers:", results.layers);  
		  arrayUtils.map(results.layers, function(result){  
			if(result.error){  
			  arrayOfErrors.push(result.error);  
			  console.info("Error loading:" + result.error.message);  
			}  
		  });  
		});
		
		macZoom = map.getZoom(); //we are going to do our own mousewheel zooming on mac
		if(isMac){
			map.disableScrollWheel(); //disable current panning on mousewheel
			map.on('mouse-wheel', function(e){
				macZoom += e.value; //if we keep this var seperate, then we can do multiple zooms at a time, not rely on map.getZoom()
				var max = map.getMaxZoom();
				var min = map.getMinZoom();
				if(macZoom > max){ //dont zoom in past max
					macZoom = max;
				} else if (macZoom < min){ //dont zoom out past min
					macZoom = min;
				}
				map.centerAndZoom(e.mapPoint, macZoom)
			})
		}

			
		map.addLayers([countySwitchingLayer, imageryLayer, imagery2015Layer, imagery2010Layer, imagery2005Layer, demLayer, wetLayer, hydroLayer, noWakeLayer, municipalBoundaryLayer, streetsLayer, contoursVectorLayer, femaFloodLayer,trashPickupLayer,  sectionsLayer, sanitarySewerServicePossibleLayer, waterServicePossibleLayer, zoningBeaverLayer, zoningKawkawlinLayer, zoningHamptonLayer, zoningLayer, schoolDistrictsLayer, votingPrecinctLayer, fireDistrictsLayer, emsDistrictsLayer, commDistrictsLayer, districtsWardsLayer, bayParcelLayer, pollingPlaceLayer, parksLayer, snowParkingLayer, nonMotorTrailsLayer, proposedTrailsLayer, bcatsLayer, bcrcLayer, drainsLayer, roadProjectsLayer, municipalFacilitiesLayer,  fireStationsLayer, airportsLayer, boatLaunchesLayer, fairgroundsLayer, courthouseLayer, historicalMarkersLayer, librariesLayer, planetariumLayer, schoolsLayer, trailheadLayer,sanSewersLayer,countyWaterMainsLayer, /*hydrantsLayer, sewerManholeLayer, waterValveLayer, catchBasinLayer, waterLineLayer, sewerLineLayer,*/ geoLocationLayer, highlightLayer, measurementLayer, measurementLabelsLayer, userGraphicsLayer, offlineTileDLTempGfxLayer, selectParGraphicsLayer, parperpTempLayer, sectionLabels,schoolsDistLabels,emsDistLabels,votingDistLabels,fireDistLabels,commDistrictsLabels1,commDistrictsLabels2,wardLabels1,wardLabels2,parksLabels,]);

		if(sw !== false){
			map.on('update-end', function(){
				if($('#swRecordTiles').hasClass('fa-circle')){
					swSendMessage('{"recTiles":"false"}');
				}
				if($('#swRecordData').hasClass('fa-circle')){
					swSendMessage('{"recData":"false"}');
				}
			});
		}
		
		initLayerDefState();
		

		
		
		//add the overview map 
		
					var expFactor = 4;
					try{
						if(localStorage["overviewMapState"] === undefined){
							localStorage["overviewMapState"] = "hidden";
							localStorage["overviewMapExpFactor"] = 4;
						} else {
							if(localStorage["overviewMapState"] === "visible"){
								minimapLayer.setVisibility(true);
								$("#overviewMapContainer").css({height: "200px"});
								$("#overviewMapToggle").removeClass("fa-chevron-circle-down").addClass("fa-chevron-circle-up");
							}
							if(localStorage["overviewMapExpFactor"]){
								expFactor = parseInt(localStorage["overviewMapExpFactor"]);
								if(expFactor === 2){
									$("#overviewMapZoomIn").addClass("disabledButton");
								}
								if(expFactor === 6){
									$("#overviewMapZoomOut").addClass("disabledButton");
								}
							}
						}
					}catch(err){
						console.log(err);
					}
					
					overviewMapDijit = new OverviewMap({
						map: map,
						height: 176,
						width: 270,
						baseLayer: minimapLayer,
						expandFactor: expFactor
					}, dojo.byId("overviewMapDiv"));
					overviewMapDijit.startup();
					
					$( "#overviewMapZoomIn" ).on("click", function(){
						
						$("#overviewMapZoomOut").removeClass("disabledButton");
						if(expFactor >= 4){
							expFactor = expFactor - 2;
							overviewMapDijit.expandFactor = expFactor;
							overviewMapDijit._syncOverviewMap(map.extent, null, null, null);
						}
						if(expFactor === 2){
							$( "#overviewMapZoomIn" ).addClass("disabledButton");
						}
						localStorage["overviewMapExpFactor"] = expFactor;
					});
					$( "#overviewMapZoomOut" ).on("click", function(){
						
						$("#overviewMapZoomIn").removeClass("disabledButton");
						if(expFactor <= 4){
							expFactor = expFactor + 2;
							overviewMapDijit.expandFactor = expFactor;
							overviewMapDijit._syncOverviewMap(map.extent, null, null, null);
						}
						if(expFactor === 6){
							$("#overviewMapZoomOut").addClass("disabledButton");
						}
						localStorage["overviewMapExpFactor"] = expFactor;
					});
				
						$('#map_layers').addClass('pan');
		$('.panIcon').addClass('active');
		
		
					createNavToolbar();
					createDrawToolbar();
					createEditToolbar();
				        //add scalebar to map                             
		scaleBar = new Scalebar({
            map: map,
            scalebarUnit: "dual",
        });
		
		if(params.activeControl != undefined && params.activeControl != "undefined"){
			if(params.pageTitle != true){
				$('#printTitleInput').val(decodeURIComponent(params.pageTitle));
				$('#printTitle').html(decodeURIComponent(params.pageTitle));
			} else {
				$('#printTitleInput').val(defaultPrintTitle);
				$('#printTitle').html(defaultPrintTitle);
			}
			
			if(params.subTitle != true){
				$('#subTitleInput').val(decodeURIComponent(params.subTitle));
				$('#subTitle').html(decodeURIComponent(params.subTitle));
			} else {
				$('#subTitleInput').val("");
				$('#subTitle').html("");
			}
			
			if(params.pageOrientation == "landscape"){
				$('#paperOrientationLandscape').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
				$('#paperOrientationPortrait').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
				pageOrientation = "landscape";
			} else {
				$('#paperOrientationLandscape').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
				$('#paperOrientationPortrait').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
				pageOrientation = "portrait";
			}
			$('.printModeRadioSize').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
			if(params.pageSize == "letter"){
				$('#paperSizeLetter').removeClass('fa-dot-circle-o').addClass('fa-dot-circle-o');
				pageSize = "letter";
			} else if(params.pageSize == "legal"){
				$('#paperSizeLegal').removeClass('fa-dot-circle-o').addClass('fa-dot-circle-o');
				pageSize = "legal";
			} else if(params.pageSize == 'ledger') {
				$('#paperSizeLedger').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
				pageSize = "ledger";
			} else if(params.pageSize == 'A4'){
				$('#paperSizeA4').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
				pageSize = "A4";
			} else if(params.pageSize == 'A3'){
				$('#paperSizeA3').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
				pageSize = "A3";
			}
			
			if(params.northArrow){
				$("#northArrow" + params.northArrow).trigger('click');
			}
			
			// if featureEditControls was active when page was reloaded, show EH controls once the template picker is loaded
			if(params.activeControl == "featureEditControls"){
				featureEditControlsActiveOnLoad = true;
			} else if (params.activeControl == "insightControls") {
				insightsControlsActiveOnLoad = true;
			} else {
				activateControl('#' + params.activeControl);
			}
			
			// move map zoom controls so they can be placed above the layer swipe
			$('#map').after($('#map_zoom_slider'));
			$('#map').after($('.esriScalebar'));
			$('#map').after($('#scalebarBackgroundDiv'));
			
			if(loadReport == true){
				//$('#printedPage, #neatline, #generalDetailsWrapper').addClass('pdf');
				// this was causing a very sneaky error, burried in init.js. On page load, all you got from the console was "Type error: this._div is undefined
				try{
					var loadReportWhenReady = setInterval(function(){
						if(typeof getParcelData == "function"){
							generateReportPdF();
							clearInterval(loadReportWhenReady);
							clearTimeout(forgetAboutIt);
						}
					}, 200);
					var forgetAboutIt = setTimeout(function(){
						clearInterval(loadReportWhenReady);
					}, 2000);
				}catch(err){
					Console.log("Error loading report PDF: " + err.message)
				}
			}
			if(isDow){
				if(loadDowReport){
					loadDowReportFromURL();
				}
			}
			if(loadSalesReport == true){
				loadSalesReportFromURL();
			}
		} else {
			// just set the print title to default
			if(typeof(defaultPrintTitle) !== "undefined"){
				$('#printTitleInput').val(defaultPrintTitle);
				$('#printTitle').html(defaultPrintTitle);
			}
		}

		watchFontSize($("#printTitle"),20);
		watchFontSize($("#subTitle"),30);
	
		// Add shaded background to scale bar and resize it.
		$('.esriScalebar').after('<div id="scalebarBackgroundDiv"><img src="./img/scalebarBackground.png"></div>');
		// then a little leapfrog... put the scalebar in the background div after the black background
		$('#scalebarBackgroundDiv img').after($('.esriScalebar'));

		if(activeControl == 'printControls'){ //if print on load, make sure scalebar background is hidden
			$('#scalebarBackgroundDiv').addClass('hidden').hide();
		}

		resizeScalebarBackground();
		
		//Function to get drawn geometries out of url data string and restore to map
		window.restoredObjects;
		restoreBase64Graphics = function restoreBase64Graphics(){
			// Add back drawn items from previous sessions
			
			try{
				if(data64 != "" && data64 != undefined && data64 != 'undefined' && data64 != "NoXSA" && data64 !== "null"){
					var restoredObjects64 = LZString.decompressFromEncodedURIComponent(data64);
					restoredObjects = JSON.parse(restoredObjects64);
					
					if(restoredObjects.currentMap === currentMap){
						restoreUserGraphicsLayer(restoredObjects);
						if(typeof(restoredObjects.buff64) !== "undefined"){
							restoreBuffer(restoredObjects.buff64);
						}
						for(var i=0; i < restoredObjects.measurementLayer.length; i++){
							measurementLayer.add(new Graphic(restoredObjects.measurementLayer[i], parcelRenderer));
						}
						for(var i=0; i < restoredObjects.measurementLabelsLayer.length; i++){
							measurementLabelsLayer.add(new Graphic(restoredObjects.measurementLabelsLayer[i], parcelRenderer));
						}

					}
					if(!isPhone && typeof rebuildMeasureLabels != "undefined"){
						var mStartup = map.on('update-end', function(){
							mStartup.remove();
							try {
								rebuildMeasureLabels(true);
							} catch (e){
								console.log("error in rebuilding measure labels: " + e);
							}
						});
					}
				}
			} catch(err) {
				console.log(err.message);
			}
			activateDrawSubtools();		};

		
				
		// Check to see if data returned from a share link is a factor. If not, fire the restore... function.
		// If so, if the data string has come back, run the function, but if not, run an interval that checks for the data to come in (but don't let it run it forever)
		var dataShareInterval, dataShareIntervalAttempts = 0;
		if(typeof(params.data64code) === "undefined"){
			restoreBase64Graphics();
		} else {
			if(shareDataReturned){
				restoreBase64Graphics();
			} else {
				dataShareInterval = setInterval(function(){
					if(shareDataReturned){
						clearInterval(dataShareInterval);
						restoreBase64Graphics();
					} else {
						dataShareIntervalAttempts++;
						if(dataShareIntervalAttempts > 20){ // at 4 intervals per second, this is a 5 second timeout.
							clearInterval(dataShareInterval);
							showAlert("Notice", "Application state data retrieval failed (usually this is drawn graphics or measurements). Try pasting the share link into the address bar again.");
						}
					}
				}, 250);
			} 
		}
		
		
		//create link in the bottom of the popup that makes the data pane slide out, and then create a handler for it
		$('.actionList').append('<span id="showDetails">Show Details</span>');
		
		if(isDow){
            $('#showDetails').hide();//parcel popup show details link
        }
		dojo.connect(map.infoWindow,"onSelectionChange",function(){ // event handler to hide and show the 'showDetails' option where appropriate

			var waitForSelectionTimer = setTimeout(function(){ // safety timer to clear interval below in case of error
				clearInterval(waitForSelection);
			},1000);
			var waitForSelection = setInterval(function(){ // checks for the popup/infoWindow's selected feature, this feature is not available immediately so needs to be checked for and acted upon when ready
				var selectedFeature = map.infoWindow.getSelectedFeature(); // get selected feature
				if(selectedFeature){ // if not defined keep waiting
					try{
						if(selectedFeature._layer){
							if(parcelLayers[selectedFeature._layer.id] && isDow === false && parcelLayers[selectedFeature._layer.id]['lrpMapStr']){ // only show the element if not Dow viewer and is parcel layer with LRP connected
								$('#confirmMapSwitch').hide();
								$('#showDetails').show();
							} else if(selectedFeature._layer.id == 'countySwitchingLayer') { // if county switching layer is active show that message
								$('#showDetails').hide();
								$('#confirmMapSwitch').show();
							} else { // hide both
								$('#showDetails').hide();
								$('#confirmMapSwitch').hide();
							}
							clearTimeout(waitForSelectionTimer);
							clearInterval(waitForSelection);
						}
						
					} catch(err){
						console.log("waitForSelection" + err.message);
					}
				}
			},50);
		});
		
	
		$('#showDetails').on("click", function(){
			if($('#showDetails').html() === 'Show Details'){
				showDataPane();
				if(isMobile == false){
					$('#showDetails').html('Hide Details');
				}
			} else {
				hideDataPane();
				$('#showDetails').html('Show Details');
			}
		});
		
		
		// change boolean variable that decides whether a parcel is highlighted or not in pring modes needs to be in onLoad section to work. 

		
		// Use this for debug if you want :)
		//dojo.connect(map, "onClick", function(evt){
		//})

		var logoWidth = $(".mini-logo").width();
		var logoHeight = $(".mini-logo").height();

		if(logoWidth > 40){
			if(isPhone === true){
				$('.mini-logo').css({left: "10px", height: "25px", width: "auto", top: "13px"});

				// now that we have the REAL logo width, we can calculate the margin needed for the clients name
				$(".navbar-brand").css({marginLeft: ($(".mini-logo").width() - 53) + "px"});

				$('#disclaimerBanner img').css({width: "80px"});
			} else {
				$(".navbar-brand").css({marginLeft: ($(".mini-logo").width() - 35) + "px"});
			}
		}
		$('#navContainer').animate({opacity: 1}, 1000);
		
		
		// this function runs on load and zooms to a parcel and highlights it. It's mainly for Zillow and BSNA links.
		// Also, make it work with multiple parcel layers that could have the two parcels with the same PIN in the same app!
		var parRunOnceOnLoad = false, parLayer2Load = null;
		if(params.pin !== undefined){
			var targetParcelLayer;
			
			/* query the parcel layers to see if there is a parcel with this pin.
				-If only one found, check if layer is turned on, turn the layer on if needed, and zoom to it
				-If more than one found, create a dialog so the user can pick which parcel to zoom to before doing the above.
			*/
			var pLayerCount = 0, pLayerQueriesIn = 0, parcelsFound = [];
			for(var layer in parcelLayers){ // for "key" in object loop
			
				var pQuery = new query();
				var pQueryTask = new QueryTask(parcelLayers[layer].url);
				
				var pinField=parcelLayers[layer].pinField;
				if(window[layer].url.indexOf('geoservices') > -1 ){
					pinField='"'+pinField+'"';
				} 
				pQuery.where =  pinField + " = '"+ decodeURIComponent(params.pin) +"'";
				
				pQuery.returnGeometry = true;
				pQuery.outSpatialReference=map.spatialReference;
				pQuery.outFields = ["*"];
				pQuery.layer = window[layer];
		
				pLayerCount++;
				
				var _funcMaker = function(curLayer) {
					return function(result) {
						// do something with results based on type
						// that was passed to it
						pLayerQueriesIn++;
						if(result.features.length > 0){
							// pin should at least be unique per parcel layer
							console.log("parcelFound");
							console.log(result);
							parcelsFound.push({"layer": curLayer, "attributes" : result.features[0].attributes, "geometry": result.features[0].geometry}); // get the layer and attributes. These two things are enough to find the parcel in it's layer on the map.
							window.resultz = result;
						}
						
						if(pLayerCount === pLayerQueriesIn){
							if(parcelsFound.length === 1){
								goThere(parcelsFound[0].layer, 0);
							} else if(parcelsFound.length > 1){
								// construct a dialog with options to go to the parcel in the county of the users choice
								var choicesHTML = '<div style="width: 250px;"><div>We found "'+decodeURIComponent(params.pin)+'" in the following areas:</div>';
								choicesHTML += '<div id="parButtonWrapper" style="margin: 10px auto 0px;">';
								for(var j = 0; j < parcelsFound.length; j++){
									choicesHTML += '<button class="btn btn-primary btn-sm pinLoadChoice" data-layer="'+parcelsFound[j].layer+'" data-index="'+j+'">' + parcelLayers[parcelsFound[j].layer].displayName + '</button>&nbsp;';
								}
								choicesHTML += '</div><div>Simply click an area to zoom to it.</div>';
								showModal('Notice', choicesHTML);
								
								// adjust button container width to center the buttons. Could just use css width: "fit-content" but IE...
								var parButtonWidthAdjust = 0;
								$('.pinLoadChoice').each(function(index){
									parButtonWidthAdjust += $('.pinLoadChoice').eq(index).width();
								});
								$('#parButtonWrapper').width(parButtonWidthAdjust+30);
								
								// click handler for buttons
								$('.pinLoadChoice').on("click", function(e){
									console.log(e);
									var idx = parseInt(e.target.attributes["data-index"].value);
									goThere(parcelsFound[idx].layer, idx);
									closeModal();
									$('.pinLoadChoice').off('click');
								});
								
							}
						}
					};
				};
				
				pQueryTask.execute(pQuery, _funcMaker(layer));
			}
		}
		
		function goThere(layerId, layersFoundIndex){
			// turn on the parcel if it's off
			if($('#' + layerId + 'Checkbox').hasClass('fa-square-o')){
				$('#' + layerId + 'Checkbox').trigger('click');
				console.log('turned on: ' + layerId);
			}
			
			parExt = parcelsFound[layersFoundIndex].geometry.getExtent();
			map.setExtent(parExt, true)
			
			// now that we're sure the layer is on, make sure it's loaded and done updating
			var parLoadInterval = setInterval(function(){
				
				if(window[layerId].loaded === true){
					
					try{
						// start looking for the graphic... it might not be there right away
						for(var i = 0; i < window[layerId].graphics.length; i++){
							if(window[layerId].graphics[i].attributes[parcelLayers[layerId].pinField] === decodeURIComponent(params.pin)){
								clearInterval(parLoadInterval);
								var selParcel = window[layerId].graphics[i];
								
								if(typeof(selParcel) !== "undefined"){
									resultFound = true;
									selParcel.setInfoTemplate(template);
									
									goToParcel2(decodeURIComponent(params.pin), layerId);
								} else {
									showAlert('Notice:', 'This parcel has not been mapped');
									getParcelData(decodeURIComponent(params.pin), layerId); // getting parcel data doesn't happen automatically on unmapped parcels without this.
									return;
								}
							}
						}

					} catch (err){
						showAlert('Notice:', 'This parcel has not been mapped');
						getParcelData(decodeURIComponent(params.pin), layerId); // getting parcel data doesn't happen automatically on unmapped parcels without this.
						return;
					}
				}
			}, 1000);
		}
		
		// scrollbar for IE11 acts-a-fool in the popup. Allowing "stock" scrollbar for now
		if(ieVersion !== "IE 11"){
			$('#map .esriPopup .contentPane').perfectScrollbar({supressScrollX: true});

			// Set handlers on scroll bar to update when content changes.
			var observerConfig = {
				attributes: false,
				childList: true,
				characterData: false
			};

			var psPopupTargetElement = $('#map .esriPopup .contentPane').get(0);
			var psPopupObserver = new MutationObserver(function(mutations) {
				mutations.forEach(function(mutation) {
					//console.log(mutation.type);
					$(mutation.target).perfectScrollbar('update');
				});
			});
			psPopupObserver.observe(psPopupTargetElement, observerConfig);
		} else {
			$('#map .esriPopup .contentPane').css({'overflow-x':'hidden'})
		}

		map.disableKeyboardNavigation();

		// this prevents marker symbols from growing every time the user clicks a marker symbol
		popup.on('hide', function(){
			if(popup.selectedIndex	!== -1){
				//for(var i = 0; i < popup.features.length; i++){
					popup.features[0]._layer.redraw();//this will redraw the layer of the clicked feature. i think that is what we want.
				//}
			}	
		});

        // this is a sort of crazy fix for printing when zoomed in beyond the default imagery layer's native resolution. It prevents the distortion of tiles.
		map.on("zoom-end", function(){
			fixPrintResampling();
		});
		var runThisOnce = map.on("update-end", function(){
			resetMarkerHoverHander();
			// This is added here to ensure that it doesn't run too soon.			
			fixPrintResampling();
			runThisOnce.remove();
			
			// set radio button for right side layer swipe
			try{
				if($('.rightSwipe').length > 0){
					var activeImageryLayerId = $('.fa-dot-circle-o.legendCheckbox').attr('id').replace('Checkbox', '');
					$('.rightSwipe').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
					$('.rightSwipe').each(function(index){
						if($('.rightSwipe').eq(index).attr('layer') === activeImageryLayerId){
							$('.rightSwipe').eq(index).removeClass('fa-circle-o').addClass('fa-dot-circle-o');
						}
					});
				}
				
			}catch(err){
				console.log(err.message);
			}
			
			updateTOCvisibility();

			// updating snappingLayerInfos global var
			try {
				console.log("Updating snapping layer infos");
				
				window.snappingLayerInfos = function(){
					var layerInfos = [];
					for(var i=0; i<map.graphicsLayerIds.length; i++){
						var layer = window[map.graphicsLayerIds[i]];
						if(layer && layer.type!='Table'){
							if(layer.mode === 2){ // disable snapping on selection layers
								layerInfos.push({
									"layer": layer,
									"snapToEdge" : false,
									"snapToPoint" : false,
									"snapToVertex" : false
								});
							} else { // other layers are fine just through em in
								layerInfos.push({
									"layer": layer 
								});
							}
						}
					}
					return layerInfos;
				}
			} catch (e) {
				window.snappingLayerInfos = undefined; // reset so the default is used so atleast snapping still works
				console.log("Error in setting snapping layer infos: " + e);
			}
			
		});
		
		if(measurementLayer.graphics.length > 0){
			$('#clearMeasureButton, #selectMeasureButton').removeClass('disabledButton');
		}

	}


	
	dojo.connect(map, "onLayerAdd", function(e){
		//console.log("added a layer");
		if(e.type != undefined){
			if(e.type == "Feature Layer");
			//loadedFeatureLayers.push(e.id);
			
			loadedFeatureLayers = map.graphicsLayerIds;
			
			//console.log("Feature Layer loaded: " + e.id);
		}
		
	})

	window.fixPrintResampling = function(){
		/*
			-get all non-graphics layer ids.
			-check if any are resampling
			-if they are, set a rule in the head to make the tile layer a certain width
		
		*/
		setTimeout(function(){
			for(var i = 0; i < map.layerIds.length; i++){
				if(typeof(window[map.layerIds[i]]) !== "undefined"){ // check if layer is global, and if so, move on
					if(typeof(window[map.layerIds[i]].resampling) !== "undefined" ){ // check if layer supports resampling
						if(window[map.layerIds[i]].resampling === true){ // check if resampling is enabled for the layer
							var layrId = map.layerIds[i];
							if(window[map.layerIds[i]].isResampling === true){ // check if the layer is actualy in the act of resampling
								if($('#' + layrId + 'PrintFix').hasClass('exists') === false){ // check if print style was already applied
									if(typeof(params.pdf) === "undefined"){
										// css 3 3d transitions
										$("head").append('<style id="' + layrId + 'PrintFix" class="exists">@media print{ #map_' + layrId + ' div{ width: 2048px!important; } }</style>');
									} else {
										// css 2
										$("head").append('<style id="' + layrId + 'PrintFix" class="exists">@media print{ #map_' + layrId + '{ width: 2048px!important; } }</style>');
									}
								}
							} else {
								$('#' + layrId + 'PrintFix').remove();
							}
						}
					}
				}
			}
		}, 400);
	}

	window.getCenterFromFeature = function(feature, offset, isPoint){ //used to get center point for showing popups and centering map on a feature, if offset true will try to reposition centering with white data pane in mind
		var geometry = {};
		if(feature._layer){
			if(feature._layer.geometryType == "esriGeometryPolygon"){
				geometry = feature.geometry.getCentroid();
			} else if(feature._layer.geometryType == "esriGeometryPoint" || isPoint){
				geometry = feature.geometry;
			} else if(feature._layer){
				var mid = parseInt(feature.geometry.paths[0].length / 2);
				geometry.x = feature.geometry.paths[0][mid][0];
				geometry.y = feature.geometry.paths[0][mid][1];
				geometry.spatialReference = feature.geometry.spatialReference;
			} 
		} else { //if no ._layer, probably coming from geocoder
			geometry = feature.geometry;
		}
		if(offset) {
			var mapDivWidth = $("#map").width();  // Get width of the div which is partialy obscuring the mapDiv 
			var tableListWidth = 828;  // Get the min and max X coordinate of the current map  
			var minX = map.toMap(new ScreenPoint(0,0)); 
			var maxX = map.toMap(new ScreenPoint((mapDivWidth-1),0));  // Calculate the current pixel resolution 
			var pointResolution = (maxX.x - minX.x)/mapDivWidth;   // Get the correct map center 
			var currentMapCenter = (mapDivWidth - tableListWidth) / 2;  // Get the wished offset in coordinate system units 
			var newOffset = ((mapDivWidth / 2) - currentMapCenter) * pointResolution;
			if(geometry.spatialReference.wkid != 102100){ //just to make sure our offset is correct
				let newGraphic = {};
				newGraphic.geometry = {};
				newGraphic.geometry.x = parseFloat(feature.geometry.x);
				newGraphic.geometry.y = parseFloat(feature.geometry.y);
				newGraphic.geometry.spatialReference = {'wkid':geometry.spatialReference.wkid}
				geometry = projectGeometry(newGraphic.geometry, {'wkid':3857}) //the maps spaatial reference
			}
			geometry = geometry.offset(newOffset,0);
		}
		return geometry;
	}
	
		
	// Symbol definitions for SELECTED lines, points, and polygons
var selectedPolygonSymbol = new SimpleFillSymbol(SimpleFillSymbol.STYLE_SOLID,
	new SimpleLineSymbol(SimpleLineSymbol.STYLE_DASH,
	new Color([255,0,0]), 2),new Color([255,0,0,0.15])
);

var selectedLineSymbol = new SimpleLineSymbol(SimpleLineSymbol.STYLE_DASH, new Color([255,0,0]), 2);
var plineTempLineSymbol = new SimpleLineSymbol(SimpleLineSymbol.STYLE_DASH, new Color([255,0,0]), 2);
var ninetyBearingSymbol = new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID, new Color([100,100,255]), 2);

var parperpSnappingCross = new SimpleMarkerSymbol(SimpleMarkerSymbol.STYLE_CROSS, 24,
	new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID,
	new Color([0,255,255,1]), 3),
	new Color([0,255,255,1])
);

var verticeSnapMarker = new SimpleMarkerSymbol(SimpleMarkerSymbol.STYLE_X, 24,
	new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID,
	new Color([255,0,0,1]), 3),
	new Color([255,0,0,1])
);

// color picker stuff
var cpPalette = [
	["#000","#444","#666","#999","#ccc","#eee","#f3f3f3","#fff"],
	["#f00","#f90","#ff0","#0f0","#0ff","#00f","#90f","#f0f"],
	["#f4cccc","#fce5cd","#fff2cc","#d9ead3","#d0e0e3","#cfe2f3","#d9d2e9","#ead1dc"],
	["#ea9999","#f9cb9c","#ffe599","#b6d7a8","#a2c4c9","#9fc5e8","#b4a7d6","#d5a6bd"],
	["#e06666","#f6b26b","#ffd966","#93c47d","#76a5af","#6fa8dc","#8e7cc3","#c27ba0"],
	["#c00","#e69138","#f1c232","#6aa84f","#45818e","#3d85c6","#674ea7","#a64d79"],
	["#900","#b45f06","#bf9000","#38761d","#134f5c","#0b5394","#351c75","#741b47"],
	["#600","#783f04","#7f6000","#274e13","#0c343d","#073763","#20124d","#4c1130"]
];

var cpOptions = {
	showAlpha: true,
	chooseText: "apply",
	showInput: true,
	preferredFormat: "rgb",
	showPaletteOnly: true,
	togglePaletteOnly: true,
	togglePaletteMoreText: 'more',
	togglePaletteLessText: 'less',
	palette: cpPalette
};

// init all draw colorpickers
$('.drawColorPicker, .editColorPicker').spectrum(cpOptions);

function setSelectedText(txt, size, fontFamily) {
	var textSymbol = new TextSymbol(txt).setColor(new Color([255,0,0,1])).setDecoration(TextSymbol.DECORATION_LINETHROUGH).setAlign(TextSymbol.ALIGN_MIDDLE).setFont(new Font(size).setFamily(fontFamily));
	return textSymbol;
}

// not sure but this function might be dead code
function setSelectedPoint(size, style) {
	var ptStyle;
	switch(style){ //set point Style
		case "circle":
			ptStyle = SimpleMarkerSymbol.STYLE_CIRCLE;
			break;
		case "square":
			ptStyle = SimpleMarkerSymbol.STYLE_SQUARE;
			break;
		case "diamond":
			ptStyle = SimpleMarkerSymbol.STYLE_DIAMOND;
			break;
		case "cross":
			ptStyle = SimpleMarkerSymbol.STYLE_CROSS;
			break;
		default:
			ptStyle = SimpleMarkerSymbol.STYLE_X;
			break;
	}
	var pointSymbol = new SimpleMarkerSymbol(ptStyle, size,
		new SimpleLineSymbol(SimpleLineSymbol.STYLE_SHORTDASH,
		new Color([255,0,0,1]), 2),
		new Color([255,0,0, 0.25]));
	return pointSymbol;
}
	
// Call this function with no options to hide options and show selection/deletion buttons
setDrawColorOption = function setDrawColorOption(active) {
	switch(active) {
		case 'POINT':
			$('.pointOptions').show();
			$('.lineOptions').hide();
			$('.polylineOptions').hide();
			$('.polylineCogoOptions').hide();
			$('.polyOptions').hide();
			$('.snappingOptions').show().removeClass('disabledButton');
			$('.polyCogoOptions').hide();
			$('.textOptions').hide();
			$('.rectangleOptions').hide();
			//$('#drawUnitsDiv').show();
			$('#selectionButtons').hide();
			$('#selectionButtons, .deleteAllButton').hide();
			$('#ninetyOption').hide();
			break;
		case 'LINE':
		case 'FH_POLYLINE':
			$('.pointOptions').hide();
			$('.lineOptions').show();
			$('.polylineOptions').hide();
			$('.polylineCogoOptions').hide();
			$('.snappingOptions').hide();
			$('.polyOptions').hide();
			$('.polyCogoOptions').hide();
			$('.rectangleOptions').hide();
			$('.textOptions').hide();
			//$('#drawUnitsDiv').show();
			$('#selectionButtons, .deleteAllButton').hide();
			$('#ninetyOption').hide();
			break;
		case 'POLYLINE':
			$('.pointOptions').hide();
			$('.lineOptions').show();
			$('.polylineOptions').show();
			$('.polylineCogoOptions').show();
			$('.polyOptions').hide();
			$('.snappingOptions').show().removeClass('disabledButton');
			$('.polyCogoOptions').hide();
			$('.rectangleOptions').hide();
			$('.textOptions').hide();
			//$('#drawUnitsDiv').show();
			$('#selectionButtons, .deleteAllButton').hide();
			break;
		case 'POLYGON':
			$('.pointOptions').hide();
			$('.lineOptions').hide();
			$('.polylineOptions').hide();
			$('.polylineCogoOptions').hide();
			$('.polyOptions').show();
			$('.snappingOptions').show().removeClass('disabledButton');
			$('.rectangleOptions').hide();
			$('.polyCogoOptions').show();
			$('.textOptions').hide();
			//$('#drawUnitsDiv').show();
			$('#selectionButtons, .deleteAllButton').hide();
			$('#ninetyOption').hide();
			break;
		case 'EXTENT':
			$('.pointOptions').hide();
			$('.lineOptions').hide();
			$('.polylineOptions').hide();
			$('.polylineCogoOptions').hide();
			$('.snappingOptions').hide();
			$('.polyOptions').show();
			$('.rectangleOptions').show();
			$('.polyCogoOptions').hide();
			$('.textOptions').hide();
			$('#rectangleGPS').hide(); // hidden but will be shown if the feature being created is a eh feature
			//$('#drawUnitsDiv').show();
			$('#selectionButtons, .deleteAllButton').hide();
			$('#ninetyOption').hide();
			break;
		case 'CIRCLE':
		case 'ARROW':
		case 'FH_POLYGON':
			$('.pointOptions').hide();
			$('.lineOptions').hide();
			$('.polylineOptions').hide();
			$('.polylineCogoOptions').hide();
			$('.snappingOptions').hide();
			$('.polyOptions').show();
			$('.polyCogoOptions').hide();
			$('.rectangleOptions').hide();
			$('.textOptions').hide();
			//$('#drawUnitsDiv').show();
			$('#selectionButtons, .deleteAllButton').hide();
			$('#ninetyOption').hide();
			break;
		case 'TEXT':
			$('.pointOptions').hide();
			$('.lineOptions').hide();
			$('.polylineOptions').hide();
			$('.polylineCogoOptions').hide();
			$('.snappingOptions').hide();
			$('.polyOptions').hide();
			$('.polyCogoOptions').hide();
			$('.rectangleOptions').hide();
			$('.textOptions').show();
			//$('#drawUnitsDiv').hide();
			$('#selectionButtons, .deleteAllButton').hide();
			$('#ninetyOption').hide();
			break;
		default:
			$('.pointOptions').hide();
			$('.lineOptions').hide();
			$('.polylineOptions').hide();
			$('.polylineCogoOptions').hide();
			$('.snappingOptions').hide();
			$('.polyOptions').hide();
			$('.polyCogoOptions').hide();
			$('.rectangleOptions').hide();
			$('.textOptions').hide();
			$('#drawUnitsDiv').hide();
			$('#selectionButtons, .deleteAllButton').show();
			$('#ninetyOption').hide();
	}
	$('#drawControlsAccordionPane').perfectScrollbar('update');
};

// DRAW TOOL-BAR
// added to map during dojo.connect
$('#drawPoint').on("click", function(){
	if($(this).hasClass('active') === false){
		popup.clearFeatures();
		popup.hide();
		navToolbar.deactivate();
		toolbar.activate(Draw["POINT"]);
		setDrawColorOption('POINT');
	}
});
$('#drawLine').on("click", function(){
	if($(this).hasClass('active') === false){
		popup.clearFeatures();
		popup.hide();
		navToolbar.deactivate();
		toolbar.activate(Draw["LINE"]);
		setDrawColorOption('LINE');
		$("#drawUnitsDiv").show();
	}
});
$('#drawPolyline').on("click", function(){
	if($(this).hasClass('active') === false){
		popup.clearFeatures();
		popup.hide();
		navToolbar.deactivate();
		toolbar.activate(Draw["POLYLINE"]);
		toolbar.lineSymbol.color.r = 255;
		toolbar.lineSymbol.color.g = 0;
		toolbar.lineSymbol.color.b = 0;
		setDrawColorOption('POLYLINE');
		$("#drawUnitsDiv").show();

	}
});
$('#drawPolygon').on("click", function(){
	if($(this).hasClass('active') === false){
		popup.clearFeatures();
		popup.hide();
		navToolbar.deactivate();
		toolbar.activate(Draw["POLYGON"]);
		toolbar.fillSymbol.color.r = 0;
		toolbar.fillSymbol.color.g = 0;
		toolbar.fillSymbol.color.b = 0;
		toolbar.fillSymbol.outline.color.r = 255;
		toolbar.fillSymbol.outline.color.g = 0;
		toolbar.fillSymbol.outline.color.b = 0;
		setDrawColorOption('POLYGON');
		$("#drawUnitsDiv").show();
	}
});
$('#drawRectangle').on("click", function(){
	if($(this).hasClass('active') === false){
		popup.clearFeatures();
		popup.hide();
		navToolbar.deactivate();
		toolbar.activate(Draw["EXTENT"]);
		toolbar.fillSymbol.color.r = 0;
		toolbar.fillSymbol.color.g = 0;
		toolbar.fillSymbol.color.b = 0;
		toolbar.fillSymbol.outline.color.r = 255;
		toolbar.fillSymbol.outline.color.g = 0;
		toolbar.fillSymbol.outline.color.b = 0;
		setDrawColorOption('EXTENT');
		$("#drawUnitsDiv").show();
	}
});
$('#drawCircle').on("click", function(){
	if($(this).hasClass('active') === false){
		popup.clearFeatures();
		popup.hide();
		navToolbar.deactivate();
		toolbar.activate(Draw["CIRCLE"]);
		toolbar.fillSymbol.color.r = 0;
		toolbar.fillSymbol.color.g = 0;
		toolbar.fillSymbol.color.b = 0;
		toolbar.fillSymbol.outline.color.r = 255;
		toolbar.fillSymbol.outline.color.g = 0;
		toolbar.fillSymbol.outline.color.b = 0;
		setDrawColorOption('CIRCLE');
		//$("#drawUnitsDiv").show();
	}
});
$('#drawArrow').on("click", function(){
	if($(this).hasClass('active') === false){
		popup.clearFeatures();
		popup.hide();
		navToolbar.deactivate();
		toolbar.activate(Draw["ARROW"]);
		toolbar.fillSymbol.color.r = 0;
		toolbar.fillSymbol.color.g = 0;
		toolbar.fillSymbol.color.b = 0;
		toolbar.fillSymbol.outline.color.r = 255;
		toolbar.fillSymbol.outline.color.g = 0;
		toolbar.fillSymbol.outline.color.b = 0;
		setDrawColorOption('ARROW');
		//$("#drawUnitsDiv").show();
	}
});
$('#drawFreehandPolyline').on("click", function(){
	if($(this).hasClass('active') === false){
		popup.clearFeatures();
		popup.hide();
		navToolbar.deactivate();
		toolbar.activate(Draw["FREEHAND_POLYLINE"]);
		setDrawColorOption('FH_POLYLINE');
	}
});
$('#drawFreehandPolygon').on("click", function(){
	if($(this).hasClass('active') === false){
		popup.clearFeatures();
		popup.hide();
		navToolbar.deactivate();
		toolbar.activate(Draw["FREEHAND_POLYGON"]);
		setDrawColorOption('FH_POLYGON');
	}
});
$('#addTextSymbol').on("click", function(){
	if($(this).hasClass('active') === false){
		popup.clearFeatures();
		popup.hide();
		navToolbar.deactivate();
		toolbar.activate(Draw["POINT"]);
		setDrawColorOption('TEXT');
	}
});
	
$('#doneDrawing button').on("click", function(){
	cancelDrawingSelection();
});
$('#drawButton').on('click', function(){
	$('#createCircleDrainFieldButton').hide();
})
// This event handler fires for all buttons. The rest are for the individual
$('.drawButton').on("click", function (){
	if($(this).hasClass('active')){
		cancelDrawingSelection();
	} else {

		$('#textEditorContainer').fadeOut();
		//navToolbar.deactivate(); // For whatever reason, these have to be on the handlers for the IDs.
		$('#editToolbarCheckbox').prop('checked', '');
		$('.drawButton').removeClass('active');
		$(this).addClass('active');
		$(this).children().children().removeClass('hidden');
		
					$(".drawButton").each(function(index){
						if($(".drawButton").eq(index).hasClass("active") == false){
							$(".drawButton").eq(index).stop().addClass("noPointerEvents").animate({height: "0px", opacity: 0}, 400, function(){
								$("#drawOptionsDiv").css({height: "auto"});
								if($("#drawControlsScrollPane").hasClass("ps-container")){
									$("#drawControlsScrollPane").perfectScrollbar("update");
								} else {
									$("#drawControlsScrollPane").perfectScrollbar();
									setTimeout(function(){
										$("#drawControlsScrollPane").perfectScrollbar("update");
									}, 200);
								}
								$("#doneDrawing").show();
							});
						}
					});
						if($('.selectButton').hasClass('btn-primary') == false){
			// This is for when there is some stuff selected to edit/delete.
			// We cannot simply use cancelDrawingSelection() as it does too much in this instance
			$('#textEditorContainer').fadeOut();
			editToolbar.deactivate();
			//reset user drawn feature symbol styles back to original
			for(var i = 0; i < selectedGeometry.length; i++){
				for(var j = 0; j < graphicStore.length; j++){
					if (selectedGeometry[i].attributes === graphicStore[j].ID){
						selectedGeometry[i].setSymbol(graphicStore[j].Symbol);
					}
				}
			}
			selectedGeometry = [];
			graphicStore = [];
			if($('.selectButton').hasClass('btn-success')){
				$('.selectButton').removeClass('btn-success')
			}
			if($('.selectButton').hasClass('btn-danger')){
				$('.selectButton').removeClass('btn-danger')
			}
			$('.selectButton').addClass('btn-primary').html('<span class="fa fa-mouse-pointer"></span> Select');
			if(selectionHandler != undefined){
				selectionHandler.remove();
				selectionHandler = undefined;
			}
			enableGraphicHover(false);
		}
	}
});

$('#pline_Finish').on("click", function(){
	toolbar.finishDrawing();
});

$('#pline_qbRadio').on("click", function(){
	$('#revAngle').removeClass('btn-primary').addClass('btn-default disabledButton');
	$('#pline_qbRadio').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
	$('#pline_polarRadio').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
	if(drawClickHandlerActive == true){
		$('#pline_polarDimInput').addClass('hidden');
		$('#pline_qbDimInput').removeClass('hidden');
	} else {
		$('#pline_polarDimInput').addClass('hidden');
		$('#pline_qbDimInput').removeClass('hidden');
	}
});
$('#pline_polarRadio').on("click", function(){
	$('#pline_qbRadio').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
	$('#pline_polarRadio').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
	if(isNumber($('#pline_polarAngle').val()) && isNumber($('#pline_polarDistance').val()))
		$('#revAngle').removeClass('disabledButton btn-default').addClass('btn-primary');
	if(drawClickHandlerActive == true){
	  $('#pline_qbDimInput').addClass('hidden');
	  $('#pline_polarDimInput').removeClass('hidden');
	} else {
		$('#pline_polarDimInput').removeClass('hidden');
		$('#pline_qbDimInput').addClass('hidden');
	}
});
$('#pline_qbAngle').on('keydown', function(e){
	setTimeout(function(){
		if($('#pline_qbAngle').val() != "" && $('#pline_qbDistance').val() != "" ){
			$('#pline_Next').css({
				pointerEvents: "",
				opacity: '1'
			});
		} else {
			$('#pline_Next').css({
				pointerEvents: "none",
				opacity: '0.5'
			});
		}
	}, 20);
	if(e.keyCode == 13 || e.which == 13){ // keycode 13 is 'Enter' key. The word 'which' is there for compatibility. Some browsers don't like keyCode
		var qbAngle = $('#pline_qbAngle').val(); // input string from user
		var pattern = /[ns] \d{1,3} [ew]/i; // reg exp to test against

		// attempt to add spaces between numbers and letters so we can easily split later. Plus, this is what we validate for
		if(qbAngle.charAt(1) == ' '){
		}else{
		  qbAngle = qbAngle.substr(0, 1) + ' ' + qbAngle.substr(1);
		}

		var numStart = null, numEnd = null;
		for(var i=2; i < qbAngle.length; i++){
		  if(numStart == null && qbAngle.charCodeAt(i) > 47 && qbAngle.charCodeAt(i) < 58){
			numStart = i;
		  }
		  if(numStart != null && qbAngle.charCodeAt(i) > 47 && qbAngle.charCodeAt(i) < 58){
			numEnd = i;
		  }
		}

		if(qbAngle.charAt(numEnd + 1) == ' '){
		}else{
		  qbAngle = qbAngle.substr(0, numEnd + 1) + ' ' + qbAngle.substr(numEnd + 1);
		}

		if (pattern.test(qbAngle)){
			$('#pline_qbDistance').trigger("focus");
		} else {
			$('#pline_qbError').html('Invalid Angle: see help');
			$('#pline_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
				$('#pline_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
					$('#pline_qbError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
				});
			});
			return false;
		}
	}
});
$('#pline_qbDistance').on('keydown', function(e){
	setTimeout(function(){
		if($('#pline_qbAngle').val() != "" && $('#pline_qbDistance').val() != "" ){
			$('#pline_Next').css({
				pointerEvents: "",
				opacity: '1'
			});
		} else {
			$('#pline_Next').css({
				pointerEvents: "none",
				opacity: '0.5'
			});
		}
	}, 20);
	if(e.keyCode == 13 || e.which == 13){
		var qbDistance = parseInt($('#pline_qbDistance').val()); // input string from user
		var pattern = /\d{1,10}/g; // reg exp to test against
		if (pattern.test(qbDistance)){
			$('#pline_Next').trigger('click');
			$('#pline_qbAngle').trigger("focus");
		} else {
			$('#pline_qbError').html('Invalid Distance: see help');
			$('#pline_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
				$('#pline_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
					$('#pline_qbError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
				});
			});
			return false;
		}
	}
});
	
$('#pline_polarAngle').on('keydown', function(e){
	setTimeout(function(){
		if($('#pline_polarAngle').val() != "" && $('#pline_polarDistance').val() != "" ){
			$('#pline_Next').css({
				pointerEvents: "",
				opacity: '1'
			});
		} else {
			$('#pline_Next').css({
				pointerEvents: "none",
				opacity: '0.5'
			});
		}
	}, 20);
	if(e.keyCode == 13 || e.which == 13){
		var polarAngle = $('#pline_polarAngle').val(); // input string from user
		var pattern = /\d{1,10}/g; // reg exp to test against

		if (pattern.test(polarAngle)){
			$('#pline_polarDistance').trigger("focus");
		} else {
			$('#pline_polarError').html('Invalid Angle: see help');
			$('#pline_polarError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
				$('#pline_polarError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
					$('#pline_polarError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
				});
			});
			return false;
		}
	}
});
$('#pline_polarDistance').on('keydown', function(e){
	setTimeout(function(){
		if($('#pline_polarAngle').val() != "" && $('#pline_polarDistance').val() != "" ){
			$('#pline_Next').css({
				pointerEvents: "",
				opacity: '1'
			});
		} else {
			$('#pline_Next').css({
				pointerEvents: "none",
				opacity: '0.5'
			});
		}
	}, 20);
	if(e.keyCode == 13 || e.which == 13){
		var polarDistance = parseInt($('#pline_polarDistance').val()); // input string from user
		var pattern = /\d{1,10}/g; // reg exp to test against
		if (pattern.test(polarDistance)){
			$('#pline_Next').trigger('click');
			$('#pline_polarAngle').trigger("focus");
		} else {
			$('#pline_polarError').html('Invalid Distance: see help');
			$('#pline_polarError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
				$('#pline_polarError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
					$('#pline_polarError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
				});
			});
			return false;
		}
	}
});
	
$('#pgon_Finish').on("click", function(){
	toolbar.finishDrawing();
});

$('#pgon_qbRadio').on("click", function(){
	//$('#pgon_clickToStartHint').html('click to enter starting point');
	$('#pgon_qbRadio').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
	$('#pgon_polarRadio').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
	$('#pgon_polarAngle').addClass('disabledButton');
	if(drawClickHandlerActive == true){
		$('#pgon_polarDimInput').stop().animate({height: '0px'}, 500, function(){$('#pgon_qbDimInput').stop().animate({height: '72px'});});
	} else {
		$('#pgon_dimInputDiv').stop().animate({height: '0px'});
		$('#pgon_polarDimInput').css({height: '0px'});
		$('#pgon_qbDimInput').css({height: '72px'});
	}
});
$('#pgon_polarRadio').on("click", function(){
	//$('#pgon_clickToStartHint').html('click to enter starting point');
	$('#pgon_qbRadio').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
	$('#pgon_drawRectangle').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
	$('#pgon_polarRadio').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
	if(isNumber($('#pgon_polarAngle').val()) && isNumber($('#pgon_polarDistance').val()))
		$('#gonRevAngle').removeClass('disabledButton btn-default').addClass('btn-primary');
	if(drawClickHandlerActive == true){
	  $('#pgon_qbDimInput').stop().animate({height: '0px'}, 500, function(){$('#pgon_polarDimInput').stop().animate({height: '72px'});});
	} else {
		$('#pgon_dimInputDiv').stop().animate({height: '0px'});
		$('#pgon_polarDimInput').css({height: '72px'});
		$('#pgon_qbDimInput').css({height: '0px'});
	}
});
$('#pgon_qbAngle').on('keydown', function(e){
	setTimeout(function(){
		if($('#pgon_qbAngle').val() != "" && $('#pgon_qbDistance').val() != ""){
			$('#pgon_Next').css({
				pointerEvents: "",
				opacity: '1'
			});
		} else {
			$('#pgon_Next').css({
				pointerEvents: "none",
				opacity: '0.5'
			});
		}
	}, 20);
	if(e.keyCode == 13 || e.which == 13){
		var qbAngle = $('#pgon_qbAngle').val(); // input string from user
		var pattern = /[ns] \d{1,3} [ew]/i; // reg exp to test against

		// attempt to add spaces between numbers and letters so we can easily split later
		if(qbAngle.charAt(1) == ' '){
		}else{
		  qbAngle = qbAngle.substr(0, 1) + ' ' + qbAngle.substr(1);
		}

		var numStart = null, numEnd = null;
		for(var i=2; i < qbAngle.length; i++){
		  if(numStart == null && qbAngle.charCodeAt(i) > 47 && qbAngle.charCodeAt(i) < 58){
			numStart = i;
		  }
		  if(numStart != null && qbAngle.charCodeAt(i) > 47 && qbAngle.charCodeAt(i) < 58){
			numEnd = i;
		  }
		}

		if(qbAngle.charAt(numEnd + 1) == ' '){
			// do nothing
		}else{
		  qbAngle = qbAngle.substr(0, numEnd + 1) + ' ' + qbAngle.substr(numEnd + 1);
		}

		if (pattern.test(qbAngle)){
			$('#pgon_qbDistance').trigger("focus");
		} else {
			$('#pgon_qbError').html('Invalid Angle: see help');
			$('#pgon_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
				$('#pgon_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
					$('#pgon_qbError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
				});
			});
			return false;
		}
	}
});
$('#pgon_qbDistance').on('keydown', function(e){
	setTimeout(function(){
		if($('#pgon_qbAngle').val() != "" && $('#pgon_qbDistance').val() != ""){
			$('#pgon_Next').css({
				pointerEvents: "",
				opacity: '1'
			});
		} else {
			$('#pgon_Next').css({
				pointerEvents: "none",
				opacity: '0.5'
			});
		}
	}, 20);
	if(e.keyCode == 13 || e.which == 13){
		var qbDistance = parseInt($('#pgon_qbDistance').val()); // input string from user
		var pattern = /\d{1,10}/g; // reg exp to test against
		if (pattern.test(qbDistance)){
			$('#pgon_Next').trigger('click');
			$('#pgon_qbAngle').trigger("focus");
		} else {
			$('#pgon_qbError').html('Invalid Distance: see help');
			$('#pgon_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
				$('#pgon_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
					$('#pgon_qbError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
				});
			});
			return false;
		}
	}
});
$('#pgon_polarAngle').on('keydown', function(e){
	setTimeout(function(){
		if($('#pgon_polarAngle').val() != "" && $('#pgon_polarDistance').val() != ""){
			$('#pgon_Next').css({
				pointerEvents: "",
				opacity: '1'
			});
		} else {
			$('#pgon_Next').css({
				pointerEvents: "none",
				opacity: '0.5'
			});
		}
	}, 20);
	if(e.keyCode == 13 || e.which == 13){
		var polarAngle = $('#pgon_polarAngle').val(); // input string from user
		var pattern = /\d{1,10}/g; // reg exp to test against

		if (pattern.test(polarAngle)){
			$('#pgon_polarDistance').trigger("focus");
		} else {
			$('#pgon_polarError').html('Invalid Angle: see help');
			$('#pgon_polarError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
				$('#pgon_polarError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
					$('#pgon_polarError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
				});
			});
			return false;
		}
	}
});
$('#pgon_polarDistance').on('keydown', function(e){
	setTimeout(function(){
		if($('#pgon_polarAngle').val() != "" && $('#pgon_polarDistance').val() != "" ){
			$('#pgon_Next').css({
				pointerEvents: "",
				opacity: '1'
			});
		} else {
			$('#pgon_Next').css({
				pointerEvents: "none",
				opacity: '0.5'
			});
		}
	}, 20);

	if(e.keyCode == 13 || e.which == 13){
		var polarDistance = parseInt($('#pgon_polarDistance').val()); // input string from user
		var pattern = /\d{1,10}/g; // reg exp to test against
		if (pattern.test(polarDistance)){
			$('#pgon_Next').trigger('click');
			$('#pgon_polarAngle').trigger("focus");
		} else {
			$('#pgon_polarError').html('Invalid Distance: see help');
			$('#pgon_polarError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
				$('#pgon_polarError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
					$('#pgon_polarError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
				});
			});
			return false;
		}
	}
});

$('#rectangleWidth, #rectangleHeight').on('keyup', function(e){ // enable rectangle button after numeric values have been entered
	setTimeout(function(){
		if(isNumber($('#rectangleWidth').val()) && isNumber($('#rectangleHeight').val())){
			$('#drawSpecRectangle').removeClass('disabledButton');
		} else {
			$('#drawSpecRectangle').addClass('disabledButton');
		}
	}, 20);
});
	
function getSpecRectangleGeom(origin){ // origin point should be a esri point type

	var distUnits = $('#drawUnits').val(); // placing this here allows the user to change the unit value on the fly and see it represented in the temporary graphic
	var unitToFeet;
	switch(distUnits){ // lookup table for converting from user defined units to state plane feet
		case '9002': // feet
			unitToFeet = 1;
			break;
		case '9096': //yards
			unitToFeet = 3;
			break;
		case '9035': //miles
			unitToFeet = 5280;
			break;
		case '9001': //meters
			unitToFeet = 3.28084;
			break;
		case '9036': // kilometers
			unitToFeet = 3280.84;
			break;
	}
	var width = parseFloat($('#rectangleWidth').val()) * unitToFeet;
	var height = parseFloat($('#rectangleHeight').val()) * unitToFeet;
	if($('#cornerPickerULRadio').hasClass('fa-dot-circle-o')){ // since the cursor is used as a corner of the rectangle the other corners need to be generated based off of the corner and the specified height and width
		/*var xMinYmin = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x, origin.y-height));
		var xMinYmax = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x, origin.y));
		var xMaxYmax = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x+width, origin.y));
		var xMaxYmin = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x+width, origin.y-height));*/
		var xMinYmin = projectGeometry(new Point(origin.x, origin.y-height, origin.spatialReference), map.spatialReference);
		var xMinYmax = projectGeometry(new Point(origin.x, origin.y, origin.spatialReference), map.spatialReference);
		var xMaxYmax = projectGeometry(new Point(origin.x+width, origin.y, origin.spatialReference), map.spatialReference);
		var xMaxYmin = projectGeometry(new Point(origin.x+width, origin.y-height,origin.spatialReference), map.spatialReference);
	}
	else if($('#cornerPickerURRadio').hasClass('fa-dot-circle-o')){
		/*
		var xMinYmin = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x-width, origin.y-height));
		var xMinYmax = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x-width, origin.y));
		var xMaxYmax = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x, origin.y));
		var xMaxYmin = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x, origin.y-height));
		*/
		var xMinYmin = projectGeometry(new Point(origin.x-width, origin.y-height, origin.spatialReference), map.spatialReference);
		var xMinYmax = projectGeometry(new Point(origin.x-width, origin.y, origin.spatialReference), map.spatialReference);
		var xMaxYmax = projectGeometry(new Point(origin.x, origin.y, origin.spatialReference), map.spatialReference);
		var xMaxYmin = projectGeometry(new Point(origin.x, origin.y-height, origin.spatialReference), map.spatialReference);
	}
	else if($('#cornerPickerLRRadio').hasClass('fa-dot-circle-o')){
		/*
		var xMinYmin = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x-width, origin.y));
		var xMinYmax = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x-width, origin.y+height));
		var xMaxYmax = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x, origin.y+height));
		var xMaxYmin = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x, origin.y));
		*/
		var xMinYmin = projectGeometry(new Point(origin.x-width, origin.y, origin.spatialReference), map.spatialReference);
		var xMinYmax = projectGeometry(new Point(origin.x-width, origin.y+height, origin.spatialReference), map.spatialReference);
		var xMaxYmax = projectGeometry(new Point(origin.x, origin.y+height, origin.spatialReference), map.spatialReference);
		var xMaxYmin = projectGeometry(new Point(origin.x, origin.y, origin.spatialReference), map.spatialReference);
	}
	else if($('#cornerPickerLLRadio').hasClass('fa-dot-circle-o')){
		/*
		var xMinYmin = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x, origin.y));
		var xMinYmax = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x, origin.y+height));
		var xMaxYmax = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x+width, origin.y+height));
		var xMaxYmin = Proj4js.transform(stateDef, webDef, new Proj4js.Point(origin.x+width, origin.y));
		*/
		var xMinYmin = projectGeometry(new Point(origin.x, origin.y, origin.spatialReference), map.spatialReference);
		var xMinYmax = projectGeometry(new Point(origin.x, origin.y+height, origin.spatialReference), map.spatialReference);
		var xMaxYmax = projectGeometry(new Point(origin.x+width, origin.y+height, origin.spatialReference), map.spatialReference);
		var xMaxYmin = projectGeometry(new Point(origin.x+width, origin.y, origin.spatialReference), map.spatialReference);
	}
	console.log(xMinYmin);
	rectGeom = new Polygon(map.spatialReference); // create geometry to display to user during mouse move
	rectGeom.addRing([[xMinYmin.x,xMinYmin.y],[xMinYmax.x,xMinYmax.y],[xMaxYmax.x,xMaxYmax.y],[xMaxYmin.x,xMaxYmin.y],[xMinYmin.x,xMinYmin.y]]);
	
	return rectGeom;
}

var templateMoveHandler;
$('#drawSpecRectangle').on("click", function(){
	if($(this).hasClass('btn-primary')){
		if(featureEditEnabled){
			$('#pgon_rectangleOptions').stop().animate({height: '65px'});
		} else {
			$('#pgon_rectangleOptions').stop().animate({height: '35px'});
		}
		rectangleHandlerActive = true; // needs to come first so the draw color options do not appear in the tool controls during eh feature creation
		$('.rectangleInput').addClass('disabledButton');
		toolbar.deactivate(); // manipulating the geometry of extent type tools is either impossible or not worth the time so just dectivate and temporariliy switch to polygon type
		snapManager = map.enableSnapping({ // make sure snap manager is initialized with latest layers loaded...
			layerInfos: snappingLayerInfos(), alwaysSnap: false, snapKey: has("mac") ? keys.META : keys.CTRL, snapPointSymbol: parperpSnappingCross
		});
		toolbar.activate(Draw['POLYGON']);
		$(this).removeClass('btn-primary').addClass('btn-danger').html('Cancel');

		var toProjection = new SpatialReference(statePlaneCode);
		
		if(isMobile == false){
			templateMoveHandler = dojo.connect(map,'onMouseMove',function(e){ // mouse move event that displays where the feature will be placed upon click, creates the geometry for the click event to use
				parperpTempLayer.clear();
				var statePlaneTrans;
				if(snappingActive){ // find snap point if snapping active
					var snapPromise = snapManager.getSnappingPoint(e.screenPoint);
					snapPromise.then(function(point){
						if(point !== undefined){
							var snapPoint = point;
							statePlaneTrans = projectGeometry(snapPoint, toProjection);
							//statePlaneTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(snapPoint.x, snapPoint.y));
							var snapCross = new Graphic(new Point(snapPoint.x,snapPoint.y,map.spatialReference), parperpSnappingCross);
							parperpTempLayer.add(snapCross);
						} else {
							statePlaneTrans = projectGeometry(e.mapPoint, toProjection);
							//statePlaneTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(e.mapPoint.x, e.mapPoint.y));
						}
					},
					function(error){
						console.log('Tell connor that his snapping stuff failed');
					});
				} else { // if no snap point just use the events mapPoint attribute
					statePlaneTrans = projectGeometry(e.mapPoint, toProjection);
					//statePlaneTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(e.mapPoint.x, e.mapPoint.y));
				}
				console.log(statePlaneTrans);
				var templateGeom = getSpecRectangleGeom(statePlaneTrans);
				var templateOutline = new Graphic(templateGeom,plineTempLineSymbol);
				parperpTempLayer.add(templateOutline);

			});
		}

		rectangleClickHandler = dojo.connect(map,'onClick',function(e){ // event that adds the geometry created above to the toolbar and the map.
			dojo.disconnect(templateMoveHandler);
			dojo.disconnect(rectangleClickHandler);
			/*try {
				toolbar._graphic.geometry.rings[0].pop(); // remove the point added to the toolbar, it will snap to this if it is present
			} catch (e) {
				console.log(e);
			}*/
			try {
				toolbar._graphic.geometry = null;// remove the point added to the toolbar, it will snap to this if it is present
			} catch (e){
				console.log("Problem with clearing graphic geometry: " + e);
			}
			parperpTempLayer.clear();
			var toProjection = new SpatialReference(statePlaneCode);
			if(snappingActive){ // find snap point if snapping active
				var snapPromise = snapManager.getSnappingPoint(e.screenPoint);
				snapPromise.then(function(point){
					if(point !== undefined){
						var snapPoint = point;
						statePlaneTrans = projectGeometry(snapPoint, toProjection);
						//statePlaneTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(snapPoint.x, snapPoint.y));
						var snapCross = new Graphic(new Point(snapPoint.x,snapPoint.y,map.spatialReference), parperpSnappingCross);
						parperpTempLayer.add(snapCross);
					} else {
						statePlaneTrans = projectGeometry(e.mapPoint, toProjection);
						//statePlaneTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(e.mapPoint.x, e.mapPoint.y));
					}
				},
				function(error){
					console.log('Tell connor that his snapping stuff failed');
				});
			} else { // if no snap point just use the events mapPoint attribute
				statePlaneTrans = projectGeometry(e.mapPoint, toProjection);
				//statePlaneTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(e.mapPoint.x, e.mapPoint.y));
			}

			var templateGeom = getSpecRectangleGeom(statePlaneTrans);
			var newPoints = [];
			toolbar._graphic.setGeometry(templateGeom);
			templateGeom.rings[0].forEach(function(i){ // toolbar's points need to match the points in the geometry being drawn, setting geomety itself does not do this.
				newPoints.push(new Point(i,map.spatialReference));
			});
			toolbar._points=newPoints;
			$('#drawSpecRectangle').removeClass('btn-danger').addClass('btn-primary').html('Draw Rectangle');
			$('#rectangleHeight, #rectangleWidth').val('');
			$('#drawSpecRectangle').addClass('disabledButton');
			$('#pgon_rectangleOptions').stop().animate({height: '0px'});
			rectangleHandlerActive = false;
			$('.rectangleInput').removeClass('disabledButton');
			toolbar.finishDrawing();
		});
	} else {
		disableAutoSnap();
		cancelSpecRectangle();
		toolbar.activate(Draw['EXTENT']);
	}
});

cancelSpecRectangle = function(){
	if(rectangleHandlerActive){
		$('#drawSpecRectangle').removeClass('btn-danger').addClass('btn-primary').html('Draw Rectangle');
		parperpTempLayer.clear();
		$('#pgon_rectangleOptions').stop().animate({height: '0px'});
		toolbar.deactivate();
		dojo.disconnect(templateMoveHandler);
		dojo.disconnect(rectangleClickHandler);
		$('#rectangleHeight, #rectangleWidth').val('');
		$('#drawSpecRectangle').addClass('disabledButton');
		$('.rectangleInput').removeClass('disabledButton');
		rectangleHandlerActive = false;
	}
}

$('.cornerRadio').on("click", function(){
	$('.cornerRadio').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
	$(this).addClass('fa-dot-circle-o');

});

$('#pline_Cancel, #pgon_Cancel').on("click", function(){
	if(typeof(templatePicker) !== "undefined"){
		if(templatePicker.getSelected() === null){
			cancelDrawingSelection();
		} else {
			cancelFeatureEditing("cancelFeatureCreation button clicked");
		}
	} else {
		cancelDrawingSelection();
	}
});

// Don't get the idea to combine these two functions. One uses paths, the other uses rings.
$('#pline_Undo').on("click", function(){
	cancelParperp();
	if(toolbar._points.length > 1){
		parperpTempLayer.clear(); // remove dotted temporary line for cogo stuff if visible
		toolbar._graphic.geometry.paths[0].pop();
		toolbar._points.pop();
		toolbar._graphic.draw();
		map_x = toolbar._graphic.geometry.paths[0][toolbar._graphic.geometry.paths[0].length -1][0]; // update map_x and map_y variables so further drawing starts form the correct place.
		map_y = toolbar._graphic.geometry.paths[0][toolbar._graphic.geometry.paths[0].length -1][1];
	}else if(toolbar._points.length == 1){
		if(typeof(templatePicker) !== "undefined"){
			if(templatePicker.getSelected() === null){
				cancelDrawingSelection();
			} else {
				cancelFeatureEditing("cancelFeatureCreation button clicked");
			}
		} else {
			cancelDrawingSelection();
		}
	}
	if(toolbar._points.length < 2){ // if there is no longer a single line, disable the finish button
		$('#pline_Finish').css({
			pointerEvents: "none",
			opacity: '0.5'
		});
	}
});
$('#pgon_Undo').on("click", function(){
	cancelParperp()
	if(toolbar._points.length > 1){
		parperpTempLayer.clear();  // remove dotted temporary line for cogo stuff if visible
		toolbar._graphic.geometry.rings[0].pop();
		toolbar._points.pop();
		toolbar._graphic.draw();
		map_x = toolbar._graphic.geometry.rings[0][toolbar._graphic.geometry.rings[0].length -1][0];
		map_y = toolbar._graphic.geometry.rings[0][toolbar._graphic.geometry.rings[0].length -1][1];
	} else if(toolbar._points.length == 1){
		if(typeof(templatePicker) !== "undefined"){
			if(templatePicker.getSelected() === null){
				cancelDrawingSelection();
			} else {
				cancelFeatureEditing("cancelFeatureCreation button clicked");
			}
		} else {
			cancelDrawingSelection();
		}
	}
	if(toolbar._points.length < 3){ // if there is no longer a single line, disable the finish button
		$('#pgon_Finish').css({
			pointerEvents: "none",
			opacity: '0.5'
		});
	}
	if(toolbar._points.length == 3){
		$('#squareAndFinish').removeClass('disabledButton');
	}
});

var nextCenterPoint, drawClickHandlerActive = false;
function createDrawToolbar(/*themap*/) { // created in dojo.connect for map
  toolbar = new Draw(map);
  toolbar.extentGoodToGo = true;
  toolbar.updateNullGeometry = undefined;
  toolbar.setUpdateNullGeometry = function(attributes){
	this.updateNullGeometry = attributes;
  }
  

  toolbar.on("activate", async function(e){

	disablePopup();
/*
	try{  // Bryan - this is here to remove popups while drawing
		if(templateLayers != undefined){
			for(var i = 0; i < templateLayers.length; i++){
				templateLayers[i].setInfoTemplate(null);
			}
		}
	} catch(err){
		console.log('no template layers3: ' + err.message);
	}
	*/
	if(toolbar._geometryType == "polygon" || toolbar._geometryType == "polyline"){ // reset cogo boxes
		if(!(snappingActive)){ // if snapping deactivates and reactivates the toolbar so anything that should only happen on the first opening of a drawing session should check if snapping is active
			$('#pgon_qbDistance, #pgon_qbAngle, #pgon_polarDistance, #pgon_polarAngle').val('');
			if($('#pgon_drawRectangle').hasClass('fa-dot-circle-o')){
				$('#pgon_dimInputDiv').css({height: '82px'});
			} else {
				$('#pgon_dimInputDiv').css({height: '0px'});
			}
			$('.reverseAngle').addClass('disabledButton');
			$('#pline_qbDistance, #pline_qbAngle, #pline_polarDistance, #pline_polarAngle').val('');
			$('#pline_dimInputDiv').addClass('hidden');
		}
	}
	if(toolbar._geometryType == 'extent'){
		$('#rectangleWidth, #rectangleHeight').val('');
		$('#drawSpecRectangle').addClass('disabledButton');
	}
	//parcelLayer.infoTemplate = "";
	//parcelLayer.disableMouseEvents();
	for(layer in parcelLayers){
		window[layer].setInfoTemplate(null);
		try {
			window[layer].disableMouseEvents();
		} catch (e) {

		}
	}

	$('.esriPopup, .dijitTooltipDialogPopup').css({'pointer-events': 'none', 'opacity': 0});

	$('#map_layers').css('cursor', 'crosshair');
	var map_sr;
	drawClickHandlerActive = false;
	parperpHandlerActive = false;
	//rectangleHandlerActive = false;
	var unlab = getUnitAndLabel();
	var drawUnits = unlab[0]; // update drawunits and label if the user changes them
	var drawUnitsLabel = unlab[1];
	var totLength = 0;
	var addLength = 0;
	var labelDeg;
	var origUnits = $('#drawUnits').val();

	// THIS IS WHERE ADVANCED DRAWING FUNCTIONS ARE WRITTEN (Such as showing lengths of lines as they're drawn)
	
	// remove handlers if they exist. If this isn't done here, they could get stuck on until the page reloads

	if(drawDistanceHandler1 != undefined){
		drawDistanceHandler1.remove();
	}
	if(drawDistanceHandler2 != undefined){
		drawDistanceHandler2.remove();
	}

	if(toolbar._geometryType == "line" || toolbar._geometryType == "polyline" || toolbar._geometryType == "polygon" || toolbar._geometryType == "extent"){
		var plineTempline;

		if(toolbar._geometryType == "line"){
			drawDistanceHandler1 = dojo.connect(map, 'onMouseDown', function(e){
				var unlab = getUnitAndLabel();
				drawUnitsLabel = unlab[1]; // update label if the user changes them

				$('#lengthOutput').removeClass('hidden');
				$('#lengthOutput').html('Length: 0' + drawUnitsLabel);
				$('#lengthOutput').css('left', e.screenPoint.x);
				$('#lengthOutput').css('top', e.screenPoint.y - 20);
				drawClickHandlerActive = true;
				var map_sr = e.mapPoint.spatialReference;
				map_x = e.mapPoint.x;
				map_y = e.mapPoint.y;
				drawDistanceHandler2 = map.on('mouse-drag', function(e){
					if(drawClickHandlerActive){
						var map_x1 = e.mapPoint.x;
						var map_y1 = e.mapPoint.y;
						$('#lengthOutput').css('left', e.screenPoint.x);
						$('#lengthOutput').css('top', e.screenPoint.y - 20);
						var tempLine = new Polyline(map.spatialReference);
						tempLine.addPath([[map_x, map_y],[map_x1, map_y1]]);

						$('#lengthOutput').html('Length: ' + geometryEngine.geodesicLength(tempLine, drawUnits).toFixed(1) + drawUnitsLabel);
					}
				});
			});
		} else if(toolbar._geometryType == "extent" && !(isMobile)){ // we can exclude mobile from this check, touch doesn;t suffer from the same ill effects as a mouse when it comes to accidentally triggering a mouse up event
			var firstScreenPt;
			var endScreenPt;
			drawDistanceHandler1 = dojo.connect(map, 'onMouseDown', function(evt1){
				 firstScreenPt = evt1.screenPoint;
				drawDistanceHandler2 = map.on('mouse-drag', function(evt2){ // set extent boolean to false if extent is too small, this is most likely an accidental creation, no one wants such a small rectangle
					endScreenPt = evt2.screenPoint;
					var diagDistance = Math.sqrt(Math.pow(Math.abs(firstScreenPt.x-endScreenPt.x),2) + Math.pow(Math.abs(firstScreenPt.y-endScreenPt.y),2));
					if(diagDistance < 20){
						toolbar.extentGoodToGo = false;
					} else{
						toolbar.extentGoodToGo = true;
					}
				});
			});

		} else {
			drawDistanceHandler1 = dojo.connect(map, 'onClick', async function(e){ //changed to a dojo.connect event - synchronized the actual esri graphic and the line created pixel coordinates to produce measurements.
				parperpTempLayer.clear();
				//clearInterval(hintColorPulse);
		
				if(toolbar._geometryType == "polyline"){
					if(!$('#pline_qbRadio').hasClass('fa-dot-circle-o')){ //if polar has been selected, make sure drawing tools starts with polar inputs visible
						$('#pline_qbDimInput').addClass('hidden');
						$('#pline_polarDimInput').removeClass('hidden');
					} //else defaults to quadrant
					$('#pline_dimInputDiv').removeClass('hidden');
					if(featureEditEnabled == true){
						$('#pline_dimInputDiv').css('height', '105px');
					} else {
						$('#pline_dimInputDiv').css('height','65px');
					}
					
					$('#pline_Undo, #pline_Cancel').css({
						pointerEvents: "",
						opacity: '1'
					});

					if(toolbar._points.length > 1 && !(parperpHandlerActive)){ // if there is a single line, allow finish button
						$('#pline_Finish').css({
							pointerEvents: "",
							opacity: '1'
						});
					}
				} else if(toolbar._geometryType == "polygon"){
					//$('#pgon_dimInputDiv').stop().animate({height: '105px'});
		
					if(featureEditEnabled == true){
						$('#pgon_dimInputDiv').stop().animate({height: '105px'});
					} else {
						$('#pgon_dimInputDiv').stop().animate({height: '65px'});
					}
					$('#pgon_Undo, #pgon_Cancel').css({
						pointerEvents: "",
						opacity: '1'
					});
					if(toolbar._points.length > 2 && !(parperpHandlerActive)){ // if there are two lines, allow the finish button
						$('#pgon_Finish').css({
							pointerEvents: "",
							opacity: '1'
						});
					}
					
					setUncloseDrawPolygonHandler();
				}
				totLength = totLength += addLength; // modify total length value for display

				var unlab = getUnitAndLabel();
				drawUnitsLabel = unlab[1]; // update label if the user changes them

				var map_sr = e.mapPoint.spatialReference;
				if(!(parperpHandlerActive) && !(rectangleHandlerActive) && !(ninetyHandlersActive) && (typeof(ehSepticLateralsController) == 'undefined' || typeof(ehSepticLateralsController) != 'undefined' && !(ehSepticLateralsController.active))){ // this is really important, these variables hold the last clicked position, the cogo tools base their next move off of this, if this is updated when clicking on a segment in a parrallel session then the cogo tools will place the next drawin line in the incorrect place.
					$('.parperp').removeClass('disabledButton');
					try{ // added try/catch for safety's sake, just in case the points are not available in the toolbar (they should be) the old method will be used
						map_x = toolbar._points[toolbar._points.length-1].x;
						map_y = toolbar._points[toolbar._points.length-1].y;
					} catch(err){
						map_x = e.mapPoint.x;
						map_y = e.mapPoint.y;
					}
					$('#lengthOutput').removeClass('hidden'); // also dont let the length output show
				}
				$('#lengthOutput').html('Length: 0' + drawUnitsLabel);
				$('#lengthOutput').css('left', e.screenPoint.x);
				$('#lengthOutput').css('top', e.screenPoint.y - 20);
				drawClickHandlerActive = true;


				if(typeof(measurementLabelsLayer) != 'undefined' && $('#measureDistance').hasClass('active')){
					if(totLength > 0 && toolbar._points.length > 1){
						var mX1 = toolbar._points[toolbar._points.length-1].x;
						var mY1 = toolbar._points[toolbar._points.length-1].y;
						var mX2 = toolbar._points[toolbar._points.length-2].x;
						var mY2 = toolbar._points[toolbar._points.length-2].y;

						var xm = (mX1+mX2)/2;
						var ym = (mY1+mY2)/2;
						labelDeg = Math.atan2(mY2 - mY1, mX2 - mX1) * 180 / Math.PI;
						if(Math.abs(labelDeg)>90){
							labelDeg = labelDeg - 180;
						}
						labelDeg = labelDeg * -1;
						var lengthSym = new TextSymbol(addLength.toFixed(2) +drawUnitsLabel);
						lengthSym.setColor( new dojo.Color([25, 140, 253]));
						lengthSym.setAngle(labelDeg);
						lengthSym.setOffset(0,5);
						var font = new Font("12pt", Font.STYLE_BOLD,
							Font.VARIANT_NORMAL, Font.WEIGHT_BOLD,"arial");
						lengthSym.setFont(font);
						var color = new Color([255,255,255]);
						lengthSym.setHaloColor(color);
						lengthSym.setHaloSize(1);
						measurementLabelsLayer.add(new Graphic(new Point(xm,ym,map_sr), lengthSym, {"labelKey": measureTimestamp}));
					}
				}

				if(toolbar._geometryType == "polyline"){
					window.drawingLineX = undefined;
					window.drawingLineY = undefined;
				// set variable to change to see if we need to grab the next xy from last click, or last number input via input boxes
					clickedAgain = true;
										// wipe out handler if it's set.
					if(nextButtonHandler != undefined){
						nextButtonHandler.off();
						nextButtonHandler = undefined;
					}


					showLineDirectionHandler = $('.cogoInput').on('keyup', async function(){
						drawingLineX = undefined;
						drawingLineY = undefined;
						if($(this).val() != ''){
							parperpTempLayer.clear(); // using the same layer as the parperp functionality
							cancelParperp();
							if($('#pline_qbRadio').hasClass('fa-dot-circle-o')){
								// validat the input
								var qbAngle = $('#pline_qbAngle').val(); // input string from user
								var pattern = /[ns] \d{1,3}\s(\d{0,2}\s)?(\d{0,2}\s+)?[ew]/i; // reg exp to test against
								
								// attempt to add spaces between numbers and letters so we can easily split later
								
								if(qbAngle.charAt(1) == ' '){
								}else{
									qbAngle = qbAngle.substr(0, 1) + ' ' + qbAngle.substr(1);
								}

								var numStart = null, numEnd = null;
								for(var i=2; i < qbAngle.length; i++){
									if(numStart == null && qbAngle.charCodeAt(i) > 47 && qbAngle.charCodeAt(i) < 58){
									numStart = i;
									}
									if(numStart != null && qbAngle.charCodeAt(i) > 47 && qbAngle.charCodeAt(i) < 58){
									numEnd = i;
									}
								}

								if(qbAngle.charAt(numEnd + 1) == ' '){
									// do nothing
								}else{
									qbAngle = qbAngle.substr(0, numEnd + 1) + ' ' + qbAngle.substr(numEnd + 1);
								}


								qbAngle = qbAngle.replace(/:/g," ");
								if (pattern.test(qbAngle)){
									var qbAngleSubstr = qbAngle.split(' '); // split into string array
									var dmsCoords = qbAngleSubstr.slice(1,qbAngleSubstr.length-1);
									var DD;
									if(dmsCoords.length == 1){
										DD = parseFloat(dmsCoords[0]); 
									} else if(dmsCoords.length == 2){
										DD = parseFloat(dmsCoords[0]) + parseFloat(dmsCoords[1])/60;
									} else if(dmsCoords.length == 3){
										DD = parseFloat(dmsCoords[0]) + parseFloat(dmsCoords[1])/60 + parseFloat(dmsCoords[2])/3600;
									}
									var qbAngleStart = null, qbAngleEnd = null; // var to hold converted start point. North being 90 and south being 180.
									if (qbAngleSubstr[0].toUpperCase() == 'N'){
										qbAngleStart = 90;
										if(qbAngleSubstr[qbAngleSubstr.length-1].toUpperCase() == 'E'){
											qbAngleEnd = qbAngleStart - DD;
										} else if(qbAngleSubstr[qbAngleSubstr.length-1].toUpperCase() == 'W'){
											qbAngleEnd = qbAngleStart + DD;
										}
									} else if(qbAngleSubstr[0].toUpperCase() == 'S'){
										qbAngleStart = 270;
										if(qbAngleSubstr[qbAngleSubstr.length-1].toUpperCase() == 'E'){
											qbAngleEnd = qbAngleStart + DD;
										} else if(qbAngleSubstr[qbAngleSubstr.length-1].toUpperCase() == 'W'){
											qbAngleEnd = qbAngleStart - DD;
										}
									}
								}  else {

									if(this.id == 'pline_qbDistance'){
										$('#pline_qbError').html('Invalid Angle: see help');
										$('#pline_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
											$('#pline_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
												$('#pline_qbError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
											});
										});
									}
									return false;
								}

							}
							// Get distance and angle. Adjust distance for selected units.
							// need to conver to feet for stateplane stuff
							var distance = 0, angle = 0;
							var unitStr = $('#drawUnits').val();
							var unitToFeet;
							switch(unitStr){
								case '9002': // feet
									unitToFeet = 1;
									break;	
								case '9096': //yards
									unitToFeet = 3;
									break;	
								case '9035': //miles
									unitToFeet = 5280;
									break;								
								case '9001': //meters
									unitToFeet = 3.28084;
									break;	
								case '9036': // kilometers
									unitToFeet = 3280.84;
									break;
							}
							
							if($('#pline_qbRadio').hasClass('fa-dot-circle-o')){
								distance = parseFloat($('#pline_qbDistance').val());
								angle = qbAngleEnd;
							} else if($('#pline_polarRadio').hasClass('fa-dot-circle-o')){
								distance = parseFloat($('#pline_polarDistance').val());
								angle = parseFloat($('#pline_polarAngle').val());
								angle *= -1;
								angle += 90;
							}

							if(isNumber(distance) && isNumber(angle)){

								//$('#pline_Next').css({'pointer-events': '', 'opacity': '1'});
								if(distance < 0.1){
									if($('#pline_qbRadio').hasClass('fa-dot-circle-o')){
										$('#pline_qbError').html('Invalid Distance: (zero)');
										$('#pline_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
											$('#pline_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
												$('#pline_qbError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
											});
										});
										return false;
									} else if($('#pline_polarRadio').hasClass('fa-dot-circle-o')){
										$('#pline_polarError').html('Invalid Distance: (zero)');
										$('#pline_polarError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
											$('#pline_polarError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
												$('#pline_polarError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
											});
										});
										return false;
									}
								} else {
									var adjustedDistance = unitToFeet * distance;
								}

								// Adjust (convert) angle from Polar degrees (0 being East and increasing Counterclockwise) to
								// Compass degrees (North = 0 and increasing clockwise).

								if(clickedAgain == true){
									nextCenterPoint = webMercatorUtils.xyToLngLat(map_x, map_y);
									clickedAgain = false;
								}
								var statePlaneTrans = projectGeometry(new Point(map_x, map_y, map.spatialReference), statePlaneSR);
								//var statePlaneTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(map_x, map_y));

								var spX = statePlaneTrans.x;
								var spY = statePlaneTrans.y;

								if(angle == 90 || angle == 270){ // handle complete vertical lines
									if(angle == 90){
										var tempX = spX;
										var tempY = spY + adjustedDistance;
									}
									if(angle == 270){
										var tempX = spX;
										var tempY = spY - adjustedDistance;
									}
								} else if(angle == 180 || angle == 0){ // handle complete horizontal lines
									if(angle == 180){
										var tempX = spX - adjustedDistance;
										var tempY = spY;
									}
									if(angle == 0){
										var tempX = spX + adjustedDistance;
										var tempY = spY;
									}
								} else {
									var yDist = (Math.sin(angle * Math.PI/180)) * adjustedDistance;
									var xDist = (Math.cos(angle * Math.PI/180)) * adjustedDistance;
									var tempX = spX + xDist;
									var tempY = spY + yDist;
								}
								//var statePlanePt = new Proj4js.Point(tempX, tempY);
								var webMercTrans = projectGeometry(new Point(tempX, tempY, statePlaneSR), map.spatialReference);
								//var webMercTrans = Proj4js.transform(stateDef, webDef, new Proj4js.Point(tempX, tempY));
								drawingLineX = webMercTrans.x;
								drawingLineY = webMercTrans.y;
								$('#map').trigger("focus");
								var tempSegment = new Polyline(map.spatialReference);
								tempSegment.addPath([[map_x,map_y],[drawingLineX,drawingLineY]]);
								plineTempLine = new Graphic(tempSegment,plineTempLineSymbol);
								parperpTempLayer.add(plineTempLine);
							}
						}

					});
					

					nextButtonHandler = $('#pline_Next').on("click", function(){
						parperpTempLayer.clear();
						if(typeof(drawingLineX) != 'undefined' || drawingLineX != undefined && isNumber(drawingLineX)){
							var mapPt = new Point(drawingLineX,drawingLineY);
							var screenPt = map.toScreen(mapPt);
							if(snapManager.alwaysSnap){ // check if auto snap is on turn it off if so
								parperpDisableAutoSnap(); // disable the auto snap if on
							}
							$('#map').trigger("focus");
							// This line was found in some obscure forum
							map.emit("click", { bubbles: true, cancelable: true, mapPoint: mapPt, screenPoint: screenPt});
							if(snappingActive){
								enbSnapping();
							}
							// Clear the input boxes.
							$("#revAngle").removeClass('btn-primary').addClass("disabledButton btn-default"); // reset reverse angle button
							$('#pline_qbDistance, #pline_qbAngle, #pline_polarDistance, #pline_polarAngle').val('');
							$('#pline_next').css({'pointer-events': 'none', 'opacity': '.5'});
						}
					});
				} else if(toolbar._geometryType == "polygon"){
					window.drawingPolyX= undefined;
					window.drawingPolyY = undefined;
					// set variable to change to see if we need to grab the next xy from last click, or last number input via input boxes
					clickedAgain = true;

					// wipe out handler if it's set.
					if(nextButtonHandler != undefined){
						nextButtonHandler.off();
						nextButtonHandler = undefined;
					}
					if(!(parperpHandlerActive) && !(rectangleHandlerActive)){
						if(toolbar._points.length == 3){
							$('#squareAndFinish').removeClass('disabledButton');
						} else {
							$('#squareAndFinish').addClass('disabledButton');
						}
					}
					showPolyDirectionHandler = $('.cogoInput').on('keyup', function(){
						drawingPolyX = undefined;
						drawingPolyY = undefined;
						parperpTempLayer.clear(); // using the same layer as the parperp functionality
						cancelParperp();
						if($('#pgon_qbRadio').hasClass('fa-dot-circle-o')){

							// validat the input
							var qbAngle = $('#pgon_qbAngle').val(); // input string from user
							var pattern = /[ns] \d{1,3}\s(\d{0,2}\s)?(\d{0,2}\s+)?[ew]/i; // reg exp to test against

							// attempt to add spaces between numbers and letters so we can easily split later
							if(qbAngle.charAt(1) == ' '){
							}else{
								qbAngle = qbAngle.substr(0, 1) + ' ' + qbAngle.substr(1);
							}

							var numStart = null, numEnd = null;
							for(var i=2; i < qbAngle.length; i++){
								if(numStart == null && qbAngle.charCodeAt(i) > 47 && qbAngle.charCodeAt(i) < 58){
								numStart = i;
								}
								if(numStart != null && qbAngle.charCodeAt(i) > 47 && qbAngle.charCodeAt(i) < 58){
								numEnd = i;
								}
							}

							if(qbAngle.charAt(numEnd + 1) == ' '){
								// do nothing
							}else{
								qbAngle = qbAngle.substr(0, numEnd + 1) + ' ' + qbAngle.substr(numEnd + 1);
							}
							qbAngle = qbAngle.replace(/:/g," ");
							if (pattern.test(qbAngle)){
								var qbAngleSubstr = qbAngle.split(' '); // split into string array
								var dmsCoords = qbAngleSubstr.slice(1,qbAngleSubstr.length-1);
								var DD;
								if(dmsCoords.length == 1){
									DD = parseFloat(dmsCoords[0]);
								} else if(dmsCoords.length == 2){
									DD = parseFloat(dmsCoords[0]) + parseFloat(dmsCoords[1])/60;
								} else if(dmsCoords.length == 3){
									DD = parseFloat(dmsCoords[0]) + parseFloat(dmsCoords[1])/60 + parseFloat(dmsCoords[2])/3600;
								}
								//var DD = parseFloat(dmsCoords[0]) + parseFloat(dmsCoords[1])/60 + parseFloat(dmsCoords[2])/3600;
								var qbAngleStart = null, qbAngleEnd = null; // var to hold converted start point. North being 90 and south being 180.
								if (qbAngleSubstr[0].toUpperCase() == 'N'){
									qbAngleStart = 90;
									if(qbAngleSubstr[qbAngleSubstr.length-1].toUpperCase() == 'E'){
										qbAngleEnd = qbAngleStart - DD;
									} else if(qbAngleSubstr[qbAngleSubstr.length-1].toUpperCase() == 'W'){
										qbAngleEnd = qbAngleStart + DD;
									}
								} else if(qbAngleSubstr[0].toUpperCase() == 'S'){
									qbAngleStart = 270;
									if(qbAngleSubstr[qbAngleSubstr.length-1].toUpperCase() == 'E'){
										qbAngleEnd = qbAngleStart + DD;
									} else if(qbAngleSubstr[qbAngleSubstr.length-1].toUpperCase() == 'W'){
										qbAngleEnd = qbAngleStart - DD;
									}
								}
							}  else {
								if(this.id == 'pgon_qbDistance'){
									$('#pgon_qbError').html('Invalid Angle: see help');
									$('#pgon_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
										$('#pgon_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
											$('#pgon_qbError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
										});
									});
								}
								return false;
							}

						}
						// Get distance and angle. Adjust distance for selected units.
						// The API SHOULD internally convert all units to meters, but it's broken. Will be fixed in v4.0
						var distance = 0, angle = 0;
						var unitStr = $('#drawUnits').val();
						var unitToFeet;
						switch(unitStr){
							case '9002': // feet
								unitToFeet = 1;
								break;
							case '9096': //yards
								unitToFeet = 3;
								break;
							case '9035': //miles
								unitToFeet = 5280;
								break;
							case '9001': //meters
								unitToFeet = 3.28084;
								break;
							case '9036': // kilometers
								unitToFeet = 3280.84;
								break;
						}

						if($('#pgon_qbRadio').hasClass('fa-dot-circle-o')){
							distance = parseFloat($('#pgon_qbDistance').val());
							angle = qbAngleEnd;
						} else if($('#pgon_polarRadio').hasClass('fa-dot-circle-o')){
							distance = parseFloat($('#pgon_polarDistance').val());;
							angle = parseFloat($('#pgon_polarAngle').val());
							angle *= -1;
							angle += 90;
						}
						if(isNumber(distance) && isNumber(angle)){
							$('#pgon_next').css({'pointer-events': '', 'opacity': '1'});
							if(distance < 0.1){
								if($('#pgon_qbRadio').hasClass('fa-dot-circle-o')){
									$('#pgon_qbError').html('Invalid Distance: (zero)');
									$('#pgon_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
										$('#pgon_qbError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
											$('#pgon_qbError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
										});
									});
									return false;
								}else if($('#pgon_polarRadio').hasClass('fa-dot-circle-o')){
									$('#pgon_polarError').html('Invalid Distance: (zero)');
									$('#pgon_polarError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 250,function(){
										$('#pgon_polarError').stop().animate({color: 'rgba(200, 0, 0, 1)'}, 3000,function(){
											$('#pgon_polarError').stop().animate({color: 'rgba(200, 0, 0, 0)'}, 1000);
										});
									});
									return false;
								}
							} else {
								var adjustedDistance = unitToFeet * distance;
							}

							// Adjust (convert) angle from Polar degrees (0 being East and increasing Counterclockwise) to 
							// Compass degrees (North = 0 and increasing clockwise).

							if(clickedAgain == true){
								nextCenterPoint = webMercatorUtils.xyToLngLat(map_x, map_y);
								clickedAgain = false;
							}
							//var webMercPt = new Proj4js.Point(map_x, map_y);
							var statePlaneTrans = projectGeometry(new Point(map_x, map_y, map.spatialReference), statePlaneSR);
							//var statePlaneTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(map_x, map_y));

							var spX = statePlaneTrans.x;
							var spY = statePlaneTrans.y;

							if(angle == 90 || angle == 270){ // handle complete vertical lines
								if(angle == 90){
									var tempX = spX;
									var tempY = spY + adjustedDistance;
								}
								if(angle == 270){
									var tempX = spX;
									var tempY = spY - adjustedDistance;
								}
							} else if(angle == 180 || angle == 0){ // handle complete horizontal lines
								if(angle == 180){
									var tempX = spX - adjustedDistance;
									var tempY = spY;
								}
								if(angle == 0){
									var tempX = spX + adjustedDistance;
									var tempY = spY;
								}
							} else {

								var yDist = (Math.sin(angle * Math.PI/180)) * adjustedDistance;

								var xDist = (Math.cos(angle * Math.PI/180)) * adjustedDistance;

								var tempX = spX + xDist;
								var tempY = spY + yDist;
							}

							//var statePlanePt = new Proj4js.Point(tempX, tempY);
							var webMercTrans = projectGeometry(new Point(tempX, tempY, statePlaneSR), map.spatialReference);
							//var webMercTrans = Proj4js.transform(stateDef, webDef, new Proj4js.Point(tempX, tempY));
							drawingPolyX = webMercTrans.x;
							drawingPolyY = webMercTrans.y;
							$('#map').trigger("focus");
							var tempSegment = new Polyline(map.spatialReference);
							tempSegment.addPath([[map_x,map_y],[drawingPolyX,drawingPolyY]]);
							plineTempLine = new Graphic(tempSegment,plineTempLineSymbol);
							parperpTempLayer.add(plineTempLine);
						}
					});

					nextButtonHandler = $('#pgon_Next').on("click", function(){
						if(typeof(drawingPolyX) != 'undefined' && isNumber(drawingPolyX)){
							parperpTempLayer.clear();
							var mapPt = new Point(drawingPolyX,drawingPolyY);
							var screenPt = map.toScreen(mapPt);
							if(snapManager.alwaysSnap){ // check if auto snap is on turn it off if so
								parperpDisableAutoSnap(); // disable the auto snap if on
							}
							$('#map').trigger("focus");
							// This line was found in some obscure forum
							map.emit("click", { bubbles: true, cancelable: true, mapPoint: mapPt, screenPoint: screenPt});
							if(snappingActive){
								enbSnapping();
							}
							// Clear the input boxes.
							$("#gonRevAngle").removeClass('btn-primary').addClass("disabledButton btn-default"); // reset reverse angle button
							$('#pgon_qbDistance, #pgon_qbAngle, #pgon_polarDistance, #pgon_polarAngle').val('');
							$('#pgon_next').css({'pointer-events': 'none', 'opacity': '.5'});
						}
					});

					// This is here for reference, to show that emit() also takes screen points.
					//var scrPt = new Point(-122, 45.6);
					//map.emit("click", { bubbles: true, cancelable: true, screenPoint: scrPt, mapPoint: mapPt });
				}
			});
		}
	}
	if(toolbar._geometryType == "polyline" || toolbar._geometryType == "polygon"){
		var measToolsActive = ($('#measureToolsHeading').is(":visible") && $('#measureToolsHeading').hasClass('ui-state-active'));
		if(toolbar._geometryType == "polyline" && measToolsActive === true){
			if($('#measureDistanceTable td').eq(0).html() === "no measurements"){
				$('#measureDistanceTable td').eq(0).html("");
			}
			var numberOfDMrows = $(".DMrow").length + 1;
			let hasPreviousDistance = $('#measureTotalLength'+(numberOfDMrows-1)).text(); //toggling snap will cause this to run multiple times creating blank distances in the list
			if(hasPreviousDistance || numberOfDMrows == 1){
				// DM stands for distance measurement
				$('#measureDistanceTable').append('<tr id="DMrow' + numberOfDMrows + '" class="DMrow" labelKey="' + measureTimestamp + '"><td>Distance ' + numberOfDMrows + '</td><td><span id="measureTotalLength' + numberOfDMrows +'"</span></td></tr>');
			}
		}	

		drawDistanceHandler2 = map.on('mouse-move', async function(e){

			if(drawClickHandlerActive){

				var map_x1 = e.mapPoint.x;
				var map_y1 = e.mapPoint.y;
				$('#lengthOutput').css('left', e.screenPoint.x);
				$('#lengthOutput').css('top', e.screenPoint.y - 20);
				var tempLine = new Polyline(map.spatialReference);
				let pt;
				if($('#measurementSnappingButton').hasClass('fa-check-square-o')){
					pt = await snapManager.getSnappingPoint(e.screenPoint);
				} 
				if(pt){
					tempLine.addPath([[map_x, map_y],[pt.x, pt.y]]);
				} else {
					tempLine.addPath([[map_x, map_y],[map_x1, map_y1]]);
				}

				var unlab = getUnitAndLabel();
				drawUnits = unlab[0]; // update drawunits and label if the user changes them
				drawUnitsLabel = unlab[1];
				$('#lengthOutput').html('Length: ' + geometryEngine.geodesicLength(tempLine, drawUnits).toFixed(1) + drawUnitsLabel);
				addLength = geometryEngine.geodesicLength(tempLine, drawUnits); //compute temporary length
				if(origUnits != drawUnits){ // check if user has changed units
				  totLength = lengthConversion(totLength, origUnits, drawUnits); // convert length of total length variable for display if units have changed
				  if(typeof(measurementLabelsLayer) != 'undefined'){
					  updateLabelUnits(origUnits, drawUnits, drawUnitsLabel); //update segment labels if units have changed
				  }
				}
				if(toolbar._geometryType == "polyline"){
				  $('#measureTotalLength' + numberOfDMrows).html((totLength + geometryEngine.geodesicLength(tempLine, drawUnits)).toFixed(1) + drawUnitsLabel);
				} // no else clause needed for polygon (area) geometryType
				
				origUnits = drawUnits;
			}
		});
	}
});

function setToolbarGeometry(geometry){
	if(!toolbar.active){
		return false;
	}
	var points;
	switch(geometry.type){
		case "polygon":
			points = geometry.rings[0];
			break;
		case "polyline":
			points = geometry.paths[0];
			break;
		case "point":
			points = [geometry.x, geometry.y];
		default:
			console.log("setToolbarGeometry() only accepts point, polyline or polygon features");
			return false;
	}
	// replace toolbar geometry
	points.forEach(function(i){ // redraw each node of graphic - i hate doing it this way buy its basically guaranteed
		var mapPt = new Point(i[0],i[1]);
		var screenPt = map.toScreen(mapPt);
		map.emit("click", { bubbles: true, cancelable: true, mapPoint: mapPt, screenPoint: screenPt}); // inelegant but it works, the geometry is recreated the same way it came into this world, through map clicks
	});
}
  
	toolbar.on("draw-end", function(e){
		// the two if clauses below will stop a graphic from being drawn
		if(toolbar._geometryType == 'extent' && toolbar.extentGoodToGo == false){
			return; // exit, addDrawnToMap() not needed
		}
		if(e.geometry.type == "polygon" && e.geometry.rings[0].length < 4){
			e.geometry.rings[0] = e.geometry.rings[0].slice(0,2); // cut off unesesary point
			setToolbarGeometry(e.geometry);
			return;
		}
		$('#doneDrawing button, #cancelMeasureButton').html('Done'); // #cancelMeasureButton text change will only be noticeable with location measurements (point markers)
		rmUncloseDrawPolygonHandler();
		if($('#measureArea').hasClass('active')){ // for filling in the area label for the area measurement tool
			var pt = e.geometry.getExtent().getCenter();
			var unlab = getUnitAndLabel(true);
			var drawAreaUnitsLabel = unlab[1];
			var areaVal = geometryEngine.geodesicArea(e.geometry, $('#measureAreaUnits').val());
			var areaSym = new TextSymbol(areaVal.toFixed(2) + drawAreaUnitsLabel);
			areaSym.setColor( new dojo.Color([25, 140, 253])); 
			var font = new Font("12pt", Font.STYLE_BOLD,
				Font.VARIANT_NORMAL, Font.WEIGHT_BOLD,"arial"); 
			areaSym.setFont(font);
			var color = new Color([255,255,255]);
			areaSym.setHaloColor(color);
			areaSym.setHaloSize(1);  
			measurementLabelsLayer.add(new Graphic(pt, areaSym, {"labelKey": measureTimestamp, "unit": $('#measureAreaUnits').val()}));
		}
		addDrawnToMap(e);
		//enablePopup();
		/*
		try{
			if(templateLayers != undefined){
				for(var i = 0; i < templateLayers.length; i++){
				templateLayers[i].setInfoTemplate(wildcardInfoTemplate);				}
			}
		} catch(err){
			console.log('no template layers2: ' + err.message);
		}*/

		if($('#measureLocation').hasClass('active') === false){
			if(measurementLayer.graphics.length > 0){
				$('#clearMeasureButton, #selectMeasureButton').removeClass('disabledButton');
			}
			$('#cancelMeasureButton').addClass('disabledButton');
		}
		
		$('#pgon_dimInputDiv').stop().animate({height: '0px'}); // hide cogo options - connor -1/25/2018
	});
	
	setDrawToobarListener();
}

function setDrawToobarListener(){
	toolbar.active = false;
	toolbar.on('activate', function(){
		toolbar.active = true;
		disableToolSelection();
	});
	toolbar.on('deactivate', function(){
		toolbar.active = false;
		enableToolSelection();
	});
}

function getUnitAndLabel(forArea){ // forArea boolean, determines if the unit returned needs to be from the area options or the polyline options, not necessary if measureing for segments
	var label
	if(forArea){
		var units = $('#measureAreaUnits').val();
		switch(units){
			case '109402':
				label = " ac";
				break;
			case '109439':
				label = " sq mi";
				break;
			case '109442':
				label = " sq yds";
				break;
			case '109405':
				label = " sq ft";
				break;
			case '109414':
				label = " sq km";
				break;
				case '109404':
				label = " sq M";
				break;
				case '109401':
				label = " ha";
				break;
			default:
				label = " ft";
				break;
		}
	} else {
		var units = $('#drawUnits').val();
		switch(units){
			case '9002':
				label = " ft";
				break;
			case '9096':
				label = " yds";
				break;
			case '9035':
				label = " miles";
				break;
			case '9001':
				label = " meters";
				break;
			case '9036':
				label = " km";
				break;
			default:
				label = " ft";
				break;
		}
	}
	
	return [units,label];

}
	
function updateLabelUnits(unit1, unit2, label){

	/*
		Each measure polyline makes separate labels for each segment, so we have to get the number of segments
		from the line currently being drawn. To do this, we need to know how many polyline SEGMENTS exist in the
		measurementLayer. Then we use that to set "i" in the loop below. Note we only look a paths[0] since we don't
		provide a tool to make multi-path polylines
	*/
	var lineSegmentsCount = 0;
	for(var i = 0; i < measurementLayer.graphics.length; i++){
		if(measurementLayer.graphics[i].geometry.type === "polyline"){
			lineSegmentsCount += measurementLayer.graphics[i].geometry.paths[0].length - 1; // paths have 1 more point then they have segments
		}
	}
	
	// temp labels count is the number of labels already on screen from the current measurement that is in progress
	var tempLabelsCount = measurementLabelsLayer.graphics.length - lineSegmentsCount;
	console.log("temp labels count: " + tempLabelsCount);

	for(var ii = measurementLabelsLayer.graphics.length - tempLabelsCount; ii < measurementLabelsLayer.graphics.length; ii++){

		var graphic = measurementLabelsLayer.graphics[ii];
		var origLen = graphic.symbol.text;
		origLen = parseFloat(origLen.replace(/[^\d.-]/g, ''));
		var newLen = lengthConversion(origLen, unit1,unit2);
		measurementLabelsLayer.graphics[ii].symbol.text = newLen.toFixed(2) + label;
	}
	measurementLabelsLayer.redraw();
}
	
function lengthConversion(length, unit1, unit2){ // function that converts the total length to whatever the new units are for display during measurement graphic draw
	var asFeet;
	var convLength;
		switch(unit1){ // convert to feet
			case '9002': //ft
				asFeet = length;
				break;
			case '9096': //yds
				asFeet = length*3;
				break;
			case '9035': //miles
				asFeet = length*5280;
				break;
			case '9001': //meters
				asFeet = length*3.28084;
				break;
			case '9036': // km
				asFeet = length*3280.84;
				break;
			default:
				asFeet = length;
				break;
		}
		switch(unit2){ // convert from feet to whatever the user wants.
			case '9002':
				convLength = asFeet;
				return convLength;
			case '9096':
				convLength = asFeet/3;
				return convLength;
			case '9035':
				convLength = asFeet/5280;
				return convLength;
			case '9001':
				convLength = asFeet/3.28084;
				return convLength;
			case '9036':
				convLength = asFeet/3280.84;
				return convLength;
			default:
				convLength = asFeet;
				return convLength;
		}
	
	
}

//function to control select/delete/delete all button selectability
function activateDrawSubtools(){
	if (userGraphicsLayer.graphics.length != 0){
	   $('.drawEditButton').css({'pointer-events': 'inherit', 'opacity': '1'});
	} else {
		$('.drawEditButton, .deleteButton').css({'pointer-events': 'none', 'opacity': '.5'});
	}
}


function createStandardSymbol(style, color, size){
	var pointSymbol = new SimpleMarkerSymbol(style, size,
		new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID,
		color, 2),
		color);
	return pointSymbol;
}
function createSVGSymbol(path,color,size){
	var iconSymbol = new SimpleMarkerSymbol();
	iconSymbol.setPath(path);
	iconSymbol.setColor(color);
	var outline = new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID,
		color, 2);
	iconSymbol.setOutline(outline);
	iconSymbol.setSize(size);
	return iconSymbol;
}
	
//function to collect and set drawing tools options
function getDrawParams(featType){
	//r,b,g,a is the main color values
	//olr, olb, olg, ola are color values for outlines
	var r, g, b, a, ftSize, ptStyle, symbolStyle, olr, olg, olb, ola, olWidth, splitColor, splitOlColor,tmp,txt, fontBold, fontItalic, fontUnderline, fontFamily;
	switch (featType) {
		case "point":
			ftSize = parseInt($('#pointSize').val());
			if(ftSize > 30){
				ftSize = 30;
				$('#pointSize').val('30');
			}

			var pointColor = $('#pointColor').val();
			pointColor = pointColor.replace("rgb(", "[").replace("rgba(", "[").replace(")", "]");
			pointColor = JSON.parse(pointColor);

			var iconColor = new Color(pointColor);
			tmp = ($('.pointStyle.active').prop('id')).replace('style', '')
			switch(tmp){ //set point Style
				case "Circle":
					return createStandardSymbol(SimpleMarkerSymbol.STYLE_CIRCLE, iconColor, ftSize);
				case "Square":
					return createStandardSymbol(SimpleMarkerSymbol.STYLE_SQUARE, iconColor, ftSize);
				case "Diamond":
					return createStandardSymbol(SimpleMarkerSymbol.STYLE_DIAMOND, iconColor, ftSize);
				case "Cross":
					return createStandardSymbol(SimpleMarkerSymbol.STYLE_CROSS, iconColor, ftSize);
				case "Flag":
					var path = "M9.5,3v10c8,0,8,4,16,4V7C17.5,7,17.5,3,9.5,3z M6.5,29h2V3h-2V29z"
					return createSVGSymbol(path,iconColor,ftSize+5);
				case "Marker":
					var path = "M16,3.5c-4.142,0-7.5,3.358-7.5,7.5c0,4.143,7.5,18.121,7.5,18.121S23.5,15.143,23.5,11C23.5,6.858,20.143,3.5,16,3.5z M16,14.584c-1.979,0-3.584-1.604-3.584-3.584S14.021,7.416,16,7.416S19.584,9.021,19.584,11S17.979,14.584,16,14.584z";
					return createSVGSymbol(path,iconColor,ftSize+5);
				case "Star":
					var path = "M16,22.375L7.116,28.83l3.396-10.438l-8.883-6.458l10.979,0.002L16.002,1.5l3.391,10.434h10.981l-8.886,6.457l3.396,10.439L16,22.375L16,22.375z";
					return createSVGSymbol(path,iconColor,ftSize+5);
				case "Pointer":
					var path = "M15.834,29.084 15.834,16.166 2.917,16.166 29.083,2.917z";
					return createSVGSymbol(path,iconColor,ftSize+5);
				case "StarHollow":
					var path = "M16,22.375L7.116,28.83l3.396-10.438l-8.883-6.458l10.979,0.002L16.002,1.5l3.391,10.434h10.981l-8.886,6.457l3.396,10.439L16,22.375L16,22.375zM22.979,26.209l-2.664-8.205l6.979-5.062h-8.627L16,4.729l-2.666,8.206H4.708l6.979,5.07l-2.666,8.203L16,21.146L22.979,26.209L22.979,26.209z";
					return createSVGSymbol(path,iconColor,ftSize+5);
				case "House":
					var path = "M27.812,16l-3.062-3.062V5.625h-2.625v4.688L16,4.188L4.188,16L7,15.933v11.942h17.875V16H27.812zM16,26.167h-5.833v-7H16V26.167zM21.667,23.167h-3.833v-4.042h3.833V23.167z";
					return createSVGSymbol(path,iconColor,ftSize+5);
				case "Triangle":
					var path = "M 2 26 L 26 26 L 14 4 L 2 26";
					return createSVGSymbol(path,iconColor,ftSize+5);
				default:
					return createStandardSymbol(SimpleMarkerSymbol.STYLE_X, iconColor, ftSize);
			}


		case 'text': //font fam, bold, underline, italic
			fontFamily = $("#textFontFamily").val();
			fontBold = 'normal';
			fontItalic = 'normal';
			fontUnderline = 'normal';

			if($('#textBold').hasClass('active')){
				fontBold = 'bold';
			}
			if($('#textItalic').hasClass('active')){
				fontItalic = 'italic';
			}
			if($('#textUnderline').hasClass('active')){
				fontUnderline = 'underline';
			}

			txt = $('#textInputBox').val();
			ftSize = parseInt($('#textInputSize').val()); //will be font size of the text

			var textColor = $('#textInputColor').val();
			textColor = textColor.replace("rgb(", "[").replace("rgba(", "[").replace(")", "]");
			textColor = JSON.parse(textColor);

			var textSymbol = new TextSymbol(txt).setColor(new Color(textColor)).setAlign(TextSymbol.ALIGN_MIDDLE).setFont(new Font(ftSize).setFamily(fontFamily).setWeight(fontBold).setStyle(fontItalic).setDecoration(fontUnderline));
			return textSymbol;
			break;

		case "polyline":
			olWidth = parseInt($('#lineWidth').val());
			if(olWidth > 30){
				olWidth = 30;
				$('#lineWidth').val('30');
			}

			var lineColor = $('#lineColor').val();
			lineColor = lineColor.replace("rgb(", "[").replace("rgba(", "[").replace(")", "]");
			lineColor = JSON.parse(lineColor);

			var lineSymbol = new CartographicLineSymbol(CartographicLineSymbol.STYLE_SOLID,	new Color(lineColor), olWidth, CartographicLineSymbol.CAP_ROUND, CartographicLineSymbol.JOIN_MITER);
			return lineSymbol;
			break;

		default: //polygon
			olWidth = parseInt($('#polyOutlineWidth').val());
			if(olWidth > 30){
				olWidth = 30;
				$('#polyOutlineWidth').val('30');
			}
			//set fill color
			var fillColor = $('#polyFillColor').val();
			fillColor = fillColor.replace("rgb(", "[").replace("rgba(", "[").replace(")", "]");
			fillColor = JSON.parse(fillColor);

			//set outline color
			var olColor = $('#polyOutlineColor').val();
			olColor = olColor.replace("rgb(", "[").replace("rgba(", "[").replace(")", "]");
			olColor = JSON.parse(olColor);

			var polygonSymbol = new SimpleFillSymbol(SimpleFillSymbol.STYLE_SOLID,
			new CartographicLineSymbol(CartographicLineSymbol.STYLE_SOLID,
			new Color(olColor), olWidth, CartographicLineSymbol.CAP_ROUND, CartographicLineSymbol.JOIN_MITER),new Color(fillColor));
			return polygonSymbol;
			break;
	}
}

function addDrawnToMap(evt) {
	$('#map_layers').css('cursor', 'crosshair');
	$('#lengthOutput').addClass('hidden');
        
	if($("#addTextSymbol").hasClass("active")){
		if($('#textInputBox').val() === ''){
			// If no text to add, abort this function
			$('#textInputBox').addClass('colorPlaceholder');
			$('#textInputBox').attr('placeholder', 'Please enter text first');
			setTimeout(function(){
				$('#textInputBox').removeClass('colorPlaceholder');
				$('#textInputBox').attr('placeholder', 'Enter text here. Max length is 100 characters.');
			}, 2000);
			return false;
		}
	}
		
	var symbol;

	// if offline controls were used to draw something, cancel drawing...
	if(activeControl === "offlineControls"){
		cancelDrawingSelection();
	}

	//map.showZoomSlider();
	switch (evt.geometry.type) {
		case "point":
		case "multipoint":
			//symbol = pointSymbol;
			if ($("#addTextSymbol").hasClass("active")){
				symbol = textSymbol = getDrawParams("text");
			} else {
				if($('#measureToolsHeading').hasClass('ui-accordion-header-active') && activeControl =="drawControls"){ // added additional and statement for EH configuration. In EH both the drawing tools heading and measurement tools heading can have the class active at the same time
					symbol = measureLocationSymbol;
				} else {
					symbol = pointSymbol = getDrawParams("point");
				}
			}
			break;
		case "polyline":
		  //symbol = lineSymbol;
			if($('#measureToolsHeading').hasClass('ui-accordion-header-active') && activeControl =="drawControls"){
				symbol = measureDistanceSymbol;
			} else{
				symbol = lineSymbol = getDrawParams("polyline");
			}
			break;
		default:
			//symbol = polygonSymbol;
			if($('#measureToolsHeading').hasClass('ui-accordion-header-active') && activeControl =="drawControls"){
				symbol = measureAreaSymbol;
			} else {
				symbol = polygonSymbol = getDrawParams("polygon");
			}
			break;
	}
	
	if(evt.geometry.type == 'extent'){ // handle extent geometry types so they are treated as polygons during editing
		var extent2PolyGeom = new Polygon(map.spatialReference);
		extent2PolyGeom.addRing([[evt.geometry.xmin,evt.geometry.ymin],[evt.geometry.xmin,evt.geometry.ymax],[evt.geometry.xmax,evt.geometry.ymax],[evt.geometry.xmax,evt.geometry.ymin],[evt.geometry.xmin,evt.geometry.ymin]]);
		var graphic = new Graphic(extent2PolyGeom, symbol);
	} else {
		var graphic = new Graphic(evt.geometry, symbol);
	}

	//var jsonifiedGraphic = graphic.toJson();
	//graphic = esri.symbol.fromJson(jsonifiedGraphic);
	  
	if($('#measureToolsHeading').hasClass('ui-accordion-header-active') && activeControl =="drawControls"){
		
		// update Measure timestamp IF we are doing location measures
		if($('#measureLocation').hasClass('active')){
			var dateObj = new Date();
			measureTimestamp = dateObj.getTime().toString();
		}

		if($('.measureButton.active').prop('id') == 'measureDistance'){
			graphic.setAttributes({"labelKey": measureTimestamp, "unit": $('#measureDistanceUnits').val()}); // add timestamp and unit here
			measurementLayer.add(graphic);

			var output = geometryEngine.geodesicLength(evt.geometry, $('#measureDistanceUnits').val());
			//console.log(output);
			var unitStr;
			switch($('#measureDistanceUnits').val()){
				case '9002':
					unitStr = " ft";
					break;
				case '9096':
					unitStr = " yds";
					break;
				case '9035':
					unitStr = " miles";
					break;
				case '9001':
					unitStr = " meters";
					break;
				case '9036':
					unitStr = " km";
					break;
				default:
					unitStr = " ft";
					break;
			}

			var numberOfDMrows = $(".DMrow").length;

			$('#measureTotalLength' + numberOfDMrows).html(output.toFixed(1) + unitStr);

		} else if($('.measureButton.active').prop('id') == 'measureArea'){
			graphic.setAttributes({"labelKey": measureTimestamp, "unit": $('#measureAreaUnits').val()}); // add timestamp and unit here
			measurementLayer.add(graphic);

			var output = geometryEngine.geodesicArea(evt.geometry, $('#measureAreaUnits').val());
			var unitStr;
			switch($('#measureAreaUnits').val()){
				case '109402':
					unitStr = " ac";
					break;
				case '109439':
					unitStr = " mi&sup2;";
					break;
				case '109442':
					unitStr = " yds&sup2;";
					break;
				case '109405':
					unitStr = " ft&sup2;";
					break;
				case '109414':
					unitStr = " km&sup2;";
					break;
					case '109404':
					unitStr = " M&sup2;";
					break;
					case '109401':
					unitStr = " ha";
					break;
				default:
					unitStr = " ft";
					break;
			}

			if($('#measureAreaTable td').eq(0).html() === "no measurements"){
				$('#measureAreaTable td').eq(0).html("");
			}

			var numberOfAMrows = $(".AMrow").length + 1;
			// AM stands for distance measurement
			$('#measureAreaTable').append('<tr id="AMrow' + numberOfAMrows + '" class="AMrow"><td>Area ' + numberOfAMrows + '</td><td>' + output.toFixed(2) + unitStr + '</td></tr>');
			
		} else {
			graphic.setAttributes({"labelKey": measureTimestamp, "unit": $('#measureLocationUnits').val()}); // add timestamp and unit here
			measurementLayer.add(graphic);
		}
		disableMeasurementSnapping();
	} else if($('#offlineControls').hasClass('active') && activeControl =="offlineControls"){
		graphic.attributes = {};
		graphic.attributes.isBuffer = false;
		offlineTileDLTempGfxLayer.add(graphic);
		updateOfflineGeomBuffers();
	} else {
		userGraphicsLayer.add(graphic);
		activateDrawSubtools();
	}

	if(hasParcelLayer == true){
		//parcelLayer.enableMouseEvents();
		//parcelLayer.infoTemplate = template;
		for(layer in parcelLayers){
			window[layer].setInfoTemplate(parcelLayers[layer].infoTemplate);
			try {
				window[layer].enableMouseEvents();
			} catch (e) {

			}
			
		}
	}

	updateURLgeometry();
	rebuildMeasureLabels();
	if($('.measureButton.active').prop('id') === 'measureDistance' || $('.measureButton.active').prop('id') === 'measureArea' ){
		cancelDrawingSelection();
		$('.measureButton').removeClass('active');
	}
}

$('.pointStyle').on("click", function (){
	$('.pointStyle').removeClass('active');
	$(this).addClass('active');
});

$('.textFontOption').on("click", function (){
	if ($(this).hasClass('active')){
		$(this).removeClass('active');
	} else {
		$(this).addClass('active');
	}
});

function disableMeasurementSnapping() {
	$('#measurementSnappingButton').addClass('disabledButton');
	$('#measurementSnappingButton').addClass('fa-square-o').removeClass('fa-check-square-o');
}

function enableMeasurementSnappng(){
	$('#measurementSnappingButton').removeClass('disabledButton');
}

graphicStore = []; // create empty array for user graphics
function storeGraphic(graphic){
	// add random number to graphic attributes field
	// store that same number with the features original symbol styles
	graphic.attributes = Math.random();

	// create a new geometry object instance based on type
	var newGeom, symbolRefBreaker;
	symbolRefBreaker = JSON.stringify(graphic.symbol.toJson());
	if(graphic.geometry.type === "point"){
		newGeom = new Point(graphic.geometry.toJson());
		if(graphic.symbol.type === "textsymbol"){
			symbolRefBreaker = new TextSymbol(JSON.parse(symbolRefBreaker));
		} else {
			symbolRefBreaker = new SimpleMarkerSymbol(JSON.parse(symbolRefBreaker));
		}
	} else if (graphic.geometry.type === "polyline"){
		newGeom = new Polyline(graphic.geometry.toJson());
		symbolRefBreaker = new SimpleLineSymbol(JSON.parse(symbolRefBreaker));
	} else if (graphic.geometry.type === "polygon"){
		newGeom = new Polygon(graphic.geometry.toJson());
		symbolRefBreaker = new SimpleFillSymbol(JSON.parse(symbolRefBreaker));
	} else if (graphic.geometry.type === "extent"){
		newGeom = new Extent(graphic.geometry.toJson());
		symbolRefBreaker = new SimpleFillSymbol(JSON.parse(symbolRefBreaker));
	}

	graphicStore.push({'ID': graphic.attributes, 'Symbol': symbolRefBreaker, 'Geometry': newGeom});
}


// layers used in parperp functionality

userLinesLayer = new GraphicsLayer({ // used for parallel/perpendicular and snapping purposes, holds any valid geometry (polyline or polygon) and holds them as polyline types
	id: "userLinesLayer"
});
map.addLayer(userLinesLayer);

parperpTempLayer = new GraphicsLayer({ // used to add simple temporary graphics such as the dotted lines for rectange and parallel drawing along with the snapping point crosses
	id: "parperpTempLayer"
});

ninetyLayer = new GraphicsLayer({ // used to add simple temporary graphics such as the dotted lines for rectange and parallel drawing along with the snapping point crosses
	id: "ninetyLayer"
});

verticeSnapLayer = new GraphicsLayer({ // shows the user where thir selected vertice is when performing a edit-move-snapping session
	id: "verticeSnapLayer"
});

parperpHighlightLayer = new GraphicsLayer({ // layer used to highlight the segment being hovered over during parallel/perpendicular session
	id: "parperpHighlightLayer"
});



$('.createGPSPoint').on("click", function(){ // gets the users position for the purpose of creating a point feature out of their location
	// check if browser supports GeoLocation
	createGpsPointFeature(currentPosition);
});
function createGpsPointFeature(position){ // actually creates the point feature out of the user's position
	// Create AGO Point object
	if(!featureEditEnabled){
		console.log("createGpsPointFeature() requires feature edit module.");
		return;
	}
	var notGPSPoint = false;
	if(templatePicker.getSelected().featureLayer.name != 'GPS Point'){
		notGPSPoint = true; // if user is not creating a gps point already set to true so we can create gps point for them
	}
	var mapPt = new Point(position.coords.longitude, position.coords.latitude);
	var clickGeom = webMercatorUtils.geographicToWebMercator(mapPt); // api mod needed for this to work
	map.centerAt(mapPt); // get map to center at gps location
	var screenPt = map.toScreen(mapPt);
	//$('#map').trigger("focus");
	var callback = function(){
		if(currentMap == "cortevamiopsinfr" || currentMap === "dowmiopsutil" || currentMap == 'hsc'){
			var altitude = currentPosition.coords.altitude ? currentPosition.coords.altitude * 3.28084 : null;
			attributes = { // populate the point z - we are doing it here because within the 
				POINT_Z : altitude // convert to feet
			}
		}
		if(templatePicker.getSelected().featureLayer.geometryType!="esriGeometryPoint"){//lines,polys....continue mapping
			map.emit("click", { bubbles: true, cancelable: true, mapPoint: clickGeom, screenPoint: screenPt}); // use dojo.emit to simulate click event and draw point
		}else{
			if(templatePicker.getSelected().featureLayer.id=='ehSoilBoringsLayer'){
				attributes = { // populate the gps attr
					gps_latitude: position.coords.latitude,
					gps_longitude: position.coords.longitude
				};
			}
			isDrawEndFeature = true;
			ehOnDrawEnd(new Graphic(clickGeom), attributes, true); // stop using the emit function, weve got our own now
		}
		
		
		if(featureEditEnabled){
			if(notGPSPoint){ // make sure the user is not creating a gps point already, it would make no sense to make 2
				var gpsLayer, gpsAttributes, gpsGraphic;
				if(geometryEngine.contains(editableFeaturesBoundary,clickGeom)){ // make sure gps point is inside the bounds
					for(var i = 0; i < templatePicker.featureLayers.length; i++){
						if(templatePicker.featureLayers[i].name == 'GPS Point'){
							gpsLayer = templatePicker.featureLayers[i];
							gpsAttributes = lang.mixin({}, gpsLayer.templates[0].prototype.attributes);
							gpsGraphic = new Graphic(clickGeom, null, gpsAttributes);
							gpsGraphic.attributes.pos_accuracy = position.coords.accuracy; // add accuracy to attributes
							//if(currentMap == 'devEH' || currentMap == 'fgdemoEH'){ // ehChange
							gpsGraphic.attributes.Latitude = position.coords.latitude; // add accuracy to attributes
							gpsGraphic.attributes.Longitude = position.coords.longitude; // add accuracy to attributes
							//}
						}
					}
					try {
						gpsLayer.applyEdits([gpsGraphic], null, null, function(response){
							checkForApplyEditsError(response);
							if(sw !== false && sw !== "down"){
								caches.delete('generalFiles');
							}
						}, errorBack); // add gps point to gps point layer in eh gdb
					} catch(err){
						console.log('Error in applyEdits for GPS Points Layer: ' + err.message);
					}
				}
			}
		}
	}
	var attributes;
	if(currentMap === 'dowbrine'){
		orthoHeightCorrection(position, function(res){
			var altitude = res ? ((Math.round(res * 3.28084 *100)/100) + 6.5) : null;
			attributes = { // populate the point z - we are doing it here because within the 
				POINT_Z : altitude // convert to feet
			}
			callback();
		});
	} else {
		callback();
	}
	
}

function gpsError(error){
	//Handle geolocation functionality denied
	if(error.code == 1){
		//alert('Note: Location permission Denied. Please check your browser and/or system security settings if you wish to use GPS.');
		showAlert('Notice:', 'Location permission denied. Please see help section for instructions on enabling browser location access.');
	}
}

/// the reason you'll find button event handlers withing this pretend 90 degree drawing class
/// is beacuse they then have access to variables that are only in reach withing this function
/// didn't want to add more globals the sea of variables we already have
/// event handlers on buttons are unbinded when exiting the tool and registered when entering the tool
function initialize90Deg(){
	ninetyHandlersActive = true;
	$('#finishNinety').addClass('disabledButton');
	$('#lengthOutput').addClass('hidden');
	if(snapManager.alwaysSnap){ // if the ninty layer is removed from snapping we can utilize the default snapping shiz
		var layerInfos = snapManager.layerInfos;
		for(var i=0; i<layerInfos.length;i++){
			if(layerInfos[i].layer.id == 'ninetyLayer'){
				layerInfos.splice(i,1);
				snapManager.setLayerInfos(layerInfos); // set new layer info
				break;
			}
		}
	}
	$('#drawOptionsTable').addClass('disabledButton');
	$('#ninetyDegreeOptionsBox').fadeIn('fast');
	ninetyLayer.clear();
	toolbar.lineSymbol.width = 0; // mak
	if(toolbar._graphic){
		toolbar._graphic.draw();
	}
	var bearingGraphic;
	var nGraphic1;
	var originX;
	var originY;
	var secondaryX;
	var secondaryY;
	var angle = 270;

	function toggleStage(stage){ // get event handlers ready and changes the little dialog box
		$('#ninetyStage2, #ninetyStage3, #ninetyStage1').addClass('hidden');
		switch(stage){
			case 1:
				$('#ninetyStage1').removeClass('hidden');
				break;
			case 2:
				$('#ninetyInt').val('0');
				$('#ninetyFloat').val('.00');
				$('#ninetyStage2').removeClass('hidden');
				break;
			case 3:
				$('#finishNinety').addClass('disabledButton');
				$('#ninetyStage3').removeClass('hidden');
				startStage3();
				break;
		}
	}
	toggleStage(1); // set to stage one

	ninetyClickStage1 = map.on("click", function(evt){ // first stage, creates first point in drawing, user can then rotate the bearing graphic in stage 2
		originX = toolbar._points[toolbar._points.length-1].x;
		originY = toolbar._points[toolbar._points.length-1].y;
		rotateLineOnAxis(originX,originY,angle,map.getScale()/14);
		toggleStage(2);
		ninetyLayer.add(new Graphic(new Point(originX,originY,map.spatialReference), verticeSnapMarker));
		ninetyClickStage1.remove();
	});

	function startStage3(){ // starts stage 3 event handlers for mouse move and click
		ninetyMouseMove = dojo.connect(map,'onMouseMove',function(e){
			if(snapManager.alwaysSnap && typeof(snapManager._snappingPoint) !== 'undefined'){ // if snapping enabled and a snap point is available use this
				secondaryX = snapManager._snappingPoint.x;
				secondaryY = snapManager._snappingPoint.y;
			} else { // or dont
				secondaryX = e.mapPoint.x;
				secondaryY = e.mapPoint.y;
			}
			buildNinetyGraphics();
		});
		ninetyClickStage3 = map.on("click", function(evt){ // stage 3, locks the final point in place creating the graphic which the user can then create a feature from.
			dojo.disconnect(ninetyMouseMove);
			secondaryX = toolbar._points[toolbar._points.length-1].x;
			secondaryY = toolbar._points[toolbar._points.length-1].y;
			buildNinetyGraphics(true);
			ninetyClickStage3.remove();
		});
	}

	$('#ninetyInt, #ninetyFloat').on('input', function(e){ // handles changes to the angle inputs
	   var tempAngle = parseInt($('#ninetyInt').val()) + parseFloat($('#ninetyFloat').val());
	   if(tempAngle == 0){
			 angle = 270;
			 rotateLineOnAxis(originX,originY,angle,map.getScale()/14);
	   } else if(isFinite(tempAngle)){
			angle = (tempAngle + 90) * -1;
			rotateLineOnAxis(originX,originY,angle,map.getScale()/14);
	   }
	});

	$('#ninetyInt, #ninetyFloat').on('keyup', function(){ // trigger change on keyup  yayaya
	   $('#ninetyInt, #ninetyFloat').trigger('change');
	});

	$('#setNinetyAngle').on("click", function(){ // nuff said
		toggleStage(3);
	});

	ninetyZoomHandler = map.on('zoom-end', function(e){ // the bearing graphic's size is a function of the maps current scale, this allows for resizing
		if(originX){
			rotateLineOnAxis(originX,originY,angle,map.getScale()/14);
		}
	});

	function buildNinetyGraphics(finished){ // actualy builds the 90 degree angle geometry/ graphic, used in mousemove and mouse click events in stage 3
		ninetyLayer.clear();
		var statePlaneOrigin = projectGeometry(new Point(originX, originY, map.spatialReference), statePlaneSR);
		var statePlaneSecondary = projectGeometry(new Point(secondaryX, secondaryY, map.spatialReference), statePlaneSR);
		//var statePlaneOrigin = Proj4js.transform(webDef, stateDef, new Proj4js.Point(originX, originY)); // transfer to state plane coordinates for each segment
		//var statePlaneSecondary = Proj4js.transform(webDef, stateDef, new Proj4js.Point(secondaryX,secondaryY));

		if(angle == 90 || angle == 270){ // handle vertical for which there is no slope
			var intersectX = statePlaneOrigin.x;
			var intersectY = statePlaneSecondary.y;
		} else { // handle everything else
			var originSlope = Math.tan(angle*Math.PI/180);
			var secondarySlope = Math.tan((angle+90)*Math.PI/180);
			var originIntercept = statePlaneOrigin.y - (originSlope * statePlaneOrigin.x);
			var secondaryIntercept = statePlaneSecondary.y - (secondarySlope * statePlaneSecondary.x);

			var ixin = originSlope-secondarySlope;
			var ixing = secondaryIntercept - originIntercept;
			var intersectX = ixing/ixin;
			var intersectY = originSlope*intersectX + originIntercept;
		}

		var webIntersect = projectGeometry(new Point(intersectX, intersectY, statePlaneSR),  map.spatialReference); // transfer to state plane coordinates for each segment
		//var webIntersect = Proj4js.transform(stateDef, webDef, new Proj4js.Point(intersectX, intersectY)); // transfer to state plane coordinates for each segment
		var nGraphicCoords = [[originX,originY],[webIntersect.x,webIntersect.y],[secondaryX,secondaryY]];
		if(finished){
			var symbol = ninetyBearingSymbol;
		} else {
			var adjustedAngle;
			var unlab = getUnitAndLabel();
			drawUnits = unlab[0]; // update drawunits and label if the user changes them
			drawUnitsLabel = unlab[1];
			var labelColor = new dojo.Color([255, 255, 0]);
			var font = new Font("12pt", Font.STYLE_BOLD,
				Font.VARIANT_NORMAL, Font.WEIGHT_BOLD,"arial");

			var lengthSym1 = new TextSymbol(Math.round(geometryEngine.geodesicLength(new Polyline(map.spatialReference).addPath([nGraphicCoords[0],nGraphicCoords[1]]), drawUnits)) +drawUnitsLabel);
			var lengthSym2 = new TextSymbol(Math.round(geometryEngine.geodesicLength(new Polyline(map.spatialReference).addPath([nGraphicCoords[1],nGraphicCoords[2]]), drawUnits)) +drawUnitsLabel);
			lengthSym1.setFont(font);
			lengthSym2.setFont(font);
			lengthSym1.setColor(labelColor);
			lengthSym2.setColor(labelColor);
			if(angle > 360)
				angle %= 360;

			adjustedAngle = 360 - angle;

			if(adjustedAngle > 359)
				adjustedAngle %= 360;

			var labelDeg = adjustedAngle;
			var labelDeg2 = adjustedAngle - 90;

			if(Math.abs(labelDeg)>90 && Math.abs(labelDeg)<270)
				labelDeg = labelDeg - 180;

			if(Math.abs(labelDeg2)>90 && Math.abs(labelDeg2)<270)
				labelDeg2 = labelDeg2 - 180;

			lengthSym1.setAngle(labelDeg);
			lengthSym2.setAngle(labelDeg2);
			lengthSym1.setOffset(0,5);
			lengthSym2.setOffset(0,5);
			var xm1 = (originX + webIntersect.x)/2;
			var ym1 = (originY + webIntersect.y)/2;
			var xm2 = (secondaryX + webIntersect.x)/2;
			var ym2 = (secondaryY + webIntersect.y)/2;
			ninetyLayer.add(new Graphic(new Point(xm1,ym1,map.spatialReference), lengthSym1));
			ninetyLayer.add(new Graphic(new Point(xm2,ym2,map.spatialReference), lengthSym2));

			var symbol = plineTempLineSymbol;
		}
		nGraphic1 = new Graphic(new Polyline(map.spatialReference).addPath(nGraphicCoords),symbol);

		ninetyLayer.add(nGraphic1);
		$('#finishNinety').removeClass('disabledButton');
	}

	$('#finishNinety').on("click", function(){ // fill the toolbar with the 90 degree graphic and finish drawing
		if(nGraphic1){
			toolbar._graphic.setGeometry(nGraphic1.geometry);
			toolbar._points= $.map(nGraphic1.geometry.paths[0],function(i){
			   return new Point(i,map.spatialReference);
			});
			toolbar.finishDrawing();
			ninetyLayer.clear();
			cancel90Deg();
		}
	});

	function rotateLineOnAxis(originX,originY,angle,distance){ // distance in feet
		var adjustedAngle;
		if(angle > 360){
			angle %= 360;
		}
		adjustedAngle = 360 - angle;

		if(adjustedAngle > 359){
			adjustedAngle %= 360;
		}

		var statePlaneTrans = projectGeometry(new Point(originX, originY, map.spatialReference), statePlaneSR);
		//var statePlaneTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(originX, originY));

		var yDist1 = (Math.sin(angle * Math.PI/180)) * distance;
		var xDist1 = (Math.cos(angle * Math.PI/180)) * distance;
		var tempX1 = statePlaneTrans.x + xDist1;
		var tempY1 = statePlaneTrans.y + yDist1;

		var yDist2 = (Math.sin((angle-180) * Math.PI/180)) * distance;
		var xDist2 = (Math.cos((angle-180) * Math.PI/180)) * distance;
		var tempX2 = statePlaneTrans.x + xDist2;
		var tempY2 = statePlaneTrans.y + yDist2;

		var webMercTrans1 = projectGeometry(new Point(tempX1, tempY1, statePlaneSR), map.spatialReference);
		var webMercTrans2 = projectGeometry(new Point(tempX2, tempY2, statePlaneSR), map.spatialReference);
		/*
		var webMercTrans1 = Proj4js.transform(stateDef, webDef, new Proj4js.Point(tempX1, tempY1));
		var webMercTrans2 = Proj4js.transform(stateDef, webDef, new Proj4js.Point(tempX2, tempY2));
		*/
		var tempSegment = new Polyline(map.spatialReference);
		tempSegment.addPath([[webMercTrans2.x,webMercTrans2.y],[webMercTrans1.x,webMercTrans1.y]]);
		ninetyLayer.remove(bearingGraphic);
		bearingGraphic = new Graphic(tempSegment,ninetyBearingSymbol);
		ninetyLayer.add(bearingGraphic);
	}

	$(this).removeClass('btn-default').addClass('btn-danger');
}

function cancel90Deg(){ // makes everyhting go back to normal from a 90 degree drawing tool session
	if(ninetyHandlersActive){
		$('#drawOptionsTable').removeClass('disabledButton');
		$('#ninetyDegreeOptionsBox').fadeOut('fast');
		toolbar.lineSymbol.width = 2; // mak
		if(ninetyClickStage1){
			ninetyClickStage1.remove();
		}
		if(ninetyClickStage3){
			ninetyClickStage3.remove();
		}
		if(ninetyZoomHandler){
			ninetyZoomHandler.remove();
		}
		if(ninetyMouseMove){
			dojo.disconnect(ninetyMouseMove);
		}
		$('#ninetyInt, #ninetyFloat').off();
		$('#setNinetyAngle').off();
		$('#finishNinety').off();
		$(this).removeClass('btn-danger').addClass('btn-default');
		ninetyLayer.clear();
		if(toolbar.active){
			if(toolbar._geometryType = 'polyline'){
				toolbar.deactivate();
				toolbar.activate('polyline');
			}
		}
		ninetyHandlersActive = false;
	}
}


$('#cancelNinetyDegree').on("click", function(){
	cancel90Deg();
});

$('#ninetyDegTool').on("click", function(){
	if($(this).hasClass('btn-default')){
		initialize90Deg();
	} else {
		cancel90Deg();
	}
});

$('#pgon_polarAngle, #pgon_polarDistance').on('keyup', function(){
	if(isNumber($('#pgon_polarAngle').val()) && isNumber($('#pgon_polarDistance').val())){
		$('#gonRevAngle').removeClass('disabledButton btn-default').addClass('btn-primary');
	} else {
		$('#gonRevAngle').removeClass('btn-primary').addClass('disabledButton btn-default');
		parperpTempLayer.clear();
	}
});

$('#pline_polarAngle, #pline_polarDistance').on('keyup', function(){
	if(isNumber($('#pline_polarAngle').val()) && isNumber($('#pline_polarDistance').val())){
		$('#revAngle').removeClass('disabledButton btn-default').addClass('btn-primary');
	} else {
		$('#revAngle').removeClass('btn-primary').addClass('disabledButton btn-default');
		parperpTempLayer.clear();
	}
});

// handler for reverse angle input
$('.reverseAngle').on("click", function(){
	if(toolbar._geometryType == 'polyline'){
		if(isNumber($('#pline_polarAngle').val())){
			var currentAngle = parseFloat($('#pline_polarAngle').val());
			var newAngle = currentAngle %= 360; // get angle within the 0 - 360 range
			if(newAngle + 180 > 359){
				newAngle = newAngle - 180; // reverse the angle appropriately based on whether the number will appear as being above or below 360 degrees
			} else {
				newAngle = newAngle + 180;
			}
			$('#pline_polarAngle').val(newAngle); // update angle input value
			$('#pline_polarAngle').trigger('keyup'); // if more keyup events are attached to this input it would be wise to test thoroughly.
		}
	} else {
		if(isNumber($('#pgon_polarAngle').val())){
			var currentAngle = parseFloat($('#pgon_polarAngle').val());
			var newAngle = currentAngle %= 360; // get angle within the 0 - 360 range
			if(newAngle + 180 > 359){
				newAngle = newAngle - 180; // reverse the angle appropriately based on whether the number will appear as being above or below 360 degrees
			} else {
				newAngle = newAngle + 180;
			}
			$('#pgon_polarAngle').val(newAngle); // update angle input value
			$('#pgon_polarAngle').trigger('keyup'); // if more keyup events are attached to this input it would be wise to test thoroughly.
		}
	}
});

$('#squareAndFinish').on("click", function(){ // square and finish functionality for polygon drawing
	if(toolbar._points.length == 3){ // make sure correct number of points are present in the polygon
		if(snapManager.alwaysSnap){ // check if auto snap is on turn it off if so
			parperpDisableAutoSnap(); // disable the auto snap if on
		}

		var point1 = projectGeometry(new Point(toolbar._points[0].x, toolbar._points[0].y, map.spatialReference), statePlaneSR);
		var point2 = projectGeometry(new Point(toolbar._points[1].x, toolbar._points[1].y, map.spatialReference), statePlaneSR);
		var point3 = projectGeometry(new Point(toolbar._points[2].x, toolbar._points[2].y, map.spatialReference), statePlaneSR);
		/*
		var point1 = Proj4js.transform(webDef, stateDef, new Proj4js.Point(toolbar._points[0].x,toolbar._points[0].y)); // get stateplane points
		var point2 = Proj4js.transform(webDef, stateDef, new Proj4js.Point(toolbar._points[1].x,toolbar._points[1].y));
		var point3 = Proj4js.transform(webDef, stateDef, new Proj4js.Point(toolbar._points[2].x,toolbar._points[2].y));
		*/
		var yDiff = point2.y - point1.y; // calculate differences in coordinates
		var xDiff = point2.x - point1.x;
		var newX = point3.x-xDiff; // create 4th point based on distance between 1 and 2nd point from the 3rd point.
		var newY = point3.y-yDiff;
		var point4 = projectGeometry(new Point(newX, newY, statePlaneSR), map.spatialReference);
		//var point4 = Proj4js.transform(stateDef, webDef, new Proj4js.Point(newX,newY));
		var mapPt = new Point(point4.x,point4.y);
		var screenPt = map.toScreen(mapPt);
		$('#map').trigger("focus");
		map.emit("click", { bubbles: true, cancelable: true, mapPoint: mapPt, screenPoint: screenPt}); // use map click to add 4th point
		toolbar.finishDrawing();

	} else{
		console.log('somehow this button wasnt disabled');
	}
});

window.orthoHeightCorrection = function(position, callback){

	////disabling now..
	//callback(position.coords.altitude);
	//return;

	if(!position.coords.altitude){
        callback(position.coords.altitude); // just return null or zero
        return;
    }
	//var url = 'https://services7.arcgis.com/xdHvC4rURPAw2lXw/ArcGIS/rest/services/fgGeoidModel/FeatureServer/0';
	var url ='https://app.fetchgis.com/geoservices/fgis/fgGeoidModel/FeatureServer/0';
	var qt = new QueryTask(url);
	var q = new query();
	q.outFields = ["*"];
	q.geometry = new Point(position.coords.longitude, position.coords.latitude);
	q.spatialRelationship = query.SPATIAL_REL_INTERSECTS;
	qt.execute(q, function(res){
		if(res.features.length){
			callback(position.coords.altitude - res.features[0].attributes.OFFS_METER);
		} else {
			showMessage('Warning: altitude could not be calculated. Falling back to default geolocation altitude.', null, 'info');
			callback(position.coords.altitude);
		}
	}, function(){
		showMessage('Warning: altitude could not be calculated. Falling back to default geolocation altitude.', null, 'info');
		callback(position.coords.altitude);
	});
}

function findDistance(x, y, x1, y1, x2, y2) { // helper function for find segment angle, calculates distance from point to line
	var differenceA = x - x1; // calculate coordinate differences
	var differenceB = y - y1;
	var differenceC = x2 - x1;
	var differenceD = y2 - y1;
	var dot = differenceA * differenceC + differenceB * differenceD; // calculate cross products of differences
	var length_sq = differenceC * differenceC + differenceD * differenceD;
	var param = -1;
	if (length_sq != 0) //in case of 0 length line
		param = dot / length_sq;
	var xx, yy;
	if (param < 0) {
	  xx = x1;
	  yy = y1;
	}
	else if (param > 1) {
	  xx = x2;
	  yy = y2;
	}
	else {
	  xx = x1 + param * differenceC;
	  yy = y1 + param * differenceD;
	}
	var distanceX = x - xx;
	var distanceY = y - yy;
	return Math.sqrt(distanceX * distanceX + distanceY * distanceY); // return euclidean distance from line ( will be coordinates)
}

function findSegmentAngle(evt, forSegment, perpBool){  // returns wither a degree or a segment for use in highlighting the segment the mouse is hovering over or for calculating the degree at which a parallel or perpendicular line should travel
	var mouseX = evt.mapPoint.x; // mouse click map pt
	var mouseY = evt.mapPoint.y;
	if(evt.graphic.geometry.type == "polyline"){
	   var segmentArray = evt.target.e_graphic.geometry.paths[0]; // get the segment nodes
	} else {
	   var segmentArray = evt.target.e_graphic.geometry.rings[0];
	}
	var closestDistance = Infinity; // intial variable to be tested against for segment candidate scoring

   for(i=0;i<=segmentArray.length - 2;i++){ // iterate the nodes

	   var firstNode = segmentArray[i]; // get first node of segment
	   var secondNode = segmentArray[i+1]; // get second node of segment
	   var distanceFromLine = findDistance(mouseX,mouseY,firstNode[0],firstNode[1],secondNode[0],secondNode[1]);
	   if(distanceFromLine < closestDistance){
		   var closestSegment = firstNode.concat(secondNode);
		   closestDistance = distanceFromLine; // update closestDistance to lowest score
	   }

   }
   if(!(forSegment)){
	   var statePlaneTrans1 = projectGeometry(new Point(closestSegment[0], closestSegment[1], map.spatialReference), statePlaneSR);
	   var statePlaneTrans2 = projectGeometry(new Point(closestSegment[2], closestSegment[3], map.spatialReference), statePlaneSR);
	 /*  
	   var statePlaneTrans1 = Proj4js.transform(webDef, stateDef, new Proj4js.Point(closestSegment[0], closestSegment[1])); // transfer to state plane coordinates for each segment
	   var statePlaneTrans2 = Proj4js.transform(webDef, stateDef, new Proj4js.Point(closestSegment[2], closestSegment[3]));
	 */ 
	   var degree = Math.atan2(statePlaneTrans2.y - statePlaneTrans1.y, statePlaneTrans2.x - statePlaneTrans1.x) * 180 / Math.PI; // calculate angle
	   if(perpBool){
		   degree = degree + 90; // if perpendicular simply add 90 degrees
	   }
	   if(degree < 0){
		   degree = 360 + degree; // make the number non negative, again, this would look bad
	   }
	   degree  = degree %= 180; // get the angle under 181 as it would look nasty otherwise
	   var polarEntryAngle = degree * -1;
	   polarEntryAngle += 90;
	   if(toolbar._geometryType == 'polyline'){
		   $('#pline_polarRadio').trigger('click'); // change to polar coordinates
		   $('#pline_polarAngle').val((Math.round(polarEntryAngle*1000)/1000)); // round angle for Bryan's cogo handler and display
	} else {
		   $('#pgon_polarRadio').trigger('click'); // change to polar coordinates
		   $('#pgon_polarAngle').val((Math.round(polarEntryAngle*1000)/1000)); // round angle for Bryan's cogo handler and display
	   }
	   return degree;
   } else{
	   return closestSegment;
   }

}

function withinExtent(graphic){ // works similar to esri's .contains except it actually works. this might be useful outside of parperp stuff
	if(graphic._extent){
		// create geometries from the graphic's and maps extent
		var graphicGeom = new Polygon([[graphic._extent.xmin,graphic._extent.ymin],[graphic._extent.xmin,graphic._extent.ymax],[graphic._extent.xmax,graphic._extent.ymax],[graphic._extent.xmax,graphic._extent.ymin],[graphic._extent.xmin,graphic._extent.ymin]]);
		var extentGeom = new Polygon([[map.extent.xmin,map.extent.ymin],[map.extent.xmin,map.extent.ymax],[map.extent.xmax,map.extent.ymax],[map.extent.xmax,map.extent.ymin],[map.extent.xmin,map.extent.ymin]]);
		if(geometryEngine.intersects(graphicGeom,extentGeom)){ // if the extents intersect in any way return true, the graphic is within the extent
			return true;
		} else{
			return false;
		}
	} else{
		return false;
	}
}

function copyUserLines(layerArr){ // function to create a layer composed of all feature layers that will be able to be acted on by parperp functionality as polylines.
	userLinesLayer.clear();
	if(parperpHandlerActive){ // check if parperp session active
		layerArr.forEach(function(i){ /// loop through specified layers
			if(i.visibleAtMapScale && i.visible && i.graphics.length > 0){ // make sure the graphic actually contains features and is visible at the current scale
				var featuresArr = i.graphics;
				featuresArr.forEach(function(j){ // for each individual graphic within the layer
					if(withinExtent(j) && j.visible){ // check if feature is within the current map extent and that the individual feature is visible
						if(j.geometry.type == "polyline" || j.geometry.type == "polygon"){ // ignore point type features
							try{
								var style = j._shape.strokeStyle; // try keeping the same symbology as the original graphic
								var strokeWidth = style.width;
								if(isMobile){ //  additional width for mobile (its hard to tap the segments with your finger)
									strokeWidth = strokeWidth+10; // increase stroke width so it will be easier to grab/hover over
								} else {
									strokeWidth = strokeWidth+2; // increase stroke width so it will be easier to grab/hover over
								}
								var strokeStyle = new CartographicLineSymbol(CartographicLineSymbol.STYLE_SOLID, new Color([style.color.r, style.color.g, style.color.b]), strokeWidth, CartographicLineSymbol.CAP_ROUND, CartographicLineSymbol.JOIN_MITER); // set style to match the user graphic except for color;
							} catch(err){
								var strokeStyle = new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID, new Color([0,255,0]), 3); // if a style cannnot be extracted then a default symbology is used
							}
							if(j.geometry.type == "polyline"){
								userLinesLayer.add(new Graphic(new Polyline(map.spatialReference).addPath(j.geometry.paths[0]),strokeStyle)); // add the new polyline graphic
							}
							if(j.geometry.type == "polygon"){
								userLinesLayer.add(new Graphic(new Polyline(map.spatialReference).addPath(j.geometry.rings[0]),strokeStyle)); // turn polygon into polyline geometry and add to layer
							}
						}
					}
				});
			}

		});
		if(isMobile){
			strokeWidth = 10;
		} else {
			strokeWidth = 3;
		}
		var strokeStyleDrawing = new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID, new Color([255,165,0]), strokeWidth); // now for the currenlty drawing graphic (graphic within the toolbar)
		if(toolbar._graphic.geometry.type == 'polyline'){
			userLinesLayer.add(new Graphic(new Polyline(map.spatialReference).addPath(toolbar._graphic.geometry.paths[0]), strokeStyleDrawing)); // add graphic
		}
		if(toolbar._graphic.geometry.type == 'polygon'){
			userLinesLayer.add(new Graphic(new Polyline(map.spatialReference).addPath(toolbar._graphic.geometry.rings[0]), strokeStyleDrawing)); // if polygon convert to polyline and add to layer
		}
		$('#_internal_LabelLayer_layer').after($('#userLinesLayer_layer')); // place this layer above all other layers so it can be interacted with without impedence
	}
}
/// this function has to deactivate the draw toolbar, if any changes occur to the draw toolbars activate method then this will need to be checked
function switchSnapping(snapBool){ // turns auto snap on or off and redraws graphic, if parperp is active and does not enable auto snap, instead tells the parperp functinality it should be snapping. for drawing toolbar
	var copyType;
	var tempGeometry;

	if(!(parperpHandlerActive) && !(rectangleHandlerActive)){
		if(toolbar._points.length == 0 || toolbar._geometryType == 'point'){ // point features will never have graphics already drawn
			var newGraphic = true;
		} else{ // if the graphic already contains nodes then the geometry needs to be copied, this boolean tells the rest of the function to do that
			var newGraphic = false;
		}
		if(toolbar._geometryType == 'polyline'){
			copyType = "POLYLINE"; // set the geometry type to copy and to reactivate the toolbar
			if(!(newGraphic)){
				tempGeometry = toolbar._graphic.geometry.paths[0]; // store the current geometry
			}
		}
		if(toolbar._geometryType == 'polygon'){
			copyType = "POLYGON"; // set the geometry type to copy and to reactivate the toolbar
			if(!(newGraphic)){
				tempGeometry = toolbar._graphic.geometry.rings[0]; // store the current geometry
			}
			rmUncloseDrawPolygonHandler(); // remove the smackdown, it will be reset when the click events below get fired!
		}
		if(toolbar._geometryType == 'point'){
			copyType = "POINT"; // no need to set the geometry as point features will never have coordinates at this point
		}
		toolbar.deactivate(); // deactivate the current drawing toolbar
		snapManager = map.enableSnapping({ // enable snapping as automatic using the snapBool variable
			layerInfos: snappingLayerInfos(), alwaysSnap: snapBool, snapKey: has("mac") ? keys.META : keys.CTRL, snapPointSymbol: parperpSnappingCross
		});

		toolbar.activate(Draw[copyType]); // reactivate the toolbar, snapping will be availble automatically now
		if(!(newGraphic)){ // if the previous geometry needs to be recreated
			tempGeometry.forEach(function(i){ // redraw each node of graphic
				var mapPt = new Point(i[0],i[1]);
				var screenPt = map.toScreen(mapPt);
				map.emit("click", { bubbles: true, cancelable: true, mapPoint: mapPt, screenPoint: screenPt}); // inelegant but it works, the geometry is recreated the same way it came into this world, through map clicks
			});
		}

	}
}


$('.enbSnapping, .enbEditSnapping').on("click", function(e){ // handler for snapping buttons
	if($(this).hasClass('enbEditSnapping')){ //for edit toolbar snapping
		if($(this).hasClass('fa-square-o')){
			enbEditSnapping();
		} else {
			disableEditSnapping();
		}
	} else { // for draw toolbar snapping - requires more detailed functionality
		if($(this).hasClass('fa-square-o')){
			enbSnapping();
		} else {
			disableAutoSnap();
		}
	}
});

enbSnapping = function(){ // function to generically enable auto snap
	$('.enbSnapping').removeClass('fa-square-o').addClass('fa-check-square-o');
	snappingActive = true; /// this boolean does not necessarily tell you if the auto snap is enabled, a more accurate description of its use is that it determines if snapping is supposed to APPEAR to be happening (parperp stuff does not use the auto snapping however this boolean still needs to be true while it is running)
	switchSnapping(true); // deactivate and reactivate the toolbar for autosnap
}

enbEditSnapping = function(){
	$('.enbEditSnapping').removeClass('fa-square-o').addClass('fa-check-square-o');
	snapManager = map.enableSnapping({ // enable auto snapping, on edit toolbar the toolbar does not need to be deactivated and reactivated
		layerInfos: snappingLayerInfos(), alwaysSnap: true, snapKey: has("mac") ? keys.META : keys.CTRL, snapPointSymbol: parperpSnappingCross
	});


	var verticeIndex = 0; // reset vetice index, this is the index of the vertice that will be used for snapping
	var snapToNode; // intitialize the variable that will hold the node that will be snapped to in the handler for move-stop
	var selectedNodeGraphic; // 'X' graphic design marking which vertice will be used for snapping
	var snapped = false; // boolean to tell if a snapping point was found, if false the move-stop handler will not run

	var maxIndex =0;
	if(editToolbar._graphic && editToolbar._graphic.geometry.type != 'point'){ // determine the max index
		var originalCoords = editToolbar._graphic.geometry.type == 'polyline' ? editToolbar._graphic.geometry.paths : editToolbar._graphic.geometry.rings;
		for(var i=0;i<originalCoords.length;i++){
			for(var j=0; j < originalCoords[i].length; j++){
				maxIndex +=1;
			}
		}
		if(editToolbar._graphic.geometry.type == 'polygon') maxIndex -= 1;
	} else {
		maxIndex = 1;
	}


	function getSnapVertex(){ // get the coordinate at the current vertice index
		if (editToolbar._graphic.geometry.type == 'point'){
			var tempGeom = editToolbar._graphic.geometry;
			return [tempGeom.x, tempGeom.y];
		}
		var originalCoords = editToolbar._graphic.geometry.type == 'polyline' ? editToolbar._graphic.geometry.paths : editToolbar._graphic.geometry.rings;
		var counter = 0;
		for(var i=0;i<originalCoords.length;i++){
			var featureCoords = originalCoords[i];
			for(var j=0; j < featureCoords.length; j++){
				if(counter == verticeIndex)
					return featureCoords[j];
				counter +=1;
			}
		}
	}

	function getSingleArrayCoords(){ // for use with multi-featured features, sometimes its easier to have them all in one array instead of an array of arrays for comparisons
		if (editToolbar._graphic.geometry.type == 'point'){
			var tempGeom = editToolbar._graphic.geometry;
			return [[tempGeom.x, tempGeom.y]];
		}
		var originalCoords = editToolbar._graphic.geometry.type == 'polyline' ? editToolbar._graphic.geometry.paths : editToolbar._graphic.geometry.rings;
		var singleArrayCoords = [];
		for(var i=0;i<originalCoords.length;i++){
			var featureCoords = originalCoords[i];
			for(var j=0; j < featureCoords.length; j++){
				singleArrayCoords.push(featureCoords[j]);
			}
		}
		return singleArrayCoords;
	}

	$('.verticeChange').removeClass('disabledButton');
	if(editToolbar._graphic && editToolbar._graphic.geometry.type != 'point'){  // show snapping node
		var selectedNode = getSnapVertex();
		selectedNodeGraphic = new Graphic(new Point(selectedNode[0],selectedNode[1],map.spatialReference), verticeSnapMarker); // create the graphic to show the selected vertice
		verticeSnapLayer.add(selectedNodeGraphic);
	}


	changeVerticeHandler = $('.verticeChange').on("click", function(){ // handler to switch the selected vertices
		if(editToolbar._graphic && editToolbar._graphic.geometry.type != 'point'){ // check if there is a graphic in the toolbar and it is not a point feature (points just use the esri auto snap)

			verticeSnapLayer.clear(); // reset the vertice marker layer
			if($(this).hasClass('nextVertice')){
				verticeIndex += 1; // increase vertice, start at 0 if the value has exceeded the length of the feature
				if(verticeIndex > maxIndex -1){
					verticeIndex = 0;
				}
			}
			if($(this).hasClass('lastVertice')){
				verticeIndex += - 1; // decrease vertice, start at the end of the array if the value has gone below 0
				if(verticeIndex < 0){
					verticeIndex = maxIndex -1;
				}
			}

			var selectedNode = getSnapVertex(); // get the final selected node
			selectedNodeGraphic = new Graphic(new Point(selectedNode[0],selectedNode[1],map.spatialReference), verticeSnapMarker);
			verticeSnapLayer.add(selectedNodeGraphic);

		}
	});

	function displaySnapPoint(point, symbol){
		if(!symbol){
			symbol = parperpSnappingCross;
		}
		var snapScrPt = map.toScreen(point);
		/*var transDistanceX = snapScrPt.x - screenPoint.x; // calculate distance between the feature if it were snapped and where it currently is
		var transDistanceY = snapScrPt.y - screenPoint.y;*/
		var snapCross = new Graphic(new Point(point.x,point.y,map.spatialReference), symbol); // add standard snapping symbol
		parperpTempLayer.add(snapCross);
	}

	editSnappingHandler = editToolbar.on('graphic-move',function(evt){
		//if(editToolbar._graphic.geometry.type != 'point'){
			snapped = false;
			verticeSnapLayer.clear();
			var dontSnapCoords = getSingleArrayCoords();
			var selectedNode = getSnapVertex();
			var mapPt = new Point(selectedNode,map.spatialReference); // create esri map point and snap point from snap vertex
			var editSnappingScreenPt = map.toScreen(mapPt);
			if(evt.transform){ // move the snap point with the graphic being moved by using svg matrix transformations
				editSnappingScreenPt.x = editSnappingScreenPt.x + evt.transform.dx;
				editSnappingScreenPt.y = editSnappingScreenPt.y + evt.transform.dy;
			}

			parperpTempLayer.clear();

			var point;
			// see if snappingUtility has anything for us
			if(typeof(snappingUtility) != 'undefined'){
				if(point = snappingUtility.getSnappingPoint(editSnappingScreenPt)){
					snapped = true;
					snapToNode = point.point;
					displaySnapPoint(point.point, point.symbol);
					return;
				}
			}
			// no snapping utility snapping within tolerance try built in esri snapping
			var snapPromise = snapManager.getSnappingPoint(editSnappingScreenPt);
			snapPromise.then(function(point){ // get the snap point based off of the variable editSnappingScreenPt
				if(point !== undefined){
					snapToNode = point;
					var onFeature = false;
					if(editToolbar._graphic.geometry.type != 'point'){
						for(var i=0;i<dontSnapCoords.length-1;i++){
							var distance2toolbarGraphic = findDistance(point.x,point.y, dontSnapCoords[i][0],dontSnapCoords[i][1],dontSnapCoords[i+1][0],dontSnapCoords[i+1][1]); // calulate distance from snap point to segment to determine if you are on the old feature
							if(distance2toolbarGraphic<.5){
								onFeature = true;
							}
						}
					} else {
						if(point.x > dontSnapCoords[0][0] - 10  && point.x < dontSnapCoords[0][0] + 10 && point.y > dontSnapCoords[0][1] - 10 && point.y < dontSnapCoords[0][1] + 10)
							onFeature = true;
					}
					if(!(onFeature)){
						snapped = true;
						if(!isMobile){ // skip the mouse move svg transform stuff, not necessary on mobile.
							displaySnapPoint(point);
						}
					}
				}
			},
			function(error){
				console.log('Tell connor that his snapping stuff failed');
			});
		//}
	});

	ensureSolidSnap = editToolbar.on('graphic-move-stop',function(evt){ // tidies things up at the end, the transformations are not always accurate and th user can easily move the graphic in a way that the snapping will be off
		verticeSnapLayer.clear();
		if(snapped == false)
			return;

		snapped = false;
		var snappedGeom;
		var originalCoords;
		if(evt.graphic.geometry.type == 'polygon'){
			originalCoords = editToolbar._graphic.geometry.rings;
			snappedGeom = new Polygon(map.spatialReference);
			snappedGeom.pushTo = function(array){
				this.addRing(array); // create new geometry out of new coordinates
			}
		}
		if(evt.graphic.geometry.type == 'polyline'){
			originalCoords = editToolbar._graphic.geometry.paths;
			snappedGeom = new Polyline(map.spatialReference);
			snappedGeom.pushTo = function(array){
				this.addPath(array);
			}
		}
		if(evt.graphic.geometry.type == 'point'){
			var tempGeom = evt.graphic.geometry;
			originalCoords = [[[tempGeom.x,tempGeom.y]]];
			snappedGeom = new Point(tempGeom.x,tempGeom.y,  map.spatialReference);
			snappedGeom.pushTo = function(array){
				this.update(array[0][0],array[0][1]);
			}
		}
		

		var snapVertex = getSnapVertex(); // this point will be replaced by the specified snap point itself, this is the snapping vertex in the feature being moved
		for(var i=0; i<originalCoords.length;i++){
			var featureCoords = originalCoords[i];
			var snappedFeatureCoords = [];
			for(var j=0; j<featureCoords.length; j++){
				var relativeVertex = featureCoords[j];
				
				var statePlaneSnap = projectGeometry(new Point(snapVertex[0], snapVertex[1], map.spatialReference), statePlaneSR);
				var statePlaneRelative = projectGeometry(new Point(relativeVertex[0], relativeVertex[1], map.spatialReference), statePlaneSR);
				var statePlaneSnapTo = projectGeometry(new Point(snapToNode.x, snapToNode.y, map.spatialReference), statePlaneSR);

				var distanceX = statePlaneSnap.x - statePlaneRelative.x; // distance values between snapping vertex and other coordinates on said feature (this is a relative value so we can rebuild the feature based on the snap point instead of the snapping vertex)
				var distanceY = statePlaneSnap.y - statePlaneRelative.y;
				var newX = statePlaneSnapTo.x - distanceX; // caculate new coordinate based on the snap point posiitoned in the same relative way to the snapping vertex
				var newY = statePlaneSnapTo.y - distanceY;
				var webMercShift = projectGeometry(new Point(newX, newY, statePlaneSR), map.spatialReference);
				snappedFeatureCoords.push([webMercShift.x,webMercShift.y]);
			}
			snappedGeom.pushTo(snappedFeatureCoords);
		}

		var callback = function(){
			evt.graphic.setGeometry(snappedGeom); // reset geometry for feature in question
			parperpTempLayer.clear();
			editToolbar.refresh(); // this is used to update the edit toolbar's box editor as it would sometimes be shifted based on the timing between the new geometry being set and when the editor was done moving the graphic	
		}
		if(currentMap === 'dowbrine' && evt.graphic.geometry.type === 'point' && $('#setLocation').hasClass('enabled')){ // find out if we call pull z information from geolocationLayer
			// find out if what you snapped to is the geoLocationLayer - the tricky part
			var gpsPoint = $.grep(geoLocationLayer.graphics, function(g) {
				return g.geometry.type === 'point'; // there should only be one and it should be the gps point as long as we are not misusing the geoLocationLayer
			})[0];
			if(gpsPoint){
				var trans = projectGeometry(gpsPoint.geometry, map.spatialReference);
				// compare goometries to see if the feature is the same - most straight forward way to do this
				if(Math.abs(trans.x - snappedGeom.x) < 1 && Math.abs(trans.y - snappedGeom.y) < 1){ // they snapped to the gps point
					orthoHeightCorrection(currentPosition, function(res){
						var altitude = res ? Math.round(res * 3.28084 *100)/100 : null;
						feature2edit.attributes['POINT_Z'] = altitude;
						callback();
					});
				}
			} else {
				callback();
			}
		} else {
			callback();
		}

	});

}

disableEditSnapping = function(){ // function to be called for disabling any edit snapping
	if($('.enbEditSnapping').hasClass('fa-check-square-o')){
		$('.enbEditSnapping').removeClass('fa-check-square-o').addClass('fa-square-o'); // disable auto snap
		snapManager = map.enableSnapping({
			layerInfos: snappingLayerInfos(), alwaysSnap: false, snapKey: has("mac") ? keys.META : keys.CTRL, snapPointSymbol: parperpSnappingCross
		});
		$('.verticeChange').addClass('disabledButton');
		verticeSnapLayer.clear(); // possible for snapping vertices to still be visible at this stage hence this removal

		if(changeVerticeHandler != undefined){ // remove the edit snapping handlers
			changeVerticeHandler.off();
			changeVerticeHandler = undefined;
		}
		if(startMouseCorrectionHandler != undefined){ // remove the edit snapping handlers
			startMouseCorrectionHandler.remove();
			startMouseCorrectionHandler = undefined;
		}
		if(editSnappingHandler != undefined){
			editSnappingHandler.remove();
			editSnappingHandler = undefined;
		}
		if(ensureSolidSnap != undefined){
			ensureSolidSnap.remove();
			ensureSolidSnap = undefined;
		}
	}
}

function parperpDisableAutoSnap(){ // for disabling auto snap while parperp snapping takes its place. auto snap is disabled without changing the check box status or turning off the boolean snappingActive
	if(snappingActive){
		switchSnapping(false); // deactivate and reactive the toolbar for snapping without setting auto snap
	}
}

cancelDrawingWithSnap = function(){ // auto snap disable for when exiting drawing process altogther, does not redraw just sets snapping back to default and boolean to false
	if(snappingActive){
		$('.enbSnapping').removeClass('fa-check-square-o').addClass('fa-square-o');
		snapManager = map.enableSnapping({ // set snapping back to normal if need be
			layerInfos: snappingLayerInfos(), alwaysSnap: false, snapKey: has("mac") ? keys.META : keys.CTRL, snapPointSymbol: parperpSnappingCross
		});
		snappingActive = false;
	}
}

disableAutoSnap =  function(){ // standard, sets snapping back to normal and sets boolean to false and redraws
	if(snappingActive){
		$('.enbSnapping').removeClass('fa-check-square-o').addClass('fa-square-o');
		switchSnapping(false);
		snappingActive = false;
	}
}

function collectSnappingCandidates(mercX,mercY,deg){ // takes coordinates for parallel/perpendicualar line starting point the angle at which the line is to be drawn and extrapolates all possible snapping points for the given line within the current extent.
	var statePlaneStart = projectGeometry(new Point(mercX,mercY, map.spatialReference), statePlaneSR);
	//var statePlaneStart = Proj4js.transform(webDef, stateDef, new Proj4js.Point(mercX,mercY)); // convert coordaintes to stateplane coordinates
	var m = Math.tan(deg*Math.PI/180); // calculate slope of line based off of angle argument
	var b = statePlaneStart.y - (m * statePlaneStart.x);// determine y-intercept
	var snapArr=[]; // intialize an array to hold all snapping candidates
	var featuresArr = userLinesLayer.graphics; // use the user lines layer (in which all relevant layers will be represented) for collecting candidates
	var tX = statePlaneStart.x + 1; // create and imaginary point on the theoretical line based on slope, and input coordinates (I need a line not a point to find out where this line will intersect with the lines in the user lines layer)
	var tY = m*tX+b; // calculate theoretical y based on theoretical x
	var x3 = statePlaneStart.x;
	var y3 = statePlaneStart.y;
	var x4 = tX;
	var y4 = tY;
	featuresArr.forEach(function(i){

		var segmentArr = i.geometry.paths[0]; // get nodes and coordinate points from the graphic in the userLinesLayer


		for(j=0;j<segmentArr.length-1;j++){ // for each segment in the graphic find out if and where the theoretical segment and the graphic's segment will intersect

			var firstNode = segmentArr[j];
			var secondNode = segmentArr[j+1];
			var statePlaneTrans1 = projectGeometry(new Point(firstNode[0], firstNode[1], map.spatialReference), statePlaneSR);
			var statePlaneTrans2 = projectGeometry(new Point(secondNode[0], secondNode[1], map.spatialReference), statePlaneSR);
			/*
			var statePlaneTrans1 = Proj4js.transform(webDef, stateDef, new Proj4js.Point(firstNode[0], firstNode[1])); // convert all coordinates in the segments to stateplane
			var statePlaneTrans2 = Proj4js.transform(webDef, stateDef, new Proj4js.Point(secondNode[0], secondNode[1]));
			*/
			var x1 = statePlaneTrans1.x;
			var y1 = statePlaneTrans1.y;
			var x2 = statePlaneTrans2.x;
			var y2 = statePlaneTrans2.y;
			var a1 = y2 - y1; // the next few lines are just a simple mathematical check to determine if the slopes of the two lines will cause an intersection
			var b1 = x1 - x2;
			var c1 = a1 * x1 + b1 * y1
			var check1 = (x4-x3)*(y1-y4)-(y4-y3)*(x1-x4);
			var check2 = (x4-x3)*(y2-y4)-(y4-y3)*(x2-x4);
			if(Math.min(check1,check2) < 0 && Math.max(check1,check2) > 0){ // if one of the above checks evaluates to negative while the other evalutes to positive then there is an intersection
				var a2 = y4 - y3;
				var b2 = x3 - x4;
				var c2 = a2 * x3 + b2 * y3;
				var det = a1 * b2 - a2 * b1;
				if(det != 0){
					var intX = (b2*c1 - b1*c2)/det;
					var intY = (a1*c2 - a2*c1)/det;
					var webMercTrans = projectGeometry(new Point(intX, intY, statePlaneSR), map.spatialReference);
					//var webMercTrans = Proj4js.transform(stateDef, webDef, new Proj4js.Point(intX, intY));
					snapArr.push([webMercTrans.x,webMercTrans.y]);
				}
			}

		}
	});
	return snapArr;
}

var parUserMoveHandler;
$('.parperp').on("click", function(e){ // start parallel or perpendicular drawing session
	$('#squareAndFinish').addClass('disabledButton');
	$('#ninetyDegTool').addClass('disabledButton');
	parperpTempLayer.clear();
	plineTempLine = undefined;
	layerArray = [userGraphicsLayer];// these need to be ordered in their draw order, layers near the end of the array will draw on top of the previous layers.

	try{
		if(templateLayers != undefined){
			for(var i = 0; i < templateLayers.length; i++){
				layerArray.push(templateLayers[i]);
			}
		}
	} catch(err){
		console.log('no template layers1: ' + err.message);
	}

	for(layer in parcelLayers){
		layerArray.push(window[layer]);
	}

	var doNotCopy = false;
	var mapX;
	var mapY;
	var snapPointArr=[];
	var parperpDeg;
	var slope;
	var intercept;
	var startParClkHandler = false;
	var tempId = '#' + $(this).attr('id'); // get id of button pressed
	if($(this).hasClass('parallel')){
		var isPerpendicular = false;
	} else {
		var isPerpendicular = true;
	}
	if($(this).hasClass('btn-primary')){ // if not in session already disable other parperp class button
		parperpDisableAutoSnap();
		parperpHandlerActive = true;
		copyUserLines(layerArray);
		userLinesLayer.show();
		$('.parperp').addClass('disabledButton');
		$(this).removeClass('disabledButton btn-primary').addClass('btn-danger').html('<span class="cancelParperp">&#10799;</span>');
		toolbar.lineSymbol.width = 0; // make the line that is currently being drawn invisible
		toolbar.fillSymbol.outline.width = 0;
		$('#lengthOutput').addClass('hidden'); // hide the length output thing as it wont be needed during segment selection
		var numNodes = toolbar._points.length; // find out the current number of nodes contained in the toolbar, this is used to determine if a newly createdone needs to be deleted

		if(!(isMobile)){
			parHoverOverHandler = userLinesLayer.on('mouse-over', function(evt) { // similar to select button, highlight the graphic layer
				parperpHighlightLayer.clear();
				var extraWidth = parseInt($(evt.target).attr('stroke-width')) + 4;
				highlightSegmentCoords = findSegmentAngle(evt,true,false);
				if(typeof(highlightSegmentCoords)!= 'undefined'){
					var highlightSegment = new Polyline(map.spatialReference);
					highlightSegment.addPath([[highlightSegmentCoords[0],highlightSegmentCoords[1]],[highlightSegmentCoords[2],highlightSegmentCoords[3]]]);
					highlightLine = new Graphic(highlightSegment,new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID, new Color([255,0,0]), extraWidth));
					parperpHighlightLayer.add(highlightLine);
				}
			});

			parHoverOffHandler = userLinesLayer.on('mouse-out', function(evt) { // same as above just for exiting
				parperpHighlightLayer.clear();
			});
		}

		parExtentChangeHandler = map.on('extent-change', function(e){
			try{
				clearTimeout(copyTimeout);
				clearInterval(copyInterval);
			} catch(err){}
			toolbar.lineSymbol.width = 2; // make line visible again for redraw
			toolbar.fillSymbol.outline.width = 2;
			toolbar._graphic.draw(); // draw
			toolbar.lineSymbol.width = 0; // after drawing finished make temporary line invisible again.
			toolbar.fillSymbol.outline.width = 0;

			copyTimeout = setTimeout(function(){ // just in case somehow some way the layers do not load
				//console.log('time...it ran out');
				clearInterval(copyInterval);
			},5000);
			//var timeout = 0
			copyInterval = setInterval(function(){ // check if layers are ready to be compied based on extent change
				if(doNotCopy){
					clearTimeout(copyTimeout);
					clearInterval(copyInterval);
				} else {
					var readyCounter = 0;
					layerArray.forEach(function(i){
						if(typeof(i.updating) == "undefined"){ // handling user drawn or created graphics which should not have to update
							readyCounter +=1;
						} else if(i.updating == false){ // if the layer is a feature service it will have this attribute
							readyCounter +=1;
						}
					});
					if(readyCounter == layerArray.length && !(doNotCopy)){
						copyUserLines(layerArray); // update user lines layer due to limiting by extent
						snapPointArr = collectSnappingCandidates(map_x,map_y,parperpDeg);
						clearTimeout(copyTimeout);
						clearInterval(copyInterval);
					}
				}
				//timeout +=1;
			},15);



		});

		parClickHandler = map.on("click", function(evt){ // since esri's drawing handler cannot be ignored without disabling the entire toolbar this deletes the esri drawing node created by clicking on the segment desired.
			if(toolbar._geometryType == "polygon"){
				setUncloseDrawPolygonHandler(true);
			}
			var cleared = false
			doNotCopy = true;
			var clearUnwanted = setInterval(function(){
				if(toolbar._points.length > numNodes){ // make sure a node has actually been added
					toolbar._points.pop(); // remove unwanted nodes
					if(toolbar._geometryType == 'polyline'){
						toolbar._graphic.geometry.paths[0].pop();
						toolbar.lineSymbol.width = 2; // make line visible again for redraw
						toolbar._graphic.draw();
						toolbar.lineSymbol.width = 0;
					} else {
						toolbar._graphic.geometry.rings[0].pop();
						toolbar.fillSymbol.outline.width = 2;
						toolbar._graphic.draw();
						toolbar.fillSymbol.outline.width = 0;
					}
					doNotCopy = false;
					cleared = true;
					clearInterval(clearUnwanted);
				}
			}, 5);

			if(startParClkHandler){
				if(typeof(parUserMoveHandler) != 'undefined'){
					parUserMoveHandler.off();
				}
				var checkFinish = setInterval(function(){
					if(cleared){
						parperpTempLayer.clear();
						$(tempId).trigger('click');
						if(isMobile){ // need to calculate extrapolated parallel or perpendiuclar coordinate point
							var screenPt = new ScreenPoint(evt.clientX,evt.clientY- $('#navbar').height()+10); // minus 55 makes ammends for the height of the navbar
							var mapPt = map.toMap(screenPt);
							var statePlaneTrans = projectGeometry(new Point(map_x, map_y, map.spatialReference), statePlaneSR);
							var statePlaneUserTrans = projectGeometry(new Point(mapPt.x, mapPt.y, map.spatialReference), statePlaneSR);
							/*
							var statePlaneTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(map_x, map_y));
							var statePlaneUserTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(mapPt.x, mapPt.y));
							*/
							var spX = statePlaneTrans.x;
							var userX = statePlaneUserTrans.x;
							var userY = statePlaneUserTrans.y;
							if(parperpDeg == 90){
								tempY = userY;
								tempX = spX;
							} else if(parperpDeg < 135 && parperpDeg > 45){
								tempY = userY;
								tempX = (userY-intercept)/slope;
							} else {
								tempX = userX;
								tempY = (slope*userX) + intercept;
							}
							var webMercUserTrans = projectGeometry(new Point(tempX, tempY, statePlaneSR), map.spatialReference);
							//var webMercUserTrans = Proj4js.transform(stateDef, webDef, new Proj4js.Point(tempX, tempY));
							mapX = webMercUserTrans.x;
							mapY = webMercUserTrans.y;
							parperpTempLayer.clear();
							if(snappingActive){
								var foundSnapPoint = false
								var tolerance = snapManager.tolerance * (Math.abs(map.extent.xmin - map.extent.xmax)/map.width);
								snapPointArr.forEach(function(i){
									var dist = Math.sqrt(Math.pow((i[0]-mapX),2) + Math.pow((i[1]-mapY),2));
									if(dist<tolerance){
										tolerance = dist;
										mapX = i[0];
										mapY = i[1];
									}
								});
							}
						} // mapX and mapY will already be set by the mouse move handler if not on mobile
						var parMapPt =  new Point([mapX,mapY]);
						var parScreenPt = map.toScreen(parMapPt);
						if(toolbar._geometryType == "polygon"){
							setUncloseDrawPolygonHandler(true);
						}
						map.emit("click", { bubbles: true, cancelable: true, mapPoint: parMapPt, screenPoint: parScreenPt});
						plineTempLine = undefined;
						clearInterval(checkFinish);
					}
				},10);
			}
		});

		parUserGraphHandler = userLinesLayer.on("click", function(evt){ // if a graphics layer is clicked on

			parperpHighlightLayer.clear();
			var statePlaneTrans = projectGeometry(new Point(map_x, map_y, map.spatialReference), statePlaneSR);
			//var statePlaneTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(map_x, map_y));
			var spX = statePlaneTrans.x;
			var spY = statePlaneTrans.y;
			parperpDeg = findSegmentAngle(evt,false,isPerpendicular);
			if(parHoverOverHandler != undefined){ // remove all necessary handlers
				parHoverOverHandler.remove();
				parHoverOverHandler = undefined;
			}
			if(parHoverOffHandler != undefined){
				parHoverOffHandler.remove();
				parHoverOffHandler = undefined;
			}
			slope = Math.tan(parperpDeg*Math.PI/180);
			intercept = spY - (slope * spX);
			snapPointArr = collectSnappingCandidates(map_x,map_y,parperpDeg);
			userLinesLayer.hide();
			var parCounter = 1;

			if(!(isMobile)){ // if not on mobile device create the mouse move handler
				parUserMoveHandler = $('#map').mousemove(function(e){
					if(parCounter == 1){
						var screenPt = new ScreenPoint(e.clientX,e.clientY- $('#navbar').height()+10); // minus 55 makes ammends for the height of the navbar
						var mapPt = map.toMap(screenPt);
						var statePlaneTrans = projectGeometry(new Point(map_x, map_y, map.spatialReference), statePlaneSR);
						var statePlaneUserTrans = projectGeometry(new Point(mapPt.x, mapPt.y, map.spatialReference), statePlaneSR);
						/*
						var statePlaneTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(map_x, map_y));
						var statePlaneUserTrans = Proj4js.transform(webDef, stateDef, new Proj4js.Point(mapPt.x, mapPt.y));
						*/
						var spX = statePlaneTrans.x;
						var userX = statePlaneUserTrans.x;
						var userY = statePlaneUserTrans.y;
						if(parperpDeg == 90){
							tempY = userY;
							tempX = spX;
						} else if(parperpDeg < 135 && parperpDeg > 45){
							tempY = userY;
							tempX = (userY-intercept)/slope;
						} else {
							tempX = userX;
							tempY = (slope*userX) + intercept;
						}
						var webMercUserTrans = projectGeometry(new Point(tempX, tempY, statePlaneSR), map.spatialReference);
						//var webMercUserTrans = Proj4js.transform(stateDef, webDef, new Proj4js.Point(tempX, tempY));
						mapX = webMercUserTrans.x;
						mapY = webMercUserTrans.y;
						parperpTempLayer.clear();
						if(snappingActive){
							var foundSnapPoint = false
							var tolerance = snapManager.tolerance * (Math.abs(map.extent.xmin - map.extent.xmax)/map.width);
							snapPointArr.forEach(function(i){
								var dist = Math.sqrt(Math.pow((i[0]-mapX),2) + Math.pow((i[1]-mapY),2));
								if(dist<tolerance){
									tolerance = dist;
									mapX = i[0];
									mapY = i[1];
									foundSnapPoint = true;
								}
							});
							if(foundSnapPoint){
								snapCross = new Graphic(new Point(mapX,mapY,map.spatialReference), parperpSnappingCross);
								parperpTempLayer.add(snapCross);
							}
						}
						var tempSegment = new Polyline(map.spatialReference);
						tempSegment.addPath([[map_x,map_y],[mapX,mapY]]);
						plineTempLine = new Graphic(tempSegment,plineTempLineSymbol);

						parperpTempLayer.add(plineTempLine);

						parCounter +=1
					} else {
						if(parCounter == 8){ // increase to increase the interval at which the templine is drawn
							startParClkHandler = true;
							parCounter = 1;
						} else {
							parCounter += 1;
						}
					}
				});
			} else { // mobile device will not use mouse move handler.
				setTimeout(function(){ // need delay otherwise the click on the graphics layer may trigger the addition of the parallel/perpendicular line
					startParClkHandler = true;
				},200);
			}
		});
	} else{
		cancelParperp();
	}
});
cancelParperp = function(){ // global function that allows canceling parallel/perpendicular stuff in the canceldrawingselection function.
	parperpTempLayer.clear();
	if(parperpHandlerActive){

		if(typeof(parUserMoveHandler) != 'undefined'){ // handled differently because will not always be instated
			parUserMoveHandler.off();
		}
		if(parHoverOverHandler != undefined){ // remove all necessary handlers
			parHoverOverHandler.remove();
			parHoverOverHandler = undefined;
		}
		if(parHoverOffHandler != undefined){
			parHoverOffHandler.remove();
			parHoverOffHandler = undefined;
		}
		if(parExtentChangeHandler != undefined){
			parExtentChangeHandler.remove();
			parExtentChangeHandler = undefined;
		}
		if(parUserGraphHandler != undefined){
			parUserGraphHandler.remove();
			parUserGraphHandler = undefined;
		}
		if(parClickHandler != undefined){
			parClickHandler.remove();
			parClickHandler = undefined;
		}
		parperpHandlerActive = false; // must be updated before re-enabling snapping since the snapping function checks this variable
		userLinesLayer.clear(); // refresh user lines layer
		parperpTempLayer.clear(); // remove any temp snapping and parperp stuff if showing
		parperpHighlightLayer.clear();
		toolbar.lineSymbol.width = 2; // make sure toolbar is set back to normal
		toolbar.fillSymbol.outline.width = 2;

		$('#ninetyDegTool').removeClass('disabledButton');
		if(toolbar._points.length == 3){
			$('#squareAndFinish').removeClass('disabledButton');
		}
		if($('.perpendicular').hasClass('btn-danger')){ // set buttons back to normal
			$('.perpendicular').removeClass('btn-danger').addClass('btn-primary').html('<span class="perpendicularIcon">&#9516;</span>');
		}
		if($('.parallel').hasClass('btn-danger')){
			$('.parallel').removeClass('btn-danger').addClass('btn-primary').html('<span class="parallelIcon">&#9553;</span>');
		}

		if(snappingActive){ // at endo of function because parperphandelr boolean must be false in order to re-enable
			enbSnapping(); // re-enable snapping
		}
		$('.parperp').removeClass('disabledButton');
	}
}
// end of parperp and snapping section

// this is for feature editing only... does not effect userGraphicsLayer edits.
function changeEditMode(){
	var tool = 0;
	var fakeTheRotate = false;

	var popupFeature = popup.features[popup.selectedIndex];
	
	var snapping_enabled = $("#editSnapping").hasClass("fa-check-square-o");
	
	editToolbar.deactivate();
	if ($('#toggleFeatureMove').hasClass('fa-check-square-o')) {
		tool = tool | Edit.MOVE;
	}

	if(popupFeature.geometry.type != "point"){ // ignore these for point features

		if ($('#toggleFeatureVerticie').hasClass('fa-dot-circle-o') && $('#toggleFeatureModify').hasClass('fa-check-square-o')) {
			tool = tool | Edit.EDIT_VERTICES;
		}
		if ($('#toggleFeatureStretch').hasClass('fa-dot-circle-o') && $('#toggleFeatureModify').hasClass('fa-check-square-o')) {
			tool = tool | Edit.SCALE;
		}
		if ($('#toggleFeatureVertexRotate').hasClass('fa-check-square-o')) {
			fakeTheRotate = true;
		}

	}

	if(tool != 0){
		editToolbar.activate(tool, popupFeature);
		if(snapping_enabled){
			$("#editSnapping").trigger('click'); // re-enable snapping
		}
	} else if (fakeTheRotate === true){ // The toolbar must be on for the custom rotation stuff to actually work. So i fake the toolbar out by setting it to ROTATE and then hiding the handles so the actual func cant be used.
		editToolbar.activate(Edit.ROTATE, popup.features[popup.selectedIndex],
			{
				boxHandleSymbol : new SimpleMarkerSymbol(SimpleMarkerSymbol.STYLE_SQUARE, 0.1,
					new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID,
					new Color([255,0,0, 0]), 1),
					new Color([0,255,0,0])),
				boxLineSymbol : new SimpleLineSymbol(
					SimpleLineSymbol.STYLE_DASH,
					new Color([0,0,0, 0]),
					3
				  ),
			}
		);
		if(snapping_enabled){
			$("#editSnapping").trigger('click'); // re-enable snapping
		}
	}
}

// function used to select and edit symbols
function setSelectedSVG(path, size, xoffset, yoffset) {
	var iconSelect = new SimpleMarkerSymbol();
	iconSelect.setPath(path);
	iconSelect.setColor([255,0,0,.25]);
	var outline = new SimpleLineSymbol(SimpleLineSymbol.STYLE_SHORTDASH,
		new Color([255,0,0,1]), 2);
	iconSelect.setOutline(outline);
	iconSelect.setSize(size);
	if(typeof(xoffset) !== "undefined"){
		iconSelect.setOffset(xoffset, yoffset);
	}
	return iconSelect;
}

var firstTextObj = false;
window.editGraphicSymbolJSON;
window.editGraphicElement;
var editedFont;
userGraphicsOnSelect = function(evt){

	// Check if the user already selected the graphic so we don't fill an array with duplicates
	var alreadyExists = false;
	for(var i = 0; i < selectedGeometry.length; i++){
		if(selectedGeometry[i].attributes == evt.graphic.attributes){
			alreadyExists = true;
		}
	}
	// if exists, find selected feature that matches users clicked feature
	// find symbol in graphicStore object that matches users click
	// replace selected feature with symbol style from graphicStore
	if (alreadyExists){
		for (var i = 0; i < selectedGeometry.length; i++){
			if (selectedGeometry[i].attributes == evt.graphic.attributes){
				for (var j = 0; j < graphicStore.length; j++){
					//if (selectedGeometry[i].attributes == graphicStore[j].ID){
					if (evt.graphic.attributes == graphicStore[j].ID && $('.selectButton').hasClass('btn-success') === false){
						selectedGeometry[i].setSymbol(graphicStore[j].Symbol);
						// code to change line width back to original from hoverOnHandler
						if (selectedGeometry[i].geometry.type == 'point' && selectedGeometry[i].symbol.text != undefined){
							var newSize = parseInt($(evt.target).attr('font-size')) + 2;
							$(evt.target).attr('font-size', newSize);
						} else {
							var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
							$(evt.target).attr('stroke-width', newWidth);
						}
						selectedGeometry.splice(i, 1); // remove feature from selected array
					}
				}
			}
		}

	} else if(alreadyExists == false) {


		// add graphic to array of selected geometries and highlight it by changing it's symbol
		selectedGeometry[selectedGeometry.length] = evt.graphic;
		if(selectedGeometry.length == 1){ // only one graphic selected, so show the edit options.
			//specify toolbar options
			var options = {
				allowAddVertices: registry.byId("vtx_ca").checked,
				allowDeleteVertices: registry.byId("vtx_cd").checked,
				uniformScaling: registry.byId("uniform_scaling").checked

			};
			if(evt.graphic.symbol.type === 'textsymbol'){

				//setTextEditHandlers();

				// store the first symbol
				firstTextObj = evt;

				textObj2edit = evt.graphic;
				console.log('Activate text edit tool');

				editToolbar.activate(Edit.MOVE, evt.graphic, options); // will need my own move handler too? the widget is overwriting my stuff
				$('#textEditorContainer').fadeIn();



				editedFont = new Font();

				// get the current values
				var etFamily = evt.graphic.symbol.font.family; // Verdana, Arial, etc
				var etSize = evt.graphic.symbol.font.size; // 15 is default
				var etColor = evt.graphic.symbol.color; // an object of {r, g, b, a}
				var etStyle = evt.graphic.symbol.font.style; // italic or not
				var etWeight = evt.graphic.symbol.font.weight; // bold or not
				var etDecoration;
				if(evt.graphic.symbol.decoration != undefined){
					etDecoration = evt.graphic.symbol.decoration; // underline or not
				}
				var etText = evt.graphic.symbol.text; // content
				var etAngle = evt.graphic.symbol.angle * -1; // Text Angles are drawn opposite of lines.

				// initialize the options in the symbol editor to match the symbol
				if(typeof(etColor.a) === "undefined"){
					etColor.a = 1;
				}
				$('#editTextColor').spectrum('set', "rgba(" + etColor.r + ", " + etColor.g + ", " + etColor.b + ", " + etColor.a + "");

				$('#editTextSize').val(etSize);
				if(etWeight === 'bold'){
					$('#editTextBold').addClass('active');
				} else {
					$('#editTextBold').removeClass('active');
				}

				if(etStyle === 'italic'){
					$('#editTextItalic').addClass('active');
				} else {
					$('#editTextItalic').removeClass('active');
				}

				if(etDecoration === 'underline'){
					$('#editTextUnderline').addClass('active');
				} else {
					$('#editTextUnderline').removeClass('active');
				}

				switch(etFamily){
					case 'Arial':
						$('#editTextFontFamily').val('Arial');
						break;
					case 'Georgia':
						$('#editTextFontFamily').val('Georgia');
						break;
					case 'TimesNewRoman':
						$('#editTextFontFamily').val('TimesNewRoman');
						break;
					case 'Verdana':
						$('#editTextFontFamily').val('Verdana');
						break;
				}
				$('#editTextArea').val(etText);
				$('#editTextAngle').val(etAngle);
				//$('#angleIdentifier').attr('transform', 'rotate(' + (etAngle * -1) + ', 25, 25)');
				$('#editTextRotationDiv').css('transform','rotate('+ (etAngle * -1) +'deg)'); // webkit friendly

			} else { // still only one graphic selected, so show editing options applicable to the chosen graphic

				// activate the toolbar
				editGraphic = evt.graphic; // the esri/dojo graphic
				editGraphicElement = evt.target; // the svg dom node
				editGraphicSymbolJSON = JSON.stringify(evt.graphic.symbol);
				window.egSymbol = JSON.parse(editGraphicSymbolJSON);

				//determine what kind of graphic, and show options that apply to editing it. Also, set color picker(s)
				var gType = evt.graphic.geometry.type;
				if(gType === "point"){
					console.log("do point stuff");

					// note: simpleMarkerSymbols have stroke and fill like polygons, but we just set both with one color picker
					$('#editPointColor').spectrum("set", "rgba(" + evt.graphic.symbol.outline.color.r + ", " + evt.graphic.symbol.outline.color.g + ", " + evt.graphic.symbol.outline.color.b + ", " + evt.graphic.symbol.outline.color.a + ")");
					$("#editPointSize").val(editGraphic.symbol.size);

					// forget about detecting which sybmol type is selected. There's no good way to identify them
					$('.editPointOptions').fadeIn();

				} else if(gType === "polyline"){
					console.log("do polyline stuff");
					console.log("rgba: (" + evt.graphic.symbol.color.r + ", " + evt.graphic.symbol.color.g + ", " + evt.graphic.symbol.color.b + ", " + evt.graphic.symbol.color.a + ")");
					// set color picker
					$('#editStrokeColor').spectrum("set", "rgba(" + evt.graphic.symbol.color.r + ", " + evt.graphic.symbol.color.g + ", " + evt.graphic.symbol.color.b + ", " + evt.graphic.symbol.color.a + ")");

					$('.editStrokeOptions').fadeIn();
				} else if(gType === "polygon"){
					console.log("do polygon stuff");
					$('#editFillColor').spectrum("set", "rgba(" + evt.graphic.symbol.color.r + ", " + evt.graphic.symbol.color.g + ", " + evt.graphic.symbol.color.b + ", " + evt.graphic.symbol.color.a + ")");
					$('#editStrokeColor').spectrum("set", "rgba(" + evt.graphic.symbol.outline.color.r + ", " + evt.graphic.symbol.outline.color.g + ", " + evt.graphic.symbol.outline.color.b + ", " + evt.graphic.symbol.outline.color.a + ")");
					$('.editStrokeOptions, .editFillOptions').fadeIn();
				} else {
					console.log("ok, what's going on here?");
				}

				//start editToolbar
				editToolbar.activate(15, evt.graphic, options);

				// handlers for changing "select" button to "apply" button
				editToolbar.on('rotate-first-move, graphic-first-move, scale-first-move, vertex-add, vertex-delete, vertex-first-move',function(evt){
					$('.selectButton').removeClass('btn-danger').addClass('btn-success').html('<span class="fa fa-mouse-pointer"></span> Apply');
				});
			}
			//$('.selectButton').html('<span class="fa fa-mouse-pointer"></span> Apply');


		} else {
			editToolbar.deactivate();
			$('.editToolOption').fadeOut();

			$('#textEditorContainer').fadeOut();
			if($('.selectButton').hasClass('btn-success')){
				$('.selectButton').removeClass('btn-success');
			}
			if($('.selectButton').hasClass('btn-primary')){
				$('.selectButton').removeClass('btn-primary');
			}
			$('.selectButton').addClass('btn-danger').html('<span class="fa fa-mouse-pointer"></span> Cancel');
		}

		if(editToolbar.active === false){ // we don't want this to alter a graphic that is in the middle of editing.
			if(selectedGeometry.length === 2){
				// while the edit toolbar disables tool selection, it's no longer active once a second geometry is selected (to delete)
				disableToolSelection();

				// hightlight the first text graphic once more graphics are selected for deletion
				if(firstTextObj != false){
					//firstTextObj
					var ftxt = firstTextObj.graphic.symbol.text;
					var fsize = firstTextObj.graphic.symbol.font.size;
					var ffontFam = firstTextObj.graphic.symbol.font.family;
					firstTextObj.graphic.setSymbol(setSelectedText(ftxt, fsize, ffontFam));
					var fnewSize = parseInt($(firstTextObj.target).attr('font-size')) + 2;
					$(firstTextObj.target).attr('font-size', fnewSize);
					// this is to make sure we only do this once
					firstTextObj = false;
				} else {
					if(selectedGeometry[0].geometry.type === "point"){
						var size = selectedGeometry[0].symbol.size;
						var style = selectedGeometry[0].symbol.style;
						if(style != 'path'){
							selectedGeometry[0].setSymbol(setSelectedPoint(size, style));
						} else {
							var path = selectedGeometry[0].symbol.path;
							var xoffset = selectedGeometry[0].symbol.xoffset;
							var yoffset = selectedGeometry[0].symbol.yoffset;
							selectedGeometry[0].setSymbol(setSelectedSVG(path,size,xoffset,yoffset));
						}
						var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
						$(evt.target).attr('stroke-width', newWidth);
						//selectedGeometry[0].setSymbol(selectedPointSymbol);
					} else if(selectedGeometry[0].geometry.type === "polyline"){
						selectedGeometry[0].setSymbol(selectedLineSymbol);
					} else if (selectedGeometry[0].geometry.type === "polygon"){
						selectedGeometry[0].setSymbol(selectedPolygonSymbol);
					}
				}
			}

			if(evt.graphic.geometry.type == "polyline"){ // lines are still of geometry type polyline
				evt.graphic.setSymbol(selectedLineSymbol);
				var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
				$(evt.target).attr('stroke-width', newWidth);
			} else if(evt.graphic.geometry.type == "polygon"){
				evt.graphic.setSymbol(selectedPolygonSymbol);
				var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
				$(evt.target).attr('stroke-width', newWidth);
			} else if (evt.graphic.geometry.type == "point" && evt.graphic.symbol.type == "textsymbol"){
				if(selectedGeometry.length > 1){
					var txt = evt.graphic.symbol.text;
					var size = evt.graphic.symbol.font.size;
					var fontFam = evt.graphic.symbol.font.family;
					evt.graphic.setSymbol(setSelectedText(txt, size, fontFam));
					var newSize = parseInt($(evt.target).attr('font-size')) + 2;
					$(evt.target).attr('font-size', newSize);
				}
			} else if (evt.graphic.geometry.type == "point"){
				x = evt.graphic;
				var size = evt.graphic.symbol.size;
				var style = evt.graphic.symbol.style;
				if(style != 'path'){
					evt.graphic.setSymbol(setSelectedPoint(size, style));

				} else {
					var path = evt.graphic.symbol.path;
					var xoffset = evt.graphic.symbol.xoffset;
					var yoffset = evt.graphic.symbol.yoffset;
					evt.graphic.setSymbol(setSelectedSVG(path,size,xoffset,yoffset));
				}
				var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
				$(evt.target).attr('stroke-width', newWidth);
			}
		}

	}
	if(selectedGeometry.length > 0){
		$('.deleteButton').css({pointerEvents: "", opacity: "1"});
	} else {
		$('.deleteButton').css({pointerEvents: "none", opacity: "0.5"});
	}

}
function applyTextEdit(){

	var constSize = $('#editTextSize').val();
	var constFamily = $('#editTextFontFamily').val();

	var constColor = $('#editTextColor').val();
	var constColor = constColor.replace("rgb(", "[").replace("rgba(", "[").replace(")", "]");
	var constColor = JSON.parse(constColor);

	var constText = $('#editTextArea').val();
	var constAngle = $('#editTextAngle').val() * -1;
	if($('#editTextBold').hasClass('active')){
		constWeight = 'bold';
	} else {
		constWeight = 'normal';
	}

	if($('#editTextItalic').hasClass('active')){
		constStyle = 'italic';
	} else {
		constStyle = 'normal';
	}

	if($('#editTextUnderline').hasClass('active')){
		constDecoration = 'underline';
	} else {
		constDecoration = 'normal';
	}

	editedFont.setSize(constSize);
	editedFont.setFamily(constFamily);
	editedFont.setStyle(constStyle);
	editedFont.setDecoration(constDecoration);
	editedFont.setWeight(constWeight);

	textObj2edit.symbol.setFont(editedFont);
	textObj2edit.symbol.setText(constText);
	textObj2edit.symbol.setColor(new Color(constColor));
	textObj2edit.symbol.setAngle(constAngle);

	userGraphicsLayer.refresh();
	updateURLgeometry();
	$('.selectButton').removeClass('btn-danger').addClass('btn-success').html('<span class="fa fa-mouse-pointer"></span> Apply');

};


// first, set the handers (IE/Edge drop down's don't respond to the 'input' event, so we have to use change)
$('#textEditorContainer input, #editTextArea').on("input", function(){
	applyTextEdit();
});

$('#editTextColor, #editTextFontFamily').on("change", function(){
	applyTextEdit();
});

$('.spThumbInner').on("mouse-up", function(){
	applyTextEdit();
});

$('.editFontOption').on("click", function (){
	if ($(this).hasClass('active')){
		$(this).removeClass('active');
	} else {
		$(this).addClass('active');
	}
	applyTextEdit();
});


$('.selectButton').on("click", function (e){
	popup.clearFeatures();
	popup.hide();

	// fisrtTextObj is a handle for the first text graphic selected

	if($(this).hasClass('btn-primary')){

				
		$('.selectButton').removeClass('btn-primary').addClass('btn-danger').html('<span class="fa fa-mouse-pointer"></span> Cancel');
		$('#map_layers').css('cursor', 'crosshair');
		$('.esriPopup, .dijitTooltipDialogPopup').css({'pointer-events': 'none', 'opacity': 0});
		highlightLayer.hide();
		$('#layerInfoHover').hide();
		//parcelLayer.setInfoTemplate();
		for(layer in parcelLayers){
			window[layer].setInfoTemplate(null);
		}

		// store all current graphics once select button is clicked
		for (var i = 0; i < userGraphicsLayer.graphics.length; i++){
		   storeGraphic(userGraphicsLayer.graphics[i]);
		}
		enableGraphicHover(true);

		var context = this;
		selectionHandler = userGraphicsLayer.on("click", function(evt){
			userGraphicsOnSelect.call(context,evt);
		});


	} else { // reset graphics' symbols and clear the selection array.
		// check if an edit is being applied
		if($('.selectButton').html() === '<span class="fa fa-mouse-pointer"></span> Apply'){
			// true means restore graphics symbols (from the red dashed selection symbol)
			cancelDrawingSelection();
		} else {
			cancelDrawingSelection(true);
		}
		$('#closeTextEditTool').trigger('click');

		}

	// save edit to url
	updateURLgeometry();
});

$('#closeTextEditTool').on("click", function(){
	cancelDrawingSelection();
});

enableGraphicHover = function enableGraphicHover(trueFalse){
	if(trueFalse == true){

		//function to handle graphics hover while select button is active on drawing tools
		hoverOnHandler = userGraphicsLayer.on('mouse-over', function(evt) {
			if(evt.graphic.geometry.type == "polyline"){
				var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
				$(evt.target).attr('stroke-width', newWidth);
			} else if(evt.graphic.geometry.type == "polygon" || evt.graphic.geometry.type == "extent"){
				var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
				$(evt.target).attr('stroke-width', newWidth);
			} else if (evt.graphic.geometry.type == "point" && evt.graphic.symbol.type == "textsymbol"){
				var newSize = parseInt($(evt.target).attr('font-size')) + 2;
				$(evt.target).attr('font-size', newSize);                    
			} else if(evt.graphic.geometry.type == "point"){
				var newSize = parseInt($(evt.target).attr('stroke-width')) + 2;
				$(evt.target).attr('stroke-width', newSize);
			}
		});
		hoverOffHandler = userGraphicsLayer.on('mouse-out', function(evt) {
			if(evt.graphic.geometry.type == "polyline"){
				var newWidth = parseInt($(evt.target).attr('stroke-width')) - 2;
				$(evt.target).attr('stroke-width', newWidth);
				evt.graphic.draw();
			} else if(evt.graphic.geometry.type == "polygon" || evt.graphic.geometry.type == "extent"){
				var newWidth = parseInt($(evt.target).attr('stroke-width')) - 2;
				$(evt.target).attr('stroke-width', newWidth);
				evt.graphic.draw();
			} else if (evt.graphic.geometry.type == "point" && evt.graphic.symbol.type == "textsymbol"){
				var newSize = parseInt($(evt.target).attr('font-size')) - 2;
				$(evt.target).attr('font-size', newSize);
			} else if(evt.graphic.geometry.type == "point"){
				var newSize = parseInt($(evt.target).attr('stroke-width')) - 2;
				$(evt.target).attr('stroke-width', newSize);
			}
		});
		// end of hover handler
	} else {
		if(hoverOnHandler != undefined){
			hoverOnHandler.remove();
			hoverOnHandler = undefined;
		}
		if(hoverOffHandler != undefined){
			hoverOffHandler.remove();
			hoverOffHandler = undefined;
		}
	}

};

$('#editPointColor, #editPointSize').on('change', function(){
	if(editToolbar.active === true && activeControl === "drawControls"){
		// build color array needed by esri api from rgb(a?) string

		var pointColor = $('#editPointColor').val();
		pointColor = pointColor.replace("rgb(", "[").replace("rgba(", "[").replace(")", "]");
		pointColor = JSON.parse(pointColor);
		var pointSymbolColor = new Color(pointColor);
		var pointSymbolOutline = new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID, new Color(pointSymbolColor), 2);

		editGraphic.symbol.setColor(pointSymbolColor);
		editGraphic.symbol.setOutline(pointSymbolOutline);
		editGraphic.symbol.setSize($('#editPointSize').val());

		userGraphicsLayer.redraw();
		$('.selectButton').removeClass('btn-danger').addClass('btn-success').html('<span class="fa fa-mouse-pointer"></span> Apply');
	}
});

$('#editStrokeColor, #editFillColor, #editLineWidth').on('change', function(){
	if(editToolbar.active === true && activeControl === "drawControls"){
		// build color array needed by esri api from rgb(a?) string

		var strokeColor = $('#editStrokeColor').val();
		strokeColor = strokeColor.replace("rgb(", "[").replace("rgba(", "[").replace(")", "]");
		strokeColor = JSON.parse(strokeColor);
		var strokeSymbol = new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID, new Color(strokeColor), parseInt($('#editLineWidth').val()));

		if(editGraphic.geometry.type === "polyline"){
			editGraphic.setSymbol(strokeSymbol);
		} else { // polygon... have to build simpleFillSymbol
			var fillColor = $('#editFillColor').val();
			fillColor = fillColor.replace("rgb(", "[").replace("rgba(", "[").replace(")", "]");
			fillColor = JSON.parse(fillColor);
			var fillSymbol = new SimpleFillSymbol(SimpleFillSymbol.STYLE_SOLID, strokeSymbol,new Color(fillColor));
			editGraphic.setSymbol(fillSymbol);
		}

		$('.selectButton').removeClass('btn-danger').addClass('btn-success').html('<span class="fa fa-mouse-pointer"></span> Apply');
	}
});

// Edit Toolbar
function createEditToolbar(){
	editToolbar = new Edit(map);
	setEditToobarListener();
}

function setEditToobarListener(){
	editToolbar.active = false;
	editToolbar.on('activate', function(){
		editToolbar.active = true;
		disableToolSelection();
	});
	editToolbar.on('deactivate', function(){
		editToolbar.active = false;
		enableToolSelection();
	});
}

$('.deleteButton').on("click", function (e){
	for(var i = 0; i < selectedGeometry.length; i++){
		userGraphicsLayer.remove(selectedGeometry[i]);
	}
	selectedGeometry = [];
	if($('.selectButton').hasClass('btn-success')){
		$('.selectButton').removeClass('btn-success')
	}
	if($('.selectButton').hasClass('btn-danger')){
		$('.selectButton').removeClass('btn-danger')
	}
	$('.selectButton').addClass('btn-primary').html('<span class="fa fa-mouse-pointer"></span> Select');
	$('#map_layers').css('cursor', '');
	updateURLgeometry();
	cancelDrawingSelection();
	activateDrawSubtools();
});

//control to clear all graphics
$('.deleteAllButton').on("click", function(){
	showConfirm('Wait!', 'Are you sure you want to delete all graphics? This action cannot be undone.');
	$('#confirmModalWrapper').fadeIn();
	$('#confirmAgree').on("click", function(){
		userGraphicsLayer.clear();
		activateDrawSubtools();
		updateURLgeometry();
		closeConfirm();
		cancelDrawingSelection();
	});
});

// MEASUREMENT TOOLS
// These are just the draw tools, but with different functionality. We didn't want this to look like the ESRI dijit...

//First, we need a new layer for measurment on screen graphics, as well as some measurement symbols.
// we also attach a timestamp as a sort of link between the graphics, and the table entries in the black pane
var measureLocationMoveHandler, measureLocationClickHandler, measureTimestamp;

$('#measureLocation').on("click", function(){
	if($(this).hasClass('active') == false){
		var dateObj = new Date();
		measureTimestamp = dateObj.getTime().toString();

		cancelMeasurement();
		navToolbar.deactivate();

		// show the correct units dropdown
		$('#measureLocationUnits').removeClass('hidden');
		$('#measureDistanceUnits, #measureAreaUnits').addClass('hidden');

		toolbar.activate(Draw["POINT"]);
		measureLocationMoveHandler = map.on('mouse-move', function(e){
			// THIS FUNCTION DOES NOT CURRENTLY HANDLE NEGATIVE NUMBERS CORRECTLY
			var measureLngLat = webMercatorUtils.xyToLngLat(e.mapPoint.x, e.mapPoint.y);
				if($('#measureLocationUnits').val() == 9096){
				var d, m, s;
				var eastWest, northSouth;
				if(measureLngLat[0] < 0){
					eastWest = 'W';
					measureLngLat[0] *= -1;
				} else {
					eastWest = 'E';
				}
				if(measureLngLat[1] < 0){
					northSouth = 'S';
					measureLngLat[1] *= -1;
				} else {
					northSouth = 'N';
				}
				d = Math.floor(measureLngLat[0]);
				m = Math.floor((measureLngLat[0] - d) * 60);
				s = ((measureLngLat[0] - d) * 60 - m) * 60;
				s = s.toFixed(3);
				measureLngLat[0] = eastWest + ' ' + d + '&deg; ' + m + '\' ' + s + '"';

				d = Math.floor(measureLngLat[1]);
				m = Math.floor((measureLngLat[1] - d) * 60);
				s = ((measureLngLat[1] - d) * 60 - m) * 60;
				s = s.toFixed(3);
				measureLngLat[1] = northSouth + ' ' + d + '&deg; ' + m + '\' ' + s + '"';
			} else if($('#measureLocationUnits').val() !== "9102"){ 
				var statePlaneTrans = projectGeometry(e.mapPoint, parseInt($('#measureLocationUnits').val()));	
				//var statePlaneTrans = Proj4js.transform(webDef, new Proj4js.Proj($('#measureLocationUnits').val()), new Proj4js.Point(e.mapPoint.x, e.mapPoint.y));
				measureLngLat[0] = statePlaneTrans.x.toFixed(8);
				measureLngLat[1] = statePlaneTrans.y.toFixed(8);
			} else {
				measureLngLat[0] = measureLngLat[0].toFixed(8);
				measureLngLat[1] = measureLngLat[1].toFixed(8);
			}
			$('#cursorLng').html(measureLngLat[0]);
			$('#cursorLat').html(measureLngLat[1]);
		})
		measureLocationClickHandler = map.on("click", function(e){
			var measureLngLat = webMercatorUtils.xyToLngLat(e.mapPoint.x, e.mapPoint.y);
			// convert to DMS if selected
			if($('#measureLocationUnits').val() == 9096){
				var d, m, s;
				var eastWest, northSouth;
				if(measureLngLat[0] < 0){
					eastWest = 'W';
					measureLngLat[0] *= -1;
				} else {
					eastWest = 'E';
				}
				if(measureLngLat[1] < 0){
					northSouth = 'S';
					measureLngLat[1] *= -1;
				} else {
					northSouth = 'N';
				}
				d = Math.floor(measureLngLat[0]);
				m = Math.floor((measureLngLat[0] - d) * 60);
				s = ((measureLngLat[0] - d) * 60 - m) * 60;
				s = s.toFixed(3);
				measureLngLat[0] = eastWest + ' ' + d + '&deg; ' + m + '\' ' + s + '"';

				d = Math.floor(measureLngLat[1]);
				m = Math.floor((measureLngLat[1] - d) * 60);
				s = ((measureLngLat[1] - d) * 60 - m) * 60;
				s = s.toFixed(3);
				measureLngLat[1] = northSouth + ' ' + d + '&deg; ' + m + '\' ' + s + '"';
			} else if($('#measureLocationUnits').val() !== "9102"){// i think this block is unused - connor - 1/23/2019 <-- connor was wrong - Bryan :P
				var statePlaneTrans = projectGeometry(e.mapPoint, parseInt($('#measureLocationUnits').val()));	
				//var statePlaneTrans = Proj4js.transform(webDef, new Proj4js.Proj($('#measureLocationUnits').val()), new Proj4js.Point(e.mapPoint.x, e.mapPoint.y));
				measureLngLat[0] = statePlaneTrans.x.toFixed(8);
				measureLngLat[1] = statePlaneTrans.y.toFixed(8);
			} else {
				measureLngLat[0] = measureLngLat[0].toFixed(8);
				measureLngLat[1] = measureLngLat[1].toFixed(8);
			}

			setTimeout(function(){
				toolbar.activate(Draw["POINT"]); // cheater workaround for multipoint. This should be improved later.
			}, 100);

		})
	}
})

measurementLabelsLayer = new GraphicsLayer({id: "measurementLabelsLayer"});
measurementLabelsLayer.setMinScale(map.getScale()*3);

//var measureDistanceUnitHandler;
$('#measureDistance').on("click", function(){
	if($(this).hasClass('active') == false){
		var dateObj = new Date();
		measureTimestamp = dateObj.getTime().toString();

		cancelMeasurement();
		navToolbar.deactivate();

		// show the correct units dropdown
		$('#measureDistanceUnits').removeClass('hidden');
		$('#measureLocationUnits, #measureAreaUnits').addClass('hidden');

		$('#drawUnits').val($('#measureDistanceUnits').val());
		measureDistanceUnitHandler = $('#measureDistanceUnits').on("change", function(){
			$('#drawUnits').val($('#measureDistanceUnits').val());
		});
		toolbar.lineSymbol.color.r = 0;
		toolbar.lineSymbol.color.g = 128;
		toolbar.lineSymbol.color.b = 255;
		toolbar.activate(Draw["POLYLINE"]);
	}

})
$('#measureArea').on("click", function(){
	if($(this).hasClass('active') == false){
		var dateObj = new Date();
		measureTimestamp = dateObj.getTime().toString();

		cancelMeasurement();
		navToolbar.deactivate();
		
		// show the correct units dropdown
		$('#measureAreaUnits').removeClass('hidden');
		$('#measureLocationUnits, #measureDistanceUnits').addClass('hidden');

		toolbar.fillSymbol.color.r = 0;
		toolbar.fillSymbol.color.g = 128;
		toolbar.fillSymbol.color.b = 255;
		toolbar.fillSymbol.outline.color.r = 0;
		toolbar.fillSymbol.outline.color.g = 128;
		toolbar.fillSymbol.outline.color.b = 255;
		toolbar.activate(Draw["POLYGON"]);
	}
})

var measureHoverOnHandler, measureHoverOffHandler;

enableMeasureHover = function enableMeasureHover(trueFalse){
	if(trueFalse == true){

		//function to handle graphics hover while select button is active on drawing tools
		measureHoverOnHandler = measurementLayer.on('mouse-over', function(evt) {
			if(evt.graphic.geometry.type == "polyline"){
				var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
				$(evt.target).attr('stroke-width', newWidth);
			} else if(evt.graphic.geometry.type == "polygon" || evt.graphic.geometry.type == "extent"){
				var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
				$(evt.target).attr('stroke-width', newWidth);
			} else if (evt.graphic.geometry.type == "point" && evt.graphic.symbol.type == "textsymbol"){
				var newSize = parseInt($(evt.target).attr('font-size')) + 2;
				$(evt.target).attr('font-size', newSize);
			} else if(evt.graphic.geometry.type == "point"){
				var newSize = parseInt($(evt.target).attr('stroke-width')) + 2;
				$(evt.target).attr('stroke-width', newSize);
			}
		});
		measureHoverOffHandler = measurementLayer.on('mouse-out', function(evt) {
			if(evt.graphic.geometry.type == "polyline"){
				var newWidth = parseInt($(evt.target).attr('stroke-width')) - 2;
				$(evt.target).attr('stroke-width', newWidth);
				evt.graphic.draw();
			} else if(evt.graphic.geometry.type == "polygon" || evt.graphic.geometry.type == "extent"){
				var newWidth = parseInt($(evt.target).attr('stroke-width')) - 2;
				$(evt.target).attr('stroke-width', newWidth);
				evt.graphic.draw();
			} else if (evt.graphic.geometry.type == "point" && evt.graphic.symbol.type == "textsymbol"){
				var newSize = parseInt($(evt.target).attr('font-size')) - 2;
				$(evt.target).attr('font-size', newSize);
			} else if(evt.graphic.geometry.type == "point"){
				var newSize = parseInt($(evt.target).attr('stroke-width')) - 2;
				$(evt.target).attr('stroke-width', newSize);
			}
		});
		// end of hover handler
	} else {
		if(typeof(measureHoverOnHandler) !== "undefined"){
			measureHoverOnHandler.remove();
			measureHoverOnHandler = undefined;
		}
		if(typeof(measureHoverOffHandler) !== "undefined"){
			measureHoverOffHandler.remove();
			measureHoverOffHandler = undefined;
		}
	}

};

// This event handler fires for all buttons. The rest are for the individual
$('.measureButton').on("click", function (){
	if($(this).hasClass('active')){
		cancelMeasurement();
	} else {
		//navToolbar.deactivate(); // For whatever reason, these have to be on the handlers for the IDs.
		highlightLayer.hide();
		$('#measureResultsScrollPane').perfectScrollbar("update");
		$('.measureButton').removeClass('active');
		$(this).addClass('active');
		$('#cancelMeasureButton').removeClass('disabledButton');
		enableMeasurementSnappng();
		if(measurementLayer.graphics.length > 0){
			$('#clearMeasureButton').removeClass('disabledButton');
		}
		$('#selectMeasureButton').addClass('disabledButton');
	}
});

$('#cancelMeasureButton').on("click", function (){
	cancelMeasurement();
});

$('#clearMeasureButton').on("click", function (){
	clearAllMeasurements();
	$('#clearMeasureButton, #selectMeasureButton').addClass('disabledButton');
});

cancelMeasurement = function cancelMeasurement(){
	enableMeasureHover(false);
	if(typeof(measureSelectionHandler) !== "undefined"){
		measureSelectionHandler.remove();
	}

	// restore selected graphics symbols
	for (var i = 0; i < measurementLayer.graphics.length; i++){

		if(measurementLayer.graphics[i].geometry.type === "point"){
			measurementLayer.graphics[i].setSymbol(measureLocationSymbol);
		}
		if(measurementLayer.graphics[i].geometry.type === "polyline"){
			measurementLayer.graphics[i].setSymbol(measureDistanceSymbol);
		}
		if(measurementLayer.graphics[i].geometry.type === "polygon"){
			measurementLayer.graphics[i].setSymbol(measureAreaSymbol);
		}

		selectedMsmtGraphics = []; // remove feature from selected array

	}
	disableMeasurementSnapping();

	if(toolbar.active){
		$('#clearMeasureButton, #selectMeasureButton').removeClass('disabledButton');
		$('#cancelMeasureButton').addClass('disabledButton');
		$('.measureButton').removeClass('active');
		cancelDrawingSelection();

		$('#lengthOutput').addClass('hidden');
		$('#cursorLng, #cursorLat').html('');

		for(var i = measurementLabelsLayer.graphics.length - 1; i > -1; i--){
			if(measurementLabelsLayer.graphics[i].attributes.labelKey === measureTimestamp){
				measurementLabelsLayer.remove(measurementLabelsLayer.graphics[i]);
			}
		}
		// remove table entries (currently only distance measurements)
		$('.DMrow').each(function(index){
			if($('.DMrow').eq(index).attr('labelKey') === measureTimestamp){
				$('.DMrow').eq(index).remove();
			}
		});
		if($('.DMrow').length === 0){
			$('#measureDistanceTable').html('<tr><td>no measurements</td><td></td></tr>');
		}

		if(measureLocationMoveHandler != undefined){
			measureLocationMoveHandler.remove();
		}
		if(measureLocationClickHandler != undefined){
			measureLocationClickHandler.remove();
		}
		if(measureDistanceUnitHandler != undefined){
			measureDistanceUnitHandler.off();
			measureDistanceUnitHandler = undefined;
		}
		$('#measureResultsScrollPane').perfectScrollbar("update");
		highlightLayer.show();

		if(measurementLayer.graphics.length === 0){
			$('#clearMeasureButton, #selectMeasureButton').addClass('disabledButton');
		}
		updateURLgeometry();
	} else if($('#selectMeasureButton').hasClass('active')){
		enableMeasureHover(false);
		$('#selectMeasureButton').trigger('click');
	}
	$('#cancelMeasureButton').trigger('blur');
	$('#deleteMeasureButton').trigger('blur').addClass('disabledButton');
};

clearAllMeasurements = function clearAllMeasurements(){
	measurementLayer.clear();
	measurementLabelsLayer.clear();
	updateURLgeometry();
	// clear the results tables
	$('.mlRow').remove();
	$('#measureDistanceTable, #measureAreaTable').html('<tr><td>no measurements</td><td></td></tr>');
}

$('#selectMeasureButton').on("click", function(e){
	if($('#cancelMeasureButton').hasClass('disabledButton')){
		$('#cancelMeasureButton').removeClass('disabledButton');
		$('#clearMeasureButton').addClass('disabledButton');
		$('#selectMeasureButton').addClass('active').trigger('blur');

		var context = this;
		enableMeasureHover(true);
		measureSelectionHandler = measurementLayer.on("click", function(evt){
			measurementOnSelect.call(context,evt);
		});
	} else {
		enableMeasureHover(false);
		$('#cancelMeasureButton').addClass('disabledButton');
		$('#clearMeasureButton').removeClass('disabledButton');
		$('#selectMeasureButton').removeClass('active');
		if(typeof(measureSelectionHandler) !== "undefined"){
			measureSelectionHandler.remove();
		}
		// restore selected graphics symbols
		for (var i = 0; i < measurementLayer.graphics.length; i++){

			if(measurementLayer.graphics[i].geometry.type === "point"){
				measurementLayer.graphics[i].setSymbol(measureLocationSymbol);
			}
			if(measurementLayer.graphics[i].geometry.type === "polyline"){
				measurementLayer.graphics[i].setSymbol(measureDistanceSymbol);
			}
			if(measurementLayer.graphics[i].geometry.type === "polygon"){
				measurementLayer.graphics[i].setSymbol(measureAreaSymbol);
			}

			selectedMsmtGraphics = []; // remove feature from selected array

		}
	}
});


window.selectedMsmtGraphics = [];
measurementOnSelect = function(evt){

	// Check if the user already selected the graphic so we don't fill an array with duplicates
	var alreadyExists = false;
	for(var i = 0; i < selectedMsmtGraphics.length; i++){
		if(selectedMsmtGraphics[i].attributes == evt.graphic.attributes){
			alreadyExists = true;
		}
	}

	if (alreadyExists){
		for (var i = 0; i < selectedMsmtGraphics.length; i++){
			if (selectedMsmtGraphics[i].attributes == evt.graphic.attributes){
				if(selectedMsmtGraphics[i].geometry.type === "point"){
					selectedMsmtGraphics[i].setSymbol(measureLocationSymbol);
				}
				if(selectedMsmtGraphics[i].geometry.type === "polyline"){
					selectedMsmtGraphics[i].setSymbol(measureDistanceSymbol);
				}
				if(selectedMsmtGraphics[i].geometry.type === "polygon"){
					selectedMsmtGraphics[i].setSymbol(measureAreaSymbol);
				}

				selectedMsmtGraphics.splice(i, 1); // remove feature from selected array
			}
		}

	} else if(alreadyExists == false) {

		// add graphic to array of selected geometries and highlight it by changing it's symbol
		selectedMsmtGraphics[selectedMsmtGraphics.length] = evt.graphic;

		if(evt.graphic.geometry.type == "polyline"){ // lines are still of geometry type polyline
			evt.graphic.setSymbol(selectedLineSymbol);
			var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
			$(evt.target).attr('stroke-width', newWidth);
		} else if(evt.graphic.geometry.type == "polygon"){
			evt.graphic.setSymbol(selectedPolygonSymbol);
			var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
			$(evt.target).attr('stroke-width', newWidth);
		} else if (evt.graphic.geometry.type == "point" && evt.graphic.symbol.type == "textsymbol"){
			if(selectedGeometry.length > 1){
				var txt = evt.graphic.symbol.text;
				var size = evt.graphic.symbol.font.size;
				var fontFam = evt.graphic.symbol.font.family;
				evt.graphic.setSymbol(setSelectedText(txt, size, fontFam));
				var newSize = parseInt($(evt.target).attr('font-size')) + 2;
				$(evt.target).attr('font-size', newSize);
			}
		} else if (evt.graphic.geometry.type == "point"){
			x = evt.graphic;
			var size = evt.graphic.symbol.size;
			var style = evt.graphic.symbol.style;
			if(style != 'path'){
				evt.graphic.setSymbol(setSelectedPoint(size, style));

			} else {
				var path = evt.graphic.symbol.path;
				var xoffset = evt.graphic.symbol.xoffset;
				var yoffset = evt.graphic.symbol.yoffset;
				evt.graphic.setSymbol(setSelectedSVG(path,size,xoffset,yoffset));
			}
			var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
			$(evt.target).attr('stroke-width', newWidth);
		}


	}

	if(selectedMsmtGraphics.length > 0){
		$('#deleteMeasureButton').removeClass('disabledButton');
	} else {
		$('#deleteMeasureButton').addClass('disabledButton');
	}

}

$('#deleteMeasureButton').on("click", function(){
	deleteMeasurements();
});

function deleteMeasurements(){
	// this deletes all selected measurements and their labels with a reverse loop
	for (var i = selectedMsmtGraphics.length - 1; i > -1; i--){
		for(var j = measurementLabelsLayer.graphics.length - 1; j > -1; j--){
			if(measurementLabelsLayer.graphics[j].attributes.labelKey === selectedMsmtGraphics[i].attributes.labelKey){
				measurementLabelsLayer.remove(measurementLabelsLayer.graphics[j]);
			}
		}
		measurementLayer.remove(selectedMsmtGraphics[i]);
	}
	updateURLgeometry();
	cancelMeasurement();
	rebuildMeasureLabels();
}

// function to rebuild the measurement output stuff in the control pane on page reload/share link
function rebuildMeasureLabels(pageLoad){

	// first we wipe them if they're still left over from having just been deleted
	$('.mlRow, .DMrow, .AMrow').remove();

	for(var i = 0; i < measurementLayer.graphics.length; i++){
		if(measurementLayer.graphics[i].geometry.type === "point"){
			var measureLngLat = webMercatorUtils.xyToLngLat(measurementLayer.graphics[i].geometry.x, measurementLayer.graphics[i].geometry.y);

			if(pageLoad){
				// update label for location in case this is a page reload (since the user isn't clicking the select)
				$("#measureLocationUnits").val(measurementLayer.graphics[i].attributes.unit);

				// NOTE: THIS IS NOT COMPLETE, BUT I DON'T SEE A PROJECTION LISTED FOR SOME OHIO STATE PLANE CODES
				// CHECK HERE: https://developers.arcgis.com/rest/services-reference/projected-coordinate-systems.htm
				if(measurementLayer.graphics[i].attributes.unit == "2251" || measurementLayer.graphics[i].attributes.unit == "2252" || measurementLayer.graphics[i].attributes.unit == "2253"){ // for dowmiopsutil (STATE PLANE MICHINGAN SOUTH), but will be for any state plane at some point
					// change text to Easting/Northing if state plane
					$("#measureLocationTable th").eq(1).html("Easting/Northing");
				}
			}
			
			// convert to DMS if selected
			if(measurementLayer.graphics[i].attributes.unit == "9096"){
				var d, m, s;
				var eastWest, northSouth;
				if(measureLngLat[0] < 0){
					eastWest = 'W';
					measureLngLat[0] *= -1;
				} else {
					eastWest = 'E';
				}
				if(measureLngLat[1] < 0){
					northSouth = 'S';
					measureLngLat[1] *= -1;
				} else {
					northSouth = 'N';
				}
				d = Math.floor(measureLngLat[0]);
				m = Math.floor((measureLngLat[0] - d) * 60);
				s = ((measureLngLat[0] - d) * 60 - m) * 60;
				s = s.toFixed(3);
				measureLngLat[0] = eastWest + ' ' + d + '&deg; ' + m + '\' ' + s + '"';

				d = Math.floor(measureLngLat[1]);
				m = Math.floor((measureLngLat[1] - d) * 60);
				s = ((measureLngLat[1] - d) * 60 - m) * 60;
				s = s.toFixed(3);
				measureLngLat[1] = northSouth + ' ' + d + '&deg; ' + m + '\' ' + s + '"';
			} else if(measurementLayer.graphics[i].attributes.unit !== "9102"){ // i think this block is unused - connor - 1/23/2019 <-- you thought wrong - Bryan :P
				var statePlaneTrans = projectGeometry(measurementLayer.graphics[i].geometry, parseInt(measurementLayer.graphics[i].attributes.unit));
				//var statePlaneTrans = Proj4js.transform(webDef, new Proj4js.Proj(measurementLayer.graphics[i].attributes.unit), new Proj4js.Point(measurementLayer.graphics[i].geometry.x, measurementLayer.graphics[i].geometry.y));
				measureLngLat[0] = statePlaneTrans.x.toFixed(8);
				measureLngLat[1] = statePlaneTrans.y.toFixed(8);
			} else /*(its 9102: or in other words just regluar lngLat degrees)*/{
				measureLngLat[0] = measureLngLat[0].toFixed(8);
				measureLngLat[1] = measureLngLat[1].toFixed(8);
			}

			var rowStr = '<tr class="mlRow" labelKey="' + measurementLayer.graphics[i].attributes.labelKey + '">'+
							'<td rowspan="2" class="mlCol1"><span class="fa fa-map-marker fa-lg"></span></td>'+
							'<td colspan="2">' + measureLngLat[0] + '</td>'+
						'</tr>'+
						'<tr class="mlRow" labelKey="' + measurementLayer.graphics[i].attributes.labelKey + '">'+
							'<td colspan="2">' + measureLngLat[1] + '</td>'+
						'</tr>';

			if($('#measureLocationCursorEnd').next().hasClass('mlRow')){
				$('.mlRow').last().after(rowStr);
			} else {
				$('#measureLocationCursorEnd').after(rowStr);
			}
		}

		if(measurementLayer.graphics[i].geometry.type === "polyline"){
			var numberOfDMrows = $(".DMrow").length + 1; // add one because non computer science types don't like counting up from zero as a starting point
			var unitStr;
			var unitCode = measurementLayer.graphics[i].attributes.unit;
			switch(unitCode){
				case '9002':
					unitStr = " ft";
					break;
				case '9096':
					unitStr = " yds";
					break;
				case '9035':
					unitStr = " miles";
					break;
				case '9001':
					unitStr = " meters";
					break;
				case '9036':
					unitStr = " km";
					break;
				default:
					unitStr = " ft";
					break;
			}

			var lengthOfLine = geometryEngine.geodesicLength(measurementLayer.graphics[i].geometry, parseInt(unitCode));

			if($(".DMrow").length === 0){
				$('#measureDistanceTable tbody').html("");
			}

			// DM stands for distance measurement
			$('#measureDistanceTable').append('<tr id="DMrow' + numberOfDMrows + '" class="DMrow" labelKey="' + measurementLayer.graphics[i].attributes.labelKey+ '"><td>Distance ' + numberOfDMrows + '</td><td><span id="measureTotalLength' + numberOfDMrows +'"</span>' + lengthOfLine.toFixed(1) + ' ' + unitStr + '</td></tr>');
		}

		if(measurementLayer.graphics[i].geometry.type === "polygon"){
			var output = geometryEngine.geodesicArea(measurementLayer.graphics[i].geometry, parseInt(measurementLayer.graphics[i].attributes.unit));
			var unitStr;
			switch(measurementLayer.graphics[i].attributes.unit){
				case '109402':
					unitStr = " ac";
					break;
				case '109439':
					unitStr = " mi&sup2;";
					break;
				case '109442':
					unitStr = " yds&sup2;";
					break;
				case '109405':
					unitStr = " ft&sup2;";
					break;
				case '109414':
					unitStr = " km&sup2;";
					break;
					case '109404':
					unitStr = " M&sup2;";
					break;
					case '109401':
					unitStr = " ha";
					break;
				default:
					unitStr = " ft&sup2;";
					break;
			}

			if($('#measureAreaTable td').eq(0).html() === "no measurements"){
				$('#measureAreaTable td').eq(0).html("");
			}

			var numberOfAMrows = $(".AMrow").length + 1;
			// AM stands for distance measurement
			$('#measureAreaTable').append('<tr id="AMrow' + numberOfAMrows + '" class="AMrow" labelKey="' + measurementLayer.graphics[i].attributes.labelKey+ '"><td>Area ' + numberOfAMrows + '</td><td>' + output.toFixed(2) + unitStr + '</td></tr>');

		}
		setMeasureResultsListHandlers();
	}

}

window.setMeasureResultsListHandlers = function(){
	// set handlers for selecting location measurements from the results pane
	$('.mlRow').on('mouseover', function(e){
		var key = e.currentTarget.attributes.labelKey.value;
		for(var i = 0; i < measurementLayer.graphics.length; i++){
			if(measurementLayer.graphics[i].attributes.labelKey === key){
				var mpSelSymbol = new SimpleMarkerSymbol(measureLocationSymbol.toJson());
				mpSelSymbol.setColor(new dojo.Color([255, 0, 0, 0.2]));
				mpSelSymbol.outline.setColor(new dojo.Color([255, 0, 0]));
				measurementLayer.graphics[i].setSymbol(mpSelSymbol);
			}
		}
	});

	// set handlers for selecting location measurements from the results pane
	$('.mlRow').on('mouseout', function(e){
		var key = e.currentTarget.attributes.labelKey.value;
		for(var i = 0; i < measurementLayer.graphics.length; i++){
			if(measurementLayer.graphics[i].attributes.labelKey === key){
				measurementLayer.graphics[i].setSymbol(measureLocationSymbol);
			}
		}
	});

	// set handlers for selecting distance measurements from the results pane
	$('.DMrow').on('mouseover', function(e){
		var key = e.currentTarget.attributes.labelKey.value;
		for(var i = 0; i < measurementLayer.graphics.length; i++){
			if(measurementLayer.graphics[i].attributes.labelKey === key){
				//console.log(key);
				var selectedMLsym = new SimpleLineSymbol(
					SimpleLineSymbol.STYLE_SOLID,
					new Color([255,0,0]), 3
				);

				measurementLayer.graphics[i].setSymbol(selectedMLsym);
			}
		}
		for(var j = 0; j < measurementLabelsLayer.graphics.length; j++){
			console.log(measurementLabelsLayer.graphics[j].attributes.labelKey + " vs " + key);
			if(measurementLabelsLayer.graphics[j].attributes.labelKey === key){
				console.log(key);

				var highlightTextSymbol = new TextSymbol(measurementLabelsLayer.graphics[j].symbol.toJson());
				highlightTextSymbol.setColor(new dojo.Color([255, 0, 0]));
				measurementLabelsLayer.graphics[j].setSymbol(highlightTextSymbol);
			}
		}
	});

	$('.DMrow').on('mouseout', function(e){
		var key = e.currentTarget.attributes.labelKey.value;
		for(var i = 0; i < measurementLayer.graphics.length; i++){
			if(measurementLayer.graphics[i].attributes.labelKey === key){
				measurementLayer.graphics[i].setSymbol(measureDistanceSymbol);
			}
		}
		for(var j = 0; j < measurementLabelsLayer.graphics.length; j++){
			console.log(measurementLabelsLayer.graphics[j].attributes.labelKey + " vs " + key);
			if(measurementLabelsLayer.graphics[j].attributes.labelKey === key){
				console.log(key);

				var regalaTextSymbol = new TextSymbol(measurementLabelsLayer.graphics[j].symbol.toJson());
				regalaTextSymbol.setColor(new dojo.Color([0, 128, 255]));
				measurementLabelsLayer.graphics[j].setSymbol(regalaTextSymbol);
			}
		}
	});

	// set handlers for selecting distance measurements from the results pane
	$('.AMrow').on('mouseover', function(e){
		var key = e.currentTarget.attributes.labelKey.value;
		for(var i = 0; i < measurementLayer.graphics.length; i++){
			if(measurementLayer.graphics[i].attributes.labelKey === key){
				//console.log(key);
				var selectedMAsym = new SimpleFillSymbol(
					SimpleFillSymbol.STYLE_SOLID,
					new SimpleLineSymbol(
						SimpleLineSymbol.STYLE_SOLID,
						new Color([255,0,0]), 3
					),
					new Color([255,0,0,0.1])
				);

				measurementLayer.graphics[i].setSymbol(selectedMAsym);
			}
		}
		for(var j = 0; j < measurementLabelsLayer.graphics.length; j++){
			console.log(measurementLabelsLayer.graphics[j].attributes.labelKey + " vs " + key);
			if(measurementLabelsLayer.graphics[j].attributes.labelKey === key){
				console.log(key);

				var highlightTextSymbol = new TextSymbol(measurementLabelsLayer.graphics[j].symbol.toJson());
				highlightTextSymbol.setColor(new dojo.Color([255, 0, 0]));
				measurementLabelsLayer.graphics[j].setSymbol(highlightTextSymbol);
			}
		}
	});

	$('.AMrow').on('mouseout', function(e){
		var key = e.currentTarget.attributes.labelKey.value;
		for(var i = 0; i < measurementLayer.graphics.length; i++){
			if(measurementLayer.graphics[i].attributes.labelKey === key){
				measurementLayer.graphics[i].setSymbol(measureAreaSymbol);
			}
		}
		for(var j = 0; j < measurementLabelsLayer.graphics.length; j++){
			console.log(measurementLabelsLayer.graphics[j].attributes.labelKey + " vs " + key);
			if(measurementLabelsLayer.graphics[j].attributes.labelKey === key){
				console.log(key);

				var regalaTextSymbol = new TextSymbol(measurementLabelsLayer.graphics[j].symbol.toJson());
				regalaTextSymbol.setColor(new dojo.Color([0, 128, 255]));
				measurementLabelsLayer.graphics[j].setSymbol(regalaTextSymbol);
			}
		}
	});
}

// measuring/buffering symbols
var measureLocationSymbol = new SimpleMarkerSymbol(SimpleMarkerSymbol.STYLE_PATH, 22,
	new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID,
	new Color([0,128,255]), 2),
	new Color([0,128,255,0.25])
);
measureLocationSymbol.setPath("M16,3.5c-4.142,0-7.5,3.358-7.5,7.5c0,4.143,7.5,18.121,7.5,18.121S23.5,15.143,23.5,11C23.5,6.858,20.143,3.5,16,3.5z M16,14.584c-1.979,0-3.584-1.604-3.584-3.584S14.021,7.416,16,7.416S19.584,9.021,19.584,11S17.979,14.584,16,14.584z");
measureLocationSymbol.setOffset(0,11);

var measureDistanceSymbol = new SimpleLineSymbol(
	SimpleLineSymbol.STYLE_SOLID,
	new Color([0,128,255]), 3
);

var measureAreaSymbol = new SimpleFillSymbol(
  SimpleFillSymbol.STYLE_SOLID,
  new SimpleLineSymbol(
	SimpleLineSymbol.STYLE_SOLID,
	new Color([0,128,255]), 3
  ),
  new Color([0,128,255,0.1])
);

window.bufferRenderer = new SimpleFillSymbol(
  SimpleFillSymbol.STYLE_SOLID,
  new SimpleLineSymbol(
	SimpleLineSymbol.STYLE_SOLID,
	new Color([255, 255, 0]), 3
  ),
  new Color([0,0,0, .2])
);

// These functions control the application state when switching controls.
$('.navButton, #editingToolsHeading, #drawingToolsHeading, #measureToolsHeading, #bufferToolsHeading').on("click", function(e){
	// if user is just setting location tracking enabled, don't do anything.
	if(this.id === 'setLocation' || this.id === 'mapSwitcher' || this.id === 'openHelp' || this.id === 'outdoorMode'){
		return false;
	}

	if($('#deleteEditFeature').hasClass('fa-check-square-o')){ // if eh editing is active, cancel editing
		$('#deleteEditFeature').trigger('click');
	}

	$("#showDetails").html("Show Details");
	
	if($(this).prop('id') == 'drawingToolsHeading' || $(this).prop('id') == 'measureToolsHeading' || $(this).prop('id') == 'bufferToolsHeading' || $(this).hasClass('navButton')){
		editToolbar.deactivate();
		if($(this).hasClass('navButton') === false){
			cancelMeasurement(); // we don't want the nav buttons to cancel measurement (say the user wants to toggle layers or print the measurement)
		}
		exitLabelPrint();
		if($(this).prop('id') != 'printModeToggle' && $(this).prop('id') != 'bufferToolsHeading' && $(this).prop('id') != 'draw' && $(this).prop('id') != 'share'){
			cancelBuffer();
		}

		$('#toggleEditing').removeClass('btn-primary').addClass('btn-warning').html('<span class="fa fa-square-o"></span> Disabled');
	}
	if($(this).prop('id') == 'editingToolsHeading' || $(this).prop('id') == 'measureToolsHeading' || $(this).prop('id') == 'bufferToolsHeading' || $(this).hasClass('navButton')){
		cancelDrawingSelection();
		if($(this).hasClass('navButton') === false){
			cancelMeasurement(); // we don't want the nav buttons to cancel measurement (say the user wants to toggle layers or print the measurement)
		}
		exitLabelPrint();
		if($(this).prop('id') != 'printModeToggle' && $(this).prop('id') != 'bufferToolsHeading' && $(this).prop('id') != 'draw' && $(this).prop('id') != 'signIn' && $(this).prop('id') != 'share'){
			cancelBuffer();
		}
	}
});
	
cancelDrawingSelection = function cancelDrawingSelection(restoreGraphics){
	rmUncloseDrawPolygonHandler();
	cancelDrawingRunning = true;
	$('#doneDrawing button, #cancelMeasureButton').html('Cancel');
	$('#textEditorContainer').fadeOut();
	cancel90Deg();
	cancelParperp(); // undo prallel or perpendicular stuff
	cancelSpecRectangle();
	toolbar.updateNullGeometry = undefined; //reset
	toolbar.deactivate();
	cancelDrawingWithSnap(); // must go after the draw toolbar is deactivated otherwise sometimes the snapping cross symbol still shows up under the mouse cursor
	editToolbar.deactivate();
	setDrawColorOption(); // This removes the specifice draw options and restores the delete buttons
	$('.drawButton').removeClass('active');
	$('.drawButton .closeDrawTool').addClass('hidden');
	$('#map_layers').css('cursor', '');
	$('.esriPopup, .dijitTooltipDialogPopup').css({'pointer-events': 'auto', 'opacity': 1});
	highlightLayer.show();
	$('#layerInfoHover').show();
	enablePopup();
	/*
	try{
		if(templateLayers != undefined){
			for(var i = 0; i < templateLayers.length; i++){
				templateLayers[i].setInfoTemplate(wildcardInfoTemplate);				
			}
		}
	} catch(err){
		console.log('no template layers4: ' + err.message);
	}*/
	
	$('.esriPopup, .dijitTooltipDialogPopup').css({'pointer-events': 'auto', 'opacity': 1});
	if(hasParcelLayer == true && activeControl != 'printControls'){
		try{
			for(layer in parcelLayers){
				window[layer].setInfoTemplate(parcelLayers[layer].infoTemplate);
				try {
					window[layer].enableMouseEvents();
				} catch (e) {

				}
			}
		} catch(err){
			console.log(err);
		}
	}
	
	//reset user drawn graphic symbol styles back to original
	if(restoreGraphics === true){
		for(var i = 0; i < selectedGeometry.length; i++){
			for(var j = 0; j < graphicStore.length; j++){
				if (selectedGeometry[i].attributes === graphicStore[j].ID){
					selectedGeometry[i].setSymbol(graphicStore[j].Symbol);
					selectedGeometry[i].setGeometry(graphicStore[j].Geometry);
				}
			}
		}
	}
	
	selectedGeometry = [];
	graphicStore = [];
	$('.editToolOption').fadeOut();
	if($('.selectButton').hasClass('btn-success')){
		$('.selectButton').removeClass('btn-success');
	}
	if($('.selectButton').hasClass('btn-danger')){
		$('.selectButton').removeClass('btn-danger');
	}
	$('.selectButton').addClass('btn-primary').html('<span class="fa fa-mouse-pointer"></span> Select');
	if(selectionHandler != undefined){
		selectionHandler.remove();
		selectionHandler = undefined;
	}

	enableGraphicHover(false);
	
	$('#lengthOutput').addClass('hidden');
	if(drawDistanceHandler1 != undefined){
		drawDistanceHandler1.remove();
	}
	if(drawDistanceHandler2 != undefined){
		drawDistanceHandler2.remove();
	}

	if(featureEditEnabled){ // if eh, do not animate
		$("#drawOptionsDiv").css({height: "0px"});
		$(".drawButton").addClass("noPointerEvents").animate({height: "32px", opacity: 1});
		$(".drawButton").removeClass("noPointerEvents");
		$("#drawControlsAccordionPane").perfectScrollbar("update");					
		cancelDrawingRunning = false; /// ! es muy importante, this tells the rest of the application when drawing has successfully been canceled, the eh template picker provides an oppportunity to start a drawing session when this is still running which es muy bueno
	} else {
		$("#drawOptionsDiv").animate({height: "0px"}, 250, function(){
			$(".drawButton").stop().addClass("noPointerEvents").animate({height: "32px", opacity: 1}, 400, function(){
				$(".drawButton").removeClass("noPointerEvents");
				$("#drawControlsAccordionPane").perfectScrollbar("update");
				cancelDrawingRunning = false; /// ! es muy importante, this tells the rest of the application when drawing has successfully been canceled, the eh template picker provides an oppportunity to start a drawing session when this is still running which es muy bueno
			});
		});
	}

	$('#doneDrawing').hide();
	
	$('#pline_Undo, #pline_Next, #pline_Cancel, #pline_Finish').css({
		pointerEvents: "none",
		opacity: '0.5'
	});
	$('.parperp').addClass('disabledButton');
	
	$('#pgon_Undo, #pgon_Next, #pgon_Cancel, #pgon_Finish').css({
		pointerEvents: "none",
		opacity: '0.5'
	});
	$('.parperp').addClass('disabledButton');
	$('#pline_dimInputDiv, #plineqbDinInput, #pline_polarDimInput').addClass('hidden');
	$('.deleteButton').css({pointerEvents: 'none', opacity: '0.5'});
	enableToolSelection();
}
	
function cancelBuffer(){
	// uncheck print legend checkbox if it's checked
	if($("#bufferPrintLegendCheckbox").hasClass("fa-check-square-o")){
		$("#bufferPrintLegendCheckbox").trigger('click');
	}
	$('#bufferPrintLegendCheckbox').parent().addClass('hidden');
	
	exitLabelPrint();
	clearBuffers();
	$('#bufferResultsDiv, #saveButtonsDiv, #bufferResultsTable').addClass('hidden');
	$("#bufferOutMessage").html('');
	$('#bufferControlsDiv').stop().animate({height: '260px'}, 400, function(){
			$("#bufferResultsScroll").perfectScrollbar('update');
		}
	);
	$("#bufferResultsScroll").perfectScrollbar();
	buff64 = undefined;
	updateURLgeometry();
}

$("#newBuffer").on("click", function(){
	//parcelLayer.infoTemplate = template; // reset template for parcel layer 
	for(layer in parcelLayers){
		window[layer].setInfoTemplate(parcelLayers[layer].infoTemplate);
	}
	
	cancelBuffer();
});

// this function used to be called putTheSmackdown when it was just an experiment. 
window.uncloseDrawPolygonObserver = undefined;
window.setUncloseDrawPolygonHandler = function(forceRemove){

	if(forceRemove === true){ // this is for special occasions, just removes the observer so we can re-initiate it
		rmUncloseDrawPolygonHandler();
	}

	if($('#map_graphics_layer g').children().length > 1 && uncloseDrawPolygonObserver === undefined){
		// Set handlers on scroll bar to update when content changes.
		var smackdownConfig = {
			attributes: true,
			childList: false,
			characterData: false
		};

		var smackdownChangeTimeout, smackdownInAction = false;
		var smackdownTargetElement;
		smackdownTargetElement = $('#map_graphics_layer g path').get($('#map_graphics_layer g path').length - 2);
		uncloseDrawPolygonObserver = new MutationObserver(function(mutations) {
			mutations.forEach(function(mutation) {
				//console.log(mutation.type);
				if(mutation.type === "attributes"){
					if(mutation.target.attributes.path.value.split(",").length > 3){
						console.log("zzzzz");
						if(!smackdownInAction){
							smackdownInAction = true;
							mutation.target.attributes.d.value = mutation.target.attributes.d.value.replace("Z", "");
							smackdownChangeTimeout = setTimeout(function(){
								smackdownInAction = false;
							}, 250);
						}
					}
				}
			});
		});
		uncloseDrawPolygonObserver.observe(smackdownTargetElement, smackdownConfig);
	}
}
window.rmUncloseDrawPolygonHandler = function(){
	if(typeof uncloseDrawPolygonObserver != "undefined"){
		uncloseDrawPolygonObserver.disconnect();
	}
	uncloseDrawPolygonObserver = undefined;
}	

	
	$('body').on('keydown', function(e){
		// 27 is escape
		if (e.keyCode == 27 || e.which == 27){
			if(activeControl == "featureEditControls"){
				if($("#templatePickerHeading").hasClass("ui-state-active") && templatePicker.getSelected() !== null){
					$("#cancelFeatureCreation").trigger("click");
				} else if($("#attributeEditorHeading").hasClass("ui-state-active") && $("#emptyDummyEditorDiv").hasClass("hidden")){ // emptyDummyEditorDiv just holds the text "No Features Selected"
					$("#cancelFeatureEditing").trigger("click");
				}
			} else if(activeControl == "drawControls"){
				if($("#drawingToolsHeading").hasClass("ui-state-active")){
					cancelDrawingSelection();
				} else if($("#measureToolsHeading").hasClass("ui-state-active")){
					$('#cancelMeasureButton').trigger("click");
				} else if($("#bufferToolsHeading").hasClass("ui-state-active")){
					exitLabelPrint();
				}
			} else if (activeControl == 'taperTool'){
				taperCreationController.deactivate();
			} else if (activeControl == 'lateralTool'){
				ehSepticLateralsController.endSession()
			}
		}
	});
	
		// NAV TOOL-BAR
		function createNavToolbar(){
			navToolbar = new Navigation(map);
			on(navToolbar, "onExtentHistoryChange", extentHistoryChangeHandler);
	
			registry.byId("zoomin").on("click", function () {
				cancelDrawingSelection();// gotta make parcel highlighting work here somehow
				navToolbar.activate(Navigation.ZOOM_IN);
				$("#map_layers").removeClass("pan zoomOut");
				$("#map_layers").addClass("zoomIn");
				$("#zoomout .dijitIcon, #pan .dijitIcon, #zoomout2 .dijitIcon, #pan2 .dijitIcon").removeClass("active");
				$("#zoomin .dijitIcon, #zoomin2 .dijitIcon").addClass("active");
			});
	
			registry.byId("zoomout").on("click", function () {
				cancelDrawingSelection();
				navToolbar.activate(Navigation.ZOOM_OUT);
				$("#map_layers").removeClass("pan zoomIn");
				$("#map_layers").addClass("zoomOut");
				$("#zoomin .dijitIcon, #pan .dijitIcon, #zoomin2 .dijitIcon, #pan2 .dijitIcon").removeClass("active");
				$("#zoomout .dijitIcon, #zoomout2 .dijitIcon").addClass("active");
			});
	
			registry.byId("zoomfullext").on("click", function () {
				//navToolbar.zoomToFullExtent();
				cancelDrawingSelection();
			try{map.setExtent(imageryLayer.fullExtent)}catch(err){console.log(err.message);} 	
				});
		
				registry.byId("zoomprev").on("click", function () {
					cancelDrawingSelection();
					navToolbar.zoomToPrevExtent();
				});

				registry.byId("zoomnext").on("click", function () {
					cancelDrawingSelection();
					navToolbar.zoomToNextExtent();
				});
		
				registry.byId("pan").on("click", function () {
					cancelDrawingSelection();
					navToolbar.activate(Navigation.PAN);
					$("#map_layers").removeClass("zoomIn zoomOut");
					$("#map_layers").addClass("pan");
					$("#zoomin .dijitIcon, #zoomout .dijitIcon, #zoomin2 .dijitIcon, #zoomout2 .dijitIcon").removeClass("active");
					$("#pan .dijitIcon, #pan2 .dijitIcon").addClass("active");
				});
		
				registry.byId("deactivate").on("click", function () {
					cancelDrawingSelection();
					navToolbar.deactivate();
				});

				registry.byId("zoomin2").on("click", function () {
					cancelDrawingSelection();
					navToolbar.activate(Navigation.ZOOM_IN);
					$("#map_layers").removeClass("pan zoomOut");
					$("#map_layers").addClass("zoomIn");
					$("#zoomout .dijitIcon, #pan .dijitIcon, #zoomout2 .dijitIcon, #pan2 .dijitIcon").removeClass("active");
					$("#zoomin .dijitIcon, #zoomin2 .dijitIcon").addClass("active");
				});

				registry.byId("zoomout2").on("click", function () {
					cancelDrawingSelection();
					navToolbar.activate(Navigation.ZOOM_OUT);
					$("#map_layers").removeClass("pan zoomIn");
					$("#map_layers").addClass("zoomOut");
					$("#zoomin .dijitIcon, #pan .dijitIcon, #zoomin2 .dijitIcon, #pan2 .dijitIcon").removeClass("active");
					$("#zoomout .dijitIcon, #zoomout2 .dijitIcon").addClass("active");
				});
				
				registry.byId("zoomfullext2").on("click", function () {
					//navToolbar.zoomToFullExtent();
					cancelDrawingSelection();
			map.setExtent(imageryLayer.fullExtent)	
			});
			registry.byId("pan2").on("click", function () {
				cancelDrawingSelection();
				navToolbar.activate(Navigation.PAN);
				$("#map_layers").removeClass("zoomIn zoomOut");
				$("#map_layers").addClass("pan");
				$("#zoomin .dijitIcon, #zoomout .dijitIcon, #zoomin2 .dijitIcon, #zoomout2 .dijitIcon").removeClass("active");
				$("#pan .dijitIcon, #pan2 .dijitIcon").addClass("active");
			});
	
			function extentHistoryChangeHandler () {
				registry.byId("zoomprev").disabled = navToolbar.isFirstExtent();
				registry.byId("zoomnext").disabled = navToolbar.isLastExtent();
			}
			
			// Replace nav toolbar icons
			$("#zoomin .dijitIcon").removeClass("dijitReset").removeClass("zoominIcon").addClass("fa fa-search-plus");
			$("#zoomout .dijitIcon").removeClass("dijitReset").removeClass("zoomoutIcon").addClass("fa fa-search-minus");
			$("#zoomfullext .dijitIcon").removeClass("dijitReset").removeClass("zoomfullextIcon").addClass("fa fa-arrows-alt");
			$("#zoomprev .dijitIcon").removeClass("dijitReset").removeClass("zoomprevIcon").addClass("fa fa-arrow-left");
			$("#zoomnext .dijitIcon").removeClass("dijitReset").removeClass("zoomnextIcon").addClass("fa fa-arrow-right");
			$("#pan .dijitIcon").removeClass("dijitReset").removeClass("panIcon").addClass("fa fa-hand-stop-o");
			
			$("#zoomin2 .dijitIcon").removeClass("dijitReset").removeClass("zoominIcon").addClass("fa fa-search-plus");
			$("#zoomout2 .dijitIcon").removeClass("dijitReset").removeClass("zoomoutIcon").addClass("fa fa-search-minus");
			$("#zoomfullext2 .dijitIcon").removeClass("dijitReset").removeClass("zoomfullextIcon").addClass("fa fa-arrows-alt");
			$("#pan2 .dijitIcon").removeClass("dijitReset").removeClass("panIcon").addClass("fa fa-hand-stop-o");

			$( "#navToolbar" ).draggable({ handle: "#navToolbarHandle", containment: $("#map") });
			$( "#featureEditToolsOptionsBox" ).draggable({ handle: "#featureEditToolsOptionsHandle", containment: $("#map") });
			$( "#featureEditOptionsBox" ).draggable({ handle: "#featureEditOptionsHandle", containment: $("#map") });
			$( "#ninetyDegreeOptionsBox" ).draggable({ handle: "#ninetyDegreeOptionsHandle", containment: $("#map") });
			
			$( "#multiSelectDialogBox" ).draggable({ handle: "#multiSelectHandle", containment: $("#map") });
			$( "#bulkimport-dialog-box" ).draggable({ handle: "#bulkimport-handle", containment: $("#map") });
			$( "#septicLateralsOptionsBox" ).draggable({ handle: "#septicLateralsOptionsHandle", containment: $("#map") });
		}//  end function createNavToolbar()
		
		// MAP LAYERS
		imageryLayer = new ArcGISTiledMapServiceLayer("https://app.fetchgis.com/geoservices/rest/services/bayImagery2020/MapServer" , { // default imagery layer
			id: "imageryLayer",
			maxZoom: 21,
			visibleAtMapScale: true
		});
			dojo.connect(imageryLayer, "onLoad", function(){
				if(params.switchingMaps == "true" || params.switchingMaps == undefined){
					map.setExtent(imageryLayer.fullExtent, true);
				}
			});
			switchingMaps = 'false';
	/*
	dojo.connect(map, "onZoomStart", function(extent, zoomFactor, anchorz, level){
		//console.log("zoomStart: " + level);
		// just in case we need to do anything on zoom
	});
	dojo.connect(map, "onZoomEnd", function(extent, zoomFactor, anchorz, level){
		// also just in case we need to do something here.
	});
	*/
	
	
		
	geoLocationLayer = new GraphicsLayer({ 		id: "geoLocationLayer"
	});

	highlightLayer = new GraphicsLayer({ 		id: "highlightLayer",
		opacity: .8,
		stroke: 3
	});

	userGraphicsLayer = new GraphicsLayer({ 		id: "userGraphicsLayer",
		opacity: .9,
		stroke: 3
	});
	
	offlineTileDLTempGfxLayer = new GraphicsLayer({ 		id: "offlineTileDLTempGfxLayer",
		opacity: .9,
		stroke: 3
	});
	
    selectParGraphicsLayer = new GraphicsLayer({         id: "selectParGraphicsLayer",
        opacity: .9,
        stroke: 3
    });
	
	measurementLayer = new GraphicsLayer({ 		id: "measurementLayer",
		opacity: .9,
		stroke: 3
	});
	
	

    var TemplateCreator = function(options){
        this.setOptions(options);
    }
    
    TemplateCreator.DEFAULTS = {
        strictMatching      : {},
        looseMatching       : {},
        onStart             : null,
        onFinishPopup       : null,
        customLinks         : null,
        fieldInfos          : null,
        contentOverride     : null,
        titleOverride       : null,
        subTypes            : false,
        titleAttribute      : null,
        selectable          : false,
        exclude             : [],
        layerFieldOrder     : [],
    }
    
    TemplateCreator.TemplateContents = function(){
            this.orderedTableHtmlStore = [];
            this.orderedArray = null;
            this.headHtml = "";
            this.tableHtml = "";
            this.linksHtml = ""; // links in the table go on bottom
            this.extrasHtml = ""; // not in the table 
            this.fieldName = null, this.value = null, this.feature = null; // feature and attribute values
    }
    
    TemplateCreator.prototype = {
        
        setOptions: function(options){
            this.options = $.extend({},TemplateCreator.DEFAULTS,options);
            //this.options = Object.assign({}, TemplateCreator.DEFAULTS, options);
        },
        
        buildTemplate: function(){
            var context = this;
            var newInfoTemplate = new InfoTemplate();
            var titleFn = typeof(this.options.titleOverride) == 'function' ?  this.options.titleOverride : function(){ return context._getTitleFunction(); }; 
            var contentFn = typeof(this.options.contentOverride) == 'function' ?  this.options.contentOverride : function(){ return context._getContentFunction(); }; 
            
            newInfoTemplate.setTitle(titleFn);
            newInfoTemplate.setContent(contentFn);
            return newInfoTemplate;
        },
        
        _getTitleFunction: function(){
            try{
                var feature = popup.getSelectedFeature();
                if(this.options.titleAttribute != null && typeof(feature.attributes[this.options.titleAttribute]) != 'undefined')
                    return feature.attributes[this.options.titleAttribute];
                return feature._layer.name;
                
            } catch(err){
                console.log('tile error: ' + err.message);
            }
        },
        
        _getContentFunction: function(){
            var context = this;
            
            var options = this.options;
            // generate a table driven by layer infos with Friendly Field Labels mapped to dojo field-name selectors
            var contentHtml = "<table id='popupTable'><tbody>"; // main content
            
            var templateContents = new TemplateCreator.TemplateContents(); // anything stored in this object is available to the input functions passed in the setOptions method of this prototype
     
            var strictMatching = options.strictMatching;
            var looseMatching = options.looseMatching;


            /// helper functions
            function getFunc(field){
                if(typeof(strictMatching[field]) == 'function')
                    return strictMatching[field];
                
                for(var partial in looseMatching){
                    if(field.toLowerCase().indexOf(partial.toLowerCase()) != -1)
                         return looseMatching[partial];
                }
                return false;
            }

            function getFieldType(fieldName){ // gets the esri field type for input field
                if(typeof(templateContents.feature._layer.fields)!="undefined"){//cuz feature layer would be required
                    var fields = templateContents.feature._layer.fields;
                    for(var i=0; i< fields.length; i++){
                        var field = fields[i];
                        if(field.name == fieldName){
                            return field.type;
                        }
                    }
                }
                return false;
            }

            function getSubType(fieldVal){ // looks through the types specified by the feature layer and returns the coded value
                for(var i=0; i< templateContents.feature._layer.types.length; i++){
                    var type = templateContents.feature._layer.types[i];
                    if(type.id == fieldVal){
                        if(typeof type.name == "string") return type.name.replace("; Other", ""); // ****this is just for benches in dow gardens right not
                        return type.name;
                    }
                }
                return false;
            }

            function getCodedValue(fieldVal){
                if(typeof(templateContents.feature._layer.fields)!="undefined"){//cuz feature layer would be required
                    for(var i=0; i<templateContents.feature._layer.fields.length; i++){
                        var fieldInfo = templateContents.feature._layer.fields[i];
                        if(fieldInfo.domain && fieldInfo.domain.codedValues && (templateContents.fieldName == fieldInfo.name || templateContents.fieldName == fieldInfo.alias)){
                            for(var j=0; j<fieldInfo.domain.codedValues.length; j++){
                                var codedValueInfo = fieldInfo.domain.codedValues[j];
                                if(codedValueInfo.code == fieldVal){
                                    return codedValueInfo.name;
                                }
                            }
                        }
                    }
                }

                return false;
            }
            
            try{
                if(popup.selectedIndex != -1){ // if popup has something assinged
                    templateContents.feature = popup.features[popup.selectedIndex]; // get selected feature

                    var layerInfo = [];
 
                    if(typeof(layerInfos4EH) != "undefined"){ // if layer infos present get alias values 

                        for (var i = 0; i < layerInfos4EH.length; i++){

                                var layerInfo = layerInfos4EH[i];

                                if(templateContents.feature._layer.id == layerInfo.featureLayer.id){

                                    if(layerInfo.fieldInfos){
                                        layerInfo = layerInfo.fieldInfos;
                                    }
                                    break;
                                }
                        }
                    }
                    
                    if(typeof(options.onStart) == 'function')
                        options.onStart.call(null, templateContents);
                    
                    templateContents.orderedArray = options.layerFieldOrder[templateContents.feature._layer.id] || null;
                    if(templateContents.orderedArray) templateContents.orderedTableHtmlStore.length = templateContents.orderedArray.length;
                    // get list of attributes that have timestamps
                    var tsArray = [];
    
                    if(typeof(templateContents.feature._layer.fields) !== "undefined"){
                        for(var i = 0; i < templateContents.feature._layer.fields.length; i++){
                            if(templateContents.feature._layer.fields[i].type === "esriFieldTypeDate"){
                                tsArray.push(templateContents.feature._layer.fields[i].name);
                            }
                        }
                    }
                    
                    for(var key in templateContents.feature.attributes){// loop through associative array and get the key names and values, and build a string to make the html to go inside the popup
                        templateContents.defaultFieldName = templateContents.fieldName = key;
                        templateContents.value = templateContents.feature.attributes[key];

                        var isTimestamp = false;
                        if(templateContents.fieldName.search('OBJECTID') == -1 && templateContents.fieldName.search('Shape__Length') == -1 && (templateContents.fieldName.search('Shape__Area') == -1 || templateContents.feature._layer.id =='directContactLayer') && templateContents.fieldName != "OID" && templateContents.fieldName != "GlobalID"){
                            // find if there is an alias provided by the feature service. If no alias, skip.
                            // while we are doing that, might as well also check to see if the field is a date field (timestamp to convert)
                            try{
                                if(typeof(templateContents.feature._layer.fields) !== "undefined"){
                                    var aliasFound=false;
                                    for(var j = 0; j < templateContents.feature._layer.fields.length; j++){
                                        for(var k = 0; k < tsArray.length; k++){
                                            if(tsArray[k] === templateContents.fieldName){
                                                isTimestamp = true;
                                            }
                                        }
                                        if(templateContents.feature._layer.fields[j].name === templateContents.fieldName){
                                            templateContents.fieldName = templateContents.feature._layer.fields[j].alias;
                                            aliasFound=true;
                                        }
                                    }
                                    if(aliasFound===false) continue; //skip this field 
                                }
                            } catch(err){
                                console.log("Error getting feature attribute alias names: " + err.message)
                            }
                            
                            // if value is null, replace with blank space
                            var dateConversion = "";
                            if(templateContents.value === null){
                                templateContents.value = " ";
                            } else { // who cares if it's a timestamp if it's null
                                if(isTimestamp && templateContents.value){
                                    dateConversion = new Date(parseInt(templateContents.value));
                                  //  templateContents.value = dateConversion.toDateString(); // dont convert here.....need full value to go through
                                }
                            }
                            
                            var orderedIndex = templateContents.orderedArray ? templateContents.orderedArray.indexOf(templateContents.fieldName) : -1;
                            
                            var fn = getFunc(templateContents.fieldName);
                            if(typeof(fn) == 'function'){
                                fn.call(null, templateContents,orderedIndex);
                            } else {
  
                                var fieldName = templateContents.fieldName;
                                var fieldVal =  templateContents.value;

                                if(options.fieldInfos != null && !options.fieldInfos[templateContents.defaultFieldName] || options.exclude.indexOf(templateContents.fieldName) != -1) // skip if field infos present and the field name is not in it
                                    continue;
                                if(options.fieldInfos != null)
                                    fieldName = options.fieldInfos[templateContents.defaultFieldName]; // get specified display name

                                for(var i=0; i<layerInfo.length; i++){ // check layerInfos4EH array for label
                                    var fieldInfo = layerInfo[i];
                                    if(fieldInfo["fieldName"] == templateContents.fieldName || (fieldInfo["alias"] && fieldInfo["alias"] == templateContents.fieldName)) 
                                        if(fieldInfo["label"]) fieldName = fieldInfo["label"];
                                }

                                

                                if(options.subTypes == true){
                                    if (templateContents.feature._layer.typeIdField && templateContents.fieldName == templateContents.feature._layer.typeIdField){ // domains
                                        var subType = getSubType(templateContents.value);
                                        if(subType) fieldVal = subType;
                                    }
                                }

                                var codedValue = getCodedValue(templateContents.value);
                                if(codedValue) fieldVal = codedValue;
                                
                                // convert to readable date
								if((fieldVal !== null && typeof(fieldVal) !== undefined && ((typeof fieldVal == "string" && fieldVal.trim() != "") || typeof fieldVal =="number")) && getFieldType(templateContents.defaultFieldName) === "esriFieldTypeDate"){
                                    fieldVal = locale.format(new Date(fieldVal), {
										selector: "date",
										datePattern: "MMMM d, y"
                                    });
								}     

                                if(fieldVal && fieldVal.toString().slice(0,4) == "http"){ //turn values that look like links into links
                                    fieldVal = `<a href="${fieldVal}" target="_blank">${fieldVal}</a>`;
                                }

                                var selectable = options.selectable === true ? "selectable" : ""; // handle selectable option

                                var tr = "<tr>" + 
                                                    "<th class='"+selectable+"'>" + fieldName + "</th>" +
                                                    "<td class='"+selectable+"'> " + fieldVal + "</td>" +
                                                "</tr>";
                                                
                                if(orderedIndex != -1)
                                    templateContents.orderedTableHtmlStore[orderedIndex] = tr;
                                else
                                    templateContents.tableHtml += tr;
                            }
                        }
                    }
                }
            } catch(err) {
                console.log('Content error: ' + err.message);
            }
            
            if(typeof(options.customLinks) == 'function')
                options.customLinks.call(null, templateContents);
            
            contentHtml += templateContents.orderedTableHtmlStore.join(" ");
            contentHtml += templateContents.headHtml;
            contentHtml += templateContents.tableHtml;
            contentHtml += templateContents.linksHtml;
            contentHtml += "</tbody></table>";
            contentHtml += templateContents.extrasHtml;
            
            if(typeof(options.onFinishPopup) == 'function'){
                this._setOnFinish(function(){
                    options.onFinishPopup.call(null,templateContents);
                });
            }
            return contentHtml;
        },
        
        _setOnFinish: function(callback){
            var interval = setInterval(function(){
                if($('#popupTable').length == 0) // if popup table is present in the dom
                    return;
                clearTimeout(timeout);
                clearInterval(interval);
                callback();
            },1000);
            var timeout = setTimeout(function(){
                clearTimeout(timeout);
                clearInterval(interval);
            },8000);
        },
    }
    	
	var setCountySwitcherContent = function(feature){
		var url = feature.attributes.URL;
		
		if(isMobile === true && url.indexOf("www.fetchgis.com") !== -1){
			return '<p style="white-space: initial">This viewer does not yet support mobile devices.</p>';
		} else {
			return '<div style="width: 100%; height: 100%; text-align: center; font-size: 10pt">' +
												'<a href="' + url + '" style="position: relative; top: 8px; color: #fff; text-decoration: none">Load this map <span class="fa fa-arrow-circle-right fa-lg" style="color: rgb(27, 160, 221)"></span>' +
											'</div>';
		}
	}

	countySwitcherTC = new TemplateCreator();
	countySwitcherTC.setOptions({
		titleAttribute  : "mapTitle",
		contentOverride : setCountySwitcherContent,
	});
	countySwitcherTemplate = countySwitcherTC.buildTemplate(); // infotemplate for county switching layer

	/// set county switching layer
	countySwitchingLayer = new FeatureLayer('https://app.fetchgis.com/geoservices/fgis/CountyOutlines/FeatureServer/0',{
		id: "countySwitchingLayer",
		outFields: ["*"],
        mode: FeatureLayer.MODE_ONDEMAND,
        infoTemplate: countySwitcherTemplate,
		//maxScale: 30000,
		visible: false, // set to false and enabled in config files for viewers that desire this functionality
	});
	countySwitchingLayer.setDefinitionExpression('"mapName" <> \'' + currentMap + '\''); // exclude current viewer from county overlay layer
	map.addLayer(countySwitchingLayer);
	
	countySwitchingLayer.on("mouse-over", function(evt){ // mouse event handlers for highlighting the hovered county boundary
		var newWidth = parseInt($(evt.target).attr('stroke-width')) + 2;
		$(evt.target).attr('stroke-width', newWidth);
	});
	countySwitchingLayer.on("mouse-out", function(evt){
		var newWidth = parseInt($(evt.target).attr('stroke-width')) - 2;
		$(evt.target).attr('stroke-width', newWidth);
	});
	
	
	var msQueryTask = new QueryTask("https://app.fetchgis.com/geoservices/fgis/CountyOutlines/FeatureServer/0");
	var msQuery = new query();
	msQuery.outSpatialReference = new SpatialReference({ wkid:102100 });
	msQuery.where = "1=1";
	msQuery.outFields = ['*'];
	msQuery.returnGeometry = false;
	msQueryTask.execute(msQuery, function(results){
		var countySwitcherHTML = "";
		for(var i = 0; i < results.features.length; i++){
			var feeStr = "";
			var attr = results.features[i].attributes;
			if(attr.FEE === "true"){
				feeStr = " (Fee Service)";
			}
			if(attr.fetchGIS === '2'){
				var urlStr = window.location.pathname + attr.URL
				countySwitcherHTML += '<div id="' + attr.mapName + 'MapSelect" class="mapSwitcherDialogText"><a href="' + urlStr + '" target="_blank">' + attr.mapTitle + feeStr + '</a></div>';
			} else {
				countySwitcherHTML += '<div id="' + attr.mapName + 'MapSelect" class="mapSwitcherDialogText"><a href="' + attr.URL + '" target="_blank">' + attr.mapTitle + feeStr + '</a></div>';
			}
		}
		$('#mapSwitcherMainHeading').after(countySwitcherHTML);
	});

	
		
	var getWildcardTitle = function(){
		try{
			found = false;
			var selectedFeature = popup.getSelectedFeature();
			if(popup.selectedIndex != -1){
				return selectedFeature._layer.name;
			}
		}catch(err){
			console.log('tile error: ' + err.message);
		}
	}/*
	var getWildcardContent = function(feature){
		// generate a table driven by layer infos with Friendly Field Labels mapped to dojo field-name selectors
		var contentStr = "<table id='popupTable'><tbody>";
		
		try{
			if(popup.selectedIndex != -1){ // if popup has something assinged
				var selectedFeature = popup.features[popup.selectedIndex]; // get selected feature
				
				// get list of attributes that have timestamps
				var tsArray = [];

				if(typeof(selectedFeature._layer.fields) !== "undefined"){
					for(var i = 0; i < selectedFeature._layer.fields.length; i++){
						if(selectedFeature._layer.fields[i].type === "esriFieldTypeDate"){
							tsArray.push(selectedFeature._layer.fields[i].name);
						}
					}
				}
				
				for(var key in popup.features[popup.selectedIndex].attributes){// loop through associative array and get the key names and values, and build a string to make the html to go inside the popup
									
					var keyValPair = [key, popup.features[popup.selectedIndex].attributes[key]];
					var isTimestamp = false;
					if(keyValPair[0].search('OBJECTID') == -1 && keyValPair[0].search('Shape__Length') == -1 && keyValPair[0].search('Shape__Area') == -1 && keyValPair[0] != "OID" && keyValPair[0] != "GlobalID"){
						// find if there is an alias provided by the feature service. If no alias, just use the table name.
						// while we are doing that, might as well also check to see if the field is a date field (timestamp to convert)
						try{
							if(typeof(selectedFeature._layer.fields) !== "undefined"){
								for(var j = 0; j < selectedFeature._layer.fields.length; j++){
									for(var k = 0; k < tsArray.length; k++){
										if(tsArray[k] === keyValPair[0]){
											isTimestamp = true;
										}
									}
									if(selectedFeature._layer.fields[j].name === keyValPair[0]){
										keyValPair[0] = selectedFeature._layer.fields[j].alias;
									}
								}
							}
						} catch(err){
							console.log("Error getting feature attribute alias names: " + err.message)
						}
						
						// if value is null, replace with blank space
						var dateConversion = "";
						if(keyValPair[1] === null){
							keyValPair[1] = " ";
						} else { // who cares if it's a timestamp if it's null
							if(isTimestamp){
								dateConversion = new Date(parseInt(keyValPair[1]));
								keyValPair[1] = dateConversion.toDateString();
							}
						}
						
						contentStr += 	"<tr>" + 
											"<th>" + keyValPair[0] + "</th>" +
											"<td> " + keyValPair[1] + "</td>" +
										"</tr>"; 
					}
				}
			}
		}catch(err){
			console.log('Content error: ' + err.message);
		}
		contentStr += "</tbody></table>";
		//console.log(contentStr);
		//ehInfoTemplate.setContent(contentStr);
		return contentStr;
	}*/
	
	
	var wildcardTemplateCreator = new TemplateCreator(); // no options necessary
	wildcardInfoTemplate = wildcardTemplateCreator.buildTemplate();



	
	
		
		
		var buildCustomLinks = function(templateContents){
			// add link for bcats
			templateContents.linksHtml += "<tr>"+
							"<th>Information</th>" +
							"<td><a target=\"_blank\" href=\"http://www.baycounty-mi.gov/Transportation/FY2014151617TransportationImprovementProgramTIP.aspx\">Transportation Improvement Program (TIP)</a></td>"+
						  "</tr>";
		}
		
		var bcatsOptions = {
			customLinks : buildCustomLinks,
			fieldInfos: {
				"Year" 			: "Year",
				"FULLNAME_1"	: "Road Name",
				"Agency"		: "Agency",
				"type"			: "Type",
				"Status"		: "Status",
			},
		}
		var bcatsTemplateCreator = new TemplateCreator();
		bcatsTemplateCreator.setOptions(bcatsOptions);
		window.bcatsInfoTemplate = bcatsTemplateCreator.buildTemplate();
		
		
		roadProjectsTC = new TemplateCreator();
		roadProjectsTC.setOptions({
			fieldInfos: {
				"ProjectNam" 	: "Project Name",
				"ProjectLim"	: "Project Limits",
				"Agency"		: "Agency",
				"Constructi"	: "Construction Cost",
				"Start"			: "Start Date",
				"Completion"	: "Completion",
				"WorkType1"		: "Work Type",
				"TrafficInf"	: "Traffic Info",
			},
		});
		roadProjectsTemplate = roadProjectsTC.buildTemplate();
		
		
		
	
	 // default template
				defaultParcelTemplate = new InfoTemplate();
				defaultParcelTemplate.setTitle(getDefaultParcelTitle);
				defaultParcelTemplate.setContent(getDefaultParcelContent);
			
	function checkForLRP(layerId){
		var lrpStr;
		if(lrpStr = parcelLayers[layerId].lrpMapStr){ // assign if present
			if(lrpStr.trim() != "" && lrpStr.toLowerCase() != "none")
				return true;
		}
		return false;
	}
	
	// Parcel symbol
	var parcelRenderer = new SimpleFillSymbol(
	  SimpleFillSymbol.STYLE_SOLID, 
	  new SimpleLineSymbol(
		SimpleLineSymbol.STYLE_SOLID, 
		new Color([0,0,0,1]), 
		1
	  ),
	  new Color([0,0,0,0])
	);
	// Parcel highlight symbol for mouse over/selection
	var highlightSymbol = new SimpleFillSymbol(
	  SimpleFillSymbol.STYLE_SOLID, 
	  new SimpleLineSymbol(
		SimpleLineSymbol.STYLE_SOLID, 
		new Color([255,0,0]), 3
	  ), 
	  new Color([255,0,0,0.1])
	);
	

        
    //map layer symbol for selected parcels/geometries before buffer runs
    var highlightSelectPar = new SimpleFillSymbol(
	  SimpleFillSymbol.STYLE_SOLID, 
	  new SimpleLineSymbol(
		SimpleLineSymbol.STYLE_SOLID, 
		new Color([0,255,0]), 3
	  ), 
	  new Color([0,255,0,0])
	);
	
	parcelLayers = {};
	
	parcelLayers["bayParcelLayer"] = {}
				parcelLayers["bayParcelLayer"].id = "bayParcelLayer";
		
				parcelLayers["bayParcelLayer"].url = "https://app.fetchgis.com/geoservices/fgis/bayParcels/FeatureServer/0";
		
				parcelLayers["bayParcelLayer"].pinField = "PARCELID";
		
				parcelLayers["bayParcelLayer"].mouseOverField = "PARCELID";
		
				parcelLayers["bayParcelLayer"].visibility = true;
		
				parcelLayers["bayParcelLayer"].infoTemplate = defaultParcelTemplate;
		
				parcelLayers["bayParcelLayer"].outfields = ["OBJECTID", "PARCELID"];
		
				parcelLayers["bayParcelLayer"].lrpMapStr = "bay";
			
	// generate parcel layer objects and add to map
	for(var layer in parcelLayers){ // for "key" in object loop
		var outfields = parcelLayers[layer].outfields; // for whatever reason, this needs to be assigned into a variable vs direct access
		var template = parcelLayers[layer].infoTemplate;
		
		window[layer] = new FeatureLayer(parcelLayers[layer].url, {
			id: parcelLayers[layer].id,
			className: "parcelLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_ONDEMAND,
			visible: parcelLayers[layer].visibility,
			outFields: outfields,
			infoTemplate: template
		});
		if(parcelLayers[layer].minScale){
			window[layer].setMinScale(parcelLayers[layer].minScale);
		}
	}
	
	turnOffParcelPopup = function(){
		for(layer in parcelLayers){
			window[layer].setInfoTemplate(null);
			try {
				window[layer].disableMouseEvents();
			} catch (e) {

			}
		}
	}
	
	turnOnParcelPopup = function(){
		for(layer in parcelLayers){
			window[layer].setInfoTemplate(parcelLayers[layer].infoTemplate);
			try {
				window[layer].enableMouseEvents();
			} catch (e) {

			}
		}
	}
	
	// Begin xhr to grab popup data
	getPopupData = function getPopupData(graphic, pinStr){
		var pinFieldName = parcelLayers[graphic._layer.id].pinField;
		var mapStr = parcelLayers[graphic._layer.id].lrpMapStr;
		
		var parcelHasLRP = checkForLRP(graphic._layer.id);
		// Add a spinner here		
		if (parcelHasLRP){
			var dashBool, pin; // dashBool determines whether PSsearch 2 
            for(var i = 0; i < pinStr.length; i++){
				if(pinStr.indexOf("-") > -1){
					pin = pinStr.split("-");
					dashBool = true;
				} else if(pinStr.indexOf(" ") > -1){
					pin = pinStr.split(" ");
					dashBool = false;
				}else{
                    pin=[pinStr];
                }
            }
			
			if(!pin) {

					return 'No Data Returned';
			}
			
			var url = 'ws/PSsearch2.php?';//Pin1='+ pin[0] +'&Pin2='+ pin[1] +'&Pin3='+ pin[2] +'&Pin4='+ pin[3] +'&Pin5='+ pin[4] + '&Map='+ selectedCounty + '&dash=' + dashBool + '&recLimit=1';
			for(var i = 0; i < pin.length; i++){
				url += 'Pin' + (i+1) + '=' + pin[i] + '&';
			}
			url += 'Map='+ mapStr + '&dash=' + dashBool + '&recLimit=1';
			
            xhr.onreadystatechange = XHRhandler;
            xhr.open("GET", url, true);
            xhr.send(null);

			getParcelData(pinStr, graphic._layer.id);
			
        } else {
           // console.log('in useLRP test');
            var queryTask = new QueryTask(parcelLayers[graphic._layer.id].lrpMapStr);
            var parQuery = new query();
			parQuery.outSpatialReference = new SpatialReference({ wkid:102100 });
            parQuery.where = pinFieldName + " = '"+ pinStr +"'";
            parQuery.outFields = ['*'];
            queryTask.execute(parQuery, function(results){
                var attributes = results.features[0].attributes;
                $('.esriPopupWrapper > .content > .contentPane').html(
                                    );
            });
        }
		
	};

	// handle response
	XHRhandler = function XHRhandler() {// popup
		
		if (xhr.readyState == 4) {
			if(xhr.responseXML == null){
				$('.esriPopupWrapper > .content > .contentPane').html(
					'<div style="text-align: center">No info</div>'
				);


			

			} else {

			
				popupData = XML2jsobj(xhr.responseXML.documentElement);
				if(popupData.record.OwnerName != undefined){
				   let selFeat = popup.getSelectedFeature();
                   if(selFeat._layer.id.indexOf('ParcelLayer') !== -1){ // more paranoia

						var popupName = popupData.record.OwnerName; // extreme paranoia
						var popupAddr = popupData.record.PropertyAddress;

						if($.isEmptyObject(popupName)){
							popupName = "";
						}

						if($.isEmptyObject(popupAddr) || popupAddr === "  , "){
							popupAddr = "";
						}

                        $('.esriPopupWrapper > .content > .contentPane').html(
                            popupName + '<br>' +
                            popupAddr 														                        );
						
												var popupImageHTML = 'https://app.fetchgis.com/linkedDocs/bay/fetchPhoto.php?pin=' + popupData.record.ParcelNumber;
												$(".esriPopupWrapper > .content > .contentPane").append('<br><br><img id="popupImage" src="' + popupImageHTML + '")>');
																										
												$( "#popupImage" ).on("error", function() {
													//console.log("Handler for .error() called.");
													$(this).remove();
												});
										                    }
				} else {
					$('.esriPopupWrapper > .content > .contentPane').html(
						'<div style="text-align: center">No info</div>'
					);
				}	
			}
	
		}
	};
	// End xhr to grab popup data

    //function to write and save CSV file
    $('#saveCSV').on("click", function(){
		saveCSV();
    });

	function saveCSV(transId){
		
				var csvContentArray = [];
				var csvHeader = "PIN,Name,Property Street Address,Property City,Property State,Property Zipcode,Owner Street Address,Owner City,Owner State,Owner Zipcode";
				for (var i=0; i < buffData.length; i++){
					var row = buffData[i].ParcelNumber + "<>" +
							buffData[i].OwnerName1 + "<>" +
							buffData[i].PropAddressCombined + "<>" +
							buffData[i].PropAddressCity + "<>" +
							buffData[i].PropAddressState + "<>" +
							buffData[i].PropAddressZip + "<>" +
							buffData[i].OwnerStreetAddress + "<>" +
							buffData[i].OwnerCity + "<>" +
							buffData[i].OwnerState + "<>" +
							buffData[i].OwnerZip;
					//reaplce all commas in each data string
					//repalce junk returned data values with NULL
					//replace $ with comma to create consistent CSV
					row = row.replace(/,/g,"").replace(/\[object Object\]/g, "Null").replace(/<>/g, ",").replace(/#/g, " ");

					csvContentArray.push(row);  
				}
				
				if(navigator.msSaveBlob){
					var blob = new Blob([ csvHeader + "\n" + csvContentArray.join("\n")],{type: "text/csv;charset=utf-8;"});
					navigator.msSaveBlob(blob, "ParcelBufferResultsList.csv")
				} else {
					csvContent = "data:text/csv;charset=utf-8," + csvHeader + "\n" ;
					var encodedURI= csvContent + encodeURIComponent(csvContentArray.join("\n"));//better encoding for special chars
					var CSVlinkStr = '<a id="downloadCSV" download="ParcelBufferResultsList.csv" href="' + encodedURI + '">here</a>';
					showModal("Success", "Your CSV is ready.<br>Click " + CSVlinkStr + " to download");
				}
				}
	
    //function to populate buffer results table with buffer results
    var requestPins = [];
    //var buffData = []; moved to global var, which is cleared as soon as you hit the run buffer button
    function fetchBuffResultsData(buffResults, layerId){ //set temp to parQuery for testing

		if(!parcelLayers[layerId].lrpMapStr){ //use buffresults instead of making another call
			let arr = [];
			for (i = 0; i < buffResults.length; i++){ //loop buffered PINs and push into array
				let obj = {
					OwnerName1 : buffResults[i].attributes[parcelLayers[buffResults[i]._layer.id].parcelOwnerField],
					ParcelNumber : buffResults[i].attributes[parcelLayers[buffResults[i]._layer.id].pinField],
					OwnerCity : buffResults[i].attributes[parcelLayers[buffResults[i]._layer.id].ownerCityField],
					OwnerState : buffResults[i].attributes[parcelLayers[buffResults[i]._layer.id].ownerStateField],
					OwnerStreetAddress : buffResults[i].attributes[parcelLayers[buffResults[i]._layer.id].ownerStreetField],
					OwnerZip : buffResults[i].attributes[parcelLayers[buffResults[i]._layer.id].ownerZipCodeField]
				};
				if(parcelLayers[buffResults[i]._layer.id].ownerCSZField && buffResults[i].attributes[parcelLayers[buffResults[i]._layer.id].ownerCSZField]){
					if(buffResults[i].attributes[parcelLayers[buffResults[i]._layer.id].ownerCSZField].indexOf(',') > -1){
						let t = buffResults[i].attributes[parcelLayers[buffResults[i]._layer.id].ownerCSZField].split(',');
						obj.OwnerCity = t[0];
						obj.OwnerState = t[1].split(' ')[1];
						obj.OwnerZip = t[1].split(' ')[2];
					} else {
						let t = buffResults[i].attributes[parcelLayers[buffResults[i]._layer.id].ownerCSZField].split(' ');
						obj.OwnerCity = t[0];
						obj.OwnerState = t[1];
						obj.OwnerZip = t[2];
					}
				}
				arr.push(obj)
			}
			//hide spinner, move back to original position
			$('#buffSpinner').css('top', '-=150px').addClass('hidden');
			buffData = buffData.concat(arr);
			setBufferResultsCount();
			populateBufferTable();
		} else {
			for (i = 0; i < buffResults.length; i++){ //loop buffered PINs and push into array
				var PIN = buffResults[i].attributes[parcelLayers[buffResults[i]._layer.id].pinField];
				//buffRequests[i] = new requestBuff(PIN);
				requestPins.push(PIN);
			}
	
			if(requestPins.length !== 0){
				requestBuff(requestPins.toString(), parcelLayers[layerId].lrpMapStr); //send pins to function to request data from server
			}	
		}
        
        requestPins = []; //clear pins array
    }

    function populateBufferTable(){
        $('#bufferResultsTable').find('tr').remove();
        for (i = 0; i < buffData.length; i++){
            var ownerAddress = buffData[i].OwnerName1 + '<br>' + 
                buffData[i].OwnerStreetAddress + '<br>' +
                buffData[i].OwnerCity + ', ' +
                buffData[i].OwnerState + ' ' +
                buffData[i].OwnerZip;
            $("#bufferResultsTable").find('tbody').append('<tr><td class="recordData"><a href="#" onclick="parcelContent(\'' + buffData[i].ParcelNumber + '\')">' + buffData[i].ParcelNumber + '<br>' + ownerAddress + '</a></td></tr>');
        }
        $("#bufferResultsScroll").perfectScrollbar();
		$('#bufferControlsDiv').stop().animate({height: '1px'}, 400, function(){
				$('#bufferResultsDiv, #saveButtonsDiv').removeClass('hidden');
				$("#bufferOutMessage").html('');
				$("#bufferResultsTable").removeClass('hidden');
				$("#bufferResultsScroll").perfectScrollbar('update');
			}
		);

    }

    //fucntion to request parcel data 
    function requestBuff(pin, mapStr){
        //show spinner, move down to center in buffer div
		$('#buffSpinner').removeClass('hidden').css('top', '+=150px');
		var xhr4Buff = (window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP"));
		var url = "ws/getAddFromPINS.php";
		
		// AJAX request
		xhr4Buff.open("POST", url, true);
		xhr4Buff.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		xhr4Buff.onreadystatechange = function() {
			if (xhr4Buff.readyState == 4 && xhr4Buff.status == 200){
				try{
					//hide spinner, move back to original position
					$('#buffSpinner').css('top', '-=150px').addClass('hidden');
					buffData = buffData.concat(JSON.parse(xhr4Buff.responseText));
					setBufferResultsCount();
					populateBufferTable();
				} catch (err) {
					console.log(err);
					//hide spinner, move back to original position
					$('#buffSpinner').css('top', '-=150px').addClass('hidden');
				}
			} else if (xhr4Buff.readyState == 4 && xhr4Buff.status != 200){
				// show error message if stuff breaks
				cancelBuffer();
				$('#buffSpinner').css('top', '-=150px').addClass('hidden');
				$('#bufferOutMessage').html('An error fetching parcel address data has occured. Please try again.').css('opacity', 1).animate({opacity: 1}, 10000,function(){
					$('#bufferOutMessage').animate({opacity: 0}, 2000);
				});
			}
		}
		xhr4Buff.send('Map='+ mapStr + '&pins='+encodeURIComponent(pin));
    }
	
    //function to display number of parcels selected to buffer 
    function setBufferSelectCount() {
		var word = "";
		if($('#bufferLayerParcel').hasClass('fa-dot-circle-o')){
			word = 'parcel';
		} else if($('#bufferLayerGraphic').hasClass('fa-dot-circle-o')){
			word = 'graphic';
		}
        if (bufferGeometrySelect.length == 1) {
			$('#runBuffer').addClass('active'); // if there is a graphic present for buffering enable run buffer button
            $(".bufferResultsCounter").show().html(bufferGeometrySelect.length + " " + word + " selected");    
        } else if (bufferGeometrySelect.length > 1) {
            $(".bufferResultsCounter").show().html(bufferGeometrySelect.length + " " + word + "s selected");                                  
        } else {
			$('#runBuffer').removeClass('active'); // if no graphic is present for buffering make runbuffer button unavailable
            $(".bufferResultsCounter").hide();    
        }
    }

    //function to display number of selected parcels resulting from buffer
    function setBufferResultsCount(){
        $(".bufferResultsCounter").show().html(buffData.length + " records returned");
    }
	
	if(isMobile === false){
		var popDialog = new TooltipDialog ({
			id: 'layerInfoHover',
			style: "position: relative; width: 140px; z-index:10000; opacity: .8; overflow: hidden;"
		});
		popDialog.startup();
	
		for(var layer in parcelLayers){ // for "key" in object loop
			window[layer].on("mouse-over", function(evt){
				var popupPinStr = parcelLayers[evt.graphic._layer.id].mouseOverField;
				// Check county layers for Tax Pin field name
				var pin = evt.graphic.attributes[popupPinStr];
				var content = "<span>" + pin;
				if(parcelLayers[evt.graphic._layer.id].infoTemplate != ''){ //if infotemplate, display click for more info in mouseover popup
					content += "<br>*Click for more info"
				}
				content += "</span>"
				popDialog.setContent(content);
				var highlightGraphic = new Graphic(evt.graphic.geometry,highlightSymbol);
				//highlightLayer.clear(highlightGraphic);
				if(pin == 'ROW' || swipeLayerActive){
					return false;
				} else {
					domStyle.set(popDialog.domNode, 'opacity', 0.85);
                    $(".dijitTooltipConnector").show();
					if(evt.screenPoint.y < viewportHeight/2){// is cursor on top of buttom half of screen
						dijitPopup.open({
							popup: popDialog,
							around: evt.graphic._shape.rawNode,
							orient: ['below-centered', 'above-centered']
						});
					} else {
						dijitPopup.open({
							popup: popDialog,
							around: evt.graphic._shape.rawNode,
							orient: ['above-centered', 'below-centered']
						});
					}
	
			
				}
			});
			window[layer].on("mouse-out", function(evt){
				//alert('over');
				dijitPopup.close(popDialog);
			});	
		}
	}
    
	/// These three event handlers are for making the run buffer button available or unavailable to the user
	$('#bufferToolsHeading, #drawButton').on("click", function(){ // event for making the run buffer function available if a parcel is already selected when opening buffering pane
		if(map.infoWindow.isShowing && $('#bufferToolsHeading').hasClass('ui-accordion-header-active') || $(this).prop('id') == 'bufferToolsHeading' && map.infoWindow.isShowing){
			if(map.infoWindow.getSelectedFeature()._layer.id != "countySwitchingLayer"){ // exclude the county overlay layer from evaluation
				$('#runBuffer').addClass('active');
			}
		}
	});
	
	map.infoWindow.on('show', function(evt){
		if(popup.isShowing && popup.getSelectedFeature() && popup.getSelectedFeature()._layer && popup.getSelectedFeature()._layer.id.indexOf('ParcelLayer') !== -1 && $('#bufferLayerParcel').hasClass('fa-dot-circle-o')){
			$('#runBuffer').addClass('active');
		}
	});
	map.infoWindow.on('hide', function(){ // event makes run buffer unavailable if infowindow has been closed
		if($('#bufferLayerParcel').hasClass('fa-dot-circle-o')){
				$('#runBuffer').removeClass('active');
		}
	});
	$('#bufferLayerParcel').on("click", function(){
		if(popup.isShowing && popup.getSelectedFeature() && popup.getSelectedFeature()._layer.id.indexOf('ParcelLayer') !== -1){
			$('#runBuffer').addClass('active');
		}
	});
	$('#bufferLayerGraphic').on("click", function(){
		$('#runBuffer').removeClass('active');
	});
	
    function collectFeatures(buffGeom) { //used to collect multiple parcels to buffer
       // if ($('#selectBuffer').hasClass('btn-danger') || !(evt)){
            var highlightParGraphic = new Graphic(buffGeom, highlightSelectPar);
            //test clicked parcel against array
            //array is empty at first, so first try will always fail and add parcel
            //following addditions and subtractions 
            for(var i = 0; i <= bufferGeometrySelect.length; i++){
                try{
                    if(bufferGeometrySelect[i].geometry != buffGeom){
                        bufferGeometrySelect.push(buffGeom);
                        selectParGraphicsLayer.add(highlightParGraphic);
                        setBufferSelectCount(bufferGeometrySelect);
                        break;
                    }
                } catch(err) {
					bufferGeometrySelect.push(buffGeom);
                    selectParGraphicsLayer.add(highlightParGraphic);
                    setBufferSelectCount(bufferGeometrySelect);
                    break;
                }
            }
			if(bufferGeometrySelect.length > 0 && $('#bufferSize').val() != ""){
				$('#runBuffer').addClass('active');
			} else {
				$('#runBuffer').removeClass('active');
			}
        //} 
    } //end of collectFeatures function
    
    //function to remove selected parcel from buffering array if clicked again
    function removeSelectedPar(evt) {
        bufferGeometrySelect.splice((bufferGeometrySelect.indexOf(evt.graphic.geometry)), 1);
        selectParGraphicsLayer.remove(evt.graphic);
        setBufferSelectCount(bufferGeometrySelect);
    }
	
    //Once user click 'select' in the buffer window and parcel radio button is checked, collect each feature that is clicked
    //on in the map. Once user is ready, click button again to run buffer

	
	var bufferSelectHandler = [];
    var parGraphicsLayerHandler;
    $('#selectBuffer').on("click", function(evt) {
		// This block takes care of the button
		// activate the button

		if($('#selectBuffer').hasClass('btn-primary')){
			infoWindowHide(); // cancels popups on parcels
			$('#selectBuffer').removeClass('btn-primary').addClass('btn-danger').html('Cancel');
            $('#bufferLayerSelect').addClass('noPointerEvents').css('opacity', 0.3); //gray out radio buttons when select is active
			if($('#bufferLayerGraphic').hasClass('fa-dot-circle-o')){
				enableGraphicHover(true);
			}
			
			if($('#bufferLayerParcel').hasClass('fa-dot-circle-o') && $('#selectBuffer').hasClass('btn-danger')) {
				//parcelLayer.infoTemplate = "";
				
				highlightLayer.hide();
				$('#layerInfoHover').hide();
				$('#map_layers').css('cursor', 'crosshair');
				
				/*bufferSelectHandler = dojo.connect(parcelLayer, "onClick", function(evt){
					collectFeatures(evt.graphic.geometry);
				});*/
				for(layer in parcelLayers){
					bufferSelectHandler.push(dojo.connect(window[layer], "onClick", function(evt){
						collectFeatures(evt.graphic.geometry);
					}));
				}
				
				
				parGraphicsLayerHandler = dojo.connect(selectParGraphicsLayer, "onClick", removeSelectedPar);
			} else if ($('#bufferLayerGraphic').hasClass('fa-dot-circle-o') && $('#selectBuffer').hasClass('btn-danger')){
				//parcelLayer.infoTemplate = "";
				highlightLayer.hide();
				$('#layerInfoHover').hide();
				$('#map_layers').css('cursor', 'crosshair');
				// no need for a for(layer in ParcelLayers) loop here since we're only acting on the user graphics layer, but it does need to be an array so it can be cleaned up later
				bufferSelectHandler.push(dojo.connect(userGraphicsLayer, "onClick", function(evt){
					collectFeatures(evt.graphic.geometry);
				}));

				parGraphicsLayerHandler = dojo.connect(selectParGraphicsLayer, "onClick", removeSelectedPar);
			}
		} else {
			// deactivate the button
			clearBuffers();
			//parcelLayer.infoTemplate = template; // reset parcel template for click events
			for(layer in parcelLayers){
				window[layer].setInfoTemplate(parcelLayers[layer].infoTemplate);
			}
        }
    });

	
	function clearBuffers(){
		// clean up all kinds of event handlers for updating buffers on zoom
		if(typeof(bufferZoomEndHandler) !== "undefined"){
			bufferZoomStartHandler.remove();
			bufferZoomEndHandler.remove();
			bufferZoomStartHandler = undefined;
			bufferZoomEndHandler = undefined;

			for(layer in parcelLayers){
				if(typeof(parcelLayers[layer].updateStartHandler) !== "undefined"){
					parcelLayers[layer].updateStartHandler.remove();
					parcelLayers[layer].updateEndHandler.remove();
					parcelLayers[layer].updateStartHandler = undefined;
					parcelLayers[layer].updateEndHandler = undefined;
				}
			}
		}

		$('#runBuffer').removeClass('active');
		for(var i = 0; i < bufferSelectHandler.length; i++){
			if(bufferSelectHandler[i] != undefined){
				dojo.disconnect(bufferSelectHandler[i]);
			}
		}
		bufferSelectHandler = []; // just to be safe

		if(parGraphicsLayerHandler != undefined){
			dojo.disconnect(parGraphicsLayerHandler);
		}
		//if($('#selectBuffer').hasClass('btn-danger')){
			$('#selectBuffer').removeClass('btn-danger').addClass('btn-primary').html('Select');
            $('#bufferLayerSelect').removeClass('noPointerEvents').css('opacity', 1);
			if($('#bufferLayerGraphic').hasClass('fa-dot-circle-o')){
				enableGraphicHover(false);
			}
			highlightLayer.show();
            $('#layerInfoHover').show();
			
		//}
		$('#map_layers').css('cursor', '');
		for (var i = 0; i < map.graphics.graphics.length; i++) {
            if (map.graphics.graphics[i].attributes == 'bufferSymbol'){
                map.graphics.remove(map.graphics.graphics[i])
            }
        }
        
        //parcelLayer.clearSelection(); //removes highlighted parcels within buffer
		for(layer in parcelLayers){
			window[layer].clearSelection();
		}
		
		selectParGraphicsLayer.clear();
        bufferGeometrySelect = [];
        $('#bufferOutMessage').html(''); //hide red buffer message
        //map.infoWindow.hide();
        $(".bufferResultsCounter").html(''); //clear and hide selected parcels message
        $("#bufferResultsTable, #saveButtonsDiv").addClass('hidden'); //hide parcel results table
        $("#bufferTableScroll > tbody").html(''); //clear buffer results table data
		$('#bufferResultsScroll').perfectScrollbar('update');
	};

	$('#bufferLayerGraphicDiv').on("click", function(){
		$('#bufferLayerGraphic').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
		$('#bufferLayerParcel').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
	});
	$('#bufferLayerParcelDiv').on("click", function(){
		$('#bufferLayerGraphic').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
		$('#bufferLayerParcel').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
	});
	
	function storeBuff64(){
		try{
			//Build an object that will be used to set buffer params
			var buff64Object = [];
			var type = "";
			var size = $('#bufferSize').val();
			var units = $('#bufferUnits').val();
			if ($('#bufferLayerParcel').hasClass('fa-dot-circle-o')){
				type = "p" // parcel
			} else {
				type = "g"	// graphic
			}
			
			var geoms = [] //array of geometries
			for(var i = 0; i < selectParGraphicsLayer.graphics.length; i++){
				//geoms[i] = selectParGraphicsLayer.graphics[i].geometry.rings;
				geoms[i] = selectParGraphicsLayer.graphics[i].geometry.toJson();
			}

			buff64Object[0] = type;
			buff64Object[1] = size;
			buff64Object[2] = units;
			for(var i = 0; i < geoms.length; i++){
				buff64Object.push(geoms[i]);
			}
			var jsonStr = JSON.stringify(buff64Object)
			buff64 = buff64Object;
			updateURLgeometry();
			
			//console.log(LZString.decompressFromEncodedURIComponent(buff64)); //tested the output, now we need to implement this on page load
		} catch(err) {
			console.log("Error in storeBuff64: " + err.message);
		}
		
	}
	
	function restoreBuffer(buffRestoreObj){
		//setTimeout(function(){
			infoWindowHide();
			if(params.activeControl != "drawControls" && params.activeControl != "printControls"){
				$('#draw').trigger('click');
				$('#bufferToolsHeading').trigger('click');
			}
			
			setTimeout(function(){
				try{
					// Set radio buttons
					if (buffRestoreObj[0] == "p"){
						$('#bufferLayerParcel').removeClass('fa-circle-o').addClass('fa-dot-circle-o'); // parcel
						$('#bufferLayerGraphic').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
					} else {
						$('#bufferLayerParcel').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
						$('#bufferLayerGraphic').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
					}
					// set size and units
					$('#bufferSize').val(buffRestoreObj[1]);
					$('#bufferUnits').val(buffRestoreObj[2]);
					// add graphics back to the map
					for(var i = 3; i < buffRestoreObj.length; i++){
						if(buffRestoreObj[i].paths != undefined){
							var polylineGeom = new Polyline(buffRestoreObj[i]);
							var highlightParGraphic = new Graphic(polylineGeom, highlightSelectPar);
							bufferGeometrySelect.push(polylineGeom);
						} else if(buffRestoreObj[i].rings != undefined){
							var polygonGeom = new Polygon(buffRestoreObj[i]);
							var highlightParGraphic = new Graphic(polygonGeom, highlightSelectPar);
							bufferGeometrySelect.push(polygonGeom);
						}
						selectParGraphicsLayer.add(highlightParGraphic);
						setBufferSelectCount(bufferGeometrySelect);
					}
					// run the buffer
					runBuffer();
				} catch(err) {
					console.log('Error in buffer restoration: ' + err.message);
				}
			}, 500);
		//}, 100);
	}
	
	function runBuffer(){
		buffData = [];
		
		//$('#selectBuffer').html('Select'); //change selectBuffer text back to orig and fire off buffer
		if(selectParGraphicsLayer.graphics.length < 1 && popup.isShowing && popup.getSelectedFeature()._layer.id.indexOf('ParcelLayer') !== -1 && $('#bufferLayerParcel').hasClass('fa-dot-circle-o')){ // paranoia
			collectFeatures(popup.getSelectedFeature().geometry);
			infoWindowHide(); // cancels popups on parcels
		}

		storeBuff64();	
		bufferParcels(map);
		highlightLayer.show();
		$('#layerInfoHover').show();
		$("#bufferTableScroll > tbody").html('');
		for(var i = 0; i < bufferSelectHandler.length; i++){
			if(bufferSelectHandler[i] != undefined){
				dojo.disconnect(bufferSelectHandler[i]);
			}
		}
		dojo.disconnect(parGraphicsLayerHandler);
		$('#map_layers').css('cursor', '');
		$('#selectBuffer').removeClass('btn-danger').addClass('btn-primary');
		$('#bufferLayerSelect').removeClass('noPointerEvents').css('opacity', 1);
		if($('#bufferLayerGraphic').prop('checked')){
			//parcelLayer.show();
			for(layer in parcelLayers){
				window[layer].show();
			}
		}
		//parcelLayer.infoTemplate = template;
		for(layer in parcelLayers){
			window[layer].setInfoTemplate(parcelLayers[layer].infoTemplate);
		}

	}
	
	$('#runBuffer').on("click", function(){
		runBuffer();
	});

    //function to run esri buffer tool and create parcel query to be used for selecting intersecting parcels
	function bufferParcels(map){
		if($('#bufferSize').val() != ""){
			var bufferDistance = parseInt($('#bufferSize').val());
			if(bufferDistance === 0){ // if the user specifies a zero buffer, bring the buffer in a unit so it doesn't select any OTHER parcels
				bufferDistance = -2;
			}
            $('#bufferOutMessage').html('');
			$('#bufferSize').css('border', 'none');
		} else { // user left distance blank
			$('#bufferSize').css('border', '2px solid red');
            $('#bufferOutMessage').html('Please enter a distance').css('opacity', 1).animate({opacity: 1}, 4000,function(){
				$('#bufferOutMessage').animate({opacity: 0}, 2000);
			});
			return false;
			
		}
		
		var bufferUnits = parseInt($('#bufferUnits').val());
        //var bufferedGeometries = geometryEngine.geodesicBuffer(geometries, [2000], 9036, true);
        if (bufferGeometrySelect == ''){
            $('#bufferOutMessage').html('No features selected to buffer');
			setTimeout(function(){
				$('#bufferOutMessage').animate({opacity: 0}, 2000, function(){
					$('#bufferOutMessage').html('').css('opacity', 1);
				});
			},2000);
            return false;
		}
		
        bufferedGeometries = geometryEngine.geodesicBuffer(bufferGeometrySelect, [bufferDistance], bufferUnits, true);
        bufferGeometrySelect = [] //clear out variable after buffer fucntion has run
        //when buffer is done set up renderer and add each geometry to the map's graphics layer as a graphic
		var bufferedSymbol = map.graphics.add(new Graphic(bufferedGeometries[0],bufferRenderer));
        bufferedSymbol.attributes = 'bufferSymbol';
        var parQuery = new query(); //init query for parcels intersecting buffer
		parQuery.outSpatialReference = new SpatialReference({ wkid:102100 });
        parQuery.geometry = bufferedGeometries[0];
		parQuery.returnGeometry = true;
		parQuery.outFields = ["*"];
		parQuery.simplifyGeometry = false;

		for(layer in parcelLayers){
			//run query if layer is visible, then send results to selectInBuffer
			if(window[layer].visible){
				var parQueryTask = new QueryTask(window[layer].url);
				var _funcMaker = function(layerId) {
					return function(queryResults) {
						// do something with results based on type
						// that was passed to it
						checkBufferResultsLimit(queryResults, layerId);
					};
				};
				parQueryTask.execute(parQuery, _funcMaker(layer));
			}
		}
	}
    function checkBufferResultsLimit(response, layerId){
        if (response.features.length == 2000){
            cancelBuffer();
            showAlert('Notice:', 'Maximum buffer limit reached (2000 records). Please try again with a smaller buffer.')
        } else {
            selectInBuffer(response, layerId);
        }
    }
    //sets feature selection and selection symbol for parcels intersecting buffer
	var bufferZoomStartHandler, bufferZoomEndHandler;
    function selectInBuffer(response, layerId) {

		//for(layer in parcelLayers){
			//window[layer].setInfoTemplate(parcelLayers[layer].infoTemplate);
			var features = response.features;
			var inBuffer = []; //only PIN for feature layer selecteion
			var inBufferForTable = []; //whole feature for buffer table

			for (var i = 0; i < features.length; i++) {
				if (features[i].attributes[parcelLayers[layerId].pinField] != 'ROW'){ //do not select ROW parcels
					features[i]._layer = window[layerId]; // results from server lack the layer reference
					inBuffer.push(features[i].attributes[window[layerId].objectIdField]);  // parcelLayer.objectIdField is better because objectid field name can be anything per featurelayer
					inBufferForTable.push(features[i]);
				}
			}
			var parQuery = new query();
			parQuery.outSpatialReference = new SpatialReference({ wkid:102100 });
			parQuery.objectIds = inBuffer;
			//setBufferResultsCount(parQuery);
			try{

				$('#bufferPrintLegendCheckbox').parent().removeClass('hidden');
				if(params.pdf != '1' && inBufferForTable.length > 0){ // prevents fetching of buffer table data if printing PDF map of buffer.
					fetchBuffResultsData(inBufferForTable, layerId);
					if($("#bufferPrintLegendCheckbox").hasClass("fa-square-o")){
						$("#bufferPrintLegendCheckbox").trigger('click');
					}
					window[layerId].selectFeatures(parQuery, window[layerId].SELECTION_NEW);
					window[layerId].setSelectionSymbol(setSelectionSymbol());

					// before zooming,see if buffer-selected features exist in parcel layers. Store as bool in parcelLayers object for each layer;
					if(typeof(bufferZoomStartHandler) === "undefined"){
						bufferZoomStartHandler = map.on('zoom-start', function(){
							for(layer in parcelLayers){
								var selectedFeatures = window[layer].getSelectedFeatures();
								if(selectedFeatures.length > 0){
									parcelLayers[layer].hasSelectedFeatures = true;
								} else {
									parcelLayers[layer].hasSelectedFeatures = false
								}
							}
						});
					}

					// after zooming, if a parcel layer has buffer-selected features, clear the selection before the layer starts updating (or the layer will hold on to simplified geometries!)
					// then once the layer is done updating, re-select the buffered geometries
					if(typeof(bufferZoomEndHandler) === "undefined"){
						bufferZoomEndHandler = map.on('zoom-end', function(){
							for(layer in parcelLayers){
								if(parcelLayers[layer].hasSelectedFeatures === true){

									if(typeof(parcelLayers[layer].updateStartHandler) === "undefined"){
										// clear the layer's selection just before the layer starts to update
										parcelLayers[layer].updateStartHandler = window[layer].on("update-start", function(event){
											window[event.target.id].clearSelection();
										});

										parcelLayers[layer].updateEndHandler = window[layer].on("update-end", function(event){
											try{
												var parQuery = new query();
												//parQuery.where = window[event.target.id].objectIdField + " = " + objectIdStr + "'";
												parQuery.outSpatialReference = new SpatialReference({ wkid:102100 });
												parQuery.geometry = bufferedGeometries[0];
												parQuery.outFields = ["*"];

												window[event.target.id].selectFeatures(parQuery);
												// remove update-end handler from layer when done
												//parcelLayers[event.target.id].updateEndHandler.remove();
											}catch(err){
												console.log(err.message);
											}
										});
									}
								}
							}
						});
					}
					
				} else if(params.pdf === '1') {
					// just select buffered parcels and set the symbol
					window[layerId].setSelectionSymbol(setSelectionSymbol());
					window[layerId].selectFeatures(parQuery, window[layerId].SELECTION_NEW).then(function(){
						pdfReady = '2';
						console.log('pdfReady = 2');
					});
				}
			
			
			} catch(err) {
				console.log("Buffer Error: " + err.message);
			}
		//}
    } 
    //function to set symbol of selected parcels
    function setSelectionSymbol() {
        var symbol;
        //if 'highlight affected parcels' box is checked, set a semi-transparent selection symbol
        //if not checked, set a completely trasnparent symbol
        if ($('#highlightParcelsCheckbox').hasClass('fa-check-square-o') == true) {
            symbol = new SimpleFillSymbol().setColor(new Color([255,255,0,.15])); //selection symbol set to visable if 'hihglight parcel button' is checked
			return symbol;
        } else {
            symbol = new SimpleFillSymbol().setColor(new Color([255,255,0,.00])); //selection symbol set to transparent 
            return symbol;
        }
    }

    //Hide/Show map parcel bufer selection if 'disable parcel highlighting' button is clicked
    $('#bufferParcelHightlightCheckbox').on("click", function() {
		if($('#highlightParcelsCheckbox').hasClass('fa-check-square-o')){
			$('#highlightParcelsCheckbox').removeClass('fa-check-square-o').addClass('fa-square-o');
		} else {
			$('#highlightParcelsCheckbox').removeClass('fa-square-o').addClass('fa-check-square-o');
            if ($('#bufferResultsTable > tbody > tr').length > 0){
                $("#saveButtonsDiv").removeClass('hidden');
            }
		}
        //parcelLayer.setSelectionSymbol(setSelectionSymbol());
		for(layer in parcelLayers){
			window[layer].setSelectionSymbol(setSelectionSymbol());
		}
    });
	
	$('#averyLabelsSelectContainer li').on("click", function(evt){
		var val = $(evt.target).val();
		var newValHTML= '';

		if(val === 5160){
			newValHTML = 'Avery 5160 <span class="caret"></span>';
		} else if(val === 5162){
			newValHTML = 'Avery 5162 <span class="caret"></span>';
		} else if(val === 5163){
			newValHTML = 'Avery 5163 <span class="caret"></span>';
		}

		$('#averyLabelsSelect').html(newValHTML);

		$('#averyLabelsSelectContainer li').removeClass('selected');
		$(this).addClass('selected');

		// if labels are showing, fade out and regenerate the new ones
		if($('.averyLabel').length > 0 ){
			exitLabelPrint(true);
		}
	});

	// avery label creation code moved to main.js
	
    $("#createBufferMailingLabels").on("click", function(){
        createBufferMailingLabels();
    });
	
	function createBufferMailingLabels(transId){
		if($("#createBufferMailingLabels").html() == 'Print Labels'){
			
						printAveryLabels(buffData, $("#averyLabelsSelect").html());
						$("#createBufferMailingLabels").removeClass("btn-success").addClass("btn-danger").html("Close Labels");
								$('#closeLabels').removeClass('hidden');
		} else {
			exitLabelPrint();
		}
	}
	
	$('#closeLabels').on("click", function(){
		exitLabelPrint();
	});
	
	function exitLabelPrint(isChangingLabels){
		$('#pdfLabelStyle').remove(); // take the zero margin setting out of the document head
		
		$('#closeLabels').addClass('hidden');
		if(!$('#printModeToggleButton').hasClass('active')){
			$('#printHint').removeClass('hidden');
		}
		$('.dijitTooltipDialogPopup').removeClass('hidden');
		if(typeof(isChangingLabels) === "undefined"){ // if it's not undefined, it's true
			$("#createBufferMailingLabels").removeClass('btn-danger').addClass('btn-success').html('Print Labels');
		}
		if(firefox){
			$('body').height('');
		}
		if($('.printLabelsPage').eq(0).hasClass('portraitLetter')){
			$('.printLabelsPage, #labelPrintDiv').stop().fadeOut(400, function(){

				$('.printLabelsPage, .pageSpacer').remove();
				if(typeof(isChangingLabels) === "undefined"){ // if it's not undefined, it's true
					$('#contentContainer, #printedPage').removeClass('portraitVariable');
					$('#labelPrintPaginationDiv').addClass('hidden');
					$('#neatline').removeClass('visuallyHidden');
					map.resize(true);
					map.reposition();
					$('#neatline').animate({opacity: 1});
					$("#createBufferMailingLabels").removeClass('btn-danger').addClass('btn-success').html('Print Labels');
				} else {
						printAveryLabels(buffData, $("#averyLabelsSelect").html());
				}
			});
		}
		
	}


	$(function(){ // remove parcel stuff that requires lrp if no lrp is present
		var parcelHasLRP = false;
		var parcelHasFields = false;
		for(layer in parcelLayers){
			var parcelInfo = parcelLayers[layer];
			if(typeof parcelInfo.lrpMapStr != "undefined"){
				parcelHasLRP = true;
			} else if(parcelInfo.ownerStreetField){ //will have fields for buffer tool
				parcelHasFields = true;
			}
		}

		if(parcelHasLRP == false && currentMap != "dowGardens"){
			if(parcelHasFields == false) {
				$("#bufferToolsHeading").remove();
			}
			$("#recentParcelHeader").remove();
		}
	})
    
	    
	
        // ########################################
        // TILE LAYERS
        // ########################################
            
        imagery2010Layer = new ArcGISTiledMapServiceLayer("https://app.fetchgis.com/geoservices/rest/services/bayImagery2010/MapServer",{
			id: "imagery2010Layer",
			visible:false
		});
		imagery2015Layer = new ArcGISTiledMapServiceLayer("https://app.fetchgis.com/geoservices/rest/services/bayImagery2015/MapServer",{
			id: "imagery2015Layer",
			visible:false
		});
        
        imagery2005Layer = new ArcGISTiledMapServiceLayer("http://tiles.arcgis.com/tiles/blE8NdGZSd9Sfngv/arcgis/rest/services/bayImagery2005/MapServer",{
			id: "imagery2005Layer",
            visible: false
		});
        
        hydroLayer = new ArcGISTiledMapServiceLayer("https://app.fetchgis.com/geoservices/rest/services/bayHydro/MapServer",{
			id: "hydroLayer"
		});
       
		municipalBoundaryLayer = new ArcGISTiledMapServiceLayer("https://app.fetchgis.com/geoservices/rest/services/bayCVT/MapServer",{
			id: "municipalBoundaryLayer"
		});  
		
		wetLayer = new ArcGISTiledMapServiceLayer("https://app.fetchgis.com/geoservices/rest/services/bayWetlands/MapServer",{
			id: "wetLayer",
			visible: false
		});

        demLayer = new ArcGISTiledMapServiceLayer("https://app.fetchgis.com/geoservices/rest/services/bayDEM/MapServer",{
			id: "demLayer",
			visible: false
		});
		
		//########## FEMA LAYERS ###########

		
    // FEMA nationwide flood map
    femaFloodLayer = new ArcGISDynamicMapServiceLayer(
        "https://hazards.fema.gov/arcgis/rest/services/public/NFHLWMS/MapServer",{
            //useMapImage: true,
            id: "femaFloodLayer",
            minScale: 36200,
            imageTransparency: true,
            visible: false
        }
    );
    femaFloodLayer.on("error", collectLoadError);
    
    femaFloodLayer.setImageFormat("png32");
    //make this layer smarter, so when they change order or layers, we still get the correct one
    femaFloodLayer.on("load", function(e){
        femaFloodLayer.layerInfos.forEach(function(li){
            if(li.name == "Flood Hazard Zones"){
                femaFloodLayer.setVisibleLayers([li.id]);
            }
        })
    })


		contoursVectorLayer = new VectorTileLayer("https://tiles.arcgis.com/tiles/blE8NdGZSd9Sfngv/arcgis/rest/services/bayContours/VectorTileServer", {
			id: "contoursVectorLayer",
			visible: false,
			maxScale: 282.124294,
			minScale: 37000
		});
       
        // ########################################
        // FEATURE LAYERS
        // ########################################
        /*
        parcelLayer = new FeatureLayer("", {
            id: "parcelLayer",
            surfaceType: "canvas-2d",
            mode: "FeatureLayer.MODE_ONDEMAND",     
            outFields: ["OBJECTID", "PARCELID"],
            infoTemplate: template
	   });
       */
   
		streetsLayer = new ArcGISTiledMapServiceLayer("https://app.fetchgis.com/geoservices/rest/services/bayRoads/MapServer",{
			id: "streetsLayer"
		});
		
		

        trashPickupLayer = new FeatureLayer("https://services3.arcgis.com/blE8NdGZSd9Sfngv/arcgis/rest/services/bayTrash/FeatureServer/0", {
			id: "trashPickupLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_ONDEMAND,
			outFields: ["DOW"],
            visible: false
		});

        zoningLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayZoning/FeatureServer/0", {
			id: "zoningLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_ONDEMAND,
			outFields: ["ZONING"],
            visible: false
		});

        zoningBeaverLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayZoning/FeatureServer/1", {
			id: "zoningBeaverLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_ONDEMAND,
			outFields: ["ZONING"],
            visible: false
		}); 


        zoningKawkawlinLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayZoning/FeatureServer/3", {
			id: "zoningKawkawlinLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_ONDEMAND,
			outFields: ["ZONING"],
            visible: false
		});

        zoningHamptonLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayZoning/FeatureServer/2", {
			id: "zoningHamptonLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_ONDEMAND,
			outFields: ["ZONING"],
            visible: false
		});
		
        bcatsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayBCATS/FeatureServer/0", {
			id: "bcatsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_ONDEMAND,
			outFields: ["Year", "FULLNAME_1", "Agency", "type"],
            visible: false,
			infoTemplate: bcatsInfoTemplate
		});
		
		drainsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayDrains/FeatureServer/0", {
			id: "drainsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_ONDEMAND,
			outFields: ["NAME"],
            visible: false,
			//infoTemplate: wildcardInfoTemplate
		});

        sectionsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/baySec/FeatureServer/0", {
			id: "sectionsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["FRSTDIVLAB", "PRCLIDNUM"],
            visible: false
		});
        
        schoolDistrictsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/baySchoolDist/FeatureServer/0", {
			id: "schoolDistrictsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["OBJECTID", "SUBTYPEFIE", "NAME", "DISTRCTNAM", "Elementary", "SCH_FP"],
            visible: false
		});
        
        emsDistrictsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayEMS/FeatureServer/0", {
			id: "emsDistrictsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME"],
            visible: false
		});
        
        pollingPlaceLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayVoting/FeatureServer/0", {
			id: "pollingPlaceLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
            outFields: ["NAME", "PRECINCT"],
            visible: false
		});
		
        bcrcLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayBCRCProjects/FeatureServer/0", {
			id: "bcrcLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
            outFields: ["Project_No", "Road_Name", "Agency", "Project", "Termini"],
            visible: false,
			infoTemplate: wildcardInfoTemplate
		});
        
        votingPrecinctLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayVoting/FeatureServer/1", {
			id: "votingPrecinctLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
            outFields: ["NAME"],
            visible: false
		});
        
        parksLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayParks/FeatureServer/0", {
			id: "parksLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME"],
            visible: false
		}); 
		
        roadProjectsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayCityRoadProjects/FeatureServer/0", {
			id: "roadProjectsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
            outFields: ["*"],
            visible: false,
			infoTemplate: roadProjectsTemplate,
		});
        
        nonMotorTrailsLayer = new FeatureLayer("http://services3.arcgis.com/blE8NdGZSd9Sfngv/arcgis/rest/services/bayNonMotorTrails/FeatureServer/0", {
			id: "nonMotorTrailsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME", "SURFACE"],
            visible: false,
			infoTemplate: wildcardInfoTemplate
		});
		
        proposedTrailsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayProposedTrails/FeatureServer/0", {
			id: "proposedTrailsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME", "SURFACE"],
            visible: false,
			infoTemplate: wildcardInfoTemplate
		});
        
        municipalFacilitiesLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayMunicipalFacilities/FeatureServer/0", {
			id: "municipalFacilitiesLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME"],
            visible: false
		});
        
		fireStationsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayFire/FeatureServer/0", {
			id: "fireStationsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME"],
            visible: false
		});
        
        fireDistrictsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayFire/FeatureServer/1", {
			id: "fireDistrictsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME"],
            visible: false
		});
        
        commDistrictsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayCommissionerDistricts/FeatureServer/0", {
			id: "commDistrictsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME", "REPNAME"],
            visible: false
		});
        
        districtsWardsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayWards/FeatureServer/0", {
			id: "districtsWardsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME", "REPNAME"],
            visible: false
		});
        
        airportsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayPOI/FeatureServer/0", {
			id: "airportsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME"],
            visible: false,
			infoTemplate: wildcardInfoTemplate
		});
        
        boatLaunchesLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayPOI/FeatureServer/1", {
			id: "boatLaunchesLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["ACCESSSITE"],
            visible: false,
			infoTemplate: wildcardInfoTemplate
		});
        
        fairgroundsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayPOI/FeatureServer/2", {
			id: "fairgroundsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME"],
            visible: false,
			infoTemplate: wildcardInfoTemplate
		});
        
        courthouseLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayPOI/FeatureServer/3", {
			id: "courthouseLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME", "FULLADDR", "MUNICIPALI", "STATE"],
            visible: false,
			infoTemplate: wildcardInfoTemplate
		});
        
        historicalMarkersLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayPOI/FeatureServer/4", {
			id: "historicalMarkersLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME"],
            visible: false,
			infoTemplate: wildcardInfoTemplate
		});
        
        librariesLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayPOI/FeatureServer/5", {
			id: "librariesLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME", "FULLADDR", "MUNICIPALI"],
            visible: false,
			infoTemplate: wildcardInfoTemplate
		});
        
        planetariumLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayPOI/FeatureServer/6", {
			id: "planetariumLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME"],
            visible: false,
			infoTemplate: wildcardInfoTemplate
		});
        
        schoolsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayPOI/FeatureServer/7", {
			id: "schoolsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME"],
            visible: false
		});   
		
       trailheadLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayTrailheads/FeatureServer/0", {
			id: "trailheadLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["NAME"],
            visible: false,
			infoTemplate: wildcardInfoTemplate
		});
		
       snowParkingLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/baySnowParking/FeatureServer/0", {
			id: "snowParkingLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: [],
            visible: false,
		});

		sanSewersLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/empty_layer_dont_delete/FeatureServer/0", {
			id: "sanSewersLayer",
			mode: FeatureLayer.MODE_ONDEMAND,
			outFields: [],
			visible: false,
		  });
  

			countyWaterMainsLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/empty_layer_dont_delete/FeatureServer/0", {
			  id: "countyWaterMainsLayer",
			  mode: FeatureLayer.MODE_ONDEMAND,
			  outFields: [],
			  visible: false,
		  });

		sanitarySewerServicePossibleLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayWaterSewerService/FeatureServer/0", {
			id: "sanitarySewerServicePossibleLayer",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: [],
			visible: false,
		});

		waterServicePossibleLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayWaterSewerService/FeatureServer/1", {
			id: "waterServicePossibleLayer",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: [],
			visible: false,
		});

		noWakeLayer = new FeatureLayer("https://app.fetchgis.com/geoservices/fgis/bayNoWake/FeatureServer/0", {
			id: "noWakeLayer",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: [],
			visible: false,
		});

		// TEMPORARY LAYERS
		/*
		hydrantsLayer = new FeatureLayer("http://services3.arcgis.com/blE8NdGZSd9Sfngv/arcgis/rest/services/bayHydrant/FeatureServer/0", {
			id: "hydrantsLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["*"],
            visible: false
		});
		
		sewerManholeLayer = new FeatureLayer("http://services3.arcgis.com/blE8NdGZSd9Sfngv/arcgis/rest/services/baySewerManhole/FeatureServer/0", {
			id: "sewerManholeLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["*"],
            visible: false
		});
		
		waterValveLayer = new FeatureLayer("http://services3.arcgis.com/blE8NdGZSd9Sfngv/arcgis/rest/services/bayWaterValve/FeatureServer/0", {
			id: "waterValveLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["*"],
            visible: false
		});
		
		catchBasinLayer = new FeatureLayer("http://services3.arcgis.com/blE8NdGZSd9Sfngv/arcgis/rest/services/bayCatchBasin/FeatureServer/0", {
			id: "catchBasinLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["*"],
            visible: false
		});
		
		waterLineLayer = new FeatureLayer("http://services3.arcgis.com/blE8NdGZSd9Sfngv/arcgis/rest/services/bayWaterLine/FeatureServer/0", {
			id: "waterLineLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["*"],
            visible: false
		});
		
		sewerLineLayer = new FeatureLayer("http://services3.arcgis.com/blE8NdGZSd9Sfngv/arcgis/rest/services/baySewerLine/FeatureServer/0", {
			id: "sewerLineLayer",
			surfaceType: "canvas-2d",
			mode: FeatureLayer.MODE_SNAPSHOT,
			outFields: ["*"],
            visible: false
		});*/
		
		
		
		
		/*
		#################################
		 START CODE TO SET MAP LABELS
		#################################
		*/
        var sectionLabels = new LabelLayer({id: "sectionLabels"});
        var sectionsLabelSymbol = new TextSymbol({
            type        : "esriTS",
            color       : [255,0,0,255],
			haloSize	: 1,
			haloColor	: [255,255,255,255],
            font        :{
                size    : 16,
                weight  : "bold"
            }
        })
        var sectionsLabelRenderer = new SimpleRenderer(sectionsLabelSymbol);
        sectionLabels.addFeatureLayer(sectionsLayer, sectionsLabelRenderer, "{FRSTDIVLAB}");
        
        var schoolsDistLabels = new LabelLayer({id: "schoolsDistLabels", mode: "DYNAMIC"});
        var schoolsDistSymbol = new TextSymbol({
            type        : "esriTS",
            color       : [0,0,0,255],
			haloSize	: 1,
			haloColor	: [255,255,255,255],
            font        :{
                size    : 16,
                weight  : "bold"
            }
        })
        var schoolsDistLabelRenderer = new SimpleRenderer(schoolsDistSymbol);
        schoolsDistLabels.addFeatureLayer(schoolDistrictsLayer, schoolsDistLabelRenderer, "{DISTRCTNAM}");
        
        var emsDistLabels = new LabelLayer({id: "emsDistLabels", mode: "DYNAMIC"});
        var emsDistSymbol = new TextSymbol({
            type        : "esriTS",
            color       : [132,0,168,255],
			haloSize	: 1,
			haloColor	: [255,255,255,255],
            font        :{
                size    : 16,
                weight  : "bold"
            }
        })
        var emsDistLabelRenderer = new SimpleRenderer(emsDistSymbol);
        emsDistLabels.addFeatureLayer(emsDistrictsLayer, emsDistLabelRenderer, "{NAME}");
        
        var votingDistLabels = new LabelLayer({id: "votingDistLabels", mode: "DYNAMIC"});
        var votingDistSymbol = new TextSymbol({
            type        : "esriTS",
            color       : [0,112,255,255],
			haloSize	: 1,
			haloColor	: [255,255,255,255],
            font        :{
                size    : 16,
                weight  : "bold"
            }
        })
        var votingDistLabelRenderer = new SimpleRenderer(votingDistSymbol);
        votingDistLabels.addFeatureLayer(votingPrecinctLayer, votingDistLabelRenderer, "{NAME}");
        map.addLayer(votingDistLabels);
        
        var fireDistLabels = new LabelLayer({id: "fireDistLabels", mode: "DYNAMIC"});
        var fireDistSymbol = new TextSymbol({
            type        : "esriTS",
            color       : [255,0,0,255],
			haloSize	: 1,
			haloColor	: [255,255,255,255],
            font        :{
                size    : 16,
                weight  : "bold"
            }
        })
        var fireDistLabelRenderer = new SimpleRenderer(fireDistSymbol);
        fireDistLabels.addFeatureLayer(fireDistrictsLayer, fireDistLabelRenderer, "{NAME}");
        
        var commDistrictsLabels1 = new LabelLayer({id: "commDistrictsLabels1", mode: "DYNAMIC"});
        var commDistrictsLabels2 = new LabelLayer({id: "commDistrictsLabels2", mode: "DYNAMIC"});
        // first symbol for district number, second offset for repname
        var commDistrictsSymbol1 = new TextSymbol({
            type        : "esriTS",
            color       : [0,148,0,255],
			haloSize	: 1,
			haloColor	: [255,255,255,255],
            font        :{
                size    : 16,
                size    : 16,
                weight  : "bold"
            }
        })
        var commDistrictsSymbol2 = new TextSymbol({
            type        : "esriTS",
            color       : [0,148,0,255],
			haloSize	: 1,
			haloColor	: [255,255,255,255],
            yoffset     : -20,
            font        :{
                size    : 16,
                size    : 16,
                weight  : "bold"
            }
        })
        var commDistrictsLabelRenderer1 = new SimpleRenderer(commDistrictsSymbol1);
        var commDistrictsLabelRenderer2 = new SimpleRenderer(commDistrictsSymbol2);
        commDistrictsLabels1.addFeatureLayer(commDistrictsLayer, commDistrictsLabelRenderer1, "{NAME}");
        commDistrictsLabels2.addFeatureLayer(commDistrictsLayer, commDistrictsLabelRenderer2, "{REPNAME}");
        
        var wardLabels1 = new LabelLayer({id: "wardLabels1", mode: "DYNAMIC"});
        var wardLabels2 = new LabelLayer({id: "wardLabels2", mode: "DYNAMIC"});
        var wardSymbol1 = new TextSymbol({
            type        : "esriTS",
            color       : [255,170,0,255],
			haloSize	: 1,
			haloColor	: [255,255,255,255],
            font        :{
                size    : 16,
                weight  : "bold"
            }
        })
        var wardSymbol2 = new TextSymbol({
            type        : "esriTS",
            color       : [255,170,0,255],
			haloSize	: 1,
			haloColor	: [255,255,255,255],
            yoffset     : -20,
            font        :{
                size    : 16,
                weight  : "bold"
            }
        })
        var wardLabelRenderer1 = new SimpleRenderer(wardSymbol1);
        var wardLabelRenderer2 = new SimpleRenderer(wardSymbol2);
        wardLabels1.addFeatureLayer(districtsWardsLayer, wardLabelRenderer1, "{NAME}");
        wardLabels2.addFeatureLayer(districtsWardsLayer, wardLabelRenderer2, "{REPNAME}");
        
        var parksLabels = new LabelLayer({id: "parksLabels", mode: "DYNAMIC"});
        var parksSymbol = new TextSymbol({
            type        : "esriTS",
            color       : [56,168,0,255],
			haloSize	: 1,
			haloColor	: [255,255,255,255],
            font        :{
                size    : 12,
                weight  : "bold"
            }
        })
        var parksLabelRenderer = new SimpleRenderer(parksSymbol);
        parksLabels.addFeatureLayer(parksLayer, parksLabelRenderer, "{NAME}");
        
	
	
    if(typeof window.dynamicTocOptions == "undefined"){
        window.dynamicTocOptions = [];
    }

    const tocSvgs = {
        
        rectangle: function(options){
            var html = '<rect rx="2" ry="2" width="100%" height="100%"';
                if(options.style == 'dash'){ //if dash is set, then don't do fill, just stroke
                    html += 'stroke-dasharray="4" stroke="'+options.fill+'" fill-opacity="0"'; //equal distance between dashes
                } else if(options.fillOpacity > -1){
                    html+='fill-opacity="'+options.fillOpacity+'"';
                }
                if(options.stroke && options.strokeWidth){
                    html += 'stroke="'+options.stroke+'"\
                    stroke-width="'+options.strokeWidth+'"';
                }
                if(options.diagonal == 'forward'){
                    html += 'fill="url(#forwardDiagonal)" shape-rendering="cripsEdges"';
                } else if(options.diagonal == 'backward'){
                    html += 'fill="url(#backwardDiagonal)" shape-rendering="cripsEdges"';
                }else {
                    html += 'fill="'+options.fill+'"';
                }

                html += '>';
            return html;
        },

        polyline: function(options){
            var html = '<polyline width="100%" height="100%"\
                points="0,11 29,11"\
                fill="'+(options.fill || "none")+'"';
                if(options.stroke && options.strokeWidth){
                    html += 'stroke="'+options.stroke+'"\
                    stroke-width="'+options.strokeWidth+'"';
                }
                if(options.style == 'dash'){
                    html += 'stroke-dasharray="4"'; //equal distance between dashes
                }
                if(options.style == 'dashdot'){
                    html += 'stroke-dasharray="4 4 1 4"'; //equal distance between dashes
                }
                html += '>';
            return html;
        },
        
        circle: function(options){

            var html = '<circle width="100%" height="100%" cx="50%" cy="50%" r="8" ';
            if(options.style == 'square'){
                html = '<rect x="1"  width="18" height="18" ';
            }

            html+=' fill="'+options.fill+'"';
                if(options.fillOpacity > -1){
                    html+='fill-opacity="'+options.fillOpacity+'"';
                }
                if(options.stroke && options.strokeWidth){
                    html += 'stroke="'+options.stroke+'"\
                    stroke-width="'+options.strokeWidth+'"';
                }

                html += '>';
            return html;
        },

        colorRamp: function(options){

            var id = "gradient_" + new Date().valueOf() + Math.floor(Math.random() * 101);

            var numColorBreaks = options.colorBreaks.length;
            var step = 100/numColorBreaks;
            var stopHtml  = '';
            var stop = 0;
            for(var i=0; i< numColorBreaks; i++){
                var color = options.colorBreaks[i];
                stopHtml += '<stop offset="'+stop+'%" stop-color="'+color+'"/>';
                stop += step;
            }

            var html = '<defs>\
                <linearGradient id="'+id+'" x1="0%" y1="0%" x2="100%" y2="0%">\
                   ' + stopHtml +'\
                </linearGradient>\
            </defs>\
            <rect width="100%" height="100%"  fill="url(#'+id+')"/></svg>';
            return html;
        },

        colorRamp2: function(options){
            var html = '<img src="img/'+options.color+'ColorRamp.png">';
            return html;
		}
    };
   
   const DynamicTOC = {

        html_store: {},

        _formatRangeLabel: function(label){
            if(label.indexOf("-") !== -1){
                if(label.indexOf("-<") !== -1 || label.indexOf("->") !== -1){ //leave less than or greater than labels alone
                    return label;
                }
                var split = label.split("-");
                if(isNaN(split[0])) return label;
                label = roundTo(split[0], 2) + "-" + roundTo(split[1], 2);
            }

            return label;
        },
        
        
        _getHeatmapColorRampInfos: function(renderer){

            var output = {
                colorBreaks : [],
                minValue : null,
                maxValue : null
            };

            for(var i=0; i<renderer.colorStops.length; i++){
                var info = renderer.colorStops[i];
                var color = new Color.fromRgb(info.color); // turn to hex to ignore transparecies
                output.colorBreaks.push(color.toHex());
                if(i == 0){
                    output.minValue = typeof renderer.fg_minValue != "undefined" ? roundTo(renderer.fg_minValue, 2) : null;
                } 
                if(i == renderer.colorStops.length - 1){
                    output.maxValue = typeof renderer.fg_maxValue != "undefined" ? roundTo(renderer.fg_maxValue, 2) : null;
                }
            }
            return output;
        },
        
        _getDefaultColorRampInfos: function(renderer){
          var output = {
                colorBreaks : [],
                minValue : null,
                maxValue : null
            };

            for(var i=0; i<renderer.infos.length; i++){
                var info = renderer.infos[i];
                var color = info.symbol.color.toString();
                output.colorBreaks.push(color);
                if(i == 0){
                    output.minValue = typeof info.minValue != "undefined" ? roundTo(info.minValue, 2) : roundTo(info.value, 2);
                }
                if(i == renderer.infos.length - 1){
                    output.maxValue = typeof info.maxValue != "undefined" ? roundTo(info.maxValue, 2) : roundTo(info.value, 2);
                }
            }
            
            return output;
        },

        _createLabelLookup: function(graphics, field, labelField){
            var lookup = {};
            for(var i=0; i< graphics.length; i++){
                var attributes = graphics[i].attributes;
                var key = attributes[field];
                if(!lookup[key]){
                    lookup[key] = attributes[labelField];
                }
            }
            return lookup;
        },
    
        _getRendererSymbol: function(symbol){
            return symbol.symbol || symbol;
        },
        
        _getLabelExtra: function(value, ba){
            if(typeof ba != "function"){
                return ba;
            }
            
            return ba(value);
        },
    
        applyDynamicTOC: function(layerOptions){
            
            var self = this;
    
            var layerId = layerOptions.layerId;
            var layerObj = window[layerId];
            
            if(layerObj.loaded != true){
                layerObj.on('load', function(){ // when layer is present so we can access renderer
                    self.applyDynamicTOC(layerOptions);
                });
                return false;
            }
            
            var renderer = layerObj.renderer;
            var attributeField = renderer.attributeField;
             /// handle label options
            var labelInfo;
            if(layerOptions.labels) {
                labelInfo = layerOptions.labels;
            } else if (layerOptions.fieldOptions && layerOptions.fieldOptions[attributeField] && layerOptions.fieldOptions[attributeField].labels){
                labelInfo = layerOptions.fieldOptions[attributeField].labels;
            } else {
                labelInfo = {}
            }            
            var before = labelInfo.before || ""; // get properties, intialize html vars
            var after = labelInfo.after || "";
            var modify = labelInfo.modify || false;
            var labelStrip = labelInfo.labelStrip || [];

            var tocHtml = "";
            var printLegendHtml = "";

            var geometryType;
            if(layerObj.geometryType)
                geometryType = layerObj.geometryType;
            else
                return false;

            function label_callback(labelLookup){

                var field_type = getFieldType(layerObj, attributeField);
                
                if(field_type != "esriFieldTypeString" && (layerOptions.colorRamp === true || renderer.declaredClass == "esri.renderer.HeatmapRenderer")){ // color ramp override
                    var infos;
                
                    switch(renderer.declaredClass){
                        case "esri.renderer.HeatmapRenderer":
                            infos = self._getHeatmapColorRampInfos(renderer);
                            break;
                        default:
                            infos = self._getDefaultColorRampInfos(renderer);
                            break;
                    }
                    let breakLength = infos.colorBreaks.length;
                    if(infos.colorBreaks.length > 0){
                        var toc_svg = tocSvgs.colorRamp({ // color ramp 
                            "colorBreaks": infos.colorBreaks,
                        });
    
                        var print_svg = tocSvgs.colorRamp({ // color ramp 
                            "colorBreaks": infos.colorBreaks,
                        });
                        
                        var minValue = infos.minValue;
                        var maxValue = infos.maxValue;
                        if(modify){
                            minValue = modify(minValue);
                            maxValue = modify(maxValue);
                        } else if(field_type == "esriFieldTypeDate"){ // is timestamp
                            var minDate = new Date(minValue);
                            minValue = (minDate.getMonth() + 1) + "/" + minDate.getDate() + "/" + minDate.getFullYear();
                            var maxDate = new Date(maxValue);
                            maxValue = (maxDate.getMonth() + 1) + "/" + maxDate.getDate() + "/" + maxDate.getFullYear();
                        }
                        
                        var full_label = before + minValue + after + '-' + before + maxValue + after;
    
                        tocHtml += '<div class="legendThumbnail">\
                                        <div class="dyna-colorramp-div">\
                                            <svg class="dyna-toc-symbol" style="width:160px;height:25px;">'+toc_svg+'</svg><br>\
                                            <span class="dyna-toc-label">' + (breakLength == 1 && !layerOptions.fromInsights ? '' : full_label) + '</span>\
                                        </div>\
                                    </div>\
                                    </div>';
                        printLegendHtml += '<div class="printLegendSymbolRow printLegendSymbolRowColorRamp">\
                                                <div class="prtLgndIconWrapper prtLegendColorRamp">\
                                                    <svg class="dyna-toc-symbol" style="width:100%;height:100%;">' +print_svg + '</svg>\
                                                </div>\
                                                <div class="prtLgndLabel gradientLabel" contenteditable="true">' + (breakLength == 1 ? '' : full_label) + '</div>\
                                            </div>';
                    }

                } else {
                    var symbols;
                    if(renderer.infos){ // get the symbols from the renderer object - multiple symbols, but not too many
                        symbols = renderer.infos;
                    } else { // single symbol
                        var legend_label = $("#"+layerId+"_legendLabel").html();
                        symbols = [
                            {
                                label : legend_label,
                                symbol : renderer.infos ? [] : renderer.getSymbol(),
                            }           
                        ];
                    }
                    if(renderer.exceeded){
                        tocHtml += 'Too many unique values';
                        symbols = [];
                    }
                    
                    if(symbols.length>1){
                        try{//get distinct symbols via same label
                            let newsymbols=[];
                            for(var s=0; s < symbols.length; s++){
                                if(newsymbols.findIndex(ns => ns.label == symbols[s].label) === -1){      
                                    if(rendererField == 'feesched_globalid'){
                                        symbols[s].label = formViewer.makeRenewalFeeText(symbols[s].label);
                                    }                       
                                    newsymbols.push(symbols[s]);
                                }
                            }
                            symbols=newsymbols;                          
                        }
                        catch(err){
                            console.log('couldnt get distinct vals from renderer for dynatoc. ' + err.message);
                        }
                    }

                    let symbolLength = symbols.length;

                    for(var i=0; i< symbols.length; i++){

                        var info = symbols[i];
                        var symbol = info.symbol;
                        var label = info.label;

                        if(!symbol) continue;

                        var svg, printsvg;
                        if(symbol.type =='picturemarkersymbol'){

                            svg='<img src="'+symbol.url+'" symbolval="'+info.value+'" layerlabel="'+label+'" layerName="'+layerId+'">';  //issue #1503
                            printsvg=svg;
                        }else{
                            var color = symbol.color.toHex();

                            var outlineColor = null;
                            var outlineWidth = null;
                            var fillOpacity=null;
                            if(symbol.outline){
                                outlineColor = symbol.outline.color.toHex();
                                outlineWidth = symbol.outline.width;
                                if(color == '#000000'){ //if fill is black
                                    if((outlineColor && outlineColor == '#000000') || !outlineColor) { //if outline is black, ensure we have a white border
                                        outlineColor = '#ffffff';
                                        outlineWidth = 1;
                                    }
                                }
                            }
                            if(symbol.color.a >-1 ){
                                fillOpacity=symbol.color.a;
                            }
    
                            
                            switch(geometryType){
                                case "esriGeometryPolygon":
                                    var options = { // rectangle - polygon
                                        "stroke": outlineColor,
                                        "fill" : color,
                                        "strokeWidth" : outlineWidth,
                                        "fillOpacity":fillOpacity
                                    };
                                    if(symbol.style && symbol.style == 'forwarddiagonal'){
                                        options.diagonal = 'forward';
                                    } else if(symbol.style && symbol.style == 'backwarddiagonal'){
                                        options.diagonal = 'backward';
                                    } else if(symbol.style && symbol.style == 'dash'){
                                        options.style = 'dash';
                                    }
                                    svg = tocSvgs.rectangle(options);
                                    break;
                                case "esriGeometryPoint":

                                    var pointOptions ={ // circle - point
                                        "stroke": outlineColor,
                                        "fill" : color,
                                        "strokeWidth" : outlineWidth,
                                        "fillOpacity":fillOpacity
                                    };
                                    if(symbol.style == 'square'){
                                        pointOptions.style = 'square'; //we want square
                                    }
                                    svg = tocSvgs.circle(pointOptions);
                                    break;
                                case "esriGeometryPolyline":
                                    var plineOptions = { // line - line
                                        "stroke": color,
                                        "fill" : "none",
                                        "strokeWidth" : 3,
                                    }
                                    if(symbol.style == 'dash'){
                                        plineOptions.style = 'dash'; //we want dashed lines
                                    }
                                    if(symbol.style == 'dashdot'){
                                        plineOptions.style = 'dashdot'; //we want dashed lines
                                    }
                                    svg = tocSvgs.polyline(plineOptions);
                                    break;
                                default:
                                    return false;
                            }
                            printsvg='<svg class="dyna-toc-symbol" style="width:100%;height:100%;">'+svg+'</svg>';
                            svg='<svg class="dyna-toc-symbol" symbolval="'+info.value+'" layerlabel="'+label+'" layerName="'+layerId+'" style="width:29px;height:22px;">'+svg+'</svg>';
                            
                        }
                        
                        
                        
                        if(labelLookup){
                            label = labelLookup[label] || label;
                        } else if(renderer.declaredClass == "esri.renderer.ClassBreaksRenderer"){
                            label = self._formatRangeLabel(label); // format the range string
                        }

                        if(labelStrip.length > 0){
                            for(var j=0; j<labelStrip.length; j++){
                                label = label.replace(new RegExp(labelStrip[j]), "");
                            }
                            label = label.replace(/  /g, "");
                        }
                        
                        if(modify){
                            label = modify(label);
                        }
                        
                        var full_label = before + label + after;
        
                        // build the html for the legend entry
                        tocHtml += '<div class="legendThumbnail" value="'+info.value+'">\
                                        <div>\
                                            '+svg+'\
                                        </div>\
                                        <span class="dyna-toc-label" >' +(symbolLength == 1 && !layerOptions.fromInsights? '' : full_label)+ '</span>\
                                    </div>';

                        printLegendHtml += '<div class="printLegendSymbolRow">\
                                                <div class="prtLgndIconWrapper">\
                                                    ' +printsvg + '\
                                                </div>\
                                                <div class="prtLgndLabel dyna-toc-label" contenteditable="true">' +(symbolLength == 1 ? '' : full_label)+ '</div>\
                                            </div>';
                    }
                }

                if(tocHtml == ""){
                    tocHtml = "&emsp;No Data";
                    printLegendHtml = "No Data";
                }

                self.html_store[layerId] = { // store html so other pieces of the app can access
                    "toc_html": tocHtml,
                    "print_html": printLegendHtml
                };
                
        
                var wait4Toc = setInterval(function(){ // make sure the element in the toc is present before appending it to DOM
                    
                    var $toc_elem = $('#'+layerId+'_toc-dynamic');//doesnt get set if more than ONE of the same layer in toc.
                    var $print_elem = $('#'+layerId+'_printLegendSymbology');//doesnt get set if more than ONE of the same layer in toc.

                    var $toc_elems= $('div[id='+layerId+'_toc-dynamic]');//get ALL of the toc items for that layer....works even though same id. (doesnt work for print legend)
                    
                    $toc_elems.html(tocHtml);//set
                
                    if($print_elem.length > 0){ // update print legend html
                        $print_elem.html(printLegendHtml);
                    }
                    
                    if(layerOptions.dynamicFieldTOCName == true){ // update toc and print labels if necessary
                        var fieldDisplay = renderer.attributeField;
                        var fields = layerObj.fields; 
                        for(var i=0; i<fields.length; i++){ // try to find alias if exists
                            var field = fields[i];
                            if(field.name == renderer.attributeField){
                                if(field.alias) fieldDisplay = field.alias;
                                break;
                            }
                        }



                        var name = "" + layerObj.name + " (" + fieldDisplay + ")";

                        $(
                           "#"+layerId+"_legendLabel,\
                            #"+layerId+"_printLegendCheckboxLabel,\
                            #"+layerId+"_printLegendLabel"
                        ).html(name);
                    }

                    if($toc_elem.length > 0){
                        //$toc_elem.html(tocHtml);
                        clearInterval(wait4Toc); // we can stop looking now, if this is here print will be there (if its present)
						//if filter layer, update dynamic toc to show only filtered types
                        $(".legendLayerChild[layerid=" + layerId + "]").each(function (i, ea) {
							let filterfields = $(this).attr("data-filterfield");
							let filtervalues = $(this).attr("data-filtervalue");
							let exclude = $(this).attr("data-filterexclude");
							if (!filterfields || !filtervalues) {
								//nothing set, stop
								return;
							}
							if (filterfields.indexOf("[") > -1) {
								//check for an array
								try {
									filterfields = JSON.parse(filterfields);
								} catch (e) {
									filterfields = [""];
								}
							} else {
								//just string, set as array
								filterfields = [filterfields];
							}
							if (filtervalues.indexOf("[") > -1) {
								//checking for array
								try {
									filtervalues = JSON.parse(filtervalues);
								} catch (e) {
									filtervalues = [""];
								}
							} else {
								//just a string, set as array
								filterfields = [filterfields];
							}
							for (let i = 0; i < filterfields.length; i++) {
								if (filterfields[i] == attributeField || (filterfields[i] == "UTILITY" && attributeField == "UTILCODE" && currentMap == 'hsc') ) { //if filterfield is equal to the attribute used for the renderer, fitings are special in hsc map
									let vals = filterfields.length == 1 ? filtervalues : filtervalues[i];
									$(ea).parent().find(".legendThumbnail").each(function (i2, ea2) { //each item in the toc
										let thisText = trimThis($(ea2).attr('value'));
										if ( $(ea2).find(".dyna-toc-label").length > 0 ) { //if dynamically generated
                                            if(exclude === 'true'){
                                                if (vals.indexOf(thisText) > -1) { //exclude this particular value
                                                    $(ea2).remove(); //remove it
                                                }
                                            } else {
                                                if (vals.indexOf(thisText) == -1 ) { //this toc item was not found in this filter
                                                    let found = false;
                                                    for(val of vals){ //final search for items that sort of match. for example fittings on hsc
                                                        if (thisText.indexOf(val) > -1 ) { //this toc item was found in this filter
                                                            found = true;
                                                            break;
                                                        }
                                                    }
                                                    if(!found){
                                                        $(ea2).remove(); //remove it
                                                    }
                                                }
                                            }
										}
									});
								}
							}
						});
                    } else { //check to make sure layer is actually dynamic
                        let layerDef;
                        digDeeper(layerDefs);
                        if(!layerDef || layerDef.images != 'dynamic'){
                            clearInterval(wait4Toc);
                            console.log(layerId + ' is no longer a dynamic toc item'); //remove dynamic toc for this layer
                        }

                        function digDeeper(obj){
                            for(let i =0; i<obj.length; i++){
                                if(layerDef) break;
                                let def = obj[i];
                                if(def.id == layerId){
                                    layerDef = def;
                                    break;
                                } else if(def.childLayers && def.childLayers.length > 0){
                                    digDeeper(def.childLayers);
                                }
                            }
                        }
                    }
                    
                },100);
                
                if(layerOptions.onFinish){ /// callback defined on per layer basis in dynamicTocOptions
                    layerOptions.onFinish(self.html_store[layerId]); // pass in the html store for the layer
                }
            }

            var rendererField = renderer.attributeField;

            if (layerOptions.labels && layerOptions.labels.labelField){
                var labelField = layerOptions.labels.labelField;
                 if(layerObj.url) {
                    var queryTask = new QueryTask(layerObj.url);
                    var tocQuery = new query();
                    tocQuery.outFields = [rendererField, labelField];

                    if(layerObj.supportsAdvancedQueries)
                        tocQuery.returnDistinctValues = true; // advanced param

                    tocQuery.where = layerObj.getDefinitionExpression() || "1=1";
                    tocQuery.returnGeometry = false;

                    queryTask.execute(tocQuery, function(res){
                        if(res.features.length > 0){
                            var labelLookup = self._createLabelLookup(res.features, rendererField, layerOptions.labels.labelField);
                            label_callback(labelLookup);
                        } else {
                            label_callback();
                        }
                    }, function(){
                        label_callback();
                    });
                } else if(layerObj.graphics.length > 0){ // try to query for total data - we'll take any current definition expressions into account here
                    // just get what is available on the screen 
                    var labelLookup = this._createLabelLookup(layerObj.graphics, rendererField, labelField);
                    label_callback(labelLookup);   
                }   
            } else {
                label_callback();
            }

        },

        

       applyDynamicTOC2: function(layerOptions,label1,label2){

            var layer = layerOptions.layerId;
            var layerObj = window[layer];

            var before = "";
            var after = "";
            if(layerOptions.labels){
                before = layerOptions.labels.before || ""; // get properties, intialize html vars
                after = layerOptions.labels.after || "";
            }

            var tocHtml = "";
            var printLegendHtml = "";
            var symbol = tocSvgs.colorRamp2({
                        "color": "blue"
                       });

            var fullLabel=before + label1 + after+ ' - ' + before + label2 + after;
            // build the html for the legend entry
            tocHtml += '<div class="legendThumbnail"><div class="legendColorRampDiv">'+symbol+'</div>  &nbsp;</div><div class="legendThumbnail">' + fullLabel+ '</div>';
            printLegendHtml += '<div class="printLegendSymbolRowDynamic">' + symbol + ' ' + fullLabel + '</div><br>';

            var wait4Toc = setInterval(function(){ // make sure the element in the toc is present before appending it to DOM
                
                var $print_elem = $('#'+layer+'_printLegendSymbology');
                
                if($('#'+layer+'_toc-dynamic').length > 0){
                    clearInterval(wait4Toc);
                    $('#'+layer+'_toc-dynamic').html(tocHtml);
                }
                if($print_elem.length > 0){ // update print legend html
                    $print_elem.html(printLegendHtml);
                }
            },100);

            //another temp solution....to show some stats based on active layer
            var timeFilterHTML=layerObj.name+ (layerOptions.labels.afterName || "")+'<div style="font-size:18px;">'+fullLabel+'</div>';
            layerObj.timeFilterHTML=timeFilterHTML;
            if(layerObj.visible){
                $("#timeFilterStats").html(timeFilterHTML);
            }

        },

    
        onLoad: function(){
            console.log("ran");
            for(var i=0; i<dynamicTocOptions.length; i++){
                var layerOptions = dynamicTocOptions[i];
                if(layerOptions.onLoad == false) continue;
                DynamicTOC.applyDynamicTOC(layerOptions);
            }
        },

        getLayerOptions: function(id){ // layer id
            if(!dynamicTocOptions){
                return false;
            }
            for(var i=0; i<dynamicTocOptions.length;i++){
                if(dynamicTocOptions[i].layerId == id)
                    return dynamicTocOptions[i];
            }
            return false;
        } // 

    };
    




	
	layerOrderArray = undefined

	// set fema layer opacity if it exists
	// just going to do this here so we dont have to add it to every config that contains the fema layer
	if(typeof femaFloodLayer !== 'undefined' && femaFloodLayer.opacity === 1){ // 
		femaFloodLayer.setOpacity(.7);
	}
	
    //code for LayerSwipe
	function updateSwipeLabels(){
		$('#lblLeft, #lblRight').removeClass('current');
		$('.leftSwipe').each(function(){
			if($(this).hasClass('fa-dot-circle-o')){
				$('#lblLeft p').text($(this).attr('name').slice(0, 4));
			}
		});
		$('.rightSwipe').each(function(){	
			if($(this).hasClass('fa-dot-circle-o')){
				$('#lblRight p').text($(this).attr('name').slice(0, 4));
			}
		});
 	}
	
    toggleSwipe = function toggleSwipe(bool, leftLayersArr){
        var temp, baseLayerObj;
        if (bool){ // turn on swipe layer
			swipeLayerActive = true;
			$('.parcelLayer').addClass('swipe');
            $('#goToFetch').after('<div id="swipeDiv"></div>');
			
			for(var i =0; i < leftLayersArr.length; i++){
				if(leftLayersArr[i].serviceDataType == 'esriImageServiceDataTypeProcessed'){ // for naip layer, the swipe layer needs to be set for an imagery service layer 
					var newSwipeLayer = new ArcGISImageServiceLayer(leftLayersArr[i].url, {
						id: 'swipeLayer' + i
					});
				} else {
					if(leftLayersArr[i].url.indexOf('getWMS') == -1 && leftLayersArr[i].url.indexOf('nearmap.com') == -1 ){ //AGOL service
						var newSwipeLayer = new ArcGISTiledMapServiceLayer(leftLayersArr[i].url, {
							id: 'swipeLayer' + i
						});
					} else { //WMSLayer
						var resourceInfoNew = {
							extent: new Extent(map.extent.xmin,map.extent.ymin,map.extent.xmax,map.extent.ymax,{wkid:3857}),
							getMapURL: leftLayersArr[i].url,
							layerInfos: [],
							version: "1.1.1"
						};

						var newSwipeLayer = new WMSLayer(leftLayersArr[i].url, {
							id: 'swipeLayer' + i,
							visibleLayers: leftLayersArr[i].visibleLayers,
							resourceInfo: resourceInfoNew,
							format: "jpg",
							visible: true,
							minScale: leftLayersArr[i].minScale,
							spatialReferences: [3857]
						});
					}
					
				}
				
				swipeLayers.push(newSwipeLayer);
			}
			
			for(var i=0; i<swipeLayers.length; i++){
				map.addLayer(swipeLayers[i]);
			}
            //map.addLayers(swipeLayers); // EH didn't like this?
			
			
            swipeWidget = new LayerSwipe({
                layers: swipeLayers, // array of layers
                map: map,
                type: 'vertical',
                enabled: false,
                visible: true,
                left: viewportWidth / 2,
                onload: function(){
                    $('.LayerSwipe .vertical .handle').html('<span id="swipeArrow" class="fa fa-arrows-h fa-lg"></span>');
					setTimeout(function(){ // build and set labels
				
					$('.vertical').append("<div id='lblLeft' class='swipeLbl'><p></p></div><div id='lblRight' class='swipeLbl'><p></p></div>");
						updateSwipeLabels();
					}, 200);		
					swipeLayerMouseDown = function(){
						for(layer in parcelLayers){
							try {
								window[layer].disableMouseEvents();
							} catch (e) {

							}
						}
					}
					$('.vertical').on('mousedown', swipeLayerMouseDown);
					
					swipeLayerMouseUp = function(){
						if(!($('.vertical').hasClass('dojoMoveItem'))){
							for(layer in parcelLayers){
								try {
									window[layer].enableMouseEvents();
								} catch (e) {

								}
                            }
						}
					}
					$('body').on("mouseover", swipeLayerMouseUp);
				
                }
            }, 'swipeDiv');
            swipeWidget.startup();
			swipeWidget.enable();
            
            // find highest imagery layer and place swipe div above it
			var mapLayerNames = map.layerIds;
			var swipeIndex = 0;
			for(var i = 0; i < mapLayerNames.length; i++){
				if(map.layerIds[i].search("imagery") != -1){
					swipeIndex = i;
				}
			}
			for(var i = 0; i < swipeLayers.length; i++){
				map.reorderLayer(swipeLayers[i], swipeIndex + 1)
			}
            
            
        } else { // turn off swipe layer
			swipeLayerActive = false;
			$('.parcelLayer').removeClass('swipe');
			for(var i = 0; i < swipeLayers.length; i++){
				map.removeLayer(swipeLayers[i])
			}
			
			swipeWidget.destroy();
			//parcelLayer.enableMouseEvents();
            imageryLayer.show() // turn on main imagery layer
            swipeLayers = [];
            //baseLayerObj = undefined;
            $('#swipeDiv').remove();
			$('.vertical').off("mousedown", swipeLayerMouseDown);
			$('body').off("mouseover", swipeLayerMouseUp);
        }
    }
	
	$('#toggleLayerSwipeDiv').on("click", function(){
		if($('#swipeToggle').hasClass('fa-check-square-o')){ // turn off swipe
            $('#swipeToggle').removeClass('fa-check-square-o').addClass('fa-square-o');
            $('#layerSwipeSelect').css({'pointer-events': 'none', 'opacity': '.5'})
            toggleSwipe(false);
		} else { // turn on swipe
            $('#swipeToggle').removeClass('fa-square-o').addClass('fa-check-square-o');
            $('#layerSwipeSelect').css({'pointer-events': 'inherit', 'opacity': '1'});
			var leftSideLayers = [];
			$('.leftSwipe').each(function(index){
				if($('.leftSwipe').eq(index).hasClass('fa-dot-circle-o')){
					leftSideLayers.push(window[$('.leftSwipe').eq(index).attr('layer')]);
					if(typeof($('#' + $('.leftSwipe').eq(index).attr('layer') + 'Checkbox').attr('linkedLayerIds')) !== "undefined"){
						var linkedLayersArr = $('#' + $('.leftSwipe').eq(index).attr('layer') + 'Checkbox').attr('linkedLayerIds').replace(/ /g, "");
						linkedLayersArr = linkedLayersArr.split(",");
						for(var i = 0; i < linkedLayersArr.length; i++){
							leftSideLayers.push(window[linkedLayersArr[i]]);
						}
					}
				}
			});
            toggleSwipe(true, leftSideLayers);
		}
	});
	
	// Both checked and not checked need to be here, since we are attaching the handler to the dom nodes.
	$('.legendCheckbox.fa-circle-o, .legendCheckbox.fa-dot-circle-o').on("click", function(e){
		var id = $(e.currentTarget).attr('layerid');
		//id = id.replace('Checkbox', '');
		$('.rightSwipe').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
		$('.rightSwipe').each(function(index){
			if($('.rightSwipe').eq(index).attr('layer') === id){
				$('.rightSwipe').eq(index).removeClass('fa-circle-o').addClass('fa-dot-circle-o');
			}
		});
	});
    // end of Layer Swipe Code
	
	function getDefaultParcelTitle(graphic) {
        var pinStr = parcelLayers[graphic._layer.id].pinField;
		
		var attributes = graphic.attributes;
		if(attributes[pinStr] != 'ROW' && attributes[pinStr] != '' && attributes[pinStr] != ' '){
			return graphic.attributes[pinStr];
		} else {
			return "No info";
		}
    }
	
	function getDefaultParcelContent (graphic) {
		$('#showDetails').show();//parcel popup show details link
		var pinStr = parcelLayers[graphic._layer.id].pinField;
		
		if(dataPaneExpandoOpen === true){
			$('#showDetails').html('Hide Details');
		} else{
			$('#showDetails').html('Show Details');
		}
		if ($('#generalDetailsScrollPane').hasClass('hidden')) {
			$("#generalDetailsOverlay").fadeIn(function(){
				$("#generalDetailsWrapper").show();
				$('.dataScrollPane').addClass('hidden');
				$('#generalDetailsScrollPane').removeClass('hidden');
				$("#generalDetailsOverlay").fadeOut();
			});
		}
		// close highlight layer popup
		getInfoWinParcel(); // stores any selected parcel from the parcel layer for further use. placing it here ensures that there is no discrepency between the infowindow parcel, parcel information datapane and the parcel represented by infoWinParcel itself.
		dijitPopup.close(popDialog);
		var attributes = graphic.attributes;
		//var outputStr = "OBJECTID: " + attributes.OBJECTID;
		if(attributes[pinStr].indexOf('ROW') === -1 && attributes[pinStr].indexOf('--') === -1 && attributes[pinStr] != '' && attributes[pinStr] != ' '){
			var outputStr = getPopupData(graphic, attributes[pinStr]);// pinStr is county specific. Some counties might use TAX_ID, where another might use TaxID.
			return outputStr;
		} else {
			getParcelData(null);
			return "This is not a Parcel";
		}		
    }

	function getInfoWinParcel(){
		var parcelVerify = map.infoWindow.getSelectedFeature(); // compares this event parcel with current infoWinParcel
		if(parcelVerify == infoWinParcel){
			return; // zooming in and out was causing multiple events on the same parcel even though the infowindow had not been moved. the event options for the infowindow are limited.....
		}
		infoWinParcel = parcelVerify;
		var parGeo = infoWinParcel.geometry;
		var repSymbol = new SimpleLineSymbol(
		SimpleLineSymbol.STYLE_SOLID, new Color([255, 0, 0]), 2);
		infoWinGraphic = new Graphic(parGeo, repSymbol);
	}
	
	// helper functions for when you need the infowindow to go away...and....re-appear...mysterious!
	infoWindowHide = function(){
		map.infoWindow.hide();
		map.infoWindow.clearFeatures();
		//parcelLayer.infoTemplate = "";
		for(layer in parcelLayers){
			window[layer].setInfoTemplate(null);
		}
	}
	
	infoWindowShow = function(){ // be careful with this one, it will open the infowindow on whatever parcel was last clicked on regardless of whether the infowindow was closed by the user 
		//parcelLayer.infoTemplate = template;
		for(layer in parcelLayers){
			window[layer].setInfoTemplate(parcelLayers[layer].infoTemplate);
		}
		map.infoWindow.setFeatures([infoWinParcel]);
		map.infoWindow.show(infoWinParcel.geometry.getCentroid());
	}

	
	// Add GeoLocation functionality
	function getLocation () {
		if (!$('#setLocation').hasClass('enabled')){
				// check if browser supports GeoLocation
				if (navigator.geolocation){
					var geoOptions = {
						enableHighAccuracy: true, 
						maximumAge: 20000,
						timeout: 30000
					};
					//if(userAgent == 'other'){//just following/watching all the time now .. :|
					//	watch = navigator.geolocation.getCurrentPosition(showPosition, geoError, geoOptions);
					//} else {
						watch = navigator.geolocation.watchPosition(showPosition, geoError, geoOptions);
					//}			
				} else {
					showAlert('Notice:', 'Sorry, your browser does not support Geolocation.');
				}
			
		} else {
			//remove geolocation graphic and classes
			$('#setLocation, #setLocationAlt').removeClass('enabled');
			geoLocationLayer.remove(locGraphic);
			geoLocationLayer.remove(accuracyGraphic);
			navigator.geolocation.clearWatch(watch);

					}
	}
	function showPosition(position){
		// Create AGO Point object
		currentPosition = position;
		var pt = new Point(position.coords.longitude, position.coords.latitude); // create marker symbol geometry
		var accuracy = position.coords.accuracy; // get accuracy of coordinates in meters
		var circleGeometry = new Circle(pt,{ // create accuracy circle geometry
			"radius": accuracy,
			geodesic: true
		});
		//Check if first time on watch position, if so make new point feature
		if (!$('#setLocation').hasClass('enabled')){
			// Create symbol - circle, teal/blue
			//var pointSymbol = new PictureMarkerSymbol(gpsPointIconB64, 30, 30);
			var pointSymbol =new SimpleMarkerSymbol(SimpleMarkerSymbol.STYLE_CIRCLE,16,new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID, new Color([255,255,255,.7]), 2),new Color([25,190,253,.85]));
			
			
			var circleSymbol = new SimpleFillSymbol().setColor(new Color([25,140,253,.15]));
			circleSymbol.setOutline(new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID, new Color([255,255,255,.15]), 4));
			// Create location Graphic and add to map object
			locGraphic = new Graphic(pt, pointSymbol);
			accuracyGraphic = new Graphic(circleGeometry, circleSymbol);
			geoLocationLayer.add(accuracyGraphic);
			geoLocationLayer.add(locGraphic);
			map.centerAt(pt);
			$('#setLocation, #setLocationAlt').addClass('enabled');
		} else {
			//If not first time just update geometry 
			accuracyGraphic.setGeometry(circleGeometry);
			locGraphic.setGeometry(pt);
		}
			}
	
	function geoError(error){
		//Handle geolocation functionality denied 
		if(error.code == 1){
            showAlert('Notice:', 'Location permission denied. Please see help section for instructions on enabling browser location access.');
		}
	}
	
	// run geolocation functionality if button is pressed
	$('#setLocation, #setLocationAlt').on("click", function() {
		getLocation();
	});

	
	/////////////////////////////////////// Search AREA !!!!!!! ////////////////////////////////////////////
	// set county search selection options. Build parcel layer (county) select dropdown. Only show if more than one parcel layer.
var searchCountyOptionsStr = "", searchCountiesLength = 0, searchLayer, parOwnerField = "", parStreetField = "";
window.searchData2 = [];
var searchDataFromLayer = [], parUrlField, parPinField;

for(var layer in parcelLayers){
	searchCountyOptionsStr += '<li class="searchCountyOption" data-layer="' + parcelLayers[layer].id + '">' + parcelLayers[layer].displayName + '</li>';
	searchCountiesLength++;
}

//removing recently viewed from any search option other than parcel
if($('#recentParcelHeader').length == 1){ //check to see if recents have been removed already ie non LRP parcel layer
	var recentParcelHeader = $('#recentParcelHeader'); //storing the jquery object so that it can be detached and reloaded later	
	$('#searchType').on("click", function( event ) {
			if (event.target.parentNode.id == 'parcelSearchRadio'){ // looking only for parcel button
				if ($('#recentParcelHeader').length == 0) { //if recent parcels not currently on the screen, add and refresh
					$('#recentContainer').before(recentParcelHeader);
					if($("#searchControlsAccordion").hasClass('ui-accordion')){
						$("#searchControlsAccordion").accordion('refresh');						
					}
				}
			} else if (event.target.parentNode.id != 'searchType' && event.target.parentNode.id != 'searchDivScrollPane'){
				if ($('#recentParcelHeader').length == 1) { //if recent parcels currently on the screen, detach and refresh
					recentParcelHeader.detach();
					if($("#searchControlsAccordion").hasClass('ui-accordion')){
						$("#searchControlsAccordion").accordion('refresh');						
					}
				}
			}
	});
}



if(searchCountiesLength > 0){
	// create the parcel layer select dropdown
	$('#searchCountyDropdown').html(searchCountyOptionsStr);

	// create click handlers
	$('.searchCountyOption').on("click", function(){
		var name = $(this).html();
		name = name.charAt(0).toUpperCase() + name.slice(1);
		$('#searchSelectedCounty').html(name);
		$('#searchSelectedCounty').attr('data-layer', $(this).attr('data-layer'));
		searchLayer = $('#searchSelectedCounty').attr('data-layer');

		$('.pinInputDiv:visible').fadeOut(400, function(){
			$('#' + searchLayer + 'PinInputs').fadeIn();
		});
		
		parUrlField = parcelLayers[searchLayer].url;
		parPinField = parcelLayers[searchLayer].pinField;
 
		$("#OName, #Street").val("");
		$("#OName, #Street, label[for='OName'], label[for='Street']").removeClass('disabledButton');

		// next two fields are for Non LRP search
		if(typeof parcelLayers[searchLayer].lrpMapStr == "undefined" || !parcelLayers[searchLayer].lrpMapStr){
			if(typeof(parcelLayers[searchLayer].parcelOwnerField) !== "undefined"){
				parOwnerField = parcelLayers[searchLayer].parcelOwnerField;
			} else {
				$("#OName, label[for='OName']").addClass('disabledButton');
			}
			
			if(typeof(parcelLayers[searchLayer].parcelStreetField) !== "undefined"){
				parStreetField = parcelLayers[searchLayer].parcelStreetField;
			} else {
				$("#Street, label[for='Street']").addClass('disabledButton');
			}
		}

	});
	
	// set default search county as first in the list
	$('.searchCountyOption').eq(0).trigger('click'); // set selected county as first county option

	// if display name isn't set in config (due to only one parcel layer), just use beginning of parcel layer name
	if(typeof(selectedCounty) === "undefined"){
		selectedCounty = $('#searchSelectedCounty').attr('data-layer').replace("ParcelLayer", "");
	}


	// show the select box if it's a multi parcel layer
	if(searchCountiesLength > 1){
		$('#searchCountySelectDiv').removeClass('hidden');
	}
}

// Search Controls Section 
	$('.pinInput').on('keydown', function(e){
		if((e.keyCode >= 35 && e.keyCode <= 105) || e.key == "Enter" || e.key == "Tab" || e.keyCode == 8 || e.key == "Delete"){
			return;
		} else if($(this).hasClass('pinInput-full') && (e.keyCode == 189 || e.keyCode == 32)) {
			return;
		} else {
			e.preventDefault();
		}
	});
	$('.pinInput').on('keyup', function(e){
		if((e.keyCode >= 35 && e.keyCode <= 105)){
			if($(this).val().length == this.maxLength){
				$(e.currentTarget).next().trigger("focus");
			}
		}
	});

	$('.excSearch').on("click", function(){
		excSearch();
	});

	$('#searchControlsAccordion').on('keydown', '.searchInput, .pinInput, .searchInputWide', function(e){
		if(e.which == 13){
			excSearch();
		}
	});

	
	// search button function
	excSearch = function(){

		$("#search-result-details-container").addClass("hidden"); // well just reset this here
		var searchString="";
		var allowSearch = false;

		if($("#intersectionSearchButton").hasClass("fa-dot-circle-o")){
			if ($("#rd1").val() !== "" &  $("#rd2").val() !== ""){
				allowSearch = true;
			} else{
				//alert("nothing to search for?")
			}
			if(allowSearch === true){
				var road1 = $("#rd1").val();
				var road2 = $("#rd2").val();
				queryIntersectCount = 0;
				queryIntersect(road1,road2,queryIntersectCount);
			}
			
		} else if($("#addressSearchButton").hasClass("fa-dot-circle-o")){
			
			if($("#houseNumber").val() !== "" || $("#aStreet").val() !== ""  || $("#city").val() !== "" ){
				allowSearch = true;
			} else {
				//alert("nothing to search for?");
			}
			if(allowSearch === true){
				var houseNumber = $("#houseNumber").val();
				if($('#houseNumber').attr('useUppercase')){
					houseNumber = houseNumber.toUpperCase();
				}
				var aStreet = $("#aStreet").val();
				var city = $("#city").val(); // if not midland this will be '' which is fine it just won't show up in the html search results
				queryAddressCount = 0;
				queryAddress(houseNumber,aStreet,city,queryAddressCount);
				
			}

		} else if( $("#veteransSearchButton").hasClass("fa-dot-circle-o")){
            
            if( ($("#veteranName").val() !== "" || $("#veteranNameL").val() !== "") || $("#veteranBranch").val() != "unknown" || $("#veteranWar").val() != "unknown" || $("#veterancemetery").val() != "unknown"  ){
                allowSearch = true;
            }
            if(allowSearch){
                var name = $("#veteranName").val();
				var nameL = $("#veteranNameL").val();
                var branch = $("#veteranBranch").val();
                var war = $("#veteranWar").val();
                var cemetery = $("#veteranCemetery").val();
                queryVeteran(name, nameL, branch, war, cemetery);
            }
        
        } else if( $("#coordinateSearchButton").hasClass("fa-dot-circle-o")){

            if( $("#DegreeX").val() !== "" && $("#DegreeY").val() !== ""){
                allowSearch = true;
            }
			if(allowSearch){
				var xCoord = $("#DegreeX").val();
				var yCoord = $("#DegreeY").val();
				xCoord = xCoord.replace(/[^0-9-.]/g, " ");
				yCoord = yCoord.replace(/[^0-9-.]/g, " ");
				xCoord = xCoord.trim(); // if the user entered standard lat long coordinate points calcualtions end here
				yCoord = yCoord.trim();
				if(!(parseInt(xCoord) && parseInt(yCoord))){
					showAlert('Notice:', 'Make sure coordinates are valid and within the extent of the map, consult help for proper use.');
				} else if($('.stPlaneCheck').hasClass('fa-check-square-o')){
					let coordSys = $('#stPlaneSelection').attr('coordsys');
					if(!coordSys){
						convertAltCoordToDecimal([xCoord,yCoord]); // no need to call coord search, convertAltCoordToDecimal() calls it
					} else {
						convertAltCoordToDecimal([xCoord,yCoord],coordSys); // no need to call coord search, convertAltCoordToDecimal() calls it
					}
				} else { // check for possible DMS match
					var dmsPattern = /^\s*(\-?\d{0,3}\s+\d{0,2}\s+?\d{0,2}?\.?\d+?)$/; // DMS pattern to test against
				
					if (dmsPattern.test(xCoord)){ // if patter is matched then both coordinates are updated
						xCoord = convertDMStoDecimal(xCoord);
					}
					if (dmsPattern.test(yCoord)){
						yCoord = convertDMStoDecimal(yCoord);
					}
					var pt = new Point(parseFloat(xCoord), parseFloat(yCoord), new SpatialReference({ wkid:4326 }));
					coordSearch(pt);
                }
                
				
			}
		} else if ($("#subdivisionSearchButton").hasClass("fa-dot-circle-o")){
			if($("#subNumber").val() !== "" || $("#subName").val() !== ""  || $("#subMunicipal").val() !== ""){
				allowSearch = true;
			}
            if(allowSearch){ 
                var subNumber = $("#subNumber").val();
                var subName = $("#subName").val();
                var municipality = $("#subMunicipal").val();
                querySubdivision(subNumber, subName, municipality);
            }		
		} else if ($("#parcelSearchButton").hasClass("fa-dot-circle-o")){
			/*$("#parcelSearchExpando input").each(function(index){
				if($("#parcelSearchExpando input:not(.multiselect-container input)").eq(index).val() !== ""){
					allowSearch = true;
				}
			}); */

			let ownerName = $("#OName").val();
			let street = $("#Street").val();
			if(parUrlField.indexOf('gis.allegancounty.org/server/rest/services/') > -1){
				if(ownerName){
					ownerName = ownerName.toUpperCase();
				}
				if(street){
					street = street.toUpperCase();
				}
			}

			$('.pinInput:visible').each(function(index){
				if($(this).val() !== ""){
					allowSearch = true;
				}
			});
			if(street !== "" || ownerName !== "" || ($("#parcelUnit").val() && $("#parcelUnit").val().length !== 0)){
				allowSearch = true;
			}

			var parcelHasLRP = checkForLRP(searchLayer);
			
            if (parcelHasLRP){
                searchString = "ws/PSsearch2.php?";
			}
			
			if(allowSearch === true){
				var streetStr = street, StrNum = "", StrName = "", strObj;
				strObj = streetStr.split(" ");
				if(strObj[0].charCodeAt(0) > 47 && strObj[0].charCodeAt(0) < 58){
					StrNum = strObj[0];
					for(i=1; i < strObj.length; i++){
						StrName += strObj[i] + " ";
					}
				} else {
					StrName = streetStr;
				}
				
				
				if (parcelHasLRP){
					var pinIdStr;
					// if optional pin entries present (Not to confuse with multiple parcel layers. This is for single parcel layer with more than one pin format)
					// note, pin inputs for multi parcel search go by which inputs are visible.
					if($('.pinOptionRadio').length){ 
						var pinInputContainerId = $('.pinOptionRadio.fa-dot-circle-o').attr('id').replace("pin", "pinInput");
						$('#'+pinInputContainerId+' .pinInput:visible').each(function(index){
							pinIdStr = pinIdStr =  "Pin" + (index + 1); //$('#'+pinInputContainerId+' .pinInput:visible').eq(index).attr('pinVal');
							searchString += "&" + pinIdStr + "=" + $('#'+pinInputContainerId+' .pinInput:visible').eq(index).val();
						});
					} else {
						$('.pinInput:visible').each(function(index){
							pinIdStr =  "Pin" + (index + 1); //$('.pinInput:visible').eq(index).prop('id'); // we used to have Id's on pin inputs... no longer needed.
							searchString += "&" + pinIdStr + "=" + $('.pinInput:visible').eq(index).val();
						});
					}
					searchString += "&OName=" + removeSpecialChars(ownerName, ' ');
					searchString +=	"&StrNum=" + StrNum + "";
					searchString +=	"&StrName=" + StrName + "";

					if(currentMap === "saginaw"){
						var parcelUnit = $("#parcelUnit").val();
						if(parcelUnit){
							for(var i in parcelUnit){
								searchString += "&unit[]=" + parcelUnit[i];
							}
						}
					}
					
					
					searchString += "&Map=" + parcelLayers[searchLayer].lrpMapStr;
					
					console.log(searchString);

                    getUserSearchData(searchString);
                } else {
                    // Non-LRP PARCEL SEARCH
					let amalgamURL = false;
					if(parUrlField.indexOf('/geoservices/') > -1){
						amalgamURL = true;
					}
                    if ($(".pinInput:visible").eq(0).val() !== ""){ 
                        var pin = '';

						$('.pinInput:visible').each(function(index){
							if($('.pinInput:visible').eq(index).val()){
								let spacer = '-';
								if($('.pinInput:visible').eq(index).hasClass('hideDash')){
									spacer = ' ';
								} else if($('.pinInput:visible').eq(index).hasClass('addDot')){
									spacer = '.'
								}
								pin += $('.pinInput:visible').eq(index).val() + spacer;
							} else {
								return false;
							}
						});
						
						// remove last dash
						pin =pin.substring(0, pin.length - 1);

                        if (ownerName !== ""){
                            if (street !== ''){
                                // pin, owner, and street is filled in
                                searchString += parPinField + " LIKE '%" + pin + "%' AND ";
                                searchString += parOwnerField  + " LIKE '%" + ownerName + "%' AND ";
                                var streetVal = street;
                                streetVal =streetVal.replace(/ /g, "%");
                                searchString += parStreetField + " LIKE '%" + streetVal + "%'";
                            } else {
                                //pin and owner filled in 
                                searchString += parPinField + " LIKE '%" + pin + "%' AND ";
                                searchString += parOwnerField + " LIKE '%" + ownerName + "%'";
                            }
                        } else {
                            if (street !== ''){
                                // pin and street fill in
                                searchString += parPinField + " LIKE '%" + pin + "%' AND ";
                                var streetVal = street;
                                streetVal =streetVal.replace(/ /g, "%");
                                searchString += parStreetField + " LIKE '%" + streetVal + "%'";
                            } else {
                                // only PIN filled in
                                searchString += parPinField + " LIKE '%" + pin + "%'";
                            }
                        }
                    } else {
                        if (ownerName !== ""){
                            if (street !== ""){
                                // owner name and street filled
                                searchString += parOwnerField + " LIKE '%" + ownerName + "%' AND";
                                var streetVal = street;
                                streetVal =streetVal.replace(/ /g, "%");
                                searchString += parStreetField + " LIKE '%" + streetVal + "%'";
                            } else {
                                // owner name only
                                searchString += parOwnerField + " LIKE '%" + ownerName + "%'";
                            }
                        } else {
                            // only street filled
                            var streetVal = street;
                            streetVal =streetVal.replace(/ /g, "%");
                            searchString += parStreetField + " LIKE '%" + streetVal + "%'";
                        }
                    }
                    $('#searchControlsAccordion').children().eq(2).trigger('click');
					//fix search for amalgam urls
					if(amalgamURL){
						let whereArr = searchString.split('AND');
						let newWhere = '';
						for(let j = 0; j < whereArr.length; j++){
							let w = whereArr[j];
							let t = w.trim(' ');
							t = t.split(' ');
							for(let i=0; i< t.length; i++){
								if(i==0){ //add double quotes around field
									t[i] = `"` + t[i] + `"`;
								} else if(i==1){ //change LIKE to ILIKE
									t[i] = 'ILIKE'
								}
							}
							if(j > 0){ //add and back, after the first array item
								newWhere += ' AND ';
							}
							newWhere += t.join(' ');
						}
						searchString = newWhere;
					}
                    //show spinner
                    $('#searchSpinner').removeClass('hidden');
                    if (typeof(Offline) != 'undefined' && Offline.state == 'down'){
                        searchDataStr = '';
                        searchDataStr += '<div id="searchRecordsFound">Cannot search for parcels while offline</div>';
                        showSearchResults(searchDataStr);
                        $('#searchSpinner').addClass('hidden');
                    } else {
                        var queryTask = new QueryTask(parUrlField);
						var parQuery = new query();
						parQuery.outSpatialReference = new SpatialReference({ wkid:102100 });
                        parQuery.returnGeometry = true;
                        parQuery.where = searchString;
                        parQuery.outFields = ["*"];
                        console.log(parQuery);
                        queryTask.execute(parQuery, populateSearchFromLayer);
                    }
                    
                    
                }
				$(this).trigger('blur');
				$("#searchResultsInner").trigger("focus");
				
			} else {
				//alert("Nothing to search for?");
			}
		}
	};
	
	$('.stPlaneCheck').on("click", function(){
		$('#DegreeX').val('');
		$('#DegreeY').val('');
		if($('.stPlaneCheck').hasClass('fa-square-o')){
			$('.stPlaneCheck').removeClass('fa-square-o').addClass('fa-check-square-o');
			let coordSys = $('#stPlaneSelection').attr('coordsys');
			if(!coordSys){
				$('#coordXLabel').text('Easting (Intl Feet)');
				$('#coordYLabel').text('Northing (Intl Feet)');
			} else {
				let xVal = $('#stPlaneSelection').attr('xval') ? $('#stPlaneSelection').attr('xval') : 'x';
				let yVal = $('#stPlaneSelection').attr('yval') ? $('#stPlaneSelection').attr('yval') : 'y';
				$('#coordXLabel').text(xVal);
				$('#coordYLabel').text(yVal);
			}
		} else {
			$('.stPlaneCheck').removeClass('fa-check-square-o').addClass('fa-square-o');
			$('#coordXLabel').text('Longitude');
			$('#coordYLabel').text('Latitude');
		}
	});

	function convertAltCoordToDecimal(coordArr,coordSys){ // using proj4js converst stateplane coordinate to wgs84 coordinate point which the search needs to operate
		/*try{
			clearInterval(waitForProj4);
		} catch(err){}
		var stopTransformation = setTimeout(function(){ // just in case of infinite loop
			clearInterval(waitForProj4);
		},2000);
		var waitForProj4 = setInterval(function(){ // proj4 can be a pain, this makes sure that the coordinate is returned before proceding 
			//var statePlanePt = new Proj4js.Point(coordArr[0], coordArr[1]); 
			var pt = new Point(coordArr[0], coordArr[1], statePlaneSR);
			console.log(pt);
			var wgs84Trans = projectGeometry(pt, map.spatialReference);
			console.log(wgs84Trans);
			//var wgs84Trans = Proj4js.transform(stateDef, wgs84Def, new Proj4js.Point(coordArr[0], coordArr[1]));
			if(wgs84Trans.x != coordArr[0] && wgs84Trans.y != coordArr[1]){
				coordSearch(wgs84Trans.x, wgs84Trans.y);
				clearTimeout(stopTransformation);
				clearInterval(waitForProj4);
			}
		},5);*/
		// with new proj stuff we just need this:
		try {
			let newCoordSys = statePlaneSR;
			let useProj4 = false;
			if(coordSys){
				newCoordSys = new SpatialReference({wkid: coordSys});
				useProj4 = true;
			}
			var pt = new Point(parseFloat(coordArr[0]), parseFloat(coordArr[1]), newCoordSys);
			coordSearch(pt,useProj4);
		} catch(e) {
			showAlert("Notice", "Invalid coordinate format");
		}
	}
	
	
	function convertDMStoDecimal(coord){ //COORDINATE SEARCH ONLY  takes single x or y portion of coordinate not whole coordinate
		
		var dmsSplit = coord.split(" ");
		dmsSplit = dmsSplit.filter(function(str) {
			return /\S/.test(str);
		});
		var d = parseFloat(dmsSplit[0]);
		if (d < 0){
			var neg = -1;
		} else {
			var neg = 1;
		}
		var m = parseFloat(dmsSplit[1]) * neg;
		var s = parseFloat(dmsSplit[2]) * neg;
		var DD = d + m/60 + s/3600;
		return DD;
		
	}
	

	
	//var cleanedSearchData = [];
	var xhr2 = (window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP"));
	function getUserSearchData(url){
		$('#searchControlsAccordion').children().eq(2).trigger('click');
		//show spinner
		$('#searchSpinner').removeClass('hidden');
		// AJAX request
		xhr2.onreadystatechange = XHRhandler2;
		xhr2.open("GET", url, true);
		xhr2.send(null);
	}

    getUserSearchDataNewPage = function(varString){
        var url = 'ws/PSsearch2.php?' + varString;
		$('#searchControlsAccordion').children().eq(2).trigger('click');
        // fade out current results
        $('.searchResultsTable').fadeOut();
		//show spinner
		$('#searchSpinner').removeClass('hidden');
		// AJAX request
		xhr2.onreadystatechange = XHRhandler2;
		xhr2.open("GET", url, true);
		xhr2.send(null);
		xhr2.onerror = function(e) {
			queryTimeout();
		};
		setSearchResultsHandlers();
	};
    getLayerSearchNewPage = function(currentPg, totalPages, step){
        var newLower, newUpper, pageDisplay;
        searchDataStr = '';
        currentPage = currentPg;
        
        // DONT REPEAT CODE FUNCTIONS
        function searchDataLoop(lower, upper){
            var string = '';
            for (var i=(lower - 1); i < (upper - 1); i++){
				var ownerStr = searchDataFromLayer[i].attributes[parOwnerField] ? "<br>" + searchDataFromLayer[i].attributes[parOwnerField] : "";
				var addressStr = searchDataFromLayer[i].attributes[parStreetField] ? "<br>" + searchDataFromLayer[i].attributes[parStreetField] : "";;
                string +=   '<tr>' +
                                '<td><span id="recordLink' + i + '" class="fa fa-map-marker fa-2x recordLink" title="Zoom to Parcel" onclick="goToParcel(\''+ searchDataFromLayer[i].attributes[parPinField] + '\')"></span></td>' +
                                '<td id="recordData' + i + '" class="recordData"><a href="#" onclick="parcelContent(\''+ searchDataFromLayer[i].attributes[parPinField] + '\')">' + searchDataFromLayer[i].attributes[parPinField] + ownerStr + addressStr + '</a></td>' +
							'</tr>';
                }
            return string;
        }
        
        // LAST PAGE FUNCTION
        function lastPage(){
            currentPage = totalPages;
            newLower = ((totalPages - 1) * 25) + 1;
            if (searchDataFromLayer.length == 1000){
                newUpper = 1000;
            } else {
                newUpper = (searchDataFromLayer.length % 25) + newLower - 1;
            }
            
            pageDisplay = "<div id='parLastPage'><a title='First page' onclick='getLayerSearchNewPage(1 ," + totalPages + ",\"first\")' class='fa fa-fast-backward'></a> <a title='Previous page' onclick ='getLayerSearchNewPage(" + (totalPages - 1) + "," + totalPages + ", \"prev\")' class='fa fa-backward'></a></div><div id='parPageDisplay'> Page " + totalPages + " of " + totalPages + " pages<br>Showing " + newLower + "-" + newUpper + " of " + searchDataFromLayer.length + " results </div><div id='parNextPage'><span class='disabled fa fa-forward'></span> <span class='disabled fa fa-fast-forward'></span></div></pagedisplay>"

            searchDataStr += '<div id="searchRecordsFound">' + pageDisplay + '</div>';
			searchDataStr += '<div id="searchResultsScrollPane">';
            searchDataStr +=	'<table class="table searchResultsTable">' +
                                        '<tbody>';
            searchDataStr += searchDataLoop(newLower, newUpper);
            searchDataStr +=	'</tbody>' +
                                    '</table>'; 
                
        } // end of last page function

        //FIRST PAGE FUNCTION
        function firstPage(){
            currentPage = 1;
            newUpper = 25;
            newLower = 1;
            pageDisplay = "<div id='parLastPage'><span class='disabled fa fa-fast-backward'></span> <span class='disabled fa fa-backward'></span></div><div id='parPageDisplay'> Page " + currentPg + " of " + totalPages + " pages<br>Showing " + newLower + "-" + newUpper + " of " + searchDataFromLayer.length + " results </div><div id='parNextPage'><a title='Next page' onclick='getLayerSearchNewPage(" + (currentPg + 1) + "," + totalPages + ", \"next\")' class='fa fa-forward'></a> <a title='Last page' onclick='getLayerSearchNewPage(" + currentPg + "," + totalPages + ",\"last\")' class='fa fa-fast-forward'></a></div>"

            searchDataStr += '<div id="searchRecordsFound">' + pageDisplay + '</div>';
			searchDataStr += '<div id="searchResultsScrollPane">';
            searchDataStr +=	'<table class="table searchResultsTable">'+
										'<tbody>';
            searchDataStr += searchDataLoop(newLower, newUpper);
            searchDataStr +=	'</tbody>' +
									'</table>'; 

        } // end of first page function
        
        
        if (step == 'last'){
            // go to last page
            lastPage();
        } else if (step == 'first') {
            // go to first page
            firstPage();
        } else {
            if (currentPg == totalPages){
                lastPage();
            } else if (currentPg == 1){
                firstPage();
            } else {
            // should handle all other prev and next page request
                newUpper = currentPg * 25;
                newLower = newUpper - 24;
                pageDisplay = "<div id='parLastPage'><a title='First page' onclick='getLayerSearchNewPage(1 ," + totalPages + ",\"first\")' class='fa fa-fast-backward'></a> <a title='Previous page' onclick ='getLayerSearchNewPage(" + (currentPg - 1) + "," + totalPages + ", \"prev\")' class='fa fa-backward'></a></div><div id='parPageDisplay'> Page " + currentPg + " of " + totalPages + " pages<br>Showing " + newLower + "-" + newUpper + " of " + searchDataFromLayer.length + " results </div><div id='parNextPage'><a title='Next page' onclick='getLayerSearchNewPage(" + (currentPg + 1) + "," + totalPages + ", \"next\")'\' class='fa fa-forward'></a> <a title='Last page' onclick='getLayerSearchNewPage(" + totalPages + "," + totalPages + ", \"last\")' class='fa fa-fast-forward'></a></div>";

                searchDataStr += '<div id="searchRecordsFound">' + pageDisplay + '</div>';
				searchDataStr += '<div id="searchResultsScrollPane">';
                searchDataStr +=	'<table class="table searchResultsTable">' +
                                    '<tbody>';
                searchDataStr += searchDataLoop(newLower, newUpper);
                searchDataStr +=	'</tbody>' +
                                '</table>' +
								'</div>';
            }                
        }
        showSearchResults(searchDataStr);
    }; // end of getLayerSearchNewPage function
	
	// handle response
	var isRunning = false;
	function XHRhandler2() {
		if (xhr2.readyState == 4) {
			if(xhr2.responseXML === null){
				$('#searchSpinner').addClass('hidden');
				var searchDataStr = '<p id="searchRecordsFound"><span id="searchRecordsFoundVal"> 0 </span> &nbsp records found from search</p>';
				showSearchResults(searchDataStr);
			} else {
				searchData2 = XML2jsobj(xhr2.responseXML.documentElement);
				// hide spinner when done loading
				$('#searchSpinner').addClass('hidden');
				populateUserSearchData();
				if(isRunning === false){
					//populateUserSearchData();
					isRunning = true;
					setTimeout(function(){
						isRunning = false;
					},5000);

				}
			}
			
		}
	}

	var searchDataStr = "", totalPages = "", currentPage = "";
	function populateUserSearchData(){
		searchDataStr = "";
		var cleanedAddress = "";
		if(typeof(searchData2.totalpages) == 'undefined'){
			searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal"> 0 </span> &nbsp records found from search</p>';
		} else {
			if(typeof(searchData2.record.length) === "undefined"){ //One search result is not an array, which would break the following loop. Just type-cast in this case.
				searchData2.record = [searchData2.record];
			}
			totalPages = searchData2.totalpages;
			currentPage = searchData2.currentpage;
			searchDataStr += '<div id="searchRecordsFound">' + searchData2.pagedisplay + '</div>';
			searchDataStr += '<div id="searchResultsScrollPane">';
            searchDataStr +=	'<table class="table searchResultsTable">' +
										'<tbody>';
			for (i=0; i < searchData2.record.length; i++){
				cleanedAddress = searchData2.record[i].PropertyAddress.replace(".0 ", " "); // cleans bay county address garbage
				if(cleanedAddress === "  , "){ // don't bother showing a comma if that's all the data is for this field.
					cleanedAddress = "";
				}
				searchDataStr +=				'<tr>' +
												// this calls goToParcel()
												'<td><span id="recordLink' + i + '" class="fa fa-map-marker fa-2x parcelLink" title="Zoom to Parcel" data-pin="' + searchData2.record[i].ParcelNumber + '" data-propclass="' + searchData2.record[i].PropertyClass + '" data-layerName="' + searchLayer + '"></span></td>' +
												// this calls parcelContent()
												'<td id="recordData' + i + '" class="parcelData" data-pin="' + searchData2.record[i].ParcelNumber + '" data-propclass="' + searchData2.record[i].PropertyClass + '" data-layerName="' +searchLayer + '">' + searchData2.record[i].ParcelNumber + '<br>' + searchData2.record[i].OwnerName + '<br>' + cleanedAddress + '</td>' +
											'</tr>';
			}
			searchDataStr +=			'</tbody>' +
									'</table>' +
								'</div>';
		} 

		showSearchResults(searchDataStr);
	}
	var parcelLinkHandler, parcelDataHandler;
    window.setSearchResultsHandlers = function setSearchResultsHandlers(){
		if(typeof(parcelLinkHandler) !== "undefined"){
			parcelLinkHandler.off('click');
		}
		if(typeof(parcelDataHandler) !== "undefined"){
			parcelDataHandler.off('click');
		}
		
		parcelLinkHandler = $('.parcelLink, .parcelDataNoLrp').on("click", function(evt){
			var pin, layer;
			pin = $(this).attr('data-pin');
			layer = $(this).attr('data-layerName');
			propclass = $(this).attr('data-propclass');
			goToParcel(pin, layer,propclass);
		});
		
		parcelDataHandler = $('.parcelData').on("click", function(evt){
			var pin, layer;
			pin = $(this).attr('data-pin');
			layer = $(this).attr('data-layerName');
			propclass = $(this).attr('data-propclass');
			parcelContent(pin, layer,propclass);
		});
	};

	
    function populateSearchFromLayer(results){
        // DATA PULLED FROM PARCEL! NO LRP!!
        console.log("results: ", results);
        searchDataFromLayer = results.features;

        searchDataStr = '';
        var resultCount = searchDataFromLayer.length;
        if (resultCount === 0){
            searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal"> 0 </span> &nbsp records found from search</p>';
        } else if (resultCount == 1){
			var ownerStr = searchDataFromLayer[0].attributes[parOwnerField] ? "<br>" + searchDataFromLayer[0].attributes[parOwnerField] : "";
			var addressStr = searchDataFromLayer[0].attributes[parStreetField] ? "<br>" + searchDataFromLayer[0].attributes[parStreetField] : ""; 
            searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal">' + resultCount + '</span> &nbsp record found from search</p>';
			searchDataStr += '<div id="searchResultsScrollPane">';
			searchDataStr +=	'<table class="table searchResultsTable">' +
										'<tbody>'+
											'<tr>' +
												'<td><span id="recordLink0" class="fa fa-map-marker fa-2x parcelLink" title="Zoom to Parcel" data-pin="' + searchDataFromLayer[0].attributes[parPinField] + '" data-layerName="' + searchLayer + '"></span></td>' +
												'<td id="recordData0" class="parcelDataNoLrp" data-pin="' + searchDataFromLayer[0].attributes[parPinField] + '" data-layerName="' + searchLayer + '">' + searchDataFromLayer[0].attributes[parPinField] + ownerStr + addressStr + '</td>' +
											'</tr>' +
										'</tbody>' +
									'</table>' +
								'</div>';
        } else {
            totalPages = Math.ceil(resultCount/25);
            currentPage = 1;
            var lowerRange = 0;
            var lowerRangeAdj = lowerRange + 1;
            if (totalPages > 1){
                var UpperRange = 25;
                var pageDisplay = "<div id='parLastPage'><span class='disabled fa fa-fast-backward'></span> <span class='disabled fa fa-backward'></span></div><div id='parPageDisplay'> Page " + currentPage + " of " + totalPages + " pages<br>Showing " + lowerRangeAdj + "-" + UpperRange + " of " + resultCount + " results </div><div id='parNextPage'><a title='Next page' onclick='getLayerSearchNewPage(" + (currentPage + 1) + "," + totalPages + ", \"next\")' class='fa fa-forward'></a> <a title='Last page' onclick='getLayerSearchNewPage(" + currentPage + "," + totalPages + ", \"last\")' class='fa fa-fast-forward'></a></div>"
            } else {
                var UpperRange = resultCount;
                var pageDisplay = "<div id='parLastPage'><span class='disabled fa fa-fast-backward'></span> <span class='disabled fa fa-backward'></span></div><div id='parPageDisplay'> Page " + currentPage + " of " + totalPages + " pages<br>Showing " + lowerRangeAdj + "-" + UpperRange + " of " + resultCount + " results </div><div id='parNextPage'><span class='disabled fa fa-forward'></span> <span class='disabled fa fa-fast-forward'></span></div></pagedisplay>"
			}

            searchDataStr += '<div id="searchRecordsFound">' + pageDisplay + '</div>';
			searchDataStr += '<div id="searchResultsScrollPane">';
            searchDataStr +=	'<table class="table searchResultsTable">' +
										'<tbody>';
            for (var i=lowerRange; i < UpperRange; i++){
				var ownerStr = searchDataFromLayer[i].attributes[parOwnerField] ? "<br>" + searchDataFromLayer[i].attributes[parOwnerField] : "";
				var addressStr = searchDataFromLayer[i].attributes[parStreetField] ? "<br>" + searchDataFromLayer[i].attributes[parStreetField] : ""; 
                searchDataStr +=				'<tr>' +
												'<td><span id="recordLink' + i + '" class="fa fa-map-marker fa-2x parcelLink" title="Zoom to Parcel" data-pin="' + searchDataFromLayer[i].attributes[parPinField] + '" data-layerName="' + searchLayer + '"></span></td>' +
												'<td id="recordData' + i + '" class="parcelDataNoLrp" data-pin="' + searchDataFromLayer[i].attributes[parPinField] + '" data-layerName="' + searchLayer + '">' + searchDataFromLayer[i].attributes[parPinField] + ownerStr + addressStr + '</td>' +
											'</tr>';
			}
			searchDataStr +=			'</tbody>' +
									'</table>' +
								'</div>';
            
        }
        showSearchResults(searchDataStr);
        $('#searchSpinner').addClass('hidden');
    }

	function setPageButtons(){
        if (totalPages == '1'){
            setTimeout(function(){
                $('#parLastPage span, #parNextPage span').css({'pointerEvents':'none', 'opacity': '.5'});
            }, 700);
        } else if (currentPage == '1'){
            setTimeout(function(){
                $('#parLastPage span').css({'pointerEvents':'none', 'opacity': '.5'});
            }, 700);
        } else if (currentPage == totalPages){
            setTimeout(function(){
                $('#parNextPage span').css({'pointerEvents':'none', 'opacity': '.5'});
            }, 700);
        } else {           
            setTimeout(function(){
                try {
                    $('#parLastPage span', '#parNextPage span').css({'pointerEvents':'all', 'opacity': '1'});
                } catch (err){
                    console.log('Non-Critical Error: ' + err.message);
                }   
            }, 700);
        }
        setTimeout(function(){
            try{
				$('#searchResultsOuter').perfectScrollbar(); // 1
				$('#searchResultsScrollPane').perfectScrollbar('update');
			}catch(err){
				console.log("scrollbar error: " + err.message);
			}
        }, 700);
            
    }

	function showSearchResults(results, options, callback){
		results = results.replace(/\[object Object\]/g, "--");
		var viewportHeight = $('#map').height();
		var availableHeight = viewportHeight-160;
		// This is for searches done while search results are showing
			var previousHeight = $('#searchResultsInner').height();
			if(options){ //don't want to erase searchResultsInner if performing multiple queries for the same search
				if(options == 'start'){
					$('#searchResultsInner').animate({opacity: 'toggle'}, 500);
					$('#searchResultsInner').html(results);
				} else if (options == 'save'){
					$('#searchResultsInner').append(results);
				} else if (options == 'end') {
					$('#searchResultsInner').append(results);
					$('#searchResultsInner').animate({opacity: 'toggle'}, 500, function(){
						$('#searchResultsOuter').css('height', availableHeight);
						$('#searchResultsOuter').css('overflow', 'auto');
						try {
							$('#searchResultsOuter').perfectScrollbar(); // 2
							$('#searchResultsOuter').perfectScrollbar('update');
							$('#searchResultsOuter').scrollTop(0);
						} catch(err){
							console.log("scrollbar error: " + err.message);
						}
						setSearchResultsHandlers();
					});
					setPageButtons();
					setSearchResultsHandlers();
				}
				if(callback) callback();
			} else {
				$('#searchResultsInner').animate({opacity: 'toggle'}, 500, function(){
					$('#searchResultsInner').html(results);
					$('#searchResultsInner').animate({opacity: 'toggle'}, 500);
					$('#searchResultsOuter').css('height', availableHeight);
					$('#searchResultsOuter').css('overflow', 'auto');
					try {
						$('#searchResultsOuter').perfectScrollbar(); // 2
						$('#searchResultsOuter').perfectScrollbar('update');
						$('#searchResultsOuter').scrollTop(0);
					} catch(err){
						console.log("scrollbar error: " + err.message);
					}
					setSearchResultsHandlers();
				});
				if(callback) callback();
				setPageButtons();
				setSearchResultsHandlers();
			}
	}
	
	function showRecentParcels(results){
		results = results.replace(/\[object Object\]/g, "--");
		var availableHeight = $('#recentContainer').height()-50;
		// This is for searches done while search results are showing
		var previousHeight = $('#recentInner').height();
		
		$('#recentInner').animate({opacity: 'toggle'}, 500, function(){
			$('#recentInner').html(results);
			setSearchResultsHandlers();
			if($('#recentInner').height() > availableHeight ){
				$('#recentInner').animate({opacity: 'toggle'}, 500);
				$('#recentOuter').css('height', availableHeight);
				$('#recentOuter').css('overflow', 'auto');
				try{
					$('#searchResultsScrollPane').perfectScrollbar(); // 2
					$('#searchResultsScrollPane').perfectScrollbar('update');
				} catch(err){
					console.log("scrollbar error: " + err.message);
				}
				
			} else {					
				$('#recentInner').animate({
					opacity: 'toggle'
				}, 500, function(){
					$('#recentOuter').css('overflow', 'hidden');
					$('#recentOuter').css('height', $('#recentInner').height() + 5);
					$('#recentOuter').scrollTop(0);
				});
			}
		});
	
	}
	
	function clearSearchResults(){
		$('#searchResultsInner').html("");
		$('#searchResultsInner').css('height', '');
		//$('#searchResultsOuter').css('height', '1px');
		$('#searchResultsOuter').css('overflow', 'hidden');
		$('#searchSpinner').addClass('hidden');
		firstSearch = true;
		xhr2 = ""; // Wipe-out the xhr object
		xhr2 = (window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP")); // recreate the object
	}
	

	$('.clearSearch').on("click", function(){
		$('.pinInput, .searchInputWide, #OName, #Street, .searchSelect').val("");
		$('#veteransSearchExpando select.searchInputWide').val('unknown'); //veteran selects are unknown, not ''
		$('#searchDivScrollPane input[type=checkbox]').prop('checked',false); //uncheck any checkboxes
		$(this).trigger('blur');
		if(typeof(coordGraphic) != "undefined"){
		measurementLayer.remove(coordGraphic);
		map.infoWindow.hide();
		}
		clearSearchResults();
	});
	
	function clearRecent(){
		localStorage.recentList = JSON.stringify([]);
		$('#recentInner').html("");
		$('#recentInner').css('height', '');
		$('#recentOuter').css('height', '0px');
		//$('#recentOuter').css('overflow', 'hidden');
		$('#searchSpinner').addClass('hidden');
		$('#clearButton, #clearSep').hide();
	}
	
	$('#clearRecent').on("click", function(){
		clearRecent();
	});

	// first, query the server to find the parcel in case it's not visible at the current scale
	goToParcel = function(pin, parcelLayerId,propclass){
		
		var targetLayer;
		// no looping through parcel layers. We know the one they want via the searchLayer variable at the top of the page, set by the selection box.
		if(parcelLayerId !== undefined && parcelLayerId != null){ // use != for parcelLayerId. It is tolerant of undefined
			if(parcelLayerId.indexOf("ParcelLayer") === -1){
				parcelLayerId += "ParcelLayer";
			}
			targetLayer = parcelLayerId;
		} else {
			targetLayer = searchLayer;
		}
		
		
		var resultFound = false;
		
		var pinQuery, pinQueryTask, parExt;
		pinQuery = new query();
		pinQuery.outSpatialReference = new SpatialReference({ wkid:102100 });
		pinQueryTask = new QueryTask(parcelLayers[targetLayer].url);
		pinQuery.where =  `"${parcelLayers[targetLayer].pinField}" = '${pin}'`;
		//pinQuery.outFields = ['ParIDShort', 'OBJECTID', 'Xcoord', 'Ycoord']; //why was this in here??
		pinQuery.returnGeometry = true;
        pinQuery.outSpatialReference=map.spatialReference;

		pinQueryTask.execute(pinQuery, function(result){
			try{
				var selParcel = result.features[0];
				// will likely only get a result from one of the layers being queried
				if(typeof(selParcel) !== "undefined"){
					resultFound = true;
					selParcel.setInfoTemplate(template);
					parExt = selParcel.geometry.getExtent();
					map.setExtent(parExt, true).then(function(){
						goToParcel2(pin, parcelLayerId);
					});
				} else {
					if(typeof propclass != 'undefined' && currentMap=='saginaw' && (propclass=='098' || propclass=='099')){
						showAlert('Notice:', 'This is a retired parcel.  Retired parcels are not mapped on this system.');
					}else{
						showMessage('This parcel is not currently mapped, or is not a mappable parcel',null,'info');
					}
					
					getParcelData(pin, parcelLayerId); // getting parcel data doesn't happen automatically on unmapped parcels without this.
                    showDataPane();
					return;
				}
			} catch (err){
                showMessage('This parcel is not currently mapped, or is not a mappable parcel', null, 'info');
				getParcelData(pin); // getting parcel data doesn't happen automatically on unmapped parcels without this.
                showDataPane();
				return;
			}
		});
	};

	// select the feature, and place the popup on the parcel. (Hard coded to use the third point, which should be upper left corner of rectangle parcels)
	goToParcel2 = function(pin, county){
		if(county !== undefined && county != null){ // use != for county. It is tolerant of undefined
			if(county.indexOf("ParcelLayer") === -1){
				county += "ParcelLayer";
			}
			targetLayer = county;
		} else {
			targetLayer = searchLayer;
		}

		var pinQuery;
		pinQuery = new query();
		pinQuery.outSpatialReference = new SpatialReference({ wkid:102100 });
		pinQuery.where =  `"${parcelLayers[targetLayer].pinField}" = '${pin}'`;
		pinQuery.returnGeometry = true;
		//currentMap = parcelLayers[searchLayer].lrpMapStr;
		window[targetLayer].selectFeatures(pinQuery, FeatureLayer.SELECTION_NEW, function(selParcel){
			//currentMap = params.currentMap;
			// test to see if getting the extent will bomb out on us and if so, skip doing anything more for this layer.
			try{
				var testgeom = selParcel[0].geometry.getExtent();
			} catch(err) {
				return false;
			}

			if(typeof(testgeom) !== "undefined"){
				selParcel = selParcel[0];
				try{
					var parExt = selParcel.geometry.getExtent();
				} catch (err){
                    showMessage('This parcel is not currently mapped, or is not a mappable parcel',null,'info');
					getParcelData(pin); // getting parcel data doesn't happen automatically on unmapped parcels without this.
                    showDataPane();
					return false;
				}
				map.infoWindow.hide();
				map.infoWindow.clearFeatures();
				map.setExtent(parExt, true).then(function(){
					map.infoWindow.setFeatures([selParcel]);
					//map.infoWindow.show(selParcel.geometry.getCentroid());
					var pointt = new Point(popup.features[0].geometry.rings[0][2][0], popup.features[0].geometry.rings[0][2][1], new SpatialReference({ wkid:102100 }));
					map.infoWindow.show(pointt);
				});
			} else {
				// should put in code to see if any result found
				showMessage('This parcel is not currently mapped, or is not a mappable parcel',null,'info');
				getParcelData(pin);
                showDataPane();
			}
		});

	};
	
	parcelContent = function(pin, county, propclass){
		goToParcel(pin, county,propclass);
		showDataPane(pin, county);
	};
	
	
	function populateRecentParcelData(recParcelList){
		$('#searchSpinner').removeClass('hidden');
		var recArray = [];
		for(i=0;i<recParcelList.length;i++){
			for(var layer in parcelLayers){
				if(recParcelList[i].parcelLayerId === layer){
					recArray.push(recParcelList[i]);
				}
			}
		}
		searchDataStr = "";
		
		if(recArray.length == "0"){
			$('#clearButton, #clearSep').hide();
				
		} else if(recArray.length == "1"){ //One search result is not an array. There will not be unique Id's like in the next else clause
			$('#clearButton, #clearSep').show();

			searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal">' + recArray.length + '</span>&nbsp recently viewed parcel</p>';
			
			searchDataStr +=	'<table class="table searchResultsTable">' +
										'<tbody>'+
											'<tr>' + //data-pin="' + searchDataFromLayer[0].attributes[parPinField] + '" data-layerName="' + selectedCounty + 'ParcelLayer"
												'<td><span id="recordLinkR0"  class="fa fa-map-marker fa-2x parcelLink" title="Zoom to Parcel" data-pin="'+ recArray[0].ParcelNumber + '" data-layerName="' + recArray[0].parcelLayerId + '"></span></td>' +
												'<td id="recordDataR0" class="parcelData" data-pin="'+ recArray[0].ParcelNumber + '" data-layerName="' + recArray[0].parcelLayerId + '">' + recArray[0].ParcelNumber + '<br>' + recArray[0].OwnerName1 + '<br>' + recArray[0].PropAddressCombined + '<br>' + recArray[0].PropAddressCity + ", " + recArray[0].PropAddressState + " " + recArray[0].PropAddressZip + '<br>' + recArray[0].date +'</td>' +
											'</tr>' +
										'</tbody>' +
									'</table>';
		} else {
			$('#clearButton, #clearSep').show();

			searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal">' + recArray.length + '</span>&nbsp recently viewed parcels</p>';
			
			searchDataStr +=	'<table class="table searchResultsTable">' +
										'<tbody>';
			for (i=0; i < recArray.length; i++){
				
			searchDataStr +=				'<tr>' +
												'<td class="parcelLink" data-pin="'+ recArray[i].ParcelNumber + '" data-layerName="' + recArray[i].parcelLayerId + '"><span id="recordLinkR' + i + '" class="fa fa-map-marker fa-2x" title="Zoom to Parcel"></span></td>' +
												'<td id="recordDataR' + i + '" class="parcelData" data-pin="'+ recArray[i].ParcelNumber + '" data-layerName="' + recArray[i].parcelLayerId + '">'  + recArray[i].ParcelNumber + '<br>' + recArray[i].OwnerName1 + '<br>' + recArray[i].PropAddressCombined + '<br>' + recArray[i].PropAddressCity + ", " + recArray[i].PropAddressState + " " + recArray[i].PropAddressZip + '<br>' + recArray[i].date +'</td>' +
											'</tr>';
				}
			searchDataStr +=			'</tbody>' +
									'</table>';
		}
		showRecentParcels(searchDataStr);
		$('#searchSpinner').addClass('hidden');
	}
	
	
	// below are helper functions for preparing search entries for the intersection search
	function cleanRoadString(str){ // remove any road indicators to try and find a match (for wrong road type designation entered)
		str = str.toLowerCase(); /// next few lines are cleaning the search to remove anything to do with road designations, esri's query don't like em
		str = str.toLowerCase();
		str = str.replace(".", "");
		str = str.replace("rd", "");
		str = str.replace(" road", "");
		str = str.replace("ave", "");
		str = str.replace("ct", "");
		str = str.replace("dr", "");
		str = str.replace("st", "");
		str = str.replace(" street", "");
		str = trimThis(str);
		return str;
	}
	function stripBearing(str){ // strip compass bearing to try and find a match (for wrong compass bearing entered)
		var str1 = str.slice(0,2);
		var str2 = str.slice(2,str.length);
		str1 = str1.replace("w ", "");
		str1 = str1.replace("n ", "");
		str1 = str1.replace("e ", "");
		str1 = str1.replace("s ", "");
		var strippedStr = str1+str2;
		strippedStr = trimThis(strippedStr);
		return strippedStr;
	}

	function queryIntersect(rd1,rd2,queryIntersectCount, url){ // url is optional and so far is only used to search a secondary url if present
		rd1 = trimThis(rd1);
		rd2 = trimThis(rd2);
		var intersectionUrl = 'https://app.fetchgis.com/geoservices/fgis/bayIntersection/FeatureServer/0';
		if(url)
			intersectionUrl = url;
		if(queryIntersectCount == 1){
			rd1 = cleanRoadString(rd1);
			rd2 = cleanRoadString(rd2);
		} else if(queryIntersectCount == 2){
			rd1 = stripBearing(rd1);
			rd2 = stripBearing(rd2);
		} else if(queryIntersectCount > 2){
			queryIntersectCount = 0;
			showMessage("This intersection does not exist. Please try a new intersection.");			return;
		}
		var found = false;
		var queryTask = new QueryTask(intersectionUrl);
        var intersectionStr = 'FULLNAME';
		if(intersectionStr === ''){
			console.log('intersection search not set up for this county yet');
		}
		var likestring="ILIKE";
		if(intersectionUrl.indexOf('geoservices') == -1 ){
			likestring="LIKE";
		}

		var intQuery1 = new query();
		intQuery1.outSpatialReference = new SpatialReference({ wkid:102100 });
		intQuery1.returnGeometry = true;
		intQuery1.where = ` "${intersectionStr}" ${likestring} '%${rd1}%'`;
		intQuery1.outFields = [intersectionStr];
		var intQuery2 = new query();
		intQuery2.outSpatialReference = new SpatialReference({ wkid:102100 });
		intQuery2.returnGeometry = true;
		intQuery2.where = ` "${intersectionStr}" ${likestring} '%${rd2}%'`;
		intQuery2.outFields = [intersectionStr];
		queryTask.execute(intQuery1, function(arr1){
			queryTask.execute(intQuery2, function(arr2){
				for(i=0; i<arr1.features.length; i++){
					var geo1= /*webMercatorUtils.webMercatorToGeographic(*/arr1.features[i].geometry/*)*/; // new api doesn't appear to require geographic transformations
					for(j=0; j<arr2.features.length; j++) {
						var geo2 = /*webMercatorUtils.webMercatorToGeographic(*/arr2.features[j].geometry/*)*/;
						if(geo1 == null || geo2 == null)
							continue;
						if(geometryEngine.intersects(geo1,geo2)){	
							map.infoWindow.hide();
							map.infoWindow.clearFeatures();
							var tempArr = [];
							var buff1 = geometryEngine.buffer(geo1,0.1,"feet"); // intersection requires polygon inputs so place small buffer around each road
							var buff2 = geometryEngine.buffer(geo2,0.1,"feet");
							var intersection = geometryEngine.intersect(buff1, buff2);
							//intersection = new Point(intersection.rings[0][0]);
							intersection = webMercatorUtils.webMercatorToGeographic(new Point(intersection.rings[0][0])); // now convert to trad. XY coordinate

							stName1 = arr1.features[i].attributes[intersectionStr];
							stName2 = arr2.features[j].attributes[intersectionStr];
							popup.setTitle('Intersection:');
							popup.setContent("Street 1: " + stName1 + "<br>Street 2: " + stName2);
							map.centerAndZoom(intersection, 18).then(function(){
								map.infoWindow.show(intersection);
							});
							var found = true;
							queryIntersectCount = 0; // reset intersect count
							return;
						}
					}
				}
				if(found !== true){
					queryIntersectCount += 1;
					if(url){
						queryIntersect(rd1,rd2,queryIntersectCount, intersectionUrl); // recursive call
					} else {
						queryIntersect(rd1,rd2,queryIntersectCount); // recursive call
					}
				}
			}, function(){
				queryTimeout();
				queryIntersectCount = 0; // reset intersect count
			});

		}, function(){
			queryTimeout();
			queryIntersectCount = 0; // reset intersect count
		});
	}
	
	queryTimeout = function(){
        showMessage('Sorry, your search can not be completed at this time.');
		$('#searchSpinner').addClass('hidden');
	};
	
	function coordSearch(input){
		var coordPoint= projectGeometry(input, map.spatialReference);
		var boundary = new Polygon({"rings":[[[-7180060.406075722,6340352.75281709],[-6884096.232555598,2387641.1461351076],[-14246510.796981815,2306923.644265983],[-14209821.023404941,6359920.63205809],[-7180060.406075722,6340352.75281709]]],"spatialReference":{"wkid":102100,"latestWkid":3857}});
		if(geometryEngine.contains(boundary,coordPoint) || currentMap.indexOf('dow') > -1) { // just protect against
			if(typeof(coordGraphic) != 'undefined') // remove graphic if existent
				measurementLayer.remove(coordGraphic);

			var coordMarker =   new SimpleMarkerSymbol(SimpleMarkerSymbol.STYLE_CROSS, 18, // cross marker for showing location of coodinate search result
				new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID,
				new Color([255,0,0]), 1),
				new Color([0,255,0,0.25]));
			if($('.stPlaneCheck').hasClass('fa-check-square-o')){ // if state plane configure attr accordingly
				let coordSys = $('#stPlaneSelection').attr('coordsys');
				if(!coordSys){
					var coordAttr = {"Easting":input.x, "Northing":input.y};
					var coordIT = new InfoTemplate("Coodinate Search Result","Easting: ${Easting} <br/>\
						Northing: ${Northing}");
				} else {
					let xVal = $('#stPlaneSelection').attr('xval') ? $('#stPlaneSelection').attr('xval') : 'x';
					let yVal = $('#stPlaneSelection').attr('yval') ? $('#stPlaneSelection').attr('yval') : 'y';
					var coordAttr = {xVal:input.x, yVal:input.y};
					var coordIT = new InfoTemplate("Coodinate Search Result",xVal+": ${xVal} <br/>"+yVal+": ${yVal}");
				}
			} else { // else configure for normal x-y
				var coordAttr = {"x-coordinate":input.x,"y-coordinate":input.y};
				var coordIT = new InfoTemplate("Coodinate Search Result","x-coordinate: ${x-coordinate} <br/>\
					y-coordinate: ${y-coordinate}");
			}
			// create graphic object for coordinate result
			window.coordGraphic = new Graphic(coordPoint, coordMarker, coordAttr, coordIT);

			measurementLayer.add(coordGraphic); // add to measurement layer

			map.centerAndZoom(coordPoint, 18).then(function(){ // zoom to search result
				map.infoWindow.setFeatures([coordGraphic]);
				map.infoWindow.show(coordPoint);
			});

		} else {
			showAlert('Notice:', 'Make sure coordinates are valid and within the extent of the map, consult help for proper use.');
		}
	}
	
	$('#recentParcelHeader').on("click", function(){
		try{ // if the local storage is empty then a benign error occurs
			var tempList = JSON.parse(localStorage['recentList']);
			populateRecentParcelData(tempList);
		} catch(err){
			console.log('recent parcel list is empty: ' + err.message);
		}
	});
	
	
	addToRecent = function(pin, parcelLayerId){
	
		if(typeof(Storage) != "undefined") {
			// incase of clearing bowser history
			if(typeof(localStorage['recentList']) == 'undefined'){
				localStorage['recentList'] = JSON.stringify([]);
			}
			var xhr4Recent = (window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP"));
			var url = "ws/getAddFromPINS.php";
		
			// AJAX request
			xhr4Recent.open("POST", url, true);
			xhr4Recent.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			xhr4Recent.send('Map='+ parcelLayers[parcelLayerId].lrpMapStr + '&pins=' + pin);
			xhr4Recent.onreadystatechange = function() {
				if (xhr4Recent.readyState == 4 && xhr4Recent.status == 200){
					try{
					   if(xhr4Recent.responseText === "Error, query failed"){
							console.log("A minor error occurred adding to recent parcel list. (Query failed due to wrong map being passed in. This is temporary)");
							return;
					   }
					   var recentData = JSON.parse(xhr4Recent.responseText);
					   if(recentData === null){
							console.log("recentData is null");
					   }
					   var dateAcc = getSysDate();
					   var timeAcc = getSysTime();
					   if(timeAcc[0] == '0'){
						timeAcc = '12' + timeAcc.slice(1,timeAcc.length); // make 0 read as 12 midnight
					   }
					   
					   var tempList = JSON.parse(localStorage['recentList']);
					   //tempList.unshift([recentData[0].ParcelNumber, selectedCounty, recentData[0].OwnerName1, recentData[0].PropAddressZip, recentData[0].PropAddressStreet, recentData[0].PropAddressCity, timeAcc, dateAcc]);
					   tempList.unshift(
							//[
								{
									ParcelNumber:			recentData[0].ParcelNumber,
									parcelLayerId: 			parcelLayerId,
									OwnerName1: 			recentData[0].OwnerName1,
									PropAddressCombined: 	recentData[0].PropAddressCombined,
									PropAddressCity: 		recentData[0].PropAddressCity,
									PropAddressState: 		recentData[0].PropAddressState,
									PropAddressZip: 		recentData[0].PropAddressZip,
									date:					dateAcc,
									time:					timeAcc
								}
							//]
						);
					   
					   if(tempList.length > 100){
							tempList = tempList.slice(0,100);
					   }
					   
					   var newTempList = [];
					   
					   var oneDay = 24*60*60*1000; 
					   dateAcc = new Date(dateAcc);
					   
					   // for expiration of records
					   // TODO: make this blow away old style records that used indexed array.
					   // Currently, the app doesn't show old style records since there's no parcel layer id.
					   var pinList = [];
					   for(var i = 0; i < tempList.length; i++){
							var recDate = tempList[i].date;
							recDate = new Date(recDate);
							var diffDays = Math.round(Math.abs((recDate.getTime() - dateAcc.getTime())/(oneDay)));
							if(pinList.indexOf(tempList[i].ParcelNumber) == -1 && diffDays < 5){
								pinList.push(tempList[i].ParcelNumber);
								newTempList.push(tempList[i]);
							}
					   }
					   
					   if($('#recentContainer').hasClass('ui-accordion-content-active') && $('#searchButton').hasClass('active')){
							populateRecentParcelData(newTempList);
						}
					    localStorage['recentList'] = JSON.stringify(newTempList);
					  
					} catch (err) {
						console.log("A minor error occurred adding to recent parcel list.");
						console.log(err);
					}
				}
			}
		}
	}
	
	//################################################
	//########### extra search functionality ########
	//################################################

	
	zoomToAddress = function(xyStr){
		map.infoWindow.hide();
		map.infoWindow.clearFeatures();
		var strArr = xyStr.split(',');
		strArr = strArr.filter(function(e){ return e === 0 || e });
		var addLoc = new Point([strArr[0], strArr[1]]);
		var infoArr = strArr.slice(2,strArr.length);
		var houseNumber = infoArr[0];
		var streetName = infoArr[1] ? ", " + dontDuplicateAddressNumber(infoArr[0], infoArr[1]) : "";
		var cityName = infoArr[2] ? ", " + infoArr[2] : "";
		var entryStr = houseNumber + streetName + cityName;
		map.centerAndZoom(addLoc, 18).then(function(){
			popup.setTitle('Address:');
			popup.setContent(entryStr);
			map.infoWindow.show(addLoc);
		});
	}
   
	// veteran graphic so as to be able to delte it when necessary (global)
	var vetGraphic;

    zoomToVeteran = function(xyStr){
        map.graphics.remove(vetGraphic);
        var cem, other;
		map.infoWindow.hide();
		map.infoWindow.clearFeatures();
		var strArr = xyStr.split(';');
        // set name
        if (strArr[2] != '  ' && strArr[3] == '  '){ // first name does not exist
            var name = strArr[2] + '<br>'
        } else if (strArr[2] != '  ' && strArr[3] != '  '){ //first name does exist
            var name = strArr[2] + ', ' + strArr[3] + '<br>';
        }
        //set branch and war
        if(strArr[4] != '  ' && strArr[5] != '  '){ //both branch and war exist
            var baw = strArr[4] + ' - ' + strArr[5] + '<br>';
        } else if (strArr[4] != '  ' && strArr[5] == '  '){ //only branch exists
            var baw = strArr[4]+ '<br>';
        } else if (strArr[4] == '  ' && strArr[5] != '  '){ //only war exists
            var baw = strArr[5]+ '<br>';
        } else { // neither branch or war exists
            var baw = '';
        }
		var addLoc = new Point([strArr[0], strArr[1]]);
        var vetSymbol = new PictureMarkerSymbol({
            url: "img/Usflag_Waving.gif",
            height: 18,
            width: 25,
        });
		map.centerAndZoom(addLoc, 18).then(function(){
			vetGraphic = new Graphic (addLoc, vetSymbol);
            map.graphics.add(vetGraphic);
			popup.setTitle(name);
            if (strArr[6] != '  ' && strArr[7] != '  '){ // cemetery and other exist
				if(currentMap == 'sanilac'){
					cem = strArr[6] + '<br>';
					var src = 'https://app.fetchgis.com/linkedDocs/sanilac/vets/'+strArr[7].trim();
					other = '<a href="'+src+'" target="_blank"><img class="sanVetImage" style="height:200px;margin-top:16px;" src="'+src+'"></a>'; //#1498
					popup.setContent(name + baw + cem + other);
				} else {
					cem = strArr[6];
					other = strArr[7] + '<br>';
					popup.setContent(name + baw + other + cem);
				}
            }
            if (strArr[6] != '  ' && strArr[7] == '  '){ //other does not exist
                cem = strArr[6];
                popup.setContent(name + baw + cem);
            }
			if (strArr[6] == '  ' && strArr[7] != '  '){ //cemetery does not exist
				if(currentMap == 'sanilac'){
					var src = 'https://app.fetchgis.com/linkedDocs/sanilac/vets/'+strArr[7].trim();
					other = '<a href="'+src+'" target="_blank"><img class="sanVetImage" style="height:200px;margin-top:16px;" src="'+src+'"></a>'; //#1498
					popup.setContent(name + baw + other);
				} else {
					other = strArr[7];
                popup.setContent(name + baw + other);
				}
                other = strArr[7];
                popup.setContent(name + baw + other);
            }
            if (strArr[6] == '  ' && strArr[7] == '  '){
                popup.setContent(name + baw);
            }
			map.infoWindow.show(addLoc);
		});
	}
	
	dojo.connect(popup, 'onHide', function(){ // used to clear grave search graphic when popup is closed/changed
        map.graphics.remove(vetGraphic);
    });

	
	
	function queryAddress(num,st,city,queryAddressCount){ // city argument is optional and only currently used for midland searches
		num = trimThis(num);
		st = trimThis(st);
		city = trimThis(city);
		let usePG = true; //is this layer hosted on fetch?
		if(queryAddressCount == 1){
			st = cleanRoadString(st);
		} else if(queryAddressCount == 2){
			st = stripBearing(st);
		} 
		var isRunning = false;
		var queryTask = new QueryTask('https://app.fetchgis.com/geoservices/fgis/bayAddressPoints/FeatureServer/0');
		var addQuery = new query();
		let addressNum = "NUMBER";
		let addressName = "FULLNAME";
		let addressCity = "none";
		addQuery.outSpatialReference = new SpatialReference({ wkid:102100 });
		st = st.replace(/\./g,'');
		var x = "("+addressNum+" LIKE '%" + num + "%')";
		var y =  "("+addressName+" LIKE '%" + st + "%')";
		if(usePG){
			x = `("${addressNum}"::Text LIKE '%${num}%')`; //cast as text, could be text or integer field
			y = `("${addressName}" ILIKE '%${st}%')`;
		}
		if (city != undefined && city != null && city != ''){
			var z =  "("+addressCity+" LIKE '%" + city + "%')";
			if(usePG){
				z =  `("${addressCity}" ILIKE '%${city}%')`;
			}
		}
		var wClause = "";
		if(num!=""){
			wClause += x;
		}
		if(st!=""){
			if(num!=""){
				wClause+= " AND "+y
			} else{
				wClause+=y;
			}
		}
		if(city!=""){
			if(num!="" || st!=""){
				wClause+= " AND "+z
			}else {
				wClause+=z;
			}
		}
		addQuery.where = wClause;
		addQuery.outFields = ["NUMBER","FULLNAME"];
		addQuery.returnGeometry = true;
		$("#searchControlsAccordion").children().eq(2).trigger("click");
		$("#searchSpinner").removeClass("hidden");
		queryTask.execute(addQuery,function(result){
			var selAddress = result;
			selAddress = selAddress.features;
			if(isRunning == false){
				if(selAddress.length >= 1 || queryAddressCount > 2){
					queryAddressCount = 0; // reset global 
					populateUserAddressData(selAddress); // populate the search pane
					isRunning = true;
					setTimeout(function(){
						isRunning = false;
					},5000);
				} else if(selAddress.length < 1){
					queryAddressCount += 1;
					queryAddress(num,st,city,queryAddressCount);
				} 
	
			}

		}, function(){
			queryAddressCount = 0;
			queryTimeout();
			
		});
	}
	


    function queryVeteran(name, nameL, branch, war, cemetery){
		var isRunning = false;
		var url = "https://app.fetchgis.com/geoservices/fgis/midVeterans/FeatureServer/0";
		if(currentMap == 'sanilac'){
			url = "https://app.fetchgis.com/geoservices/fgis/sanVeterans/FeatureServer/0";
		}
		branch = decodeURIComponent(branch);
		war = decodeURIComponent(war);
		cemetery = decodeURIComponent(cemetery);
        var queryTask = new QueryTask(url);
		var vetQuery =  new query();
		vetQuery.outSpatialReference = new SpatialReference({ wkid:102100 });
        
        //build where clause
        var w = "(\"LASTNAME\" = '" + name.toUpperCase() + "') OR (\"FULLNAME\" ILIKE '" + name.toUpperCase() + "%')";
        var x = "(\"BRANCH\" = '" + branch + "')";
        var y = "(\"WAR\" = '" + war + "')";
		var z = "(\"CEM_NAME\" = '" + cemetery + "')";

		if(currentMap == 'sanilac'){ //built for first name / last name, no fullname field
			w = '';
			if(nameL){
				w = "(\"LASTNAME\" ILIKE '" + nameL.toUpperCase() + "')"
			}
			if(nameL && name){
				w += " AND ";
			}
			if(name){
				w += "(\"FIRSTNAME\" ILIKE '" + name.toUpperCase() + "%')"
			}
			x = "(\"Branch\" = '" + branch + "')";
			y = "(\"Era\" = '" + war + "')";
			z = "(\"CEMETERY\" = '" + cemetery + "')";
		}
		var wClause = "";
		var isNameSet = false;
		if(name != '' || nameL != ''){
			isNameSet = true;
		}
        
        if(isNameSet){
            wClause += w;
        }
        if (branch != 'unknown'){
            if(isNameSet){
                wClause += " AND " + x;
            } else {
                wClause += x;
            }
        }
        if (war != 'unknown'){
            if(isNameSet || branch != 'unknown'){
                wClause += " AND " + y;
            } else {
                wClause += y;
            }
        }
        if(cemetery != 'unknown'){
            if (isNameSet || branch != 'unknown' || war != 'unknown'){
                wClause += " AND " + z;
            }else{
                wClause += z;
            }
        } // finish where caluse
        
        vetQuery.where = wClause;
		vetQuery.outFields = ["LASTNAME", "FIRST_NAME", "BRANCH", "WAR", "CEM_NAME", "OTHER"];
		if(currentMap == 'sanilac'){
			vetQuery.outFields = ["LASTNAME", "FIRSTNAME", "Branch", "Era", "CEMETERY", "PicName"];
		}
		vetQuery.returnGeometry = true;
        $('#searchControlsAccordion').children().eq(2).trigger('click');
		$('#searchSpinner').removeClass('hidden');
        queryTask.execute(vetQuery, function(result){
            var selVeterans = result.features;
            if (isRunning == false){
                
                populateUserVeteranData(selVeterans);
                isRunning = true;
                setTimeout(function(){
                    isRunning = false;
                },5000);
			}

        }, function(){
            queryTimeout();
        });
    }
	
	
	
	function checkField(field, addCarriage){ //for checking any optional field used the populateUser functions. apply to any optional field that needs to be included in one county but not another, etc. 
		if (field === undefined || field === null || trimThis(field) === '' || field === 'null') { // check if field actually exists
			return ''; // return nothing so it is not displayed in zoomToAddress functionality and address results data pane
		} else{
			if(addCarriage){
				field+='<br>';
			}
			return field;
		}
	}

	function dontDuplicateAddressNumber(number, street){ // utility for street names that also contain the house number, no point in showing this twice
		if(!street) return number;
		try{
			var house_number = number.toString();
			if(street.indexOf(house_number) == -1){
				return street;
			} else {
				return street.replace(new RegExp(house_number), "");
			}
		} catch(e) {}
	}
	
	function populateUserAddressData(addArray){
		
		searchDataStr = "";
		if(addArray.length ==  0){
			showAlert('Notice:', 'No matching addresses were found.');
			searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal">' + addArray.length + '</span> &nbsp record(s) found from search</p>';
		} else if(addArray.length == "1"){ //One search result is not an array. There will not be unique Id's like in the next else clause
			var tempStr = "";
			var addGeo = webMercatorUtils.webMercatorToGeographic(addArray[0].geometry);
			tempStr =  buildPopupText(addGeo,addArray[0]);
			searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal">' + addArray.length + '</span> &nbsp record found from search</p>';
			searchDataStr +=	'<table class="table searchResultsTable">' +
										'<tbody>'+
											'<tr>' +
												'<td><span id="recordLink0" class="fa fa-map-marker fa-2x recordLink" title="Zoom to Address" onclick="zoomToAddress(\''+ tempStr + '\')"></span></td>' +
												'<td id="recordData0" class="recordData" ><a href="#" onclick="zoomToAddress(\''+ tempStr + '\')">' + addArray[0].attributes.NUMBER + '<br>' + addArray[0].attributes.FULLNAME + '<br>' +  checkField(addArray[0].attributes.none) +'</a></td>' +
											'</tr>' +
										'</tbody>' +
									'</table>';
		} else {
			searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal">' + addArray.length + '</span> &nbsp record(s) found from search</p>';
			searchDataStr +=	'<table class="table searchResultsTable">' +
										'<tbody>';
			for (i=0; i < addArray.length; i++){
			var tempStr = "";
			var addGeo = webMercatorUtils.webMercatorToGeographic(addArray[i].geometry);
			tempStr =  buildPopupText(addGeo,addArray[i]);
			var streetName = dontDuplicateAddressNumber(addArray[i].attributes.NUMBER, addArray[i].attributes.FULLNAME);
			searchDataStr +=				'<tr>' +
												'<td><span id="recordLink' + i + '"  class="fa fa-map-marker fa-2x recordLink" title="Zoom to Address" onclick="zoomToAddress(\''+ tempStr + '\')"></span></td>' +
												'<td id="recordData' + i + '" class="recordData"><a href="#" onclick="zoomToAddress(\''+ tempStr + '\')">' + buildSearchText(addArray[i]) + '</a></td>' +
											'</tr>';
			}
			searchDataStr +=			'</tbody>' +
									'</table>';
		}
		showSearchResults(searchDataStr);
		$('#searchSpinner').addClass('hidden');
		function buildPopupText(addGeo,feat){
			let string = addGeo.x + "," + addGeo.y + ",";
			if(addArray[0].attributes.NUMBER){
				string += feat.attributes.NUMBER + ",";
			}
			if(addArray[0].attributes.FULLNAME){
				string += feat.attributes.FULLNAME + ","
			}
			if(addArray[0].attributes.none){
				string += checkField(feat.attributes.none)
			}
			return string;
		}
		function buildSearchText(feat){
			let string = "";
			if(feat.attributes.NUMBER){
				string += feat.attributes.NUMBER;
			}
			if(streetName && (streetName != feat.attributes.NUMBER)){
				string += "<br>" + streetName;
			}
			if(feat.attributes.none){
				string += "<br>" + checkField(feat.attributes.none)
			}
			return string;
		}
	}	

	function populateUserVeteranData(addArray){
		searchDataStr = "";
		if(addArray.length ==  0){
			showAlert('Notice:', 'No matching record was found.');
			searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal">' + addArray.length + '</span> &nbsp record(s) found from search</p>';
		} else if(addArray.length == "1"){ 
            //One search result is not an array. There will not be unique Id's like in the next else clause
			var tempStr = "";
			if(currentMap == 'sanilac'){ //just updating pointers, so the whole function doesn't need to be redone for sanilac
				addArray[0].attributes.FIRST_NAME = addArray[0].attributes.FIRSTNAME;
				addArray[0].attributes.BRANCH = addArray[0].attributes.Branch;
				addArray[0].attributes.WAR = addArray[0].attributes.Era;
				addArray[0].attributes.OTHER = addArray[0].attributes.PicName;
				addArray[0].attributes.CEM_NAME = addArray[0].attributes.CEMETERY;
			}
			var addGeo = webMercatorUtils.webMercatorToGeographic(addArray[0].geometry);
			tempStr =  "" +  addGeo.x + ";" + addGeo.y + ";" + addArray[0].attributes.LASTNAME + ";" + addArray[0].attributes.FIRST_NAME + ";" + addArray[0].attributes.BRANCH + ";" + addArray[0].attributes.WAR + ";" + addArray[0].attributes.CEM_NAME + ";" + addArray[0].attributes.OTHER + "";
			searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal">' + addArray.length + '</span> &nbsp record found from search</p>';
			searchDataStr +=	'<table class="table searchResultsTable">' +
										'<tbody>'+
											'<tr>' +
												'<td><span id="recordLink0"  class="fa fa-map-marker fa-2x recordLink" title="Zoom to Location" onclick="zoomToVeteran(\''+ tempStr + '\')"></span></td>' +
												'<td id="recordData0" class="recordData" ><a href="#" onclick="zoomToVeteran(\''+ tempStr + '\')">';
            
            if(addArray[0].attributes.LASTNAME != ' '){
                searchDataStr += addArray[0].attributes.LASTNAME;
            }
            if(addArray[0].attributes.FIRST_NAME != ' '){
                searchDataStr += ", " + addArray[0].attributes.FIRST_NAME;
            }
            // branch exists, war does not
            if(addArray[0].attributes.BRANCH != ' ' && addArray[0].attributes.WAR == ' '){
                searchDataStr += '<br>' + addArray[0].attributes.BRANCH;
            }
            //war exists, branch does not
            if(addArray[0].attributes.BRANCH == ' ' && addArray[0].attributes.WAR != ' '){
                searchDataStr += '<br>' + addArray[0].attributes.WAR;
            }
            //both branch and war exist
            if(addArray[0].attributes.BRANCH != ' ' && addArray[0].attributes.WAR != ' '){
                searchDataStr += '<br>' + (addArray[0].attributes.BRANCH ? addArray[0].attributes.BRANCH : 'Unknown') + ' - ' + (addArray[0].attributes.WAR ? addArray[0].attributes.WAR : 'Unknown');
			}
			if(currentMap == 'sanilac'){ //we don't want picName displaying in search results
				addArray[0].attributes.OTHER = ' ';
			}
            if(addArray[0].attributes.OTHER && addArray[0].attributes.OTHER != ' '){
                searchDataStr += '<br>' + (addArray[0].attributes.OTHER).toUpperCase();
            }
            if(addArray[0].attributes.CEM_NAME != ' '){
                searchDataStr += '<br>' + addArray[0].attributes.CEM_NAME;
            }
            
            searchDataStr += '</a></td></tr></tbody></table>';
            
		} else {
            
			searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal">' + addArray.length + '</span> &nbsp record(s) found from search</p>';
			searchDataStr +=	'<table class="table searchResultsTable">' +
										'<tbody>';
			for (i=0; i < addArray.length; i++){
				if(currentMap == 'sanilac'){ //just updating pointers, so the whole function doesn't need to be redone for sanilac
					addArray[i].attributes.FIRST_NAME = addArray[i].attributes.FIRSTNAME;
					addArray[i].attributes.BRANCH = addArray[i].attributes.Branch;
					addArray[i].attributes.WAR = addArray[i].attributes.Era;
					addArray[i].attributes.OTHER = addArray[i].attributes.PicName;
					addArray[i].attributes.CEM_NAME = addArray[i].attributes.CEMETERY;
				}
			var tempStr = "";
			var addGeo = webMercatorUtils.webMercatorToGeographic(addArray[i].geometry);
			tempStr =  "" +  addGeo.x + ";" + addGeo.y + ";" + addArray[i].attributes.LASTNAME + "; " + addArray[i].attributes.FIRST_NAME + "; " + addArray[i].attributes.BRANCH + "; " + addArray[i].attributes.WAR + "; " + addArray[i].attributes.CEM_NAME + "; " + addArray[i].attributes.OTHER + "";
		
			searchDataStr +=				'<tr>' +
												'<td><span id="recordLink' + i + '"  class="fa fa-map-marker fa-2x recordLink" title="Zoom to Location" onclick="zoomToVeteran(\''+ tempStr + '\')"></span></td>' +
												'<td id="recordData' + i + '" class="recordData"><a href="#" onclick="zoomToVeteran(\''+ tempStr + '\')">'; 
                
                if(addArray[i].attributes.LASTNAME != ' '){
                    searchDataStr += addArray[i].attributes.LASTNAME;
                }
                if(addArray[i].attributes.FIRST_NAME != ' '){
                    searchDataStr += ", " + addArray[i].attributes.FIRST_NAME;
                }
                // branch exists, war does not
                if(addArray[i].attributes.BRANCH != ' ' && addArray[i].attributes.WAR == ' '){
                    searchDataStr += '<br>' + addArray[i].attributes.BRANCH;
                }
                //war exists, branch does not
                if(addArray[i].attributes.BRANCH == ' ' && addArray[i].attributes.WAR != ' '){
                    searchDataStr += '<br>' + addArray[i].attributes.WAR;
                }
                //both branch and war exist
                if(addArray[i].attributes.BRANCH != ' ' && addArray[i].attributes.WAR != ' '){
                    searchDataStr += '<br>' + (addArray[i].attributes.BRANCH ? addArray[i].attributes.BRANCH : 'Unknown') + ' - ' + (addArray[i].attributes.WAR ? addArray[i].attributes.WAR : 'Unknown');
				}
				if(currentMap == 'sanilac'){
					if(addArray[i].attributes.CEM_NAME != ' '){
						searchDataStr += '<br>' + addArray[i].attributes.CEM_NAME;
					}
				} else {
					if(addArray[i].attributes.OTHER && addArray[i].attributes.OTHER != ' '){
                    	searchDataStr += '<br>' + (addArray[i].attributes.OTHER).toUpperCase();
					}
					if(addArray[i].attributes.CEM_NAME != ' '){
						searchDataStr += '<br>' + addArray[i].attributes.CEM_NAME;
					}
				}
               
            
                searchDataStr += '</a></td></tr>';
            }
            searchDataStr += '</tbody></table>';
	    }
        showSearchResults(searchDataStr);
        $('#searchSpinner').addClass('hidden');
    }

	
/*
 * Sample for using the genericSearch class*
	
	var twpCode = $('#otsSectionSelect').val();

	var sm = {
		'twpCode'   : {'match':twpCode,'comp': '='}
	}
	
	var zoomTo = function(sIdentity){
		var query = new esri.tasks.Query();
		query.outSpatialReference = new SpatialReference({ wkid:102100 });
		query.where = "ID = '" + sIdentity +"'";
		query.returnGeometry = true;
		sectionsLayer.selectFeatures(query, FeatureLayer.SELECTION_NEW, function(result){
			if(result.length == 0){
				return;
			}
			
			var result = result[0];
			
			var attr = result.attributes;
			var section = attr.SECTION;
			var township = attr.twpName;
			
			map.infoWindow.hide();
			map.setExtent(result.geometry.getExtent(), true).then(function(){
				popup.setTitle('Section:');
				popup.setContent('NO: ' + section + '<br>' + township);
				map.infoWindow.show(result.geometry.getCentroid());
			});
		});
	}
	
	var labels = {
		"SECTION"  : "Section",
		"twpName"  : "Township"
	}
	
	
	var searchObj =  {
		url    		: "https://app.fetchgis.com/geoservices/fgis/baySec/FeatureServer/0",
		identity  		: "ID",
		searchMapping   : sm,
		outfields       : ["SECTION", "twpName"],
		labels			: labels,
		layer 			: layerObject,
		onZoom 			: zoomTo,
		datefields      : ['CREATED_DATE'],
		joinMethod		: 'OR'
	}
	
	var sectionSearch = new GenericSearch(searchObj);
	sectionSearch.formid = '71'; //override form to use
	sectionSearch.query();
	
*/	


	// new generic search - this will work for most searches
	function GenericSearch(oConfig){

		if(Array.isArray(oConfig.layer)){
			this.layer = oConfig.layer;
			this.identity = oConfig.identity;
		} else if(oConfig.layer){
			this.layer = oConfig.layer;
			this.identity = oConfig.identity;
			this.url =  this.layer.url;
		} else if (oConfig.url){
			this.url = oConfig.url; // required - the url of the service being queried
			this.identity = oConfig.identity; // required - the field that contains a unique value for querying
			this.layer = null;
		} else {
			alert("Error: no url or layer provided in configuration object for generic search");
		}
		
		this.alternativeDelete = oConfig.alternativeDelete || false;
		this.zoomLevel = oConfig.zoomLevel || 19;
		this.order = oConfig.order || false;
		this.onZoom = oConfig.onZoom || this._layerZoomTo; // required - the function used for when a search result is clicked
		this.outfields = oConfig.outfields ? oConfig.outfields : []; // the outfields of the query also used to dtermine what is displayed in the search results
		this.searchMapping = oConfig.searchMapping ? oConfig.searchMapping : {}; // match a field name to the user inputed value also specify what kind of comparison this will be
		this.labels = oConfig.labels ? oConfig.labels : {}; // labels that can correspond to fields in the outfield, this is for when you need something like Section: 01 where 01 is the actual value
		this.datefields = oConfig.datefields ? oConfig.datefields : []; //stores what fields need to be transformed into readable dates
		this.options = oConfig.options ? oConfig.options : null; //start and save are the current choices, start will erase search results, save will append, null will erase
		this.whatsearch = oConfig.whatsearch ? oConfig.whatsearch : null; //used to specify what search is being performed, if special functions or parameters are needed
		this.multipleLayers = oConfig.multipleLayers ? oConfig.multipleLayers : null; //search will involve multiple layers - layer needs to be an array of layer objects, ie [ window[layerID] ]
		this.joinMethod = oConfig.joinMethod ? oConfig.joinMethod : 'AND'; //if multiple search params, method used to join them
	};
	
	GenericSearch.prototype = {
		
		_buildDisplayContent: function(feature){ // builds the display information used in the results
			var contentStr = '';
			for(var i=0; i<this.outfields.length;i++){
				var curAttr = this.outfields[i]; // 
				var fieldVal = checkField(feature.attributes[curAttr],true); // add the value of the field to the content str if it is present
				if(fieldVal !== ''){
					if(this.labels[curAttr]){
						contentStr += this.labels[curAttr] + ': '; // see if a label exists for the field, if so add it to the content str
					}
					if(this.datefields.indexOf(curAttr) > -1){ //check for datefield that needs to be transformed
						contentStr += moment(fieldVal, 'x').format('MM/DD/YYYY') + '<br>'
					} else {
						contentStr += fieldVal; 
					}
				}
			}
			if(this.multipleLayers){ //show what layer this result is from
				contentStr += '<div class="searchResultLayerName">' + feature._layer.name + '</div>';
			}
			return contentStr;
		},
		
		_attachZoomHandlers: function(){
			var context = this;
			$(document).off('click', '.searchZoomTo'); // clear previous hanler 
			$(document).on("click", '.searchZoomTo', function() { // add new one
				var identity = $(this).data().identity;
				var clickedLayer = $(this).data().layer;
				var parentKey = $(this).data().parentkey;
				context.onZoom(identity, clickedLayer, parentKey); //  add user defined callback function to use
			});
		},

		_attachFormHandlers: function(){ //open form when clicked
			var context = this;
			$(document).off('click', '.searchZoomTo'); // clear previous handlers
			$(document).on("click", '.searchZoomTo', function() { // add new one
				var identity = $(this).data().identity;
				$(document).on('formCleared.searchFunc',function(){ //listening for it here
					$(document).off('formCleared.searchFunc'); //no need to listen anymore, turn off
					var Query = new query();
					Query.outSpatialReference = new SpatialReference({ wkid:102100 });
					Query.where = "\"" +context.identity + "\" = '" + identity +"'";
					Query.returnGeometry = true;
					context.layer.queryFeatures(Query, function(result){
						formViewer.newForm(result.features[0], context.formid);
						$('#pfe-rendered').fadeIn('fast', function(){ //ensure that searches using the white pane have the generate report button hidden
							showDataPane();
						})
					}, function(error){
						console.log(error)
					})
				})
				if(popup.isShowing || popup.count > 0){
					popup.clearFeatures(); //not synchronous, so new event gets fired when form resets, to ensure the reset doesnt happen when trying to build new form
					popup.hide();
				} else {
					formViewer._reset();
				}
			});
		},

		_orderArrayOfObjects: function(aoo, field){
			function compare(a,b) {
				if (a.attributes[field] < b.attributes[field])
				  return -1;
				if (a.attributes[field] > b.attributes[field])
				  return 1;
				return 0;
			  }

			aoo.sort(compare);
			return aoo;
		},
		
		buildSearchHTML: function(arrayOfFeatures, callback){ // generates the search result html like we've been doing for some time

			searchDataStr = "";
			var styleAddons = '';
			var parentKey = '';
			var layerid = '';
			var layerName = '';
			if(this.layer && !Array.isArray(this.layer)){
				layerName = ' in ' + window[this.layer.id].name; //gives more information on records found line, helpful for multiple queries
				if(window[this.layer.id].type == 'Table'){
					layerName += ' Table'
				}
				if(this.layer.id == 'phPersonLayer'){ //hides the locate button
					styleAddons = ' style="display: none" '
				}
				layerid = this.layer.id;
			}
			
			
			if(this.order){
				arrayOfFeatures = this._orderArrayOfObjects(arrayOfFeatures, this.order);
			}

			if(arrayOfFeatures.length ==  0){
				if(!this.options){ //for now blocks alert for multiple layer search, eg. bayEH drain/septic search
					showMessage('No matching results were found.',null,'info');
				}
				searchDataStr += 	'<p id="searchRecordsFound" style="margin-bottom: 15px"><span id="searchRecordsFoundVal">' + arrayOfFeatures.length + '</span> &nbsp record(s) found' + layerName + '</p>';
			} else if(arrayOfFeatures.length == 1){ //One search result is not an array. There will not be unique Id's like in the next else clause
				searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal">' + arrayOfFeatures.length + '</span> &nbsp record found' + layerName + '</p>';
				searchDataStr +=	'<table class="table searchResultsTable">' +
											'<tbody>' +
												'<tr>' +
													'<td><span id="recordLink0"' + styleAddons + ' class="fa fa-map-marker fa-2x recordLink searchZoomTo" title="Zoom to Feature" data-layer="' + arrayOfFeatures[0]._layer.id + '" data-identity="' + arrayOfFeatures[0].attributes[this.identity] +'"></span></td>' +
													'<td id="recordData0" class="recordData searchZoomTo" data-parentkey="' + arrayOfFeatures[0].attributes[this.outfields[0]] + '" data-identity="' + arrayOfFeatures[0].attributes[this.identity] + '" data-layer="' + layerid + '">' + this._buildDisplayContent(arrayOfFeatures[0]) + '</td>' +
												'</tr>' +
											'</tbody>' +
										'</table>';
			} else {
				searchDataStr += 	'<p id="searchRecordsFound"><span id="searchRecordsFoundVal">' + arrayOfFeatures.length + '</span> &nbsp record(s) found' + layerName + '</p>';
				searchDataStr +=	'<table class="table searchResultsTable">' +
											'<tbody>';
				for (i=0; i < arrayOfFeatures.length; i++){
				
				searchDataStr +=				'<tr>'
				searchDataStr += 					'<td><span id="recordLink' + i + '"' + styleAddons + 'class="fa fa-map-marker fa-2x recordLink searchZoomTo" title="Zoom to Feature" data-layer="' + arrayOfFeatures[i]._layer.id + '" data-identity="' + arrayOfFeatures[i].attributes[this.identity] + '"></span></td>'								
				searchDataStr +=					'<td id="recordData' + i + '" class="recordData searchZoomTo" data-layer="' + arrayOfFeatures[i]._layer.id + '" data-parentkey="' + arrayOfFeatures[i].attributes[this.outfields[0]] + '" data-identity="' + arrayOfFeatures[i].attributes[this.identity] + '">' + this._buildDisplayContent(arrayOfFeatures[i]) + '</td>' +
												'</tr>';
				}
				searchDataStr +=			'</tbody>' +
										'</table>';
			}
			showSearchResults(searchDataStr, this.options, callback);
			$('#searchSpinner').addClass('hidden');
			if(this.layer && typeof(perfectForms) == 'object'){ //only try to attach form handlers if we have forms available
				if(!this.layer.geometryType){ //current hack until this is thought out more, or problems arise
					this._attachFormHandlers(); //attach handlers to open form
				} else {
					this._attachZoomHandlers(); // attach those new handlers to the search result "buttons"
				}
			} else {
				this._attachZoomHandlers(); // attach those new handlers to the search result "buttons"
			}
			// if(callback) callback();
		},
		
		_createWClause: function(oSearchMappings){
			var wClause;
			if(this.alternativeDelete === true){
				wClause = "(fgStatus != 'Inactive' OR fgStatus is null)";
			} else {
				wClause = "";
			}
			
			for(var info in oSearchMappings){ // for each field in the search mappings
				
				var infoObj = oSearchMappings[info]; //
				
				try{
					if(infoObj.match == '')
						continue;
				} catch (e) {}

				if(info.indexOf('searchGroup') > -1){ //we want an or for searchGroups
					let wClause2 = '( ';
					for(info2 in oSearchMappings[info]){
						let infoObj2 = oSearchMappings[info][info2];
						if(infoObj2 == '*'){
							wClause2 += "(\""+info2+"\" = "+info2+")";
						} else {
							var compStr2 = "'" + infoObj2.match + "'";
							if(infoObj2.comp.toLowerCase() == "like" || infoObj2.comp.toLowerCase() == "ilike" || infoObj2.comp.toLowerCase() == "not ilike") // if the comparison is a 'like' or postgres 'ilike'
								compStr2 = "'%" + infoObj2.match.replaceAll("'","''") + "%'"; // put them % around for wildcard goodness
							wClause2 += "(UPPER(" + info2 + ") " + infoObj2.comp + " " + compStr2.toUpperCase() + ")"; // glue that statement together
						}
						wClause2 += " OR ";
					}
					if(wClause2.slice(-3) == "OR "){ //remove last or
						wClause2 = wClause2.slice(0,-4);
					}
					wClause2 += ' ) ';
					wClause += wClause2; //add group
					continue;
				}
				
				if(wClause != "") wClause += " " + this.joinMethod + " "; // we need an AND/OR if there is multiple statements
				
				if(infoObj == '*'){
				    wClause += "(UPPER("+info+") = "+info.toUpperCase()+")";
				} else if(infoObj.splitString){ //we are going to split the search term and do wildcard for each term
					let terms = infoObj.match.split(' ');
					for(let i=0; i<terms.length; i++){
						let term = terms[i];
						if(i>0){ //if more than one term
							wClause += ' AND '
						}
						term = term.replaceAll("'","''");
						wClause += "(UPPER(" + info + ") " + infoObj.comp + " '%" + term.toUpperCase() + "%')"; // glue that statement together
					}
				} else if(infoObj.addNull){ //add nulls Only used for facility search atm 2/29/24
						wClause += "(\"" + info + "\" " + infoObj.comp + " '" + infoObj.match.replaceAll("'","''") + "' or \"" + info + "\" is null)";
				} else {
					var compStr = "'" + infoObj.match + "'"; // 

					if(infoObj.comp.toLowerCase() == "like" || infoObj.comp.toLowerCase() == "ilike" || infoObj.comp.toLowerCase() == "not ilike"){ // if the comparison is a 'like' or postgres 'ilike'
						compStr = "'%" + infoObj.match.replaceAll("'","''") + "%'"; // put them % around for wildcard goodness
					} else if(infoObj.comp == 'IN'){ //hsc trap search, find all in array from multiselect
						//match must be an array of values, just text for now, until needed
						let str = '';
						for(s of infoObj.match){ //take array and format to string of "'value',""
							str += "'" + s + "',";
						}
						compStr = '(' + str.slice(0,str.length-1) + ')'; //remove last comma
					}
					wClause += "(UPPER(" + info + ") " + infoObj.comp + " " + compStr.toUpperCase() + ")"; // glue that statement together
				}

			}
			if(wClause == ''){ //if no where clause, return all
				wClause = '1=1';
			}
			return wClause;
		},
		
		query: function(oSearchMappings,callback){
			if(oSearchMappings)
				this.searchMapping = oSearchMappings;
			var context= this;
			var results = []; //holds all results
			var counter = 0;
			if(context.multipleLayers){
				//console.time('multiple layer search');
				context.layer.forEach(function(lay){
					runQuery(lay.url,lay.id)
				})
			} else {
				runQuery(context.url,context.layer.id)
			}

			function runQuery(url,layerID){
				counter++;
				if(url.indexOf('empty_layer_dont_delete') > -1){ //simple check, dont perform a search on a private layer
					counter--;
					return;
				}
				var queryTask = new QueryTask(url); // query the provided url
				var Query = new query();
				Query.outSpatialReference = new SpatialReference({ wkid:102100 });
				wClause = context._createWClause(context.searchMapping); // create where cluse

				Query.where = wClause;

				Query.returnGeometry = true;

				if(context.layer && !context.identity) // dont bother setting the identity again
					context.identity = context.layer.objectIdField;

				var outfields = context.outfields.slice(0); // clone the outfields
				outfields.push(context.identity); // append identity field to cloned outfields for querying
				Query.outFields = outfields;
				$("#searchControlsAccordion").children().eq(2).trigger("click");
				$("#searchSpinner").removeClass("hidden");
				
				queryTask.execute(Query,function(result){
					counter--;
					result.features.forEach(function(f){
						f._layer = window[layerID];
						results.push(f);
					})
				}, function(){
					counter--;
					queryTimeout();
				});
			}
			var queryChecker = setInterval(function(){ //wait until all queries have returned
				if(counter == 0){
					clearInterval(queryChecker);
					//console.timeEnd('multiple layer search');
					context.buildSearchHTML(results, callback); // populate the search pane
				}
			}, 400);
						
		},

		_layerZoomTo: function(sIdentity, clickedLayer, parentKey){ // assumes a query layer where a selction reveals the feature
			var layer = this.layer;
			var Query = new query();
			Query.where ="\""+ this.identity +"\"" + " = '" + sIdentity +"'";
			if (clickedLayer) layer = window[clickedLayer]; //pass currentlayer from feature
			if (this.whatsearch == 'septic/drain' && window[clickedLayer].type == 'Table'){ //this is special for bayEH drain/septic search, finds parent to zoom to if table record
				layer = window['ehPermitLayer']
				Query.where = "permit_id = '" + parentKey +"'";
			}
			layer.clearSelection();
			Query.outSpatialReference = new SpatialReference({ wkid:102100 });
			Query.returnGeometry = true;
			var $checkboxes = $(".legendCheckbox[layerid='"+layer.id+"']"); // get chekboxes that are for the layer in question
			if(layer.filterLayer === true){ // just click all the checkboxes that are used with this layer for now
				if($('#onlyShowResults').length > 0){ //if steam trap, we don't want to turn on all layers, just show results
					if(!$('#onlyShowResults').prop('checked')){
						$('#onlyShowResults').trigger('click');
					}
				} else {
					$checkboxes.each(function(n, i){
						//var filterValues = $(this).data().filtervalue;
						//if(filterValues.indexOf(templateValues[attrIndex]) != -1){ // we have our checkbox
							if($(this).hasClass("fa-square-o")){ // only checks for now no radios
								$(this).trigger('click');
							}
						//}
					});
				}
			} else {
				if($($checkboxes[0]).hasClass("fa-square-o")){
					$($checkboxes[0]).trigger('click');
				}
			}
			var self = this;
			layer.selectFeatures(Query, FeatureLayer.SELECTION_NEW, function(result){
				if(result.length == 0){
					showMessage('Parent feature not found.',null,'info')
					return;
				}
				var result = result[0], zoomPoint;
				if(result.geometry.type == 'polyline'){
					zoomPoint = new Point(result.geometry.paths[0][1][0], result.geometry.paths[0][1][1], new SpatialReference({ wkid:102100 }));
				} else if(result.geometry.type == 'polygon'){
                    zoomPoint = new Point(result.geometry.rings[0][2][0], result.geometry.rings[0][2][1], new SpatialReference({ wkid:102100 }));
                } else {
					zoomPoint = result.geometry;
				}

				map.centerAndZoom(zoomPoint, self.zoomLevel).then(function(){
					if(typeof layer.infoTemplate != 'undefined'){
						map.infoWindow.hide();
						map.infoWindow.setFeatures([result]);
						map.infoWindow.show(zoomPoint);
						var popupCloseConnect = dojo.connect(popup,"onSelectionChange",function(evt){
							layer.clearSelection();
							dojo.disconnect(popupCloseConnect);
						});
					}
				});
			});
		}
		
		
	};
	
	

		
	/// special modules //////	
 		
	
	
	/////////////////////// multiSelectTool //////////////////////////////
			/////////////////////////

		var highlightPointSymbol = new SimpleMarkerSymbol(
			SimpleMarkerSymbol.STYLE_X, 12,
			new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID,
			new Color([255,0,0]), 3),
			new Color([0,255,0,0.25])
		);

		/////////////////////////
		function MultiSelectTool(){
			this.onSelectionComplete = undefined;
			this.toolbar = new Draw(map);
			this.active = false;
		}

		MultiSelectTool.prototype = {

			_removeHandler: function(){
				if(typeof(this.onSelectionComplete) != 'undefined')
					dojo.disconnect(this.onSelectionComplete);
			},

			_setDrawnend: function(callback){
				this._removeHandler();
				this.onSelectionComplete = dojo.connect(this.toolbar, "onDrawEnd", callback);
			},

			deactivate: function(){
				this.toolbar.deactivate();
				this.active = false;
			},

			activate: function(callback){
				this._setDrawnend(callback);
				this.toolbar.activate(Draw.EXTENT);
				this.active = true;
			},

		};

		/// multi-select functionality base class
		function MultiSelectController(){
			this.title = "";
			this.cbColumnHeader = "";
			this.btnClass = "";
			this.instance = "";
			this.arrayOfLayers = undefined;
		}
		MultiSelectController.prototype = {
			active: false,
			graphicsLayer: undefined,
			cancelHandler: undefined,
			reSelectHandler: undefined,
			finishHandler: undefined,
			checkBoxHandler: undefined,
			arrayOFLayers: this.arrayOfLayers,
			type: this.type || "editable",
			toolName: undefined,
			toolbar: new MultiSelectTool(),

			stop: function(){
				if(this.active == false)
					return;
				if(typeof(this.graphicsLayer) != 'undefined')
					map.removeLayer(this.graphicsLayer);
				this._deactivateTool();
				$('#multiSelectDialogBox [data-toggle="tooltip"]').tooltip('destroy'); //if tooltips, destroy
				$('#multiSelectDialogBox').hide();
				$('#multiSelectTableWrapper').html('No Selected Features');
                if(typeof(this.cancelHandler) != 'undefined') this.cancelHandler.off();
				if(typeof(this.reSelectHandler) != 'undefined') this.reSelectHandler.off();
				if(typeof(this.finishHandler) != 'undefined') this.finishHandler.off();
				if(typeof(this.checkBoxHandler) != 'undefined') this.checkBoxHandler.off();
                				for(layer in parcelLayers){
					window[layer].setInfoTemplate(parcelLayers[layer].infoTemplate);
					try {
						window[layer].enableMouseEvents();
					} catch (e) {

					}
				}
				this.active = false;
				eval(this.instance + " = null;");
				eval("delete " + this.instance + ";");

				enableToolSelection();

				//$('#controlPaneExpando, #controlPaneHandle').removeClass('disabledButton');

							},

			_deactivateTool: function(){
				this.toolbar.deactivate();
                $("#multiSelectSelect").removeClass('disabled');
                $('#map_layers').css('cursor', '');
			},

			_activateTool: function(){
				if(this.toolbar.active == true)
					return;
				var constructor = this;
				if(typeof(this.graphicsLayer) != 'undefined' && this.graphicsLayer.graphics.length > 0)
					this.graphicsLayer.clear();

				$('#multiSelectTableWrapper').html('No Selected Features');
				$('#map_layers').css('cursor', 'crosshair');
				$("#multiSelectSelect").addClass('disabled');
				var selectToolCallback = function(extent){
					constructor._onCollectFeatures(extent);
				}
				this.toolbar.activate(selectToolCallback);
			},

			// this one is left blank so prototypes that extend this "class" can override it
			_onFinish: function(features){},
			
			_getTypeColumnName: function(){ //allow passing in of field names
				if(this.type == "snapshot"){
					return "Snapshot User";
				} else if(this.type == 'editable'){
					return "User";
				} else { //use object passed, for editing
					return this.type.display
				}
			},

			_populateTable: function(featuresInExtent){
				validationContentStr = "<table id='multiSelectTable'>" +
												"<tbody>" +
														"<tr class='multiSelectHeader'>" +
																"<td>Type</td>" +
																"<td>"+this._getTypeColumnName()+"</td>" +
																"<td style='text-align:center;'>"+this.cbColumnHeader+"</td>" +
														"</tr>";
						for(var i=0; i<featuresInExtent.length;i++){
								var feature = featuresInExtent[i];
								validationContentStr += "<tr>" +
																"<td>"+feature._graphicsLayer.name+"</td>"
								if(this.type.type == 'date'){ //if date we need to format properly
									validationContentStr += "<td>"+(feature.attributes[this.type.name] == null ? '' : moment(feature.attributes[this.type.name], 'x').format('MM/DD/YYYY'))+"</td>"
								} else {
									var val = ''; //will not write null to screen, only blank
									if(feature.attributes[(this.type.name ? this.type.name : 'created_user')] != null){ //if object passed use that, else created_user and is not null
										val = feature.attributes[(this.type.name ? this.type.name : 'created_user')]
									} 
									validationContentStr += "<td>"+val+"</td>"
								}
								validationContentStr += "<td style='text-align:center;'><input class='multiSelectCKBox' type='checkbox' value="+i+" checked></td>" +
														"</tr>";
						}

						validationContentStr += "</tbody>" +
										"</table>";
				$('#multiSelectTableWrapper').html(validationContentStr);
				$('#multiSelectTableWrapper').perfectScrollbar('update');
			},

			_collectFeatures: function(extent){
				var extentPoly = new Polygon([[extent.xmin,extent.ymin],[extent.xmin,extent.ymax],[extent.xmax,extent.ymax],[extent.xmax,extent.ymin],[extent.xmin,extent.ymin]]);
				var featuresInExtent = [];
				var validationContentStr = '';
				let hasLinkedFeatures = false;

				if(this.arrayOfLayers != undefined){
					for(var i = 0; i < this.arrayOfLayers.length; i++){
						if(this.arrayOfLayers[i].visibleAtMapScale && this.arrayOfLayers[i].visible){
							var graphicsArr = this.arrayOfLayers[i].graphics;
							for(var j = 0; j <graphicsArr.length;j++){
								var graphic = graphicsArr[j];
								var graphicGeom = graphic.geometry;
								if(graphicGeom && graphic.visible){//account for hidden Filter layer graphics
									if(geometryEngine.intersects(graphicGeom,extentPoly)){
										let addFeat = true
										if(editDays > -1){ //user is restricted to own data
											if(graphic.attributes.created_user != userProfileName){
												addFeat = false; //not users data
											}
											if(addFeat){
												let goodDate = formViewer.canEditUsingEditDays(graphic.attributes.created_date);
												if(!goodDate){ //feature is time restricted
													addFeat = false;
												}
											}
										}
										if(this.toolName == 'linkFeaturesToSystem'){
											if(graphic.attributes.system_globalid){ //for this tool we only want non linked features
												addFeat = false;
												hasLinkedFeatures = true;
											}
										}
										if(addFeat){
											featuresInExtent.push(graphic);
											var symbol = highlightSymbol;
											if(graphicGeom.type == 'point')
												symbol = highlightPointSymbol;
											var highlightGraphic = new Graphic(graphicGeom, symbol, {"Index":featuresInExtent.length-1});
											this.graphicsLayer.add(highlightGraphic);
										}
									}
								}
							}
						}
					}
				}
				if(hasLinkedFeatures){
					showMessage('Only unlinked features are able to be linked with this tool', 9000, 'info');
				}
				return featuresInExtent;
			},

			_onCollectFeatures: function(extent){

				var featuresInExtent = this._collectFeatures(extent);

				if(featuresInExtent.length > 0){ // if any features within selection
						const self = this;
						var constructor = this;
						$('#multiSelectAccept').removeClass('disabled');
						this._deactivateTool();
						this._populateTable(featuresInExtent);

						if(typeof(this.reSelectHandler) !== 'undefined') this.reSelectHandler.off(); // clear events if already defined
						if(typeof(this.finishHandler) !== 'undefined') this.finishHandler.off();
						if(typeof(this.checkBoxHandler) !== 'undefined') this.checkBoxHandler.off();

						this.finishHandler = $("#multiSelectAccept").on("click", function(){
								var chosenFeatures = constructor._getFinalFeatures(featuresInExtent);
								constructor._onFinish(chosenFeatures);
								constructor.stop();
						});

					    this.reSelectHandler = $("#multiSelectSelect").on("click", function(){
								constructor._activateTool();
						});

						if(this.toolName == 'linkFeaturesToSystem'){ //we only want one system point checked
							let first = -1;
							let moreThanOne = false; //more than one system, for new linking tool
							$('#multiSelectTable td:first-child').each(function(i,ea){
								let index = i-1; //first row is table header row, so we need to correct
								if($(this).text() == 'System'){
									if(first == -1){
										first = index;
										$(this).parent().find('input').prop('checked',true); //ensure the first is checked
									} else {
										moreThanOne = true;
										$(this).parent().find('input').prop('checked',false); //uncheck the rest
										constructor.graphicsLayer.graphics[index].hide();
									}
								}
							})
							if(first == -1){
								showMessage('There must be at least one System in your selection.', 6000, 'info');
								$('#multiSelectSelect').trigger('click'); //need a new selection
								return;
							}
							if(moreThanOne){ //warn the user that only one system point is allowed
								showMessage('Only one system may be selected at a time', 10000, 'info');
							}
						}

						this.checkBoxHandler = $('.multiSelectCKBox').on("click", function(){
							var index = $(this).val();
							let disableOtherSystems = false; //used for linking features to system points, more than one will make this true
							$('#multiSelectAccept').removeClass('disabledButton');
							if($('.multiSelectCKBox:checked').length < 1){
									$('#multiSelectAccept').addClass('disabledButton');
							}
							if(self.toolName == 'linkFeaturesToSystem'){
								if($(this).parent().parent().find('td:first').text() == 'System'){ //if this checkbox is a system, make sure all others are unchecked
									disableOtherSystems = true;
								}
							}
						   	for(var i =0; i< constructor.graphicsLayer.graphics.length;i++){
								var highlightGraphic = constructor.graphicsLayer.graphics[i];
								if(highlightGraphic.attributes.Index == index){
									if($(this).is(':checked')) {
										highlightGraphic.show();
									} else {
										highlightGraphic.hide();
									}
								} else if(disableOtherSystems) {
									let thisSelect = $('#multiSelectTable .multiSelectCKBox[value='+i+']');
									if($(thisSelect).parent().parent().find('td:first').text() == 'System'){
										$(thisSelect).prop('checked',false);
										highlightGraphic.hide();
									}
								}
						   	}
						});
				}

			},

			_getFinalFeatures: function(featuresInExtent){
					var aFinal = [];
					var selectedCheckboxes = $('.multiSelectCKBox:checked');
					for(var i = 0; i< selectedCheckboxes.length; i++){
						   var index = $(selectedCheckboxes[i]).val();
						   aFinal.push(featuresInExtent[index]);
					}
					return aFinal;
			},

			start: function(onCancelCallback){
				if(this.active == true)
					return;
				hideControlPane();
				disableToolSelection();
				//$('#controlPaneExpando, #controlPaneHandle').addClass('disabledButton');

				$("#multiSelectAccept").off( "click" ); // just to make sure that button doesnt have any other events tied to it
				var constructor = this;
				this.active = true;
				popup.hide();
				for(layer in parcelLayers){
						window[layer].setInfoTemplate(null);
						try {
							window[layer].disableMouseEvents();
						} catch (e) {

						}
				}
				this.graphicsLayer = new GraphicsLayer();
				map.addLayer(this.graphicsLayer);
				$('#multiSelectTitle').html(this.title);
				$('#multiSelectAccept').removeClass("btn-danger").removeClass("btn-success").removeClass("btn-primary").addClass(this.btnClass);

				this.cancelHandler = $("#multiSelectCancel").on("click", function(){
					constructor.stop();
					if(onCancelCallback)
						onCancelCallback();
				});

				this._activateTool();
				$('#multiSelectDialogBox').show();
				$('#multiSelectDialogBox [data-toggle="tooltip"]').tooltip().tooltip('show'); //if tooltips, initialize and show
				
											},
		};





	/////////////////////// OptionsBox //////////////////////////////
	
	
	function OptionsBox(options){
        this.options = {};
        this.options.title = options.title || OptionsBox.DEFAULTS.title;
        this.options.content = options.content || OptionsBox.DEFAULTS.content;
        this.options.cancel = options.cancel || OptionsBox.DEFAULTS.cancel;
        this.options.finish = options.finish || OptionsBox.DEFAULTS.finish;

        this.optionsBoxID = this._generateID();
        this._buildHTML();
    }
    
    OptionsBox.DEFAULTS = {
        title       : 'Title',
        content     : '',
        cancel      : false,
        finish      : false,
    }
    
    OptionsBox.prototype = {
        
        _generateID: function(){
            return new Date().getTime().toString() + (Math.floor(Math.random() * (999 - 100)) + 100).toString();
        },
        
        _buildHTML: function(){
            var buttonHTML = '';
            if(this.options.cancel)
                buttonHTML += '<div class="col-xs-4"><button id="'+this.optionsBoxID+'_OptionsCancel" class="btn btn-default btn-sm">Cancel</button></div>';
            if(this.options.finish)
                buttonHTML += '<div class="col-xs-4"><button id="'+this.optionsBoxID+'_OptionsFinish" class="btn btn-success btn-sm" title="">Finish</button></div>';
            var html = '' +
                '<div id="'+this.optionsBoxID+'_OptionsBox" class="noSelect optionsBox" style="display: none">' +
                    '<div id="'+this.optionsBoxID+'_OptionsHeading" class="optionsBoxHeading"><span id="'+this.optionsBoxID+'_Handle" class="fa fa-ellipsis-v optionsBoxHandle"></span><span id="'+this.optionsBoxID+'_OptionsTitle" class="optionsBoxTitle">'+this.options.title+'</span></div>' +
                    '<div id="'+this.optionsBoxID+'_OptionsWrapper" class="optionsBoxWrapper container-fluid">' +
                        '<div id="'+this.optionsBoxID+'_OptionsContent" class="optionsBoxContent">' +
                            this.options.content +
                        '</div><br>' +
                        buttonHTML +
                    '</div>' +
                '</div>';
                
            $('body').append(html);
            $('#'+this.optionsBoxID+'_OptionsBox').draggable({ handle: '#'+this.optionsBoxID+'_Handle', containment: $("#map") });
        },
        
        setTitle: function(content){
             $('#'+this.optionsBoxID+'_OptionsTitle').html(content);
        },
        
        setContent: function(content){
            $('#'+this.optionsBoxID+'_OptionsContent').html(content);
        },
        
        setOnCancel: function(callback){
            $('#'+this.optionsBoxID+'_OptionsCancel').off('click').on("click", function(){
                callback();
            }); 
        },
        
        setOnFinish: function(callback){
            $('#'+this.optionsBoxID+'_OptionsFinish').off('click').on("click", function(){
                callback();
            }); 
        },
        
        show: function(){
            $('#'+this.optionsBoxID+'_OptionsBox').show();
        },
        
        hide: function(){
            $('#'+this.optionsBoxID+'_OptionsBox').hide();
        },
        
        destroy: function(){
            $('#'+this.optionsBoxID+'_OptionsBox').remove();
        },
        
    }
    
    window.OP = OptionsBox;	
	///////////////////////// DOW SHTUFF ////////////////////////////////
 
 		
	///////////////////////// ACS SHTUFF ////////////////////////////////
		
	//// insights //////
		
	/////////////////  FEATURE SERVICE EDITING STUFF /////////////////////
		
	
				//dojo.keys.copyKey maps to CTRL on windows and Cmd on Mac., but has wrong code for Chrome on Mac
				snapManager = map.enableSnapping({
					layerInfos: snappingLayerInfos, snapKey: has("mac") ? keys.META : keys.CTRL, snapPointSymbol: parperpSnappingCross
				});
			
var SnappingUtility = function(options){

	this.options = {};
	this.options.midpoint = options.midpoint || SnappingUtility.DEFAULTS.midpoint;
	this.options.snappingSymbol = options.snappingSymbol || SnappingUtility.DEFAULTS.snappingSymbol;

	this.active = false;
	this.layerInfos =null;
	this.snappingPoint = null;
	this._snappingCandidates = [];
	this.candidatesUpdated = false;
	this._onHandleMouseMoveConnect = null;

	var context = this;
	this._mapUpdateConnect = map.on('update-end', function(evt){ // mostly used for editing snapping
		if(!context.active)
			context.candidatesUpdated = false;
	});
	this._extentChangeConnect = map.on('zoom-end, pan-end', function(evt){ // used for draw snapping
		context.candidatesUpdated = false;
	});
}

SnappingUtility.DEFAULTS = { // default options for snapping utility
	midpoint: false,
	snappingSymbol: new SimpleMarkerSymbol(SimpleMarkerSymbol.STYLE_CROSS, 28,
		new SimpleLineSymbol(SimpleLineSymbol.STYLE_SOLID,
		new Color([255,90,0,1]), 3),
		new Color([255,90,0,1])
	),
}

SnappingUtility.prototype = {

    activate: function(){
		this.active = true;

		this._getSnappingCandidates(); // get initial snapping candidates

		var context = this;
		this._onHandleMouseMoveConnect = map.on('mouse-move', function(evt){ // mouse move handler mimicing esri's
			context._onHandleMouseMove.call(context, evt);
		});
    },

    deactivate: function(){
		this.active = false;
		
		dojo.disconnect(this._onHandleMouseMoveConnect); // remove mouse move handler
		this.snappingPoint = null; // reinitialize vars
		this._snappingCandidates = []; 
	},

	setLayerInfos: function(layerInfos){
		this.layerInfos = layerInfos;
	},

	_onHandleMouseMove: function(evt){ // function to call on mouse move

		if(!snapManager.alwaysSnap) return;

		if(this.options.midpoint){

			this.snappingPoint = this.getSnappingPoint(evt.screenPoint); // get nsapping object containing point feature and symbol
		
			try {
				if(this.snappingPoint){
					snapManager.setSnappingPoint(this.snappingPoint); // interface with snappingManager (esri mod)
				} else {
					snapManager.setSnappingPoint(null);
				}
			} catch(err) {
				console.log('error in trying to interface with snappingManager.js mod: ' + err);
			}
		}

	},
	
	_getMidpointOfSegement: function(start, end) { // segment - array    gets the midpoint between any edge
		var startScreenPt = map.toScreen(new Point(start, map.spatialReference));
		var endScreenPt = map.toScreen(new Point(end, map.spatialReference));

		var sc_midpoint = new ScreenPoint();
		sc_midpoint.setX((startScreenPt.x + endScreenPt.x)/2);
		sc_midpoint.setY((startScreenPt.y + endScreenPt.y)/2);

		return sc_midpoint;
	},

    _getSnappingCandidates: function(){
		this.setLayerInfos(snapManager.layerInfos); // get current layer infos from snap manager
		var candidates = [];
		for(var i=0; i< this.layerInfos.length; i++){
			var graphics = this.layerInfos[i].layer.graphics;
			for(var j=0; j<graphics.length; j++){
				var graphic = graphics[j];
				var geometry = graphic.geometry; 
				if(!geometry || !map.extent.contains(graphic._extent)) continue; // make sure geometry actually exists and the current map extent contains it
				var coords = geometry.paths || geometry.rings || [];
				for(var k=0; k<coords.length; k++){
					var path = coords[k];
					 for(var h=0; h<path.length; h++){
						// midpoint collection
						 if(this.options.midpoint){
							var start, end;
							if(end = path[h+1]){
								start = path[h];
								candidates.push(this._getMidpointOfSegement(start, end)); // add midpoint to cadidates array
							}
						 }

					 }
				}
			}
		}

		this.candidatesUpdated = true;
		this._snappingCandidates = candidates;

	},
	
	_getClosestCandidate: function(screenPt){ // gets the closest candidate to the input point, must be within tolerance set on snapManager

		var closestCandidateDistance = Infinity;
		var closestCandidate = undefined;

		for(var i=0; i<this._snappingCandidates.length; i++){
			var candidate = this._snappingCandidates[i];
			var distance = Math.sqrt(Math.pow((candidate.x - screenPt.x),2) + Math.pow((candidate.y - screenPt.y),2));
			if(distance < snapManager.tolerance){
				if(distance < closestCandidateDistance){
					closestCandidateDistance = distance;
					closestCandidate = candidate;
				}
			}
		}
		
		return closestCandidate;
	},
	

    getSnappingPoint: function(screenPoint){ // used internally and externally. functions similarly to the method of the same name in the esri snapping manager
		if(!this.candidatesUpdated)
			this._getSnappingCandidates();
	
		var snappingPoint;

		if(snappingPoint = this._getClosestCandidate(screenPoint)){
			return { // create snapping object with symbol
				point: map.toMap(snappingPoint),
				symbol: this.options.snappingSymbol
			}
		}

		return snappingPoint;
	},
	

}


if(typeof(snappingUtilityOptions) != 'undefined'){ // for now were not even going to create this thing unless options are present in the config

	window.snappingUtility = new SnappingUtility(snappingUtilityOptions);


	$(function(){ // attach these events to initEditing so they get intialized at the right time
		var core_initEditing = initEditing;
		window.initEditing = function(){
			core_initEditing.apply(this);

			toolbar.on('activate', function(){
				snappingUtility.activate();
			});

			toolbar.on('deactivate', function(){
				snappingUtility.deactivate();
			});
		};

	});

}




	
	var overTriggered = false, overHandler, outHander;
	// Rollover effect for markerSymbols
	function resetMarkerHoverHander(){
		if(overHandler !== undefined){
			$(overHandler).off('mouseover');
			$(overHandler).off('mouseout');
		}
		
		overTriggered = false;
		overHandler = $('g image, g circle').on("mouseover", function(evt){
			if(typeof(evt.currentTarget.e_graphic) !== "undefined" ){
				if(typeof(evt.currentTarget.e_graphic._layer.infoTemplate) !== "undefined"){
					if(evt.currentTarget.e_graphic._layer.infoTemplate !== null){
						if(typeof(evt.currentTarget.e_graphic._layer.infoTemplate.content) !== "undefined"){
						  overTriggered = true;
						  $(evt.target).css({cursor: "pointer"});
						  if(evt.target.nodeName === "circle"){ // circle is not a picture marker symbol. We only need to change the radius. Center point is ok.
							$(evt.target).attr('r', evt.target.r.baseVal.value + 2);
						  } else {
							$(evt.target).attr('height', evt.target.height.baseVal.value + 4);
							$(evt.target).attr('width', evt.target.width.baseVal.value + 4);
							$(evt.target).attr('y', evt.target.y.baseVal.value - 2);
							$(evt.target).attr('x', evt.target.x.baseVal.value - 2);
						  }
						}
					}
				}
			}
		});
		outHandler = $('g image, g circle').on("mouseout", function(evt){
		  if(overTriggered === true){
			if(typeof(evt.currentTarget.e_graphic) !== "undefined" ){
				if(typeof(evt.currentTarget.e_graphic._layer.infoTemplate) !== "undefined"){
					if(evt.currentTarget.e_graphic._layer.infoTemplate !== null){
						if(typeof(evt.currentTarget.e_graphic._layer.infoTemplate.content) !== "undefined"){
							if(evt.target.nodeName === "circle"){ // circle is not a picture marker symbol
							  $(evt.target).attr('r', evt.target.r.baseVal.value - 2);
							} else {
							  $(evt.target).attr('height', evt.target.height.baseVal.value - 4);
							  $(evt.target).attr('width', evt.target.width.baseVal.value - 4);
							  $(evt.target).attr('y', evt.target.y.baseVal.value + 2);
							  $(evt.target).attr('x', evt.target.x.baseVal.value + 2);
							}
							over = false;
						}
					}
				}
			}
		  }
		});
	}
	map.on('extent-change', resetMarkerHoverHander);
	map.on('update-end', function(){
		resetMarkerHoverHander();
	});
	
	// hover effect for point features
	if(map.loaded){ // if map is ready durring page load
		map.on('update-end', resetMarkerHoverHander);
		map.on('extent-change', resetMarkerHoverHander);
		map.on('layer-add', resetMarkerHoverHander);
	} else {
		map.on('load', function(){ // wait if not ready
			map.on('update-end', resetMarkerHoverHander);
			map.on('extent-change', resetMarkerHoverHander);
			map.on('layer-add', resetMarkerHoverHander);
		})
	}
		
	map.on('pan-end', function(){
		updateURLParams();
	});
	map.on('zoom-start', function(e){
		if($('#swipeToggle').hasClass('fa-check-square-o')){
			$('#layerSwipeSelect').css({'pointer-events': 'none', 'opacity': '.5'});
		}
	});
	
	function setParcelDefExp(){
		if(!parcelLayers)
			return;
		
		var scale = map.getScale();
		var exp = null;
		var expVal = 1;
		// scale levels are 36111, 18055, 9027, 4513
		if (scale > 36000){
			//exp = '"Shape__Area" > 43560';
			expVal = 43560;
		}
		else if (scale > 18000 && scale < 36000){
			//exp = '"Shape__Area" > 13068.033079464';
			expVal = 13068.033079464;
		}
		var setDef = function(layer){

			if(expVal > 1 && layer.geometryProperties && layer.geometryProperties.shapeAreaFieldName && layer.geometryProperties.units){
				if(layer.geometryProperties.units=='esriFeet'){
					layer.setDefinitionExpression('"'+layer.geometryProperties.shapeAreaFieldName+'" > '+(expVal*3.048));
				}else{//assume meters
					layer.setDefinitionExpression('"'+layer.geometryProperties.shapeAreaFieldName+'" > '+(expVal));
				}
			}else{
				layer.setDefinitionExpression(null);
			}
			/*if(layer.fields.some(function(obj) {
				return obj.alias === 'Shape__Area';
			})) {
				layer.setDefinitionExpression(exp);
			} */
		}
		
		for(var layer in parcelLayers){
			try {
				var parcelLayer = window[layer];
				if(parcelLayer.loaded){
					setDef(parcelLayer);
				} else {
					parcelLayer.on('load', function(e){
						setDef(e.layer);
					});
				}
			} catch (e) {
				console.log('error in setting parcel definition expression: ' + e);
			}
		}
	};
	
	map.on('zoom-end', function(e){
		if($('#swipeToggle').hasClass('fa-check-square-o')){
			$('#layerSwipeSelect').css({'pointer-events': 'all', 'opacity': '1'});
		}
		updateURLParams();
		// resize background for scalebar
		resizeScalebarBackground();
        // set TOC visibility
        updateTOCvisibility();  
		
		if(typeof(setDisplayedParcels)=='function'){ // just checking if the function is available then running it if so w/ javascript
			setDisplayedParcels();
		} else {
			setParcelDefExp();
		}

	 });

	 map.on('load', function(e){ // run parcel def exp setting on map load also (the scale wont be set yet without this)
		if(typeof(setDisplayedParcels)=='function'){ // just checking if the function is available then running it if so w/ javascript
			setDisplayedParcels();
		} else {
			setParcelDefExp();
		}
	 });





	     
	
        
        /*
        #################################
         START CODE TO SET HOVER POPUPS
        #################################
        */
        
        fireStationsLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.NAME;
            openToolTip(evt, content);
        });
        fireStationsLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
        
        pollingPlaceLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.NAME + "<br>" + evt.graphic.attributes.PRECINCT;
            openToolTip(evt, content);
        });
        pollingPlaceLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
        
        airportsLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.NAME;
            openToolTip(evt, content);
        });
        airportsLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
        
        boatLaunchesLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.ACCESSSITE;
            openToolTip(evt, content);
        });
        boatLaunchesLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
        
        fairgroundsLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.NAME;
            openToolTip(evt, content);
        });
        fairgroundsLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
        
        courthouseLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.NAME;
            openToolTip(evt, content);
        });
        courthouseLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
        
        historicalMarkersLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.NAME;
            openToolTip(evt, content);
        });
        historicalMarkersLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
        
        librariesLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.NAME;
            openToolTip(evt, content); 
        });
        librariesLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
        
        planetariumLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.NAME;
            openToolTip(evt, content);
        });
        planetariumLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
		nonMotorTrailsLayer.on("mouse-over", function(evt){
            var content = "Trail Name: " + evt.graphic.attributes.NAME + "<hr>Surface Type: " + evt.graphic.attributes.SURFACE;
            openToolTip(evt, content);
        });
        nonMotorTrailsLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
		proposedTrailsLayer.on("mouse-over", function(evt){
            var content = "Trail Name: " + evt.graphic.attributes.NAME + "<hr>Surface Type: " + evt.graphic.attributes.SURFACE;
            openToolTip(evt, content);
        });
        proposedTrailsLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
		trailheadLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.NAME;
            openToolTip(evt, content);
        });
        trailheadLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
        schoolsLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.NAME;
            openToolTip(evt, content);
        });
        schoolsLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
        });
        municipalFacilitiesLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.NAME;
            openToolTip(evt, content);
        });
        municipalFacilitiesLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
		}); 
		
        zoningLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.ZONING;
            openToolTip2(evt, content);
        });
        zoningLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
		});
		
        zoningBeaverLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.ZONING;
            openToolTip2(evt, content);
        });
        zoningBeaverLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
		});

        zoningHamptonLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.ZONING;
            openToolTip2(evt, content);
        });
        zoningHamptonLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
		});

        zoningKawkawlinLayer.on("mouse-over", function(evt){
            var content = evt.graphic.attributes.ZONING;
            openToolTip2(evt, content);
        });
        zoningKawkawlinLayer.on("mouse-out", function(){
            dijitPopup.close(popDialog);
		});
		
		
		bcatsLayer.on("mouse-over", function(evt){

			var newWidth = parseInt($(evt.target).attr("stroke-width")) + 5;
			$(evt.target).attr("stroke-width", newWidth);
			var content = evt.graphic.attributes.FULLNAME_1;

			if(trimThis(content) != "")
				openToolTip2(evt, content);
		});
		bcatsLayer.on("mouse-out", function(evt){
			var newWidth = parseInt($(evt.target).attr("stroke-width")) - 5;
			$(evt.target).attr("stroke-width", newWidth);
			dijitPopup.close(popDialog);
		});
		
    	
	//turn off all autocomplete on input fields
	$('input').each(function(i,ea){
		if($(ea).parents('form').length == 0){
			$(ea).attr({'autocomplete':'off','aria-autocomplete':'none'})
		}
	})
	
	$('#loginButton').on("click", function(){
		var user = $('#userNameInput').val();
		var pwd = $('#passwordInput').val();
				loginPrivateLayerUser(user, pwd);	});
	$('#passwordInput').on('keydown', function(e){
		if(e.which == 13 || e.keyCode == 13){
			$('#loginButton').trigger("click");
		}
	});
	
	
	loginPrivateLayerUser = function loginPrivateLayerUser(uName, pwd){
 	$('#loginSpinner').fadeIn();
	$('#loginButton').attr({disabled: 'disabled'});
	
	var canWrite = false;

	var postData = { 
		// currentMap is a global variable set on page load so no need to pass in
		map: currentMap,
		username: uName,
		password: pwd,
		privateLayers: 'y'
	};
	
	// request login info
	$.ajax({
		url: "ws/sso/gatekeeperlogin.php",
		method: "POST",
		data: postData,
		success: function(data){
			try{
				if (data !== ''){
					if(data.status != 'fail' && data.map == currentMap){

						// log em' in
						window.location.reload(); // just reload current page with current url params.
						
					} else {
						$('#loginMessage').html(data.error).fadeIn();
						
						setTimeout(function(){
							$('#loginMessage').fadeOut();
						}, 10000);
					}
				}
			} catch(err){
				console.log('Error, no data returned from function loginUser(): ' + err.message);
			}
		},
		complete: function(){
			$('#loginButton').prop('disabled',false);
			$('#loginSpinner').fadeOut();
		}
	});
};

$('#logout').on("click", function(){
	unload();
});	
	
	// ##############################################
	// ####             FEE SERVICE              ####
	// ##############################################
		
	
	window.wipeLRP = function(){
		$(".lrpDataCell").html("--");
		$('#detailsParcelNo').html("No Parcel Selected");
		$("#lrpComments").html("<p>No Comments</p>");
		$('#salesInfo').html("No Records Found");
		if($( "#salesInfo" ).hasClass('ui-accordion')){
			$( "#salesInfo" ).accordion( "refresh" );
		}
		
		$('#delTaxInfo').html("No Records Found");
		if($( "#delTaxInfo" ).hasClass('ui-accordion')){
			$( "#delTaxInfo" ).accordion( "refresh" );
		}
		
		$('#taxInfo').html("No Records Found");
		if($( "#taxInfo" ).hasClass('ui-accordion')){
			$( "#taxInfo" ).accordion( "refresh" );
		}
	}
	
	var xhr4lrp = (window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP"));
	window.par4getParcelData;
	window.parIdStr4getParcelData;
	getParcelData = function(parcel, parIdStr, transactionId){//parcel is a string "00-00-00-00", transactionId is a string "000000"



			// check if something else is focused in the data pane and if so, fade it out and fade in the LRP (generalDetailsScrollPane)
			$("#generalDetailsScrollPane > div").each(function(index){
				if($("#generalDetailsScrollPane > div").eq(index).css("display") !== "none"){
					//if($("#generalDetailsScrollPane > div").eq(index).prop("id") !== "lrpFeeOverlay"){
						// check if something else is focused in the data pane (except fee overlay) and if so, fade it out and fade in the insightsOandMWrapper
						$("#generalDetailsScrollPane > div").eq(index).stop().fadeOut(400, function(){
							$("#generalDetailsWrapper").fadeIn();
						});
					//}
				}
			});
		

		// insert a hidden reference to the parcel layer on the LRP page. This is used in case the user closes the popup, but still tries to generate a pdf.
		$('#detailsParcelLayer').html(parIdStr);
		
		par4getParcelData = parcel;
		parIdStr4getParcelData = parIdStr;
		setTimeout(function(){
			searchData = null; // wipe out searchData
			currentPin = parcel;			// currentPin and currentParcelLayer sets a global variable for tracking which parcel is last clicked. 
			currentParcelLayer = parIdStr;	// Very important. Note: this variable is only used for fee service.
			var mapStr;
			
			if(typeof(parIdStr) !== "undefined"){
				if(parIdStr !== "" && parIdStr !== null){
					// find the string from the parcelLayers object from the config file, which is part of the url to get the record from the correct LRP database
					mapStr = parcelLayers[parIdStr].lrpMapStr;
				}
			} else {
				if(popup.selectedIndex === -1){
					return;
				}
				if(typeof(parcelLayers[popup.features[popup.selectedIndex]._layer.id]) !== "undefined"){ // check if it's a parcel at all
					mapStr = parcelLayers[popup.features[popup.selectedIndex]._layer.id].lrpMapStr;
				}
				
			}
			
			if(mapStr === undefined){
				console.log("getParcelData: no way to find layer Id");
				return false;
			}
			
			var url = "ws/GPDsearch.php?pin=" + parcel+"&Map="+mapStr+"&taxh="+config_taxh;
			
			// AJAX request
			
						xhr4lrp.onreadystatechange = XHRhandler4lrp;
						xhr4lrp.open("GET", url, true);
						xhr4lrp.send(null);
						// wipe everything from LRP
						wipeLRP();
								
		}, 250);
	}
	// handle response
	var XHRhandler4lrp = function XHRhandler4lrp() { // lrp
		if (xhr4lrp.readyState == 1) {
			// Add spinner/overlay to lrp
			$('#generalDetailsOverlay').fadeIn(400, function(){
				//$('#salesInfo').html("No Records Found"); // #salesInfo already gets wiped, and this just caused a bug.
				if($( "#salesInfo" ).hasClass('ui-accordion')){
					$( "#salesInfo" ).accordion( "refresh" );
				}
			});
			$('#generalDetailsScrollPane').stop().animate({opacity:0}, 300);
					}

		if (xhr4lrp.readyState == 4) {
			if(xhr4lrp.responseXML != null){
				searchData = XML2jsobj(xhr4lrp.responseXML.documentElement);
				populateDetailData();// found in includes/lrpScript.php section
				if($('#detailsParcelNo').html() === ""){
					$('#detailsParcelNo').html("No Parcel Selected");
				}
								
				// Align rows that wrap in general info if not printing a report... 
				if(loadReport == false){
					$('#generalInfo_tbodyLeft tr th').each(function(index){
						$('#generalInfo_tbodyRight tr th div').eq(index).css('height', $('#generalInfo_tbodyLeft tr th').eq(index).height());
					});
				}

				// if page is loading to print a report, then create the report
				if(loadReport == true){
					$('.genReport').first().trigger("click");
				}
			} else {
				$('#generalDetailsScrollPane').stop().animate({'opacity':'1'});
				$('#generalDetailsOverlay').fadeOut();
				console.log('Null Response for LRP record');
			}
		}
	}
	
	// Parcel Records Report Code
	$( "#lrpImage" ).on("error", function() {
    //console.log("Handler for .error() called.");
	$(this).attr("src", "img/noImage.jpg");
});

 
	getPopupGraphic = function getPopupGraphic(parcelArr){
		return new Graphic(parcelArr[0].geometry, hilightedParcelSymbol, {"pin": parcelArr[0].attributes[parcelLayers[parcelArr[0]._layer.id].pinField]});
	};

	// NOTE: handlers for the "Report" button are in uiHandlers.php
	
	var prevCenter, prevZoom, centerOfParcel, rptDateTimeStr;
	window.createReport = function(){
		$('#printHint, .lrpCustomLink').addClass('hidden');
		$('#printButtonsWrapper').stop().fadeOut(400);
		$('#printControlsAccordionWrapper').stop().fadeOut(400, function(){
			$('#reportControls').fadeIn();
		});
		if(!firefox){
			//$('head').append('<style id="printMargins">@page {margin: .5in .25in;}</style>');
		} else {
			$('head').append('<style id="printMargins">@page {margin: .25in 0in;}</style>');
		}
		var animationSpeed = 400;
		if(loadReport){
			animationSpeed = 1;
		}
		prevZoom = map.getZoom();
		prevCenter = [centerLngLat[0], centerLngLat[1]];
		$('#dataPaneExpando, #dataPaneHandle').animate({opacity: 0}, animationSpeed).addClass('noPointerEvents');
		if(isMobile){
		   $('#mobileDataPane, #modalOverlay').fadeOut();
		}
		$('.dijitTooltipDialogPopup').css('opacity', 0);
		//parcelLayer.disableMouseEvents();
		for(layer in parcelLayers){
			try {
				window[layer].disableMouseEvents();
			} catch (e) {

			}
		}
		
		$('#neatline').animate({opacity: 0}, animationSpeed, function(){
			$('#navToolbar, #overviewMapContainer, #newSearch, #insightsQuickAccessToolbar').addClass('hidden');
			$('#closeReport').removeClass('hidden');
			$('#contentContainer, #printedPage').addClass('portraitVariable');
			$('#printedPage, #neatline, #generalDetailsWrapper, #salesInfo, #delTaxInfo, .saleDateSec, .taxInfoH3, .taxTableSuperHeader').addClass('reportView');
			$('#printedPage').append($('#generalDetailsWrapper'));
			$('#salesInfo, #taxInfo, #delTaxInfo, #lrpResBuildingWrapper, #lrpAgBuildingWrapper, #lrpComBuildingWrapper').accordion('destroy');
			$('#generalDetails').after($('#neatline'));
			map.resize(true);
			map.reposition();
			$('#generalDetails').addClass('visuallyHidden');
			
			for(layer in parcelLayers){
				//window[layer].setInfoTemplate(parcelLayers[layer].infoTemplate);
				var pinQuery;
				pinQuery = new query();
				pinQuery.outSpatialReference = new SpatialReference({ wkid:102100 });
				pinQuery.where =  `"${parcelLayers[layer].pinField}" = '${$('#detailsParcelNo').html()}'`;
				pinQuery.returnGeometry = true;
				window[layer].selectFeatures(pinQuery, window[layer].SELECTION_NEW, function(selParcel){
					console.log(selParcel); // this is here for debugging parcel layer array conversion
					if(selParcel.length < 1){
						return;
					}
					var parExt = selParcel[0]._extent.getExtent();
					highlightLayer.add(getPopupGraphic(selParcel));
					map.setExtent(parExt, true).then(function(){
						if(params.pdf === "1"){
							// when pdfs are built the boxes are sometimes checked (map, tax hist, etc) well before the results are in from LRP, so the sections were not correctly hidden.
							fixReportRowAlignment();
							toggleReportOptions();
							if(map.updating === false){
								pdfReady = 2;
								console.log('pdfReady = 2');
							} else {
								var printInterval = setInterval(function(){
									if(map.updating === false){
										clearTimeout(printInterval);
										pdfReady = 2;
										console.log('pdfReady = 2');
									}
								}, 500);
							}
						}
					});
				});
			}

			var now = new Date();
			if(typeof(params.rptDateTime) === "undefined"){ // create date unless we're printing pdf on server (where it's provided in url)
				rptDateTimeStr = (now.getMonth() + 1) + "/" + now.getDate() + "/" + now.getFullYear() + "<br>" + now.toLocaleTimeString();
			} else {
				rptDateTimeStr = LZString.decompressFromEncodedURIComponent(params.rptDateTime);
			}
			// These if statements are for Bay only, but other viewers can tap this too (E-U.P. comes to mind). It is for showing two seals.
			if(currentMap == "bay"){
				if($('#ParcelNumber').html().slice(0,3) == "160"){
					$('#generalDetailsWrapper').prepend(
						'<div id="rptHeaderDiv"><div id="rptSealDiv"><img id="rptSealBayCity" src="./img/BayCityLogo.png"></div>' +
						'<div id="rptClientNameHeader"><span class="h3">' + $('.navbar-brand').html() + '</span><br><span id="rptSubHeader">Parcel Report: ' + $('#detailsParcelNo').html() + '</span></div><div id="rptDate">' + rptDateTimeStr + '</div>' +
						'</div>'
					);
				} else {
					$('#generalDetailsWrapper').prepend(
						'<div id="rptHeaderDiv"><div id="rptSealDiv"><img id="rptSeal" src="./img/BayCountyLogo.jpg"></div>' +
						'<div id="rptClientNameHeader"><span class="h3">' + $('.navbar-brand').html() + '</span><br><span id="rptSubHeader">Parcel Report: ' + $('#detailsParcelNo').html() + '</span></div><div id="rptDate">' + rptDateTimeStr + '</div>' +
						'</div>'
					);
				}
			} else {
				$('#generalDetailsWrapper').prepend(
					'<div id="rptHeaderDiv"><div id="rptSealDiv"><img id="rptSeal" src="' + $('.mini-logo').attr('src') + '"></div>' +
					'<div id="rptClientNameHeader"><span class="h3">' + $('.navbar-brand').html() + '</span><br><span id="rptSubHeader">Parcel Report: ' + $('#detailsParcelNo').html() + '</span></div><div id="rptDate">' + rptDateTimeStr + '</div>' +
					'</div>'
				);
			}
			infoWindowHide();
			//$('#reportControls').fadeIn(animationSpeed);			
			$('#neatline').animate({opacity: 1}, animationSpeed);
			createReportHanders();
			//createReportPdfLink();
			activateControl('#printControls');
			showControlPane();
			
			// pull discalimer text file for current map county 
			$.ajax({
				url: "disclaimers/" + currentMap + ".txt",
				async: true,
				success: function(data){
					if(params.pdf === undefined){
						$('#printedPage').append('<div id="rptDisclaimer">' + data + '</div>');
					} else {
						$('#printedPage').append('<div id="rptDisclaimer" class="pdf">' + data + '</div>');
						$('#rptHeaderDiv').addClass('pdf');
					}
					//$('body').css({height: $('#printedPage').outerHeight() + 96});
				}
			});
                        
			
								$("#lrpImage").attr("src", "https://app.fetchgis.com/linkedDocs/bay/fetchPhoto.php?pin=" + $("#detailsParcelNo").html());
	
								if($("#photoCheck").hasClass("fa-check-square-o")){
										$("#propAddressContainer").removeClass("col-sm-12").addClass("col-sm-7");
										$("#lrpImageContainer").removeClass("hidden");
								}
									
			fixReportRowAlignment();
		});
	}
	
	$('#report2pdf').on("click", function(){
		/*reportPIN = LZString.compressToEncodedURIComponent($('#detailsParcelNo').html());
		reportLayer = LZString.compressToEncodedURIComponent($('#detailsParcelLayer').html());
		updateURLParams();
		var urlString = window.location.href;
		var URL4reportPDF = urlString + "&pdf=1"; // this makes app load in pdf print mode
				URL4reportPDF += "&rptDateTime=" + LZString.compressToEncodedURIComponent(rptDateTimeStr);
		URL4reportPDF += "&data64=" + data64;
        */
        //new way
        var printContent=encodeURIComponent($("#contentContainer").html());
        var randomString=Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15) + Date.now();
        //


		$.ajax({
			type: "POST",
			url: 'https://link.fetchgis.com/add.php',
			//data: {"url": URL4reportPDF},
            data: {"url": "https://"+window.location.host+window.location.pathname.replace("index.php","")+"print.php?currentMap="+currentMap+"&rand="+randomString, "printContent": printContent},
			crossDomain: true,
			success: function(data){
				if(typeof(data.code) !== "undefined"){
					//showModal('PDF Generator Ready:', '<a id="shareLinkBox" href="http://link.fetchgis.com/' + data.code + '" target="_blank">Click here to open another Tab and Generate your pdf.</a>');
					var tinyURL ="http://link.fetchgis.com/" + data.code;
					var encodedTinyURL = encodeURIComponent(tinyURL);
					var fullLinkString = '<a id="shareLinkBox" href="'+window.location.origin+':8080/?url=' + encodedTinyURL + '&orientation=portrait&format=Letter&zoom=1&margin=10mm" target="_blank">Click here to open a new tab with your pdf.</a>';
					//console.log(fullLinkString);
					showConfirm('PDF link is ready:', fullLinkString);
					$("#confirmAgree").hide();
					$('#confirmCancel').text('close').addClass('btn-primary').removeClass('btn-error');
					$('#confirmHeading').css('color','#5286AE').css('border-color','#5286AE')
				} else {
					console.log(data.error);
				}
			},
			error: function(){
				showAlert('Notice', 'PDF generation was not successful. Please try again.');
			},
			complete: function(){
				$('#pdfPrint').removeClass('disabledButton');
			}
		});

		//reportPIN = '0';
		//updateURLParams();
	});
	
	destroyReport = function destroyReport(){
		$('#printMargins').remove();
		$('#mapCheck').removeClass('fa-square-o').addClass('fa-check-square-o');
		toggleReportOptions();
		
		var pinToRemove = $('#detailsParcelNo').html();
		highlightLayer.clear();
		
                
		
							   $("#propAddressContainer").removeClass("col-sm-7").addClass("col-sm-12");
							   $("#lrpImage").attr("src", "");
							   $("#lrpImageContainer").addClass("hidden");
					   	   
		destroyReportHanders();
		$('#reportControls').fadeOut();
		$('#generalDetailsWrapper').stop().animate({opacity: 0},400, function(){
			$('#rptHeaderDiv').remove();
			$('#generalDetails').removeClass('visuallyHidden');
			$('#generalDetailsScrollPane').perfectScrollbar('destroy'); // After what this poor html element has gone through, the scroll bar needs to be born again
			$('#closeReport').addClass('hidden');
			$('#navToolbar, #overviewMapContainer, #newSearch, #insightsQuickAccessToolbar, .lrpCustomLink').removeClass('hidden');
			$('#contentContainer, #printedPage').removeClass('portraitVariable');
			//$('#salesInfo, #taxInfo').accordion('destroy');
			$('#printedPage, #neatline, #generalDetailsWrapper, #salesInfo, #delTaxInfo, .saleDateSec, .taxInfoH3, .taxTableSuperHeader').removeClass('reportView');
			$('#generalDetailsScrollPane').append($('#generalDetailsWrapper'));
			$('#printedPage').prepend($('#neatline'));
			map.resize(true);
			map.reposition();
			$('body').css({height: "100%"});
								
			map.centerAndZoom(prevCenter, prevZoom).then(function(){
				$('.dijitTooltipDialogPopup').css('opacity', 1);
				if(hasParcelLayer == true){
					//parcelLayer.enableMouseEvents();
					for(layer in parcelLayers){
						try {
							window[layer].enableMouseEvents();
						} catch (e) {

						}
					}
				}
				infoWindowShow();
			});
			$('#generalDetailsWrapper, #dataPaneExpando, #dataPaneHandle').css({opacity: 1}).removeClass('noPointerEvents');
			$('#neatline').animate({opacity: 1}, 400, function(){
				$( "#salesInfo" ).accordion({
					heightStyle: "content",
					active: false,
					collapsible: true
				});
				$('#lrpResBuildingWrapper, #lrpAgBuildingWrapper, #lrpComBuildingWrapper').accordion({
					header: ".buildingInfoHeader",
					heightStyle: "content",
					active: false,
					collapsible: true
				});
				$( "#delTaxInfo" ).accordion({
					heightStyle: "content",
					active: false,
					collapsible: true
				});
				$( "#taxInfo" ).accordion({
					heightStyle: "content",
					collapsible: true,
					active: false,
					header: ".taxInfoH3",
					beforeActivate: function( event, ui ) {
						accordionFocus = setInterval(function(){
							$(location).attr('href', '#' + $('.taxInfoH3.ui-accordion-header-active').attr('id'));
						},120);
					},
					activate: function( event, ui ) {
						clearInterval(accordionFocus);
						$(location).attr('href', '#' + $('.taxInfoH3.ui-accordion-header-active').attr('id'));
						setTimeout(function(){$(location).attr('href', '#');},150);
					}
				});
			});
			$('#generalDetailsScrollPane').perfectScrollbar();
			updateURLgeometry();
                        $('#generalDetailsWrapper').stop().animate({opacity:1});
                        $('#rptDisclaimer').remove();
		});
		$('#printHint').removeClass('hidden');
		$('#printControlsAccordionWrapper, #printButtonsWrapper').fadeIn();
	};
	
	if(config_taxh == "yes"){
		$('#taxHistCheck, #taxHistCheckLabel').css({'pointer-events': 'all', 'opacity': '1'});
		$('#taxHistCheck').removeClass('fa-square-o').addClass('fa-check-square-o');
	}
	
	function createReportHanders(){
		$('.repChecks').on("click", function() {
			if($(this).hasClass('fa-check-square-o')){
				$(this).removeClass('fa-check-square-o');
				$(this).addClass('fa-square-o');
			} else if ($(this).hasClass('fa-square-o')){
				$(this).removeClass('fa-square-o');
				$(this).addClass('fa-check-square-o');
			}
			toggleReportOptions();
		});
	}

	// this function was separated out of createReportHandlers() for when the page loads in html2pdf
	function toggleReportOptions(){
		if($('#taxHistCheck').hasClass('fa-square-o')){
			$('#taxHistoryContainer').addClass('hidden');
			reportShowTax = '0';
		} else {
			$('#taxHistoryContainer').removeClass('hidden');
			reportShowTax = '1';
		}
		
		if($('#mapCheck').hasClass('fa-square-o')){
			$('#neatline').addClass('hidden');
			reportShowMap = '0';
		} else {
			$('#neatline').removeClass('hidden');
			reportShowMap = '1';
		}
		
		if($('#photoCheck').hasClass('fa-square-o')){
			$('#lrpImageContainer').addClass('hidden');
			$('#propAddressContainer').removeClass('col-sm-7');
			reportShowPic = '0';
		} else {
			$('#lrpImageContainer').removeClass('hidden');
			$('#propAddressContainer').addClass('col-sm-7');
			reportShowPic = '1';
		}

		// this was added for insights O and M report. Might be moved on it's own at some point in the near future.
		if($('#omSummaryCheckWrapper').length === 1){
			if($('#omSummaryCheck').hasClass('fa-square-o')){
				$('#insightsOandMSummaryContainer').addClass('hidden');
				//reportShowSummary = '0'; // this will be uncommented when OandM reports do PDF server.
			} else {
				$('#insightsOandMSummaryContainer').removeClass('hidden');
				//reportShowSummary = '1';
			}
		}
		if($('#qiSummaryCheckWrapper').length === 1){
			if($('#qiSummaryCheck').hasClass('fa-square-o')){
				$('.insights-summary-container').addClass('hidden').prev().addClass('hidden');
				//reportShowSummary = '0'; // this will be uncommented when OandM reports do PDF server.
			} else {
				$('.insights-summary-container').removeClass('hidden').prev().removeClass('hidden');
				//reportShowSummary = '1';
			}
		}
		if($('#qiFieldStatisticWrapper').length === 1){
			if($('#qiFieldStatisticCheck').hasClass('fa-square-o')){
				$('#insights-result-stats-container').addClass('hidden')//.prev().addClass('hidden');
			} else {
				$('#insights-result-stats-container').removeClass('hidden')//.prev().removeClass('hidden');
			}
		}
		
		if(params.pin != "0"){
			updateURLParams();
		}
	}
	
	function destroyReportHanders(){$('.repChecks').off('click');}

	// this function works on map load, looking up a PIN, opening a report, and hiding things if needed
	generateReportPdF = function generateReportPdF(){
		getParcelData(LZString.decompressFromEncodedURIComponent(params.rptPIN), LZString.decompressFromEncodedURIComponent(params.rptLayer));
		if(params.rptTax == '0'){
			//$('#taxHistoryContainer').addClass('hidden');
			$('#taxHistCheck').removeClass('fa-check-square-o').addClass('fa-square-o');
		}
		
		if(params.rptMap =='0'){
			//$('#neatline').addClass('hidden');
			$('#mapCheck').removeClass('fa-check-square-o').addClass('fa-square-o');
		}
		
		if(params.rptPic == '0'){
			//$('#lrpImageContainer').addClass('hidden');
			//$('#propAddressContainer').removeClass('col-sm-6');
			$('#photoCheck').removeClass('fa-check-square-o').addClass('fa-square-o');
		}
		
		setTimeout(function(){
			fixReportRowAlignment();
			toggleReportOptions();
            //$('body').css('zoom',1);//zoom .98 helps because pdf seems to be zoomed in.
		},100);//1000

		//$('.detailsTable, .subTable, .taxItemsTable').css({'font-size': '12px!important';});
	};
	
	window.fixReportRowAlignment = function fixReportRowAlignment(){
		// Line up general details rows (make heights of left and right sides match)
		$('#generalInfo_Left tr th').each(function(index){
			$('#generalInfo_Right tr th div').eq(index).css('height', $('#generalInfo_Left tr th').eq(index).height());
		});
		
		
		//$('.taxDetails_tbodyLeft').each(function(index1){
		
			$('.taxDetails_tbodyLeft:eq(' + 0 + ') tr th').each(function(index2){
				$('.taxDetails_tbodyRight:eq(' + 0 + ') tr th div').eq(index2).css('height', $('.taxDetails_tbodyLeft:eq(' + 0 + ') tr th').eq(index2).height());
			});
		
		//});
	}

	// Grab the scripts for printing maps
	// generate print legend choices
var printLegendBuilderHTML = '', pageLoadParams4PrintLegend;
window.createPrintLegend = function(node, isChild){ // must be called for each level of the layerDefs object
	for(var i = 0; i < node.length; i++){
		if(node[i].childLayers){
			createPrintLegend(node[i].childLayers, true);
		} else if(typeof(node[i].hidePrtLegend) === "undefined"){
			var nodeId = node[i].id;
			var nodeName = node[i].name;
			if(typeof node[i].filterField != "undefined" && typeof node[i].filterValue != "undefined"){
				nodeId = getFilterLayerCheckboxId(node[i]);
				nodeName = nodeName += " - " + node[i].filterValue.join(", ");
			}
			// find if layer is checked to determine if it has to be hidden or not (this function is called after the legend is updated based on the URL params on page load)
			var hiddenStr = "hidden";
			if($('#' + nodeId + 'Checkbox').hasClass('fa-check-square-o') || $('#' + nodeId + 'Checkbox').hasClass('fa-dot-circle-o')){
				hiddenStr = "";
				// also hide the place holder text
				$('#printLegendBuilderPlaceHolder').addClass('hidden');
			}
			if(node[i].hidePrtLegend !== true){
				printLegendBuilderHTML += 	'<div class="printLegendRow noSelect ' + hiddenStr + '">' + 
												'<div id="' + nodeId + 'PrintLegendCheckbox" uniqueid="'+node[i].uniqueId+'" class="fa fa-square-o fa-lg printLegendCheckbox"> </div><div class="printLegendCheckboxLabel" id="'+nodeId+'_printLegendCheckboxLabel">' + nodeName + '</div><br>' +
											'</div>';
			}
		}
	}
	if(isChild === false){
		createRestOfPrintLegend();
	}
};

function getDynaTocSymbols(layerID){
	var html = '';
	try{
		$("#" + layerID + "_toc-dynamic > .legendThumbnail").each(function(i){
			var svg = $(this).find('svg');// shoudl have either svg or img element
			var img = $(this).find('img');
			var symbol = "", desc = "";
			if(svg[0]){
				symbol = '<svg style="width:15px;height:9px;margin-right:4px">' + svg[0].innerHTML + '</svg>';
				desc = $(this).find('span').html() || "";
			} else if (img[0]){ // this is for temporary color ramp with jays
				symbol = img[0].outerHTML;
				var nextThumbnail = $("#" + layerID + "_toc-dynamic > .legendThumbnail")[i+1];
				if(nextThumbnail){
					desc = nextThumbnail.innerHTML || "";
				}
			}
			html += '<div class="printLegendSymbolRowDynamic">' + symbol + ' ' + desc + '</div>';
		});
	} catch(err){ // nothing in layerdef images
		console.log('error in building dynamic print legend, toc may still be loading: ' + err);
	}
	html += '</div>';
	return html;
}

function createRestOfPrintLegend(){
	$('#printLegendBuilderDiv').html(printLegendBuilderHTML);

	// set handlers on the checkboxes created above
	$('.printLegendCheckbox').on("click", function(){
		
		// function to search through the layer defs tree and return the one matching the id sent in as second parameter
		function findLayerDefObject(node, idStr){
			var foundResult;
			for(var i = 0; i < node.length; i++){
				if(node[i].childLayers){
					foundResult = findLayerDefObject(node[i].childLayers, idStr);
					if(foundResult !== undefined){
						//console.log(foundResult);
						return foundResult;
					}
				} else if(node[i].uniqueId === idStr){
					return node[i];
				}
			}
		}
		
		if($(this).hasClass('fa-check-square-o')){
			$(this).removeClass('fa-check-square-o').addClass('fa-square-o');
			var htmlId = $(this).prop("id");
			htmlId = htmlId.replace("PrintLegendCheckbox", "");
			$('#' + htmlId + 'PrintLegendRow').remove();
			
			// check to see if print legend needs to be hidden
			var found1checked = false;
			$('.printLegendCheckbox').each(function(index){
				if($('.printLegendCheckbox').eq(index).hasClass('fa-check-square-o')){
					found1checked = true;
				}
			});
			if(found1checked === false){
				$('#printLegend').addClass('hidden');
			}
		} else {

			var uniqueId = $(this).attr("uniqueid");
			// show the print legend
			$('#printLegend').removeClass('hidden');
			
			// change the checkbox
			$(this).removeClass('fa-square-o').addClass('fa-check-square-o');
			
			// get the id of the layer
			var htmlId = $(this).prop("id");
			htmlId = htmlId.replace("PrintLegendCheckbox", "");
			
			// get the correct layer def
			var layerDefNode, result;
			result = findLayerDefObject(layerDefs, uniqueId);
			if(result !== undefined){
				layerDefNode = result; 
				var legendLabelDisplay = $(`.printLegendCheckbox[uniqueid="${result.uniqueId}"]`).parent().find('.printLegendCheckboxLabel').html().split(" - ")[0]; // dont show filter layer values in the legend itself
				// build the row
				var legendRowHTML = '';
				legendRowHTML = '<div id="' + htmlId + 'PrintLegendRow">' +
									'<div class="printLegendLabel" id="'+htmlId+'_printLegendLabel">' + legendLabelDisplay + '</div>' + // just get name from checkbox element, this is for dynamic name updates
									'<div class="printLegendSymbology" id="'+htmlId+'_printLegendSymbology">';

				if(layerDefNode.images == "dynamic"){
					/*if(currentMap == "jays"){
						legendRowHTML += getDynaTocSymbols(layerDefNode.id);
					} else {
						console.log(DynamicTOC.html_store);*/
						if(DynamicTOC.html_store[htmlId]){
							legendRowHTML += /*'<div id="'+htmlId+'_dynamic-printLegend">'+*/ DynamicTOC.html_store[htmlId].print_html /*+ '</div>'*/;
						} else {
							//try to grab images and text from toc
							let printHtml = ''
							let tocEntry = $('#' + htmlId + 'Checkbox').parent().find('.legendThumbnail');
							for(t of tocEntry){
								let img = $(t).find('svg');
								if(img.length == 0){ //no svg, look for image
									img = $(t).find('img');
								}
								let label = $(t).find('.dyna-toc-label'); //recreate
								if(img.length == 0 && label.length == 0){
									continue;
								}
								printHtml += '<div class="printLegendSymbolRow">\
                                                <div class="prtLgndIconWrapper">\
                                                    ' +(img.length > 0 ? img[0].outerHTML : '')+ '\
                                                </div>\
                                                <div class="prtLgndLabel dyna-toc-label" contenteditable="true">' + (label.length > 0 ? label[0].innerText : '') + '</div>\
                                            </div>';
							}
							if(printHtml){
								DynamicTOC.html_store[htmlId] = {};
								DynamicTOC.html_store[htmlId].print_html = printHtml;
							} else { //if nothing found, return no data
								printHtml = "No Data";
							}
							legendRowHTML += printHtml;
						}		
					//}

				} else {
					for(var i = 0; i < layerDefNode.images.length; i++){
						var colorRampStr = " "; // if graphic is a color ramp, add the colorRamp css class to allow extra icon width
						if(typeof(layerDefNode.images[i].colorRamp) !== "undefined"){
							if(layerDefNode.images[i].colorRamp === "true"){
								colorRampStr = ' prtLegendColorRamp';
							}
						}
						legendRowHTML += '<div class="printLegendSymbolRow"><div class="prtLgndIconWrapper' + colorRampStr + '"><img src="' + layerDefNode.images[i].imageURL + '"></div><div class="prtLgndLabel" contenteditable="true">' + layerDefNode.images[i].imageDesc + '</div></div>';
					}
				}
									
				legendRowHTML +=	'</div></div>';
				
				$('#printLegend').append(legendRowHTML);
			}
		}
		
		// update the list of checked print legends in the url
		printLegendLayers = "";
		$('.printLegendCheckbox').each(function(index,ea){
			if($(ea).hasClass('fa-check-square-o') && !$(ea).parent().hasClass('hidden')){
				var tempStr = $('.printLegendCheckbox').eq(index).prop('id');
				tempStr = tempStr.replace('PrintLegendCheckbox', '_');
				printLegendLayers += tempStr;
			}
		});
		if(printLegendLayers !== ""){
			printLegendLayers = printLegendLayers.slice(0, printLegendLayers.length - 1)
		}
		updateURLParams();
		setPrintLegendContainment(true); //as new layers added to legend reset legend to bottom right
	});

	if(params.printLegendLayers !== undefined){
		if(typeof(pageLoadParams4PrintLegend) === "undefined"){
			if(params.printLegendLayers !== "undefined" && params.printLegendLayers !== "null" && params.printLegendLayers !== null && params.printLegendLayers !== true){
				pageLoadParams4PrintLegend = params.printLegendLayers.split('_');
				setTimeout(function(){ // just the time it takes to process a 1 ms delay is enough time to process the click event handlers, but without the delay it won't work
					// fire clicks on the checkboxes
					for(var i = 0 ; i < pageLoadParams4PrintLegend.length; i++){
						$('#' + pageLoadParams4PrintLegend[i] + 'PrintLegendCheckbox').trigger('click');
					}	
				}, 1);
			}	
		}
	}
}
function setPrintLegendContainment(resetPosition){
	var option = '';
	if($('#printLegend')[0].className.indexOf('portrait')>-1){ //if portrait mode
		option='portrait'
	}
	var contain = getContainment(option);
	if(resetPosition){
		if(option == 'portrait'){
			$('#printLegend').css({bottom:'212px',right:'-3px',left:'unset',top:'unset'});
		} else{
			$('#printLegend').css({bottom:'-3px',right:'207px',left:'unset',top:'unset'});
		}
		//reset position of print extras
		$('#printExtras td').attr('style','');
		$('#printExtras td').css('position','relative');
	}
	$('#printExtras td').draggable();

	$('#printLegend').draggable({containment: contain, start: function(event, ui){
		$(this).css({bottom:'unset', right:'unset'}); //when legend moves remove these settings, ensures legend shrinks to correct height
	}, stop: function(){//checking if legend has been dragged off screen
		var neat = $("#neatline")[0].getBoundingClientRect();
		var leg = $("#printLegend")[0].getBoundingClientRect();
		// var pew = $("#printExtras")[0].getBoundingClientRect();
		if(option == 'portrait'){
			if(leg.top < neat.top){ //
				$(this).css('top','-3px')
			}
			// if(leg.bottom > pew.top){
			// 	$(this).css({'top':'unset','bottom':(pew.height-3)+'px'})
			// }
			if(leg.left < neat.left){
				$(this).css('left','-3px')
			}
			if(leg.right > neat.right){
				$(this).css({'left':'unset','right':'-3px'});
			}
		} else { //landscape
			// if(leg.right > pew.left){
			// 	$(this).css({'right':(pew.width-3)+'px','left':'unset'});
			// }
			if(leg.left < neat.left){
				$(this).css('left','-3px');
			}
			if(neat.top > leg.top){
				$(this).css('top','-3px')
			}
			if(neat.bottom < leg.bottom){
				$(this).css({'top':'unset','bottom':'-3px'})
			}
		}
	}});
	function getContainment(option){ //building containment array
		var neat = $("#neatline")[0].getBoundingClientRect();
		var leg = $("#printLegend")[0].getBoundingClientRect();
		var x1 = neat.left
		var x2 = neat.left+neat.width-leg.width//-210 //take into account 210px of title block on right
		var y1 = neat.top
		var y2 = neat.top+neat.height-leg.height
		if(option == 'portrait'){
			x2 = neat.left+neat.width-leg.width
			y2 = neat.top+neat.height-leg.height//-215 //take into account 215px of title block on bottom
		}
		return [x1,y1,x2,y2]
	}
}
// build PDF link
var printURLparams = "";
// Default print setting is landscape, letter size.
function changePageSetup(){
	printURLparams = "";
	
	// fill in date element on map print 
	$("#sysDate").html(getSysDate() + ' ' + getSysTime());
	
	if ($('#paperOrientationPortrait').hasClass('fa-dot-circle-o')){
		printURLparams += '&orientation=portrait';
	} else {
		printURLparams += '&orientation=landscape';
	}

	if ($('#paperSizeLegal').hasClass('fa-dot-circle-o')){
		printURLparams += '&format=Legal';
	} else if($('#paperSizeLetter').hasClass('fa-dot-circle-o')) {
		printURLparams += '&format=Letter';
	} else if($('#paperSizeLedger').hasClass('fa-dot-circle-o')){
		printURLparams += '&format=Tabloid';
	} else if($('#paperSizeA3').hasClass('fa-dot-circle-o')){
		printURLparams += '&format=A3';
	} else if($('#paperSizeA4').hasClass('fa-dot-circle-o')){
		printURLparams += '&format=A4';
	}
	printURLparams += '&zoom=1';
	printURLparams += '&margin=1cm';

	$('#pdfPrint').attr('link', printURLparams);
	
	// Just in case this wasn't executed when the map loaded
	$('#map').after($('.esriScalebar'));
	$('#map').after($('#scalebarBackgroundDiv'));


	// An inch is 96 CSS pixels in desktop browsers. I've found that using pixels is more effective, since using Inches is meaningless in FF when in print css(had to make custom for FF);
	let selectedSize = '';
	let selectedOrientation = ''
	if($('#paperOrientationLandscape').hasClass('fa-dot-circle-o')){
		selectedOrientation = 'landscape';
	} else {
		selectedOrientation = 'portrait';
	}
	selectedSize = 	$('.printSizeWrapper .fa-dot-circle-o').attr('id');
	selectedSize = selectedSize.slice(9);
	$('#contentContainer, #printedPage, #neatline, #map, #printExtras, #layerSwipe').stop().animate({opacity: 0.01}, 400, function(){
		$('#contentContainer, #printedPage, #neatline, #map, #printExtras, #layerSwipe').removeClass('landscapeLetter portraitLetter landscapeLegal portraitLegal landscapeLedger portraitLedger landscapeA3 portraitA3 landscapeA4 portraitA4');
		$('#contentContainer, #printedPage, #neatline, #map, #printExtras, #layerSwipe').addClass(selectedOrientation+selectedSize);
		$('#printExtras').css({opacity: '1', display: 'block'});
		$('#contentContainer, #printedPage, #neatline, #map, #printExtras, #layerSwipe').stop().animate({opacity: 1});
		if(selectedOrientation == 'landscape'){
			$('#printTitle , #subTitle, #printSeal, .esriScalebar, .mapInfo, #printFetchLogo, #printDisclaimer, #printExtrasEditableStuff').removeClass('portrait');
			$('#printExtrasPortraitTableUpper, #printExtrasPortraitTableLower').addClass('hidden');
			$('#printExtrasLandscapeTable').removeClass('hidden');
			$('#printExtrasLandscapeTable .extClientLogo').append($('#printSeal'));
			$('#printExtrasLandscapeTable .extClientLogo').append($('#printSeal2'));
			$('#printExtrasLandscapeTable .extTitle').append($('#printExtrasEditableStuff'));
			$('#printExtrasLandscapeTable .extPubData').append($('#mapInfoWrapper'));
			$('#printExtrasLandscapeTable .extScalebar').append($('.esriScalebar'));
			$('#printExtrasLandscapeTable .extFetchLogo').append($('#printFetchLogo'));
			$('#printExtrasLandscapeTable .extDisclaimer').append($('#printDisclaimer'));
			$('#printLegend').removeClass('portrait');
		} else {
			$('#printExtrasLandscapeTable').addClass('hidden');
			$('#printExtrasPortraitTableUpper, #printExtrasPortraitTableLower').removeClass('hidden');
			$('#printExtrasPortraitTableUpper .extClientLogo').append($('#printSeal'));
			$('#printExtrasPortraitTableUpper .extClientLogo').append($('#printSeal2'));
			$('#printExtrasPortraitTableUpper .extTitle').append($('#printExtrasEditableStuff'));
			$('#printExtrasPortraitTableLower .extPubData').append($('#mapInfoWrapper'));
			$('#printExtrasPortraitTableLower .extScalebar').append($('.esriScalebar'));
			$('#printExtrasPortraitTableUpper .extFetchLogo').append($('#printFetchLogo'));
			$('#printExtrasPortraitTableLower .extDisclaimer').append($('#printDisclaimer'));
			$('#printTitle , #subTitle, #printSeal, .esriScalebar, .mapInfo, #printFetchLogo, #printDisclaimer, #printExtrasEditableStuff').addClass('portrait');
			$('#printLegend').addClass('portrait');
		}
	});
	setTimeout(function(){
		map.resize(true);
		map.centerAt([centerLngLat[0], centerLngLat[1]]);

		// give the map some extra height just to make up for user set margins in print
		$('#map_root').css({'height': '' + (map.height + 100) + 'px'});
		setPrintLegendContainment(true);
	}, 500);
}

$('.printModeRadioSize, .printModeRadioOrientation').on("click", function(){

	if(currentMap == 'soo'){
		$('#printSeal').addClass('soo');
	}
	if($(this).hasClass('printModeRadioSize')){ // handle size change
		$('.printModeRadioSize').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
		$(this).addClass('fa-dot-circle-o');
	}
	if($(this).hasClass('printModeRadioOrientation')){ // handle orientation change
		$('.printModeRadioOrientation').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
		$(this).addClass('fa-dot-circle-o');
	}
	
	//alert(this.id);
	if($('#paperSizeLetter').hasClass('fa-dot-circle-o')){
		pageSize = 'letter';
	} else if($('#paperSizeLegal').hasClass('fa-dot-circle-o')){
		pageSize = 'legal';
	} else if($('#paperSizeLedger').hasClass('fa-dot-circle-o')){
		pageSize = 'ledger';
	} else if($('#paperSizeA4').hasClass('fa-dot-circle-o')){
		pageSize = 'A4';
	} else if($('#paperSizeA3').hasClass('fa-dot-circle-o')){
		pageSize = 'A3';
	}
	
	if ($('#paperOrientationLandscape').hasClass('fa-dot-circle-o')){
		pageOrientation = 'landscape';
	} else if ($('#paperOrientationPortrait').hasClass('fa-dot-circle-o')){
		pageOrientation = 'portrait';
	}
	//pageTitle = $('#printTitleInput').val();
	//subTitle = $('#subTitleInput').val();
	updateURLParams();
	changePageSetup();
});

$('#printTitleInput, #subTitleInput').on('keydown', function(e){
	if(e.which === 51 && e.shiftKey === true){ // this filters out the '#' character.
		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();
	}
});

$('#printTitleInput').on('keyup', function(){
	if($('#printTitleInput').val() !== ""){

		var input = $('#printTitleInput').val();
		input = input.replace("#", "");// this filters out the '#' character in case the above filter doesn't catch it (think tablet keyboard)
		$('#printTitleInput').val(input);

		$('#printTitle').html($('#printTitleInput').val());
	} else {
		$('#printTitle').html('Untitled Map');
	}

	//pageTitle = $('#printTitleInput').val();
	updateURLParams();
});

$('#subTitleInput').on('keyup', function(e){
	if($('#subTitleInput').val() !== ""){
		
		var input = $('#subTitleInput').val();
		input = input.replace("#", "");// this filters out the '#' character in case the above filter doesn't catch it (think tablet keyboard)
		$('#subTitleInput').val(input);
		
		$('#subTitle').html($('#subTitleInput').val());
	} else {
		$('#subTitle').html('');
	}
	
	//subTitle = $('#subTitleInput').val();
	updateURLParams();
});


//North Arrow Selection
$('.northArrow').on("click", function(){
	$('#nImage').attr('src', $(this).attr('src'));
	$('.northArrow').not(this).css({opacity: '.5'});
	$(this).css({opacity: '1'});
	var thisId = $(this).prop('id');
	northArrow = thisId.replace("northArrow", "");
	updateURLParams();
});


// cancel print functionality
$('#cancelPrint').on("click", function(){
	activateControl('#layerControls');
});

window.addMapPrint = function(transactionId){
	function addIt(){
		hideDataPane();
		$('#navToolbar, #overviewMapContainer, #newSearch, #insightsQuickAccessToolbar').hide();
		$('body').on('click.EditLegend', '.prtLgndLabel', function(e){ //legend label editor listener
			$(this).trigger("focus");
		})
		$('body').on('mouseenter.RemoveLabelIn', '.printLegendSymbolRow', function(e){ //hover over legend row, add x
			$(this).append("<div class='removeLegendLabel'>X</div>")
		})
		$('body').on('mouseleave.RemoveLabelOut', '.printLegendSymbolRow',function(e){ //leave legend row
			$('.removeLegendLabel').remove()
		})
		$('body').on('click.RemoveLabelClick', '.removeLegendLabel', function(e){ //click on x
			var sibs = $(this).parent().siblings().length; //any other rows?
			if(sibs == 0){ //no rows, remove and uncheck in legend
				var layId = $(this).parent().parent()[0].id.split('_')[0]; //grab beginning of id up to underline
				$(this).parent().parent().parent().remove();
				$('#'+layId+'PrintLegendCheckbox').trigger('click');
			} else { //else just remove row
				$(this).parent().remove();
			}
		})
		$('#map .esriMapContainer').addClass('allowOverflow');
		$('#printHint, #goToFetch, .actionsPane .actionsList').addClass('hidden');
		$('#newSearch').addClass('hidden');
		// get center of map
		updateURLParams();
		
		// set neatline to appropriate css atrributes for printing, remove tools from view
		$('#navToolbar, #overviewMapContainer, #newSearch, #incidentsContainer, #insightsQuickAccessToolbar').css({display: 'none'});
		$('#scrollbarHider').removeClass('hidden');

		changePageSetup();
		$('.esriControlsBR').hide();
		$('.neatline').removeClass('neatlineVisible');
		$(".esriScalebarLabel").addClass('print');
		$('#scalebarBackgroundDiv').addClass('hidden').hide();

		$('.esriScalebarLine').addClass('print');
		$('.esriPopup').addClass('show4print');
		
		highlightLayer.clear();
		//parcelLayer.disableMouseEvents();

		for(layer in parcelLayers){
			//window[layer].setInfoTemplate(null);
			try {
				window[layer].disableMouseEvents();
			} catch (e) {

			}
			
		}
		
		
		
        $("#printControls").removeClass("hidden");
		$('#printLegend').addClass('printMode');
		showControlPane();

		// remove this when pdf server is replaced. It removes the arrow on the side of the popup since pdf server doesn't do css rotate.
		if(params.pdf === "1"){
			$('.esriPopup .outerPointer').remove();
		}

		if(typeof(params.fineZoom) !== "undefined"){
			$('#fineZoomSlider').slider({'value': params.fineZoom});
		}


		// at this point, it's hard to tell if we're printing a pdf with a buffer without decompressing the base64 stuff, but we have to know so here we go...
		var isPDFWithBuffer = false;
		try{
			if(typeof(params.data64code) !== "undefined" && params.pdf === '1'){
				if(data64 !== "" && data64 !== undefined && data64 !== 'undefined' && data64 !== "NoXSA" && data64 !== "null"){
					var restoredObjects64 = LZString.decompressFromEncodedURIComponent(data64);
					var restoredObjectsCheck = JSON.parse(restoredObjects64);

					if(restoredObjectsCheck.currentMap === currentMap){
						if(typeof(restoredObjectsCheck.buff64) !== "undefined"){
							isPDFWithBuffer = true;
						}
					}
				}
			}
		} catch(err){
			console.log(err.message);
		}

		// last condition looks to see if a buffer is being printed. If so, no need to restore a popup. "pdfReady = 2" will be fired by the completion of the buffer.
		if(params.pdf === "1" && loadDowReport === false && loadSalesReport === false && loadReport === false && loadSitePlan === false && isPDFWithBuffer === false){

			// make sure data is done loading
			if(map.updating === false){
				restorePopup();
			} else {
				var printInterval = setInterval(function(){
					if(map.updating === false){
						clearTimeout(printInterval);
						restorePopup();
					}
				}, 500);
			}
		}

		function restorePopup() {
			//$('#printDisclaimer').html('step 1');
			try{
				if(typeof(restoredObjects) !== "undefined"){
					if(typeof(restoredObjects.popupLoc) !== "undefined"){

						var fQuery = new query();
						var fQueryTask = new QueryTask(window[restoredObjects.popupLayer].url);
						fQuery.objectIds = [restoredObjects.popupObjId];
						fQuery.outFields = [ "*" ];
						fQuery.returnGeometry = true;

						// Query for the features with the given object ID
						//$('#printDisclaimer').html('step 2');
						fQueryTask.execute(fQuery, function(featureSet) {
							if(featureSet.features.length === 0){
								$('#map .esriPopup .contentPane').html("No Parcel Found");
								popup.show(restoredObjects.popupLoc);
								pdfReady = 2;
								console.log('pdfReady = 2');
							} else {
								var found = false;
								//$('#printDisclaimer').html('step 3');
								popup.show(restoredObjects.popupLoc);

								var layer = window[restoredObjects.popupLayer];
								for(var i = 0; i < layer.graphics.length; i++){
									//$('#printDisclaimer').html('step 4');
									if(layer.graphics[i].attributes[layer.objectIdField] === restoredObjects.popupObjId){
										popup.setFeatures([layer.graphics[i]]);
										$('.esriPopup').addClass('show4print');
										//$('#printDisclaimer').html('step 5');
										found = true;
										break;
									}
								}
								if(found === false){ // parcel is probably filtered out... so just shove that sucka in the layer
									var newPoly = new Polygon(featureSet.features[0]);
									var newGraphic = new Graphic(newPoly, null, featureSet.features[0].attributes);
									layer.add(newGraphic);
									popup.setFeatures([layer.graphics[layer.graphics.length - 1]]);
									$('.esriPopup').addClass('show4print');
								}
								// use an interval to check to make sure popup is popupulated
								var contentInterval = setInterval(function(){
									if($('#map .esriPopup .contentPane').html() !== "&nbsp;"){
										console.log($('#map .esriPopup .contentPane').html());
										clearInterval(contentInterval);
										firePDFReadyWithTimeout();
									}
								}, 500);
							}

						});
					} else {
						if(typeof(params.salesQuery) === "undefined"){ // same as above for sales search
							pdfReady = 2;
							console.log('pdfReady = 2');
						}
					}
				} else {
					if(typeof(params.salesQuery) === "undefined"){ // same as above for sales search
							pdfReady = 2;
							console.log('pdfReady = 2');
					}
				}

			} catch(err){
				console.log(err.message);
				pdfReady = 2;
				console.log('pdfReady = 2');
			}
		}
	}
addIt();	$('#contentContainer').on('scroll.print', function(){ //update bounding box on scroll, when the printed page is larger than screen size
		setPrintLegendContainment();
	})
}

window.removeMapPrint = function(){
		
	if(typeof infoWinGraphic != "undefined") {
		map.graphics.remove(infoWinGraphic);
	}
	// get center of map
	updateURLParams();
	
	$('#map .esriMapContainer').removeClass('allowOverflow');
	
	// set neatline to appropriate css atrributes for printing, remove tools from view
	$('#navToolbar, #overviewMapContainer, #newSeach, #insightsQuickAccessToolbar, #incidentsContainer').css({display: ''});
	$('#scrollbarHider').addClass('hidden');
	$('#newSearch').removeClass('hidden').show();

	
	$('#contentContainer, #printedPage, #neatline, #map, #printExtras, #layerSwipe').stop().animate({opacity: 0.001}, 400, function(){
		$('#contentContainer, #printedPage, #neatline, #map, #printExtras, #layerSwipe').removeClass('landscapeLetter portraitLetter landscapeLegal portraitLegal landscapeLedger portraitLedger landscapeA3 portraitA3 landscapeA4 portraitA4');
		$('#scalebarBackgroundDiv img').after($('.esriScalebar'));
		$('#contentContainer, #printedPage, #neatline, #map, #layerSwipe').animate({opacity: 1});// printExtras stays hidden
	});
	setTimeout(function(){
		map.resize(true);
		map.centerAt([centerLngLat[0], centerLngLat[1]]);
		
	}, 500);
	$('#centerInfo').attr('class', 'cleanstate');
	$('#centerInfo').addClass('centerPortrait');
	//$('.esriScalebar, #scalebarBackgroundDiv').removeClass('hidden');
	$('.esriControlsBR, #scalebarBackgroundDiv').show();
	
	$('.esriScalebar').removeClass('landscapeLegal').removeClass('landscapeLetter');
	$(".esriScalebarLabel").removeClass('print');
	$('#scalebarBackgroundDiv').removeClass('hidden');
	$('.esriScalebarLine').removeClass('print');
	$('.esriScalebar').removeClass('landscapeLegal').removeClass('landscapeLetter');
	$('.esriPopup').removeClass('show4print');
	$('#navToolbar, #overviewMapContainer, #newSearch, #insightsQuickAccessToolbar').show();
	
	$('body').off('click.EditLegend'); //remove legend label editor listener
	$('body').off('mouseleave.RemoveLabelOut'); //remove remove legend label mouseout listener
	$('body').off('mouseenter.RemoveLabelIn'); //remove remove legend label mousein listener
	$('body').off('click.RemoveLabelClick'); //remove remove legend label click listener
	
	if(hasParcelLayer == true){
		//parcelLayer.enableMouseEvents();
		for(layer in parcelLayers){
			var _layer = window[layer];
			_layer.setInfoTemplate(parcelLayers[layer].infoTemplate);
			if(_layer._mouseEvents == false){
				_layer.enableMouseEvents();
			}
		}
	}
	$('#printHint, #goToFetch, .actionsPane .actionsList').removeClass('hidden');
	$('#printLegend').removeClass('printMode');

	$('#fineZoomSlider').slider({'value':'100'}); // reset zoom slider
$('#contentContainer').off('scroll.print') //turn off updating of bounding box if not in print mode
}


$('#pdfPrint').on("click", function(evt){
	$('#pdfPrint').addClass('disabledButton');
	
	var pdfURLparams = "&pdf=1"; // this makes app load in pdf print mode
	pdfURLparams += '&fineZoom=' + $("#fineZoomSlider").slider('value'); // used to track the zoom of the map
	

	// if popup is showing, get it's location and current selected feature to recreate on pdf server

	var data64Injection;
	if(popup.isShowing){

		var data64Decoded = {};
		if(data64 !== null){
			data64Decoded = JSON.parse(LZString.decompressFromEncodedURIComponent(data64));
		}
		data64Decoded.popupLoc = popup.location.toJson();
		data64Decoded.popupLayer = popup.getSelectedFeature().getLayer().id;
		data64Decoded.popupObjId = popup.getSelectedFeature().attributes[popup.getSelectedFeature().getLayer().objectIdField];

		data64Injection = LZString.compressToEncodedURIComponent(JSON.stringify(data64Decoded));

	} else {
		data64Injection = data64;
	}

		
	pdfURLparams += "&data64=" + data64Injection;

    //new way
    var printContent=encodeURIComponent($("#contentContainer").html());
    var randomString=Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15) + Date.now();
    //

	$.ajax({
		type: "POST",
		url: 'https://link.fetchgis.com/add.php',
		data: {"url": "https://"+window.location.host+window.location.pathname.replace("index.php","")+"print.php?currentMap="+currentMap+"&rand="+randomString, "printContent": printContent},
		crossDomain: true,
		success: function(data){
			if(typeof(data.code) !== "undefined"){
				//showModal('PDF Generator Ready:', '<a id="shareLinkBox" href="http://link.fetchgis.com/' + data.code + '" target="_blank">Click here to open another Tab and Generate your pdf.</a>');
				var tinyURL ="http://link.fetchgis.com/" + data.code;
				var encodedTinyURL = encodeURIComponent(tinyURL);
				var fullLinkString = '<a id="shareLinkBox" href="'+window.location.origin+':8080/?url=' + encodedTinyURL + '?' + $('#pdfPrint').attr('link') +'" target="_blank">Click here to open a new tab with your pdf.</a>';
				//console.log(fullLinkString);
				showConfirm('PDF link is ready:', fullLinkString);
				$("#confirmAgree").hide();
				$('#confirmCancel').text('close').addClass('btn-primary').removeClass('btn-error');
				$('#confirmHeading').css('color','#5286AE').css('border-color','#5286AE')
			} else {
				console.log(data.error);
			}
		},
		error: function(){
			showAlert('Notice', 'PDF generation was not successful. Please try again.');
		},
		complete: function(){
			$('#pdfPrint').removeClass('disabledButton');
		}
	});
});


$('#fineZoomSlider').slider({
	max: 120,
	min: 80,
	step: 1,
	value: 100,
	create: function(event, ui){
		if(firefox){
			$('#map').css({'transform':'scale(1)', 'transform-origin': '0 0'});
		} else {
			$('#map').css({'zoom':'1'});
		}
		$('.scalebar_bottom-left.esriScalebar').css({'transform':'scaleX(1)'});
	},
	slide: function(event, ui){
		//console.log(ui.value);
		var val = ui.value/100;
		if(firefox){
			$('#map').css({'transform':'scale(' + val + ')'});
		} else {
			$('#map').css({'zoom':'' + val + ''});
		}
		
		$('.scalebar_bottom-left.esriScalebar').css({'transform':'scaleX(' + val + ')'});
	},
	change: function(event, ui){
		//console.log(ui.value);
		var val = ui.value/100;
		if(firefox){
			$('#map').css({'transform':'scale(' + val + ')'});
		} else {
			$('#map').css({'zoom':'' + val + ''});
		}
		$('.scalebar_bottom-left.esriScalebar').css({'transform':'scaleX(' + val + ')'});
		updateURLParams();
	}
});

});// end of Dojo loading

function resizeScalebarBackground(){
	setTimeout(function(){
		var scalebar1 = $('.esriScalebar > div').eq(0).width();
		var scalebar2 = $('.esriScalebar > div').eq(1).width();
		if (scalebar1 > scalebar2){
			$('#scalebarBackgroundDiv').css('width', scalebar1 + 45);
			$('.esriScalebar').css({'width': '' + (scalebar1 + 20) + ''});
		} else {
			$('#scalebarBackgroundDiv').css('width', scalebar2 + 45);
			$('.esriScalebar').css({'width': '' + (scalebar2 + 20) + ''});
		}
	}, 10);
}

function trimThis(value){
	if(typeof(value) == 'string'){
		return value.trim();
	} else {
		return value;
	}
}

var updateTOCvisibility = function(){
    var layerId, LayerObj, parentLayerContainers = [];
    var layerContainers = $('.legendLayerContainer');
    $(layerContainers).each(function(i){
        if ($(layerContainers[i]).children().eq(1).hasClass('legendLayerParent') == false){
			var $checkbox_elem = $(layerContainers[i]).children().eq(1);
			layerId =  $checkbox_elem.attr('layerid');
			//layerId = layerId.replace('Checkbox', '');
			
			// build array of all ids rep[resented by the checkbox
            var allLayerIds = [layerId];
			var linkedIdsString = $checkbox_elem.attr('linkedlayerids');
			if(linkedIdsString){
				allLayerIds = allLayerIds.concat(linkedIdsString.split(",")); // combine with linked layer ids
                allLayerIds =$.map(allLayerIds, function(n,i){
					return trimThis(n)
				});
			}

			if(layerId != "imageryNAIPLayer"){ // if it's the NAIP layer... just skip it
				if(typeof(window[layerId]) !== "undefined"){ // check of the object exists
					var tocVisible = false; // if false then dim the toc elem
					for(var j=0; j<allLayerIds.length; j++){
						layerObj = window[allLayerIds[j]];
						if (!((layerObj.minScale < map.getScale() && layerObj.minScale != 0) || (layerObj.maxScale > map.getScale() && layerObj.maxScale != 0))){
							tocVisible = true; // a layer is visible the toc should be visible
						}
					}

					if (tocVisible == false){
						$(layerContainers[i]).css('opacity', '0.5');
						$('#layerSwipeTbody').find('[layer='+layerId+']').first().parent().parent().addClass('disabledButton'); //disable layer swipe row
						if(popup.selectedIndex !== -1 && popup.features.length > 0){
							if(popup.features[popup.selectedIndex]._layer && popup.features[popup.selectedIndex]._layer.id === layerObj.id){ // found a match. Close the popup
								popup.hide();
							}
						}
					} else {
						$(layerContainers[i]).css('opacity', '');
						$('#layerSwipeTbody').find('[layer='+layerId+']').first().parent().parent().removeClass('disabledButton'); //enable layer swipe row
						/*if(popup.selectedIndex !== -1 && popup.features.length > 0){
							if(popup.features[popup.selectedIndex]._layer && popup.features[popup.selectedIndex]._layer.id === layerObj.id){ // found a match. Close the popup
								//popup.show();
								//popup.select(popup.selectedIndex);//commented out, cuz popup is shown when not needed...and it's 'detached' from feature
							}
						}*/
					}
				}
			}
        } else {
			parentLayerContainers.push(layerContainers[i]);
		}
    });
	 $(parentLayerContainers).each(function(index){
		//console.log($(parentLayerContainers[index]).children().eq(1).attr('id'));
		var layerGroup = $(parentLayerContainers[index]).find($('.legendLayerContainer'));
		var layerCount = layerGroup.length, disabledLayerCount = 0;
		$(layerGroup).each(function(index2){
			var styleProps = $(layerGroup).eq(index2).prop('style');
			if(styleProps.opacity == "0.5"){
				disabledLayerCount++;
			}
		});
		if(disabledLayerCount === layerCount){
			$(parentLayerContainers[index]).css('opacity', '.5');
		} else {
			$(parentLayerContainers[index]).css('opacity', '');
		}
	 });
}

var setSwipeLabels = function(){
	var layersFound = [], layersNames = [];
	var htmlString = "";
	$('#imageryLayersCheckbox').parent().find('.legendLayerContainer').each(function(index){
		layersFound[index] = $(this).find('.legendCheckbox').attr('layerid')//.replace('Checkbox', '');
		layersNames[index] = $(this).find('.legendLabel').html();
		layersNames[index] = layersNames[index].slice(1,layersNames[index].length);
		if(index == 0){
			htmlString += 	'<tr>' + 
								'<td>' + $(this).find('.legendLabel').html() + '</td>' +
								'<td><span class="fa fa-circle-o leftSwipe" layer="' + layersFound[index] + '" name="' + layersNames[index] + '"></span></td>' +
								'<td><span class="fa fa-dot-circle-o rightSwipe" layer="' + layersFound[index] + '" name="' + layersNames[index] + '"></span></td>' +
							'</tr>';
		} else if(index == 1){
			htmlString += 	'<tr>' + 
								'<td>' + $(this).find('.legendLabel').html() + '</td>' +
								'<td><span class="fa fa-dot-circle-o leftSwipe" layer="' + layersFound[index] + '" name="' + layersNames[index] + '"></span></td>' +
								'<td><span class="fa fa-circle-o rightSwipe" layer="' + layersFound[index] + '" name="' + layersNames[index] + '"></span></td>' +
							'</tr>';
		} else {
			htmlString += 	'<tr>' + 
								'<td>' + $(this).find('.legendLabel').html() + '</td>' +
								'<td><span class="fa fa-circle-o leftSwipe" layer="' + layersFound[index] + '" name="' + layersNames[index] + '"></span></td>' +
								'<td><span class="fa fa-circle-o rightSwipe" layer="' + layersFound[index] + '" name="' + layersNames[index] + '"></span></td>' +
							'</tr>';
		}
	});
	if(layersFound.length <= 1 && (modules.indexOf('soils') != -1 || modules.indexOf('millage') != -1 || modules.indexOf('addressSubmission') != -1 || modules.indexOf('csv') != -1)){ // only one imagery layer found remove the layer swipe stuff
		$("#layerSwipeControlsDiv").remove(); // remove the layerSwipeTools
	} else if (layersFound.length <= 1){
		$("#miscToolsHeading, #miscControlsAccordionPane, #miscControlsWrapper").remove(); // remove the misc tools
		$('#drawControlsAccordion').accordion('refresh');
	}
	$('#layerSwipeTbody').html(htmlString);
	
	// hander for clicks on radio buttons
	var leftSwipeHandler = $('.leftSwipe').on("click", function(e){
		// this handler doesn't have to sync with the layers list. It just changes the URL of the swipe layer.
		var target = e.target;
		$('.leftSwipe').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
		$(target).addClass('fa-dot-circle-o');
		
		var leftSideLayers = [];
		leftSideLayers.push(window[$(target).attr('layer')]);
		toggleSwipe(false);
		
		if(typeof($('#' + $(target).attr('layer') + 'Checkbox').attr('linkedLayerIds')) !== "undefined"){
			var linkedLayersArr = $('#' + $(target).attr('layer') + 'Checkbox').attr('linkedLayerIds').replace(/ /g, "");
			linkedLayersArr = linkedLayersArr.split(",");
			for(var i = 0; i < linkedLayersArr.length; i++){
				leftSideLayers.push(window[linkedLayersArr[i]]);
			}
		}
		toggleSwipe(true, leftSideLayers);

	});
	var rightSwipeHandler = $('.rightSwipe').on("click", function(e){
		var leftSideLayers = [];

		// this handler does have to sync with the layers list. It fires a click at a layer.
		var target = e.target;
		$('.rightSwipe').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
		$(target).addClass('fa-dot-circle-o');
		var targetId = '#' + $(e.target).attr('layer') + 'Checkbox';
		$(targetId).trigger("click");

		// all of this left side stuff is here just so we can toggle layer swipe on and off to update the label
		var leftSideLayers = [];
		leftSideLayers.push(window[$(".leftSwipe.fa-dot-circle-o").attr('layer')]);
		toggleSwipe(false);
		
		if(typeof($('#' + $(".leftSwipe.fa-dot-circle-o").attr('layer') + 'Checkbox').attr('linkedLayerIds')) !== "undefined"){
			var linkedLayersArr = $('#' + $(".leftSwipe.fa-dot-circle-o").attr('layer') + 'Checkbox').attr('linkedLayerIds').replace(/ /g, "");
			linkedLayersArr = linkedLayersArr.split(",");
			for(var i = 0; i < linkedLayersArr.length; i++){
				leftSideLayers.push(window[linkedLayersArr[i]]);
			}
		}
		toggleSwipe(true, leftSideLayers);
	});
	
		$(function(){
		if(isEmbededMap === true){
			activateControl('#layerControls'); // activate layers in control pane
			$("#layersToggle, #signIn, #search").removeClass("d-none d-sm-block"); // make it so these guys still show up if embeded map is tiny
			$(".navButton:not(#signIn,#layersToggle,#search), .navbar-toggle, #navbar").remove(); // remove all navbuttons that could point to functionality we dont want them to have in an embeded map
		}	
		if(usePublicMap){
			$("#signIn, #signInAlt, #insightsAlt, .dataRefreshDiv, #dataRefreshAlt").addClass("hidden");
		}		
	});
	
	
	// TEMPORARY BUG FIX SECTION: use this area to add bug fixes that may be specific to browser or JS API versions
	
	// "will-change" should be used as a last resort. It can cause stacking order anomalies (and as of API v3.26, Chrome 70 is in fact having said anomalies).
	// To test for this anomaly, start editing any graphic (comment out the line below or disable in dev tools).
	// Just pan and then move the graphic around. It will clip behind literally nothing!
	$('head').append('<style id="willChangeMyEye">#map_gc{will-change: auto!important;}</style>');
	
}

// #################################################
// ####				JQUERY TIME!!! 				####
// #################################################
// jQuery stuff to load right away

function activateControl(controlName, state){
	// this cleans up scalebar after prints
	$('.esriScalebar').removeClass('portrait');

	$('.switcherDialog').fadeOut('fast');
	$('#mapSwitcherButton').css('color', '');

	let controlNameArr = controlName.split('%20');
	controlName = controlNameArr[0]; //header only, subheader is stored in position [1], will be used later
	// open data pane if help button is the active control

	if(controlName != '#printControls'){
		if(state != "closed"){
			showControlPane();
		}
	}

	// this cancels the rest of the function if the user clicked an already active tool
	if($(controlName).hasClass('active')){
		return;
	}

	viewportWidth = $(window).width();
	viewportHeight = $(window).height();

	// Hide print controls if showing
	if($('#printControls').hasClass('active')) {
		if($('#printControlsAccordionWrapper').css('display') == 'none'){
			if($('#generalDetailsWrapper').is(':visible')){
				destroyReport(); // lrp
			} else if($('#insightsOandMWrapper').is(':visible')){
				destroyOandMReport();
			} else if($('#insights-query-report').is(':visible')){
				destroyQIReport();
			} else if($('#sales-details-wrapper').is(':visible')){
				destroySalesRecordsReport();
			} else if($("#pfe-print-copy").length > 0){ // pfe print mode active if this element exists
				formViewer.closePfePrint();
			}
		} else {
			if($('#soilMapInfo').length && !($('#soilMapInfo').hasClass('hidden'))){
				cancelSoilPrint();
			}
			removeMapPrint();
		}
	}

	// Fade out the active control pane, and set all control icons back to white
    $('.controls.active').hide();
	$('.controls').removeClass('active');
	$('#perfectFormEditorButton, #insightsButton, #drawButton, #signInButton, #printModeToggleButton, #featureToolsButton, #offlineButton, #extraControlsButton span').removeClass('active');
	$('#measureButton').attr('src', 'img/measureLine6.png');
	$('#searchButton').removeClass('active');
	$('#layersToggleButton').removeClass('active');
	$('#printModeToggleButton').removeClass('active');

	$(controlName)
		.removeClass('hidden')
		.addClass('active') 
		.stop({clearQueue: true})
		.fadeIn(600, function(){
			$(controlName).find('.ps').perfectScrollbar('update');
		})

	
	// gonna do this a little differently here for permitting editor
	if(pfeEnabled === true){
		if(controlName.indexOf("permitting-editor") > -1){ //when page refreshes, it contains #, else no #
			$("#perfectFormEditorButton").addClass("active");
			activeControl = "permitting-editor";
			showPerfectFormEditor();
		} else if(activeControl === "permitting-editor") {
			hidePerfectFormEditor();
		}
	}

	// Set active control icon to blue.
	// this changes the active control to blue
	switch(controlName) {
		case '#extraControls':
			$('#extraControlsButton span').addClass('active');
			activeControl = 'extraControls';
			break;
		case '#accountControls':
            $('#signInButton').addClass('active');
			$( "#loginAccordion" ).accordion( "refresh" );
			$( "#accountControlsAccordion" ).accordion( "refresh" );
			activeControl = 'accountControls';
			break;
				case '#insightControls':
			$('#insightsButton').addClass('active');
			$("#insightsAccordion").accordion("refresh");
			activeControl = 'insightControls';
			break;
		case '#drawControls':
			//$('#drawButton').attr('src', 'img/draw6Active.png');
            $('#drawButton').addClass('active');
			$( "#drawControlsAccordion" ).accordion( "refresh" );
			activeControl = 'drawControls';
			break;
		case '#searchControls':
			$('#searchButton').addClass('active');
			$( "#searchControlsAccordion" ).accordion( "refresh" );
			activeControl = 'searchControls';
			break;
		case '#layerControls':
			$('#layersToggleButton').addClass('active');
			activeControl = 'layerControls';
			break;
		case '#printControls':
			$('#printModeToggleButton').addClass('active');
			$( "#printControlsAccordion" ).accordion( "refresh" );
			if($('#printedPage').hasClass('reportView')){
				$('#printControlsAccordionWrapper, #printButtonsWrapper').stop().fadeOut('fast');
				//$('#reportControls').removeClass('hidden');
				$('#printModeToggleButton').addClass('active');
			} else if($('#labelPrintDiv').css('display') != 'none'){
				print();
			} else {
				$('#printModeToggleButton').addClass('active');
				addMapPrint();
			}
			
			activeControl = 'printControls';
			break;
	}
	if(controlNameArr[1]){ //open subheader
		if(controlName[0] != 'featureEditControls'){
			$('#'+controlNameArr[1]).prev('.controlsHeading').trigger('click'); //will update activeControl with subHeader info
		}
	}
	updateURLParams();
	activeControl = controlNameArr[0].replaceAll('#',''); //reset to just the original header, so all the pointers are still working
}

$('#controlPaneExpando .controlsHeading').on('click', function(e){
	let name = $(e.currentTarget).parents('.controls').attr('id');
	if(name != 'featureEditControls'){
		name += '%20' + $(e.currentTarget).next('.controlsContent').attr('id')
	}
    activeControl = name; //update url to have header and subheader
	updateURLParams();
	activeControl = name.split('%20')[0].replaceAll('#',''); //reset to just the original header, so all the pointers are still working
});

function showControlPane(){

	$("#controlPaneHandle, #controlPaneExpando").addClass("active");
	setTimeout(function(){
		$('#controlPaneHandle > span').removeClass('fa-angle-left').addClass('fa-angle-right');
		$("#controlPaneHandle").css('padding-left', ' 7px');
	}, 750);
	//$('#printedPage').stop().animate({left: (274 * -0.5)}); //There's no room for this to move when the screen is so narrow
	
	controlPaneExpandoOpen = true;
	$('#controlPaneHandle').fadeIn();
}
function hideControlPane() {
	$(window).trigger('control-pane-close');
	
	$("#controlPaneHandle, #controlPaneExpando").removeClass("active");
	setTimeout(function(){
		$('#controlPaneHandle > span').removeClass('fa-angle-right').addClass('fa-angle-left');
		$("#controlPaneHandle").css('padding-left', '0px');
	}, 750);
	controlPaneExpandoOpen = false;

	if($('#dataPaneExpando').hasClass('active') && !$('#dataPaneExpando').hasClass('noPointerEvents')){ //dont hide in print mode
		$('#controlPaneHandle').fadeOut();
	}
}

var controlPaneExpandoOpen = false, dataPaneExpandoOpen = false;
var controlPaneHandle, dataPaneHandle;
window.addEventListener("resize", function(){
	setResponsivestuff();
});

var androidPhoneHandlersSet = false;
function setResponsivestuff(){
	viewportWidth = $(window).width();
	viewportHeight = $(window).height();
	
	// close bootstrap navbar dropdown if open
	$('.navbar-collapse').collapse('hide');

	// Make user graphics layer full screen
	$('#userGraphicsLayer_layer').css('display', '');
	
	// Resize accordion widget
	$( "#drawControlsAccordion, #searchControlsAccordion, #printControlsAccordion, #loginAccordion, #accountControlsAccordion, #featureEditAccordion" ).accordion( "refresh" );
	
	// Resize Datapane scrolling container. Mobile needs extra removed for the close button bar
	if(isMobile == false){
		$('#generalDetailsScrollPane').css('height', viewportHeight - 98);
	} else {
		$('#generalDetailsScrollPane').css('height', '100%');
	}
	
	// Resize the div that holds the layer swipe radio buttons
	$('#miscControlsDiv').height($('#miscControlsWrapper').height());
	
	// Resize the div that holds the layer swipe radio buttons
	$('#miscControlsDiv').height($('#miscControlsWrapper').height());

	// set height of measure results scroll pane
	var height = $('#measureControlsScrollPane').height() - 255;
	$('#measureResultsScrollPane').css("height", height);
	
	// Update 'perfect' scrollbars
	$('.ps').perfectScrollbar('update');
	
	var parcelSearchFocusHandler, parcelSearchFocusHandler;
	
	// this handler is for android phones so that when you're typing searches, it scrolls to the focused input box when the on screen keyboard shows
	if(androidPhoneHandlersSet == false && userAgent == "Android" && window.viewportHeight < 768){
		androidPhoneHandlersSet = true;
		// search event handlers for mobile
		$('#searchDiv').scrollTop(0);		
		parcelSearchFocusHandler = $('.pinInput, .searchInput, .searchInputWide').on("focus", function(evt){
			
			setTimeout(function(){
				if(viewportHeight < 300){
					$('.footer').css({'height':'0px'});
					$('.accordionHeading, #searchType').css({'display': 'none'});
					$('#contentContainer, #controlPaneExpando').css({'bottom':'0px'});
					$('#searchControlsAccordion').accordion('refresh');
					$('.controlsContent').css({'height': (viewportHeight - 75)+ 'px'});
					
					setTimeout(function(){
						$('#searchDiv').scrollTop(0);// must reset scroll to get measurement
						var scrollTo = $(evt.currentTarget).position();
						$('#searchDiv').scrollTop(scrollTo.top - 25);
					}, 100);
				}
			},500);
		});
		parcelSearchBlurHandler = $('.pinInput, .searchInput, .searchInputWide').on('blur', function(){
			setTimeout(function(){
				var activeElement = document.activeElement;
				// if none of these classes are present in the selected element, restore the rest of the search controls
				if($(activeElement).hasClass('pinInput') == false && $(activeElement).hasClass('searchInput') == false && $(activeElement).hasClass('searchInputWide') == false){
					$('.accordionHeading, #searchType').css({'display': 'block'});
					$('.controlsContent').css({'height': ''});
					$('#contentContainer, #controlPaneExpando').css({'bottom':'40px'});
					$('.footer').css({'height':'40px'});
					$('#searchControlsAccordion').accordion('refresh');
				}
			}, 250);
		});
	}
	
}

$(function() {
	// init accordions and scrollbars
	$( "#drawControlsAccordion" ).accordion({
		heightStyle: "fill",
		header: ".controlsHeading",
		activate: function( event, ui ) {
			// Resize the div that holds the layer swipe radio buttons
			$('#miscControlsDiv').height($('#miscControlsWrapper').height());
			
			// set height of measure controls scroll pane
			var height = $('#measureControlsScrollPane').height() - 255;
			$('#measureResultsScrollPane').css("height", height);
			$('#drawControlsAccordion').find('.ps').perfectScrollbar('update');
		}
	});
	
	$('#drawControlsScrollPane').perfectScrollbar();
	$('#measureControlsScrollPane').perfectScrollbar();
	$('#measureResultsScrollPane').perfectScrollbar();
	$('#bufferControlsScrollPane').perfectScrollbar();
    
	$( "#insightsAccordion" ).accordion({
		heightStyle: "fill",
		header: ".controlsHeading",
		/*activate: function( event, ui ) {
			//console.log(ui);
			if(ui.newHeader[0].id === "insightsOandMDataHeading"){
				getAllFailingOandMPermits(ehPermitLayer);
			}
			$('#insightsAccordion').find('.ps').perfectScrollbar('update');
		}*/
	});
    
    $('#insightsDataAccordionPane').perfectScrollbar();
    $('#insightsRegionAccordionPane').perfectScrollbar();
	
	$( "#searchControlsAccordion" ).accordion({
		heightStyle: "fill",
		header: ".controlsHeading"
	});

	$('#searchResultsScrollPane').perfectScrollbar();
	
	$('#recentOuter').perfectScrollbar();
	
	$('#miscControlsDiv').perfectScrollbar();
	
	$( "#loginAccordion" ).accordion({
		heightStyle: "fill",
		header: ".controlsHeading"
	});
	$( "#featureEditAccordion" ).accordion({
		heightStyle: "fill",
		header: ".controlsHeading"
	});
	$("#templatePickerScrollPane").perfectScrollbar();
	$( "#accountControlsAccordion" ).accordion({
		heightStyle: "fill",
		header: ".controlsHeading"
	});
    $("#featureEditAttrAccordionPane").perfectScrollbar();
	
	$("#printControlAccordion").height(viewportHeight - 220);
	$( "#printControlsAccordion" ).accordion({
		heightStyle: "fill",
		header: ".controlsHeading",
		activate: function( event, ui ) {
			// Resize the div that holds the layer swipe radio buttons
			//$('#printControlsDiv').height($('#miscControlsWrapper').height());
			$('#printControlsAccordion').find('.ps').perfectScrollbar('update');
		}
	});
	$("#printOptionsScrollPane").perfectScrollbar();
	$("#printLegendBuilderScrollPane").perfectScrollbar();
	
	$("#alertModal p").perfectScrollbar();
});


// This helper function checks for data returned as undefined or as the object prototype (that pesky empty object that appears as [object Object])
function testForGarbageData(data){
	if (data == undefined){
		return "--";
	} else if(data.toString() == '[object Object]'){
		return "--";
	} else {
		return data;
	}
}

function updateURLgeometry(){
  var jsonObj = {};
  jsonObj.currentMap = currentMap;
  jsonObj.userGraphicsLayer = [];
  jsonObj.measurementLayer = [];
  jsonObj.measurementLabelsLayer = [];
  
  for (var i = 0; i < userGraphicsLayer.graphics.length; i++){
	  jsonObj.userGraphicsLayer[i] = userGraphicsLayer.graphics[i].toJson();
  }
  for (var i = 0; i < measurementLayer.graphics.length; i++){
	  jsonObj.measurementLayer[i] = measurementLayer.graphics[i].toJson();
  }
  if(!isMobile){ // this layer isnt present on mobile devices
	for (var i = 0; i < measurementLabelsLayer.graphics.length; i++){
		jsonObj.measurementLabelsLayer[i] = measurementLabelsLayer.graphics[i].toJson();
	}
  }

  if(insightsEnabled === true && typeof(insights) == 'object'){ // store insights stuff in data64
	jsonObj.insights_store = insights.url_store;
  }

  if(typeof(buff64) !== "undefined"){
	jsonObj.buff64 = buff64;
  }

  var jsonString = JSON.stringify(jsonObj);
  data64 = LZString.compressToEncodedURIComponent(jsonString);
  updateURLParams();
}

if(isMobile){ //move spinner to mobile data pane
	var spinner = $('#generalDetailsOverlay').addClass('mobileSpinner').detach();
	$('#mobileDataPane').prepend(spinner);

}

//function to create and print Avery labels. Note: valHTML is just the text of the dropdown. This function looks for the number portion of the string. $('#averyLabelsSelect').html() would give the string.
function printAveryLabels(featureSet, valHTML){
	var type = "5160", labelsPerPage = 30;

	var labelsPerPage = 0;
	if(valHTML.indexOf("5160") !== -1){
		type = "5160";
		labelsPerPage = 30;
	} else if(valHTML.indexOf("5162") !== -1){
		type = "5162";
		labelsPerPage = 14;
	} else if(valHTML.indexOf("5163") !== -1){
		type = "5163";
		labelsPerPage = 10;
	}


	$('head').append('<style id="pdfLabelStyle">@page{margin: 0cm}</style>')
	$('#printHint').addClass('hidden');
	$('.dijitTooltipDialogPopup').addClass('hidden');
	$('#neatline').stop().animate({opacity: 0}, 400, function(){
		$('#neatline').addClass('visuallyHidden'); // if display:none is used here, it breaks the map.
		// make so content goes to printedPage div by making containers called 'printLabelsPage' for each 30 labels
		var outputStr = [];
		var pageIndex = 0;
		outputStr[0] = ''; // remove the word 'undefined' from the first array index or it will be printed. Not sure why.
		for(var i = 0; i < featureSet.length; i++){
			if (i % labelsPerPage == 0 && i != 0){
				pageIndex++;
				outputStr[pageIndex] = '';
			}
			var name = featureSet[i].OwnerName1;
			var streetAdd = featureSet[i].OwnerStreetAddress;
			var CSZ = featureSet[i].OwnerCity + ', ' +
						featureSet[i].OwnerState + ' ' +
						featureSet[i].OwnerZip;

			outputStr[pageIndex] += "<div class='averyLabel avery" + type + "'>" + name + "<br>" + streetAdd + "<br>" + CSZ + "</div>";
		}
		for(var i = 0; i <= pageIndex; i++){
			// create a "page" div for every group of labels (30 labels for 5160 for example).
			$('#printedPage').append('<div id="printLablesPage'+ i +'" class="printLabelsPage avery' + type + '" style="display: none"></div>');
			$('#printLablesPage'+ i + '').html(outputStr[i]);
		}

		$('#contentContainer, #printedPage').addClass('portraitVariable');
		$('.printLabelsPage').addClass('portraitLetter').fadeIn();
		$('#labelPrintDiv').fadeIn();
		if(pageIndex > 0){
			$('#labelPrintPaginationDiv').removeClass('hidden');
		}
		$('.printLabelsPage').eq(0).css('z-index', '1');
		if(firefox == true){
			$('body').height(1021 * outputStr.length);// Firefox needs the body set to the height you want printed if more than a page and body tag has hidden overflow
		}
		map.resize(true);
		map.reposition();

		$('#labelPrintPageNo').html('1 of ' + outputStr.length);

		var contentHTML = "";
		for(var i = 0; i < $('.printLabelsPage').length; i++){
			contentHTML += '<div id="printLablesPage'+ i +'" class="printLabelsPage portraitLetter">'+ $('.printLabelsPage').eq(i).html() + '</div>';
		}
	});
}

$('#printLabels').on("click", function(){
	print();
});

$('#labelPageUp').on("click", function(){
	//Get current z index
	var foundIndex = 0;
	for(var i = 0; i < $('.printLabelsPage').length; i++){
		if ($('.printLabelsPage').eq(i).css('z-index') == '1'){
			foundIndex = i;
		}
	}

	if(foundIndex < $('.printLabelsPage').length - 1){
		// zero them all out
		$('.printLabelsPage').css('z-index', '0');
		// index the next one up
		var plus1 = foundIndex + 1;
		var plus2 = foundIndex + 2;
		$('.printLabelsPage').eq(plus1).css('z-index', '1');
		$('#labelPrintPageNo').html(plus2 + ' of ' + $('.printLabelsPage').length);
	}
});
$('#labelPageDown').on("click", function(){
	//Get current z index
	var foundIndex = 0;
	for(var i = 0; i < $('.printLabelsPage').length; i++){
		if ($('.printLabelsPage').eq(i).css('z-index') == '1'){
			foundIndex = i;
		}
	}

	if(foundIndex > 0){
		// zero them all out
		$('.printLabelsPage').css('z-index', '0');
		// index the next one down
		var minus1 = foundIndex - 1;
		$('.printLabelsPage').eq(minus1).css('z-index', '1');
		$('#labelPrintPageNo').html(foundIndex + ' of ' + $('.printLabelsPage').length);
	}
});

// Grab script to populate LRP data pane
// we only want these classes in place if it truly is a phone. Otherwise, prints will look funny, but phones do not print
var colXsStr = " col-xs-6 ", hiddenXsStr = "";
if(isPhone === true){
	colXsStr = " col-xs-12 ";
	hiddenXsStr = " d-none d-sm-block ";
} else {
	$('#generalDetailsWrapper .col-sm-6.col-xs-12').removeClass('col-xs-12').addClass('col-xs-6');
	$('#generalDetailsWrapper .col-sm-6.col-xs-12').removeClass('col-xs-12').addClass('col-xs-6');
	$('#generalDetailsWrapper .d-none.d-sm-block').removeClass('d-none d-sm-block');
}

function populateDetailData(){
	// General Property Details heading
	if($('#formEdit').is(':visible')){
		$('#generalDetailsScrollPane').css('opacity',1);
		$('#generalDetailsWrapper').hide();
		return; //dont add if buttons are visible
	}
	$('#generalDetailsWrapper').css('display', 'block'); // show this cause we may have hidden it when creating another white-pane report
	$('#detailsParcelNo').html(searchData.record.ParcelNumber);
	
		$("#bsnaLink, #sanitationLink").remove();

		if(searchData.record.PropAddressCity && typeof searchData.record.PropAddressCity=='string' && searchData.record.PropAddressCity.toLowerCase() === "bay city"){
			$("#generalDetails").after('<a id="sanitationLink" class="lrpCustomLink hideFromPrint" href="https://www.baycitymi.org/207/Sanitation" target="_blank"> City Sanitation Service Day Information </a>');
		}

		$("#generalDetails").after('<a id="bsnaLink" class="lrpCustomLink hideFromPrint" href="https://bsaonline.com/SiteSearch/SiteSearchResults?SearchFocus=All+Records&SearchCategory=Parcel+Number&SearchText=' + searchData.record.ParcelNumber + '&uid=685&SearchOrigin=0" target="_blank"> Click here for County Website </a>');

		var pin4doxpop = searchData.record.ParcelNumber;
		if($.isEmptyObject(pin4doxpop) === false){
			pin4doxpop = pin4doxpop.replace(/-/g, "");
			$("#doxpopLink").remove();
			$("#bsnaLink").after('<a id="doxpopLink" class="lrpCustomLink hideFromPrint" href="https://www.doxpop.com/prod/mi/recorder/FindRecordedDocuments?action=GET-FIND-RECORDED-DOCUMENTS&regionId=4162&taxParcelId=' + pin4doxpop + '&i=1&appid=0&unit=685" target="_blank"> Click here for Recorded Documents </a>');
		}
		
	// Property Address
	$("#PropAddressCombined").html(testForGarbageData(searchData.record.PropAddressCombined));	$('#PropAddressCSZ').html(testForGarbageData(searchData.record.PropAddressCity) + ", " + testForGarbageData(searchData.record.PropAddressState) + ", " + testForGarbageData(searchData.record.PropAddressZip));
	
	// Owner Address
	$('#OwnerName1').html(testForGarbageData(searchData.record.OwnerName1));
	$('#TaxingUnit').html(testForGarbageData(searchData.record.TaxingUnit));
	$('#OwnerName2').html(testForGarbageData(searchData.record.OwnerName2));
	$('#TaxingUnitName').html(testForGarbageData(searchData.record.TaxingUnitName));
	$('#OwnerStreetAddress').html(testForGarbageData(searchData.record.OwnerStreetAddress));
	
	
				$("#OwnerCSZ").html(testForGarbageData(searchData.record.OwnerCity) + ", " + testForGarbageData(searchData.record.OwnerState) + " " + testForGarbageData(searchData.record.OwnerZipcode));
				$("#UT_codeLabel").html("Unit:");
				$("#UT_nameLabel").html("Unit Name:");
				$("#VillageCodeLabel").html("&nbsp;");
				$("#VillageNameLabel").html("&nbsp;");
				$("#VillageCode").html(testForGarbageData("&nbsp;"));
				$("#VillageName").html(testForGarbageData("&nbsp;"));
				
	// General info for current tax year
	// Setup Table and Rows depending on selected county (Isabella doesn't use BS&A, so it was easier to just make two separate sets of tables)
	$('#currentTaxYear').html(searchData.record.AssessmentYear);
	$('#prevTaxYear').html(parseInt(searchData.record.AssessmentYear) - 1 );
	$('#prevPrevTaxYear').html(parseInt(searchData.record.AssessmentYear) - 2 );
	
				var generalInfoRowStr = "";
				
					generalInfoRowStr += '<tr>' +
						'<th id="lrpPINLabel">Parcel Number:</th>' +
							'<td id="ParcelNumber" class="lrpDataCell">&nbsp;</td>' +
						'</tr>' +
						'<tr id="lrpTaxStatusRow" style="display:none">' +
							'<th id="lrpTaxStatusLabel">Tax Status:</th>' +
							'<td id="lrpTaxStatus" class="lrpDataCell">&nbsp;</td>' +
						'</tr>' +
						'<tr>' +
							'<th id="lrpPropClassLabel">Property Class:</th>' +
							'<td id="PropertyClass" class="lrpDataCell">&nbsp;</td>' +
						'</tr>' +
						'<tr>' +
							'<th id="lrpPropClassNameLabel">Class Name:</th>' +
							'<td id="PropertyClassName" class="lrpDataCell">&nbsp;</td>' +
						'</tr>' +
						'<tr>' +
							'<th id="lrpSchoolDistCodeLabel">School Dist Code:</th>' +
							'<td id="SchoolDistrict" class="lrpDataCell">&nbsp;</td>' +
						'</tr>' +
						'<tr>' +
							'<th id="lrpSchoolDescLabel">School Dist Name:</th>' +
							'<td id="SchoolDesc" class="lrpDataCell">&nbsp;</td>' +
						'</tr>' +
						'<tr id="PREstatusRow">' +
							'<th></th>' +
							'<td >&nbsp;</td>' +
						'</tr>' +
						'<tr id="PRE_Year2_FinalRow">' +
							'<th>PRE <span id="PRE_Year2_Year_Label">&nbsp;</span>:</th>' +
							'<td id="PRE_Year2_Final" class="lrpDataCell">&nbsp;</td>' +
						'</tr>' +
						'<tr id="PRE_Year1_MayRow">' +
							'<th>PRE <span id="PRE_Year1_Year_Label">&nbsp;</span>:</th>' +
							'<td id="PRE_Year1_May" class="lrpDataCell">&nbsp;</td>' +
						'</tr>' +
						'<tr id="">' +
							'<th id="">&nbsp;</th>' +
							'<td id="" class="lrpDataCell">&nbsp;</td>' +
						'</tr>' +
						'<tr id="">' +
							'<th id="">&nbsp;</th>' +
							'<td id="" class="lrpDataCell">&nbsp;</td>' +
						'</tr>';
					$("#generalInfo_Left").html(generalInfoRowStr);// Write left row to html
					generalInfoRowStr = "";
					generalInfoRowStr +='<tr>' +
											'<th><div>Assessed Value:</div></th>' +
											'<td id="Asr_Year1_MBOR" class="lrpDataCell">&nbsp;</td>' +
										'</tr>' +
										'<tr>' +
											'<th><div>Taxable Value:</div></th>' +
											'<td id="Tax_Year1_MBOR" class="lrpDataCell">&nbsp;</td>' +
										'</tr>' +
										'<tr>' +
											'<th>State Equalized Value:</th>' +
											'<td id="SEV_Year1_MBOR" class="lrpDataCell">&nbsp;</td>' +
										'</tr>' +
										'<tr id="ExemptionPercentRow">' +
											'<th></th>' +
											'<td ><div>&nbsp;</div></td>' +
										'</tr>' +
										'<tr id="TentativeTaxableRow" >' +
											'<th><div>&nbsp;</div></th>' +
											'<td>&nbsp;</td>' +
										'</tr>' +
										'<tr class="'+ hiddenXsStr +'">' +
											'<th><div>&nbsp;</div></th>' +
											'<td>&nbsp;</td>' +
										'</tr>' +
										'<tr id="taxStatusFiller" style="display:none">' +
											'<th id="">&nbsp;</th>' +
											'<td id="" class="lrpDataCell">&nbsp;</td>' +
										'</tr>' +
										'<tr id="PRE_Year0_MayRow">' +
											'<th>&nbsp;</th>' +
											'<td class="lrpDataCell">&nbsp;</td>' +
										'</tr>' +
										'<tr id="PRE_DateRow">' +
											'<th id="PRE_Date_Label">&nbsp;</th>' +
											'<td id="PRE_Date" class="lrpDataCell">&nbsp;</td>' +
										'</tr>' +
										'<tr id="RescinddateRow">' +
											'<th id="Rescinddate_Label">&nbsp;</th>' +
											'<td id="Rescinddate" class="lrpDataCell">&nbsp;</td>' +
										'</tr>' +
										'<tr id="PRE_DateFillerRow" class="'+ hiddenXsStr +'">' +
											'<th><div>&nbsp;</div></th>' +
											'<td>&nbsp;</td>' +
										'</tr>';
										
					$("#generalInfo_Right").html(generalInfoRowStr);// Write left row to html

	// Populate The tables created above
	
	
				var yearPlus1 = searchData.record.AssessmentYear;
				if(typeof(searchData.record.AssessmentYear) !== "number"){
					yearPlus1 = parseInt(searchData.record.AssessmentYear) + 1;
				} else {
					yearPlus1 = yearPlus1 + 1;
				}
				var yearMinus1 = searchData.record.AssessmentYear;
				if(typeof(searchData.record.AssessmentYear) !== "number"){
					yearMinus1 = parseInt(searchData.record.AssessmentYear) - 1;
				} else {
					yearMinus1 = yearMinus1 - 1;
				}


				$("#ParcelNumber").html(testForGarbageData(searchData.record.ParcelNumber));
				$("#Asr_Year1_MBOR").html("$" + testForGarbageData(searchData.record.Asr_Year1_MBOR));
				$("#PropertyClass").html(testForGarbageData(searchData.record.PropertyClass));
				$("#Tax_Year1_MBOR").html("$" + testForGarbageData(searchData.record.Tax_Year1_MBOR));
				$("#PropertyClassName").html(testForGarbageData(searchData.record.PropertyClassName));
				$("#SchoolDistrict").html(testForGarbageData(searchData.record.SchoolDistrict));
				$("#SchoolDesc").html(testForGarbageData(searchData.record.SchoolDesc));
				$("#SEV_Year1_MBOR").html("$" + testForGarbageData(searchData.record.SEV_Year1_MBOR));

				// Populate "Exemption Percent" --OR-- "Tentitive Value" here if applicable (but cant have both). Otherwise, show empty <td>s.
				// Currently, there are no maps that use both but this could be an issue down the road.
				if(typeof(searchData.record.ExemptionPercent) !== "undefined"){
					if($.isEmptyObject(searchData.record.ExemptionPercent) === false){
						$("#ExemptionPercentRow").html("<th>Exemption Percent:<\/th><td>" + searchData.record.ExemptionPercent + "<\/td>");
					} else {
						$("#ExemptionPercentRow").html("<th><\/th><td>&nbsp;<\/td>");
					}
				} else if(typeof(searchData.record.Asr_Year0_MBOR) !== "undefined"){ // this is for sagianw right now.
					if($.isEmptyObject(searchData.record.Asr_Year0_MBOR) === false){
						$("#ExemptionPercentRow").html("<th style=\"color: #d43f3a;\">" + yearPlus1 + " Tentative Assessed:<\/th><td>$" + searchData.record.Asr_Year0_MBOR + "<\/td>");
					} else {
						$("#ExemptionPercentRow").html("<th><\/th><td>&nbsp;<\/td>");
					}
				} else {
					$("#ExemptionPercentRow").html("<th><\/th><td>&nbsp;<\/td>");
				}
				
				// populate PRE status here if applicable, otherwise, show empty <td>s
				if(typeof(searchData.record.prestatus) !== "undefined"){
					if($.isEmptyObject(searchData.record.prestatus) === false){
						$("#PREstatusRow").html("<th>PRE Status<\/th><td>" + searchData.record.prestatus + "<\/td>");
					} else {
						$("#PREstatusRow").html("<th><\/th><td>&nbsp;<\/td>");
					}
				} else {
					$("#PREstatusRow").html("<th><\/th><td>&nbsp;<\/td>");
				}
				
				// populate Tentative Taxable if applicable, otherwise, do nothing because this row is already empty
				if(typeof(searchData.record.Tax_Year0_MBOR) !== "undefined"){
					if($.isEmptyObject(searchData.record.Tax_Year0_MBOR) === false){
						$("#TentativeTaxableRow").html("<th style=\"color: #d43f3a;\">" + yearPlus1 + " Tentative Taxable: <\/th><td>$" + searchData.record.Tax_Year0_MBOR + "<\/td>");
					}
				}
				
				
				
				$("#PRE_Year2_May").html(testForGarbageData(searchData.record.PRE_Year2_May) + "%");
				$("#PRE_Year2_Year_Label").html(yearMinus1);
				$("#PRE_Year2_Final").html(testForGarbageData(searchData.record.PRE_Year2_Final) + "%");
				$("#PRE_Year1_Year_Label").html(testForGarbageData(searchData.record.AssessmentYear));
				$("#PRE_Year1_May").html(testForGarbageData(searchData.record.PRE_Year1_May) + "%");
				
				// populate PRE_Year0_May if applicable, otherwise, do nothing because this row is already empty
				if(typeof(searchData.record.PRE_Year0_May ) !== "undefined"){
					if($.isEmptyObject(searchData.record.PRE_Year0_May ) === false){
						$("#PRE_Year0_MayRow").html("<th>PRE " + yearPlus1 + " Tentative: <\/th><td>" + searchData.record.PRE_Year0_May  + "%<\/td>");
					}
				}
				
				
				$("#Asr_Year2_MBOR").html("$" + testForGarbageData(searchData.record.Asr_Year2_MBOR));
				$("#SEV_Year2_MBOR").html("$" + testForGarbageData(searchData.record.SEV_Year2_MBOR));
				$("#Tax_Year2_MBOR").html("$" + testForGarbageData(searchData.record.Tax_Year2_MBOR));
				$("#Asr_Year3_MBOR").html("$" + testForGarbageData(searchData.record.Asr_Year3_MBOR));
				$("#SEV_Year3_MBOR").html("$" + testForGarbageData(searchData.record.SEV_Year3_MBOR));
				$("#Tax_Year3_MBOR").html("$" + testForGarbageData(searchData.record.Tax_Year3_MBOR));

				// populate PRE Date if applicable, otherwise, do nothing because this row is already empty
				if(typeof(searchData.record.PRE_Date) !== "undefined"){
					if($.isEmptyObject(searchData.record.PRE_Date) === false){
						$("#PRE_Date_Label").html("PRE Date:");
						$("#PRE_Date").html(searchData.record.PRE_Date);
					}
				}
				if(typeof(searchData.record.Rescinddate) !== "undefined"){
					if($.isEmptyObject(searchData.record.Rescinddate) === false){
						$("#Rescinddate_Label").html("Rescind Date:");
						$("#Rescinddate").html(searchData.record.Rescinddate);
					}
				}
				if(typeof(searchData.record.TaxStatus) !== "undefined"){
					if($.isEmptyObject(searchData.record.TaxStatus) === false){
						$("#lrpTaxStatusRow, #taxStatusFiller").show();
						$("#lrpTaxStatus").html(searchData.record.TaxStatus);
					} else {
						$("#lrpTaxStatusRow, #taxStatusFiller").hide();
					}
				} else {
					$("#lrpTaxStatusRow, #taxStatusFiller").hide();
				}
				
				
				// populate Exemption Percent here if applicable, otherwise, show empty <td>s
				if(typeof(searchData.record.TRS) !== "undefined"){
					if($.isEmptyObject(searchData.record.TRS) === false){
						$("#lrpTRSRow").html("<th>Town/Range/Sec<\/th><td>" + searchData.record.TRS + "<\/td>");
						$("#lrpTRSRow").removeClass("hidden");
					} else {
						$("#lrpTRSRow").html("<th><\/th><td>&nbsp;<\/td>");
						$("#lrpTRSRow").addClass("hidden");
					}
				} else {
					$("#lrpTRSRow").html("<th><\/th><td>&nbsp;<\/td>");
					$("#lrpTRSRow").removeClass("hidden");
				}

				// Comments
				var commentRowsHTML = "";
				if(typeof(searchData.record.comments) !== "undefined"){
					var commentResultCount = parseInt(searchData.record.comments.resultcount);
					if(commentResultCount === 0){
						commentRowsHTML = "<p>No Comments</p>";
						$("#commentsRow").addClass("hidden");
					} else if (commentResultCount === 1){
						$("#commentsRow").removeClass("hidden");
						commentRowsHTML += "<p>" + testForGarbageData(searchData.record.comments.record.comment) + "</p>";
					} else {
						$("#commentsRow").removeClass("hidden");
						for(var i = 0; i < commentResultCount; i++){
							commentRowsHTML += "<p>" + testForGarbageData(searchData.record.comments.record[i].comment) + "</p>";
						}
					}
					$("#lrpComments").html(commentRowsHTML);
				}

				// Land info
				$("#SummaryTotalAcreage").html(testForGarbageData(searchData.record.SummaryTotalAcreage));
				$("#Zoning").html(testForGarbageData(searchData.record.zoning));

				// extra land info object in next code block
				/*$("#LandValue").html(searchData.record.LandValue);
				$("#LandImprovements").html(searchData.record.LandImprovements);
				$("#RenaissanceZone").html(searchData.record.RenaissanceZone);
				$("#Frontage").html(searchData.record.Frontage);
				$("#Depth").html(searchData.record.Depth);
				$("#MortgageCode").html(searchData.record.MortgageCode);
				$("#LotDimensionsComments").html(searchData.record.LotDimensionsComments);*/
			
	if(typeof(searchData.record.landi) !== "undefined"){
		if($.isEmptyObject(searchData.record.landi) === false){
			$("#lrpFrontage").html(searchData.record.landi.Frontage);
			$("#lrpDepth").html(searchData.record.landi.Depth);
			$("#lrpLandValue").html("$" + searchData.record.landi.LandValue);
			$("#lrpPoolType").html(searchData.record.landi.PoolType);
			$("#lrpPoolArea").html(searchData.record.landi.PoolArea);
			$("#lrpPoolYearBuilt").html(searchData.record.landi.PoolYearBuilt);
			if($.isEmptyObject(searchData.record.landi.LandImpValue) === false){
				$("#lrpLandiValue").html("$" + searchData.record.landi.LandImpValue);
				$('#lrpLandiValueRow').removeClass('hidden');
			}else{
				$('#lrpLandiValueRow').addClass('hidden');
			}

			$('#additionalLandInfo').removeClass('hidden');
		} else {
			$('#additionalLandInfo').addClass('hidden');
		}
	} else {
		$('#additionalLandInfo').addClass('hidden');
	}
	

	// Legal info
	$('#LegalDescription').html(testForGarbageData(searchData.record.LegalDescription));
	// Sales info
	var salesInfoStr = "";
	if(searchData.record.salesrecords !== undefined){
		for(var i = 0; i < searchData.record.salesrecords.resultcount; i++){
			salesInfoStr += '<h3 id="saleDate' + i + '" class="salesDataSec">Sale Date: ' + searchData.record.salesrecords.record[i].SaleDate + '</h3>' +
							'<div><p id="saleData' + i + '"><span>Sale Price: </span>' + searchData.record.salesrecords.record[i].SalePrice + '<br>' +
							'<span>Instrument: </span>' + searchData.record.salesrecords.record[i].InstrumentListNum + '<br>' +
							'<span>Grantor: </span>' + searchData.record.salesrecords.record[i].Grantor + '<br>' +
							'<span>Grantee: </span>' + searchData.record.salesrecords.record[i].Grantee + '<br>' +
							'<span>Terms of Sale: </span>' + searchData.record.salesrecords.record[i].TermsofSaleList + '<br>' +
							'<span>Liber/Page: </span>' + searchData.record.salesrecords.record[i].LiberPage + '';
							if(currentMap == 'hillsdale'){
								if(searchData.record.salesrecords.record[i].LiberPage && Object.keys(searchData.record.salesrecords.record[i].LiberPage).length > 0){
									let expanded = searchData.record.salesrecords.record[i].LiberPage.split('/');
									if(expanded.length == 2){
										if(expanded[1].length == 3){
											expanded[1] = '0'+ expanded[1];
										} else if(expanded[1].length == 2){
											expanded[1] = '00'+ expanded[1];
										} else if(expanded[1].length == 1){
											expanded[1] = '000'+ expanded[1];
										}
										if(expanded[0].length == 3){
											expanded[0] = '0'+ expanded[0];
										} else if(expanded[0].length == 2){
											expanded[0] = '00'+ expanded[0];
										} else if(expanded[0].length == 1){
											expanded[0] = '000'+ expanded[0];
										}
										let code = expanded.join('|');
										salesInfoStr += '<br><a href="https://countyfusion1.kofiletech.us/countyweb/imageView.do?countyname=Hillsdale&data=LIBR|' + code + '" target="_blank">Register of Deeds Site</a>'
									} else { //UNRECORDED etc.
										salesInfoStr += '<br><a href="https://hillsdale.mi.publicsearch.us" target="_blank">Register of Deeds Site</a>'
									}
								} else {
									salesInfoStr += '<br><a href="https://hillsdale.mi.publicsearch.us" target="_blank">Register of Deeds Site</a>'
								}
							}
							salesInfoStr += '</p></div>';
		}
		if(salesInfoStr == ""){
			$('#salesInfo').html("No Records Found");
		} else {
			salesInfoStr = salesInfoStr.replace(/\[object Object\]/g, "");
			$('#salesInfo').html(salesInfoStr);
		}
		if($( "#salesInfo" ).hasClass('ui-accordion')){
			$( "#salesInfo" ).accordion( "refresh" );
		}
		$('.salesInfoRow').removeClass('hidden');
	} else {
		$('.salesInfoRow').addClass('hidden');
	}

	//****************************************************************************************************************
	// Building info
	var buildingInfoHTML = "";
	if(searchData.record.BuildingInfo !== undefined){
		//console.log(searchData.record.BuildingInfo);
		if($.isEmptyObject(searchData.record.BuildingInfo) === false){
			// handle residential buildings. One building is an object. More than one is an array.
			if(typeof(searchData.record.BuildingInfo.ResBuilding) === "object"){ // we have residential building info
				if($.isEmptyObject(searchData.record.BuildingInfo.ResBuilding) === false){
					if(typeof(searchData.record.BuildingInfo.ResBuilding.length) === "undefined"){ // just one result. Just shove it into an array. Next if statement will handle it.
						searchData.record.BuildingInfo.ResBuilding = [searchData.record.BuildingInfo.ResBuilding];
					}

					if(typeof(searchData.record.BuildingInfo.ResBuilding.length) === "number"){
						buildingInfoHTML += '<p class="buildingInfoSectionHeader">Residential Buildings</p>';
						for(var i = 0; i < searchData.record.BuildingInfo.ResBuilding.length; i++){
							var currentInfo = searchData.record.BuildingInfo.ResBuilding[i];
							buildingInfoHTML +=	'<p class="buildingInfoHeader">' + currentInfo.Style + ' - ' + currentInfo.YearBuilt + '</p>' +
								'<div>'+
									'<p class="lrpSubTableHeader">General Information</p>'+
									'<div class="col-xs-12 col-sm-6 print-xs-6 lrpLeft">'+
										'<table class="table table-condensed">'+
											'<tr>'+
												'<th style="width: 50%">Year Built:</th>'+
												'<td style="width: 50%">' + currentInfo.YearBuilt + '</td>'+
											'</tr>'+
											'<tr>'+
												'<th>Style:</th>'+
												'<td>' + currentInfo.Style + '</td>'+
											'</tr>'+
											'<tr>'+
												'<th>Exterior:</th>'+
												'<td>' + currentInfo.Exterior +'</td>'+
											'</tr>'+
											'<tr>'+
												'<th>Total Living Area:</th>'+
												'<td>' + currentInfo.TotalLivArea + '</td>'+
											'</tr>'+
											'<tr>'+
												'<th>Heating Type:</th>'+
												'<td>' + currentInfo.Heat + '</td>'+
											'</tr>'+
											'<tr class="d-none d-sm-block">'+
												'<th>&nbsp;</th>'+
												'<td>&nbsp;</td>'+
											'</tr>'+
										'</table>'+
									'</div>'+
									'<div class="col-xs-12 col-sm-6 print-xs-6 lrpRight">'+
										'<table class="table table-condensed">'+
											'<tr>'+
												'<th style="width: 50%">Rooms Basement:</th>'+
												'<td style="width: 50%">' + currentInfo.RoomsB + '</td>'+
											'</tr>'+
											'<tr>'+
												'<th>Rooms 1st Floor:</th>'+
												'<td>' + currentInfo.Rooms1 + '</td>'+
											'</tr>'+
											'<tr>'+
												'<th>Rooms 2nd Floor:</th>'+
												'<td>' + currentInfo.Rooms2 + '</td>'+
											'</tr>'+
											'<tr>'+
												'<th>Bedrooms:</th>'+
												'<td>' + currentInfo.Bedrooms + '</td>'+
											'</tr>'+
											'<tr>'+
												'<th>Baths: Full/Half:</th>'+
												'<td>' + currentInfo.BathFull + '/' + currentInfo.BathHalf + '</td>'+
											'</tr>'+
											'<tr>'+
												'<th>Fireplaces: Quantity - Type</th>'; 		// can be multiple, but can't be in own section										'<td rowspan="2">&nbsp;</td>'+
// *********************************************************** fireplace weirdness **********************************************
							buildingInfoHTML += '<td>';

				if($.isEmptyObject(currentInfo.Fires) === false){
					if(typeof(currentInfo.Fires.Fire.length) === "undefined"){ // just one result. Just shove it into an array. Next if statement will handle it.
						currentInfo.Fires.Fire = [currentInfo.Fires.Fire];
					}

					if(typeof(currentInfo.Fires.Fire.length) === "number"){
						for(var h = 0; h < currentInfo.Fires.Fire.length; h++){
							buildingInfoHTML += '' + currentInfo.Fires.Fire[h].Num + ' - ' + currentInfo.Fires.Fire[h].Type + '<br>';
						}
					}
				} else {
					buildingInfoHTML += '&nbsp;';
				}

							buildingInfoHTML += '</td>';
// ******************************************************************************************************************************
						buildingInfoHTML +=	'</tr>'+
										'</table>'+
									'</div>'+

									'<p class="lrpSubTableHeader">Area Detail</p>'+
									// these need to be a loop
									'<div class="col-xs-12 col-sm-12 print-xs-12">'+
										'<table class="table table-condensed">'+
											'<tr>'+
												'<th style="width: 25%">Height</th>'+
												'<th style="width: 25%">Ground Floor Area</th>'+
												'<th style="width: 25%">Foundation</th>'+
												'<th style="width: 25%">Exterior</th>'+
											'</tr>';

							// check for/show area detail if avail
							if(typeof(currentInfo.AreaDetails) !== "undefined"){
								if($.isEmptyObject(currentInfo.AreaDetails) === false){ // service sometimes returns this as an empty object
									if(typeof(currentInfo.AreaDetails.AreaDetail.length) === "undefined"){ // just one result. Just shove it into an array like if there were two or more. Next if statement will handle it.
										currentInfo.AreaDetails.AreaDetail = [currentInfo.AreaDetails.AreaDetail];
									}
									if(typeof(currentInfo.AreaDetails.AreaDetail.length) === "number"){

										for(var j = 0; j < currentInfo.AreaDetails.AreaDetail.length; j++){
											buildingInfoHTML +=
												'<tr>'+
													'<td>' + currentInfo.AreaDetails.AreaDetail[j].Height + '</td>'+
													'<td>' + currentInfo.AreaDetails.AreaDetail[j].Area + '</td>'+
													'<td>' + currentInfo.AreaDetails.AreaDetail[j].Foundation + '</td>'+
													'<td>' + currentInfo.AreaDetails.AreaDetail[j].Exterior + '</td>'+
												'</tr>';
										}

									}
								} else {
									buildingInfoHTML +=
											'<tr>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
											'</tr>';
								}
							} else {
								buildingInfoHTML +=
											'<tr>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
											'</tr>';
							}
									buildingInfoHTML +=
										'</table>'+
									'</div>'+

									'<p class="lrpSubTableHeader">Basement Finished Areas</p>'+
									'<div class="col-xs-12 col-sm-12 print-xs-12">'+
										'<table class="table table-condensed">'+
											'<tr>'+
												'<th style="width: 50%">Recreation:</th>'+
												'<td style="width: 50%">' + currentInfo.BaseRecArea + '</td>'+
											'</tr>'+
											'<tr>'+
												'<th>Living Area:</th>'+
												'<td>' + currentInfo.BaseLivArea + '</td>'+
											'</tr>'+
										'</table>'+
									'</div>'+

									'<p class="lrpSubTableHeader">Garage/Carport Information</p>'+
									'<div class="col-xs-12 col-sm-12 print-xs-12">'+
										'<table class="table table-condensed">'+
											'<tr>'+
												'<th style="width: 25%">Area</th>'+
												'<th style="width: 25%">Capacity</th>'+
												'<th style="width: 25%">Exterior</th>'+
												'<th>Type</th>'+
											'</tr>';

							// check for/show garages detail if avail
							if(typeof(currentInfo.Garages) !== "undefined"){
								if($.isEmptyObject(currentInfo.Garages) === false){ // service sometimes returns this as an empty object
									if(typeof(currentInfo.Garages.Garage.length) === "undefined"){ // just one result. Just shove it into an array like if there were two or more. Next if statement will handle it.
										currentInfo.Garages.Garage = [currentInfo.Garages.Garage];
									}
									if(typeof(currentInfo.Garages.Garage.length) === "number"){
										for(var k = 0; k < currentInfo.Garages.Garage.length; k++){
											buildingInfoHTML +=
												'<tr>'+
													'<td>' + currentInfo.Garages.Garage[k].Area + '</td>'+
													'<td>' + currentInfo.Garages.Garage[k].Capacity + '</td>'+
													'<td>' + currentInfo.Garages.Garage[k].Exterior + '</td>'+
													'<td>' + currentInfo.Garages.Garage[k].Type + '</td>'+
												'</tr>';
										}
									}
								} else {
									buildingInfoHTML +=
											'<tr>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
											'</tr>';
								}

							} else {
								buildingInfoHTML +=
											'<tr>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
											'</tr>';
							}
									buildingInfoHTML +=
										'</table>'+
									'</div>'+

									'<p class="lrpSubTableHeader">Porch/Breezeway Information</p>'+
									'<div class="col-xs-12 col-sm-12 print-xs-12">'+
										'<table class="table table-condensed">'+
											'<tr>'+
												//'<th>Type</th>'+
												'<th style="width: 50%">Area</th>'+
												'<th style="width: 50%">Description</th>'+
											'</tr>';
											// check for/show garages detail if avail
							if(typeof(currentInfo.Porches) !== "undefined"){
								if($.isEmptyObject(currentInfo.Porches) === false){ // service sometimes returns this as an empty object
									if(typeof(currentInfo.Porches.Porch.length) === "undefined"){ // just one result. Just shove it into an array like if there were two or more. Next if statement will handle it.
										currentInfo.Porches.Porch = [currentInfo.Porches.Porch];
									}
									if(typeof(currentInfo.Porches.Porch.length) === "number"){
										for(var l = 0; l < currentInfo.Porches.Porch.length; l++){
											buildingInfoHTML +=
												'<tr>'+
													//'<td>' + currentInfo.Porches.Porch[k].Type + '</td>'+ // placeholder for Type
													'<td>' + currentInfo.Porches.Porch[l].Area + '</td>'+
													'<td>' + currentInfo.Porches.Porch[l].Descr + '</td>'+
												'</tr>';
										}
									}
								} else {
									buildingInfoHTML +=
											'<tr>'+
												//'<td> -- </td>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
											'</tr>';
								}
							} else {
								buildingInfoHTML +=
											'<tr>'+
												//'<td> -- </td>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
											'</tr>';
							}
									buildingInfoHTML +=
										'</table>'+
									'</div>'+


									'<p class="lrpSubTableHeader">Deck Information</p>'+
									'<div class="col-xs-12 col-sm-12 print-xs-12">'+
										'<table class="table table-condensed">'+
											'<tr>'+
												//'<th>Type</th>'+
												'<th style="width: 50%">Area</th>'+
												'<th style="width: 50%">Description</th>'+
											'</tr>';
											// check for/show garages detail if avail
							if(typeof(currentInfo.Decks) !== "undefined"){
								if($.isEmptyObject(currentInfo.Decks) === false){ // service sometimes returns this as an empty object
									if(typeof(currentInfo.Decks.Deck.length) === "undefined"){ // just one result. Just shove it into an array like if there were two or more. Next if statement will handle it.
										currentInfo.Decks.Deck = [currentInfo.Decks.Deck];
									}
									if(typeof(currentInfo.Decks.Deck.length) === "number"){
										for(var m = 0; m < currentInfo.Decks.Deck.length; m++){
											buildingInfoHTML +=
												'<tr>'+
													//'<td>' + currentInfo.Porches.Porch[k].Type + '</td>'+ // placeholder for Type
													'<td>' + currentInfo.Decks.Deck[m].Area + '</td>'+
													'<td>' + currentInfo.Decks.Deck[m].Descr + '</td>'+
												'</tr>';
										}
									}
								} else {
									buildingInfoHTML +=
											'<tr>'+
												//'<td> -- </td>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
											'</tr>';
								}

							} else {
								buildingInfoHTML +=
											'<tr>'+
												//'<td> -- </td>'+
												'<td> -- </td>'+
												'<td> -- </td>'+
											'</tr>';
							}

									buildingInfoHTML +=
										'</table>'+
									'</div>'+



								'</div>';

                            buildingInfoHTML = buildingInfoHTML.replace(/\[object Object\]/g, "");

							$('#lrpResBuildingWrapper').html(buildingInfoHTML);

							if($( "#lrpResBuildingWrapper" ).hasClass('ui-accordion')){
								$( "#lrpResBuildingWrapper" ).accordion("option", "active", false);
								$( "#lrpResBuildingWrapper" ).accordion("refresh");
							}
							$('.buildingInfoRow').removeClass('hidden');
						}
					}
				} else {
					$('#lrpResBuildingWrapper').html('<p class="buildingInfoSectionHeader">Residential Buildings</p>No Records Found');
				}
			} else {
				$('#lrpResBuildingWrapper').html('<p class="buildingInfoSectionHeader">Residential Buildings</p>No Records Found');
			}

			let mapName = currentMap;
			if(currentMap == 'sagEH'){ //causing errors, linkeddocs/sagEH is bad
				mapName = 'saginaw'
			}
			// after this, grab the images and append them as another accordion.
			if(searchData.record.RBsketches !== undefined){//if sketches are avail from db instead
				var sketchResultCount = parseInt(searchData.record.RBsketches.resultcount);
				if(sketchResultCount > 0){
					var resImagesHTML = '<p class="buildingInfoHeader">Sketches (' + sketchResultCount + ')</p>';
					resImagesHTML += 	'<div id="resBuildingImagesWrapper">';
					if(sketchResultCount === 1){
													
							
							resImagesHTML += '<img class="lrpResBldgImg" src="ws/showSketch.php?type=0&Map='+currentMap+'&id=' + searchData.record.RBsketches.record.id + '">';							
												
						
					} else {
						for(i=0; i < searchData.record.RBsketches.resultcount; i++){

							resImagesHTML += '<img class="lrpResBldgImg" src="ws/showSketch.php?type=0&Map='+currentMap+'&id=' + searchData.record.RBsketches.record[i].id + '">';
							
						}
					}
					resImagesHTML += 	'</div>';
					$('#lrpResBuildingWrapper').append(resImagesHTML);
					$( "#lrpResBuildingWrapper" ).accordion("refresh");
				}
			}else{
				$.ajax({
				type: 'get',
				async: true,
				url: 'https://app.fetchgis.com/linkedDocs/'+mapName+'/fetchSketches.php?id=' + searchData.record.ParcelNumber + '&type=res',
				success:function(data){
					//console.log(data);
					if(typeof(data) !== "undefined"){
						if(typeof(data.files) !== "undefined"){
							var resImagesHTML = '<p class="buildingInfoHeader">Sketches (' + data.files.length + ')</p>';
							resImagesHTML += 	'<div id="resBuildingImagesWrapper">';
							for(var i = 0; i < data.files.length; i++){
								resImagesHTML += '<img class="lrpResBldgImg" src="https://app.fetchgis.com/linkedDocs/'+currentMap+'/' + encodeURI(data.files[i]) + '">';
							}
							resImagesHTML += 	'</div>';
						}
						$('#lrpResBuildingWrapper').append(resImagesHTML);
						$( "#lrpResBuildingWrapper" ).accordion("refresh");
					}
				},
				timeout: 5000
				});
			}
			
			$('#buildingInfo').removeClass('hidden');

			// Agricultural Buildings
			if(typeof(searchData.record.BuildingInfo.AgBuilding) === "object"){ // we have residential building info
				if($.isEmptyObject(searchData.record.BuildingInfo.AgBuilding) === false){
					if(typeof(searchData.record.BuildingInfo.AgBuilding.length) === "undefined"){ // just one result. Just shove it into an array. Next if statement will handle it.
						searchData.record.BuildingInfo.AgBuilding = [searchData.record.BuildingInfo.AgBuilding];
					}

					if(typeof(searchData.record.BuildingInfo.AgBuilding.length) === "number"){
						var	agBuildingHTML = '<p class="buildingInfoSectionHeader">Agricultural Buildings</p>';
						for(var n = 0; n < searchData.record.BuildingInfo.AgBuilding.length; n++){
							console.log(searchData.record.BuildingInfo.AgBuilding[n]);
							var agBldgAccordianHeading = searchData.record.BuildingInfo.AgBuilding[n].Dimensions + ' ' + searchData.record.BuildingInfo.AgBuilding[n].Occupancy;
							if(agBldgAccordianHeading === "[object Object] [object Object]"){
								agBldgAccordianHeading = 'Unspecified Building';
							}
					agBuildingHTML += 	'<p class="buildingInfoHeader">' + agBldgAccordianHeading + '</p>' +
										'<div>' +
											'<div class="col-xs-12 col-sm-6 print-xs-6 lrpLeft">' +
												'<table class="table table-condensed">' +
													'<tr>' +
														'<th style="width: 50%">Year Built:</th>' +
														'<td style="width: 50%">' + searchData.record.BuildingInfo.AgBuilding[n].YearBuilt + '</td>' +
													'</tr>' +
													'<tr>' +
														'<th>Area (sq ft):</th>' +
														'<td>' + searchData.record.BuildingInfo.AgBuilding[n].AreaSqFt + '</td>' +
													'</tr>' +
													'<tr>' +
														'<th>Heating Type:</th>' +
														'<td>' + searchData.record.BuildingInfo.AgBuilding[n].Heat + '</td>' +
													'</tr>' +
												'</table>' +
											'</div>' +
											'<div class="col-xs-12 col-sm-6 print-xs-6 lrpRight">' +
												'<table class="table table-condensed">' +
													'<tr>' +
														'<th style="width: 50%">Dimensions:</th>' +
														'<td style="width: 50%">' + searchData.record.BuildingInfo.AgBuilding[n].Dimensions + '</td>' +
													'</tr>' +
													'<tr>' +
														'<th>Type:</th>' +
														'<td>' + searchData.record.BuildingInfo.AgBuilding[n].Type + '</td>' +
													'</tr>' +
													'<tr>' +
														'<th>Occupancy:</th>' +
														'<td>' + searchData.record.BuildingInfo.AgBuilding[n].Occupancy + '</td>' +
													'</tr>' +
												'</table>' +
											'</div>' +
										'</div>';
						}

						agBuildingHTML = agBuildingHTML.replace(/\[object Object\]/g, "");

						$('#lrpAgBuildingWrapper').html(agBuildingHTML);

						if($( "#lrpAgBuildingWrapper" ).hasClass('ui-accordion')){
							$( "#lrpAgBuildingWrapper" ).accordion("option", "active", false);
							$( "#lrpAgBuildingWrapper" ).accordion("refresh");
						}
						$('#lrpAgBuildingWrapper').removeClass('hidden');
					}
				} else {
					$('#lrpAgBuildingWrapper').addClass('hidden');
				}
			} else {
				$('#lrpAgBuildingWrapper').addClass('hidden');
			}

			// after this, grab the images and append them as another accordion.
			if(searchData.record.AGsketches !== undefined){//if sketches are avail from db instead
				var sketchResultCount = parseInt(searchData.record.AGsketches.resultcount);
				if(sketchResultCount > 0){
					var agImagesHTML = '<p class="buildingInfoHeader">Sketches (' + sketchResultCount + ')</p>';
					agImagesHTML += 	'<div id="agBuildingImagesWrapper">';
					if(sketchResultCount === 1){
													
							
						agImagesHTML += '<img class="lrpResBldgImg" src="ws/showSketch.php?type=1&Map='+currentMap+'&id=' + searchData.record.AGsketches.record.id + '">';							
												
						
					} else {
						for(i=0; i < searchData.record.AGsketches.resultcount; i++){

							agImagesHTML += '<img class="lrpResBldgImg" src="ws/showSketch.php?type=1&Map='+currentMap+'&id=' + searchData.record.AGsketches.record[i].id + '">';
							
						}
					}
					agImagesHTML += 	'</div>';
					$('#lrpAgBuildingWrapper').append(agImagesHTML);
					$( "#lrpAgBuildingWrapper" ).accordion("refresh");
				}
			}else{
				$.ajax({
					type: 'get',
					async: true,
					url: 'https://app.fetchgis.com/linkedDocs/'+mapName+'/fetchSketches.php?id=' + searchData.record.ParcelNumber + '&type=agb',
					success:function(data){
						//console.log(data);
						if(typeof(data) !== "undefined"){
							if(typeof(data.files) !== "undefined"){
								var agImagesHTML = '<p class="buildingInfoHeader">Sketches (' + data.files.length + ')</p>';
								agImagesHTML += 	'<div id="agBuildingImagesWrapper">';
								for(var i = 0; i < data.files.length; i++){
									agImagesHTML += '<img class="lrpResBldgImg" src="https://app.fetchgis.com/linkedDocs/'+currentMap+'/' + encodeURI(data.files[i]) + '">';
								}
								agImagesHTML += 	'</div>';
							}
							$('#lrpAgBuildingWrapper').append(agImagesHTML);
							$( "#lrpAgBuildingWrapper" ).accordion("refresh");
						}
					},
					timeout: 5000
				});										
			}

			// end of Agricultural Buildings section

//**************************
			// Commercial Buildings
			if(typeof(searchData.record.BuildingInfo.ComBuilding) === "object"){ // we have residential building info
				if($.isEmptyObject(searchData.record.BuildingInfo.ComBuilding) === false){
					if(typeof(searchData.record.BuildingInfo.ComBuilding.length) === "undefined"){ // just one result. Just shove it into an array. Next if statement will handle it.
						searchData.record.BuildingInfo.ComBuilding = [searchData.record.BuildingInfo.ComBuilding];
					}

					if(typeof(searchData.record.BuildingInfo.ComBuilding.length) === "number"){
						var	comBuildingHTML = '<p class="buildingInfoSectionHeader">Commercial Buildings</p>';
						for(var n = 0; n < searchData.record.BuildingInfo.ComBuilding.length; n++){
							console.log(searchData.record.BuildingInfo.ComBuilding[n]);
							var comBldgAccordianHeading = searchData.record.BuildingInfo.ComBuilding[n].Type;
							if($.isEmptyObject(comBldgAccordianHeading) === true){
								comBldgAccordianHeading = 'Unspecified Building';
							}
					comBuildingHTML += 	'<p class="buildingInfoHeader">' + comBldgAccordianHeading + '</p>' +
										'<div>' +
											'<div class="col-xs-12 col-sm-6 print-xs-6 lrpLeft">' +
												'<table class="table table-condensed">' +
													'<tr>' +
														'<th style="width: 50%">Year Built:</th>' +
														'<td style="width: 50%">' + searchData.record.BuildingInfo.ComBuilding[n].YearBuilt + '</td>' +
													'</tr>' +
													'<tr>' +
														'<th>Area (sq ft):</th>' +
														'<td>' + searchData.record.BuildingInfo.ComBuilding[n].Area + '</td>' +
													'</tr>' +
													'<tr>' +
														'<th>Basement Area:</th>' +
														'<td>' + searchData.record.BuildingInfo.ComBuilding[n].BasementArea + '</td>' +
													'</tr>' +
												'</table>' +
											'</div>' +
											'<div class="col-xs-12 col-sm-6 print-xs-6 lrpRight">' +
												'<table class="table table-condensed">' +
													'<tr>' +
														'<th style="width: 50%">Stories:</th>' +
														'<td style="width: 50%">' + searchData.record.BuildingInfo.ComBuilding[n].Stories + '</td>' +
													'</tr>' +
													'<tr>' +
														'<th>Type:</th>' +
														'<td>' + searchData.record.BuildingInfo.ComBuilding[n].Type + '</td>' +
													'</tr>' +
													'<tr>' +
														'<th>&nbsp;</th>' +
														'<td>&nbsp;</td>' +
													'</tr>' +
												'</table>' +
											'</div>' +
										'</div>';
						}

						comBuildingHTML = comBuildingHTML.replace(/\[object Object\]/g, "");

						$('#lrpComBuildingWrapper').html(comBuildingHTML);

						if($( "#lrpComBuildingWrapper" ).hasClass('ui-accordion')){
							$( "#lrpComBuildingWrapper" ).accordion("option", "active", false);
							$( "#lrpComBuildingWrapper" ).accordion("refresh");
						}
						$('#lrpComBuildingWrapper').removeClass('hidden');
					}
				} else {
					$('#lrpComBuildingWrapper').addClass('hidden');
				}
			} else {
				$('#lrpComBuildingWrapper').addClass('hidden');
			}

			// after this, grab the images and append them as another accordion.
			if(searchData.record.CBsketches !== undefined){//if sketches are avail from db instead
				var sketchResultCount = parseInt(searchData.record.CBsketches.resultcount);
				if(sketchResultCount > 0){
					var comImagesHTML = '<p class="buildingInfoHeader">Sketches (' + sketchResultCount + ')</p>';
					comImagesHTML += 	'<div id="comBuildingImagesWrapper">';
					if(sketchResultCount === 1){
													
							
						comImagesHTML += '<img class="lrpResBldgImg" src="ws/showSketch.php?type=2&Map='+currentMap+'&id=' + searchData.record.CBsketches.record.id + '">';							
												
						
					} else {
						for(i=0; i < searchData.record.CBsketches.resultcount; i++){

							comImagesHTML += '<img class="lrpResBldgImg" src="ws/showSketch.php?type=2&Map='+currentMap+'&id=' + searchData.record.CBsketches.record[i].id + '">';
							
						}
					}
					comImagesHTML += 	'</div>';
					$('#lrpComBuildingWrapper').append(comImagesHTML);
					$( "#lrpComBuildingWrapper" ).accordion("refresh");
				}
			}else{
				$.ajax({
					type: 'get',
					async: true,
					url: 'https://app.fetchgis.com/linkedDocs/'+mapName+'/fetchSketches.php?id=' + searchData.record.ParcelNumber + '&type=cib',
					success:function(data){
						//console.log(data);
						if(typeof(data) !== "undefined"){
							if(typeof(data.files) !== "undefined"){
								var comImagesHTML = '<p class="buildingInfoHeader">Sketches (' + data.files.length + ')</p>';
								comImagesHTML += 	'<div id="comBuildingImagesWrapper">';
								for(var i = 0; i < data.files.length; i++){
									comImagesHTML += '<img class="lrpComBldgImg" src="https://app.fetchgis.com/linkedDocs/'+currentMap+'/' + encodeURI(data.files[i]) + '">';
								}
								comImagesHTML += 	'</div>';
							}
							$('#lrpComBuildingWrapper').append(comImagesHTML);
							$( "#lrpComBuildingWrapper" ).accordion("refresh");
						}
					},
					timeout: 5000
				});
			}

			// end of Commercial Buildings section

//**************************
			
		} else {
			$('#lrpResBuildingWrapper').html("No Records Found");
			$('#buildingInfo').removeClass('hidden');
			// hide Ag buildings section
			$('#lrpAgBuildingWrapper, #lrpComBuildingWrapper').addClass('hidden');
		}

	} else {
		$('#lrpResBuildingWrapper').html("No Records Found");
		$('.buildingInfoRow, #lrpAgBuildingWrapper, #lrpComBuildingWrapper').addClass('hidden');
	}

	//****************************************************************************************************************
	// Delinquent Tax info
	var delTaxInfoStr = "";
	if(searchData.record.dtaxrecords !== undefined){
		var dtaxResultCount = parseInt(searchData.record.dtaxrecords.resultcount);
		if(dtaxResultCount > 0){
			if(dtaxResultCount === 1){
				delTaxInfoStr += '<h3 id="delTaxDate' + i + '" class="salesDataSec">Tax Year: ' + searchData.record.dtaxrecords.record.TaxYear + '</h3>' +
									'<div><p id="delTaxData' + i + '"><span>Base Tax: </span>' + searchData.record.dtaxrecords.record.BaseTax + '<br>' +
									'<span>Base Tax Due: </span>' + searchData.record.dtaxrecords.record.BaseTaxDue + '<br>' +
									'<span>Base Tax Paid: </span>' + searchData.record.dtaxrecords.record.BaseTaxPaid + '<br>' +
									'<span>Total Due: </span>' + searchData.record.dtaxrecords.record.TotalDue + '<br>' +
									'<span>Last Paid: </span>' + searchData.record.dtaxrecords.record.LastPaid + '</p></div>';
			} else {
				for(i=0; i < searchData.record.dtaxrecords.resultcount; i++){
					delTaxInfoStr += '<h3 id="delTaxDate' + i + '" class="salesDataSec">Tax Year: ' + searchData.record.dtaxrecords.record[i].TaxYear + '</h3>' +
									'<div><p id="delTaxData' + i + '"><span>Base Tax: </span>' + searchData.record.dtaxrecords.record[i].BaseTax + '<br>' +
									'<span>Base Tax Due: </span>' + searchData.record.dtaxrecords.record[i].BaseTaxDue + '<br>' +
									'<span>Base Tax Paid: </span>' + searchData.record.dtaxrecords.record[i].BaseTaxPaid + '<br>' +
									'<span>Total Due: </span>' + searchData.record.dtaxrecords.record[i].TotalDue + '<br>' +
									'<span>Last Paid: </span>' + searchData.record.dtaxrecords.record[i].LastPaid + '</p></div>';
				}
			}
			if(searchData.record.dtaxrecords.dtaxdate !== undefined){
				$('#delTaxDate').html(searchData.record.dtaxrecords.dtaxdate);
			} else {
				$('#delTaxDate').html('');
			}
			if(delTaxInfoStr == ""){
				$('#delTaxInfo').html("No Records Found");
			} else {
				delTaxInfoStr = delTaxInfoStr.replace(/\[object Object\]/g, "");
				$('#delTaxInfo').html(delTaxInfoStr);
			}
			if($( "#delTaxInfo" ).hasClass('ui-accordion')){
				$( "#delTaxInfo" ).accordion( "refresh" );
			}
		} else {
			$('#delTaxDate').html('');
			$('#delTaxInfo').html("No Records Found");
		}
		$('.delTaxInfoRow').removeClass('hidden');
	} else {
		$('#delTaxDate').html('');
		$('#delTaxInfo').html("No Records Found");
		$('.delTaxInfoRow').addClass('hidden');
	}

	//****************************************************************************************************************
    var taxHistResultCount = parseInt(searchData.record.taxrecords.resultcount);
    if(taxHistResultCount ===1) searchData.record.taxrecords.record[0]=searchData.record.taxrecords.record;

	if(searchData.record.taxrecords.resultcount > 0 && searchData.record.taxrecords.record[0] != undefined){
		$('#taxHistoryContainer').removeClass('hidden');
		// Tax info header
		var taxInfoMainHeaderStr = "";
		var taxInfoStr = new Object();
		taxInfoStr.structure = ""; //Initialize the accordions empty, and add the html after they're built.
		taxInfoStr.header = new Object();
		taxInfoStr.content = new Object();
		
		
					taxInfoMainHeaderStr = 	'<tr>'+
												'<th class="taxCol1 taxColHeader taxCol_5wide">Year, Season</th>'+
												'<th class="taxCol2 taxColHeader taxCol_5wide '+ hiddenXsStr +'">Total Tax &amp; Fees</th>'+
												'<th class="taxCol3 taxColHeader taxCol_5wide '+ hiddenXsStr +'">Total Paid</th>'+
												'<th class="taxCol4 taxColHeader taxCol_5wide '+ hiddenXsStr +'">Last Paid</th>'+
												'<th class="taxCol5 taxColHeader taxCol_5wide '+ hiddenXsStr +'">Total Due *</th>'+
											'</tr>';
						
		$('#taxHistoryMainHeader').html(taxInfoMainHeaderStr);
		// Tax info records
		for(i=0; i < searchData.record.taxrecords.resultcount; i++){
			taxInfoStr.structure += 	'<div id="taxInfoH3_'+i+'" class="taxInfoH3 h3"></div><div id="taxInfoDiv'+i+'" class="taxInfoDiv"> </div>';
			taxInfoStr.header[i] = "";
			
			
						taxInfoStr.header[i] += '<table class="taxTableHeader">' +
													
														'<tr>' +
															'<td class="taxCol1 taxCol_5wide">' + searchData.record.taxrecords.record[i].year + ' ' + searchData.record.taxrecords.record[i].season + '</td>' +
															'<td class="taxCol2 taxCol_5wide"><span class="'+ hiddenXsStr +'">$'+ searchData.record.taxrecords.record[i].total_tax +'</span></td>' +
															'<td class="taxCol3 taxCol_5wide"><span class="'+ hiddenXsStr +'">'+ searchData.record.taxrecords.record[i].amt_paid +'</span></td>' +
															'<td class="taxCol4 taxCol_5wide"><span class="'+ hiddenXsStr +'">'+ searchData.record.taxrecords.record[i].LastPaidDate +'</span></td>' +
															'<td class="taxCol5 taxCol_5wide"><span class="'+ hiddenXsStr +'">'+ searchData.record.taxrecords.record[i].bal_due +'</span></td>' +
														'</tr>' +
													
												'</table>';
								
			taxInfoStr.content[i] = "";	
			var taxItemsStr = "";
			
			
						
						for(j=0; j < searchData.record.taxrecords.record[i].taxitems.taxitem.length; j++){
						taxItemsStr += "<tr>" + // This needs to be a loop done after the rest of data is done...
										"<td>" + searchData.record.taxrecords.record[i].taxitems.taxitem[j].Authority + "</td>" +
										"<td>" + searchData.record.taxrecords.record[i].taxitems.taxitem[j].MillageRate + "</td>" +
										"<td>" + searchData.record.taxrecords.record[i].taxitems.taxitem[j].Amount + "</td>" +
										"<td>" + searchData.record.taxrecords.record[i].taxitems.taxitem[j].BaseAmountPaid + "</td>" +
									"</tr>";
						}
						taxInfoStr.content[i] +=	'<div class="taxDetails container-fluid" style="">' +
														'<div id="taxInfoSec' + i +'" class="row">' +
														'<p class="tableHeadThingy taxItemsTableHeader">Tax Details ' + searchData.record.taxrecords.record[i].year + ' ' + searchData.record.taxrecords.record[i].season + '</p>' +
															'<div class="'+ colXsStr +' col-sm-6 print-xs-6">' +
																'<table class="detailsTable table table-condensed">' +
																'<tr>' +
																	'<th>School Dist. Code:</th>' +
																	'<td>' + searchData.record.taxrecords.record[i].schooldist + '</td>' +
																'</tr>' +
																'<tr>' +
																	'<th>School Dist. Name:</th>' +
																	'<td>' + searchData.record.taxrecords.record[i].schooldesc + '</td>' +
																'</tr>' +
																'<tr>' +
																	'<th>Property Class:</th>' +
																	'<td>' + searchData.record.taxrecords.record[i].prop_class + '</td>' +
																'</tr>' +
																'<tr>' +
																	'<th>Class Name: </th>' +
																	'<td>' + searchData.record.taxrecords.record[i].class_name + '</td>' +
																'</tr>' +
																'<tr><td>&nbsp;</td><td>&nbsp;</td></tr>' +
																'<tr>' +
																	'<th>Last Payment Date: </th>' +
																	'<td>' + searchData.record.taxrecords.record[i].LastPaidDate + '</td>' +
																'</tr>' +
																'<tr><td>&nbsp;</td><td>&nbsp;</td></tr>' +
																'<tr>' +
																	'<th>Base Tax: </th>' +
																	'<td>$' + searchData.record.taxrecords.record[i].base_tax + '</td>' +
																'</tr>' +
																'<tr>' +
																	'<th>Admin Fees: </th>' +
																	'<td>$' + searchData.record.taxrecords.record[i].admin_tax + '</td>' +
																'</tr>' +
																'<tr>' +
																	'<th>Interest Fees: </th>' +
																	'<td>$' + searchData.record.taxrecords.record[i].interest + '</td>' +
																'</tr>' +
																'<tr>' +
																	'<th>Total Tax &amp; Fees: </th>' +
																	'<td>$' + searchData.record.taxrecords.record[i].total_tax + '</td>' +
																'</tr>' +
																'</table>' +
															'</div>' +
															'<div class="'+ colXsStr +' col-sm-6 print-xs-6" >' +
																'<table class="detailsTable table table-condensed">' +
																

																		'<tr>' +
																			'<th>Assessed Value:</th>' +
																			'<td>$' + searchData.record.taxrecords.record[i].assvalue + '</td>' +
																		'</tr>' +
																		'<tr>' +
																			'<th>Taxable Value:</th>' +
																			'<td>$' + searchData.record.taxrecords.record[i].taxable_value + '</td>' +
																		'</tr>' +
																		'<tr>' +
																			'<th>State Equalized Value:</th>' +
																			'<td>$' + searchData.record.taxrecords.record[i].sevvalue + '</td>' +
																		'</tr>' +
																		'<tr>' +
																			'<th>Exemption Percent:</th>' +
																			'<td>' + searchData.record.taxrecords.record[i].homestead + '%</td>' +
																		'</tr>' +

																		'<tr class="'+ hiddenXsStr +'"><td>&nbsp;</td><td>&nbsp;</td></tr>' +
																		'<tr class="'+ hiddenXsStr +'"><td>&nbsp;</td><td>&nbsp;</td></tr>' +
																		'<tr class="'+ hiddenXsStr +'"><td>&nbsp;</td><td>&nbsp;</td></tr>' +
																		'<tr>' +
																			'<th>Base Paid: </th>' +
																			'<td>' + searchData.record.taxrecords.record[i].base_amt_paid + '</td>' +
																		'</tr>' +
																		'<tr>' +
																			'<th>Admin Fees Paid: </th>' +
																			'<td>' + searchData.record.taxrecords.record[i].admin_fee_paid + '</td>' +
																		'</tr>' +
																		'<tr>' +
																			'<th>Interest Fees Paid: </th>' +
																			'<td>' + searchData.record.taxrecords.record[i].interest_paid + '</td>' +
																		'</tr>' +
																		'<tr>' +
																			'<th>Total Paid: </th>' +
																			'<td>' + searchData.record.taxrecords.record[i].amt_paid + '</td>' +
																		'</tr>' +
																
																'</table>' +
															'</div>' +
														'</div>' +
														'<div id="taxItemSec' + i +'" class="row">' +
															'<div class="col-sm-12">' +
																'<p class="tableHeadThingy taxItemsTableHeader">Tax Items ' + searchData.record.taxrecords.record[i].year + ' ' + searchData.record.taxrecords.record[i].season + '</p>' +
																'<table class="taxItemsTable table table-condensed">' +
																
																		'<tr>' + 
																			'<th>Tax Source</th>' +
																			'<th>Millage Rate</th>' +
																			'<th>Tax Amt.</th>' +
																			'<th>Base Amt. Paid</th>' +
																		'</tr>' +
															
																	taxItemsStr + // Tax items string built earlier in the main loop 
																
																'</table>' +
															'</div>' +
														'</div>' +
													'</div>' +
												'</div>';
						taxInfoStr.content[i] = taxInfoStr.content[i].replace(/\[object Object\]/g, "&nbsp;");// Clean the string
						taxInfoStr.content[i] = taxInfoStr.content[i].replace(/\undefined/g, "&nbsp;");// Clean the string
							}
		if(taxInfoStr == "" && $( "#taxInfo" ).hasClass('ui-accordion')){
			$('#taxInfo').html("No Records Found");
			if($( "#taxInfo" ).hasClass('ui-accordion')){
				$( "#taxInfo" ).accordion( "refresh" );
			}
		} else {
			$('#taxInfo').html(taxInfoStr.structure);
			if($( "#taxInfo" ).hasClass('ui-accordion')){
				$( "#taxInfo" ).accordion( "refresh" );
			}
			for(i=0; i < searchData.record.taxrecords.resultcount; i++){
				$( '#taxInfoH3_'+i+'' ).html( taxInfoStr.header[i] );
				$( '#taxInfoDiv'+i+'' ).html( taxInfoStr.content[i] );
			}
		}	
		$('.taxInfoH3').on("click", function(e){
			eTrap = "#"+ e.currentTarget.id;
		});
	} else {
		$('#taxHistoryContainer').addClass('hidden');
	}
	
	// Clean-up of problem fields can go here. 
	$('#OwnerCSZ').html($('#OwnerCSZ').html().replace(/\undefined/g, "&nbsp;"));
	
	if(typeof(par4getParcelData) !== "undefined" && typeof(parIdStr4getParcelData) !== "undefined" && $("#dataPaneHandle span").hasClass("fa-angle-right")){
		if(par4getParcelData !== null && parIdStr4getParcelData !== null){
			addToRecent(par4getParcelData, parIdStr4getParcelData);
		}
	}
	
		
    // scroll to top function
    setTimeout(function(){
		
        $('#generalDetailsScrollPane').scrollTop(0).perfectScrollbar('update');
		$('#generalDetailsScrollPane').stop().animate({opacity:1}, 300, function(){
			// Line up general details rows (make heights of left and right sides match)
			$('#generalInfo_Left tr th').each(function(index){
				$('#generalInfo_Right tr th div').eq(index).css('height', $('#generalInfo_Left tr th').eq(index).height());
			});

		});
		$('#generalDetailsOverlay').stop().fadeOut();
		
    }, 1000);
	$(document).trigger('lrpDone');
}

// Grab disclaimer box code and modals code
function showConfirmLarge(heading, body){
	$('#confirmHeadingLarge').html(heading);
    $('#confirmContentLarge').html(body);
	$('#confirmModalWrapperLarge').fadeIn();
}
function closeConfirmLarge(){
    if (featureEditEnabled){
        $('#confirmModalWrapperLarge').fadeOut(400, function(){
            $('#confirmAgreeLarge').text('Ok');
            $('#confirmCancelLarge').text('Cancel');
        });
    } else {
         $('#confirmModalWrapperLarge').fadeOut();
    }
}

// We keep having to name and rename stuff so making this utility for renaming
function showNamingModal(title, placeholder, callback, lightMode){
	showConfirm(title, '<input placeholder="'+placeholder+'" maxlength="100" style="display: block; margin: auto">', 'blue', lightMode);
	$("#confirmModal input").on('keyup', function(e){
		if(e.which === 13 || e.keycode === 13){
			$('#confirmAgree').trigger('click');
		}
		if($(this).val().trim() != ''){
			$('#confirmAgree').removeClass("disabledButton");
		} else {
			$('#confirmAgree').addClass("disabledButton");
		}
	});
	$('#confirmAgree').addClass("disabledButton").html("Save");
	$("#confirmModal input").trigger("focus");
	$('#confirmAgree').on("click", function(){
		var name = $("#confirmModal input").val().trim();
		if(name != ''){
			callback(name);
		}
		closeConfirm();
	});
}

function betterShowConfirm(title, body, callback){
	showConfirm(title, body);
	$('#confirmAgree').on("click", function(){
		if(callback){
			callback(true);
		}
		closeConfirm();
	});
	$('#confirmCancel').on("click", function(){
		if(callback){
			callback(false);
		}
		closeConfirm();
	});
}

// creates a small message bubble at the top right of the screen, just goes away on it's own.
function showMessage(content, seconds, style, isPermanent){ //if isPermanent, it won't go away and doesn't get close button
	var timeStamp = new Date().getTime();
    if(!seconds) seconds = 4000; //default to 4 seconds if none set
    var classToAdd;
	let postMessage = throttler();
	if(!postMessage) return;
    switch(style){
        case 'info':
            classToAdd = 'info'
            break;
        case 'error':
            classToAdd = 'error'
            break;
        case 'success':
            classToAdd = 'success'
            break;
        default: //show as info if no style is passed
            classToAdd = 'info'
    }
	var messageBoxHTML = `<div ts="${timeStamp}" class="messageBox ${classToAdd}" style="display: none">
		${!isPermanent ? '<div class="messageBoxClose">X</div>' : ''}
		<div class="messageText">
			${content}
		</div>
        <div style='width:0%' class="messageProgressBar messageProgressBar${timeStamp} ${classToAdd}"></div>
	</div>`;
	$('.messageBoxContainer').append(messageBoxHTML); //add this new message
	$('.messageBox[ts=' + timeStamp+']').fadeIn(); //show it
	if(style != 'error' && !isPermanent){ //error will not timeout and disappear
		let thisProg = document.querySelectorAll('.messageProgressBar'+timeStamp); //plain js, little faster

		//sets the width of the progress bar
		let width = 0;
		let widthIncrement = 0;
		var interval = setInterval(function() {
			width ++;
			widthIncrement = Math.round((1000*width) / seconds);
			if (widthIncrement > 100) {
				clearInterval(interval);
			} else {
				thisProg.forEach((ea)=>{
					ea.style.width = widthIncrement + '%';
				});
			}
		}, 10);

		setTimeout(function(){ //go away
			$('.messageBox[ts=' + timeStamp+']').fadeOut(function(){
				$('.messageBox[ts=' + timeStamp+']').fadeOut().remove();
			});
		}, seconds+100);
	} 
	if(!isPermanent){
		//add click to make message go away faster
		$('.messageBox[ts=' + timeStamp+']').on('click', function(){
			$('.messageBox[ts=' + timeStamp+']').fadeOut().remove();
		})
	}
	function throttler(){
		let cont = true;
		document.querySelectorAll('.messageBox').forEach(function(ea,i){
			let text = ea.querySelector('.messageText').innerHTML;
			if(text.trim() == content){
				if(parseInt(ea.id) + 2000 > timeStamp){//throttle for time
					cont = false;
				} else if(style == 'error'){ //already showing this error
					cont = false;
				}
			}
		})
		return cont;
	}
}

function showDialog(element){
	if(!element.querySelector('.messageBoxContainer')){
		let messageContainer = document.createElement('div');
		messageContainer.classList.add('messageBoxContainer');
		element.append(messageContainer);
	}
	element.showModal();
}

function showAlert(heading, body){
	$('#alertHeading').html(heading);
    $('#alertContent').html(body);
	$('#alertModalWrapper').fadeIn();
	try{
		$('#alertModal p').perfectScrollbar('update');
	} catch(err){
		console.log(err.message);
	}
}
function closeAlert(){
	$('#alertModalWrapper').fadeOut();
}

function showConfirm(heading, body, headingColor){
	let dialog = document.createElement('dialog');
	dialog.id = 'confirmModal';
	dialog.innerHTML = `
		<div id="confirmHeading" style="color:#5286AE; border-bottom: 1px solid #5286AE">
			${heading ? heading: 'Success!'}
		</div>
		<div id="confirmContent">
			${body}
		</div>
		<div style="text-align:center">
			<button id="confirmCancel" class="btn btn-light btn-sm" type="button" onclick="closeConfirm();">Cancel</button>
			<button id="confirmAgree" autofocus class="btn btn-primary btn-sm" type="button">Okay</button>
		</div>
	`;
	document.body.append(dialog);
	let dialogElement = document.querySelector('#confirmModal');
	showDialog(dialogElement);
	$(dialogElement).on('close', function(){
		dialogElement.remove();
	})
}
function closeConfirm(callback){
	document.querySelector('#confirmModal').close();
	if(callback) callback();
}

function showModal(heading, body){
	$('#umHeading').html(heading);
    $('#umContent').html(body);
	$('#universalModalWrapper').fadeIn();
}
function closeModal(){
	$('#universalModalWrapper').fadeOut();
}

var updateCloseMessageBoxHandler = function(){
	$('.closeMessageBox').on("click", function(e){
		$('#' + e.target.parentElement.id).fadeOut(function(){
			$('#' + e.target.parentElement.id).remove();
		});
	});
};

function setDiscalimerText(){
	// pull discalimer text file for current map county 
	$.ajax({
		url: "disclaimers/" + currentMap + ".txt?v=2.0",
		async: true,
		success: function(data){
			$('#disclaimerText').prepend(data);
		}
	});
}
function setDiscalimerBanner(){
	// use ajax to pull counties crest
	// set as global object so it can be put in a few different places?
	var currentCounty;
	if(currentMap == 'soo'){ // for clients whose currentmap variable names are not exactly print worthy
		currentCounty = 'Sault Ste Marie';
	} else if (currentMap == 'stjo'){
		currentCounty = 'St. Joseph';
	} else if (currentMap == 'stjo'){
		currentCounty = 'St. Joseph';
	} else if (currentMap == 'dowmiops'){
		currentCounty = 'MiOps Remediation';
	} else if (currentMap == 'dowbrine'){
		currentCounty = 'Brine Remediation';
	} else if (currentMap == 'accApp') {
		currentCounty = 'Accurate Appraisal';
	} else {
		currentCounty = currentMap.charAt(0).toUpperCase() + currentMap.slice(1);
	}
	var disclaimerTitleHTML = ''; // this disclaimer title shiz is temporary, not sure if were going to need a disclaimer or not for dow - if so maybe we want this kind of thing config driven
	if(currentMap == 'acs'){
		disclaimerTitleHTML = '<td>Water Chemistry in the Great Lakes Region </td>';
	} else if(currentMap.slice(0,3) == 'dow' || currentMap == 'accApp' || currentMap == 'sagRC'){
		disclaimerTitleHTML = '<td>Welcome to the <br>Bay Area GIS Map </td>';
	}  else {
		disclaimerTitleHTML = '<td>Welcome to the <br>Bay Area GIS Map </td>';
	}
	$('#disclaimerBanner').html('' +
		'<table>'+
			'<tr>'+
				'<td><img src="./img/baySeal.png" /></td>'+
				//'<td>Welcome to the <br>' + currentCounty + ' County Map Viewer</td>'+
				disclaimerTitleHTML +
			'</tr>'+
		'</table>'	
	);
}
function setDiscalimerUpdates(){
	//if there are updates, display them
	//set new updates with a timestamp, and removed if older then some time
	var newUpdates = 'New updates can be displayed here!';
    var postData = {
		map: currentMap
	};
    var dataError=false;
	$.ajax({
		//url: "disclaimers/" + currentMap + "Updates.txt",
        url: "ws/sso/getMapUpdates.php",
        method: "POST",
        data: postData,
		success: function(data){
            try{
                if (data !== ''){
                    if(data.status != 'fail' && data.map == currentMap){
                        if(data.status == 'success')$('#disclaimerUpdates').html(data.message);
                    }else{
                        dataError=true;
                    }
                } else {
                    dataError=true;
                }
            } catch(err){
                console.log('Error, no data returned from getMapUpdates: ' + err.message);
                dataError=true;
            }
		},
		complete: function(){
			if(dataError)$('#disclaimerText')[0].style.setProperty('height', '345px', 'important');
		}
	});
}


// #################################################
// ####			    Set theme stuff       		####
// #################################################


function setThemesText(){
	// pull discalimer text file for current map county 
	$.ajax({
		url: "disclaimers/" + currentMap + "Themes.txt", // the addition of the word "Themes" makes the themes picker show up instead of a disclaimer
		async: true,
		success: function(data){
			$('#disclaimerModal').addClass('withThemeSwitcher');
			$('#themesText').prepend(data);
			$('.themeButton[value='+theme+']').addClass('currentTheme');
			/*if(themeTitle) // there isn't room for this on some displays
				$('.themesHeading').html('Current Theme: '+themeTitle);*/
			$('#disclaimerThemesSelector').show();

			$('.themeDialogText, .themeButton').on("click", function(){
				var newTheme = $(this).attr('value');
				if(theme != newTheme){
				  theme = newTheme;
				  if($(this).hasClass("themeDialogText")) dOff = 1;
				  activeLayers = undefined;
				  partialLayerGroups = undefined;
				  printLegendLayers = undefined;
				  updateURLParams();
				  location.reload();
				} else {
				  var friendlyThemeName = $(this)[0].innerHTML;
				  showAlert('Notice', friendlyThemeName + ' is the currently selected theme.');
				}
			 });
		}
	});
}

if(theme != ''){
	$('.themeDialogText[value="'+theme+'"]').addClass('currentTheme');
	setThemesText();
}


function setSupportedDevices(){
	if (userAgent == 'other'){
		$("#supportedDeviceLink").hide();
	}
}
function setDisclaimerScrollbar(){
	// Get the height of the upper banner and accept button container, then give what's left to the content scroll div. The -20 is for padding.
	var overallHeight = $("#disclaimerModal").height();
	var bannerHeight = $("#disclaimerBanner").height();
	var themesHeight = 0;
	if(theme != ''){
		themesHeight = $('#disclaimerThemesSelector').height();
	}
	var acceptHeight = 50;
	
	if(isPhone === true){
		$('#disclaimerScroll').prepend($('#siteUpdatesHeading'));
		$('#siteUpdatesHeading').after($('#disclaimerUpdates'));
		$('#disclaimerUpdates').after($('#appUseDisclaimerHeading'));
		$('#siteUpdatesScroll').remove();
		
		var availHeight = overallHeight - bannerHeight - acceptHeight - themesHeight;
		$('#disclaimerScroll').css('height', availHeight);
		
	} else {
		var siteUpdatesHeight = $("#siteUpdatesScroll").height();
		
		var availHeight = overallHeight - bannerHeight - acceptHeight - siteUpdatesHeight - themesHeight;
		$('#disclaimerScroll').css('height', availHeight - 70);
	}	
	
	$("#disclaimerScroll, #siteUpdatesScroll").show();
	$("#disclaimerScroll, #siteUpdatesScroll").perfectScrollbar();
	$("#disclaimerScroll, #siteUpdatesScroll").perfectScrollbar('update');
}

// this is the functionality for a basic login
// acts similar to eh except that it merely clears the modal from the screen - not an incredibly secure method
// as the user could just delete the modal but for just detering basic users its fine and dandy



$('#share').on("click", function(){
	getShareLink();
});

var getShareLink = function getShareLink(){
	$('#share').addClass('disabledButton');
	$.ajax({
		type: "POST",
		url: 'https://link.fetchgis.com/add.php',
		data: {"url": window.location.href + "&data64=" + data64},
		crossDomain: true,
		success: function(data){
			if(typeof(data.code) !== "undefined"){
				var embedHTML = createEmbedHTML('http://link.fetchgis.com/' + data.code);
				showModal('Here is your share link', '<input type="text" id="shareLinkBox" value="http://link.fetchgis.com/' + data.code + '">' + embedHTML);
				$('#shareLinkBox').trigger("focus").select();
			} else {
				showAlert('Notice', 'Share-link creation was not successful. Please try again.');
			}
		},
		error: function(){
			showAlert('Notice', 'Share-link creation was not successful. Please try again.');
		},
		complete: function(){
			$('#share').removeClass('disabledButton');
		}
	});
}


function createIframeHTML(url, width, height){
	return '<iframe src=\"'+url+'\" style=\"height:'+height+'px;width:'+width+'px;\"></iframe>';
}

function createEmbedHTML(url){
	var embedHTMLStr = "<br><br><label>Embed Map:</label><div class='embedDiv' data-url='"+url+"' data-height='500' data-width='500'>" +
							"<label>Width (px):</label>&nbsp;&nbsp;<input class='embedWidth' type='number' value='500'></input><br>" +
							"<label>Height (px):</label>&nbsp;<input class='embedHeight' type='number' value='500'></input><br>" +
							"<textarea class='embedHTML' rows='4' cols='30'>" + createIframeHTML(url, 500, 500)+ "</textarea>" +
						"</div>";
	return "";//disabled right now
	return embedHTMLStr;
}

// Grab script to run the form Viewer, will check for pfe in script

function showDataPane(elementToShow){
	if($('#detailsParcelNo').html() != "No Parcel Selected" && $('#detailsParcelNo').html() != '' && $('#detailsParcelNo').html() != ' '){ // this should ensure proper additions to the recently viewed parcel section. 
		// add to recent parcels if there's info present to do it.
		
		// if pin was passed in from another function
		/*if(typeof(pinFromFunctionCall) !== "undefined" && typeof(county) !== "undefined"){
			if(pinFromFunctionCall !== "" && county !== ""){
				addToRecent(pinFromFunctionCall, county); // PIN is passed in from another function that supplies it
			}
		} else*/ if(typeof(par4getParcelData) !== "undefined" && typeof(parIdStr4getParcelData) !== "undefined"){ //Sometimes showDataPane is called simply by clicking the "<" handle on the white pane
			if(par4getParcelData !== "" && parIdStr4getParcelData !== "" && $('#lrpFeeOverlay').is(':visible') === false){
				addToRecent(par4getParcelData, parIdStr4getParcelData); // PIN is from previous result
			}
		}
	}
	if(isMobile == false){
		$('#showDetails').html('Hide Details');
	}
	
	hideControlPane();
	$('#controlPaneHandle').fadeOut();
	$('.dataScrollPane').addClass('hidden');
	$('#generalDetailsScrollPane').removeClass('hidden');
	
	if(isMobile == false){
		// change info window details option
		
		$("#dataPaneHandle, #dataPaneExpando").addClass("active");
		setTimeout(function(){
			$('#dataPaneHandle > span').removeClass('fa-angle-left').addClass('fa-angle-right');
			$("#dataPaneHandle").css('padding-left', ' 7px');
			$('#generalInfo_Left tr th').each(function(index){
				$('#generalInfo_Right tr th div').eq(index).css('height', $('#generalInfo_Left tr th').eq(index).height());
			});
			// if(callback) callback();
		}, 500);
		
		dataPaneExpandoOpen = true;
	} else {
		$("#modalOverlay, #mobileDataPane").fadeIn(400, function(){
			if(isPhone === false && $('#detailsParcelNo').html() != "No Parcel Selected" && $('#detailsParcelNo').html() != '' && $('#detailsParcelNo').html() != ' '){
				$('#generalInfo_Left tr th').each(function(index){
					$('#generalInfo_Right tr th div').eq(index).css('height', $('#generalInfo_Left tr th').eq(index).height());
				});
			}
		});
		$("#generalDetailsScrollPane").css("height", "100%");
	}
	if(elementToShow && $(elementToShow).hasClass('dataPaneWrapper')){ //is element a dataPaneWrapper?
		$('.dataPaneWrapper').hide();
		$(elementToShow).show();
		$('.activePane').removeClass('activePane');
		$(elementToShow).addClass('activePane');
	}
	if(document.querySelector('.activePane') && document.querySelector('.activePane').id == 'pfe-rendered'){ //if form is currently showing
		$(".actionList .pfe-open-form").text("Hide Form");
	}
	if(pfeEnabled || insightsPro){
		formViewer.checkFormButtons();
	}
	
}

function hideDataPane(){
	// change info window details option 
	$('#showDetails').html('Show Details');
	if(document.querySelector('.activePane') && document.querySelector('.activePane').id == 'pfe-rendered'){ //if form is showing
		$(".actionList .pfe-open-form").text("Show Form");
	}

	$("#dataPaneHandle, #dataPaneExpando").removeClass("active");
	setTimeout(function(){
		$('#dataPaneHandle > span').removeClass('fa-angle-right').addClass('fa-angle-left');
		$("#dataPaneHandle").css('padding-left', '0px');
	}, 500);
	
	dataPaneExpandoOpen = false;

	if(isMobile){
		$("#modalOverlay, #mobileDataPane").fadeOut();
	}

	$('#controlPaneHandle').fadeIn();

	if(pfeEnabled || insightsPro){
		if(pfeEnabled){
			$(".pfe-open-form").text("Show Form");
		}
		if(typeof(formViewer) == 'object'){
			formViewer.checkFormButtons();
		}
		// if($('#pfe-rendered').length > 0 && $('#pfe-rendered').css('display') != 'none'){ //if form data is shown on white pane  ----- 2/27/23 breaking dow maps
		// 	activeControl = 'featureEditControls';
		// }
	}
}

$("#closeMobileDataPane").on("click", function(){
	$("#modalOverlay, #mobileDataPane").fadeOut();
	$('#controlPaneHandle').fadeIn();
	$(".pfe-open-form").text("Show Form");
	if($('#formEdit').attr('title') != 'Edit'){ //if currently in edit mode turn off
		$('#formEdit').trigger("click");
	}
});

document.querySelectorAll('.formControlButtonGeneric').forEach(function(ea,i){
    ea.addEventListener('click', function(ea){
		let activePane = document.querySelector('.activePane');
		let mode = ea.target.getAttribute('mode');
		if(!activePane || !mode) return; //cant find active pane or missing the purpose of click
		activePane.dispatchEvent(new CustomEvent(mode,{currentTarget:ea.currentTarget}));
	});
})

window.makeDataTable = function (element,featuresData, layerOverride, optionsOverride = {}){
    // console.time('tables')
    var isInsightsReportTable = $(element).hasClass('insights-report-table');
    let layer = $(element).attr('layerid'); //used to determine what layer to look for in savedFields			
    if(layerOverride){
        layer = layerOverride;
    }
    let numOfFields = optionsOverride.numOfFields ? optionsOverride.numOfFields : 6;
    let insightsLayer = $(element).attr('insightslayerid'); //determines if pg is enable, show popup or go to feature
    let dataTablesLayer = layer;
    if(isInsightsReportTable && window[insightsLayer].insights_options.label){ //if insights table
        dataTablesLayer = window[insightsLayer].id;
    }
    if(optionsOverride.localStorageTag){ //if not stored as the layerName. example: feeChooser, so it will not match report fee layout
        dataTablesLayer = optionsOverride.localStorageTag;
    }
    var li = getLayerInfos(layer);
    let options = {
        dom: 'lBfrtip',
        scrollX: true,
        pagingType: 'simple',
        lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]],
        language: {
            infoFiltered: "(of _MAX_ records)",
            searchPlaceholder: "Search",
            lengthMenu: "_MENU_ entries",
        },
        buttons: [ 
            {extend: 'colvis', text: 'Columns', className: 'btn-primary'},
            {extend: 'collection', text: "Download", className: 'btn-primary', buttons:[
                {extend: 'csv', text: 'Visible Columns',filename: $('#insights-query-tabs .nav-link.active').text(), className: 'btn-primary', exportOptions:{
                    columns: ':visible'
                }},
                {extend: 'csv', text: 'All Columns',filename: $('#insights-query-tabs .nav-link.active').text(), className: 'btn-primary'},
            ]},
        ],
        colReorder: true,
        deferRender: true,
        oLanguage: {'sSearch':''}, //remove search label
    };
    if(!isSFV){
        options.buttons.push(
            {
                text: 'Reset Layout',
                className: 'btn-primary',
                action: function ( e, dt, node, config ) {
                    let getStorage = getJSONFromLocalStorage('map-tableSettings3');
                    if(getStorage && getStorage[dataTablesLayer]){ //if nothing has changed, nothing will happen
                        delete getStorage[dataTablesLayer]; //remove settings
                        localStorage.setItem( 'map-tableSettings3', JSON.stringify(getStorage) ) //update localstorage
                        if(reportManager){
                            reportManager.updateTableLayouts(); //reset cache in report manager
                        }
                        $(element).DataTable().destroy(); //remove table
                        $(element).empty();
                        makeDataTable(element,featuresData); //make table over, as the columns get organized at creation
                    }
                }
            }
        )
    }
    if(isInsightsReportTable){
        let heightRemoveVal = 300;
        if(document.body.offsetWidth < 577) heightRemoveVal = 360;
        var newHeight = $('#generalDetailsScrollPane').height() - heightRemoveVal; //height of table to fit in accordion
        options.scrollY = newHeight;
        if($(element).attr('data-layer') != undefined){ //these are linked data tables
            options.data = featuresData.linkedData[$(element).attr('data-layer')]
            options['createdRow'] = function ( row, data, index ) { //we need our listeners for zooming to children feature
                $(row).attr('data-layerId', layer);
                $(row).attr('data-oid', data[window[layer].objectIdField]);
                $(row).addClass('findParent');
            };
        } else { //parent table
            options.data = featuresData.result;
            options['createdRow'] = function ( row, data, index ) { //we need our listeners for zooming to feature
                if(window[insightsLayer].insights_options && window[insightsLayer].insights_options.usePG){ //new layers
                    $(row).attr('data-oid', data[window[layer].objectIdField]);
                    $(row).attr('data-layerid', layer);
                    $(row).addClass('findParent');
                } else { //old layers, not pg
                    $(row).attr('data-queryId', data.queryid);
                }
            };
        }
    } else {
        var tempData = [];
        featuresData.forEach(function(fd){
            tempData.push(fd.attributes);
        })
        options.data = tempData;
        options.stateSave = false;
        delete options.stateSaveCallback;
    }

    if(options.data ){//&& options.data.length > 0){ //don't try to build table if no data
        if(li){
            var cols = []; //holds the columns to display
            let fromStorage;
            if(optionsOverride.allowStateSave || Object.keys(optionsOverride).length == 0){
                if(optionsOverride.useLocalStorageWhenBuilding){ //just use local storage object
                    fromStorage = getJSONFromLocalStorage('map-tableSettings3');
                } else if(!isSFV){
                    if(reportManager && (!optionsOverride.dontUseReportSettings)){
                        if(reportManager.activeReport && reportManager.activeReport.tablelayouts){ //does active report have tablelayouts?
                            if(reportManager.activeReport.tablelayouts[dataTablesLayer]){
                                fromStorage = reportManager.activeReport.tablelayouts;
                            } else {
                                let temp = getJSONFromLocalStorage('map-tableSettings3');
                                if(temp[dataTablesLayer]){ //if something saved in 
                                    fromStorage = temp;
                                }
                            }
                        } else {
                            fromStorage = getJSONFromLocalStorage('map-tableSettings3');
                        }
                    } else { //fallback
                        fromStorage = getJSONFromLocalStorage('map-tableSettings3');
                    }
                }
                if(fromStorage && fromStorage[dataTablesLayer]){
                    for(let i =0; i< fromStorage[dataTablesLayer].length; i++){
                        let c = fromStorage[dataTablesLayer][i];
                        processColumn(c.name, li, null, true);
                    };
                }
            }
            //go through the field infos and check for any missing from localStorage, or build it if no localStorage
            let parentLayers = []; //when we loop through the fields, we will split the parent layers so we can run through the field infos for these layers
            for(fi of li.fieldInfos){
                let alreadyAdded = cols.find(function(c){return c.name == fi.fieldName});
                if(!alreadyAdded){
                    let visible = cols.length < numOfFields ? true: false;
                    if(fromStorage && fromStorage[dataTablesLayer]){ //if already loaded visible layers
                        visible = false;
                    }
                    processColumn(fi.fieldName, li, null, visible);
                }
            }
            for(att in options.data[0]){
                let split = att.split('.');
                if(split.length > 1){
                    if(parentLayers.indexOf(split[0]) == -1){
                        parentLayers.push(split[0])
                    }
                }
            }
            for(l of parentLayers){
                let li = getLayerInfos(l);
                if(li){
                    for(fi of li.fieldInfos){
                        let alreadyAdded = cols.find(function(c){return c.name == l+'.'+fi.fieldName});
                        if(!alreadyAdded){
                            processColumn(l+'.'+fi.fieldName, li, null, false);
                        }
                    }
                }
            }

            options.columns = cols;
        }
        $(element).html('');
        if(optionsOverride.overwrite){ //options to be overwritten
            options = Object.assign(options,optionsOverride.overwrite);
        }
        if(optionsOverride.customizations){
            if(optionsOverride.customizations.hideCol){
                for(c of optionsOverride.customizations.hideCol){ //hideCol is the only one setup currently
                    let i = options.columns.findIndex(function(b){return b.name == c});
                    if(i>-1){ //dont show it
                        options.columns[i].visible = false;
                    }
                }
            }
            if(optionsOverride.customizations.onlyColumns){
                let newCols = [];
                for(c of optionsOverride.customizations.onlyColumns){ //hideCol is the only one setup currently
                    let i = options.columns.findIndex(function(b){return b.name == c});
                    if(i>-1){ //dont show it
                        options.columns[i].visible = true;
                        newCols.push(options.columns[i]);
                    }
                }
                options.columns = newCols;
            }
        }
        if(optionsOverride.allowStateSave || Object.keys(optionsOverride).length == 0){ //do we need to allow saving of state. If no passed options or has been specified
            options.stateSave = true;
            options.stateSaveCallback = function(settings,data) {
                let getStorage = getJSONFromLocalStorage('map-tableSettings3');
                if(!getStorage){
                    getStorage = {};
                }
                let fields = [];
                for(field of settings.aoColumns){
                    if(field.bVisible){ //only store visible fields
                        fields.push({name:field.name,title:field.title});
                    }
                }
                getStorage[dataTablesLayer] = fields;
                localStorage.setItem( 'map-tableSettings3', JSON.stringify(getStorage) )
                if(isInsightsReportTable){
                    if(Object.keys(reportManager.activeReport.tablelayouts) == 0){ //intialize layouts upon first load
                        reportManager.activeReport.tablelayouts = Object.assign({},getStorage[dataTablesLayer]);
                    }
                    reportManager.checkReportForUpdate();
                }
            }
        }
        let dt = $(element).DataTable( options );
        //add filter ability to column visibility menu
        $(element).parents('.dataTables_wrapper').find('.buttons-colvis').one('click',function(e){ //perform once on clicking the colvis button
            var visibilityFilterTimer = setInterval(function(){ //keep checking until the menu is created
                if($(e.currentTarget).parent().find('.dropdown-menu')){ //menu created
                    let menu = $(e.currentTarget).parent().find('.dropdown-menu'); //save the menu as a var as we will use it a couple times
                    makeSearchable(menu[0],'alwaysShow colVisFilter'); //make the menu searchable
                    let searchable = menu.prev().detach(); //we need to move the search div into the menu so it doesn't close when clicking into input
                    menu.prepend(searchable); //add it to the top of the menu
                    clearInterval(visibilityFilterTimer); //stop timeout
                    visibilityFilterTimer = null; //destroy
                }
            },50)
        })
        return dt;
    }
    
    // console.timeEnd('tables')
    function processColumn(fieldName, li, isDate, visible){
        let thisLi = li;
        let aDate = isDate ? isDate : false;
        var col = {data: fieldName, name:fieldName, defaultContent: '', visible:visible};
        let fi = thisLi.fieldInfos.find(function(f) {return f.fieldName == fieldName});
        let layerId = thisLi.layerId ? thisLi.layerId : thisLi.featureLayer;
        if(!fi && fieldName){ //try parent layer, could not find field in current layer
            let fieldNameSplit = fieldName.split('.');
            if(fieldNameSplit.length > 1){
                col.data = fieldNameSplit.join('\\.');
                thisLi = getLayerInfos(fieldNameSplit[fieldNameSplit.length-2]);
                if(thisLi) fi = thisLi.fieldInfos.find(function(f) {return f.fieldName == fieldNameSplit[fieldNameSplit.length-1]}) //if this layer is found in the map. Fees are in EH and Food, Food can have parent fields added, which breaks EH
            } else {
                thisLi = getLayerInfos(insightsLayer);
                if(thisLi) fi = thisLi.fieldInfos.find(function(f) {return f.fieldName == fieldName}); //if this layer is found in the map. Fees are in EH and Food, Food can have parent fields added, which breaks EH
            }
        }
        if(fi){ //did we find a fieldInfos
            col.title = fi.label;
            if(layer != layerId){
                col.title = '('+window[layerId].name+') ' + fi.label;
            }
            if(fi.field && fi.field.type == 'esriFieldTypeDate'){
                aDate = true;
            };
            if(fi.field && fi.field.length > 255){
                col.className = 'leftJustify';
            }
            if(fi.field && fi.field.name == 'feesched_globalid'){
                col.render = function(data,type,row){
                    if(!data){
                        return '';
                    } else {
                        return formViewer.makeRenewalFeeText(data);
                    }
                    
                }
            }
            if(aDate){ //date object
                col.render = function(data,type,row){
                    if(!data) return '';
                    if(data && data.date){
                        var date = new Date(data.date.replace(" ", "T")); // added for i.e.
                        var value = (date.getMonth()+1) + "/" + date.getDate() + "/" + date.getFullYear();
                        return value;
                    } else {
                        if(!isInsightsReportTable){
                            var date = new Date(data);
                            var value = (date.getMonth()+1) + "/" + date.getDate() + "/" + date.getFullYear();
                            return value;
                        } else {
                            return data; //fallback return whats there
                        }
                    }
                }
            }
            cols.push(col);
        }
    }
}

function formViewerTimeout(){ //will give a slight delay so getNewFeats wont run, issue was when updating through black pane, would run getNewFeats-bad
	if(typeof(formViewer) == 'object'){
		formViewer.cancelUpdate = true;
		setTimeout(function(){
			formViewer.cancelUpdate = false;
		},200)
	}
}

function getJSONFromLocalStorage(key){
	let fromStorage;
	try {
		fromStorage = localStorage.getItem(key);
		fromStorage = JSON.parse(fromStorage); //can we parse data from localstorage?
		if(fromStorage == null){ //is it not an object?
			fromStorage = {}; //reset
		}
	} catch { //could not parse
		fromStorage = {}; //reset
	}
	return fromStorage;
}

if(typeof ($.fn.dataTable) == 'function'){
        $.fn.dataTable.moment('MMMM D, YYYY'); //date format to be used in sorting dataTables
    }

// #################################################
// ####			jQuery document ready! 			####
// #################################################
// jQuery stuff to load after the page is loaded. Mainly this loads event handlers.
$(document).ready(function(){

	   setTimeout(function(){
        updateTOCvisibility();
    }, 2000);
  
	$( "#overviewMapToggle" ).on("click", function(){
		if($( "#overviewMapContainer" ).height() > 25){
			$( "#overviewMapContainer" ).stop().animate({height: '24px'}, 750, function(){
				minimapLayer.setVisibility(false);
				localStorage["overviewMapState"] = "hidden";
				$("#overviewMapToggle").removeClass("fa-chevron-circle-up").addClass("fa-chevron-circle-down");
			});
		} else {
			minimapLayer.setVisibility(true);
			$( "#overviewMapContainer" ).stop().animate({height: '200px'}, 750);
			localStorage["overviewMapState"] = "visible";
			$("#overviewMapToggle").removeClass("fa-chevron-circle-down").addClass("fa-chevron-circle-up");
		}
	});
    
	$( "#overviewMapContainer" ).draggable({ handle: "#overviewMapHandle", containment: $("#map") });
	$( "#measurementContainer" ).draggable({ handle: "#measurementTitlebar", containment: $("#map") });
	$( "#textEditorContainer" ).draggable({ handle: "#editTextMoveHandle", containment: $("#map") });
	$( "#insightsQuickAccessToolbar" ).draggable({ handle: "#insightsQuickAccessHandle", containment: $("#map") });
	//$( "#navToolbar" ).draggable({ handle: "#navToolbarHandle" }); // moved this up into dojo loader
	var controlPaneWidth;
	if(viewportWidth < 768){
		controlPaneWidth = 264;
	} else {
		controlPaneWidth = 364;
	}
    
	$( "#controlPaneHandle" ).on("click", function(){
		if($(this).hasClass("active")){
			hideControlPane();
		} else {
			showControlPane();
		}
	});
	
	$( "#dataPaneHandle" ).on("click", function(){
		if($(this).hasClass("active")){
			hideDataPane();
		} else {
			showDataPane();
		}
	});
     
     // extra controls handling
     $('#extraControlsButton, #extraControlsButtonAlt').on("click", function(){
          activateControl('#extraControls');
     });
	$('#extraControlsButtonAlt').on("click", function(){
		$('.navbar-toggle').trigger('click');
	});
	$('#extraControlsButton').on("mouseenter", function() {
		if(userAgent == "other"){
			$('#extraControlsButton').addClass('simHover');
		}
	});
	$('#extraControlsButton').on("mouseleave", function() {
		if(userAgent == "other"){
			$('#extraControlsButton').removeClass('simHover');
		}
	});    
	
	// Init button event handlers for switching maps
	$('#signIn').on("click", function() {
		activateControl('#accountControls');
	});
	$('#signInAlt').on("click", function(){
		$('.navbar-toggle').trigger('click');
		$('#signIn').trigger('click');
	});
	$('#signIn').on("mouseenter", function() {
		if(userAgent == "other"){
			$('#signIn').addClass('simHover');
		}
	});
	$('#signIn').on("mouseleave", function() {
		if(userAgent == "other"){
			$('#signIn').removeClass('simHover');
		}
	});
	
	// Init button event handlers for switching maps
	$('#mapSwitcher').on("click", function() {
		mapSwitcherMenu();
	});
	$('#mapSwitcher').on("mouseenter", function() {
		if(userAgent == "other"){
			$('#mapSwitcher').addClass('simHover');
		}
	});
	$('#mapSwitcher').on("mouseleave", function() {
		if(userAgent == "other"){
			$('#mapSwitcher').removeClass('simHover');
		}
	});
	$('#mapSwitcherAlt').on("click", function(){
		$('.navbar-toggle').trigger('click');
		hideControlPane();
		$('#mapSwitcher').trigger('click');
	});
     // Init button event handlers for switching themes
 	$('#themeSwitcher').on("click", function() {
		themeSwitcherMenu();
	});
	$('#themeSwitcher').on("mouseenter", function() {
		if(userAgent == "other"){
			$('#themeSwitcher').addClass('simHover');
		}
	});
	$('#themeSwitcher').on("mouseleave", function() {
		if(userAgent == "other"){
			$('#themeSwitcher').removeClass('simHover');
		}
	});    
	$('#themeSwitcherAlt').on("click", function(){
		$('.navbar-toggle').trigger('click');
		hideControlPane();
		$('#themeSwitcher').trigger('click');
	});
	// Init button event handlers for switching maps
	$('#dowMultiFeatureSelect').on("mouseenter", function() {
		if(userAgent == "other"){
			$('#dowMultiFeatureSelect').addClass('simHover');
		}
	});
	$('#dowMultiFeatureSelect').on("mouseleave", function() {
		if(userAgent == "other"){
			$('#dowMultiFeatureSelect').removeClass('simHover');
		}
	});
	$('#dowMultiFeatureSelectAlt').on("click", function(){
		$('.navbar-toggle').trigger('click');
		hideControlPane();
		$('#dowMultiFeatureSelect').trigger('click');
	});

     // Init button event handlers for extra links
 	$('#extraLinks').on("click", function() {
		extraLinksMenu();
	});
	$('#extraLinks').on("mouseenter", function() {
		if(userAgent == "other"){
			$('#extraLinks').addClass('simHover');
		}
	});
	$('#extraLinks').on("mouseleave", function() {
		if(userAgent == "other"){
			$('#extraLinks').removeClass('simHover');
		}
	});    
	$('#extraLinksAlt').on("click", function(){
		$('.navbar-toggle').trigger('click');
		hideControlPane();
		$('#extraLinks').trigger('click');
	});    
	
	
	$('#draw').on("click", function() {
		activateControl('#drawControls');
		// Resize accordion widget
		$( "#drawControlsAccordion" ).accordion( "refresh" );
		setResponsivestuff();
	});
	// This should run once on load to set the height of the accordion widget
	$( "#drawControlsAccordion" ).accordion( "refresh" );
	setTimeout(function(){
		$( "#drawControlsAccordion" ).accordion( "refresh" );
	}, 300);
	
	$('#draw').on("mouseenter", function() {
		if(userAgent == "other"){
			$('#draw').addClass('simHover');
		}
	});
	$('#draw').on("mouseleave", function() {
		if(userAgent == "other"){
			$('#draw').removeClass('simHover');
		}
	});
	$('#drawAlt').on("click", function(){
		$('.navbar-toggle').trigger('click');
		hideControlPane();
		$('#draw').trigger('click');
	});
	
	
	$('#search, #searchAlt').on("click", function(e) {
		activateControl('#searchControls');
		$( "#searchControlsAccordion" ).accordion( "refresh" );
	});
	$('#searchAlt').on("click", function(){
		$('.navbar-toggle').trigger('click');
	});
	$('#search').on("mouseenter", function() {
		if(userAgent == "other"){
			$('#search').addClass('simHover');
		}
	});
	$('#search').on("mouseleave", function() {
		if(userAgent == "other"){
			$('#search').removeClass('simHover');
		}
	});
	// This should run once on load to set the height of the accordion widget
	$( "#searchControlsAccordion" ).accordion( "refresh" );
	setTimeout(function(){
		$( "#searchControlsAccordion" ).accordion( "refresh" );
	}, 300);
	
	$('#layersToggle, #layersToggleAlt').on("click", function() {
		activateControl('#layerControls');
	});
	$('#layersToggleAlt').on("click", function() {
		$('.navbar-toggle').trigger('click');
	});
	$('#layersToggle').on("mouseenter", function() {
		if(userAgent == "other"){
			$('#layersToggle').addClass('simHover');
		}
	});
	$('#layersToggle').on("mouseleave", function() {
		if(userAgent == "other"){
			$('#layersToggle').removeClass('simHover');
		}
	});
	$('#setLocationAlt').on("click", function(){
		$('.navbar-toggle').trigger('click');
		hideControlPane();
	});
	$('#setLocation').on("mouseenter", function() {
		if(userAgent == "other"){
			$('#setLocation').addClass('simHover');
		}
	});
	$('#setLocation').on("mouseleave", function() {
		if(userAgent == "other"){
			$('#setLocation').removeClass('simHover');
		}
	});

	if(ieVersion === false && featureEditEnabled === true){
		$("#outdoorModeAlt").on("click", function(){
			$("#outdoorMode").trigger('click');
		});
		$("#outdoorMode").on("mouseenter", function() {
			if(userAgent == "other"){
				$("#outdoorMode").addClass("simHover");
			}
		});
		$("#outdoorMode").on("mouseleave", function() {
			if(userAgent == "other"){
				$("#outdoorMode").removeClass("simHover");
			}
		});

		var outdoorModeLevel = 0;
		var outdoorModeTimeout, outdoorModeTimestamp; // this is so users can click/tap and hold to turn off the high contrast (outdoor) mode
		$("#outdoorMode, #outdoorModeAlt").on('mousedown', function(){
			outdoorModeTimestamp = new Date();
			outdoorModeTimeout =  setTimeout(function(){
				$("#outdoorModeButton, #outdoorModeLevel, #outdoorModeAlt").removeClass("active");
				$(".sunlightStyleSheet").attr({href: "css/brightLightOff.css"});
				$("#map").css({filter: "contrast(100%)"});
				outdoorModeLevel = 10; // this is going to be made into 0 due to the way events fire multiple times
				$("#outdoorModeLevel, #outdoorModeLevelAlt").html(outdoorModeLevel);
			}, 500);
		});

		$("#outdoorMode, #outdoorModeAlt").on('mouseup', function(){
			var now = new Date();
			if(now.getMilliseconds() - outdoorModeTimestamp.getMilliseconds() < 750){
				clearTimeout(outdoorModeTimeout);

				if(outdoorModeLevel === 0){ // outdoor mode not active. Add outdoor style sheet to UI buttons brighter
					$("#outdoorModeButton, #outdoorModeLevel, #outdoorModeAlt").addClass("active");
					$(".sunlightStyleSheet").attr({href: "css/brightLightOn.css"});
					$("#outdoorModeAltToggle").removeClass("fa-toggle-off").addClass("fa-toggle-on");
					$("#map").css({filter: "contrast(133%)"});
					outdoorModeLevel++;
				} else if(outdoorModeLevel === 1){ // crank UI contrast
					$("#map").css({filter: "contrast(166%)"});
					outdoorModeLevel++;
				} else if(outdoorModeLevel === 2){
					$("#map").css({filter: "contrast(200%)"});
					outdoorModeLevel++;
				} else {
					$("#outdoorModeButton, #outdoorModeLevel, #outdoorModeAlt").removeClass("active");
					$(".sunlightStyleSheet").attr({href: "css/brightLightOff.css"});
					$("#map").css({filter: "contrast(100%)"});
					outdoorModeLevel = 0;
					$("#outdoorModeAltToggle").removeClass("fa-toggle-on").addClass("fa-toggle-off");
				}

				$("#outdoorModeLevel, #outdoorModeLevelAlt").html(outdoorModeLevel);
			}
		});

		$("#outdoorModeAltToggle").on("click", function(){
			if($(this).hasClass("fa-toggle-on")){
				//e.preventDefault();
				//e.stopPropagation();
				$("#outdoorModeButton, #outdoorModeLevel, #outdoorModeAlt").removeClass("active");
				$(".sunlightStyleSheet").attr({href: "css/brightLightOff.css"});
				$("#map").css({filter: "grayscale(0%) contrast(100%)"});
				outdoorModeLevel = 10;
				$("#outdoorModeAltToggle").removeClass("fa-toggle-on").addClass("fa-toggle-off");
			}
			$("#outdoorModeLevel, #outdoorModeLevelAlt").html(outdoorModeLevel);
		});
	} else {
		$("#outdoorMode, #outdoorModeAlt").remove();
	}

	$('#share').on("mouseenter", function() {
		if(userAgent == "other"){
			$('#share').addClass('simHover');
		}
	});
	$('#share').on("mouseleave", function() {
		if(userAgent == "other"){
			$('#share').removeClass('simHover');
		}
	});
	$('#shareAlt').on("click", function(){
          $('.navbar-toggle').trigger('click');
		$('#share').trigger('click');
	});

	$('#printModeToggle').on("click", function() {
		if($('#labelPrintDiv').css('display') != 'none'){
			print();
		} else {
			activateControl('#printControls');
		}

	});
	$('#printModeToggle').on("mouseenter", function() {
		if(userAgent == "other"){
			$('#printModeToggle').addClass('simHover');
		}
	});
	$('#printModeToggle').on("mouseleave", function() {
		if(userAgent == "other"){
			$('#printModeToggle').removeClass('simHover');
		}
	});
	$('#printModeToggleAlt').on("click", function(){
        $('.navbar-toggle').trigger('click');
		$('#printModeToggle').trigger('click');
	});
	
    $('#openHelp').on("click", function(){
        window.open(helpURL);
    });
    $('#openHelp').on("mouseenter", function(){
        if(userAgent == "other"){
            $('#openHelp').addClass('simHover');
        }
    });
    $('#openHelp').on("mouseleave", function(){
        if(userAgent == "other"){
            $('#openHelp').removeClass('simHover');
        }
    });
	$('#openHelpAlt').on("click", function(){
          $('.navbar-toggle').trigger('click');
		$('#openHelp').trigger('click');
	});
	
	var mapSwitcherTimeout;
	function mapSwitcherMenu(){
          clearTimeout(mapSwitcherTimeout);
          $('.switcherDialog').stop().fadeOut();
          $('#themeSwitcher, #extraLinks').css('color', '');
		if($('.mapSwitcherDialog').css('display') == 'none'){
           $('#mapSwitcherButton').css('color', '#1ba0dd');
		   
			$('.mapSwitcherDialog').stop().fadeIn(400, function(){
				mapSwitcherTimeout = setTimeout(function(){
					$('.mapSwitcherDialog').stop().fadeOut();
					$('#mapSwitcherButton').css('color', '');
					
				}, 5000);
			});
		} else {
			$('.mapSwitcherDialog').stop().fadeOut();
            $('#mapSwitcherButton').css('color', '');
			
		}
	}
	$('.mapSwitcherDialog').on("mouseover", function(){
		clearTimeout(mapSwitcherTimeout);
	});
	$('.mapSwitcherDialog').on("mouseout", function(){
		clearTimeout(mapSwitcherTimeout);
		mapSwitcherTimeout = setTimeout(function(){
			$('.mapSwitcherDialog').stop().fadeOut();
			$('#mapSwitcherButton').css('color', '');
			
		}, 5000);
	});

	var themeSwitcherTimeout;
	function themeSwitcherMenu(){
          clearTimeout(themeSwitcherTimeout);
          $('.switcherDialog').stop().fadeOut();
          $('#mapSwitcherButton, #extraLinks').css('color', '');
		if($('.themeSwitcherDialog').css('display') == 'none'){
           $('#themeSwitcher').css('color', '#1ba0dd');

			$('.themeSwitcherDialog').stop().fadeIn(400, function(){
				themeSwitcherTimeout = setTimeout(function(){
					$('.themeSwitcherDialog').stop().fadeOut();
					$('#themeSwitcher').css('color', '');

				}, 5000);
			});
		} else {
		   $('.themeSwitcherDialog').stop().fadeOut();
             $('#themeSwitcher').css('color', '');

		}
	}
	$('.themeSwitcherDialog').on("mouseover", function(){
		clearTimeout(themeSwitcherTimeout);
	});
	$('.themeSwitcherDialog').on("mouseout", function(){
		clearTimeout(themeSwitcherTimeout);
		themeSwitcherTimeout = setTimeout(function(){
			$('.themeSwitcherDialog').stop().fadeOut();
			$('#themeSwitcher').css('color', '');

		}, 5000);
	});

	 // this is the EH button, not a nav button
	 $('.createGPSPoint').on("mouseenter", function() {
		if(userAgent == "other"){
			$('.createGPSPoint').addClass('simHover');
		}
	});
	$('.createGPSPoint').on("mouseleave", function() {
		if(userAgent == "other"){
			$('.createGPSPoint').removeClass('simHover');
		}
	});	

	var extraLinksTimeout;
	function extraLinksMenu(){
          clearTimeout(extraLinksTimeout);
          $('.switcherDialog').stop().fadeOut();
          $('#mapSwitcherButton, #themeSwitcher').css('color', '');
		if($('.extraLinksDialog').css('display') == 'none'){
           $('#extraLinks').css('color', '#1ba0dd');
		   
			$('.extraLinksDialog').stop().fadeIn(400, function(){
				extraLinksTimeout = setTimeout(function(){
					$('.extraLinksDialog').stop().fadeOut();
					$('#extraLinks').css('color', '');
					
				}, 5000);
			});
		} else {
		   $('.extraLinksDialog').stop().fadeOut();
             $('#extraLinks').css('color', '');
			
		}
	}
	$('.extraLinksDialog').on("mouseover", function(){
		clearTimeout(extraLinksTimeout);
	});
	$('.extraLinksDialog').on("mouseout", function(){
		clearTimeout(extraLinksTimeout);
		extraLinksTimeout = setTimeout(function(){
			$('.extraLinksDialog').stop().fadeOut();
			$('#extraLinks').css('color', '');
			
		}, 5000);
	});
     
     $('.bingBirdsEyeLink').on("click", function(){ // this is here because it is within the extra links dialog (for portage maybe others)
          var mapCenterDD = webMercatorUtils.xyToLngLat(centerCoords.x, centerCoords.y);
          var bingURL = 'https://www.bing.com/mapspreview?cp=';
          bingURL += mapCenterDD[1] + '~' + mapCenterDD[0];
          bingURL += '&lvl=' + map.getZoom() + '&dir=0&sty=b';
          window.open(bingURL,'_blank');
     });
     
     $('.googleStreetViewLink').on("click", function(){ // this is here because it is within the extra links dialog (for portage maybe others)
          var mapCenterDD;
		  if(popup.features && popup.features.length > 0 && popup.isShowing){
			mapCenterDD = webMercatorUtils.xyToLngLat(popup.location.x, popup.location.y);
		  } else {
			mapCenterDD = webMercatorUtils.xyToLngLat(centerCoords.x, centerCoords.y);
		  }
		  
          var googleURL = 'http://maps.google.com/maps?q=&layer=c&cbll=';
          googleURL += mapCenterDD[1] + ',' + mapCenterDD[0];
          googleURL += '&cbp=12,0,0,0,0';
          window.open(googleURL,'_blank');
     });

     var searchRadioIsAnimating = false; // due to a bug in chrome, this had to be added. The class noPointerEvents was breaking this.
	 window.setSearchModeRadioHandler = function(){ // this is a function to make it so more searches can be added at any time
		$(".searchModeRadio").off();
		$(".searchModeRadio").on("click", function(){
			if(searchRadioIsAnimating === false){
				searchRadioIsAnimating =true;
				var lastPane = "";
				var lastPaneButtonId = $('#searchType').find('.fa-dot-circle-o').attr('id');
				if(typeof(lastPaneButtonId) !== "undefined"){  // this check adds fault tolerance. Without it, if no radio button was already active, stuff would break.
					lastPane = lastPaneButtonId.replace('Button', 'Expando');
				}
	
				var nextPane = this.id.replace('Button', 'Expando');
				var showCreate = $(this).attr('createLayer') ? true : false; //does search have a createlayer attribute?
	
				if(lastPane !== ""){ // for fault tolerance
					if(lastPane != nextPane){
						$(".searchModeRadio").removeClass("fa-dot-circle-o").addClass("fa-circle-o");
						$(this).removeClass('fa-circle-o').addClass("fa-dot-circle-o");
						$('#'+ lastPane).stop().fadeOut(250, function(){
							$('#' + nextPane).stop().fadeIn(250, function(){
								searchRadioIsAnimating = false;
							});
							$('#searchDivScrollPane').perfectScrollbar('update');
						});
					} else {
						 searchRadioIsAnimating = false;
					}
				} else {
					$(".searchModeRadio").removeClass("fa-dot-circle-o").addClass("fa-circle-o");
					$(this).removeClass('fa-circle-o').addClass("fa-dot-circle-o");
					$('.searchExpando').stop().fadeOut(250, function(){ // Just fade out all of them. It's a one time thing (a search was probably removed by code in config).
						$('#' + nextPane).stop().fadeIn(250, function(){
							searchRadioIsAnimating = false;
						});
					});
				}
				if(showCreate && (deniedLayers.indexOf($(this).attr('createLayer')) == -1)){ //has createlayer attribute, show create new button, also must have permission
					$('#createNewFromSearch').removeClass('hideMe');
				} else {
					$('#createNewFromSearch').addClass('hideMe');
				}
			}
		});
		$(".searchModeLabel").off("click");
		$(".searchModeLabel").on("click", function(){
			$(this).prev().trigger('click');
		});
	};
	setSearchModeRadioHandler();
	
    // optional pin choosers (currently used in calhoun) 
    $('.pinOptionRadio').on("click", function(){
        $('.pinOptionRadio').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
        $(this).addClass('fa-dot-circle-o');
        var pinInputId = $(this).attr('id').replace('pin', 'pinInput');
        $('.pinOptionInput').hide();
        $('#'+pinInputId).show();   
    });
     
	 // Static print legend handlers for buffer
    $("#bufferPrintLegendCheckbox").on("click", function(){
		if($("#bufferPrintLegendCheckbox").hasClass("fa-square-o")){
			$("#bufferStaticRow").removeClass("hidden");
		} else {
			$("#bufferStaticRow").addClass("hidden");
		}
	});

	$(document).on('click', '#createNewFromSearch', function(){
		$('#searchButtons').addClass('disabledButton'); //diabled buttons so user can't reclick
		let layer = $('#searchType .fa-dot-circle-o').attr('createLayer');
		if(!layer){ //could not find currently selected layer
			showMessage('Could not find the layer to add new record to.',null,'info');
			$('#searchButtons').removeClass('disabledButton');
		} else {
			$('#searchSpinner').removeClass('hidden');
			let parentAtts = $.extend(true, {}, window[layer].templates[0].prototype.attributes); //build blank feature
            var newGraphic = createGraphic(parentAtts);
			window[layer].applyEdits([newGraphic],null,null,function(response){ //create blank feature
				if(response[0].success == true){
					hideControlPane(); //reset things
					$('#searchSpinner').addClass('hidden');
					$('#searchButtons').removeClass('disabledButton');
					newGraphic.attributes[window[layer].objectIdField] = response[0].objectId; //add feature to layer on map for editing purposes
					newGraphic.attributes['GlobalID'] = response[0].globalId;
					newGraphic._layer = window[layer];
					window[layer].graphics.push(newGraphic);
					showDataPane(); //start showing form
					formViewer.newForm(newGraphic, null, function(){
						$('#formEdit').click(); //edit mode
					});
				} else {
					showMessage(window[layer].name + ' was not created.', null, 'error');
					$('#searchSpinner').addClass('hidden');
					$('#searchButtons').removeClass('disabledButton');
				}
			}, function(err){
				console.log(err);
				showMessage(window[layer].name + ' was not created.' + err.message, null, 'error');
				$('#searchSpinner').addClass('hidden');
				$('#searchButtons').removeClass('disabledButton');
			});
		}
	});
    
    // embed map keyup handlers for width and height of iframe change
    $(document).on('input', '.embedWidth, .embedHeight', function(){
		var context = $(this);
		var contextParent = context.parent(".embedDiv");

		// update data
		var attr = context.hasClass("embedWidth") ? "width": "height";
		var checkValue = parseInt(context.val());
		var newValue = checkValue > 0 ? checkValue : 0;
		contextParent.data(attr, newValue);
		var data = contextParent.data();
		context.siblings(".embedHTML").html(createIframeHTML(data.url, data.width, data.height));
    });
     
	$(function() {
		$( "#salesInfo" ).accordion({
			heightStyle: "content",
			active: false,
			collapsible: true
		});
		$('#lrpResBuildingWrapper, #lrpAgBuildingWrapper, #lrpComBuildingWrapper').accordion({
			header: ".buildingInfoHeader",
			heightStyle: "content",
			active: false,
			collapsible: true
		});
     	$( "#delTaxInfo" ).accordion({
			heightStyle: "content",
			active: false,
			collapsible: true
		});
		$( "#taxInfo" ).accordion({
			heightStyle: "content",
			active: false,
			collapsible: true,
			header: ".taxInfoH3",
			beforeActivate: function() {
				accordionFocus = setInterval(function(){
					$(location).attr('href', '#' + $('.taxInfoH3.ui-accordion-header-active').attr('id'));
				},120);
			},
			activate: function() {
				clearInterval(accordionFocus);
				$(location).attr('href', '#' + $('.taxInfoH3.ui-accordion-header-active').attr('id'));
				setTimeout(function(){$(location).attr('href', '#');},150);
			}
		});
		
	});
	
	if(isMobile){
		$("#mobileDataPane").append($("#generalDetailsScrollPane"));
		$("#generalDetailsScrollPane").css("height", "100%");
		$("#closeMobileDataPane").on("click", function(){
			$("#modalOverlay, #mobileDataPane").fadeOut();
		});
	}
       
	if(isMobile){
		$('#supportedDevicesText').css('display', 'block');
	}

    var showDisclaimer;
	if(params.pdf == '1' || params.dOff === "1"){
		showDisclaimer = false;
	} else {
		showDisclaimer = true;
	}

	if(showDisclaimer) showDisclaimer = checkDisclaimerTime(); //last chance to see if we still need disclaimer

	/**
	 * Check localstorage disclaimer object to see if there is a time for this map, and check if it happened in the last 24 hours
	 * @param fromAccept is this coming from the accept button of disclaimer
	 * @returns boolean does the disclaimer need to be shown?
	 */
	function checkDisclaimerTime(fromAccept=false){
		//86400000 is 24 hours
		let disclaimerTime = getJSONFromLocalStorage('disclaimerTimes');
		let reset = false;
		let t = disclaimerTime[currentMap] ? disclaimerTime[currentMap] : 0;
		if(t + 3600000 > Date.now()){ //if last time plus 1 hour is more than right now, we're good
			return false;
		} else {
			reset = true;
		}
		//only reset if coming from the accept button, we don't want to reset just because user visited the page
		if(fromAccept && reset){ //time to reset this map
			disclaimerTime[currentMap] = Date.now();
			localStorage.setItem('disclaimerTimes', JSON.stringify(disclaimerTime));
		}
		return true;
	}

	if(showDisclaimer){
		setDiscalimerText();
		setDiscalimerBanner();
		setDiscalimerUpdates();
		setSupportedDevices();
		$('#modalOverlay').fadeIn();
	} else {
		$('#modalOverlay').fadeOut();
	}
	$(window).on("orientationchange", function(){
		setDisclaimerScrollbar();
		if(viewportWidth < 481){
				setTimeout(function(){
				setDisclaimerScrollbar();
			}, 1000);
		}
	});
	$(window).on("resize", function(){
		setDisclaimerScrollbar();
		if(viewportWidth < 481){
				setTimeout(function(){
				setDisclaimerScrollbar();
			}, 1000);
		}
	});
    
	$("#accept").on("click", function(){
		$('#disclaimerModal, #modalOverlay').fadeOut();
		checkDisclaimerTime(true);
	});
    
	$('#supportedDeviceLink').on("click", function(){
		$('#supportedDevicesText').fadeIn(1000);
		setTimeout(function(){
			$(location).attr('href', '#supportedDevicesText');
			$(location).attr('href', '#');
		}, 100);
	});
	//var resizing = false; // This is (was?) used to tame the firing of the function in the resize event below
	
	$('#layerCheckboxAccordionPane').perfectScrollbar();
	$('#multiSelectTableWrapper').perfectScrollbar(); // setup scrollbars for multiselect options box
	

	$('#searchDivScrollPane').perfectScrollbar();

	if(isMobile === false){
		$('#generalDetailsScrollPane').perfectScrollbar({supressScrollX: 'true'});
	}
	setResponsivestuff();
	/*Pace.on("done", function(){
		console.log("Pace done");
		enableUI();
		Pace.stop();
		// make the border blue and remove the Pace loading bar.
		$('#paceReplacementBar').removeClass('hidden');
		clearTimeout(paceTimeout);
	});
	var paceTimeout = setTimeout(function(){
		enableUI();
		Pace.stop();
		clearTimeout(paceTimeout);
		console.log("Pace Timeout Hit");
	}, 12000);*/
	
	var mapReadyInterval = setInterval(function(){
		
		if($("#map_layers svg").length === 1 || $("#map_gc").length === 1){
			setTimeout(function(){
				enableUI();
				console.log("UI enabled");
			}, 1000);
			clearInterval(mapReadyInterval);
		}
	}, 1000);
	
	var initDestroyTimeOutPace = function() {
		var counter = 0;
	
		var refreshIntervalId = setInterval( function(){
			var progress; 
	
			if( typeof $( '.pace-progress' ).attr( 'data-progress-text' ) !== 'undefined' ) {
				progress = Number( $( '.pace-progress' ).attr( 'data-progress-text' ).replace("%" ,'') );
			}
	
			if( progress > 98 ) {
				counter++;
			}
	
			if( counter > 50 ) {
				console.log("Pace done");
				clearInterval(refreshIntervalId);
				Pace.stop();
				$('#paceReplacementBar').removeClass('hidden');
				//location.reload(); // reload the page a ton of times figure out souce of projection issue
			}
		}, 100);
	};
	initDestroyTimeOutPace();
	
	function enableUI(){
		$('#mainWrapper').animate({opacity: 1}, 600);
		$('#appLoadOverlay').fadeOut();
				if(showDisclaimer && currentMap !== "default"){
			$("#disclaimerModal").fadeIn('fast', function(){ //update scrollbar after modal is shown
				setDisclaimerScrollbar();
			});
		}

		// Bryan: 3/2018 - any troublesome accordions can be refreshed here so they're not the wrong size on page load. I wasn't brave enough to just use the ".ui-accordion" selector
		if(activeControl === "insightControls"){
		  $("#insightsAccordion").accordion("refresh");
		}

		// make scroll bar show for whatever is active in the control pane
		//active control could look like drawingTools, or drawingTools%20MiscellaneousTools
		let controlName = (activeControl && typeof(activateControl) === 'string') ? '#' + activeControl.split('%20')[0] : '';
		$(controlName).find('.ps').perfectScrollbar('update');
	}
     
     
     $(document).on('keyup', 'input.input-min-max-restricted', function(e){ // this class can be used to make sure min and max values are enforced on an input
		if(!$(this).attr('type') == 'number') return;

		e.preventDefault();

		var min = parseFloat($(this).attr('min'));
		var max = parseFloat($(this).attr('max'));
		var current_val = parseFloat($(this).val());
		var revised_val;
		if(current_val < min){
			 revised_val = min;
		}
		if(current_val > max){
			 revised_val = max;
		}

		if(typeof revised_val != "undefined") $(this).val(revised_val);

     });


	$('.genReport').on("click", function(){
		if(activeControl == 'printControls'){
			removeMapPrint();
		}
		if($('#generalDetailsWrapper').is(':visible')){
			createReport(); // lrp
		} else if($('#insightsOandMWrapper').is(':visible')){
			console.log('O&M Insights Report');
			createOandMReport();
		} else if($('#insights-query-report').is(':visible')){
			console.log('Query Insights Report');
			createQIReport();
		} else if($('#sales-details-wrapper').is(':visible')){
			console.log('Sales Records Report');
			createSalesRecordsReport();
		}
	});
	$('#closeReport').on("click", function(){
        activateControl('#layerControls','closed');
		if($('#generalDetailsWrapper').is(':visible')){
			destroyReport(); // lrp
		} else if($('#insightsOandMWrapper').is(':visible')){
			destroyOandMReport();
		} else if($('#insights-query-report').is(':visible')){
			removeChartCanvasImgs();
			destroyQIReport();
		} else if($('#sales-details-wrapper').is(':visible')){
			destroySalesRecordsReport();
		} else if($("#pfe-print-copy").length > 0){ // pfe print mode active if this element exists
			formViewer.closePfePrint();
		}
		showDataPane();
	});

//max length checker, must be a div with maxlength attribute
$(document).on('keyup keydown paste', 'div[maxlength]', function(e){
    if('8 37 38 39 40 46'.indexOf(e.which) == -1 || e.type == 'paste'){
        if(this.attributes['maxlength'].value != 'undefined'){
            var max = parseInt(this.attributes['maxlength'].value)
            if(this.innerText.length > max-1){
				e.preventDefault();
				showMessage(`You've reached the maximum length for the field. Please re-evaluate.`, null ,'info');
				$(e.target)[0].innerText = $(e.target)[0].innerText.slice(0,max-1);
				setCursorToEndOfContentEditable(e.target);
            }
        }
    }
});

// dropdown function
//click handler for new dropdowns
$(document).on("click", '.dropdownSpan', function(e){
	var oldSelect =  $(this).prev(); //hidden select
	var oldSpan = $(this)[0];
	var extraClass = '';
	let dontUpdate = false;
	let scrollVal = 0; //will store the val to scroll to if there was a selected value
	if(oldSpan.dataset['addclass']){ //if adding a custom class to container
		extraClass = oldSpan.dataset['addclass']
	}
	if(oldSpan.dataset['dontupdate']){ //if we dont want to update the spans text
		dontUpdate = true;
	}
	$('body').append('<dialog id="searchableDropdown" class="'+extraClass+'"><div class="optionsContainer"></div></dialog>'); //create new div to hold options
	document.querySelector('#searchableDropdown').showModal();
	let firstOption = oldSelect.find('option').length > 0 ? oldSelect.find('option')[0] : null; //is the first option none selected? if so include it in the dropdown
	if(firstOption && firstOption.value == 'None Selected'){
		$('#searchableDropdown .optionsContainer').append('<div class="dropdownOption">None Selected</div>');
	}
	if($(this).find('option'))
	if($(this).prev().find('optgroup').length > 0){ //if option groups in hidden select
		var html = '';
		$(this).prev().find('optgroup').each(function() {
			html += '<div class="dropdownOptionHeader">'+this.label+'</div>';
			$(this).find('option').each(function(){ //each option in group
				var dataTable = ''; //tack on attributes
				var extraClasses = '';
				if(this.attributes["data-table"]){
					dataTable += " data-table='"+this.attributes["data-table"].value+"'";
				}
				if(this.disabled == true) extraClasses += 'dropdownDisabled';
				if($(this).css('display') != 'none'){ //if set to none don't show in list
					html +='<div class="dropdownOption '+extraClasses+'" data-selvalue="'+this.value+'"'+dataTable+'>'+this.text+'</div>';
				}
			})
		});
		$('#searchableDropdown .optionsContainer').append(html);
	} else {
		var html = '';
		$(this).prev().find('option').each(function(){ //options in hidden select
			var dataTable = '';
			var extraClasses = '';
			if(this.attributes["data-table"]){
				dataTable += " data-table='"+this.attributes["data-table"].value+"'";
			}
			if(this.disabled == true) extraClasses += 'dropdownDisabled';
			if($(this).css('display') != 'none'){ //if set to none don't show in list
				html += '<div class="dropdownOption '+extraClasses+'" data-selvalue="'+this.value+'"'+dataTable+'>'+this.text+'</div>';

			}
		})
		$('#searchableDropdown .optionsContainer').append(html);
	}

	//getting attributes from span to highlight corresponding option in dropdown
	// var dataTable = '';
	// if(oldSpan.attributes["data-table"]){
	// 	dataTable += "[data-table='"+oldSpan.attributes["data-table"].value+"']";
	// }
	if(oldSpan.attributes["data-selvalue"]){
		//$('#searchableDropdown .optionsContainer').find("div[data-selvalue='"+oldSpan.attributes["data-selvalue"].value+"']"+dataTable).addClass('searchableDDSelected'); //finding currently selected option
		$('#searchableDropdown .optionsContainer').find('div').each(function(i,ea){ //basing search off text value of option
			if(ea.attributes['data-selvalue'] && ea.attributes['data-selvalue'].value == oldSpan.attributes['data-selvalue'].value){ //if option matches selected
				var thisOption = $(ea);
				if((thisOption[0].attributes['data-table'] && thisOption[0].attributes['data-table'].value == oldSpan.attributes['data-table'].value) || (thisOption[0].attributes['data-table'] == undefined)){ //if data tables match or no data table
					thisOption.addClass('searchableDDSelected');
					scrollVal = thisOption.offset().top;
				}
			}
		})
	}
   
	//perform some checking to see if we need to move the box somewhere else (up or left)
	fitDropdown(e.target);
	if($('.optionsContainer .dropdownOption').length > 10 || $(this).prev().find('optgroup').length > 0){ //more than 10 options, or contains headers, make searchable
		if(extraClass.indexOf('compoperatorDropdown') > -1){ //more than 10, not including disabled
			if($('.optionsContainer .dropdownOption:not(.dropdownDisabled)').length > 10){
				makeSearchable('.optionsContainer','dropdownOptionSearch');
			}
		} else {
			makeSearchable('.optionsContainer','dropdownOptionSearch');
		}
	};
	if(scrollVal > 0){ //scroll to selected value
		$('#searchableDropdown').scrollTop(scrollVal - 120); //minus 120 fits a little better so its not at the very top
	}
	$('#searchableDropdown input').trigger("focus"); //set focus to new search in dropdown
	$('.dropdownOption').on("click", function(){
		var selOption = this; //store to use later
		if(oldSelect.hasClass('query-filter-select') || oldSelect[0].id.indexOf("layer-select") > -1 || oldSelect[0].className.indexOf('operator') > -1){ //options with no other attributes
			oldSelect.val(this.attributes["data-selvalue"].value).trigger('change');
		} else if(oldSelect[0].id == 'formDesignerFormSelect' || oldSelect[0].id == 'formDesignerFeatureSubtypeSelect' ){ //funkyness for react controlled selects
			oldSelect.val(this.attributes["data-selvalue"].value).trigger('change');
			var input = document.querySelectorAll('#'+oldSelect[0].id)[0]
			var nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLSelectElement.prototype, "value").set;
			nativeInputValueSetter.call(input, this.attributes["data-selvalue"].value);

			var ev2 = new Event('change', { bubbles: true});
			input.dispatchEvent(ev2);
		} else { //ensure we get the correct option using attributes
			// var dataTable = '';
			let useText = false;
			if(this.attributes["data-table"]){ //if data-table, we need to use text to find match
				// dataTable += "[data-table='"+this.attributes["data-table"].value+"']";
				useText = true;
			}
			oldSelect.find('option').each(function(i,ea){ //basing search off text value of option
				if(!useText){ //check using selvalue
					if(selOption.attributes['data-selvalue'].value == ea.attributes.value.value){ //does it match this option
						$(ea).prop("selected", "selected");
						if(oldSpan.hasAttribute('useDispatch')){
							oldSelect[0].dispatchEvent(new Event('change', { 'bubbles': true }))
						} else {
							oldSelect.trigger('change');
						}
						return false; //end
					}
				} else {
					let decoded = '';
					try {
						decoded = decodeURIComponent(selOption.innerText);
					} catch { //failed decode
						decoded = selOption.innerText;
					}
					if(ea.innerText == decoded){ //if option matches selected
						var thisOption = $(ea);
						if((thisOption[0].attributes['data-table'] && thisOption[0].attributes['data-table'].value == selOption.attributes['data-table'].value) || (thisOption[0].attributes['data-table'] == undefined)){ //if data tables match or no data table
							thisOption.prop("selected", "selected");
							if(oldSpan.hasAttribute('useDispatch')){
								oldSelect[0].dispatchEvent(new Event('change', { 'bubbles': true }))
							} else {
								oldSelect.trigger('change');
							}
							return false; //end
						}
					}
				}
			})
		}
		document.querySelector('#searchableDropdown').close(); //remove dropdown
		$('body').off('click.HideSearchableDropdown'); //turn off click listener to close dropdown
		var optGroup = $(this).prevAll('.dropdownOptionHeader').first(); //if option group (layer)
		var optGroupText = '';
		if (optGroup.length > 0 && extraClass.indexOf('guidedDropdown') == -1){ //dont show header for guided checklist
			optGroupText = '('+optGroup[0].innerText+') '
		}
		if(!dontUpdate){ //do we want to update the span?
			$(oldSelect).next().html(optGroupText+this.innerText); //set span to selected option
			if(this.attributes["data-table"]){
				$(oldSelect).next().attr('data-table', this.attributes["data-table"].value);
			}
			$(oldSelect).next().attr({'data-selvalue': this.attributes["data-selvalue"] ? this.attributes["data-selvalue"].value : '', 'title': this.outerText});
		}
	})
	$('body').on('click.HideSearchableDropdown',function(e){ //listening to clicks on body to hide popup on any click not on popup
		var el = $('#searchableDropdown')[0].getBoundingClientRect(); //check for xy of click
		if(!((e.clientX < el.right && e.clientX > el.left) && (e.clientY > el.top && e.clientY < el.bottom))){ //if outside bounding box of dropdown
			document.querySelector('#searchableDropdown').close(); //remove dropdown
			$('body').off('click.HideSearchableDropdown');
		}
	})
	$('#searchableDropdown').on('close', function(){
		document.querySelector('#searchableDropdown').remove();
	})
	function fitDropdown(target){
		let dropdownHeight = $('#searchableDropdown').height();
		let dropdownWidth = $('#searchableDropdown').width();
		let top = $(target).offset().top + $(target).height()+4;
		let left = $(target).offset().left;
		let addBorderClass = false;
		if(window.innerHeight-top < dropdownHeight+40){ //need to move div up, 40 is the height of footerbar
			top = top-dropdownHeight;
			addBorderClass = true;
		}
		if(window.innerWidth-left < dropdownWidth){ //need to move div left
			left = left-dropdownWidth+$(target).width();
			addBorderClass = true;
		}
		$('#searchableDropdown').css({"top":top+'px', "left":left+'px'}); //position dropdown below element
		if(addBorderClass){
			$('#searchableDropdown').addClass('addGrayBorder');
		}
	}
})

window.updateDropdownSpan = function(element, overrideText, convertToJquery){ //take a select and then update the preceding span with current select value
	if(element){
		if(convertToJquery){
			element = $(element);
		}
		let span = 	element.next();
		let el = element[0];
		if(el){
			let selOption = el.options[el.selectedIndex]
			if(selOption){
				let parentGroup = $(selOption).parent('optgroup');
				let parentText = '';
				if(parentGroup.length > 0){ //do we have a grouping that needs to be included?
					parentText = '('+parentGroup[0].label+') ';
				}
				span.html(parentText + selOption.innerHTML);
				span.attr('title',selOption.innerHTML);
				span.attr('data-selvalue',selOption.attributes['value'].value);
				if(selOption.attributes['data-table']){
					span.attr('data-table',selOption.attributes['data-table'].value);
				}
			} else if(overrideText){
				span.html(overrideText);
				span.attr('title',overrideText);
				span.attr('data-selvalue','');
				span.attr('data-table','');
			}
		}
	}
}

		
		// enable county switching layer
		//countySwitchingLayer.setVisibility(true);
		
		// enable search functionality (make visible)
		$("#addressSearchRadio").removeClass("hidden");
	
		
		// set county name
		$("#printCountyName").html("Bay City/County");
	
		// Hide login stuff
		//$("#userNameInHeader, #signIn, #signInAlt").addClass("hidden");

		// set stuff if user logged in
		var sessionUserName = "";
		if(sessionUserName !== ""){
			$("#userNameInHeader").html(sessionUserName);
			$("#signInButton").attr("title","My Account");

			$("#loginMessage").fadeOut("slow");
			$("#link2portal").css({display: "block"});
			$("#loginAccordion").fadeOut(400, function(){
					$("#accountControlsAccordion").fadeIn();
					$("#accountControlsAccordion").accordion("refresh");
			});
		}
		
		// Replace single logo with custom dual logo DIV
		$(".mini-logo, .navbar-brand").remove();
		$("#navContainer").prepend('<div id="logoWrapper"><a target="_blank" href="http://www.baycounty-mi.gov/GIS/"><img class="bayLogo" src="img/BayCountyLogo.jpg"></a><a target="_blank" href="http://www.baycitymi.org/our-city"><img class="bayLogo" src="img/bayCityLogo.png"></a><a target="_blank" class="navbar-brand navbar-bay"> Bay <span class="d-none d-sm-inline"> Area</span> GIS</a></div>');

		$("#disclaimerBanner td").eq(0).html('<img src="img/BayCountyLogo.jpg" class="bayLogo"><img src="img/bayCityLogo.png" class="bayLogo">');
		
		$("#printSeal").prop("src", "./img/BayCountyLogo.jpg");
		$("#printSeal").after('<img id="printSeal2" src="./img/bayCityLogo.png">');
		$("#printSeal").css({
			"width": "75px",
			"display": "inline-block"
		});
		$("#printSeal2").css({"left": "98px",
			"width": "95px",
			"display": "inline-block",
			"padding-left": "10px",
			"top": "38px"
		});
		// I can not beleive I am doing this
		//$("head").append("<style>#printTitle.portrait, #subTitle.portrait{ left: 200px!important}</style>");
	
			$("#sitePlanUpload").remove();
			$("#cancelPrint").css("width", "100%");	
	// the following function prohibits touch zoom on all elements... that's right Apple... I WIN!!!
	if(userAgent === "iOS"){
		var numTouches = 0;
		$('body').on('touchmove', function(event){
			numTouches = event.originalEvent.touches.length;
			if(numTouches > 1){
				event.preventDefault();
			}
		});
		
		var mylatesttap = new Date().getTime();
		$('body').on('touchstart', function(event){
			var now = new Date().getTime();
			var timesince = now - mylatesttap;
			if((timesince < 500) && (timesince > 0)){
				// double tap
				event.preventDefault();
				event.stopPropagation();
				event.stopImmediatePropagation();
				
				// this alert is ok for letting you know the function didn't error out, but it messes with the event blocking functions.
				//alert('You tapped me Twice !!!');
			}else{
				// too much time to be a doubletap
			}
	
			mylatesttap = new Date().getTime();
		});
	}
	//chrome 108 bug messed up print
	if(navigator && navigator.userAgentData && navigator.userAgentData.brands && Array.isArray(navigator.userAgentData.brands)){
		for(b of navigator.userAgentData.brands){
			if(b.brand == 'Chromium' && (parseInt(b.version) >= 108 && parseInt(b.version) <= 110)){ //capture any chrome version above 108
				$('body').addClass('chrome108');
			}
		}
	}else if(navigator && navigator.userAgent && navigator.userAgent.split('Firefox/').length > 1){
		let vers = navigator.userAgent.split('Firefox/')[1];
		if(parseInt(vers) >= 108){
			$('body').addClass('firefox108');
		}
	}

	
});