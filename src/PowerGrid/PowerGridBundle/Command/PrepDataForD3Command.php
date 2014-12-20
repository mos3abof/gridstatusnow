<?php

namespace PowerGrid\PowerGridBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use PowerGrid\PowerGridBundle\Entity\Status;

class PrepDataForD3Command extends ContainerAwareCommand
{
	protected function configure()
	{
		$this->setName('power:prepdata')
			->setDescription('Saves current power status to database');
	}


	//
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try
		{
			$current_month = 'november';

			// prepare today's data
			$repository = $this->getContainer()->get('doctrine')->getRepository('PowerGridBundle:Status');


			// global values and configs
			$plus = $this->getContainer()->getParameter('gmt_as_number');
			$web_dir_path = $this->getContainer()->getParameter('web_dir_path');
			$tz = $this->getContainer()->getParameter('default_timezone');;

			$status_to_number = array(
				'Safe'    => 3,
				'Warning' => 2,
				'Danger'  => 1,
				'Unknown' => 1
			);


			// Querying Today's data
			$today_date = new \DateTime('today midnight');
			$yesterday = $today_date->setTimezone(new \DateTimeZone($tz))->modify('+' . $plus . ' hour')->modify('-1 day');

			$today_query = $repository->createQueryBuilder('p')
				->where('p.timestamp > :yesterday')
				->setParameter('yesterday', $yesterday)
				->orderBy('p.id', 'ASC')
				->getQuery();

			$records = $today_query->getResult();

			$averaged_load_result = array();
			foreach($records as $key => $object)
			{
				$day_number = intval($object->getTimestamp()->format('d'));
				$hour_number = intval($object->getTimestamp()->format('H'));
				$status_number = $status_to_number[$object->getStatus()];

				$averaged_load_result[$day_number][$hour_number][1] = 0;
				$averaged_load_result[$day_number][$hour_number][2] = 0;
				$averaged_load_result[$day_number][$hour_number][3] = 0;

				$averaged_load_result[$day_number][$hour_number][$status_number]++;
			}

			$file_content = "day\thour\tvalue\n";

			for($hour = 0; $hour <= count($averaged_load_result[$day_number]); $hour++)
			{
				if(isset($averaged_load_result[$day_number][$hour]))
				{
					$hour_value = array_search(max($averaged_load_result[$day_number][$hour]), $averaged_load_result[$day_number][$hour]);

					$file_content .= "1\t" . ($hour + 1) . "\t" . $hour_value . "\n";
				}
			}

			$file_content .= "34\t10\t3\n";
			$file_content .= "34\t11\t2\n";
			$file_content .= "34\t12\t1\n";

			print "Today Data\n";
			print $file_content;
			print "\n\n";

			$fs = new Filesystem();

			try
			{
				$fs->dumpFile($web_dir_path . '/today.tsv', $file_content);
			}
			catch(IOExceptionInterface $e)
			{
				echo "An error occurred while creating your directory at " . $e->getPath();
			}


			// Querying data for current month
			$first_day_in_month = new \DateTime('midnight first day of this month');
			$first_day_in_month = $first_day_in_month->setTimezone(new \DateTimeZone($tz))->modify('+' . $plus . ' hour');
			$query = $repository->createQueryBuilder('p')
				->where('p.timestamp >= :first_day')
				->setParameter('first_day', $first_day_in_month)
				->orderBy('p.id', 'ASC')
				->getQuery();

			$records = $query->getResult();

			$averaged_load_result = array();
			foreach($records as $key => $object)
			{
				$day_number = intval($object->getTimestamp()->format('d'));
				$hour_number = intval($object->getTimestamp()->format('H'));
				$status_number = $status_to_number[$object->getStatus()];

				$averaged_load_result[$day_number][$hour_number][1] = 0;
				$averaged_load_result[$day_number][$hour_number][2] = 0;
				$averaged_load_result[$day_number][$hour_number][3] = 0;

				$averaged_load_result[$day_number][$hour_number][$status_number]++;
			}

			$file_content = "day\thour\tvalue\n";

			for($day = 1; $day <= count($averaged_load_result); $day++)
			{
				for($hour = 0; $hour <= count($averaged_load_result[$day]); $hour++)
				{
					if(isset($averaged_load_result[$day][$hour]))
					{
						$hour_value = array_search(max($averaged_load_result[$day][$hour]), $averaged_load_result[$day][$hour]);
						$file_content .= $day . "\t" . ($hour + 1) . "\t" . $hour_value . "\n";
					}
				}
			}

			// A dirty hack to make sure the domain has all possible values.
			$file_content .= "34\t10\t3\n";
			$file_content .= "34\t11\t2\n";
			$file_content .= "34\t12\t1\n";

			$fs = new Filesystem();

			try
			{
				$fs->dumpFile($web_dir_path . '/october.tsv', $file_content);
			}
			catch(IOExceptionInterface $e)
			{
				echo "An error occurred while creating your directory at " . $e->getPath();
			}

			print "Month data\n";
			print $file_content;

		}
		catch(\Exception $e)
		{
			$this->getContainer()->get('doctrine')->resetManager();
			$output->writeln(sprintf('<info>[%s]</info> <error>[error]</error> %s', date('G:i:s'), $e->getMessage()));
		}
	}
}
