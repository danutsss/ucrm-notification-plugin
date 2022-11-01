<?php

// Import UCRM Plugin SDK classes.
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;

require_once __DIR__ . '/vendor/autoload.php';

(static function () {
    $builder = new \DI\ContainerBuilder();
    $container = $builder->build();
    $plugin = $container->get(\ContractExpire\Plugin::class);

    try {
        $plugin->run();
    } catch (Exception $e) {
        $logger = new \ContractExpire\Utility\Logger(new PluginLogManager());
        $logger->log(\Psr\Log\LogLevel::ERROR, $e->getMessage());
        $logger->log(\Psr\Log\LogLevel::ERROR, $e->getTraceAsString());
    }
})();
