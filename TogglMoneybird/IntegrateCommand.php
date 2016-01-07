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
    const DEBUG_MODE = false;
    const TEST_MODE = true;
    const TIMESTAMP_FORMAT = 'd-m-Y';

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

        $this->_input = $input;
        $this->_output = $output;

        $this->_questionHelper = $this->getHelper('question');

        /* Choose Toggl workspace */
        $workspaceId = $this->getTogglWorkspace();

        /* Choose Toggl project */
        list($projectName, $projectId) = $this->getTogglProject($workspaceId);

        /* Choose date range for Toggl time entries */
        list($dateTo,$dateFrom) = $this->getTogglDateRange();

        /* Choose which time entries to add to the invoice */
        $chosenTimeEntries = $this->getTogglTimeEntries($dateTo,$dateFrom,$projectId);
        print_r($chosenTimeEntries);

        /* Choose Moneybird contact to invoice to */


    }

    private function getTogglWorkspace() {
        $workspacesResults = $this->_toggl->getWorkspaces(array());

        $workspaceId = false;
        if(count($workspacesResults)==1) {
            $workspace = array_pop($workspacesResults);
            $workspaceId = $workspace['id'];
        } elseif(count($workspacesResults) > 1) {
            $workspaces = array();
            foreach ($workspacesResults as $workspaceResult) {
                $workspaces[$workspaceResult['id']] = $workspaceResult['name'];
            }

            $question = new ChoiceQuestion(
                'Choose which Toggl workspace you want to use.',
                array_values($workspaces),
                0
            );
            $question->setErrorMessage('Workspace is invalid.');

            $workspace = $this->_questionHelper->ask($this->_input, $this->_output, $question);
            $this->_output->writeln('You have just selected workspace: ' . $workspace);

            foreach ($workspacesResults as $workspaceResult) {
                if($workspaceResult['name'] == $workspace) {
                    $workspaceId = $workspaceResult['id'];
                }
            }
        }

        if(!$workspaceId) {
            die('No workspace(s) found');
        }

        return $workspaceId;
    }

    private function getTogglProject($workspaceId) {
        $projectsResults = $this->_toggl->getProjects(array('id' => $workspaceId));
        $projects = array();
        foreach($projectsResults as $projectResult) {
            $projects[$projectResult['id']] = $projectResult['name'];
        }

        if(self::TEST_MODE) {
            $projects = array_slice($projects, 0, 10);
        }

        $question = new ChoiceQuestion(
            'Choose which project you want to find entries for.',
            array_values($projects),
            0
        );
        $question->setErrorMessage('Project is invalid.');

        $project = $this->_questionHelper->ask($this->_input, $this->_output, $question);
        $this->_output->writeln('You have just selected project: ' . $project);

        $projectId = false;
        foreach($projectsResults as $projectResult) {
            if($projectResult['name'] == $project) {
                $projectId = $projectResult['id'];
            }
        }

        return array($project,$projectId);
    }

    private function getTogglDateRange() {
        $dateFromDefault = date(self::TIMESTAMP_FORMAT, strtotime('-1 month'));
        $question = new Question('From which date do you want to find entries? [' . $dateFromDefault . '] ', $dateFromDefault);
        $question->setValidator(function ($answer) {
            if (date(self::TIMESTAMP_FORMAT, strtotime($answer)) != $answer) {
                throw new \RuntimeException(
                    'Input format should be ' . self::TIMESTAMP_FORMAT
                );
            }

            return $answer;
        });
        $dateFrom = $this->_questionHelper->ask($this->_input, $this->_output, $question);

        $dateToDefault = date(self::TIMESTAMP_FORMAT);
        $question = new Question('Until which date do you want to find entries? [' . $dateToDefault . '] ', $dateToDefault);
        $question->setValidator(function ($answer) {
            if (date(self::TIMESTAMP_FORMAT, strtotime($answer)) != $answer) {
                throw new \RuntimeException(
                    'Input format should be ' . self::TIMESTAMP_FORMAT
                );
            }

            return $answer;
        });
        $dateTo = $this->_questionHelper->ask($this->_input, $this->_output, $question);

        $this->_output->writeln('Looking for entries from ' . $dateFrom . ' to ' . $dateTo);

        $dateTo = date('c', strtotime($dateTo . ' 23:59'));
        $dateFrom = date('c', strtotime($dateFrom));

        return array($dateTo, $dateFrom);
    }

    private function getTogglTimeEntries($dateTo, $dateFrom, $projectId) {
        $timeEntriesResults = $this->_toggl->getTimeEntries(array(
            'start_date' => $dateFrom,
            'end_date' => $dateTo,
        ));

        $timeEntries = array();
        foreach($timeEntriesResults as $timeEntriesResult) {
            if(!isset($timeEntriesResult['pid']) || $timeEntriesResult['pid'] != $projectId) continue;
            $timeEntries[$timeEntriesResult['id']] = $timeEntriesResult['description'] . ' - duration: ' . gmdate("H:i:s", $timeEntriesResult['duration']);
        }

        if(self::TEST_MODE) {
            $timeEntries = array_slice($timeEntries, 0, 20);
        }

        $question = new ChoiceQuestion(
            'Choose which time entries you want to invoice.',
            array_values($timeEntries),
            0
        );
        $question->setMultiselect(true);
        $question->setErrorMessage('Time entry input is invalid.');

        $timeEntryValues = $this->_questionHelper->ask($this->_input, $this->_output, $question);
        $chosenTimeEntries = array_intersect($timeEntries, $timeEntryValues);

        foreach($chosenTimeEntries as $chosenTimeEntry) {
            if(stripos($chosenTimeEntry, 'fix')!==false || stripos($chosenTimeEntry, 'bug')!==false) {
                $this->_output->writeln('Caution; you are about to invoice a time entry that indicates it is a bug fix: ' . $chosenTimeEntry);
            }
        }

        return $chosenTimeEntries;
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