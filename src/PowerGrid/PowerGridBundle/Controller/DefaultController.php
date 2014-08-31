<?php

namespace PowerGrid\PowerGridBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
	public function indexAction()
	{
		return $this->render('PowerGridBundle:Default:index.html.twig', array('status' => $this->get('power_grid_service')->getStatus()));
	}

	public function statusAction()
	{
		return new JsonResponse( array('status' => $this->get('power_grid_service')->getStatus()));
	}

	public function historyAction($month = 'august')
	{
		$allowed_months = array(
			'august',
			'september'
		);

		$year = 2014;

		$month_number = array(
			'januray' => 1,
			'february' => 2,
			'march' => 3,
			'april' => 4,
			'may' => 5,
			'june' => 6,
			'july' => 7,
			'august' => 8,
			'september' => 9,
			'october' => 10,
			'november' => 11,
			'december' => 12,
		);

		// $first_day_of_month = date('01-m-Y');

		if(!in_array($month, $allowed_months))
		{
			throw new \Exception('We don\'t have records for that month!');
		}

		$d3_days = '[';
        
		$days_number = cal_days_in_month(CAL_GREGORIAN, $month_number[$month], $year);
		
		$start_date = $year . '-' . $month_number[$month] . '-01';
		// Give in your own start date
		$start_day = date('z', strtotime($start_date));

		for($i = 0; $i < $days_number; $i++)
		{
			$date = strtotime(date("Y-m-d", strtotime($start_date)) . " $i day");
			$d3_days .=  '"' . date('l jS', $date) . '",';
		}
		$d3_days .= ']';

		return $this->render('PowerGridBundle:Default:history.html.twig', array('month' => $month, 'd3_days' => $d3_days));
	}

}
