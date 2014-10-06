<?php

namespace PowerGrid\PowerGridBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use PowerGrid\PowerGridBundle\Entity\Status;

class SaveStatusNowCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this->setName('power:savestatusnow')
			->setDescription('Saves current power status to database');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try
        {
        	$tz = $this->getContainer()->getParameter('default_timezone');;
	        $plus = $this->getContainer()->getParameter('gmt_as_number');
	        $date = new \DateTime("now");
	        $date->setTimezone(new \DateTimeZone($tz))->modify('+' . $plus .' hour');
			$output->writeln(sprintf('<info>[%s]</info> Parsing time is (<info>%s</info>) Egypt local time ...', date('G:i:s'), $date->format('l, F jS h:i:s')));

            $powerGridService = $this->getContainer()->get('power_grid_service');
            $status = $powerGridService->getStatus();

            if( $status )
            {
                $em = $this->getContainer()->get('doctrine')->getManager();

                $record = new Status();
                $record->setStatus($status);
                $record->setTimestamp($date);

                $em->persist($record);
                $em->flush();

                $output->writeln(sprintf('<info>[%s]</info> NEW STATUS (<info>%s</info>) ...', date('G:i:s'), $status));
            }
            else {
                $output->writeln(sprintf('<info>[%s]</info> UNKNOWN STATUS, TRY AGAIN ...', date('G:i:s')));
            }

        } catch (\Exception $e) {
            $this->getContainer()->get('doctrine')->resetManager();
            $output->writeln(sprintf('<info>[%s]</info> <error>[error]</error> %s', date('G:i:s'), $e->getMessage()));
        }
	}
}
