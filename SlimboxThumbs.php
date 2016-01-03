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

define( 'SlimboxThumbs_VERSION', '2014-04-01' );

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
	'version' => SlimboxThumbs_VERSION,
);

$dir = dirname( __FILE__ ) . '/';
$wgMessagesDirs['SlimboxThumbs'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['SlimboxThumbs'] = $dir . 'SlimboxThumbs.i18n.php';
$wgHooks['BeforePageDisplay'][] = 'efSBTAddScripts';
$wgAjaxExportList[] = 'efSBTGetImageSizes';
$wgAjaxExportList[] = 'efSBTRemoteThumb';

// Ajax handler to get image sizes
function efSBTGetImageSizes( $names ) {
	$result = array();
	foreach ( explode( ':', $names ) as $name ) {
		if ( !isset( $result[$name] ) ) {
			$title = Title::makeTitle( NS_FILE, $name );
			if ( $title && $title->userCan( 'read' ) ) {
				$file = wfFindFile( $title );
				if ( $file && $file->getWidth() ) {
					$result[ $name ] = array(
						'width' => $file->getWidth(),
						'height' => $file->getHeight(),
						'url' => $file->getFullUrl(),
						'local' => $file->isLocal(),
					);
				}
			}
		}
	}
	return json_encode( $result );
}

// Not really an AJAX function, used to generate thumbnails for non-local images.
// Needed because thumb.php only handles local images.
function efSBTRemoteThumb( $name, $width ) {
	$img = wfFindFile( $name );
	if ( $img && $img->exists() && $img->getTitle()->userCan( 'read' ) &&
		 !$img->isLocal() ) {
		try {
			$thumb = $img->transform( array( 'width' => $width ), 0 );
		} catch( Exception $ex ) {
			$thumb = false;
		}
		if ( $thumb && !$thumb->isError() ) {
			/**
			 * Thumbnails for foreign images have mPath == 'bogus'.
			 * So, what hack is better? Redirect to $thumb->getUrl() or
			 * make up the path using document root and stream the file?
			 * Second will work for closed intranet wikis, so I'm using it by now.
			 *
			 * Redirect code:
			 * header( 'HTTP/1.1 301 Moved Permanently' );
			 * header( 'Location: '.$thumb->getUrl() );
			 */
			global $IP;
			require_once "$IP/includes/StreamFile.php";
			StreamFile::stream( $_SERVER['DOCUMENT_ROOT'].$thumb->getUrl() );
			exit;
		}
	}
	return 'Error generating thumbnail';
}

// Adds javascript files and stylesheets.
function efSBTAddScripts( $out ) {
	global $wgVersion, $wgExtensionAssetsPath, $wgUploadPath, $wgServer, $wgScriptPath, $wgArticlePath;

	$mw16 = version_compare( $wgVersion, '1.16', '>=' );
	$useExtensionPath = $mw16 && isset( $wgExtensionAssetsPath ) && $wgExtensionAssetsPath;
	$eDir = ( $useExtensionPath ? $wgExtensionAssetsPath : $wgScriptPath . '/extensions' );
	$eDir .= '/SlimboxThumbs/slimbox';

	if ( !$mw16 ) {
		$out->addScript(
			'<script type="text/javascript"'.
			' src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>' . "\n"
		);
	}

	$re = str_replace( '\\$1', '[^:]+:(.*)', preg_quote( $wgArticlePath ) );

	$out->addScript( '<script type="text/javascript" src="' . $eDir . '/js/slimbox2.js"></script>' . "\n" );
	$out->addExtensionStyle( $eDir . '/css/slimbox2.css', 'screen' );
	$out->addScript( '<script type="text/javascript" src="' . $eDir . '/slimboxthumbs.js"></script>' . "\n" );
	$out->addInlineScript( "$( window ).on( 'load', function() {".
		"makeSlimboxThumbs( jQuery, \"".addslashes( $re ).
		"\", \"".addslashes( $wgServer.$wgScriptPath )."\" ); } );" );

	return true;
}
