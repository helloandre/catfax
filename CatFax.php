<?php
require('Services/Twilio.php');

class CatFax {
    static $log_file = "action.log";
    static $from_number = '+1xxxxxxxxxx';
    static $tw_account_sid = 'ACxxxxxxxxxxxxxx';
    static $tw_account_token = 'xxxxxxxxxxxxxxxxx';


    static $numbers = array(
        // 'number' => 'Name'
    );

    static $facts = array(
        "Cats actually only have 8 lives.",
        "Cats always land on their hind feet."
    );

    // see get_random() for formatting
    static $responses = array(
        'voice' => array(
            'Hello {{name}}, welcome to Cat Facts! Your source for the best cat facts around. Thank you for your interest in cat facts. Goodbye.',
            'Hi {{name}}, you have reached Cat Facts. We are not available right now. Please don\'t ever call again. Goodbye'
        ),
        'sms' => array(
            'We couldn\'t understand that. Please type slower.',
            'All inqueries must be sent by carrier pidgeon.'
        )
    );

    /**
     * @param string $type facts|numbers|voice|sms
     * @param string $name to replace {{name}} in returned string
     * @return string properly formatted string
     */
    static function get_random($type, $number){
        if ($type == 'voice' || $type == 'sms'){
            $obj = self::$responses[$type];
        } else {
            $obj = self::${$type};
        }
        $rand = rand(0, count($obj)-1);
        return str_replace('{{name}}', self::$numbers[$number], $obj[$rand]);
    }

    /**
     * @param string $to number to send message to
     * @param boolean $fake if true, message not actually sent via twilio
     */
    static function send($to, $body){
        try {
            $tw = new Services_Twilio(self::$tw_account_sid, self::$tw_account_token);
            $message = $tw->account->sms_messages->create(
              self::$from_number, // From a valid Twilio number
              '+'.trim($to, '+'), // Text this number
              $body
            );
        } catch (Exception $e) {
            $body = "ERROR: " . $e->getMessage();
        }

        self::log($body, $to);
        return $body;
    }

    /**
     * @param string $body to be sent to $to
     * @param string $to valid phone number
     * @param boolean $out did we send this?
     * @param boolean $voice was this a voice communication?
     */
    static function log($body, $to, $out=true, $voice=false){
        $to = trim($to, '+ ');
        $data = array(
            'time' => time(),
            'to' => $to,
            'out' => $out,
            'voice' => $voice,
            'body' => $body
        );
        file_put_contents(self::$log_file, serialize($data)."\n", FILE_APPEND);
    }

    /** 
     * Returns data from logs.
     * @param array $to numbers to fetch logs
     */
    static function fetch_log($to){
        $log = array();
        foreach ($to as $num){
            $log[$num] = array();
        }

        $fh = fopen(self::$log_file, "r");
        
        while($line = fgets($fh)){
            $data = unserialize($line);
            if (array_key_exists($data['to'], $log)){
                $log[$data['to']] []= $data;
            }
        }

        return $log;
    }
}

/** 
 * Command line interface for interacting with CatFax
 */
class CatFaxScript {
    static $min_time = 9;
    static $max_time = 22;

    static function ln($line=""){
        print $line ."\n";
    }

    static function usage($prepend=""){
        $usage = array(
            "Usage: php catfax.php send|view|fake [to] [send|in] [fake_body]",
            "\t- send : send a fact to [to]. if [to] is omitted, all numbers in CatFax::numbers will be sent a fact.",
            "\t- view : view logs for [to]. if [to] is ommitted, logs for all numbers will be displayed.",
            "\t- fake : for testing. [to] required. does not actually send anything. if 'in', [fake_body] is logged as if [to] had responded"
        );

        if (!empty($prepend)){
            self::ln(" >> " . $prepend);
        }
        foreach ($usage as $u){
            self::ln($u);
        }
        die();
    }

    static function valid_to($to="", $required=false){
        if (!empty($to)){
            if (!is_numeric($to)){
                self::usage("$to is not a number");
            }
            if (!array_key_exists($to, CatFax::$numbers)){
                self::usage("$to is not in CatFax::\$numbers");
            }
        } else if ($required){
            self::usage("[to] is required");
        }
    }

    static function send($to=""){
        // only bother between 9AM and 10PM
        $time = date("G");
        if ($time < self::$min_time && $time > self::$max_time){
            self::ln("not between 9am and 10pm");
            die();
        }
        // only send 1/2 of the time
        if (rand(0, 3) !== 1){
            self::ln("random do not send");
            die();
        }

        self::valid_to($to);
        if (empty($to)){
            $to = array_keys(CatFax::$numbers);
        }

        $send = array();
        foreach ($to as $number){
            $body = CatFax::get_random('facts', $number);
            $sent[$number] = CatFax::send($number, $body);
        }
        
        foreach ($sent as $number=>$body){
            self::ln("$number -> $body");
        }
    }

    static function view($to=""){
        self::valid_to($to);
        if (empty($to)){
            $to = array_keys(CatFax::$numbers);
        } else {
            $to = array($to);
        }

        $log = CatFax::fetch_log($to);
        foreach ($log as $number=>$entries){
            self::ln(CatFax::$numbers[$number] . " (" . $number . ")");
            foreach($entries as $entry){
                self::ln(date("\tj M y h:i:s:a", $entry['time']));
                $line = "";
                if ($entry['out']) {
                    $line .= " >>>>>> ";
                } else {
                    $line .= " <<<<<< ";
                }
                if ($entry['voice']){
                    $line .= "CALL: ";
                }
                $line .= $entry['body'];
                self::ln($line);
            }
        }
    }

    static function fake($to="", $action="", $fake_body=""){
        self::valid_to($to, true);
        if (empty($action)){
            self::usage("no action specified");
        }
        switch ($action){
            case 'send':
                $body = CatFax::get_random('facts', $to);
                CatFax::log($body, $to);
                break;
            case 'in':
                $body = empty($fake_body) ? "fake body" : $fake_body;
                CatFax::log($body, $to, false);
                break;
            default:
                self::usage("$action not found");
        }
    }
}