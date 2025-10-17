<?php

namespace Drupal\dadata_integration\Service;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Dadata API клиент
 */
class DadataClient {

  protected $token;
  protected $secret;
  protected $httpClient;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $config = $config_factory->get('dadata_integration.settings');
    $this->token = $config->get('token');
    $this->secret = $config->get('secret');
    $this->httpClient = new Client([
      'base_uri' => 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/',
      'timeout' => 10,
    ]);
  }

  public function findByInn($inn) {
    if (empty($this->token) || empty($this->secret)) {
      throw new \Exception('Dadata API ключи не настроены.');
    }

    $response = $this->httpClient->post('findById/party', [
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => 'Token ' . $this->token,
        'X-Secret' => $this->secret,
      ],
      'json' => [
        'query' => $inn,
      ],
    ]);

    $data = json_decode($response->getBody(), TRUE);
    return !empty($data['suggestions']) ? $data['suggestions'][0]['data'] : [];
  }
}
