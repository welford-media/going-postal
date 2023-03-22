<?php

namespace WelfordMedia\GoingPostal\mail;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use craft\mail\transportadapters\BaseTransportAdapter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class PostalAdapter extends BaseTransportAdapter
{
    public static function displayName(): string
    {
        return 'Postal';
    }

    public string $apiKey = '';

    public string $host = '';

    /**
     * @return array<string, string>
     */
    public function attributeLabels(): array
    {
        return [
            'apiKey' => 'API Key',
            'host' => 'Postal Server Hostname or IP Address',
        ];
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('going-postal/_settings', [
            'adapter' => $this,
        ]);
    }

    /**
     * @return mixed[]|AbstractTransport
     */
    public function defineTransport(): array|AbstractTransport
    {
        $apiKey = App::parseEnv($this->apiKey);
        $host = App::parseEnv($this->host);
        $client = HttpClient::create();
        return new PostalTransport($host, $apiKey, $client);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defineBehaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['apiKey', 'host'],
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    protected function defineRules(): array
    {
        return [
            [['apiKey', 'host'], 'required'],
        ];
    }
}