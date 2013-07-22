<?php


/**
 * Class Extractor
 */
class Extractor
{
    private $_twilioAccountSid;
    private $_twilioAuthToken;

    private $_amazonKey;
    private $_amazonSecret;
    private $_bucket;

    private $_s3;
    private $_twilio;

    const TYPE_WAV = ".wav";
    const TYPE_MP3 = ".mp3";

    /**
     * @param $amazonKey
     * @param $amazonSecret
     * @param $twilioAccount
     * @param $twilioSecret
     * @param $bucket
     */
    public function __construct(
        $amazonKey,
        $amazonSecret,
        $twilioAccount,
        $twilioSecret,
        $bucket
    )
    {
        $this->_twilioAccountSid = $twilioAccount;
        $this->_twilioAuthToken = $twilioSecret;

        $this->_amazonKey = $amazonKey;
        $this->_amazonSecret = $amazonSecret;

        $this->_bucket = $bucket;
    }

    /**
     *
     */
    public function connect()
    {

        try {

            CFCredentials::set(array(

                                   // Credentials for the development environment.
                                   'development' => array(
                                       'key' => $this->_amazonKey,
                                       'secret' => $this->_amazonSecret,
                                       'default_cache_config' => '',
                                       'certificate_authority' => false
                                   ),

                                   // Specify a default credential set to use if there are more than one.
                                   '@default' => 'development'
                               ));

            $this->_s3 = new AmazonS3();

            $this->_twilio = new Services_Twilio($this->_twilioAccountSid, $this->_twilioAuthToken);

        } catch (Exception $e) {

        }
    }

    /**
     * @param $recordingID
     * @return bool
     */
    public function extractAndRelocate($recordingID)
    {

        $mp3 = $this->_extractRecording($recordingID, self::TYPE_MP3);
        $wav = $this->_extractRecording($recordingID, self::TYPE_WAV);

        $mp3_rc = false;
        if (isset($mp3)) {
            $mp3_rc = $this->_uploadToAWS($this->_bucket, $recordingID . self::TYPE_MP3, $mp3);
        } else {
            error_log("UNABLE TO FETCH ${recordingID} as mp3");
        }

        $wav_rc = false;
        if (isset($wav)) {
            $wav_rc = $this->_uploadToAWS($this->_bucket, $recordingID . self::TYPE_WAV, $wav);
        } else {
            error_log("UNABLE TO FETCH ${recordingID} as wav");
        }


        if ($mp3_rc && $wav_rc) {
            $this->_removeRecording($recordingID);
        }

        $base = "https://s3.amazonaws.com/" . $this->_bucket . "/" . $this->_twilioAccountSid . "/" . $recordingID;

        $urls = array(
            "mp3" => $base . self::TYPE_MP3,
            "wav" => $base . self::TYPE_WAV
        );

        return ($urls);
    }

    /**
     * @param $recordingID
     */
    private function _removeRecording($recordingID)
    {
        $this->_twilio->account->recordings->delete($recordingID);
    }

    /**
     * @param $recordingID
     * @param $type
     *
     * @return string
     */
    private function _extractRecording($recordingID, $type = ".mp3")
    {

        $audio = $this->_twilio->account->recordings->get($recordingID);
        $ch = curl_init("https://api.twilio.com" . $audio->uri . $type);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $audio = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
            return $audio;
        } else {
            return null;
        }
    }

    /**
     * @param $bucket
     * @param $filename
     * @param $data
     *
     * @return bool
     */
    private function _uploadToAWS($bucket, $filename, $data)
    {
        $filename = $this->_twilioAccountSid . "/" . $filename;

        $response = $this->_s3->create_object(
            $bucket,
            $filename,
            array(
                "body" => $data,
                "acl" => AmazonS3::ACL_PUBLIC
            )
        );

        if ($response->isOK()) {
            $url = $this->_s3->get_object_url($bucket, $filename, array('https' => false));
            return ($url);
        } else {
            error_log("UNABLE TO UPLOAD TO AWS ${filename}");
            return (false);
        }

    }
}