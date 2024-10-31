<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HotelService
{
    // Set endpoint or API url in the construct from environment variables
    protected $apiUrl;
    
    // Set username and password in the construct from environment variables
    protected $userAuth = [];

    /**
     * HotelService constructor.
     *
     * Initializes the endpoint or API url, username and password properties with values
     * retrieved from the environment variables. These values are 
     * typically stored in the .env file of the application.
     *
     * @return void
    */
    public function __construct()
    {
        /**
         * 
         * Retrieves the password for the avvio service from the env configuration file.
         * The endpoint or API url, user name and password is stored in the "Services" configuration array under the
         * "avvio" key. This allows easy access to sensitive information
         * without hard-coding it directly into the codebase, promoting better
         * security practices.
         * 
        */
        $this->apiUrl = config( 'services.avvio.api_url', 'https://api.avvio.com/api/ws_api.php' );
        $username = config( 'services.avvio.user_name');
        $password = config( 'services.avvio.password');

        $this->userAuth =  [
            "username" => $username,
            "password" => $password
        ];
    }
    
    /**
     * Fetches property information and gets a list of sites based on site ID.
     *
     * @param array $siteIDList A list of site IDs for which information will be retrieved.
     * @return array Returns an array with property details of each site including 'siteID', 'primaryName',
     *               'address', 'phone', 'url', 'stars', 'currency', 'facilities', 'geoLocation', and more.
     */
    public function fetchPropertyInformation(array $siteIDList)
    {
        // Check if siteIDList is empty
        if (empty($siteIDList)) 
        {
            return []; 
        }

        $cacheKey = 'propertyInfo';
        if ( Cache::has($cacheKey) ) 
        {
            return Cache::get($cacheKey);
        } 
 
        $options = [
            "userAuth" => $this->userAuth,
            "operation" => "getPropertyInformation",
            "siteIDList" => $siteIDList,
        ];

        try {
            // Make a POST request to the API
            $response = Http::post($this->apiUrl, $options);

            // Check if the request was successful
            if ( $response->successful() ) 
            {
                $data = $response->json(); 
                /**
                 * Caches data for a day to reduce the number of API calls.
                 * This approach reduces API load and improves application performance.
                 * By blocking unnecessary requests for the same data within a 24-hour period.
                 * It ensures that users receive timely updates while optimizing resource utilization.
                */
                Cache::put($cacheKey, $data, now()->addDay());
                return $data;
            } else {
                Log::error('API request failed with status: ' . $response->status());
                return null;
            }
        } catch (\Exception $e) 
        {
            Log::error('Exception occurred while fetching property information: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves pricing information for specific locations within a given site id list, inventory horizon and for a specific date.
     *
     * @param array $siteIDList An array of site IDs to get their pricing data from.
     * @param int $inventoryHorizon The number of days (1-30) for pricing availability.
     * @param string $date Input date in "Y-mm-dd" format (default is current date).* @return array Returns an array with each site's 'siteID', 'primaryName', and 'rates' data:
     *               - 'rateID', 'roomID', 'checkin' date, 'occupancy', and 'price' details.
     */
    public function fetchRates( array $siteIDList, int $inventoryHorizon = 1, string $date = null )
    {
        // Check if siteIDList is empty
        if ( empty( $siteIDList ) ) 
        {
            return [];
        }  

        // Set default date if not provided 
        $date = $date ?? now()->format('Y-m-d'); 
       
        if ( $inventoryHorizon <= 0 || $inventoryHorizon > 30 ) {
            throw new InvalidArgumentException("The inventoryHorizon value must be an integer between 1 and 30.");
        }

        /**
         * 
         * Generate a unique cache key based on the provided parameters.
         * The cache key ensures that each cached data entry is clearly identifiable, allowing for efficient data storage and retrieval.
         * This unique feature helps prevent cache collisions and ensures that correct data is returned based on specific input criteria, for example
         * site IDs, inventory horizon and date request parameters.
         * 
        */
        $cacheKey = "siteIdList_".implode( "_", $siteIDList ) ."_DAYS_{$inventoryHorizon}_date_{$date}";
        if ( Cache::has( $cacheKey ) ) 
        {
            return Cache::get($cacheKey);
        }  
        try {
 
            $options = [
                "userAuth" => $this->userAuth,
                "operation" => "getRates",
                "siteIDList" => $siteIDList, 
                "startDate"=> $date,
                "inventoryHorizon"=> $inventoryHorizon,
                "losOptions"=> [1], 
                
            ]; 
            // Make a POST request to the API, Retries on Failure 3 and Delay Between Retries 100ms 
            $dataRates = Http::retry(3, 100)->post( $this->apiUrl, $options );

            // Check if the request was successful
            if ( $dataRates->successful() )
            {
                
                $data = $dataRates->json(); 

                /**
                 * 
                 * Caching data for 30 minutes to reduce the number of API calls.
                 * This strategy balances the need for fresh data with efficient use of resources,
                 * ensuring that the application does not make unnecessary API requests
                 * in a short period of time while providing timely updates to users.
                 * 
                */
                Cache::put( $cacheKey, $data, now()->addMinutes( 30 ) );
                return $data;
            } else 
            {
                Log::error( 'API request failed with status: ' . $dataRates->status() );
                return null;
            }
        } catch (\Exception $e) 
        {
            Log::error( 'Exception occurred while fetching rates information: ' . $e->getMessage() );
            return null;
        }
    }

    /**
     * Gets the site access information.
     *
     * @return array|null Returns an array containing site details, each with a "siteID" and a "primaryName".
     * Example: 
     * [
     *     'siteList' => [
     *         ['siteID' => 11, 'primaryName' => 'Dromoland Castle'],
     *         ....
     *     ]
     * ]
     */
    public function fetchSiteAccess()
    {
        // Try to get cached data, cache: used to optimize performance.
        $cacheKey = 'siteAccess';
        if ( Cache::has($cacheKey) ) 
        {
            return Cache::get($cacheKey);
        } 
 
        $options = [
            "userAuth" => $this->userAuth,
            "operation" => "getSiteAccess",
        ];

        try {

            // Make a POST request to the API, Retries on Failure 3 and Delay Between Retries 100ms 
            $response = Http::retry(3, 100)->post($this->apiUrl, $options);
    
            // Check if the request was successful
            if ( $response->successful() ) 
            {
                
                $data = $response->json(); 
                /**
                 * 
                 * Caches data for a day to reduce the number of API calls.
                 * This approach reduces API load and improves application performance.
                 * By blocking unnecessary requests for the same data within a 24-hour period.
                 * It ensures that users receive timely updates while optimizing resource utilization.
                 * 
                */
                Cache::put($cacheKey, $data, now()->addDay()); 
                return $data;
            } else 
            {
                Log::error('API request failed with status: ' . $response->status());
                return null;
            }
        } catch (\Exception $e) 
        {
            Log::error('Exception occurred while fetching site access information: ' . $e->getMessage());
            return null;
        }
    }
 
}
