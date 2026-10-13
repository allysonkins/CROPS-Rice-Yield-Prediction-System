<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    protected string $apiKey;
    protected string $city;
    protected string $country;

    public function __construct()
    {
        // ✅ Read via config(), never env() — survives config:cache
        $this->apiKey  = (string) config('services.openweather.key', '');
        $this->city    = (string) config('services.openweather.city', 'Santiago');
        $this->country = (string) config('services.openweather.country', 'PH');
    }

    /**
     * Get current weather data for Santiago City.
     * Always returns an array — never throws.
     */
    public function getWeather(): array
    {
        // Re-read in case config was cleared after boot
        $this->apiKey = (string) config('services.openweather.key', '');

        if ($this->apiKey === '') {
            Log::warning('OpenWeather API key is empty — check .env and config/services.php');
            return $this->getDefaultWeather();
        }

        $url = 'https://api.openweathermap.org/data/2.5/weather';

        try {
            $response = Http::timeout(10)->get($url, [
                'q'     => "{$this->city},{$this->country}",
                'appid' => $this->apiKey,
                'units' => 'metric',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'temperature' => round($data['main']['temp']     ?? 27.5, 1),
                    'humidity'    => round($data['main']['humidity'] ?? 78,   0),
                    'rainfall'    => round($data['rain']['1h']       ?? 0,    1),
                    'wind_speed'  => round($data['wind']['speed']    ?? 10,   1),
                    'description' => ucfirst($data['weather'][0]['description'] ?? 'Partly cloudy'),
                    'icon'        => $data['weather'][0]['icon'] ?? '01d',
                    'success'     => true,
                    'source'      => 'OpenWeatherMap',
                ];
            }

            Log::warning('OpenWeather returned HTTP ' . $response->status(), [
                'body' => substr($response->body(), 0, 300),
            ]);

        } catch (\Throwable $e) {
            Log::warning('Weather API failed: ' . $e->getMessage());
        }

        return $this->getDefaultWeather();
    }

    /**
     * Fallback weather used when the API is unreachable.
     */
    protected function getDefaultWeather(): array
    {
        return [
            'temperature' => 27.5,
            'humidity'    => 78,
            'rainfall'    => 0,
            'wind_speed'  => 10,
            'description' => 'Weather data unavailable',
            'icon'        => '01d',
            'success'     => false,
            'source'      => 'Default (Fallback)',
        ];
    }
}