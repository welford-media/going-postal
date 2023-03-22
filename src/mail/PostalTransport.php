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

class PostalTransport extends AbstractApiTransport
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
        $response = $this->client->request('POST', 'https://' . $this->server . '/api/v1/send/message', [
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

        if (is_array($json) && !isset($json['status']) && $json['status'] !== 'success') {
            throw new HttpTransportException('Unable to send an email: '.implode('; ', array_column($json['errors'], 'message')).sprintf(' (code %d).', $statusCode), $response);
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

        $payload = [
            'from' => $envelope->getSender()->getAddress(),
            'to' => array_map($addressStringifier, $this->getRecipients($email, $envelope)),
            'subject' => $email->getSubject(),
            'html_body' => $email->getHtmlBody(),
            'plain_body' => $email->getTextBody(),
        ];

        if ($emails = array_map($addressStringifier, $email->getCc())) {
            $personalization['cc'] = $emails;
        }

        if ($emails = array_map($addressStringifier, $email->getBcc())) {
            $personalization['bcc'] = $emails;
        }

        if ($emails = array_map($addressStringifier, $email->getReplyTo())) {
            $payload['reply_to'] = $emails[0];
        }

        return $payload;
    }

} 