<?php

namespace Drupal\dadata_integration;

use Exception;

/**
 * Exception thrown when API returns HTTP 429.
 */
class DadataRequests extends Exception {}

/**
 * Minimal Dadata client using cURL.
 *
 * Note: this class does not manage logging or retries — do that in the caller if needed.
 */
class Dadata {

  private string $clean_url = "https://cleaner.dadata.ru/api/v1/clean";
  private string $suggest_url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs";
  private string $token;
  private string $secret;
  private $handle;

  public function __construct(string $token, string $secret) {
    $this->token = $token;
    $this->secret = $secret;
  }

  /**
   * Initialize cURL handle. Call once per instance.
   */
  public function init(): void {
    if (!function_exists('curl_init')) {
      throw new Exception('cURL extension is required.');
    }
    $this->handle = curl_init();
    curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->handle, CURLOPT_HTTPHEADER, [
      "Content-Type: application/json",
      "Accept: application/json",
      "Authorization: Token " . $this->token,
      "X-Secret: " . $this->secret,
    ]);
    // We'll set POST on a per-request basis.
  }

  /**
   * Clean service (address, phone, name, passport...).
   *
   * @param string $type
   * @param string $value
   *
   * @return array|null
   *
   * @throws DadataRequests
   * @throws Exception
   */
  public function clean(string $type, string $value): ?array {
    $url = $this->clean_url . '/' . $type;
    $fields = [$value];
    return $this->executeRequest($url, $fields);
  }

  /**
   * findById service (party, bank, address).
   *
   * @param string $type
   * @param array $fields
   * @return array|null
   * @throws DadataRequests
   * @throws Exception
   */
  public function findById(string $type, array $fields): ?array {
    $url = $this->suggest_url . '/findById/' . $type;
    return $this->executeRequest($url, $fields);
  }

  /**
   * Suggest service.
   *
   * @param string $type
   * @param array $fields
   * @return array|null
   * @throws DadataRequests
   * @throws Exception
   */
  public function suggest(string $type, array $fields): ?array {
    $url = $this->suggest_url . '/suggest/' . $type;
    return $this->executeRequest($url, $fields);
  }

  /**
   * iplocate service.
   */
  public function iplocate(string $ip): ?array {
    $url = $this->suggest_url . '/iplocate/address';
    return $this->executeRequest($url, ['ip' => $ip]);
  }

  /**
   * geolocate service.
   */
  public function geolocate(float $lat, float $lon, int $count = 10, int $radius_meters = 100): ?array {
    $url = $this->suggest_url . '/geolocate/address';
    return $this->executeRequest($url, [
      'lat' => $lat,
      'lon' => $lon,
      'count' => $count,
      'radius_meters' => $radius_meters,
    ]);
  }

  /**
   * Close curl handle.
   */
  public function close(): void {
    if (!empty($this->handle)) {
      curl_close($this->handle);
      $this->handle = null;
    }
  }

  /**
   * Execute HTTP request.
   *
   * @param string $url
   * @param array|null $fields
   * @return array|null
   * @throws DadataRequests
   * @throws Exception
   */
  private function executeRequest(string $url, ?array $fields): ?array {
    if (empty($this->handle)) {
      throw new Exception('Dadata client is not initialized. Call init() first.');
    }

    curl_setopt($this->handle, CURLOPT_URL, $url);

    if ($fields !== null) {
      curl_setopt($this->handle, CURLOPT_POST, 1);
      curl_setopt($this->handle, CURLOPT_POSTFIELDS, json_encode($fields, JSON_UNESCAPED_UNICODE));
    } else {
      curl_setopt($this->handle, CURLOPT_POST, 0);
      curl_setopt($this->handle, CURLOPT_POSTFIELDS, null);
    }

    $result = curl_exec($this->handle);
    $info = curl_getinfo($this->handle);

    // Handle network / curl errors.
    if ($result === false) {
      $err = curl_error($this->handle);
      throw new Exception('cURL error: ' . $err);
    }

    $http_code = $info['http_code'] ?? 0;
    if ($http_code === 429) {
      throw new DadataRequests('Too many requests (429).');
    }
    if ($http_code < 200 || $http_code >= 300) {
      throw new Exception('Request failed with http code ' . $http_code . ': ' . $result);
    }

    $decoded = json_decode($result, TRUE);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception('Invalid JSON response from Dadata: ' . json_last_error_msg());
    }

    return $decoded;
  }
}
