<?php

/**
 * Created by PhpStorm.
 * User: user
 * Date: 28.02.17
 * Time: 23:34
 */
class Monitoring {

    /**
     * @var bool
     */
    private $status = true;

    /**
     * @var bool
     */
    private $notyfy_status = true;

    /**
     * @var \Cilex\Components\HttpClient
     */
    private $http;

    /**
     * @var \Cilex\Components\Logger
     */
    private $log;

    /**
     * @var string
     */
    private $site;

    /**
     * @var array
     */
    private $info = [];

    /**
     * Monitoring constructor.
     *
     * @param $resource stdClass
     * @throws Exception
     */
    public function __construct($resource) {

        $this->http = new \Cilex\Components\HttpClient();

        if(isset($resource->timeout)) {
            $this->http->set_timeout($resource->timeout);
        }
        if(!$resource->site) {
            throw new \Exception("Site empty");
        }

        $this->site = (string) $resource->site;

        $this->log = new \Cilex\Components\Logger(ROOT."/logs/".$resource->name.".log");

        $this->log->log("Initialize");
    }

    /**
     * @return bool|null
     */
    public function getResourceStatus() {

        if($this->status !== $this->notyfy_status) {
            $this->notyfy_status = (bool) $this->status;
            return (bool) $this->status;
        }

        return null;
    }

    /**
     * @return array
     */
    public function getResourceInfo() {
        $array =  (array) $this->info;
        $this->info = [];
        return $array;
    }

    /**
     *
     */
    public function run(){

        for($i=0; $i < 3; $i++) {

            $this->http->set_header('User-Agent','Mozilla/5.0 (Windows NT 5.1; rv:13.0) Gecko/20100101 Firefox/13.0.1');
            $this->http->get($this->site);

            $this->log->log($this->site. " Status: ".$this->http->get_status(). " | ex.time:".$this->http->get_answer_time()." | error: ".$this->http->getError());

            if($this->http->get_status() >= 400 || $this->http->get_status() <= 0) {
                $this->status = false;
                array_push($this->info, [
                    "timestamp" => date("d-m-y H:i:s"),
                    "last_error" => $this->http->getError(),
                    "status_code" => $this->http->get_status(),
                    "time_out" => $this->http->get_answer_time()
                ]);
            }

            if($this->http->get_status() < 400 && $this->http->get_status() > 0) {

                if(!$this->status) {
                    array_push($this->info, [
                        "timestamp" => date("d-m-y H:i:s"),
                        "last_error" => $this->http->getError(),
                        "status_code" => $this->http->get_status(),
                        "time_out" => $this->http->get_answer_time()
                    ]);
                } else {
                    $this->status = true;
                    return true;
                }

                $this->status = true;

            }

            sleep(2);
        }

    }
}