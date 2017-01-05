<?php
if (!defined('MEDIAWIKI')) { die(-1); } 


class WikidataAdapter {

	public static function executeWikidataAdapterret ( $parser, $frame, $args ) {
			
		global $wgWikidataAdapter;
		global $wgWikidataAdapterExpose; // Take the configuration
		global $wgWikidataAdapterValues; // What we do store

		$source = "";
		$set = "";

		if ( isset( $args[0])  && !empty( $args[0] ) ) {
			$set = trim( $frame->expand( $args[0] ) );

			if ( isset( $args[1])  && !empty( $args[1] ) ) {
				$source = trim( $frame->expand( $args[1] ) );

				$vars = explode( ",", $source );

				// Specific DB
				$dbtype = $wgWikidataAdapter["type"];
				$dbserver = $wgWikidataAdapter["server"];
				$dbuser = $wgWikidataAdapter["username"];
				$dbpassword = $wgWikidataAdapter["password"];
				$dbname = $wgWikidataAdapter["name"];
				$dbflags = $wgWikidataAdapter["flags"];
				$dbtablePrefix = $wgWikidataAdapter["tableprefix"];
				
				if ( array_key_exists( $set, $wgWikidataAdapterExpose ) ) {
					
					
					if ( array_key_exists( "db", $wgWikidataAdapterExpose[$set] ) ) {

						if ( array_key_exists( "type", $wgWikidataAdapterExpose[$set]["db"] ) ) {
							$dbtype = $wgWikidataAdapterExpose[$set]["db"]["type"];
						}
						if ( array_key_exists( "server", $wgWikidataAdapterExpose[$set]["db"] ) ) {
							$dbserver = $wgWikidataAdapterExpose[$set]["db"]["server"];
						}
						if ( array_key_exists( "username", $wgWikidataAdapterExpose[$set]["db"] ) ) {
							$dbuser = $wgWikidataAdapterExpose[$set]["db"]["username"];
						}
						if ( array_key_exists( "password", $wgWikidataAdapterExpose[$set]["db"] ) ) {
							$dbpassword = $wgWikidataAdapterExpose[$set]["db"]["password"];
						}
						if ( array_key_exists( "name", $wgWikidataAdapterExpose[$set]["db"] ) ) {
							$dbname = $wgWikidataAdapterExpose[$set]["db"]["name"];
						}
						if ( array_key_exists( "flags", $wgWikidataAdapterExpose[$set]["db"] ) ) {
							$dbflags = $wgWikidataAdapterExpose[$set]["db"]["flags"];
						}
						if ( array_key_exists( "tableprefix", $wgWikidataAdapterExpose[$set]["db"] ) ) {
							$dbtablePrefix = $wgWikidataAdapterExpose[$set]["db"]["tableprefix"];
						}
						
					}
					
					// Database definition
					$db = DatabaseBase::factory( $dbtype,
							array(
							'host' => $dbserver,
							'user' => $dbuser,
							'password' => $dbpassword,
							// Both 'dbname' and 'dbName' have been
							// used in different versions.
							'dbname' => $dbname,
							'dbName' => $dbname,
							'flags' => $dbflags,
							'tablePrefix' => $dbtablePrefix,
							)
					);
					
					if ( array_key_exists( "query", $wgWikidataAdapterExpose[$set] ) ) {
						$query = $wgWikidataAdapterExpose[$set]["query"];
	
						$query = self::process_query( $query, $vars );
						
						self::query_store_DB( $db, $query, $set, $wgWikidataAdapterValues );
						// var_dump( $wgWikidataAdapterValues );
					}
				}
			}
		}

		return;
	}

	private static function process_query( $query, $vars ) {

		$iter = 1;

		foreach ( $vars as $var ) {

			$subst = "#P".$iter;
			$query = str_replace( $subst, $var, $query );

			$iter++;

		}

		return $query;

	}

	private static function query_store_DB( $db, $query, $set, &$wgWikidataAdapterValues ) {

		$result = $db->query( $query, 'WikidataAdapter::query_store_DB' );

		if ( $result ) {

			if ( $result->numRows() > 0 ) {

				foreach( $result as $row ) {
					$object = array();
					foreach ( $row as $key => $value ) {
						$fkey = $key;
						if ( ! empty( $set ) ) {
							$fkey = $set.".".$key;
						}
						$object[$fkey] = $value;
					}
					array_push( $wgWikidataAdapterValues, $object );
				}
			} 
		}

		return true;
	}


	private static function removeNull ( $value ) {
		
		if ($value == "NULL") {
				
				$value = "";
		}
		
		// Let's strip tags -> TODO: Check if actually it's the case that not
		$value = strip_tags( $value );
		return($value);
	}


	/**
	 * Get the specified index of the array for the specified local
	 */
	public static function doExternalValue( $parser, $frame, $args ) {
		global $wgWikidataAdapterValues;
		$output = "";

		if ( isset( $args[0] ) && !empty( $args[0] ) ) {
			$var = trim( $frame->expand( $args[0] ) );

			$values = array();

			foreach ( $wgWikidataAdapterValues as $entry ) {

				if ( array_key_exists( $var, $entry ) ) {
					if ( !empty( $entry[$var] ) ) {
						array_push( $values, $entry[$var] );
					}
				}
			}
			$output = implode( "*", $values );

			if ( $output == "" && isset( $args[1] ) && !empty( $args[1] ) ) {
				$output = trim( $frame->expand( $args[1] ) );
			}

			if ( !empty($output) && isset( $args[2] ) && !empty( $args[2] ) ) {
				$formatted = trim( $frame->expand( $args[2] ) );

				$formatted_output = str_replace( "#P1", $output, $formatted ) ;

				$output = $formatted_output;
			}

		}

		return $output;
		#return $parser->insertStripItem( $output, $parser->mStripState );
	}


	/**
	 * Get the specified index of the array for the specified local
	 */
	public static function doCountValue( $parser, $frame, $args ) {
		global $wgWikidataAdapterValues;
		$output = 0;

		if ( isset( $args[0])  && !empty( $args[0] ) ) {
			$var = trim( $frame->expand( $args[0] ) );

			$values = array();

			foreach ( $wgWikidataAdapterValues as $entry ) {

				if ( array_key_exists( $var, $entry ) ) {
					if ( !empty( $entry[$var] ) ) {
						array_push( $values, $entry[$var] );
					}
				}
			}
			$output = count( $values );
		}

		return $output;
		#return $parser->insertStripItem( $output, $parser->mStripState );
	}

	/**
	 * Get the specified index of the array for the specified local
	 */
	public static function doExistsValue( $parser, $frame, $args ) {
		global $wgWikidataAdapterValues;
		$output = 0;

		if ( isset( $args[0])  && !empty( $args[0] ) ) {
			$var = trim( $frame->expand( $args[0] ) );

			$values = array();

			foreach ( $wgWikidataAdapterValues as $entry ) {

				if ( array_key_exists( $var, $entry ) ) {
					if ( !empty( $entry[$var] ) ) {
						array_push( $values, $entry[$var] );
					}
				}
			}
			$output = count( $values );
		}

		if ( $output > 0 ) {
			return isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
		} else {
			return isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : '';
		}

	}


	/**
	 * Get the specified index of the array for the specified local
	 * variable retrieved by #get_external_data
	 */
	private static function getIndexedValue( $var, $i ) {

		global $wgWikidataAdapterValues;
		if ( array_key_exists( $var, $wgWikidataAdapterValues[$i] ) ) {
			return $wgWikidataAdapterValues[$i][$var];
		}
		else {
			return '';
		}
	}
 
	/**
	 * Render the #for_external_table parser function
	 */
	public static function doForExternalTable( $parser, $frame, $args ) {

		global $wgWikidataAdapterValues;

		$output = "";

		if ( isset( $args[0])  && !empty( $args[0] ) ) {

			$expression = trim( $frame->expand( $args[0] ) );

			// get the variables used in this expression, get the number
			// of values for each, and loop through 
			$matches = array();
			preg_match_all( '/{{{([^}]*)}}}/', $expression, $matches );
			$variables = $matches[1];
			$num_loops = 0;

			$num_loops = max( $num_loops, count( $wgWikidataAdapterValues ) );
			// var_dump( $wgWikidataAdapterValues );

			for ( $i = 0; $i < $num_loops; $i++ ) {

				$cur_expression = $expression;
				$allempty = true; // We skip lines with no value

				foreach ( $variables as $variable ) {

					$prevariable = $variable;
					$variable = preg_replace('/@\w+\s*=\s*\w+\s*/','', $variable);

					$value = self::getIndexedValue( $variable , $i );
					$listpar = explode("@", $prevariable);
					
					$prefix = "";
					
					$template = "";
					
					$scientific = 0;

					if ( count( $listpar ) > 1) {
							
						for($k=1; $k<count($listpar); $k=$k+1) {
								
							$extra = trim($listpar[$k]);
							$extrav = explode('=', $extra, 2);
							
							if (trim($extrav[0]) == 'prefix') {
								$prefix = trim($extrav[1]);
							}
							if (trim($extrav[0]) == 'template') {
								$template = trim($extrav[1]);
							}
							if (trim($extrav[0]) == 'scientific') {
								$scientific = (int) trim($extrav[1]);
							}
						}
					}
					
					if ( !empty( $template ) ) {
						if ( $value != '' ) {
							$templatevar = "{{".$template."|".$value."}}";
							$value = trim( $parser->recursivePreprocess( $templatevar ) );
						}
					}
					
					// Add Prefix if available
					if ( !empty ( $prefix) ) {
							$value = $prefix.":".$value;
					}
					
					if ( $scientific > 0 ) {
						$value = (float) $value;
						if ( $value < 1 ) { 
							$numDecimals = self::numberOfDecimals( $value );
						
							if ( $numDecimals >= $scientific ) {
								$value = self::scientificNotation( $value );
							}
						}
					}
					
					$cur_expression = str_replace( '{{{' . $prevariable . '}}}', self::removeNull($value), $cur_expression );

					if ( !empty( $value ) ) {
						$allempty = false;
					}
					
				}

				// Fix if empty value -> This way we avoid to clear so often
				if ( ! $allempty ) {
					$output .= $cur_expression; //TODO: We should parse further here!
				}
			}

		}

		return $output;
	}
	


	/**
	 * Render the #for_external_table parser function
	 */
	public static function doStoreExternalTable( &$parser ) {

		global $wgWikidataAdapterValues;
		global $wgWikidataAdapterExpose;
		global $smwgDefaultStore;

		if ( $smwgDefaultStore != 'SMWSQLStore3' && ! class_exists( 'SIOHandler' ) ) {
			// If SQLStore3 is not installed, we need SIO.
			return EDUtils::formatErrorMessage( 'Semantic Internal Objects is not installed' );
		}

		$params = func_get_args();
		array_shift( $params ); // we already know the $parser...

		$expression = implode( '|', $params ); // Let's put all params together

		// get the variables used in this expression, get the number
		// of values for each, and loop through 
		$matches = array();
		preg_match_all( '/{{{([^}]*)}}}/', $expression, $matches );
		$variables = $matches[1];
		$num_loops = 0;
		
		$customProps = self::assign_custom_props( array_slice( $params, 1 ) );

		$num_loops = max( $num_loops, count( $wgWikidataAdapterValues ) );

		for ( $i = 0; $i < $num_loops; $i++ ) {

			$internal = array();
			// We assign here non parameter ones
			$external = self::assign_non_parameters( $params );

			foreach ( $variables as $variable ) {

				$prevariable = $variable;
				$variable = preg_replace('/@\w+\s*=\s*\w+\s*/','', $variable);
				$value = self::getIndexedValue( $variable , $i );
				$listpar = explode("@", $prevariable);
				
				$prefix = "";

				$template = "";

				if ( count( $listpar ) > 1) {
						
					for($k=1; $k<count($listpar); $k=$k+1) {
							
						$extra = trim($listpar[$k]);
						$extrav = explode('=', $extra, 2);
						
						if (trim($extrav[0]) == 'prefix') {
							$prefix = trim($extrav[1]);
						}
						if (trim($extrav[0]) == 'template') {
							$template = trim($extrav[1]);
						}

					}
				}
				
				if ( !empty( $template ) ) {
					if ( $value != '' ) {
						$templatevar = "{{".$template."|".$value."}}";
						$value = trim( $parser->recursivePreprocess( $templatevar ) );
					}
				}

				// Add Prefix if available
				if ( !empty ( $prefix) ) {
						$value = $prefix.":".$value;
				}
				

				// TODO: We should parse further value here

				// Here we do the mapping
				$partsvar = explode( ".", $listpar[0], 2 ); // We got the one without @

				if ( count( $partsvar ) == 2 ) {
					
					if ( ! empty( $value ) ) {
						if ( array_key_exists( $partsvar[0].".".$partsvar[1], $customProps ) ) {
							$internal[ $customProps[ $partsvar[0].".".$partsvar[1]] ] = $value;
						}
						else {
							if ( array_key_exists( $partsvar[1], $wgWikidataAdapterExpose[$partsvar[0]]["propmap"] ) ) {
								$propExposed = $wgWikidataAdapterExpose[$partsvar[0]]["propmap"][$partsvar[1]];
								$internal[$propExposed] = $value;
							}
						}
					}
				}
				
			}

			// If no keys, skip
			if ( count( $internal ) == 0 ) {
				continue;
			}
			
			// We add external to internal. Makes no sense if only external
			$internal = array_merge( $internal, $external );

			if ( empty( $params[0] ) ) {
				
				// Submitting to Object
				if ( $smwgDefaultStore == 'SMWSQLStore3' ) {
					self::callObject( $parser, $internal );
				}
				continue;
			}

			array_unshift( $internal, $params[0] );

			// If SQLStore3 is being used, we can call #subobject -
			// that's what #set_internal would call anyway, so
			// we're cutting out the middleman.
			if ( $smwgDefaultStore == 'SMWSQLStore3' ) {
				self::callSubobject( $parser, $internal );
				continue;
			}

			// Add $parser to the beginning of the $params array,
			// and pass the whole thing in as arguments to
			// doSetInternal, to mimic a call to #set_internal.
			array_unshift( $internal, $parser );
			// As of PHP 5.3.1, call_user_func_array() requires that
			// the function params be references. Workaround via
			// http://stackoverflow.com/questions/2045875/pass-by-reference-problem-with-php-5-3-1
			$refParams = array();
			foreach ( $internal as $key => $value ) {
				$refParams[$key] = &$internal[$key];
			}
			call_user_func_array( array( 'SIOHandler', 'doSetInternal' ), $refParams );

		}

		return null;
	}

	/**
	 * Render the #for_external_table parser function
	 */
	public static function doFlexStoreExternalTable( &$parser ) {

		global $wgWikidataAdapterValues;
		global $wgWikidataAdapterExpose;
		global $smwgDefaultStore;

		if ( $smwgDefaultStore != 'SMWSQLStore3' && ! class_exists( 'SIOHandler' ) ) {
			// If SQLStore3 is not installed, we need SIO.
			return EDUtils::formatErrorMessage( 'Semantic Internal Objects is not installed' );
		}

		$params = func_get_args();
		array_shift( $params ); // we already know the $parser...

		$expression = implode( '|', $params ); // Let's put all params together

		$num_loops = 0;
		
		$customProps = self::assign_custom_props_all( array_slice( $params, 1 ) );

		$num_loops = max( $num_loops, count( $wgWikidataAdapterValues ) );

		for ( $i = 0; $i < $num_loops; $i++ ) {

			$internal = self::replaceBioIndex( $customProps, $wgWikidataAdapterValues[$i], $parser );

			// If no keys, skip
			if ( count( $internal ) == 0 ) {
				continue;
			}

			if ( empty( $params[0] ) ) {
				
				// Submitting to Object
				if ( $smwgDefaultStore == 'SMWSQLStore3' ) {
					self::callObject( $parser, $internal );
				}
				continue;
			}

			array_unshift( $internal, $params[0] );

			// If SQLStore3 is being used, we can call #subobject -
			// that's what #set_internal would call anyway, so
			// we're cutting out the middleman.
			if ( $smwgDefaultStore == 'SMWSQLStore3' ) {
				self::callSubobject( $parser, $internal );
				continue;
			}

			// Add $parser to the beginning of the $params array,
			// and pass the whole thing in as arguments to
			// doSetInternal, to mimic a call to #set_internal.
			array_unshift( $internal, $parser );
			// As of PHP 5.3.1, call_user_func_array() requires that
			// the function params be references. Workaround via
			// http://stackoverflow.com/questions/2045875/pass-by-reference-problem-with-php-5-3-1
			$refParams = array();
			foreach ( $internal as $key => $value ) {
				$refParams[$key] = &$internal[$key];
			}
			call_user_func_array( array( 'SIOHandler', 'doSetInternal' ), $refParams );

		}

		return null;
	}


	private static function replaceBioIndex( $props, $values, $parser ) {

		$internal = array();

		// get the variables used in this expression, get the number
		// of values for each, and loop through 
		$matches = array();
		$expression = implode( '|', array_keys( $props ) ); // Let's put all params together
		preg_match_all( '/{{{([^}]*)}}}/', $expression, $matches );
		$variables = $matches[1];

		foreach ( $props as $key => $val ) {
		
			foreach ( $variables as $variable ) {
				$key = str_replace( $variable, $values[$variable], $key );
				$key = str_replace( "{{{", "", $key );
				$key = str_replace( "}}}", "", $key );
				$internal[$val] = $parser->recursivePreprocess( $key );
			}

		}

		return $internal;

	}

	/** Assign custom values **/
	private static function assign_non_parameters( $params ) {

		$array = array();

		foreach ( $params as $param ) {
			if ( preg_match( '/{{{[^}]*}}}/', $param ) != 1 ) {
				$paramv = explode('=', $param, 2);
				if ( count( $paramv ) == 2 ) {
					$prop = trim( $paramv[0] );
					$val = trim( $paramv[1] );

					// TODO: More processing of val needed
					$array[ $prop ] = $val;
				}
			}
		}

		return $array;
	}

	/**
	 * Based on Semantic Internal Objects'
	 * SIOSubobjectHandler::doSetInternal().
	 */
	public static function callSubobject( $parser, $params ) {
		// This is a hack, since SMW's SMWSubobject::render() call is
		// not meant to be called outside of SMW. However, this seemed
		// like the better solution than copying over all of that
		// method's code. Ideally, a true public function can be
		// added to SMW, that handles a subobject creation, that this
		// code can then call.

		$subobjectArgs = array( &$parser );
		// Blank first argument, so that subobject ID will be
		// an automatically-generated random number.
		$subobjectArgs[1] = '';
		// "main" property, pointing back to the page.
		$mainPageName = $parser->getTitle()->getText();
		$mainPageNamespace = $parser->getTitle()->getNsText();
		if ( $mainPageNamespace != '' ) {
			$mainPageName = $mainPageNamespace . ':' . $mainPageName;
		}
		$subobjectArgs[2] = $params[0] . '=' . $mainPageName;

		foreach ( $params as $i => $value ) {
			if ( $i != "0" ) {
				$subobjectArgs[] = $i . '=' . $value;
			}
		}

		if ( class_exists( 'SMW\SubobjectParserFunction' ) ) {
			// SMW 1.9+
			$instance = \SMW\ParserFunctionFactory::newFromParser( $parser )->getSubobjectParser();
			return $instance->parse( new SMW\ParserParameterFormatter( $subobjectArgs ) );
		} elseif ( class_exists( 'SMW\SubobjectHandler' ) ) {
			// Old version of SMW 1.9 - can be removed at some point
			call_user_func_array( array( 'SMW\SubobjectHandler', 'render' ), $subobjectArgs );
		} elseif ( class_exists( 'SMW\SubobjectParser' ) ) {
			// Old version of SMW 1.9 - can be removed at some point
			call_user_func_array( array( 'SMW\SubobjectParser', 'render' ), $subobjectArgs );
		} elseif ( class_exists( 'SMW\Subobject' ) ) {
			// Old version of SMW 1.9 - can be removed at some point
			call_user_func_array( array( 'SMW\Subobject', 'render' ), $subobjectArgs );
		} else {
			// SMW 1.8
			call_user_func_array( array( 'SMWSubobject', 'render' ), $subobjectArgs );
		}
		return;
	}

	/**
	 * Based on Semantic Internal Objects'
	 * SIOSubobjectHandler::doSetInternal().
	 */
	public static function callObject( $parser, $params ) {

		if ( class_exists( 'SMW\ParserData' ) ) {
			// SMW 1.9+
			
			$parserData = new \SMW\ParserData( $parser->getTitle(), $parser->getOutput() );
			$subject = $parserData->getSubject();
			
			foreach ( $params as $property => $value) {
			
				$dataValue = \SMW\DataValueFactory::getInstance()->newPropertyValue( $property , $value, null, $subject);
				$parserData->addDataValue( $dataValue );
			}
			
			$parserData->updateOutput();
		}
		return;
	}
	
	/**
	 * Assign custom properties
	 */
	public static function assign_custom_props( $array ) {
		
		$keysProps = array();
		
		foreach ( $array as $element ) {
			
			$assign = explode( "=", $element, 2 );
			if ( count( $assign ) == 2 ) {
				
				$prop = trim( $assign[0] );
				$valraw = trim( $assign[1] );
				preg_match( '/{{{([^}]*)}}}/', $valraw, $valarr );
				if ( count( $valarr ) == 2 ) {
					$keysProps[ $valarr[1] ] = $prop;
				}
			}
		}
		
		return $keysProps;
	}

	/**
	 * Assign custom properties
	 */
	public static function assign_custom_props_all( $array ) {
		
		$keysProps = array();
		
		foreach ( $array as $element ) {
			
			$assign = explode( "=", $element, 2 );
			if ( count( $assign ) == 2 ) {
				
				$prop = trim( $assign[0] );
				$valraw = trim( $assign[1] );
				$keysProps[ $valraw ] = $prop;
			}
		}
		
		return $keysProps;
	}

	/**
	 * Render the #clear_external_data parser function -> Important for every page so it can be used
	 */
	static function doClearExternalData( &$parser ) {
		global $wgWikidataAdapterValues;
		$wgWikidataAdapterValues = array();
	}

	private static function numberOfDecimals($value) {
		if ((int)$value == $value) {
			return 0;
		}
		else if (! is_numeric($value)) {
			// throw new Exception('numberOfDecimals: ' . $value . ' is not a number!');
			return false;
		}
		
		return strlen($value) - strrpos($value, '.') - 1;
	}

	private static function scientificNotation($val){
		$exp = floor(log($val, 10));
		return sprintf('%.2fE%+03d', $val/pow(10,$exp), $exp);
	}
	
	
	public static function addTable( $updater ) {
		$dbt = $updater->getDB()->getType();
		$file = __DIR__ . "/sql/WikidataAdapter.$dbt";
		if ( file_exists( $file ) ) {
			$updater->addExtensionUpdate( array( 'addTable', 'wda', $file, true ) );
		} else {
			throw new MWException( "WikidataAdapter does not support $dbt." );
		}
		return true;
	}
	
}
