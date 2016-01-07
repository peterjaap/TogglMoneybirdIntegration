<?php

namespace TogglMoneybird;

use AJT\Toggl\TogglClient;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Console;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;

class IntegrateCommand extends Command
{
    const CONFIG_FILE = 'config.yml';
    const DEBUG_MODE = true;

    protected function configure()
    {
        $this
            ->setName('integrate')
            ->setDescription('Create Moneybird invoice from Toggl entries')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_config = $this->getConfigValues();
        $this->_toggl = $this->getTogglApi();

        $projects = $this->_toggl->getProjects(array());

        print_r($projects);exit;

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Choose which project you want to find entries for.',
            array('Project 1', 'Project 2', 'Project 3'),
            0
        );
        $question->setErrorMessage('Project is invalid.');

        $project = $helper->ask($input, $output, $question);
        $output->writeln('You have just selected: '.$project);
    }

    private function getConfigValues()
    {
        if (file_exists('config.yml')) {
            try {
                $yaml = new Parser();
                return $yaml->parse(file_get_contents(self::CONFIG_FILE));
            } catch (ParseException $e) {
                printf("Unable to parse the YAML string: %s", $e->getMessage());
                die();
            }
        } else {
            die(self::CONFIG_FILE . ' does not exist. Please copy ' . self::CONFIG_FILE . '.example to ' . self::CONFIG_FILE . ' and fill the fields.');
        }
    }

    private function getTogglApi()
    {
        return TogglClient::factory(array('api_key' => $this->_config['toggl_token'], 'debug' => self::DEBUG_MODE));
    }

    private function getMoneybirdApi()
    {
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => $this->_config['moneybird_clientid'],
            'clientSecret' => $this->_config['moneybird_clientsecret'],
            'redirectUri' => 'http://togglmoneybird.dev/redirect',
            'urlAuthorize' => 'http://togglmoneybird.dev/authorize',
            'urlAccessToken' => 'http://togglmoneybird.dev/token',
            'urlResourceOwnerDetails' => 'http://togglmoneybird.dev/resource'
        ]);

        try {
            // Try to get an access token using the client credentials grant.
            $accessToken = $provider->getAccessToken('client_credentials');
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            // Failed to get the access token
            die($e->getMessage());
        }
    }
}