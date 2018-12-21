# User Agent Info
This module collects various information and can pipe it into fields on a survey.  You could use this to
record various user agent settings as field values in your survey for later analysis, etc.

## Can I add more options?
To add a new option you would modify the config.json choices and the buildOptions method in the main class.

## What does the overwrite button do?
Normally, this will only save when the input field is blank.  However, if you check overwrite it will replace
any value in that input with the new value