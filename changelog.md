# Changelog

## v1.0.3 - Flag files
- Added flag files to indicate the current configuration mode (DEV or PROD).

## v1.0.2 - Fixed updating lock files
- Fixed an issue where the lock file was not updated when switching configurations.
- Added the `switchUpdate()` method to update the current configuration based on modified dates.

## v1.0.1 - Handling improvements
- Switching PROD to PROD now updates either production configs using modified dates.
- Added some useful messages.
- Improved some error messages.

## v1.0.0 - Initial Release
- First version of the project.
