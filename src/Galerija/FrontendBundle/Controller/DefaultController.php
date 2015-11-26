<?php

namespace Galerija\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('GalerijaFrontendBundle:Default:index.html.twig');
    }
}
