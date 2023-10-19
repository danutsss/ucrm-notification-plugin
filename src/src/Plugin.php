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
            'plugin' => 'ucrm-notification-plugin',
        ]);
        $this->process();
        $this->logger->log(logLevel::INFO, sprintf('Plugin execution finished. (%s)', (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
            'plugin' => 'ucrm-notification-plugin',
        ]);
    }

    private function process(): void
    {
        $this->logger->log(LogLevel::INFO, sprintf('Processing started. (%s)', (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
            'plugin' => 'ucrm-notification-plugin',
        ]);
        $this->logger->log(LogLevel::INFO, '--- --- --- --- --- --- --- --- --- ---', [
            'plugin' => 'ucrm-notification-plugin',
        ]);

        try {
            $ucrmApi = new UcrmApi();

            // Get all clients.
            $clients = $ucrmApi->doRequest('clients');

            // Loop through all clients.
            foreach ($clients as $client) {
                $client = $ucrmApi->doRequest(sprintf('clients/%s', $client['id']));

                // Get client's custom fields.
                $customFields = $client['attributes'];

                foreach ($customFields as $customField) {
                    if ($customField['key'] === 'nextcontractsign') {
                        $contractExpirationDate = $customField['value'];
                    } else if ($customField['key'] === 'cnp') {
                        $cnp = $customField['value'];
                    }
                }

                // Check if the client has a contract end date.
                if (!isset($contractExpirationDate)) {
                    $this->logger->log(LogLevel::INFO, sprintf('Client %s has no contract end date. (%s)', $client['id'], (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
                        'plugin' => 'ucrm-notification-plugin',
                    ]);
                    $this->logger->log(LogLevel::INFO, '--- --- --- --- --- --- --- --- --- ---', [
                        'plugin' => 'ucrm-notification-plugin',
                    ]);
                    continue;
                }

                // Check if the client has a CNP set.
                if (!isset($cnp)) {
                    $this->logger->log(LogLevel::INFO, sprintf('Client %s has no CNP set. (%s)', $client['id'], (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
                        'plugin' => 'ucrm-notification-plugin',
                    ]);
                    $this->logger->log(LogLevel::INFO, '--- --- --- --- --- --- --- --- --- ---', [
                        'plugin' => 'ucrm-notification-plugin',
                    ]);
                    continue;
                }

                // Get the contract end date and current date.
                $contractEndDate = new \DateTime($contractExpirationDate);
                $currentDate = new \DateTime();

                // Get the user's e-mail address from contacts.
                $email = '';
                foreach ($client['contacts'] as $contact) {
                    if ($contact['isContact']) {
                        $email = $contact['email'];
                    }
                }

                // Check if the contract has expired or if the contract is about to expire in 30, 14 and <= 7 days..
                if ($contractEndDate < $currentDate) {
                    // Send e-mail to the user.
                    $this->sendMail($client['id'], $email, "Contractul dvs. a expirat la data: $contractEndDate. Va rugam sa accesati platforma si sa-l resemnati.", 'Contractul dvs. a expirat!');
                } else if ($contractEndDate->diff($currentDate)->days === 30 || $contractEndDate->diff($currentDate)->days === 14 || $contractEndDate->diff($currentDate)->days <= 7) {
                    // Send an email to the client.
                    $this->sendMail($client['id'], $email, "Contractul dvs. o sa expire la data: $contractEndDate. Va rugam sa accesati platforma si sa-l resemnati.", 'Contractul dvs. trebuie resemnat!');
                }

                // Send an email to the client on its birthday.
                $birthdate = $this->extractBirthDateFromCNP($cnp);
                if ($birthdate === false) {
                    $this->logger->log(LogLevel::INFO, sprintf('Client %s has an invalid CNP. (%s)', $client['id'], (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
                        'plugin' => 'ucrm-notification-plugin',
                    ]);
                } else {
                    $today = new \DateTimeImmutable();
                    $formattedBirthdate = $birthdate->format('Y-m-d');

                    if ($formattedBirthdate === $today->format('Y-m-d')) {
                        $this->sendMail($client['id'], $email, "La multi ani!", 'Echipa 07INTERNET va ureaza La multi ani!');
                    } else {
                        $this->logger->log(LogLevel::INFO, sprintf('Client %s has no birthday today. (%s)', $client['id'], (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
                            'plugin' => 'ucrm-notification-plugin',
                        ]);
                    }
                }

                // Send an email to the client if it's a female on 8th of March.
                $gender = $this->extractGenderFromCNP($cnp);
                $today = new \DateTimeImmutable();
                $eightOfMarch = new \DateTimeImmutable(date('Y') . '-03-08');
                if ($gender === false) {
                    $this->logger->log(LogLevel::INFO, sprintf('Client %s has an invalid CNP. (%s)', $client['id'], (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
                        'plugin' => 'ucrm-notification-plugin',
                    ]);
                } else if ($gender === "Feminin" && $today->format('Y-m-d') === $eightOfMarch->format('Y-m-d')) {
                    $this->sendMail($client['id'], $email, "Primavara frumoasa!", 'Echipa 07INTERNET va ureaza o primavara frumoasa si o zi a femeii fericita!');
                }
            }
        } catch (\Exception $e) {
            $this->logger->log(LogLevel::ERROR, $e->getMessage(), [
                'plugin' => 'ucrm-notification-plugin',
            ]);
        }
        $this->logger->log(LogLevel::INFO, sprintf('Processing started. (%s)', (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
            'plugin' => 'ucrm-notification-plugin',
        ]);
    }

    private function sendMail(int $clientId, string $email, string $body, string $subject): void
    {
        try {
            $ucrmApi = new UcrmApi();
            $ucrmApi->doRequest('email/1/enqueue', 'POST', [
                'to' => $email,
                'subject' => $subject,
                'body' => $body,
                'clientId' => $clientId,
            ]);

            $this->logger->log(LogLevel::INFO, sprintf('Email sent. (%s)', (new \DateTimeImmutable())->format('Y-m-d H:i:s')), [
                'plugin' => 'ucrm-notification-plugin',
                'email' => $email,
            ]);

            $this->logger->log(LogLevel::INFO, '--- --- --- --- --- --- --- --- --- ---', [
                'plugin' => 'ucrm-notification-plugin',
            ]);
        } catch (Exception $e) {
            $this->logger->log(LogLevel::ERROR, $e->getMessage(), [
                'plugin' => 'ucrm-notification-plugin',
            ]);
        }
    }

    private function extractBirthDateFromCNP(string $cnp): \DateTimeImmutable
    {
        // Verificăm dacă CNP-ul are lungimea corectă (13 caractere) și conține doar cifre
        if (strlen($cnp) !== 13 || !ctype_digit($cnp)) {
            return false;
        }

        // Extragem luna și ziua din CNP
        $month = intval(substr($cnp, 3, 2));
        $day = intval(substr($cnp, 5, 2));

        // Compunem data de naștere fără an în format "Y-m-d"
        $today = new \DateTimeImmutable();
        $birthdate = $today->format('Y') . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);

        // Creăm un obiect DateTimeImmutable din data de naștere și returnăm
        return \DateTimeImmutable::createFromFormat('Y-m-d', $birthdate);
    }

    private function extractGenderFromCNP(string $cnp): string
    {
        // Verificăm dacă CNP-ul are lungimea corectă (13 caractere) și conține doar cifre
        if (strlen($cnp) !== 13 || !ctype_digit($cnp)) {
            return false;
        }

        // Extragem prima cifră din CNP, care indică sexul
        $firstDigit = intval(substr($cnp, 0, 1));

        // Determinăm sexul în funcție de prima cifră
        if ($firstDigit % 2 === 0) {
            return "Feminin";
        } else {
            return "Masculin";
        }
    }
}
