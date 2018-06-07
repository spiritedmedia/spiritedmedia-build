<?php

namespace Pedestal\Posts\Entities\Originals;

use Timber\Timber;

use Pedestal\Registrations\Post_Types\{
    Entity_Types,
    Types
};
use Pedestal\Posts\Attachment;
use Pedestal\Posts\Clusters\{
    Org,
    Person
};

class Factcheck extends Original {

    use \Pedestal\Posts\Emailable;

    protected static $post_type = 'pedestal_factcheck';

    /**
     * Get the CSS classes for the Content in string form
     *
     * @return string String of classes
     */
    public function css_classes_content() {
        return implode( ' ', [
            'c-factcheck',
        ] );
    }

    /**
     * Is the statement a quote?
     *
     * @return boolean
     */
    public function is_statement_quote() {
        if ( 'quote' === $this->get_statement_type() ) {
            return true;
        }
        return false;
    }

    /**
     * Get the type of statement
     *
     * @return string Statement type. Can be either 'quote' or 'summary'
     */
    public function get_statement_type() {
        return $this->get_statement_meta( 'type' );
    }

    /**
     * Get the short statement text
     *
     * @return string
     */
    public function get_statement_short() {
        return $this->get_statement_meta( 'text_short' );
    }

    /**
     * Get the filtered full statement text
     *
     * @return string
     */
    public function get_the_statement_full() {
        return apply_filters( 'the_content', $this->get_statement_full() );
    }

    /**
     * Get the full statement text
     *
     * @return string
     */
    public function get_statement_full() {
        return $this->get_statement_meta( 'text_full' );
    }

    /**
     * Does the speaker have a headshot?
     *
     * @return boolean
     */
    public function has_speaker_image() {
        return (bool) $this->get_statement_speaker_image_html();
    }

    /**
     * Get the speaker's headshot HTML
     *
     * @param  string $size Image size. Defaults to thumbnail.
     * @return string       HTML or empty
     */
    public function get_statement_speaker_image_html( $size = 'thumbnail' ) {
        if ( empty( $this->get_statement_speaker() ) ) {
            return '';
        }
        return $this->get_statement_speaker()->get_featured_image_html( $size );
    }

    /**
     * Get the statement speaker
     *
     * @return obj Person
     */
    public function get_statement_speaker() {
        $speaker = static::get( $this->get_statement_meta( 'speaker' ) );
        if ( $speaker instanceof Person || $speaker instanceof Org ) {
            return $speaker;
        }
        return false;
    }

    /**
     * Get the setting of the statement
     *
     * @return string
     */
    public function get_statement_setting() {
        return $this->get_statement_meta( 'setting' );
    }

    /**
     * Get the statement date in site date format
     *
     * @return string
     */
    public function get_statement_date_str() {
        return $this->get_statement_date( get_option( 'date_format' ) );
    }

    /**
     * Get the date of the statement
     *
     * @return int|string Date in specified format
     */
    private function get_statement_date( $format = 'U' ) {
        $date = $this->get_statement_meta( 'date' );
        if ( empty( $date ) ) {
            return '';
        }
        return date( $format, $date );
    }

    /**
     * Get the statement image
     *
     * @return string HTML
     */
    public function get_statement_img() {
        if ( $this->has_rating() ) {
            return $this->get_meter_html();
        } elseif ( $this->has_speaker_image() ) {
            return $this->get_statement_speaker_image_html();
        }
        return '';
    }

    /**
     * Get the FIAS-compatible meter
     *
     * This meter image is different in that it includes a white rectangular
     * background with an "Our Ruling" header because FIAS won't allow the meter
     * to be anything smaller than the full width of the mobile device.
     *
     * @return string Image caption HTML
     */
    public function get_meter_html_fias() {
        $description = $this->get_rating_label_str() . ' Our ruling follows...';
        $content = $this->get_meter_html( [
            'src'   => get_template_directory_uri() . '/assets/images/partners/politifact/fias/meter-fias-' . $this->get_rating() . '.jpg',
            'alt'   => $description,
            'title' => $description,
        ] );
        return Attachment::get_img_caption_html( $content );
    }

    /**
     * Get the image element for the meter
     *
     * @param  array  $atts    Optional attribute overrides
     * @return string HTML or empty
     */
    public function get_meter_html( $atts = [] ) {
        if ( $this->has_rating() ) {
            $defaults = [
                'src'   => $this->get_meter_src(),
                'alt'   => $this->get_rating_label_str(),
                'title' => $this->get_rating_label_str(),
                'class' => 'factcheck-meter-img',
            ];
            $atts = wp_parse_args( $atts, $defaults );
            return Attachment::get_img_html( $atts );
        }
        return '';
    }

    /**
     * Get the image source for the meter based on its rating
     *
     * @return string URL
     */
    public function get_meter_src() {
        return get_template_directory_uri() . '/assets/images/partners/politifact/meter-' . $this->get_rating() . '-250.png';
    }

    /**
     * Does the statement have a rating?
     *
     * @return boolean
     */
    public function has_rating() {
        return (bool) $this->get_rating();
    }

    /**
     * Get the rating label in a complete sentence
     *
     * @return string
     */
    public function get_rating_label_str() {
        return sprintf( 'We rate this statement as %s.', $this->get_rating_label() );
    }

    /**
     * Get the label for the rating
     *
     * @return string
     */
    public function get_rating_label() {
        $ratings = Entity_Types::get_politifact_ratings();
        return $ratings[ $this->get_rating() ];
    }

    /**
     * Get the rating
     *
     * @return string
     */
    public function get_rating() {
        return $this->get_factcheck_meta( 'rating' );
    }

    /**
     * Get the filtered analysis text
     *
     * @return string
     */
    public function get_the_analysis() {
        return apply_filters( 'the_content', $this->get_analysis() );
    }

    /**
     * Get the analysis text
     *
     * @return string HTML
     */
    public function get_analysis() {
        return $this->get_factcheck_meta( 'analysis' );
    }

    /**
     * Get the filtered ruling text
     *
     * @return string
     */
    public function get_the_ruling() {
        return apply_filters( 'the_content', $this->get_ruling() );
    }

    /**
     * Get the ruling text
     *
     * @return string HTML
     */
    public function get_ruling() {
        return $this->get_factcheck_meta( 'ruling' );
    }

    /**
     * Get the filtered sources text
     *
     * @return string
     */
    public function get_the_sources() {
        return apply_filters( 'the_content', $this->get_sources() );
    }

    /**
     * Get the sources text
     *
     * @return string HTML
     */
    public function get_sources() {
        return $this->get_factcheck_meta( 'sources' );
    }

    /**
     * Get the name of the editor
     *
     * @return string
     */
    public function get_editor() {
        return $this->get_factcheck_meta( 'editor' );
    }

    /**
     * Get meta regarding the statement
     *
     * @param  string $key Statement subkey
     * @return mixed
     */
    private function get_statement_meta( $key ) {
        $key = 'statement_' . $key;
        return $this->get_factcheck_meta( $key );
    }

    /**
     * Get the Factcheck meta
     *
     * @param  string $key Factcheck subkey
     * @return mixed
     */
    private function get_factcheck_meta( $key ) {
        $key = 'factcheck_' . $key;
        return $this->get_meta( $key );
    }

    /**
     * Get the Twig context for this post
     *
     * @param array Existing context to filter
     * @return array Twig context
     */
    public function get_context( $context = [] ) {
        $context = [
            'statement_img'     => $this->get_statement_img(),
            'statement_setting' => $this->get_statement_setting(),
            'statement_date'    => $this->get_statement_date_str(),
        ] + parent::get_context( $context );

        $context['content_classes'][] = 's-content';

        if ( $context['statement_img'] ) {
            $context['statement_classes'] = 'has-image';
        }

        $statement_speaker = $this->get_statement_speaker();
        if ( Types::is_cluster( $statement_speaker ) ) {
            $context['statement_speaker'] = $statement_speaker->get_title();
        }

        ob_start();
        $context['content'] = Timber::render( 'partials/factchecks/content.twig', $context );
        $context['sidebar'] = Timber::render( 'sidebar-factcheck.twig', $context );
        ob_end_clean();

        return $context;
    }
}
