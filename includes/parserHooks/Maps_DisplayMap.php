<?php

/**
 * Class for the 'display_map' parser hooks.
 * 
 * @since 0.7
 * 
 * @file Maps_DisplayMap.php
 * @ingroup Maps
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapsDisplayMap extends ParserHook {
	/**
	 * No LSB in pre-5.3 PHP *sigh*.
	 * This is to be refactored as soon as php >=5.3 becomes acceptable.
	 */	
	public static function staticInit( Parser &$parser ) {
		$instance = new self;
		return $instance->init( $parser );
	}	
	
	public static function initialize() {
		
	}	
	
	/**
	 * Gets the name of the parser hook.
	 * @see ParserHook::getName
	 * 
	 * @since 0.7
	 * 
	 * @return string
	 */
	protected function getName() {
		return 'display_map';
	}
	
	/**
	 * Returns an array containing the parameter info.
	 * @see ParserHook::getParameterInfo
	 *
	 * TODO: migrate stuff
	 * 
	 * @since 0.7
	 * 
	 * @return array
	 */
	protected function getParameterInfo( $type ) {
		global $egMapsDefaultServices, $egMapsDefaultTitle, $egMapsDefaultLabel;
		
		$params = MapsMapper::getCommonParameters();

		$params['mappingservice']['default'] = $egMapsDefaultServices['display_map'];
		$params['mappingservice']['manipulations'] = new MapsParamService( 'display_point' );

		$params['zoom']['dependencies'] = array( 'coordinates', 'mappingservice' );
		$params['zoom']['manipulations'] = new MapsParamZoom();

		$params['coordinates'] = array(
			'aliases' => array( 'coords', 'location', 'address', 'addresses', 'locations' ),
			'criteria' => new CriterionIsLocation( $type === ParserHook::TYPE_FUNCTION ? '~' : '|' ),
			'manipulations' => new MapsParamLocation( $type === ParserHook::TYPE_FUNCTION ? '~' : '|' ),
			'dependencies' => array( 'mappingservice', 'geoservice' ),
			'default' => array(),
			'islist' => true,
			'delimiter' => $type === ParserHook::TYPE_FUNCTION ? ';' : "\n",
			'message' => 'maps-displaypoints-par-coordinates', // TODO
		);

		$manipulation = new MapsParamLocation();
		$manipulation->toJSONObj = true;

		$params['centre'] = array(
			'aliases' => array( 'center' ),
			'criteria' => new CriterionIsLocation(),
			'manipulations' => $manipulation,
			'default' => false,
			'manipulatedefault' => false,
			'message' => 'maps-displaypoints-par-centre', // TODO
		);

		$params['title'] = array(
			'name' => 'title',
			'default' => $egMapsDefaultTitle,
			'message' => 'maps-displaypoints-par-title', // TODO
		);

		$params['label'] = array(
			'default' => $egMapsDefaultLabel,
			'message' => 'maps-displaypoints-par-label', // TODO
			'aliases' => 'text',
		);

		$params['icon'] = array( // TODO: image param
			'default' => '', // TODO
			'message' => 'maps-displaypoints-par-icon', // TODO
		);

		$params['lines'] = array(
			'default' => array(),
			'message' => 'maps-displaypoints-par-lines', // TODO
			'criteria' => new CriterionLine( $type === ParserHook::TYPE_FUNCTION ? '~' : '|' ),
			'manipulations' => new MapsParamLine( $type === ParserHook::TYPE_FUNCTION ? '~' : '|' ),
			'delimiter' => ';',
			'islist' => true,
		);

		$params['polygons'] = array(
			'default' => array(),
			'message' => 'maps-displaypoints-par-polygons', // TODO
			'criteria' => new CriterionPolygon( $type === ParserHook::TYPE_FUNCTION ? '~' : '|' ),
			'manipulations' => new MapsParamPolygon( $type === ParserHook::TYPE_FUNCTION ? '~' : '|' ),
			'delimiter' => ';',
			'islist' => true,
		);

		$params['circles'] = array(
			'default' => array(),
			'message' => 'maps-displaypoints-par-circles', // TODO
			'manipulations' => new MapsParamCircle( $type === ParserHook::TYPE_FUNCTION ? '~' : '|' ),
			'delimiter' => ';',
			'islist' => true,
		);

		$params['rectangles'] = array(
			'default' => array(),
			'message' => 'maps-displaypoints-par-rectangles', // TODO
			'manipulations' => new MapsParamRectangle( $type === ParserHook::TYPE_FUNCTION ? '~' : '|' ),
			'delimiter' => ';',
			'islist' => true,
		);

		$params['copycoords'] = array(
			'type' => 'boolean',
			'default' => false,
			'message' => 'maps-displaypoints-par-copycoords', // TODO
		);

		$params['copycoords'] = array(
			'type' => 'boolean',
			'default' => false,
			'message' => 'maps-displaypoints-par-static', // TODO
		);

		$params['maxzoom'] = array(
			'type' => 'integer',
			'default' => false,
			'manipulatedefault' => false,
			'message' => 'maps-displaypoints-par-maxzoom', // TODO
			'dependencies' => 'minzoom',
		);

		$params['minzoom'] = array(
			'type' => 'integer',
			'default' => false,
			'manipulatedefault' => false,
			'message' => 'maps-displaypoints-par-minzoom', // TODO
			'lowerbound' => 0,
		);
		
		return $params;
	}
	
	/**
	 * Returns the list of default parameters.
	 * @see ParserHook::getDefaultParameters
	 * 
	 * @since 0.7
	 * 
	 * @return array
	 */
	protected function getDefaultParameters( $type ) {
		return array( 'coordinates' );
	}
	
	/**
	 * Renders and returns the output.
	 * @see ParserHook::render
	 * 
	 * @since 0.7
	 * 
	 * @param array $parameters
	 * 
	 * @return string
	 */
	public function render( array $parameters ) {
		// Get the instance of the service class. 
		$service = MapsMappingServices::getServiceInstance( $parameters['mappingservice'], $this->getName() );
		
		// Get an instance of the class handling the current parser hook and service. 
		$mapClass = $service->getFeatureInstance( $this->getName() );

		return $mapClass->renderMap( $parameters, $this->parser );
	}
	
	/**
	 * Returns the parser function otpions.
	 * @see ParserHook::getFunctionOptions
	 * 
	 * @since 0.7
	 * 
	 * @return array
	 */
	protected function getFunctionOptions() {
		return array(
			'noparse' => true,
			'isHTML' => true
		);
	}

	/**
	 * @see ParserHook::getMessage()
	 * 
	 * @since 1.0
	 */
	public function getMessage() {
		return 'maps-displaymap-description';
	}		
	
}