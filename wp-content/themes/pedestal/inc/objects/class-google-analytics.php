<?php

namespace Pedestal\Objects;

use Google_Client;
use Google_Service_AnalyticsReporting;
use Google_Auth_AssertionCredentials;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_Dimension;
use Google_Service_AnalyticsReporting_ReportRequest;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_DimensionFilter;
use Google_Service_AnalyticsReporting_DimensionFilterClause;
use Google_Service_AnalyticsReporting_OrderBy;

class Google_Analytics {

    /**
     * Reference to the instantiated Google Client
     * @var object
     */
    public $client = [];

    /**
     * Allowed format types of metrics
     *
     * @var array
     * @see https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#MetricType
     */
    public $metric_formats = [
        'INTEGER',
        'FLOAT',
        'CURRENCY',
        'PERCENT',
        'TIME', // HH:MM:SS format
    ];

    /**
     * The default metric format
     *
     * @var string
     */
    public $default_metric_format = 'INTEGER';

    /**
     * Ensure only one instance of this class is intialized at a time
     *
     * @return class Instance of this class
     */
    static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new static();
            $instance->setup_client();
        }
        return $instance;
    }

    /**
     * Setup the Google Client to interact with the API
     */
    private function setup_client() {
        $client = new Google_Client();
        $client->setApplicationName( 'Pedestal Google Analytics' );
        $key_file = dirname( ABSPATH ) . '/credentials/google-service-account-credentials.json';
        $client->setAuthConfig( $key_file );
        $client->setAccessType( 'offline' );
        $client->setScopes( [ 'https://www.googleapis.com/auth/analytics.readonly' ] );
        $this->client = new \Google_Service_AnalyticsReporting( $client );
    }

    /**
     * Make a request to the Google Analytics API
     *
     * @param  array  $args Arguments to send with the request
     * @see https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet
     * @return object       Payload from the API
     */
    public function make_request( $args = [] ) {
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId( PEDESTAL_GOOGLE_ANALYTICS_WEB_VIEW_ID );

        $default_args = [
            'date_range' => '',
            'metrics' => '',
            'dimension_filter_clause' => '',
            'order_by' => '',
            'page_size' => 100,
            'dimensions' => '',
        ];
        $args = wp_parse_args( $args, $default_args );

        if ( ! empty( $args['date_range'] ) ) {
            $request->setDateRanges( $args['date_range'] );
        }
        if ( ! empty( $args['metrics'] ) ) {
            $request->setMetrics( $args['metrics'] );
        }
        if ( ! empty( $args['dimension_filter_clause'] ) ) {
            $request->setDimensionFilterClauses( [ $args['dimension_filter_clause'] ] );
        }
        if ( ! empty( $args['order_by'] ) ) {
            $request->setOrderBys( $args['order_by'] );
        }
        if ( ! empty( $args['page_size'] ) ) {
            $request->setPageSize( $args['page_size'] );
        }
        if ( ! empty( $args['dimensions'] ) ) {
            $request->setDimensions( $args['dimensions'] );
        }
        $request->setSamplingLevel( 'LARGE' );

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests( [ $request ] );
        return $this->client->reports->batchGet( $body );
    }

    /**
     * Get a date range object to pass to the Google Analytics API
     *
     * @param  string $start A date further in the past
     * @param  string $end   A date closer to the present
     * @return object        A Google Analytics date object
     */
    public function get_date_range( $start = '7daysAgo', $end = 'today' ) {
        $date_range = new Google_Service_AnalyticsReporting_DateRange();
        $date_range->setStartDate( $start );
        $date_range->setEndDate( $end );
        return $date_range;
    }

    /**
     * Get a metric object to pass to the Google Analytics API
     *
     * Example:
     * $args[
     *   'ga:metricName1' => '',
     *   'ga:metricName2' => 'Alias',
     *   'ga:metricName3' => [ 'Alias', 'format' ]
     * ];
     *
     * @see https://developers.google.com/analytics/devguides/reporting/core/dimsmets
     *
     * @param  array  $args Arguments about the metrics
     * @return object       A Google Analytics metric object
     */
    public function get_metrics( $args = [] ) {
        $metrics = [];
        foreach ( $args as $expression => $more ) {
            $format = '';
            if ( is_array( $more ) ) {
                if ( empty( $more[1] ) ) {
                    $more[1] = $this->default_metric_format;
                }
                $alias = $more[0];
                $format = strtoupper( $more[1] );
                if ( ! in_array( $format, $this->metric_formats ) ) {
                    $format = $this->default_metric_format;
                }
            } else {
                $alias = $more;
            }

            $metric = new Google_Service_AnalyticsReporting_Metric();
            $metric->setExpression( $expression );
            if ( ! empty( $alias ) ) {
                $metric->setAlias( $alias );
            }
            if ( ! empty( $format ) ) {
                $metric->setFormat( $format );
            }
            $metrics[] = $metric;
        }

        return $metrics;
    }

    /**
     * Get a dimension object to pass to the Google Analytics API
     *
     * @see https://developers.google.com/analytics/devguides/reporting/core/dimsmets
     * @see https://www.lunametrics.com/blog/2017/07/27/google-analytics-api-v4-histogram-buckets/
     *
     * @param  string $name              The name of the dimension
     * @param  array  $histogram_buckets An array of values to group the data in to
     * @return object                    A Google Dimension object
     */
    public function get_dimension( $name = '', $histogram_buckets = [] ) {
        $dimension = new Google_Service_AnalyticsReporting_Dimension;
        if ( ! empty( $name ) ) {
            $dimension->setName( $name );
        }
        if ( is_array( $histogram_buckets ) && ! empty( $histogram_buckets ) ) {
            $dimension->setHistogramBuckets( $histogram_buckets );
        }
        return $dimension;
    }

    /**
     * Get a dimension filter object to pass to the Google Analytics API
     *
     * @param  array  $args Arguments for filtering the data returned
     * @return object       A Google Dimension Filter object
     */
    public function get_dimension_filters( $args = [] ) {
        if ( ! is_array( $args[0] ) ) {
            $args = [ $args ];
        }

        $default_args = [
            'name'           => '',
            'operator'       => 'REGEXP', // For list of possible values see https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#Operator
            'expressions'    => [],
            'case_sensitive' => false,
            'not'            => false,
        ];
        $filters_to_set = [];
        foreach ( $args as $arg ) {
            $arg = wp_parse_args( $arg, $default_args );
            if ( empty( $arg['name'] ) ) {
                continue;
            }
            if ( ! is_array( $arg['expressions'] ) ) {
                $arg['expressions'] = [ $arg['expressions'] ];
            }
            $filter = new Google_Service_AnalyticsReporting_DimensionFilter();
            $filter->setDimensionName( $arg['name'] );
            $filter->setOperator( $arg['operator'] );
            $filter->setExpressions( $arg['expressions'] );
            $filter->setCaseSensitive( $arg['case_sensitive'] );
            $filter->setNot( $arg['not'] );
            $filters_to_set[] = $filter;
        }

        if ( ! empty( $filters_to_set ) ) {
            $filter_clause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
            $filter_clause->setFilters( $filters_to_set );
            return $filter_clause;
        }
        return '';
    }

    /**
     * A standard way for formatting Google Analytics output
     * This should work for most requests.
     *
     * @param  array  $data The raw response from the Google Analytics API call
     * @return array       An array of objects with the metrics/dimensions and values
     */
    public function format_output( $data = [] ) {
        $report      = $data->reports[0];
        $rows        = $report->data->rows;
        $output      = [];

        $headers     = [];
        // @codingStandardsIgnoreStart
        $header_data = $report->columnHeader;
        // @codingStandardsIgnoreEnd
        foreach ( $header_data->dimensions as $dimension ) {
            $headers[] = $dimension;
        }

        $metric_header_offset = count( $header_data->dimensions );
        // @codingStandardsIgnoreStart
        foreach ( $header_data->metricHeader->metricHeaderEntries as $metric ) {
        // @codingStandardsIgnoreEnd
            $headers[] = $metric->name;
        }

        foreach ( $rows as $row ) {
            $new_data = [];

            foreach ( $row->dimensions as $index => $val ) {
                $key              = $headers[ $index ];
                $new_data[ $key ] = $val;
            }

            foreach ( $row->metrics as $index => $metric ) {
                $offset           = $index + $metric_header_offset;
                $key              = $headers[ $offset ];
                $new_data[ $key ] = intval( $metric->values[0] );
            }

            $output[] = (object) $new_data;
        }

        return $output;
    }
}
