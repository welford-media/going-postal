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

    public int $useRawMessage;

    /**
     * @return array<string, string>
     */
    public function attributeLabels(): array
    {
        return [
            'apiKey' => 'API Key',
            'host' => 'Postal Server',
            'useRawMessage' => 'Send using RAW messages',
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
        if (App::parseEnv($this->useRawMessage)) {
            return new PostalRawTransport($host, $apiKey, $client);
        }
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
                'attributes' => ['apiKey', 'host', 'useRawMessage'],
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['apiKey', 'host'], 'required'];
        $rules[] = [['useRawMessage'], 'integer', 'min' => 0, 'max' => 1];
        return $rules;
    }
}