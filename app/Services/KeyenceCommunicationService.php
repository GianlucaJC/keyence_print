<?php

namespace App\Services;

use App\Models\Printer;
use RuntimeException;

class KeyenceCommunicationService
{
    public function buildMessageCommand(int $programNumber, int $messageNumber, string $text): string
    {
        if ($programNumber < 1 || $programNumber > 500) {
            throw new RuntimeException('Il numero programma deve essere compreso tra 1 e 500.');
        }

        if ($messageNumber < 1 || $messageNumber > 64) {
            throw new RuntimeException('Il numero messaggio deve essere compreso tra 1 e 64.');
        }

        if ($text === '' || strlen($text) > 999 || preg_match('/[^\x20-\x7E]/', $text) === 1 || str_contains($text, ',')) {
            throw new RuntimeException('Il testo deve contenere da 1 a 999 caratteri ASCII stampabili e non può contenere virgole.');
        }

        return sprintf('H2,%d,%d,%s', $programNumber, $messageNumber, $text);
    }

    public function requestSystemStatus(Printer $printer): string
    {
        return $this->send($printer, 'SB');
    }

    public function send(Printer $printer, string $command): string
    {
        $wireCommand = $this->encode($printer, $command);
        $timeoutSeconds = max(1, (int) ceil($printer->timeout_ms / 1000));
        $address = sprintf('tcp://%s:%d', $printer->host, $printer->port);

        $socket = @stream_socket_client($address, $errorNumber, $errorMessage, $timeoutSeconds);

        if ($socket === false) {
            throw new RuntimeException(sprintf('Connessione a %s non riuscita: %s (%d).', $address, $errorMessage, $errorNumber));
        }

        try {
            stream_set_timeout($socket, $timeoutSeconds);

            if (fwrite($socket, $wireCommand) === false) {
                throw new RuntimeException('Invio del comando alla stampante non riuscito.');
            }

            $response = $this->readResponse($socket, $printer->protocol_format);
        } finally {
            fclose($socket);
        }

        if ($response === '') {
            throw new RuntimeException('La stampante non ha restituito una risposta entro il timeout configurato.');
        }

        if (str_starts_with($this->decode($response, $printer->protocol_format), 'ER,')) {
            throw new RuntimeException('La stampante ha restituito un errore: '.$this->decode($response, $printer->protocol_format));
        }

        return $this->decode($response, $printer->protocol_format);
    }

    public function encode(Printer $printer, string $command): string
    {
        $body = $printer->checksum_enabled ? $command.','.$this->checksum($command.',') : $command;

        return $printer->protocol_format === 'stx_etx' ? "\x02".$body."\x03" : $body."\r";
    }

    private function readResponse($socket, string $protocolFormat): string
    {
        $terminator = $protocolFormat === 'stx_etx' ? "\x03" : "\r";
        $response = '';

        while (!str_ends_with($response, $terminator)) {
            $chunk = fread($socket, 256);

            if ($chunk === false || $chunk === '') {
                $metadata = stream_get_meta_data($socket);

                if ($metadata['timed_out']) {
                    break;
                }

                continue;
            }

            $response .= $chunk;

            if (strlen($response) > 2505) {
                throw new RuntimeException('La risposta della stampante supera la dimensione prevista dal protocollo.');
            }
        }

        return $response;
    }

    private function decode(string $response, string $protocolFormat): string
    {
        return $protocolFormat === 'stx_etx'
            ? trim($response, "\x02\x03")
            : rtrim($response, "\r\n");
    }

    private function checksum(string $value): string
    {
        $checksum = 0;

        foreach (str_split($value) as $character) {
            $checksum ^= ord($character);
        }

        return strtoupper(str_pad(dechex($checksum), 2, '0', STR_PAD_LEFT));
    }
}
