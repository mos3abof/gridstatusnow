<?php

namespace PowerGrid\PowerGridBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
	public function indexAction()
	{
        return $this->render('PowerGridBundle:Default:index.html.twig', array(
            'status' => $this->get('power_grid_service')->getStatus()
        ));
	}

	public function statusAction()
	{
        return new JsonResponse(array(
            'status' => $this->get('power_grid_service')->getStatus()
        ));
	}
    
    
    public function historyAction()
    {
        return $this->render('PowerGridBundle:Default:history.html.twig');
    }

}
