<?php

namespace WelfordMedia\GoingPostal;

use Craft;
use WelfordMedia\GoingPostal\models\Settings;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\MailerHelper;
use WelfordMedia\GoingPostal\mail\PostalAdapter;
use yii\base\Event;

/**
 * Going Postal plugin
 *
 * @method static GoingPostal getInstance()
 * @method Settings getSettings()
 * @author Welford Media <hello@welfordmedia.co.uk>
 * @copyright Welford Media
 * @license https://craftcms.github.io/license/ Craft License
 */
class GoingPostal extends Plugin
{
    public string $schemaVersion = '1.0.0';

    public function init(): void
    {
        parent::init();
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
        });
    }

    private function attachEventHandlers(): void
    {
        Event::on(MailerHelper::class, MailerHelper::EVENT_REGISTER_MAILER_TRANSPORT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = PostalAdapter::class;
            }
        );
    }
}
