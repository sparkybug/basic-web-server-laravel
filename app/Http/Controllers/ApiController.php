<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    public function getHello(Request $request)
    {
        $visitorName = $request->query('visitor_name', 'Guest');

        // Remove backslashes, quotes, and slashes
        $visitorName = preg_replace('/[\\\\"\'\/]/', '', $visitorName);

        $ip = $request->ip();

        // Use a test IP when runnng locally
        if ($ip === '::1' || $ip === '127.0.0.1') {
            $ip = '8.8.8.8';
        }

        try {
            // Get location data from IPinfo
            $locationResponse = Http::get("https://ipinfo.io/{$ip}", [
                'token' => env('IPINFO_TOKEN')
            ]);

            $locationData = $locationResponse->json();
            $city = $locationData['city'];
            $loc = $locationData['loc']; // loc gives latitude and longitude

            // Get weather data from OpenWeatherMap
            [$latitude, $longitude] = explode(',', $loc);
            $weatherResponse = Http::get("https://api.openweathermap.org/data/2.5/weather", [
                'lat' => $latitude,
                'lon' => $longitude,
                'units' => 'metric',
                'appid' => env('WEATHER_API_KEY')
            ]);

            $temperature = $weatherResponse->json()['main']['temp'];

            // Build response
            $response = [
                'client_ip' => $ip,
                'location' => $city,
                'greeting' => "Hello, {$visitorName}!, the temperature is {$temperature} degrees Celsius in {$city}"
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
