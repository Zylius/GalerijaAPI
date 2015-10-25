<?php

namespace Galerija\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('GalerijaAPIBundle:Default:index.html.twig');
    }
}
