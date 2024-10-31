<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\HotelService;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\ValidateDataRequest; 

class HotelController extends Controller
{
    protected $hotelService;
    
    // Filter by days 
    private const DAYS = [
        'DAYS_7'  => 7,
        'DAYS_14' => 14,
        'DAYS_30' => 30,
        'DAYS_60' => 60,
    ];

    // Define five currencies
    private const CURRENCIES = [ "EUR", "USD", "GBP", "JPY", "CAD" ];
    
    /**
     * The default is five specified currencies.
     * Future updates will implement a service call to get live exchange rates from the Exchange Rates API.
     * 
     * Example: 
     * Fetching JSON https://v6.exchangerate-api.com/v6/YOUR-API-KEY/latest/USD
     * 
     * @return 
     *  conversion_rates: {
     *      "USD": 1,
     *      "AUD": 1.4817,
     *      "BGN": 1.7741,
     *      "CAD": 1.3168,
     *      "CHF": 0.9774,
     *      "CNY": 6.9454,
     *      "EGP": 15.7361,
     *      "EUR": 0.9013,
     *      "GBP": 0.7679,
     *      "...": 7.8536,
     *      "...": 1.3127,
     *      "...": 7.4722, etc. etc.
	 *  }
     */
    private $conversion_rates = [
        "EUR" => 1,
        "CAD" => 1.5024,
        "GBP" => 0.8335,
        "JPY" => 165.5816,
        "USD" => 1.0816
    ];

    private const DEFAULT_CURRENCY = "EUR";

    // Sigle pr1 and couple pr2
    private $pr1OrPr2 ="pr1";

    // Define the first day to initialize data collection
    private $today;

    public function __construct( HotelService $hotelService )
    {
        $this->hotelService = $hotelService;
        $this->today = new \DateTime();
    }

    /**
     * Convert the site is pricing matrix to a specific currency.
     *
     * @param array $sitePrices The array containing prices from different sites.
     * @param string $toCurrency The target currency code to convert prices to.
     * 
     * @return array An array of website prices converted to the specified currency.
    */
    private function convertToCurrency( array $sitePrices, string $toCurrency ):array
    {
        foreach ( $sitePrices as &$row ) 
        {
            foreach ($row as &$value) 
            {
                //Validate because the first element of the array is date or it can be null
                if ( is_int( $value ) || is_float( $value ) ) 
                {
                    // Access to global variable to obtain the currency to convert
                    $value *= number_format( $this->conversion_rates[ $toCurrency ], 2);
                }
            }
        }
        return $sitePrices;
    }
    
    /**
    * Retrieves hotel inventory data for a specified time horizon, currency, and sigle or cuople option.
    *
    * @param request $request The incoming request contains:
    * - 'days': (int) Number of days for the inventory horizon 1 to 60 days.
    * - 'toCurrency': (string) Target currency code for conversion.
    * - 'pr1OrPr2': (string) Id of the specified sigle or cuople option.
    *
    * @return JsonResponse The JSON response containing the hotel inventory data.
    */
    public function getHotelsInventoryHorizon( ValidateDataRequest $request ): JsonResponse
    {
        /**
         * The validated data will be assigned to the $validatedData variable, containing only data that 
         * passed validation according to the rules specified in the ValidateDataRequest class. 
         * If validation fails, it will automatically return a JSON response containing validation errors.
         * @var array $validatedData The validated data from the request.
        */
        $validatedData = $request->validated();

        // Access approved data using $validatedData
        $days = $validatedData['days'];
        $toCurrency = $validatedData['toCurrency'];

        // Set the global variable for the request. Used to filter rates by single person or couple.
        $this->pr1OrPr2 = $validatedData['pr1OrPr2'];

        // Get access to the location using the service
        $siteAccess = $this->hotelService->fetchSiteAccess(); 
        
        if ( $siteAccess !== null ) 
        {
            // Get the base names of the first row of Google Chart
            $hotelNames = $this->filterPrimaryNames( $siteAccess['siteList'] );

            // Add the header of google chart 
            $header = [ "Date", ...$hotelNames ];

            // Filter location IDs to get property information and rates
            $siteIDList = $this->filterSiteIDs( $siteAccess['siteList'] );

            // Convert the grouped prices to an indexed array 
            $sitePricesDate = $this->fetchRatesByDays( $siteIDList, $this->getDays( $days ) );

            // Convert the site is pricing matrix to a specific currency.
            $body = $this->convertToCurrency( $sitePricesDate, $toCurrency );

            // Pass the fetch hotel to the view for rendering
            return response()->json([
                'message' => 'IDs processed successfully', 
                'data' => [ $header, ...$body ]
            ]);

        } else 
        {
            // Return an error response if fetching data fails
            return response()->json(['error' => 'Unable to fetch fetch site access'], 500);
        }
        
    }

    /**
     * Extracts siteID values from an array of site information.
     *
     * @param array $siteList An array of sites, each containing a 'siteID' key.
     * @return array An array of siteID values.
     * @throws InvalidArgumentException if any siteID is not an integer.
    */
    private function filterSiteIDs(array $siteList)
    {
        return array_map(function ($site) 
        {
            if ( !isset($site['siteID']) || !is_int($site['siteID']) ) 
            {
                throw new InvalidArgumentException("Each siteID must be an integer.");
            }

            return $site['siteID'];

        }, $siteList);
    }

    /**
     * Modifies one variables by reference.
     *
     * This function updates the date and prices of the first variable.
     * One variables are modified directly because they are passed by reference.
     *
     * @param array &$datePrices The first variable, which is modified directly.
     * @param array $listSitesRate The second variable is the detail rates.
     * @return void
    */
    private function filterRates( array &$datePrices, array $listSitesRate )
    {
        
        foreach ( $listSitesRate['siteList'] as $index => $site ) 
        {
            $checkinDate ='';
            foreach ( $site['rates'] as $rate ) 
            {
                // The rate has many checkin dates and then changes the reference variable to get lower prices.
                $checkinDate = $rate['checkin'];
                
                // Get the price for pr1 single or pr2 couple the lowest price is at index 0
                $price = $rate[ 'price' ][ 0 ][ $this->pr1OrPr2 ] ?? null;

                // At first this is null, later lower prices will be set.
                $defaulPrice = $datePrices[$checkinDate][$index +1 ];

                // If prices are null, there is no need to update the datePrices variable, it is a reference variable.
                if( !is_null( $price ) )
                {
                    // Get lower price and set up in datePrices.
                    $price = is_null( $defaulPrice ) ? $price:min( $defaulPrice,$price );

                    // It is a reference variable and is then updated with the minimum price it finds.
                    $datePrices[$checkinDate][$index +1] =  $price ;
                }
                
            }  
        } 
    }

    /**
    * Retrieves the day range corresponding to a given token.
    *
    * @param mixed $code The code indicating the number of days, typically set to:
    * -DAYS_7 => 7,
    * -DAYS_14 => 14,
    * -DAYS_30 => 30,
    * -DAYS_60 => 60,
    *
    * @return int The number of days associated with the provided token.
    */
    private function getDays( $key ) {
        return self::DAYS[$key] ?? null;
    }

    /**
     * Generates a range of dates from the current date to a specified number of days in the future.
     *
     * @param int $days The number of days to generate dates for, starting from today.
     *                  For example, use 7 for the next 7 days or 14 for the next 2 weeks.
     *
     * @return array An array of dates in 'Y-m-d' format, starting from today's date and extending to the specified number of days.
     */
    private function generateDateRange( int $days ): array {
        
        if( $days === 0 ){ 
            return [];
        }
        $startDate = new \DateTime(); 
        --$days;
        $startDate->modify("+{$days} days"); 
        $date = $startDate->format('Y-m-d'); 
        // Create an array to hold the current day's data
        $dataDate = [
            $date => [$date, null, null, null]
        ];
        // Merge the previous dates with the current date
        return array_merge( $this->generateDateRange($days), $dataDate);  
    }


    /**
     * Extracts primaryName values from an array of site information.
     *
     * @param array $siteList An array of sites, each containing a 'primaryName' key.
     * @return array An array of primaryName values. 
    */
    private function filterPrimaryNames( array $siteList )
    {
        return array_map(function ($site) {
            return $site['primaryName'];
        }, $siteList);
    }

    /**
    * Retrieve hotel rates for a specified number of days based on the provided list of location IDs.
    *
    * This function outputs an array where:
    * - The first column contains the date.
    * - The second to fourth columns contain the prices for each site ID.
    *
    * @param array $siteIDList An array of site IDs whose prices will be retrieved.
    * @param int $days The number of days for which prices will be retrieved.
    *
    * @return array An array of hotel prices organized as follows:
    *           [
    *               ['2024-10-29', price1, price2, price3],
    *               ['2024-10-30', price1, price2, price3],
    *               ...
    *           ]
    */
    private function fetchRatesByDays( array $siteIDList, int $days ): array
    {
        // A data matrix of octane prices date and prices from the sites, modifying the datePrices variables by reference.
        $datePrices = [];

        if( $days>0 )
        {
            // Get the number of days using the code
            $DAYS_30 = $this->getDays( "DAYS_30" );
            $DAYS_60 = $this->getDays( "DAYS_60" );

            // Generates a range of dates from the current date to a specified number.
            $datePrices = $this->generateDateRange( $days );
            
            // init date
            $startDate = $this->today; 

            // Validate if it is 7 days, 14 days or 30 days.
            if( $days <= $DAYS_30 ){

                // Get rates by list of site IDs and then get all the details including the lowest prices
                $sitesRate = $this->hotelService->fetchRates( $siteIDList, $days, $startDate->format("Y-m-d") );
                if( $sitesRate )
                {
                    // Modifies datePrices variables by reference. 
                    $this->filterRates( $datePrices, $sitesRate );
                }  

            }else if( $days <= $DAYS_60 ) 
            {
                // Get rates by list of site IDs and then get all the details including the lowest prices, from now to 30 days
                $sitesRateFirst = $this->hotelService->fetchRates( $siteIDList, $DAYS_30, $startDate->format("Y-m-d") );
                
                if( $sitesRateFirst )
                {
                    // Modifies datePrices variables by reference. 
                    $this->filterRates( $datePrices, $sitesRateFirst );
                }                
                
                // modify and more than 30 days from now because the search is only available for 30 days at a time.
                $days = $days - $DAYS_30;
                $startDate = $startDate->modify("+". $days. " days"); 

                // Get rates by list of site IDs and then get all the details including the lowest prices from 30 days from now to 60 days.
                $sitesRateSecond = $this->hotelService->fetchRates( $siteIDList, $days, $startDate->format("Y-m-d") );
                
                if( $sitesRateSecond )
                {
                    // Modifies datePrices variables by reference.
                    $this->filterRates( $datePrices, $sitesRateSecond );
                }
                
            }
            
            return array_values( $datePrices );
        }
        return []; 
    }

    /**
     * Display hotels.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function render(Request $request)
    { 
        // Fetch fetch site access using the service
        $siteAccess = $this->hotelService->fetchSiteAccess(); 

        if ($siteAccess !== null) {

            $hotelNames = $this->filterPrimaryNames( $siteAccess['siteList']);

            // add the header of google chart
            $header = [ "Date", ...$hotelNames ];
            $siteIDList = $this->filterSiteIDs( $siteAccess['siteList'] );

            // Fetch site access using the service
            $propertyDetails = $this->hotelService->fetchPropertyInformation( $siteIDList );

            // return 60 days
            $DAYS_60 = $this->getDays( "DAYS_60" );

            // Convert aggregate prices to an indexed array
            $body = $this->fetchRatesByDays( $siteIDList, $DAYS_60 );

            //Pass the hotel into view to draw
            return view('hotels.index', [
                'data' => [ $header, ...$body ], 
                'propertyDetails' =>$propertyDetails, 
                'currencies'=>$this->conversion_rates, 
                'defaultCurrency'=>self::DEFAULT_CURRENCY
            ]);

        } else { 
            // Returns an error response if data fetch fails.
            return view('hotels.index', 
            [
                'error' => 'Unable to obtain hotel index, we did not find any available hotels at this time.'
            ]
            );
        }
    }
}

            