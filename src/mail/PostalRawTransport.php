<?php

namespace WelfordMedia\GoingPostal\mail;

use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class PostalRawTransport extends AbstractApiTransport
{
    protected string $server = '';
    protected string $apiKey = '';

    public function __construct(string $server, string $apiKey, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->server = $server;
        $this->apiKey = $apiKey;
        parent::__construct($client, $dispatcher, $logger);
    }

    public function __toString(): string
    {
        return sprintf('postal+api://%s', $this->server);
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $response = $this->client->request('POST', 'https://' . $this->server . '/api/v1/send/raw', [
            'json' => $this->getPayload($email, $envelope),
            'headers' => [
                'X-Server-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            var_dump($e);
            throw new HttpTransportException('Could not reach the remote server.', $response, 0, $e);
        }

        if (200 !== $statusCode) {
            try {
                $result = $response->toArray(false);
                throw new HttpTransportException('Unable to send an email: '.implode('; ', array_column($result['errors'], 'message')).sprintf(' (code %d).', $statusCode), $response);
            } catch (DecodingExceptionInterface $e) {
                throw new HttpTransportException('Unable to send an email: '.$response->getContent(false).sprintf(' (code %d).', $statusCode), $response, 0, $e);
            }
        }

        $json = json_decode($response->getContent(false), true);

        if (is_array($json) && (isset($json['status']) && $json['status'] !== 'success')) {
            dd($json);
            throw new HttpTransportException('Unable to send an email: '.implode('; ', array_column($json['data'], 'message')).sprintf(' (code %d).', $statusCode), $response);
        }

        $sentMessage->setMessageId($json['data']['message_id']);

        return $response;
    }

    /**
     * @return string[]
     */
    private function getPayload(Email $email, Envelope $envelope): array
    {
        $addressStringifier = function (Address $address) {
            return $address->getAddress();
        };

        $headers = $email->getHeaders();
        $headers->addHeader('Sender', $envelope->getSender()->getAddress());
        $headers->remove('From');
        $email->setHeaders($headers);
        $email->addFrom($envelope->getSender()->getAddress());

        $payload = [
            'mail_from' => $envelope->getSender()->getAddress(),
            'rcpt_to' => array_map($addressStringifier, $this->getRecipients($email, $envelope)),
            'data' => base64_encode($email->toString()),
        ];

        return $payload;
    }

} 