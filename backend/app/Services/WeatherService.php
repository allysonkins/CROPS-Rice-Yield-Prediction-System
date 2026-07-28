<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WeatherService
{
    protected $apiKey;
    protected $city;
    protected $country;

    public function __construct()
    {
        $this->apiKey = env('OPENWEATHER_API_KEY', '');
        $this->city = env('WEATHER_CITY', 'Santiago');
        $this->country = env('WEATHER_COUNTRY', 'PH');
    }

    /**
     * Get current weather data for Santiago City
     */
    public function getWeather()
{
    $this->apiKey = env('OPENWEATHER_API_KEY', '');
    
    if (empty($this->apiKey)) {
        return $this->getDefaultWeather();
    }

    $url = "https://api.openweathermap.org/data/2.5/weather";
    
    try {
        $response = Http::timeout(10)->get($url, [
            'q' => "{$this->city},{$this->country}",
            'appid' => $this->apiKey,
            'units' => 'metric'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'temperature' => round($data['main']['temp'] ?? 27.5, 1),
                'humidity' => round($data['main']['humidity'] ?? 78, 0),
                'rainfall' => round($data['rain']['1h'] ?? 0, 1),
                'wind_speed' => round($data['wind']['speed'] ?? 10, 1),
                'description' => ucfirst($data['weather'][0]['description'] ?? 'Partly cloudy'),
                'icon' => $data['weather'][0]['icon'] ?? '01d',
                'success' => true,
                'source' => 'OpenWeatherMap'
            ];
        }
    } catch (\Exception $e) {
        \Log::warning('Weather API failed: ' . $e->getMessage());
    }

    return $this->getDefaultWeather();
}

    /**
     * Get default/fallback weather data
     */
    protected function getDefaultWeather()
    {
        return [
            'temperature' => 27.5,
            'humidity' => 78,
            'rainfall' => 0,
            'wind_speed' => 10,
            'description' => 'Weather data unavailable',
            'icon' => '01d',
            'success' => false,
            'source' => 'Default (Fallback)'
        ];
    }
}