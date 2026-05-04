<?php

namespace AppBundle\Tool;

use LogicException;

class SignatureAPITool
{
    const COMMON_PATH = '/api/share';

    private string $endpoint;
    private $curl;

    public function __construct($endpoint)
    {
        if (extension_loaded('curl') === false) {
            throw new LogicException("cURL extension must be enabled");
        }

        if (filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            throw new LogicException("Endpoint $endpoint is not a valid URL");
        }

        $this->endpoint = rtrim($endpoint, '/');

        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'aurouze curl/'.curl_version()['version']);
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    }

    public function upload($file, $filename)
    {
        $mime = mime_content_type($file);

        if ($mime !== 'application/pdf') {
            throw new LogicException("Not a valid pdf");
        }

        $cfile = curl_file_create($file, $mime, $filename);
        $url = sprintf('%s%s/%s', $this->endpoint, self::COMMON_PATH, 'new');

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, ['pdf' => $cfile, 'duration' => '+1 month']);
        $json = curl_exec($this->curl);

        if ($json === false) {
            trigger_error(curl_error($this->curl));
        }

        curl_close($this->curl);

        return json_decode($json);
    }
}
