# CatFax

An easy way to use your [Twilio][1] account to send cat facts to your "friends" and record their responses.

### Install

 1. clone this repository to a public-facing website so that `sms.php` and `voice.php` are publicly accessible.

 1. Change 3 values at the top of CatFax.php to whatever your values should be: `$from_number`, `$tw_account_sid`, and `$tw_account_token`.

 1. Add some numbers to the `$numbers` array. In the form:

    static $numbers = array(
        '1xxxxxxxxxx' => 'Their Name'
    )

 1. Make sure the file `$log_file` is writable.

 1. Set the Voice and SMS links in Twilio.

### Usage

 1. In a cron job, add `/path/to/cloned/repo/run send`. This will iterate over all `$numbers` and send them a random cat fact.

 1. To view a conversation, `./run view [number]`




[1]: http://twilio.com