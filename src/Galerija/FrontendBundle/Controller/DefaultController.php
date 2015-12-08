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
            new HttpGetJson('http://awesome.dev' . $this->get('router')->generate('get_image_all'),
                ['storageId' => $this->get('galerija_api.images.storage')->getStorageId()])
        );

        return $this->render('GalerijaFrontendBundle:Default:index.html.twig', ['images' => $images]);
    }

    public function dropboxAction()
    {
        if($this->get('galerija_api.images.storage')->getStorageId() !== 'local') {
            $this->get('session')->remove('dropbox');
            return $this->redirectToRoute('galerija_frontend_homepage');
        }

        $dropboxApi = $this->get('galerija_api.dropbox.oauth');
        $this->get('session')->set('dropbox', ['request' => $dropboxApi->getRequestToken()]);

        return $this->redirect($dropboxApi->getAuthorizeUrl('http://awesome.dev' . $this->get('router')->generate('galerija_dropbox_complete')));
    }

    public function dropboxCompleteAction(Request $request)
    {
        $session = $this->get('session');

        $dropboxApi = $this->get('galerija_api.dropbox.oauth');
        $dropboxApi->setToken($session->get('dropbox')['request']);
        $session->set('dropbox',
            array_merge($session->get('dropbox'), [
                'access' => $dropboxApi->getAccessToken(),
                'uid' => $request->get('uid')
            ])
        );

        return $this->redirectToRoute('galerija_frontend_homepage');
    }
}
