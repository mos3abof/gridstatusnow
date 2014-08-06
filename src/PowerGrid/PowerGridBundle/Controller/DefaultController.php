<?php

namespace PowerGrid\PowerGridBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
	public function indexAction()
	{
		$status_array = array(
			'status' => 'Unknown',
			'cssclass' => 'danger',
		);

		try
		{

			$content = file_get_contents('http://loadmeter.egyptera.org/MiniCurrentLoadClock3.aspx');
			$content = strtolower($content);

			$power_grid_status = array('status' => 'Unknown');

			if(strpos($content, 'images/c3.gif') !== false)
			{
				$status_array = array(
					'status' => 'Danger',
					'cssclass' => 'danger',
				);
			}
			elseif(strpos($content, 'images/c2.gif') !== false)
			{
				$status_array = array(
					'status' => 'Warning',
					'cssclass' => 'warning',
				);
			}
			elseif(strpos($content, 'images/c1.gif') !== false)
			{
				$status_array = array(
					'status' => 'Normal',
					'cssclass' => 'success',
				);
			}
		}
		catch(Exception $e)
		{
			$status_array = array(
				'status' => 'Unknown, source seems to be down',
				'cssclass' => 'danger',
			);
		}

		return $this->render('PowerGridBundle:Default:index.html.twig', array('status_array' => $status_array));
	}

	public function statusAction()
	{
		$content = file_get_contents('http://loadmeter.egyptera.org/MiniCurrentLoadClock3.aspx');
		$content = strtolower($content);

		$power_grid_status = array('status' => 'Unknown');

		if(strpos($content, 'images/c3.gif') !== false)
		{
			$power_grid_status['status'] = 'Danger';
		}
		elseif(strpos($content, 'images/c2.gif') !== false)
		{
			$power_grid_status['status'] = 'Warning';
		}
		elseif(strpos($content, 'images/c1.gif') !== false)
		{
			$power_grid_status['status'] = 'Normal';
		}

        $status = json_encode($power_grid_status);
		return $this->render('PowerGridBundle:Default:status.html.twig', array('status' => $status));
	}

}
