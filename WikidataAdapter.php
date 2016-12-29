<?php
if( !defined( 'MEDIAWIKI' ) ) {
	die("This file is an extension to the MediaWiki software and cannot be used standalone.\n");
}

//self executing anonymous function to prevent global scope assumptions
call_user_func( function() {

	# Parser functions for WikidataAdapter
	# Based on ExternalData
	$GLOBALS['wgExtensionCredits']['parserhook'][] = array(
			'path' => __FILE__,     // Magic so that svn revision number can be shown
			'name' => "WikidataAdapter",
			'description' => "Retrieve information from Wikidata",
			'version' => '0.1.0', 
			'author' => array("Toniher", "Yaron Koren", "et al."),
			'url' => "https://www.mediawiki.org/wiki/User:Toniher",
	);
	
	# Define a setup function
	$GLOBALS['wgHooks']['ParserFirstCallInit'][] = 'wfWikidataAdapterParserFunction_Setup';
	# Add a hook to initialise the magic word
	$GLOBALS['wgHooks']['LanguageGetMagic'][]       = 'wfWikidataAdapterParserFunction_Magic';
	
	
	# A var to ease the referencing of files
	$dir = dirname(__FILE__) . '/';
	$GLOBALS['wgAutoloadClasses']['WikidataAdapter'] = $dir . 'WikidataAdapter_body.php';
	
	# Store values
	# All these parameters should be in LocalSettings.php
	
	$GLOBALS['wgWikidataAdapterValues'] = array();
	
	
	$GLOBALS['wgWikidataAdapterExpose'] = array(
		"gene" => array(
			"db" => array(
				"server" => "localhost",
				"type" => "mysql",
				"name" => "extraDB",
				"username" => "myuser",
				"password" => "mypasswd",
				"flags" => "",
				"tableprefix" => ""	
			),
			"query" => "SELECT distinct(c.gene_alias) AS gene_alias
				, n.gene_name AS gene_name
				, c.chromosome AS chromosome
				, c.start AS start
				, c.end AS end
				, c.strand AS strand
				, c.source AS source
				, c.assembly AS assembly
				, c.annotation AS annotation
				, c.taxon_id AS taxonid
				, c.coding_nat AS codingnature
				FROM coordinates c
				LEFT
				JOIN gene_names n
				ON n.gene_alias = c.gene_alias
				WHERE c.gene_alias = '#P1' group by gene_alias;",
			"propmap" => array( // Mapping to SMW properties
				"gene_alias" => "Has Alias",
				"gene_name" => "Has Name",
				"chromosome" => "Is in Chromosome",
				"start" => "Has Location Start",
				"end" => "Has Location End",
				"strand" => "Is in Strand",
				"source" => "Has Source",
				"assembly" => "Is in Assembly",
				"annotation" => "Has Annotation",
				"codingnature" => "Is Coding"
			)
		)
	);

});

function wfWikidataAdapterParserFunction_Setup( &$parser ) {
	$parser->setFunctionHook( 'WikidataAdapter', 'WikidataAdapter::executeWikidataAdapterret', SFH_OBJECT_ARGS );
	$parser->setFunctionHook( 'WikidataAdapter_value', 'WikidataAdapter::doExternalValue', SFH_OBJECT_ARGS );
	$parser->setFunctionHook( 'WikidataAdapter_count', 'WikidataAdapter::doCountValue', SFH_OBJECT_ARGS );
	$parser->setFunctionHook( 'WikidataAdapter_exists', 'WikidataAdapter::doExistsValue', SFH_OBJECT_ARGS );
	$parser->setFunctionHook( 'WikidataAdapter_table', 'WikidataAdapter::doForExternalTable', SFH_OBJECT_ARGS );
	$parser->setFunctionHook( 'WikidataAdapter_store_table', 'WikidataAdapter::doStoreExternalTable' );
	$parser->setFunctionHook( 'WikidataAdapter_fstore_table', 'WikidataAdapter::doFlexStoreExternalTable' );
	$parser->setFunctionHook( 'WikidataAdapter_clear', 'WikidataAdapter::doClearExternalData' );
	return true;
}

function wfWikidataAdapterParserFunction_Magic( &$magicWords, $langCode ) {
	$magicWords['WikidataAdapter'] = array( 0, 'WikidataAdapter' );
	$magicWords['WikidataAdapter_value'] = array( 0, 'WikidataAdapter_value' );
	$magicWords['WikidataAdapter_count'] = array( 0, 'WikidataAdapter_count' );
	$magicWords['WikidataAdapter_exists'] = array( 0, 'WikidataAdapter_exists' );
	$magicWords['WikidataAdapter_table'] = array( 0, 'WikidataAdapter_table' );
	$magicWords['WikidataAdapter_store_table'] = array( 0, 'WikidataAdapter_store_table' );
	$magicWords['WikidataAdapter_fstore_table'] = array( 0, 'WikidataAdapter_fstore_table' );
	$magicWords['WikidataAdapter_clear'] = array( 0, 'WikidataAdapter_clear' );
	# unless we return true, other parser functions extensions won't get loaded.
	return true;
}



