<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HotelService
{
    protected $apiUrl;
    protected $userAuth = [
                "username" => "techtestapi",
                "password" => "cB4e0dY31m"
            ];
    public function __construct()
    {
        $this->apiUrl = config('services.avvio.api_url', 'https://api.avvio.com/api/ws_api.php');
    }
    
    /**
     * Fetches property information for a list of sites based on siteID.
     *
     * @param array $siteIDList List of siteIDs to fetch information for.
     * @return array Returns an array with property details of each site including 'siteID', 'primaryName',
     *               'address', 'phone', 'url', 'stars', 'currency', 'facilities', 'geoLocation', and more.
     */
    public function fetchPropertyInformation(array $siteIDList)
    {
        // Check if siteIDList is empty
        if (empty($siteIDList)) {
            return []; // Return an empty array if no siteIDs are provided
        }
        $cacheKey = 'propertyInfo';
        if ( Cache::has($cacheKey) ) {
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
            if ($response->successful()) {
                $data = $response->json(); 
                Cache::put($cacheKey, $data, now()->addDay());
                return $data;
            } else {
                Log::error('API request failed with status: ' . $response->status());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception occurred while fetching property information: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves rate information for specified sites within a given inventory horizon and for a specific date.
     *
     * @param array $siteIDList Array of siteIDs to fetch rate data for.
     * @param int $inventoryHorizon Number of days (1-30) for rate availability.
     * @param string $date The check-in date in 'Y-mm-dd' format (default is current date).
     * @return array Returns an array with each site's 'siteID', 'primaryName', and 'rates' data:
     *               - 'rateID', 'roomID', 'checkin' date, 'occupancy', and 'price' details.
     */
    public function fetchRates( array $siteIDList, int $inventoryHorizon = 1, string $date = null )
    {
        // Check if siteIDList is empty
        if ( empty( $siteIDList ) ) {
            return []; // Return an empty array if no siteIDs are provided
        } 

        try {
            
            if ( $inventoryHorizon <= 0 || $inventoryHorizon > 30 ) {
                throw new InvalidArgumentException("The inventoryHorizon value must be an integer between 1 and 30.");
            }

            // Set default date if not provided 
            $date = $date ?? now()->format('Y-m-d'); 
 
            $options = [
                "userAuth" => $this->userAuth,
                "operation" => "getRates",
                "siteIDList" => $siteIDList, 
                "startDate"=> $date,
                "inventoryHorizon"=> $inventoryHorizon,
                "losOptions"=> [1], 
                
            ];
            
            // Make a POST request to the API, Retries on Failure 3 and Delay Between Retries 100ms 
            $dataRates = Http::retry(3, 100)->post($this->apiUrl, $options);

            // Check if the request was successful
            if ($dataRates->successful()) {
                
                // Cache the combined data for 1 day
                $data = $dataRates->json(); 
                return $data;
            } else {
                Log::error( 'API request failed with status: ' . $dataRates->status() );
                return null;
            }
        } catch (\Exception $e) {
            Log::error( 'Exception occurred while fetching rates information: ' . $e->getMessage() );
            return null;
        }
    }

    /**
     * Fetches site access information from the API.
     *
     * @return array|null Returns an array with site details, each containing 'siteID' and 'primaryName'.
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
        // Try to get cached data
        $cacheKey = 'siteAccess';
        if ( Cache::has($cacheKey) ) {
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
            if ($response->successful()) {
                // Cache the combined data for 1 day
                $data = $response->json(); 
                Cache::put($cacheKey, $data, now()->addDay());
                return $data;
            } else {
                Log::error('API request failed with status: ' . $response->status());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception occurred while fetching site access information: ' . $e->getMessage());
            return null;
        }
    }
 
}
