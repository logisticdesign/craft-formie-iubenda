# Release Notes for Iubenda for Formie

## 1.0.0
- Initial release

## 1.0.1
- Attach event handlers on Craft init

## 1.0.2
- Fixed ip_address when submission running in queue
- Uses Opt-In Field instead of field mapping for privacy

## 1.0.3
- Fixed consents being recorded with `preferences.privacy` set to `false` since 1.0.2. A consent is only sent when the Opt-In Field (if set) has been accepted, so privacy is now always sent as `true`.

## 2.0.0
> [!IMPORTANT]
> Requires Formie 4. Use version 1.x for Formie 3.

### Changed
- Updated the integration to the Formie 4 custom integration API:
  - The field mapping (`fieldMapping`) is now declared with `#[FormIntegrationSetting]`. Without it Formie 4 neither saves nor populates the mapping, so consents were sent without subject data.
  - Form settings are now defined with `defineFormSettingsSchema()` instead of the `_formSettings` Twig template. The Opt-In Field is provided by Formie.
  - `fetchFormSettings()` → `fetchConfig()` / `IntegrationConfig`
  - `sendPayload()` → `executePayload()`, returning an `IntegrationResult`
  - `getClient()` → `defineClient()`
  - `Submission::getValuesAsJson()` → `Submission::getValuesAsData()` for the consent proof.
- The private key (`privateKey`) is now marked with `#[Sensitive]` so it is redacted from logs and diagnostics.

### Removed
- Removed the unused `boolToString()` method.
