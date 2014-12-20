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

		return $this->render('PowerGridBundle:Default:index.html.twig', array('status' => $this->get('power_grid_service')->getStatus(), 'today' => $current_date));
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

	public function ApiAction()
	{
		return $this->render('PowerGridBundle:Default:api.html.twig');
	}

	public function AboutAction()
	{
		return $this->render('PowerGridBundle:Default:about.html.twig');
	}

	public function FaqAction()
	{
		return $this->render('PowerGridBundle:Default:faq.html.twig');
	}

	public function ContactAction()
	{
		return $this->render('PowerGridBundle:Default:contact.html.twig');
	}
}
