<?php

namespace Sebk\ProcessesSubmissionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class testProcess
{
    public function testProcess($echo)
    {
        echo "Start $echo\n";
        sleep(2);
        echo "End $echo\n";
    }
}

class TestCommand extends ContainerAwareCommand
{
    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('processes:test')
            ->setDescription('Test dixheure')
            ->setHelp(<<<EOT
This command allow you to run unit test of dixheure.

Usage: app/console dixheure:test
EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processesSubmission = $this->getContainer()->get("sebk_processes_submition_bundle_processes_submission");
        for($i=0; $i<20; $i++) {
            $test = new testProcess();
            $job = $this
                ->getContainer()
                ->get("sebk_processes_submition_bundle_business_factory")
                ->get("Job")
                ->setMethodCall($test, "testProcess", array($i));

            $processesSubmission->addToQueue($job);
        }

        $processesSubmission->flushQueue();
    }
} 