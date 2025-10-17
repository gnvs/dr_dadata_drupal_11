<?php

namespace Drupal\dadata_integration\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\dadata_integration\Dadata;


class DadataService {

  protected $configFactory;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Создаем и инициализируем клиент
   */
  protected function getClient(): Dadata {
    $config = $this->configFactory->get('dadata_integration.settings');
    $token = $config->get('token') ?? '';
    $secret = $config->get('secret') ?? '';

    $client = new Dadata($token, $secret);
    $client->init();
    return $client;
  }

  /**
   * Ищем ИНН используя findById('party').
   *
   * @param string $inn
   * @return array|null
   */
  public function findPartyByInn(string $inn): ?array {
    $client = $this->getClient();
    try {
      $fields = ['query' => $inn, 'count' => 1];
      $result = $client->findById('party', $fields);
    } finally {
      $client->close();
    }
    return $result;
  }

  /**
   * Чистая обертка
   */
  public function clean(string $type, string $value): ?array {
    $client = $this->getClient();
    try {
      $result = $client->clean($type, $value);
    } finally {
      $client->close();
    }
    return $result;
  }
}
