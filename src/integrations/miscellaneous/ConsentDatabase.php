<?php

namespace logisticdesign\formieiubenda\integrations\miscellaneous;

use Craft;
use craft\helpers\App;
use GuzzleHttp\Client;
use Throwable;
use verbb\formie\base\Integration;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

class ConsentDatabase extends Miscellaneous
{
    public ?string $privateKey = null;

    public ?array $fieldMapping = null;

    public static function displayName(): string
    {
        return Craft::t('formie', 'Iubenda Consent Database');
    }

    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl('@logisticdesign/formieiubenda/icon.svg', true);
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Integration of Iubenda Consent Database.');
    }

    public function getSettingsHtml(): string
    {
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate('formie-iubenda/integrations/miscellaneous/consentDatabase/_pluginSettings', $variables);
    }

    public function getFormSettingsHtml($form): string
    {
        $variables = $this->getFormSettingsHtmlVariables($form);

        return Craft::$app->getView()->renderTemplate('formie-iubenda/integrations/miscellaneous/consentDatabase/_formSettings', $variables);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $fields = [
            new IntegrationField([
                'handle' => 'email',
                'name' => Craft::t('formie-iubenda', 'Email'),
                'required' => true,
            ]),
            new IntegrationField([
                'handle' => 'first_name',
                'name' => Craft::t('formie-iubenda', 'First name'),
            ]),
            new IntegrationField([
                'handle' => 'last_name',
                'name' => Craft::t('formie-iubenda', 'Last name'),
            ]),
            new IntegrationField([
                'handle' => 'full_name',
                'name' => Craft::t('formie-iubenda', 'Full name'),
            ]),
            new IntegrationField([
                'handle' => 'privacy',
                'name' => Craft::t('formie-iubenda', 'Privacy'),
            ]),
        ];

        return new IntegrationFormSettings([
            'main' => $fields,
        ]);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $formValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $this->getFormSettingValue('main'));

            $payload = [
                'subject' => [
                    'email' => $formValues['email'] ?? null,
                    'first_name' => $formValues['first_name'] ?? null,
                    'last_name' => $formValues['last_name'] ?? null,
                    'full_name' => $formValues['full_name'] ?? null,
                ],
                'preferences' => [
                    'form_name' => $submission->getFormHandle(),
                    'privacy' => $this->boolToString($formValues['privacy'] ?? false),
                ],
                'proofs' => [
                    [
                        'content' => json_encode($submission->getValuesAsJson()),
                    ],
                ],
                'legal_notices' => [
                    ['identifier' => 'privacy_policy'],
                    ['identifier' => 'cookie_policy'],
                ],
                'ip_address' => Craft::$app->getRequest()->getUserIP(),
                'autodetect_ip_address' => false,
            ];

            $this->deliverPayload($submission, 'consent', $payload);

        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function getClient(): Client
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://consent.iubenda.com/',
            'headers' => [
                'ApiKey' => App::parseEnv($this->privateKey),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function fetchConnection(): bool
    {
        try {
            $this->request('GET', '/consent');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    protected function boolToString(mixed $value): string
    {
        return $value == true ? 'true' : 'false';
    }


    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['privateKey'], 'required'];

        $main = $this->getFormSettingValue('main');

        // Validate when saving form settings
        $rules[] = [
            ['fieldMapping'], 'validateFieldMapping', 'params' => $main, 'when' => function($model) {
                return $model->enabled;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }
}
