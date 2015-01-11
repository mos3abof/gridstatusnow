<?php

namespace PowerGrid\PowerGridBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DefaultController extends Controller
{
	public function indexAction()
	{

		$tz = $this->container->getParameter('default_timezone');
		$plus = $this->container->getParameter('gmt_as_number');
		$date = new \DateTime("now");
		$date->setTimezone(new \DateTimeZone($tz))->modify('+' . $plus . ' hour');

		$current_date = $date->format('l, F jS');

		$template_vars = array(
			'status' => $this->get('power_grid_service')->getStatus(),
			'today' => $current_date,
			'day_number' => $date->format('d'),
			'month_number' => $date->format('m'),
			'year_number' => $date->format('Y'),
		);
		return $this->render('PowerGridBundle:Default:index.html.twig', $template_vars);
	}

	public function statusAction()
	{
		return new JsonResponse(array('status' => $this->get('power_grid_service')->getStatus()));
	}

	public function historyAction($month = 'november')
	{
		$allowed_months = array(
			'august',
			'september',
			'october',
			'november'
		);

		$year = 2014;

		$month_number = array(
			'january'   => 1,
			'february'  => 2,
			'march'     => 3,
			'april'     => 4,
			'may'       => 5,
			'june'      => 6,
			'july'      => 7,
			'august'    => 8,
			'september' => 9,
			'october'   => 10,
			'november'  => 11,
			'december'  => 12,
		);

		// $first_day_of_month = date('01-m-Y');

		if(!in_array($month, $allowed_months))
		{
			// throw $this->createNotFoundException('Sorry not existing');
			// throw new HttpException(404, "No Data Available For That Month!");
			return $this->render('PowerGridBundle:Default:error.html.twig', array('error_title' => 'No Data Available For That Month!'));

		}

		$d3_days = '[';

		$days_number = cal_days_in_month(CAL_GREGORIAN, $month_number[$month], $year);

		$start_date = $year . '-' . $month_number[$month] . '-01';

		// Give in your own start date
		$start_day = date('z', strtotime($start_date));

		for($i = 0; $i < $days_number; $i++)
		{
			$date = strtotime(date("Y-m-d", strtotime($start_date)) . " $i day");
			$d3_days .= '"' . date('l jS', $date) . '",';
		}
		$d3_days .= '];';

		return $this->render('PowerGridBundle:Default:history.html.twig', array('month' => $month, 'd3_days' => $d3_days));
	}


	public function historytsvAction($year, $month, $day = '')
	{
		$repository = $this->getDoctrine()->getRepository('PowerGridBundle:Status');

		// global values and configs
		$plus = $this->container->getParameter('gmt_as_number');
		$web_dir_path = $this->container->getParameter('web_dir_path');
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
			$last_delimiter = $day_date->setTimezone(new \DateTimeZone($tz))->modify('+1 day')->modify('+' . $plus . ' hour');

			$day_loop = true;

		}
		else
		{
			// Querying data for month
			$first_delimiter = new \DateTime($year . '-' . $month . '-01');
			$first_delimiter->modify('first day of this month');

			$last_delimiter =  new \DateTime($year . '-' . $month . '-01');
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
		// prepare records for a nonth
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
