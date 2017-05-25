<?php

/*
 * This file is part of the Cilex framework.
 *
 * (c) Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cilex\Command;

use Cilex\Components\HttpClient;
use Maknz\Slack\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Cilex\Provider\Console\Command;
use Tx\Mailer;

/**
 * Example command for testing purposes.
 */
class ServiceCommand extends Command
{

    /**
     * @var \DateTime
     */
    private $start_service_time;

    /**
     * @var \stdClass
     */
    private $config;

    /**
     * @var resource
     */
    private $dump;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('service')
            ->setDescription('Php service site monitoring');

        $this->start_service_time = new \DateTime();

        if(!file_exists(ROOT."/config/config.json")) {
            throw new \Exception("First configure: ".ROOT."/config/config.json");
        }

        $resource = new \SplFileObject(ROOT."/config/config.json");

        try {

            $this->config = json_decode($resource->fread($resource->getSize()));

        } catch (\Exception $exception) {

            throw new \Exception("Incorrect json format: ".ROOT."/config/config.json");
        }


        $this->dump = new \stdClass();
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("Service started at ".date("d-m-y H:i:s"));

        if(count($this->config->resources) == 0) {
            throw new \Exception("Site not found");
        }

        while(true) {

            foreach($this->config->resources AS $resource) {

                if(!$resource->site) {
                    continue;
                }

                $site_key = str_replace(" ", "_", $resource->name);

                if(!isset($this->dump->{$site_key}) || !$this->dump->{$site_key} instanceof \Monitoring) {
                    $this->dump->{$site_key} = new \Monitoring($resource);
                }

                $this->dump->{$site_key}->run();

                if(NULL !== ($status = $this->dump->{$site_key}->getResourceStatus())) {


                    $this->sendNotify($status, $resource, $this->dump->{$site_key}->getResourceInfo());


                    if(!$status) {
                        $output->writeln($resource->name . " site down");
                    }
                    else {
                        $output->writeln($resource->name . " site up");
                    }
                }

            }

            sleep(10);
        }

        $output->writeln("Service stop at ".date("d-m-y H:i:s"));
    }


    /**
     * @param $status
     * @param $resource
     * @param array $info
     */
    private function sendNotify($status, $resource, array $info) {

        /**
         * SEND MAILS
         */

        try {
            $this->sendMail($status, $resource, $info);
        } catch (\Exception $exception) {
            //@TODO
        }


        /**
         * Send to SLack
         */
        try {
            $this->sendToSlack($status, $resource, $info);
        } catch (\Exception $exception) {
            //@TODO
        }
    }

    /**
     * @param $status
     * @param $resource
     * @param array $info
     */
    private function sendToSlack($status, $resource, array $info) {

        if(!isset($resource->notification->slacks)) {
            return;
        }
        foreach($resource->notification->slacks AS $slack) {

            if(!$slack->hook) {
                continue;
            }

            $settings = [
                'username' => 'PingFail',
                'channel' => $slack->channel ? $slack->channel : "#general",
                'link_names' => true
            ];

            $Slack  = new Client($slack->hook, $settings);

            $last_error = (string) @end($info)["last_error"];

            if(!$status) {
                $data = [
                    'fallback' => 'Current server stats',
                    'text' => "You site  ".$resource->name." [". $resource->site ."] is DOWN at ".date("d-m-Y H:i:s"),
                    'color' => 'danger',
                    'fields' => [
                        [
                            'title' => 'last error',
                            'value' => $last_error
                        ],
                    ]
                ];

            } else {


                $up_date = new \DateTime(end($info)["timestamp"]);
                $down_time = $up_date->diff(new \DateTime($info[0]["timestamp"]));

                /*if($down_time->format("%H:%I:%S") == "00:00:00") {
                    continue;
                }*/

                $data = [
                    'fallback' => 'Current server stats',
                    'text' => "You site ".$resource->name." [". $resource->site ."] is UP at ".date("d-m-Y H:i:s")." after ".$down_time->format("%H:%I:%S"). " inactivity",
                    'color' => 'danger',
                    'fields' => []
                ];

            }

            /*foreach($info AS $row) {
                array_push($data["fields"], [
                    'title' => $row["timestamp"],
                    'value' => 'CODE: '.$row["status_code"].' ERROR: '.$row["last_error"]. " TIMEOUT: ".$row["time_out"],
                    'short' => true
                ]);
            }*/

            $Slack->to($slack->channel ? $slack->channel : "#general")
                ->attach($data)
                ->send('New alert from the monitoring system');

        }
    }

    /**
     * @param $status
     * @param $resource
     * @param array $info
     */
    private function sendMail($status, $resource, array $info) {
        $msg = '<h3>Hi dude!</h3>';
        $msg .= "<p>You site  ".$resource->name." [". $resource->site ."] is ";

        if(!$status) {
            $msg .= ' DOWN at '.date("d-m-Y H:i:s")."</p> </br>";
            $msg .= 'Last log:<br>';
        } else {

            $up_date = new \DateTime(end($info)["timestamp"]);
            $down_time = $up_date->diff(new \DateTime($info[0]["timestamp"]));

            $msg .= ' UP at '.date("d-m-Y H:i:s")." after ".$down_time->format("%H:%I:%S")."  inactivity</p></br>";
            $msg .= 'Last log:<br>';
        }

        $msg .= '<p style="border: 1px solid red; background-color: #cccccc; font-size: 12px; padding: 10px;">';
        foreach($info AS $data) {
            $msg .= $data["timestamp"].' CODE: '.$data["status_code"].' ERROR: '.$data["last_error"].' TIMEOUT: '.$data["time_out"].'<br>';
        }
        $msg .= '</p>';

        $msg .='<br>I\'m sorry if that. PingFail<br>';

        //print_r($this->config);
        if(!isset($resource->notification->emails)) {
            print "empty Emails\n";
            return;
        }


        foreach($resource->notification->emails AS $email) {
            $Mailer = new Mailer();
            $Mailer = $Mailer->setServer($this->config->smtp_host, $this->config->smtp_port);
            if(isset($this->config->smtp_user) && !empty($this->config->smtp_user)) {
                $Mailer->setAuth($this->config->smtp_user, $this->config->smtp_paswd);
            }
            $Mailer->setFrom('PingFail', 'ping.fail@ping-fail.com');
            $Mailer->addTo(null, $email)
                ->setSubject("Site ".$resource->name." is " .($status ? "UP" : "DOWN") )
                ->setBody($msg)
                ->send();

        }

    }
}
