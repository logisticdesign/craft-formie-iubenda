<?php

namespace logisticdesign\formieiubenda\integrations\miscellaneous;

use Craft;
use craft\helpers\App;
use GuzzleHttp\Client;
use Throwable;
use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\attributes\Sensitive;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;

class ConsentDatabase extends Miscellaneous
{
    #[Sensitive]
    public ?string $privateKey = null;

    #[FormIntegrationSetting]
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

    public function fetchConfig(): IntegrationConfig
    {
        $fields = [
            new IntegrationField([
                'handle' => 'email',
                'name' => Craft::t('formie-iubenda', 'Email'),
                'required' => true,
            ]),
            new IntegrationField([
                'handle' => 'firstName',
                'name' => Craft::t('formie-iubenda', 'First name'),
            ]),
            new IntegrationField([
                'handle' => 'lastName',
                'name' => Craft::t('formie-iubenda', 'Last name'),
            ]),
            new IntegrationField([
                'handle' => 'fullName',
                'name' => Craft::t('formie-iubenda', 'Full name'),
            ]),
        ];

        return new IntegrationConfig([
            'main' => $fields,
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

    protected function executePayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);

        try {
            // Unlike Crm, Miscellaneous doesn't resolve a config key passed as a string
            $formValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $this->getConfigValue('main'));

            $payload = [
                'subject' => [
                    'email' => $formValues['email'] ?? null,
                    'first_name' => $formValues['firstName'] ?? null,
                    'last_name' => $formValues['lastName'] ?? null,
                    'full_name' => $formValues['fullName'] ?? null,
                ],
                'preferences' => [
                    'form_name' => $submission->getFormHandle(),
                    'privacy' => 'true',
                ],
                'proofs' => [
                    [
                        'content' => json_encode($submission->getValuesAsData()),
                    ],
                ],
                'legal_notices' => [
                    ['identifier' => 'privacy_policy'],
                    ['identifier' => 'cookie_policy'],
                ],
                'ip_address' => $submission->ipAddress,
                'autodetect_ip_address' => false,
            ];

            $response = $this->deliverPayload($submission, 'consent', $payload);

            if ($response === false) {
                return $this->resultForPayload(false);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return $this->resultForPayload(false);
        }

        return $this->resultForPayload(true);
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://consent.iubenda.com/',
            'headers' => [
                'ApiKey' => App::parseEnv($this->privateKey),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        // The parent schema already includes the Enabled and Opt-In Field settings
        $schema = parent::defineFormSettingsSchema($form);

        $schema[] = $this->createFieldMappingField([
            'name' => 'fieldMapping',
            'dataLabel' => 'Iubenda',
            'dataKey' => 'main',
        ]);

        return $schema;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['privateKey'], 'required'];

        $main = $this->getConfigValue('main');

        // Validate when saving form settings
        $rules[] = [
            ['fieldMapping'], 'validateFieldMapping', 'params' => $main, 'when' => function($model) {
                return $model->enabled;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }
}
