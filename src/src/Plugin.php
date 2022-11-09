<?php

declare(strict_types=1);

namespace ContractExpire;

// Import needed classes
use Psr\Log\LogLevel;
use ContractExpire\Utility\Logger;
use ContractExpire\Utility\UcrmApi;
use Exception;

class Plugin
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UcrmApi
     */
    private $ucrmApi;

    public function __construct(Logger $logger, UcrmApi $ucrmApi)
    {
        $this->logger = $logger;
        $this->ucrmApi = $ucrmApi;
    }

    public function run(): void
    {
        $this->logger->log(LogLevel::INFO, sprintf('Plugin execution started. (%s)', (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
            'plugin' => 'contract-expire',
        ]);
        $this->process();
        $this->logger->log(logLevel::INFO, sprintf('Plugin execution finished. (%s)', (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
            'plugin' => 'contract-expire',
        ]);
    }

    private function process(): void
    {
        $this->logger->log(LogLevel::INFO, sprintf('Processing started. (%s)', (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
            'plugin' => 'contract-expire',
        ]);
        $this->logger->log(LogLevel::INFO, '--- --- --- --- --- --- --- --- --- ---', [
            'plugin' => 'contract-expire',
        ]);

        try {
            $ucrmApi = new UcrmApi();

            // Get all clients.
            $clients = $ucrmApi->doRequest('clients');

            // Loop through all clients.
            foreach ($clients as $client) {
                $client = $ucrmApi->doRequest(sprintf('clients/%s', $client['id']));

                // Parse the attributes to an ID <=> value array.
                $attributes = [];
                foreach ($client['attributes'] as $attribute) {
                    $attributes[$attribute['id']] = $attribute['value'];
                }

                // Check if the client has a contract end date.
                if (!isset($attributes[157])) {
                    $this->logger->log(LogLevel::INFO, sprintf('Client %s has no contract end date. (%s)', $client['id'], (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
                        'plugin' => 'contract-expire',
                    ]);
                    $this->logger->log(LogLevel::INFO, '--- --- --- --- --- --- --- --- --- ---', [
                        'plugin' => 'contract-expire',
                    ]);
                    continue;
                }

                // Get the contract end date and current date.
                $contractEndDate = new \DateTime($attributes[157]);
                $currentDate = new \DateTime();

                // Get the user's e-mail address from contacts.
                $email = '';
                foreach ($client['contacts'] as $contact) {
                    if ($contact['isContact']) {
                        $email = $contact['email'];
                    }
                }

                // Check if the contract has expired.
                if ($contractEndDate < $currentDate) {
                    // Send e-mail to the user.
                    $this->contractExpired($client['id'], $email, $contractEndDate->format('Y-m-d'));
                }

                // Check if the contract is about to expire in 30, 14 and <= 7 days.
                else if ($contractEndDate->diff($currentDate)->days === 30 || $contractEndDate->diff($currentDate)->days === 14 || $contractEndDate->diff($currentDate)->days <= 7) {
                    // Send an email to the client.
                    $this->sendMail($client['id'], $email);
                }
            }
        } catch (\Exception $e) {
            $this->logger->log(LogLevel::ERROR, $e->getMessage(), [
                'plugin' => 'contract-expire',
            ]);
        }
        $this->logger->log(LogLevel::INFO, sprintf('Processing started. (%s)', (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
            'plugin' => 'contract-expire',
        ]);
    }

    private function sendMail(int $clientId, string $email): void
    {
        try {
            $ucrmApi = new UcrmApi();
            $ucrmApi->doRequest('email/1/enqueue', 'POST', [
                'to' => $email,
                'subject' => 'Contractul dumneavoastra va expira in curand!',
                'body' => 'Contractul dumneavoastra va expira in curand! Va rugam sa il resemnati cat mai repede posibil.',
                'clientId' => $clientId,
            ]);

            $this->logger->log(LogLevel::INFO, sprintf('Email sent (contract is about to expire). (%s)', (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
                'plugin' => 'contract-expire',
                'email' => $email,
            ]);

            $this->logger->log(LogLevel::INFO, '--- --- --- --- --- --- --- --- --- ---', [
                'plugin' => 'contract-expire',
            ]);
        } catch (Exception $e) {
            $this->logger->log(LogLevel::ERROR, $e->getMessage(), [
                'plugin' => 'contract-expire',
            ]);
        }
    }

    private function contractExpired(int $clientId, string $email, string $date): void
    {
        try {
            $ucrmApi = new UcrmApi();
            $ucrmApi->doRequest('email/1/enqueue', 'POST', [
                'to' => $email,
                'subject' => 'Contractul dumneavoastra a expirat!',
                'body' => 'Contractul dumneavoastra a expirat pe data de ' . $date . '. Va rugam sa il resemnati cat mai repede posibil.',
                'clientId' => $clientId,
            ]);

            $this->logger->log(LogLevel::INFO, sprintf('Email sent (contract expired). (%s)', (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
                'plugin' => 'contract-expire',
                'email' => $email,
            ]);
            $this->logger->log(LogLevel::INFO, '--- --- --- --- --- --- --- --- --- ---', [
                'plugin' => 'contract-expire',
            ]);
        } catch (Exception $e) {
            $this->logger->log(LogLevel::ERROR, $e->getMessage(), [
                'plugin' => 'contract-expire',
            ]);
        }
    }
}
