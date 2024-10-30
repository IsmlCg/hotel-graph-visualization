<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\HotelService;
use Illuminate\Support\Collection;

class HotelController extends Controller
{
    protected $hotelService; 
    private const DAYS = [
        'DAYS_7'  => 7,
        'DAYS_14' => 14,
        'DAYS_30' => 30,
        'DAYS_60' => 60,
    ];

    private $conversion_rates = [
        "EUR" => 1,
        "CAD" => 1.5024,
        "GBP" => 0.8335,
        "JPY" => 165.5816,
        "USD" => 1.0816
  ];
    private const DEFAULT_CURRENCY = "EUR";
    private const CURRENCIES = [ "EUR", "USD", "GBP", "JPY", "CAD" ];
    private $pr1OrPr2 ="pr1";
    private $today;
    public function __construct( HotelService $hotelService )
    {
        $this->hotelService = $hotelService;
        $this->today = new \DateTime();
    }
    private function convertToCurrency( array $sitePrices, string $toCurrency ):array{
        foreach ($sitePrices as &$row) {
            foreach ($row as &$value) {
                if (is_int( $value ) || is_float( $value )) {
                    $value *= number_format( $this->conversion_rates[ $toCurrency ], 2);
                }
            }
        }
        return $sitePrices;
    }
    public function getHotelRoom(Request $request)
    {
        $days = $request->input('days');
        //validate 
        $toCurrency = $request->input('toCurrency');
        $this->pr1OrPr2 = $request->input('pr1OrPr2');
        $data = []; 
        // Fetch fetch site access using the service
        $siteAccess = $this->hotelService->fetchSiteAccess(); 
        // add the header of google chart 
        $hotelNames = $this->filterPrimaryNames( $siteAccess['siteList']);
        $header = [ "Date", ...$hotelNames ];
        $siteIDList = $this->filterSiteIDs( $siteAccess['siteList'] );
        // Fetch fetch site access using the service 
        // Convert the grouped prices to an indexed array 

        $sitePricesDate = $this->fetchRatesByDays( $siteIDList, $this->getDays( $days ) );
        $body = $this->convertToCurrency( $sitePricesDate, $toCurrency );
        if ($data !== null) {
            // Pass the fetch hotel to the view for rendering
            return response()->json( [ $header, ...$body ] );
        } else {
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
        return array_map(function ($site) {
            if (!isset($site['siteID']) || !is_int($site['siteID'])) {
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
    private function filterRates( array &$datePrices, array $listSitesRate ){
        
        foreach ( $listSitesRate['siteList'] as $index => $site ) {
            $checkinDate ='';
            foreach ( $site['rates'] as $rate ) {
                $checkinDate = $rate['checkin'];
                $price = $rate['price'][0][ $this->pr1OrPr2 ] ?? null;
                $defaulPrice = $datePrices[$checkinDate][$index +1 ];
                if( !is_null( $price ) ){
                    $price = is_null( $defaulPrice ) ? $price:min( $defaulPrice,$price );
                    $datePrices[$checkinDate][$index +1] =  $price ;
                }
                
            }  
        } 
    }

    public function getDays($key) {
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
    private function fetchRatesByDays( array $siteIDList, int $days ): array{
        $datePrices = [];
        $DAYS_30 = $this->getDays("DAYS_30");
        $DAYS_60 = $this->getDays("DAYS_60"); 

        if( $days>0 ){

            $datePrices = $this->generateDateRange( $days );
            $startDate = $this->today; 
            if( $days <= $DAYS_30 ){

                $sitesRate = $this->hotelService->fetchRates( $siteIDList, $days, $startDate->format("Y-m-d") );
                $this->filterRates( $datePrices, $sitesRate );

            }else if( $days <= $DAYS_60 ) {

                $sitesRateFirst = $this->hotelService->fetchRates( $siteIDList, $DAYS_30, $startDate->format("Y-m-d") );
                $this->filterRates( $datePrices, $sitesRateFirst );
                
                $days = $days - $DAYS_30;
                $startDate = $startDate->modify("+". $days. " days"); 

                $sitesRateSecond = $this->hotelService->fetchRates( $siteIDList, $days, $startDate->format("Y-m-d") );
                $this->filterRates( $datePrices, $sitesRateSecond );
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
    public function index(Request $request)
    { 
        $data = []; 
        // Fetch fetch site access using the service
        $siteAccess = $this->hotelService->fetchSiteAccess(); 
        // add the header of google chart 
        $hotelNames = $this->filterPrimaryNames( $siteAccess['siteList']);
        $data["header"] = [ "Date", ...$hotelNames ];
        $siteIDList = $this->filterSiteIDs( $siteAccess['siteList'] );
        // Fetch fetch site access using the service
        $data["propertyDetails"] = $this->hotelService->fetchPropertyInformation( $siteIDList ); 
        // Convert the grouped prices to an indexed array
        $DAYS_60 = $this->getDays( "DAYS_60" );
        $data["body"] = $this->fetchRatesByDays( $siteIDList, $DAYS_60 );//["2024-10-25",10,15,12];
        if ($data !== null) {
            // Pass the fetch hotel to the view for rendering
            return view('hotels.index', ['data' => $data, 'currencies'=>$this->conversion_rates, 'defaultCurrency'=>self::DEFAULT_CURRENCY ]);
        } else {
            // Return an error response if fetching data fails
            return response()->json(['error' => 'Unable to fetch fetch site access'], 500);
        }
    }
}

            