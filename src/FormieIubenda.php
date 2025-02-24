<?php

namespace logisticdesign\formieiubenda;

use Craft;
use craft\base\Plugin;
use logisticdesign\formieiubenda\integrations\miscellaneous\ConsentDatabase;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

/**
 * Iubenda for Formie plugin
 *
 * @method static FormieIubenda getInstance()
 * @author Logistic Design <dev@logisticdesign.it>
 * @copyright Logistic Design
 * @license MIT
 */
class FormieIubenda extends Plugin
{
    public string $schemaVersion = '1.0.0';

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            // ...
        });
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            Integrations::class,
            Integrations::EVENT_REGISTER_INTEGRATIONS,
            function(RegisterIntegrationsEvent $event) {
                $event->miscellaneous[] = ConsentDatabase::class;
            }
        );
    }
}
