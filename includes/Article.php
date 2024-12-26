<?php
declare(strict_types=1);

namespace MediaWiki\Extension\GoogleRichCards;

use DateTime;
use MediaWiki\MediaWikiServices;
use OutputPage;
use RequestContext;
use Title;
use MediaWiki\Revision\RevisionRecord;

/**
 * GoogleRichCards
 * Google Rich Cards metadata generator for Articles
 *
 * PHP version 8.1
 *
 * @package  GoogleRichCards
 * @author   Igor Shishkin <me@teran.ru>, Drolerina
 * @license  GPL http://www.gnu.org/licenses/gpl.html
 */
class Article {
    /**
     * @var self|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * @var string Site name (was $wgSitename)
     */
    private string $sitename;

    /**
     * @var string Server URL (was $wgServer)
     */
    private string $server;

    /**
     * @var string Path to logo (was $wgLogo)
     */
    private string $logo;

    /**
     * @var Title|null The current Title object
     */
    private ?Title $title = null;

    /**
     * Private constructor (Singleton pattern)
     */
    private function __construct() {
        // Retrieve config from MediaWikiServices
        $config = MediaWikiServices::getInstance()->getMainConfig();

        // Adjust these if your local config uses different names
        $this->sitename = (string) $config->get( 'Sitename' );
        $this->server   = (string) $config->get( 'Server' );
        $this->logo     = (string) $config->get( 'Logo' );

        // Grab the current Title from the RequestContext
        $this->title = RequestContext::getMain()->getTitle();
    }

    /**
     * Singleton accessor
     *
     * @return self
     */
    public static function getInstance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Return page creation time (in ISO 8601) or "0"
     *
     * @return string
     */
    private function getCreationTime(): string {
        if ( !$this->title ) {
            return '0';
        }
        // Use RevisionLookup to get the first revision
        $revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
        $firstRevision = $revisionLookup->getFirstRevision(
            $this->title->toPageIdentity()
        );

        if ( $firstRevision instanceof RevisionRecord ) {
            $timestamp = $firstRevision->getTimestamp(); // 'YYYYMMDDHHMMSS'
            $dt = DateTime::createFromFormat( 'YmdHis', $timestamp );
            if ( $dt ) {
                return $dt->format( 'c' ); // e.g. "2023-01-01T12:34:56+00:00"
            }
        }
        return '0';
    }

    /**
     * Return page modification time (in ISO 8601) or "0"
     *
     * @return string
     */
    private function getModificationTime(): string {
        if ( !$this->title ) {
            return '0';
        }
        // Use RevisionLookup to get the *latest* revision as "modified" time
        $revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
        $latestRevision = $revisionLookup->getRevisionByTitle( $this->title );

        if ( $latestRevision instanceof RevisionRecord ) {
            $timestamp = $latestRevision->getTimestamp(); // 'YYYYMMDDHHMMSS'
            $dt = DateTime::createFromFormat( 'YmdHis', $timestamp );
            if ( $dt ) {
                return $dt->format( 'c' );
            }
        }
        return '0';
    }

    /**
     * Return first image (and its resolution) from the current page
     * Fallback to the site logo if no image found
     *
     * @param OutputPage $out The OutputPage instance.
     * @return array [string $imageUrl, int $width, int $height]
     */
    public function getIllustration( OutputPage $out ): array {
        $repoGroup = MediaWikiServices::getInstance()->getRepoGroup();
        $imageSearchOptions = $out->getFileSearchOptions();
        $image = key( $imageSearchOptions );

        if ( $image ) {
            $file = $repoGroup->findFile( $image );
            if ( $file ) {
                return [
                    $file->getFullURL(),
                    $file->getWidth() ?? 0,
                    $file->getHeight() ?? 0
                ];
            }
        }

        // Fallback: use site logo if no suitable file found
        return [
            $this->server . $this->logo,
            135, // default width
            135  // default height
        ];
    }

    /**
     * Render <script type="application/ld+json"> with Article metadata
     *
     * @param OutputPage $out The OutputPage instance.
     * @return void
     */
    public function render( OutputPage $out ): void {
        if ( !$this->title || !$this->title->isContentPage() ) {
            return;
        }

        // Acquire creation & modification timestamps
        $createdTimestamp  = $this->getCreationTime();
        $modifiedTimestamp = $this->getModificationTime();

        // Use RevisionLookup to get the author of the first revision
        $revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
        $firstRevision = $revisionLookup->getFirstRevision(
            $this->title->toPageIdentity()
        );

        if ( $firstRevision instanceof RevisionRecord ) {
            $authorName = $firstRevision->getUserIdentity()->getName();
        } else {
            $authorName = 'None';
        }

        // Find any images or fall back to logo
        [ $imageUrl, $imgWidth, $imgHeight ] = $this->getIllustration( $out );

        // Build an array describing the article in Schema.org/JSON-LD
        $article = [
            '@context'         => 'http://schema.org',
            '@type'            => 'NewsArticle',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $this->title->getFullURL(),
            ],
            'author' => [
                '@type' => 'Person',
                'name'  => $authorName,
            ],
            'headline'         => $this->title->getText(),
            'datePublished'    => $createdTimestamp,
            'dateModified'     => $modifiedTimestamp,
            'discussionUrl'    => $this->title->getTalkPage()
                ? $this->title->getTalkPage()->getFullURL()
                : '',
            'image' => [
                '@type'  => 'ImageObject',
                'url'    => $imageUrl,
                'width'  => $imgWidth,
                'height' => $imgHeight,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name'  => $this->sitename,
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => $this->server . $this->logo,
                ],
            ],
            // Using the same text for "description" and "headline" as a fallback.
            'description' => $this->title->getText(),
        ];

        // Inject JSON-LD into <head>
        $out->addHeadItem(
            'GoogleRichCardsArticle',
            '<script type="application/ld+json">' . json_encode( $article ) . '</script>'
        );
    }
}
