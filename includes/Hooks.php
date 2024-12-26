<?php
declare(strict_types=1);

namespace MediaWiki\Extension\GoogleRichCards;

use MediaWiki\MediaWikiServices;
use OutputPage;
use Parser;
use Skin;

/**
 * GoogleRichCards
 * Google Rich Cards metadata generator
 *
 * PHP version 8.1
 *
 * @category Extension
 * @package  GoogleRichCards
 * @author   Igor Shishkin <me@teran.ru>, Drolerina
 * @license  GPL http://www.gnu.org/licenses/gpl.html
 * @link     https://github.com/arnomaris/mediawiki-googlerichcards
 */
class Hooks {

  /**
   * Hook: BeforePageDisplay
   * 
   * Handle meta elements and page title modification.
   *
   * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
   * @param OutputPage $out  The output page.
   * @param Skin       $skin The current skin.
   * @return bool
   */
  public static function onBeforePageDisplay( OutputPage $out, Skin $skin ): bool {
      $config = MediaWikiServices::getInstance()->getMainConfig();

      if ( $config->get( 'wgGoogleRichCardsAnnotateArticles' ) ) {
          $article = Article::getInstance();
          $article->render( $out );
      }

    //   if ( $config->get( 'wgGoogleRichCardsAnnotateEvents' ) ) {
    //       $event = Event::getInstance();
    //       $event->render( $out );
    //   }

    //   if ( $config->get( 'wgGoogleRichCardsAnnotateWebSite' ) ) {
    //       $website = WebSite::getInstance();
    //       $website->render( $out );
    //   }

      return true;
  }

  /**
   * Hook: ParserFirstCallInit
   * 
   * Handle parser tag hook registration: <event> ... </event>
   *
   * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
   * @param Parser $parser The global Parser object.
   * @return void
   */
//   public static function onParserFirstCallInit( Parser $parser ): void {
//       $config = MediaWikiServices::getInstance()->getMainConfig();

//       if ( $config->get( 'wgGoogleRichCardsAnnotateEvents' ) ) {
//           $event = Event::getInstance();

//           // Register a <event> parser hook which calls $event->parse()
//           $parser->setHook( 'event', [ $event, 'parse' ] );
//       }
//   }
// }

