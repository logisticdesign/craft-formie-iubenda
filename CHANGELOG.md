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
