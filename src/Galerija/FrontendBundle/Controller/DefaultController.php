<?php

namespace Galerija\FrontendBundle\Controller;

use Lsw\ApiCallerBundle\Call\HttpGetJson;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $images = $this->get('api_caller')->call(
            new HttpGetJson('http://awesome.dev' . $this->get('router')->generate('get_image_all'), new Request())
        );

        return $this->render('GalerijaFrontendBundle:Default:index.html.twig', ['images' => $images]);
    }
}
