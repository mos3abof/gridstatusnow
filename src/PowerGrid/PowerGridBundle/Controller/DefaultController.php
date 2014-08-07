<?php

namespace PowerGrid\PowerGridBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;


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

			$content = @file_get_contents('http://loadmeter.egyptera.org/MiniCurrentLoadClock3.aspx');
			$content = strtolower($content);
            if (strcmp(trim($content),'') == 0)
            {
                throw new Exception("Source seems to be down", 1);
                
            }

			$power_grid_status = array('status' => 'Unknown Status');

			if(strpos($content, 'images/c3.gif') !== false)
			{
				$status_array = array(
					'status' => 'Danger Zone',
					'cssclass' => 'danger',
				);
			}
			elseif(strpos($content, 'images/c2.gif') !== false)
			{
				$status_array = array(
					'status' => 'Warning Zone',
					'cssclass' => 'warning',
				);
			}
			elseif(strpos($content, 'images/c1.gif') !== false)
			{
				$status_array = array(
					'status' => 'Safe Zone',
					'cssclass' => 'success',
				);
			}
		}
		catch(Exception $e)
		{
			$status_array = array(
				'status' => 'Unknown Status. Source seems to be down!',
				'cssclass' => 'danger',
			);
		}

		return $this->render('PowerGridBundle:Default:index.html.twig', array('status_array' => $status_array));
	}

	public function statusAction()
	{
		$content = @file_get_contents('http://loadmeter.egyptera.org/MiniCurrentLoadClock3.aspx');
		$content = strtolower($content);

		$power_grid_status = array("status" => "Unknown");

		if(strpos($content, 'images/c3.gif') !== false)
		{
			$power_grid_status["status"] = "Danger";
		}
		elseif(strpos($content, 'images/c2.gif') !== false)
		{
			$power_grid_status['status'] = 'Warning';
		}
		elseif(strpos($content, 'images/c1.gif') !== false)
		{
			$power_grid_status['status'] = 'Safe';
		}

        $status = $power_grid_status;
        
        return new JsonResponse($status);
	}

}
