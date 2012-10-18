<?php

/**
 * SlimboxThumbs extension /REWRITTEN/
 * Originally http://www.mediawiki.org/wiki/Extension:SlimboxThumbs
 * Now it does the same, but the code is totally different
 * Required MediaWiki: 1.13+
 *
 * This extension includes a copy of Slimbox.
 * It has one small modification: caption is animated together
 * with image container, instead of original annoying consecutive animation.
 * Also "autoloader" is removed from slimbox2.js, and there is an additional
 * slimboxthumbs.js file.
 *
 * You can however get your own copy of Slimbox and use it by replacing the
 * included one: http://www.digitalia.be/software/slimbox2
 *
 * @license GNU GPL 3.0 or later: http://www.gnu.org/licenses/gpl.html
 * CC-BY-SA should not be used for software, moreover it's incompatible with GPL, and MW is GPL.
 *
 * @file SlimboxThumbs.php
 *
 * @author Vitaliy Filippov <vitalif@mail.ru>
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

define( 'SlimboxThumbs_VERSION', '2012-09-17' );

// Register the extension credits.
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'SlimboxThumbs',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SlimboxThumbs',
	'author' => array(
		'[http://yourcmc.ru/wiki/User:VitaliyFilippov Vitaliy Filippov], ' .
		'[http://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw].'
	),
	'descriptionmsg' => 'slimboxthumbs-desc',
	'version' => SlimboxThumbs_VERSION
);

$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['SlimboxThumbs'] = $dir . 'SlimboxThumbs.i18n.php';
$wgHooks['BeforePageDisplay'][] = 'efSBTAddScripts';
$wgAjaxExportList[] = 'efSBTGetImageSizes';

// Ajax handler to get image sizes
function efSBTGetImageSizes( $names ) {
	$result = array();
	foreach ( explode( ':', $names ) as $name ) {
		if ( !isset( $result[$name] ) ) {
			$title = Title::makeTitle( NS_FILE, $name );
			if ( $title && $title->userCanRead() ) {
				$file = wfFindFile( $title );
				if ( $file && $file->getWidth() ) {
					$result[ $name ] = array( $file->getWidth(), $file->getHeight(), $file->getFullUrl() );
				}
			}
		}
	}
	return json_encode( $result );
}

// Adds javascript files and stylesheets.
function efSBTAddScripts( $out ) {
	global $wgVersion, $wgExtensionAssetsPath, $wgUploadPath, $wgServer, $wgScriptPath, $wgArticlePath;

	$mw16 = version_compare( $wgVersion, '1.16', '>=' );
	$useExtensionPath = $mw16 && isset( $wgExtensionAssetsPath ) && $wgExtensionAssetsPath;
	$eDir = ( $useExtensionPath ? $wgExtensionAssetsPath : $wgScriptPath . '/extensions' );
	$eDir .= '/SlimboxThumbs/slimbox';

	if ( $mw16 && substr( $wgVersion, 0, 4 ) != '1.16' ) {
		$out->includeJQuery();
	} else {
		$out->addScript(
			'<script type="text/javascript"'.
			' src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>' . "\n"
		);
	}

	$re = str_replace( '\\$1', '[^:]+:(.*)', preg_quote( $wgArticlePath ) );

	$out->addScript( '<script type="text/javascript" src="' . $eDir . '/js/slimbox2.js"></script>' . "\n" );
	$out->addExtensionStyle( $eDir . '/css/slimbox2.css', 'screen' );
	$out->addScript( '<script type="text/javascript" src="' . $eDir . '/slimboxthumbs.js"></script>' . "\n" );
	$out->addInlineScript( "addHandler( window, 'load', function() {".
		"makeSlimboxThumbs( jQuery, \"".addslashes( $re ).
		"\", \"".addslashes( $wgServer.$wgScriptPath )."\" ); } );" );

	return true;
}
