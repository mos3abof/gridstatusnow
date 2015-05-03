<?php

namespace PowerGrid\PowerGridBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
	public function indexAction()
	{

		$tz = $this->container->getParameter('default_timezone');
		$plus = $this->container->getParameter('gmt_as_number');
		$date = new \DateTime("today");
		$date->setTimezone(new \DateTimeZone($tz))->modify('+' . $plus . ' hour');

		$current_date = $date->format('l, F jS');

		$template_vars = array(
			'status'       => $this->get('power_grid_service')->getStatus(),
			'today'        => $current_date,
			'day_number'   => $date->format('d'),
			'month_number' => $date->format('m'),
			'year_number'  => $date->format('Y'),
		);

		return $this->render('PowerGridBundle:Default:index.html.twig', $template_vars);
	}

	public function statusAction()
	{
		return new JsonResponse(array('status' => $this->get('power_grid_service')->getStatus()));
	}

	public function historyAction($year = '', $month = '')
	{
		if($year == '')
		{
			$year = date('Y');
		}

		if($month == '')
		{
			$month = date('F');
		}

		$month_date = new \Datetime($month . ' ' . $year);

		$d3_days = '[';

		$days_number = cal_days_in_month(CAL_GREGORIAN, $month_date->format('m'), $year);

		$start_date = $month_date->modify('first day of this month')->format('Y-m-d');

		for($i = 0; $i < $days_number; $i++)
		{
			$date = strtotime(date("Y-m-d", strtotime($start_date)) . " $i day");
			$d3_days .= '"' . date('l jS', $date) . '",';
		}
		$d3_days .= '];';

		$template_vars = array(
			'month_name'   => $month,
			'd3_days'      => $d3_days,
			'year_number'  => $year,
			'month_number' => $month
		);

		return $this->render('PowerGridBundle:Default:history.html.twig', $template_vars);
	}

	public function historytsvAction($year, $month, $day = '')
	{
		$repository = $this->getDoctrine()->getRepository('PowerGridBundle:Status');

		// global values and configs
		$plus = $this->container->getParameter('gmt_as_number');

		$tz = $this->container->getParameter('default_timezone');;

		$status_to_number = array(
			'Safe'    => 3,
			'Warning' => 2,
			'Danger'  => 1,
			'Unknown' => 1
		);

		if(isset($day) && $day != '')
		{
			// Querying data for day
			$day_date = new \DateTime($year . '-' . $month . '-' . $day);
			$first_delimiter = $day_date->setTimezone(new \DateTimeZone($tz))->modify('+' . $plus . ' hour');
			$last_delimiter = $first_delimiter;

			$day_loop = true;

		}
		else
		{
			// Querying data for month
			$first_delimiter = new \DateTime($year . '-' . $month . '-01');
			$first_delimiter->modify('first day of this month');

			$last_delimiter = new \DateTime($year . '-' . $month . '-01');
			$last_delimiter->modify('last day of this month');
		}

		// Create a query
		$query = $repository->createQueryBuilder('p')
			->where('p.timestamp BETWEEN :starting AND :ending')
			->setParameter('starting', $first_delimiter->format('Y-m-d 00:00:00'))
			->setParameter('ending', $last_delimiter->format('Y-m-d 23:59:59'))
			->orderBy('p.id', 'ASC')
			->getQuery();

		// Get the query result
		$records = $query->getResult();

		// Initialize an array to process results
		$averaged_load_result = array();

		// If we got any results process them
		if(!$records)
		{
			print 'No results exist!';
			exit;
		}

		$tsv_output = "day\thour\tvalue\n";

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

		// Prepare records for one day
		if(isset($day_loop) && $day_loop == true)
		{
			for($hour = 0; $hour <= count($averaged_load_result[$day_number]); $hour++)
			{
				if(isset($averaged_load_result[$day_number][$hour]))
				{
					$hour_value = array_search(max($averaged_load_result[$day_number][$hour]), $averaged_load_result[$day_number][$hour]);

					$tsv_output .= "1\t" . ($hour + 1) . "\t" . $hour_value . "\n";
				}
			}
		}
		// prepare records for a month
		else
		{
			for($day_record = 1; $day_record <= count($averaged_load_result); $day_record++)
			{
				if(isset($averaged_load_result[$day_record]))
				{
					for($hour = 0; $hour <= count($averaged_load_result[$day_record]); $hour++)
					{
						if(isset($averaged_load_result[$day_record][$hour]))
						{
							$hour_value = array_search(max($averaged_load_result[$day_record][$hour]), $averaged_load_result[$day_record][$hour]);
							$tsv_output .= $day_record . "\t" . ($hour + 1) . "\t" . $hour_value . "\n";
						}
					}
				}
			}
		}

		$tsv_output .= "34\t10\t3\n";
		$tsv_output .= "34\t11\t2\n";
		$tsv_output .= "34\t12\t1\n";

		print $tsv_output;
		exit;
	}

	public function apiAction()
	{
		return $this->render('PowerGridBundle:Default:api.html.twig');
	}

	public function aboutAction()
	{
		return $this->render('PowerGridBundle:Default:about.html.twig');
	}

	public function faqAction()
	{
		return $this->render('PowerGridBundle:Default:faq.html.twig');
	}

	public function contactAction()
	{
		return $this->render('PowerGridBundle:Default:contact.html.twig');
	}
}
